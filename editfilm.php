<?php
// Koneksi ke database
include 'config/database.php';

// Cek apakah ada ID film yang dikirim
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=ID Film tidak ditemukan");
    exit();
}

$film_id = $_GET['id'];

// Ambil data film yang akan diedit
$film_query = "SELECT * FROM Films WHERE film_id = $film_id";
$film_result = mysqli_query($conn, $film_query);

if(mysqli_num_rows($film_result) == 0) {
    header("Location: index.php?error=Film tidak ditemukan");
    exit();
}

$film = mysqli_fetch_assoc($film_result);

// Ambil daftar genre untuk dropdown
$genres_query = "SELECT genre_id, genre_name FROM Genres ORDER BY genre_name";
$genres_result = mysqli_query($conn, $genres_query);

// Proses form submission
if(isset($_POST['submit'])) {
    // Ambil data dari form
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $release_year = mysqli_real_escape_string($conn, $_POST['release_year']);
    $genre_id = mysqli_real_escape_string($conn, $_POST['genre_id']);
    $director = mysqli_real_escape_string($conn, $_POST['director']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $synopsis = mysqli_real_escape_string($conn, $_POST['synopsis']);
    $poster_url = mysqli_real_escape_string($conn, $_POST['poster_url']);
    
    // Handle file upload jika ada
    if(isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['poster_file']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($ext), $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/posters/' . $new_filename;
            
            if(move_uploaded_file($_FILES['poster_file']['tmp_name'], $upload_path)) {
                $poster_url = $upload_path;
            }
        }
    }
    
    // Update data di database
    $query = "UPDATE Films SET 
              title = '$title', 
              release_year = '$release_year', 
              genre_id = '$genre_id', 
              director = '$director', 
              duration = '$duration', 
              rating = '$rating', 
              poster_url = '$poster_url', 
              synopsis = '$synopsis',
              updated_at = NOW() 
              WHERE film_id = $film_id";
    
    if(mysqli_query($conn, $query)) {
        header("Location: index.php?message=Film berhasil diperbarui");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
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
            <!-- Sidebar (sudah ada dengan include) -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Konten Utama -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Film</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Judul Film</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?= $film['title']; ?>" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="release_year" class="form-label">Tahun Rilis</label>
                                                <input type="number" class="form-control" id="release_year" name="release_year" min="1900" max="<?= date('Y'); ?>" value="<?= $film['release_year']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="genre_id" class="form-label">Genre</label>
                                                <select class="form-select" id="genre_id" name="genre_id" required>
                                                    <option value="">Pilih Genre</option>
                                                    <?php mysqli_data_seek($genres_result, 0); ?>
                                                    <?php while($genre = mysqli_fetch_assoc($genres_result)): ?>
                                                        <option value="<?= $genre['genre_id']; ?>" <?= ($genre['genre_id'] == $film['genre_id']) ? 'selected' : ''; ?>>
                                                            <?= $genre['genre_name']; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="director" class="form-label">Sutradara</label>
                                        <input type="text" class="form-control" id="director" name="director" value="<?= $film['director']; ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="duration" class="form-label">Durasi (menit)</label>
                                                <input type="number" class="form-control" id="duration" name="duration" min="1" value="<?= $film['duration']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="rating" class="form-label">Rating (1-10)</label>
                                                <input type="number" class="form-control" id="rating" name="rating" min="1" max="10" step="0.1" value="<?= $film['rating']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="synopsis" class="form-label">Sinopsis</label>
                                        <textarea class="form-control" id="synopsis" name="synopsis" rows="5"><?= $film['synopsis']; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="poster_preview" class="form-label">Poster Film</label>
                                        <div class="card mb-2">
                                            <img id="poster_preview" src="<?= !empty($film['poster_url']) ? $film['poster_url'] : 'assets/img/no-poster.jpg'; ?>" class="card-img-top" alt="Preview poster">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="poster_file" class="form-label">Upload Poster Baru</label>
                                            <input class="form-control" type="file" id="poster_file" name="poster_file" accept="image/*">
                                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah poster</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="poster_url" class="form-label">atau URL Poster</label>
                                            <input type="url" class="form-control" id="poster_url" name="poster_url" placeholder="https://..." value="<?= $film['poster_url']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <a href="view_film.php?id=<?= $film_id; ?>" class="btn btn-secondary">Batal</a>
                                <button type="submit" name="submit" class="btn btn-primary">Simpan Perubahan</button>
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