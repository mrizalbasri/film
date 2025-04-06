<?php
// genre_process.php - Place this in an "includes" folder

// Database connection
require_once "../config/db_connect.php";

// Add new genre
if (isset($_POST['add_genre'])) {
    $genre_name = mysqli_real_escape_string($conn, $_POST['genre_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $query = "INSERT INTO genre (genre_name, description) VALUES ('$genre_name', '$description')";
    
    if (mysqli_query($conn, $query)) {
        // Success
        header("Location: ../genre.php?status=added");
        exit();
    } else {
        // Error
        header("Location: ../genre.php?status=error&message=" . urlencode(mysqli_error($conn)));
        exit();
    }
}

// Update existing genre
if (isset($_POST['update_genre'])) {
    $genre_id = mysqli_real_escape_string($conn, $_POST['genre_id']);
    $genre_name = mysqli_real_escape_string($conn, $_POST['genre_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $query = "UPDATE genre SET genre_name = '$genre_name', description = '$description' WHERE genre_id = $genre_id";
    
    if (mysqli_query($conn, $query)) {
        // Success
        header("Location: ../genre.php?status=updated");
        exit();
    } else {
        // Error
        header("Location: ../genre.php?status=error&message=" . urlencode(mysqli_error($conn)));
        exit();
    }
}

// Delete genre
if (isset($_POST['delete_genre'])) {
    $genre_id = mysqli_real_escape_string($conn, $_POST['genre_id']);
    
    // Set genre_id to NULL for associated films
    $update_films = "UPDATE film SET genre_id = NULL WHERE genre_id = $genre_id";
    mysqli_query($conn, $update_films);
    
    // Delete the genre
    $delete_query = "DELETE FROM genre WHERE genre_id = $genre_id";
    
    if (mysqli_query($conn, $delete_query)) {
        // Success
        header("Location: ../genre.php?status=deleted");
        exit();
    } else {
        // Error
        header("Location: ../genre.php?status=error&message=" . urlencode(mysqli_error($conn)));
        exit();
    }
}

// Close connection
mysqli_close($conn);
?>