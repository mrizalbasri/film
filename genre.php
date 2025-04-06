<?php
// Database connection
include 'database.php';

// Function to sanitize input data
function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Create operation
if (isset($_POST['add'])) {
    $genre_name = sanitizeInput($_POST['genre_name']);
    $description = sanitizeInput($_POST['description']);
    
    $query = "INSERT INTO genres (genre_name, description) VALUES ('$genre_name', '$description')";
    
    if (mysqli_query($conn, $query)) {
        $message = "Genre berhasil ditambahkan";
        $alertClass = "alert-success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $alertClass = "alert-danger";
    }
}

// Update operation
if (isset($_POST['update'])) {
    $genre_id = sanitizeInput($_POST['genre_id']);
    $genre_name = sanitizeInput($_POST['genre_name']);
    $description = sanitizeInput($_POST['description']);
    
    $query = "UPDATE genres SET genre_name='$genre_name', description='$description' WHERE genre_id=$genre_id";
    
    if (mysqli_query($conn, $query)) {
        $message = "Genre berhasil diperbarui";
        $alertClass = "alert-success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $alertClass = "alert-danger";
    }
}

// Delete operation
if (isset($_GET['delete'])) {
    $genre_id = sanitizeInput($_GET['delete']);
    
    $query = "DELETE FROM genres WHERE genre_id=$genre_id";
    
    if (mysqli_query($conn, $query)) {
        $message = "Genre berhasil dihapus";
        $alertClass = "alert-success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $alertClass = "alert-danger";
    }
}

// Read operation
$query = "SELECT * FROM genres ORDER BY genre_id";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Genre Film</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
</head>
<body>
    
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Film</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
             .sidebar {
            min-height: 100vh;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
    
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4>Film Dashboard</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link " href="filim.php" id="films-link">
                                <i class="fas fa-film"></i> Film
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="genre.php" id="genres-link">
                                <i class="fas fa-tags"></i> Genre
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="watch-status.php" id="status-link">
                                <i class="fas fa-eye"></i> Watch Status
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

    <div class="col-md-10 mt-5">
        <h2 class="text-center mb-4">Kelola Genre Film</h2>
        
        <?php if(isset($message)): ?>
        <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Button to trigger Add Modal -->
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGenreModal">
                <i class="bi bi-plus-circle"></i> Tambah Genre Baru
            </button>
        </div>
        
        <!-- Genre List Table -->
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nama Genre</th>
                                <th scope="col">Deskripsi</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['genre_id']; ?></td>
                                <td><?php echo $row['genre_name']; ?></td>
                                <td><?php echo $row['description']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editGenreModal<?php echo $row['genre_id']; ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <a href="?delete=<?php echo $row['genre_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah anda yakin ingin menghapus genre ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal for each genre -->
                            <div class="modal fade" id="editGenreModal<?php echo $row['genre_id']; ?>" tabindex="-1" aria-labelledby="editGenreModalLabel<?php echo $row['genre_id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title" id="editGenreModalLabel<?php echo $row['genre_id']; ?>">Edit Genre</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="" method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="genre_id" value="<?php echo $row['genre_id']; ?>">
                                                <div class="mb-3">
                                                    <label for="genre_name" class="form-label">Nama Genre</label>
                                                    <input type="text" class="form-control" id="genre_name" name="genre_name" value="<?php echo $row['genre_name']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="description" class="form-label">Deskripsi</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $row['description']; ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Genre Modal -->
    <div class="modal fade" id="addGenreModal" tabindex="-1" aria-labelledby="addGenreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addGenreModalLabel">Tambah Genre Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_genre_name" class="form-label">Nama Genre</label>
                            <input type="text" class="form-control" id="add_genre_name" name="genre_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="add_description" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>