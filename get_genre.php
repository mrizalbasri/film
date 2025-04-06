<?php
// get_genre.php - Place this in an "includes" folder

// Set header for JSON response
header('Content-Type: application/json');

// Database connection
require_once "../config/db_connect.php";

// Check if ID is provided
if (isset($_GET['id'])) {
    $genre_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Query to get genre details
    $query = "SELECT * FROM genre WHERE genre_id = $genre_id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $genre = mysqli_fetch_assoc($result);
        echo json_encode($genre);
    } else {
        echo json_encode(['error' => 'Genre not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}

// Close connection
mysqli_close($conn);
?>