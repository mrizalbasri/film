<?php
// Database connection
include "database.php";

// Initialize variables
$watch_id = "";
$film_id = "";
$watch_date = "";
$is_watched = 0;
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

// Check if ID is set
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $watch_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Fetch the watch record
    $sql = "SELECT * FROM watchstatus WHERE watch_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $watch_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $film_id = $row['film_id'];
            $watch_date = $row['watch_date'];
            $is_watched = $row['is_watched'];
            $personal_rating = $row['personal_rating'];
            $notes = $row['notes'];
        } else {
            $error = "No record found with that ID.";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
} else {
    $error = "ID parameter is missing.";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $watch_id = mysqli_real_escape_string($conn, $_POST['watch_id']);
    $film_id = mysqli_real_escape_string($conn, $_POST['film_id']);
    $watch_date = !empty($_POST['watch_date']) ? mysqli_real_escape_string($conn, $_POST['watch_date']) : NULL;
    $is_watched = isset($_POST['is_watched']) ? 1 : 0;
    $personal_rating = !empty($_POST['personal_rating']) ? mysqli_real_escape_string($conn, $_POST['personal_rating']) : NULL;
    $notes = !empty($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : NULL;
    
    // Validate film_id
    if (empty($film_id)) {
        $error = "Film is required";
    } 
    // Validate rating if provided
    elseif (!empty($personal_rating) && ($personal_rating < 0 || $personal_rating > 10)) {
        $error = "Rating must be between 0 and 10";
    }
    // All validations passed
    else {
        // Update database
        $sql = "UPDATE watchstatus SET film_id = ?, watch_date = ?, is_watched = ?, personal_rating = ?, notes = ? 
                WHERE watch_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isidsi", $film_id, $watch_date, $is_watched, $personal_rating, $notes, $watch_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Watch record updated successfully!";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Watch Record</title>
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
                    <h1 class="h2">Edit Watch Record</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="watch-status.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
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
                            <input type="hidden" name="watch_id" value="<?php echo $watch_id; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="film_id" class="form-label">Film</label>
                                        <select class="form-select" id="film_id" name="film_id" required>
                                            <option value="">Select a film...</option>
                                            <?php
                                            if ($filmResult && mysqli_num_rows($filmResult) > 0) {
                                                // Reset pointer to the beginning of result set
                                                mysqli_data_seek($filmResult, 0);
                                                
                                                while($filmRow = mysqli_fetch_assoc($filmResult)) {
                                                    $selected = ($filmRow["film_id"] == $film_id) ? 'selected' : '';
                                                    echo "<option value='" . $filmRow["film_id"] . "' $selected>" . htmlspecialchars($filmRow["title"]) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                
                                    <div class="mb-3">
                                        <label for="watch_date" class="form-label">Watch Date</label>
                                        <input type="date" class="form-control" id="watch_date" name="watch_date" value="<?php echo $watch_date; ?>">
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_watched" name="is_watched" <?php echo $is_watched ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_watched">Marked as Watched</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="personal_rating" class="form-label">Personal Rating (0-10)</label>
                                        <input type="number" step="0.1" min="0" max="10" class="form-control" id="personal_rating" name="personal_rating" value="<?php echo $personal_rating; ?>" placeholder="Enter your rating from 0 to 10">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="5" placeholder="Add your notes about this film"><?php echo $notes; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="reset" class="btn btn-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary">Update Record</button>
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