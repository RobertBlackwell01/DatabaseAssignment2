<?php
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\BSON\ObjectId;

try {
    // Connect to MongoDB
    $client = new MongoDB\Client("mongodb://localhost:27017");

    // Select the database and collections
    $db = $client->Movies;
    $collectionMovies = $db->movies_1;
    $collectionRoles = $db->movies_4;
    $collectionArtists = $db->movies_3;

    // Query 1: Find specific movie with roles and actors
    
    // The specific movieid we want to find
    $targetMovieId = "11";

    // Find the document that contains the movie data
    $document1 = $collectionMovies->findOne();

    // Check if the 'data' field exists and is a BSONArray
    if (isset($document1['data']) && $document1['data'] instanceof MongoDB\Model\BSONArray) {
        echo "Query 1: Specfic movie with roles and actors:<br>";
        echo "<br>";
        // Iterate over the movies in the 'data' field
        foreach ($document1['data'] as $movie) {
            // Check if 'movieid' matches the targetMovieId
            if (isset($movie['movieid']) && $movie['movieid'] == $targetMovieId) {
                // Print the title of the movie
                echo "Title: " . $movie['title'] . "<br>";
                echo "<br>";
                break;
            }
        }
    }
    
    // Find the document that contains the roles
    $document2 = $collectionRoles->findOne();

    // Check if the 'data' field exists and is a BSONArray
      
    if (isset($document2['data']) && $document2['data'] instanceof MongoDB\Model\BSONArray) {
        // Iterate over the movies in the 'data' field
        foreach ($document2['data'] as $movie) {
            // Check if 'movieid' matches the targetMovieId
            
            if (isset($movie['movieid']) && $movie['movieid'] == $targetMovieId) {
                // Print the role name
                echo "Role: " . $movie['roleName'] . "<br>";
                
                // Set the target artistid
                $targetArtistId = $movie['artistid'];

                // Query the movies_3 collection to get the artist details
                $document3 = $collectionArtists->findOne(['data.artistid' => $targetArtistId]);

                // Check if the document is found
                if ($document3) {
                    // Extract the data array
                    $data = $document3['data'];

                    // Initialize a variable to track if the artist is found
                    $artistFound = false;

                    // Search for the document with artistid within the data array
                    foreach ($data as $item) {
                        if ($item['artistid'] === $targetArtistId) {
                            // Artist details found
                            echo "Name: " . $item['name'] . " " . $item['surname'] . "<br>";
                            $artistFound = true; // Set the flag to true
                            break; // Stop searching once the artist is found
                        }
                    }

                    // If the artist is not found, display a message
                    if (!$artistFound) {
                        echo "Artist details not found for artistid " . $targetArtistId . ".<br>";
                    }
                } else {
                    echo "Error: Document containing data array not found in collectionArtists.<br>";
                }
            }
        }
    } else {
        echo "Warning: 'data' field is not found or is not an array in collectionRoles.<br>";
    }
    echo "<br>---------------------------------------------<br>";

    // Query 2: Use aggregation to fetch artist details and the number of movies they have acted in
    // Manually specify the artist ID
    $manualArtistId = "2"; // Replace with the desired artist ID

    $pipeline = [
        ['$unwind' => '$data'],
        ['$match' => ['data.artistid' => $manualArtistId]],
        ['$lookup' => [
            'from' => 'movies_4',
            'let' => ['artistid' => '$data.artistid'],
            'pipeline' => [
                ['$unwind' => '$data'],
                ['$match' => ['$expr' => ['$eq' => ['$data.artistid', '$$artistid']]]],
                ['$group' => ['_id' => '$data.movieid']]
            ],
            'as' => 'roles'
        ]],
        ['$addFields' => [
            'numberOfMovies' => ['$size' => '$roles']
        ]],
        ['$project' => [
            'artistid' => '$data.artistid',
            'name' => '$data.name',
            'surname' => '$data.surname',
            'numberOfMovies' => 1
        ]]
    ];

    $artistAggregation = $collectionArtists->aggregate($pipeline)->toArray();

    if (count($artistAggregation) > 0) {
        echo "Query 2: Number of movies for a specific actor :<br>";
        echo "<br>";
        $artist = $artistAggregation[0];
        echo "Name: " . $artist['name'] . " " . $artist['surname'] . "<br>";
        echo "Number of movies: " . $artist['numberOfMovies'] . "<br>";
    } else {
        echo "Artist details not found for artistid " . $manualArtistId . ".<br>";
    }
    
    echo "<br>---------------------------------------------<br>";
    
    // Query 3: List all movie titles in the movies_1 collection
    
   // Find the document containing the artist data
    $document = $collectionMovies->findOne();

    // Check if the document is found
    if ($document && isset($document['data'])) {
        echo "Query 3: List of all movie titles in database :<br>";
        echo "<br>";
        // Iterate over the data array
        foreach ($document['data'] as $title) {
            if (isset($title['title'])) {
                echo $title['title'] . "<br>";
            } else {
                echo "Error: Missing tilte in document: <pre>" . json_encode($artist, JSON_PRETTY_PRINT) . "</pre><br>";
            }
        }
    } else {
        echo "Error: Document not found or missing 'data' field.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
