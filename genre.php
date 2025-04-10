<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        /* Keeping the original sidebar styles */
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
        
        /* New styles for improved UI */
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Content area styling */
        .content-wrapper {
            padding: 20px;
        }
        
        /* Header styling */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        /* Action buttons styling to match screenshot */
        .btn-action-edit {
            background-color: #ffc107;
            color: #212529;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-weight: 500;
            margin-right: 8px;
            transition: all 0.2s;
            display: block;
            width: 100%;
            margin-bottom: 8px;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-action-delete {
            background-color: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-weight: 500;
            transition: all 0.2s;
            display: block;
            width: 100%;
            text-align: center;
            text-decoration: none;
        }

        .btn-action-edit:hover {
            background-color: #e0a800;
            color: #212529;
        }
        
        .btn-action-delete:hover {
            background-color: #c82333;
            color: #fff;
        }
        
        /* Add button styling */
    
        
        /* Table styling */
        .table-container {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        /* Action column styling */
        .action-column {
            width: 120px;
        }
        
        /* Genre count badge */
        .genre-count {
            background-color: #0d6efd;
            color: white;
            font-size: 0.875rem;
            padding: 4px 10px;
            border-radius: 50px;
            font-weight: 500;
        }
        
        /* Modals styling */
        .modal-content {
            border: none;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e9ecef;
        }
        
        /* Form controls */
        .form-control {
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
    
            <!-- Sidebar - Kept unchanged -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4>Film Dashboard</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="filim.php" id="films-link">
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
                <!-- User section at bottom of sidebar -->
<div class="border-top my-4"></div>
<div class="user-profile p-3">
    <?php
    // Assuming you have a session with user information
  
    if(isset($_SESSION['username'])) {
        $user_name = htmlspecialchars($_SESSION['username']);
        $user_email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';
    ?>
        <div class="d-flex align-items-center mb-3">
            <div class="flex-shrink-0">
                <i class="fas fa-user-circle fa-2x text-primary"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="mb-0"><?= $user_name; ?></h6>
                <?php if(!empty($user_email)): ?>
                    <small class="text-muted"><?= $user_email; ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-grid gap-2">
            <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-user-cog"></i> Profile
            </a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    <?php } else { ?>
        <div class="text-center">
            <i class="fas fa-user-circle fa-3x text-muted mb-2"></i>
            <p>Please login to continue</p>
            <a href="login.php" class="btn btn-sm btn-primary">Login</a>
        </div>
    <?php } ?>
</div>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="content-wrapper">
                    <!-- Page header -->
                    <div class="page-header">
                        <h1></i> Kelola Genre Film</h1>
                        <button type="button" class="btn  btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addGenreModal">
                            <i class="bi bi-plus-lg"></i> Tambah Genre Baru
                        </button>
                    </div>
                    
                    <!-- Alert Messages -->
                    <?php if(isset($message)): ?>
                    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                        <i class="bi <?php echo $alertClass == 'alert-success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Genre List Section -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Genre</h5>
                            <span class="genre-count"><?php echo mysqli_num_rows($result); ?> genre</span>
                        </div>
                        
                        <!-- Genre Table -->
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#ID</th>
                                        <th scope="col">NAMA GENRE</th>
                                        <th scope="col">DESKRIPSI</th>
                                        <th scope="col" class="text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($result) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['genre_id']; ?></td>
                                            <td><span class="fw-semibold"><?php echo $row['genre_name']; ?></span></td>
                                            <td><?php echo $row['description']; ?></td>
                                            <td class="action-column">
                                                <button class="btn-action-edit" data-bs-toggle="modal" data-bs-target="#editGenreModal<?php echo $row['genre_id']; ?>">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </button>
                                                <a href="?delete=<?php echo $row['genre_id']; ?>" class="btn-action-delete" onclick="return confirm('Apakah anda yakin ingin menghapus genre ini?')">
                                                    <i class="bi bi-trash me-1"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">Tidak ada data genre. Silakan tambahkan genre baru.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modals for each genre -->
    <?php 
    // Reset the result pointer to the beginning
    mysqli_data_seek($result, 0);
    while($row = mysqli_fetch_assoc($result)): 
    ?>
    <div class="modal fade" id="editGenreModal<?php echo $row['genre_id']; ?>" tabindex="-1" aria-labelledby="editGenreModalLabel<?php echo $row['genre_id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editGenreModalLabel<?php echo $row['genre_id']; ?>">
                        <i class="bi bi-pencil-square me-2"></i>Edit Genre
                    </h5>
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
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $row['description']; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Batal
                        </button>
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    
    <!-- Add Genre Modal -->
    <div class="modal fade" id="addGenreModal" tabindex="-1" aria-labelledby="addGenreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addGenreModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Genre Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_genre_name" class="form-label">Nama Genre</label>
                            <input type="text" class="form-control" id="add_genre_name" name="genre_name" placeholder="Masukkan nama genre" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="add_description" name="description" rows="4" placeholder="Masukkan deskripsi genre" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Batal
                        </button>
                        <button type="submit" name="add" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>