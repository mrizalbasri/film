<?php
//database connection

$servername = "localhost";
$username = "root";
$password = "";
$database = "db_filim";

// Buat koneksi
$conn = mysqli_connect($servername, $username, $password, $database);

// Periksa koneksi
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
