<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Connect to MongoDB
    $client = new MongoDB\Client("mongodb://localhost:27017");

    // Select the new database and collections
    $db = $client->Movies; // Change this to your new database name
    $collectionMovies = $db->movies_1;
    $collectionRoles = $db->movies_4;
    $collectionArtists = $db->movies_3;

    echo "Connected to database: NewMoviesDatabase<br><br>";

    // Query 1: Fetch movie details for movieID 11
    $targetMovieId = "11";
    $movieDocuments = $collectionMovies->find(['data.movieid' => $targetMovieId]);

    $movieCount = 0;
    foreach ($movieDocuments as $movieDocument) {
        foreach ($movieDocument['data'] as $movie) {
            if ($movie['movieid'] == $targetMovieId) {
                echo "Title: " . $movie['title'] . "<br><br>";
                $movieCount++;
            }
        }
    }

    if ($movieCount === 0) {
        throw new Exception("Movie with ID $targetMovieId not found.");
    }

    // Query 2: Fetch role details for movieID 11
    $rolesDocuments = $collectionRoles->find(['data.movieid' => $targetMovieId]);

    $roleCount = 0;
    foreach ($rolesDocuments as $rolesDocument) {
        foreach ($rolesDocument['data'] as $role) {
            if ($role['movieid'] == $targetMovieId) {
                echo "Role: " . $role['roleName'] . "<br>";

                $targetArtistId = $role['artistid'];

                // Query 3: Fetch artist details
                $artistDocument = $collectionArtists->findOne(['data.artistid' => $targetArtistId]);

                if ($artistDocument && isset($artistDocument['data']['name']) && isset($artistDocument['data']['surname'])) {
                    echo "Name: " . $artistDocument['data']['name'] . " " . $artistDocument['data']['surname'] . "<br>";
                } else {
                    echo "Artist details not found for artistid " . $targetArtistId . ".<br>";
                }

                $roleCount++;
            }
        }
    }

    if ($roleCount === 0) {
        echo "Role details not found for movieid " . $targetMovieId . ".<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
