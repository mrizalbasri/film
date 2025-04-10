<?php
// Koneksi ke database
include 'database.php';

// Periksa apakah ada ID film yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: filim.php?message=ID Film tidak valid");
    exit();
}

$film_id = intval($_GET['id']);

// Cek apakah form dikirim untuk update
if (isset($_POST['update_film'])) {
    // Ambil data dari form
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $release_year = intval($_POST['release_year']);
    $genre_id = intval($_POST['genre_id']);
    $director = mysqli_real_escape_string($conn, $_POST['director']);
    $duration = intval($_POST['duration']);
    $rating = floatval($_POST['rating']);
    $poster_url = mysqli_real_escape_string($conn, $_POST['poster_url']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Aktifkan error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        // Update data film
        $query = "UPDATE Films SET 
                 title = ?, 
                 release_year = ?, 
                 genre_id = ?, 
                 director = ?, 
                 duration = ?, 
                 rating = ?, 
                 poster_url = ?, 
                 description = ?
                 WHERE film_id = ?";
                 
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "siisissssi", $title, $release_year, $genre_id, $director, $duration, $rating, $poster_url, $description, $film_id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: filim.php?message=Film berhasil diperbarui");
            exit();
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        $error = "Gagal memperbarui film: " . $e->getMessage();
    }
}

// Ambil data film yang akan diedit
$query = "SELECT * FROM Films WHERE film_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $film_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: filim.php?message=Film tidak ditemukan");
    exit();
}

$film = mysqli_fetch_assoc($result);

// Ambil daftar genre untuk dropdown
$genres_query = "SELECT genre_id, genre_name FROM Genres ORDER BY genre_name";
$genres_result = mysqli_query($conn, $genres_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Film</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Konten Utama -->
            <main class="col-md-12 ms-sm-auto col-lg-12 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Film</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="filim.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Judul Film</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($film['title']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="release_year" class="form-label">Tahun Rilis</label>
                                        <input type="number" class="form-control" id="release_year" name="release_year" value="<?= $film['release_year']; ?>" min="1900" max="<?= date('Y'); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="genre_id" class="form-label">Genre</label>
                                        <select class="form-select" id="genre_id" name="genre_id" required>
                                            <option value="">Pilih Genre</option>
                                            <?php while ($genre = mysqli_fetch_assoc($genres_result)): ?>
                                                <option value="<?= $genre['genre_id']; ?>" <?= ($genre['genre_id'] == $film['genre_id']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($genre['genre_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="director" class="form-label">Sutradara</label>
                                        <input type="text" class="form-control" id="director" name="director" value="<?= htmlspecialchars($film['director']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Durasi (menit)</label>
                                        <input type="number" class="form-control" id="duration" name="duration" value="<?= $film['duration']; ?>" min="1" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="rating" class="form-label">Rating (1-10)</label>
                                        <input type="number" class="form-control" id="rating" name="rating" value="<?= $film['rating']; ?>" min="1" max="10" step="0.1" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="poster_url" class="form-label">URL Poster</label>
                                        <input type="url" class="form-control" id="poster_url" name="poster_url" value="<?= htmlspecialchars($film['poster_url']); ?>">
                                        <?php if (!empty($film['poster_url'])): ?>
                                        <div class="mt-2">
                                            <img src="<?= htmlspecialchars($film['poster_url']); ?>" alt="<?= htmlspecialchars($film['title']); ?>" class="poster-preview">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($film['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="filim.php" class="btn btn-outline-secondary me-md-2">Batal</a>
                                <button type="submit" name="update_film" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>