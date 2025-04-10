<?php
// Koneksi ke database
include 'database.php';

// Inisialisasi variabel untuk pencarian
$search = "";
if(isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']); // Mencegah SQL injection
}

// Proses hapus film
if(isset($_POST['delete_film'])) {
    $film_id = isset($_POST['film_id']) ? intval($_POST['film_id']) : 0; // Konversi ke integer & periksa keberadaan
    
    if($film_id <= 0) {
        header("Location: filim.php?message=ID Film tidak valid");
        exit();
    }
    
    // Periksa apakah film ada di database
    $check_query = "SELECT film_id FROM Films WHERE film_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $film_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($check_result) == 0) {
        header("Location: filim.php?message=ID Film tidak valid");
        exit();
    }
    
    // Aktifkan error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        // Hapus dari tabel WatchStatus
        $delete_watch = "DELETE FROM WatchStatus WHERE film_id = ?";
        $stmt = mysqli_prepare($conn, $delete_watch);
        mysqli_stmt_bind_param($stmt, "i", $film_id);
        mysqli_stmt_execute($stmt);
        
        // Hapus dari tabel film_actors (perbaiki nama tabel)
        // Periksa dulu apakah tabel ada
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'film_actors'");
        if(mysqli_num_rows($check_table) > 0) {
            $delete_actors = "DELETE FROM film_actors WHERE film_id = ?";
            $stmt = mysqli_prepare($conn, $delete_actors);
            mysqli_stmt_bind_param($stmt, "i", $film_id);
            mysqli_stmt_execute($stmt);
        } else {
            // Coba dengan nama tabel alternatif
            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'Film_Actors'");
            if(mysqli_num_rows($check_table) > 0) {
                $delete_actors = "DELETE FROM Film_Actors WHERE film_id = ?";
                $stmt = mysqli_prepare($conn, $delete_actors);
                mysqli_stmt_bind_param($stmt, "i", $film_id);
                mysqli_stmt_execute($stmt);
            }
            // Jika tidak ada tabel actors, lanjutkan saja tanpa error
        }
        
        // Hapus dari tabel Films
        $delete_film = "DELETE FROM Films WHERE film_id = ?";
        $stmt = mysqli_prepare($conn, $delete_film);
        mysqli_stmt_bind_param($stmt, "i", $film_id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaksi
        mysqli_commit($conn);
        
        header("Location: filim.php?message=Film berhasil dihapus");
        exit();
    } catch (Exception $e) {
        // Rollback transaksi jika ada error
        mysqli_rollback($conn);
        // Tampilkan pesan error yang lebih informatif
        $error_message = $e->getMessage();
        $error = "Gagal menghapus film: " . $error_message;
        
        // Log error untuk debugging
        error_log("Error menghapus film ID $film_id: " . $error_message);
    }
}

// Query untuk mengambil data film dengan prepared statement
$query = "SELECT f.film_id, f.title, f.release_year, g.genre_name, f.director, 
         f.duration, f.rating, f.poster_url 
         FROM Films f
         JOIN Genres g ON f.genre_id = g.genre_id";

// Tambahkan kondisi pencarian jika ada
if(!empty($search)) {
    $query .= " WHERE f.title LIKE ? OR f.director LIKE ? OR g.genre_name LIKE ?";
    $search_param = "%$search%";
    
    $stmt = mysqli_prepare($conn, $query . " ORDER BY f.date_added DESC");
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
} else {
    $stmt = mysqli_prepare($conn, $query . " ORDER BY f.date_added DESC");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

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
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .card-img-top {
            height: 300px;
            object-fit: cover;
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
                <a class="nav-link active" href="filim.php" id="films-link">
                    <i class="fas fa-film"></i> Film
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="genre.php" id="genres-link">
                    <i class="fas fa-tags"></i> Genre
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="watch-status.php" id="status-link">
                    <i class="fas fa-eye"></i> Watch Status
                </a>
            </li>
        </ul>
        
     <!-- User section at bottom of sidebar -->
<div class="border-top my-4"></div>
<div class="user-profile p-3">
    <?php
    // Assuming you have a session with user information
    session_start(); // If not already started elsewhere
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
</div>
            
            <!-- Konten Utama -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Daftar Film</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="addfilm.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Tambah Film
                        </a>
                    </div>
                </div>
                
                <?php if(isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Form Pencarian -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Cari judul, sutradara, atau genre..." value="<?= htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary">Cari</button>
                            <?php if(!empty($search)): ?>
                                <a href="filim.php" class="btn btn-outline-secondary ms-2">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Tampilan Grid Film -->
                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($film = mysqli_fetch_assoc($result)): ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <?php if(!empty($film['poster_url'])): ?>
                                        <img src="<?= htmlspecialchars($film['poster_url']); ?>" class="card-img-top" alt="<?= htmlspecialchars($film['title']); ?>" onerror="this.src='assets/images/default-poster.jpg';">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 300px;">
                                            <i class="bi bi-film" style="font-size: 5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($film['title']); ?></h5>
                                        <p class="card-text">
                                            <span class="badge bg-primary"><?= htmlspecialchars($film['genre_name']); ?></span>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($film['release_year']); ?></span>
                                        </p>
                                        <p class="card-text">
                                            <small class="text-muted">Sutradara: <?= htmlspecialchars($film['director']); ?></small>
                                        </p>
                                        <p class="card-text">
                                            <small class="text-muted">Durasi: <?= htmlspecialchars($film['duration']); ?> menit</small>
                                        </p>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-star-fill text-warning me-1"></i>
                                            <span><?= htmlspecialchars($film['rating']); ?>/10</span>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between">
                                            <a href="view_film.php?id=<?= $film['film_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            <a href="edit_film.php?id=<?= $film['film_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $film['film_id']; ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal Konfirmasi Hapus -->
                            <div class="modal fade" id="deleteModal<?= $film['film_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            Apakah Anda yakin ingin menghapus film <strong><?= htmlspecialchars($film['title']); ?></strong>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <form action="" method="POST">
                                                <input type="hidden" name="film_id" value="<?= $film['film_id']; ?>">
                                                <button type="submit" name="delete_film" class="btn btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                Tidak ada film yang ditemukan.
                                <?php if(!empty($search)): ?>
                                    <a href="filim.php" class="alert-link">Tampilkan semua film</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>