<?php
// Database connection
include "database.php";

// Initialize variables
$film_id = "";
$watch_date = date("Y-m-d"); // Default to today
$is_watched = 0; // Default to not watched
$personal_rating = "";
$notes = "";
$error = "";
$success = "";

// Get list of films for dropdown
$filmQuery = "SELECT film_id, title FROM films ORDER BY title";
$filmResult = mysqli_query($conn, $filmQuery);

if (!$filmResult) {
    $error = "Error fetching film list: " . mysqli_error($conn);
}

// Process form submission
if (isset($_POST['submit'])) {
    // Validate input
    $film_id = mysqli_real_escape_string($conn, $_POST['film_id']);
    $watch_date = !empty($_POST['watch_date']) ? mysqli_real_escape_string($conn, $_POST['watch_date']) : NULL;
    $is_watched = isset($_POST['is_watched']) ? 1 : 0;
    $personal_rating = !empty($_POST['personal_rating']) ? mysqli_real_escape_string($conn, $_POST['personal_rating']) : NULL;
    $notes = !empty($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : NULL;
    
    // Validate film_id
    if (empty($film_id)) {
        $error = "Film harus dipilih";
    } 
    // Validate rating if provided
    elseif (!empty($personal_rating) && ($personal_rating < 0 || $personal_rating > 10)) {
        $error = "Rating harus antara 0 dan 10";
    }
    // All validations passed
    else {
        // Insert into database
        $sql = "INSERT INTO watchstatus (film_id, watch_date, is_watched, personal_rating, notes) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isids", $film_id, $watch_date, $is_watched, $personal_rating, $notes);
            
            if (mysqli_stmt_execute($stmt)) {
                header("Location: watch-status.php?message=Catatan tonton berhasil ditambahkan");
                exit();
            } else {
                $error = "Error: " . mysqli_stmt_error($stmt);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Catatan Tonton</title>
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
                    <h1 class="h2">Tambah Catatan Tonton</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="watch-status.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <?php if(!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="film_id" class="form-label">Film</label>
                                        <select class="form-select" id="film_id" name="film_id" required>
                                            <option value="">Pilih Film...</option>
                                            <?php
                                            if ($filmResult && mysqli_num_rows($filmResult) > 0) {
                                                while($filmRow = mysqli_fetch_assoc($filmResult)) {
                                                    $selected = ($filmRow["film_id"] == $film_id) ? 'selected' : '';
                                                    echo "<option value='" . $filmRow["film_id"] . "' $selected>" . htmlspecialchars($filmRow["title"]) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                
                                    <div class="mb-3">
                                        <label for="watch_date" class="form-label">Tanggal Tonton</label>
                                        <input type="date" class="form-control" id="watch_date" name="watch_date" value="<?php echo $watch_date; ?>">
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_watched" name="is_watched" <?php echo $is_watched ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_watched">Tandai Sudah Ditonton</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="personal_rating" class="form-label">Rating Pribadi (0-10)</label>
                                        <input type="number" step="0.1" min="0" max="10" class="form-control" id="personal_rating" name="personal_rating" value="<?php echo $personal_rating; ?>" placeholder="Masukkan rating 0 sampai 10">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Catatan</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="5" placeholder="Tambahkan catatan tentang film ini"><?php echo $notes; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="reset" class="btn btn-secondary">Reset</button>
                                <button type="submit" name="submit" class="btn btn-primary">Simpan Catatan</button>
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

<?php
// Close the database connection
mysqli_close($conn);
?>