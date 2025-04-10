<?php
// Database connection
include "database.php";

// Initialize variables
$error = "";
$success = "";
$redirect = false;

// Check if ID parameter is set
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $watch_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Check if the record exists
    $checkSql = "SELECT watch_id, film_id, watch_date FROM watchstatus WHERE watch_id = ?";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "i", $watch_id);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        // If record exists
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            mysqli_stmt_bind_result($checkStmt, $watch_id, $film_id, $watch_date);
            mysqli_stmt_fetch($checkStmt);
            mysqli_stmt_close($checkStmt);
            
            // Check if confirmation is provided (via POST method)
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
                // Delete the record
                $deleteSql = "DELETE FROM watchstatus WHERE watch_id = ?";
                $deleteStmt = mysqli_prepare($conn, $deleteSql);
                
                if ($deleteStmt) {
                    mysqli_stmt_bind_param($deleteStmt, "i", $watch_id);
                    
                    if (mysqli_stmt_execute($deleteStmt)) {
                        $success = "Watch record deleted successfully!";
                        $redirect = true;
                    } else {
                        $error = "Error deleting record: " . mysqli_stmt_error($deleteStmt);
                    }
                    
                    mysqli_stmt_close($deleteStmt);
                } else {
                    $error = "Error preparing statement: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "No record found with that ID.";
            $redirect = true;
        }
    } else {
        $error = "Error preparing statement: " . mysqli_error($conn);
        $redirect = true;
    }
} else {
    $error = "ID parameter is missing.";
    $redirect = true;
}

// Redirect after successful deletion or if record doesn't exist
if ($redirect) {
    header("refresh:2;url=watch-status.php");
}

// Get film title if we have a film_id
$film_title = "Unknown Film";
if (isset($film_id) && !empty($film_id)) {
    $filmSql = "SELECT title FROM films WHERE film_id = ?";
    $filmStmt = mysqli_prepare($conn, $filmSql);
    
    if ($filmStmt) {
        mysqli_stmt_bind_param($filmStmt, "i", $film_id);
        mysqli_stmt_execute($filmStmt);
        mysqli_stmt_bind_result($filmStmt, $title);
        
        if (mysqli_stmt_fetch($filmStmt)) {
            $film_title = $title;
        }
        
        mysqli_stmt_close($filmStmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Watch Record</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .delete-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .film-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
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
                            <a class="nav-link" href="filim.php">
                                <i class="fas fa-film"></i> Film
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="genre.php">
                                <i class="fas fa-tags"></i> Genre
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="watch-status.php">
                                <i class="fas fa-eye"></i> Watch Status
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Delete Watch Record</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="watch-status.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Watch Status
                        </a>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                    <p>Redirecting to watch status list...</p>
                </div>
                <?php endif; ?>

                <?php if (empty($error) && empty($success)): ?>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card delete-card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                            </div>
                            <div class="card-body">
                                <div class="film-details">
                                    <h5>Film Details:</h5>
                                    <p><strong>Title:</strong> <?php echo htmlspecialchars($film_title); ?></p>
                                    <p><strong>Watch Date:</strong> <?php echo isset($watch_date) ? date("F d, Y", strtotime($watch_date)) : "Not specified"; ?></p>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Are you sure you want to delete this watch record? This action cannot be undone.
                                </div>
                                
                                <form method="POST" action="">
                                    <input type="hidden" name="confirm_delete" value="1">
                                    <div class="d-flex justify-content-between">
                                        <a href="watch-status.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>