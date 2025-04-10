<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Database connection (procedural style)
include "database.php";

// Handle delete operation
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // Perform the delete operation
    $delete_sql = "DELETE FROM watchstatus WHERE watch_id = '$delete_id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        // Set success message
        $success_message = "Record deleted successfully.";
    } else {
        // Set error message
        $error_message = "Error deleting record: " . mysqli_error($conn);
    }
    
    // Redirect to remove the delete_id parameter from URL
    header("Location: watch-status.php");
    exit;
}

// Initialize variables
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$whereClause = '';
$orderClause = 'ORDER BY watch_date DESC';

// Apply filters
switch ($filter) {
    case 'watched':
        $whereClause = 'WHERE is_watched = 1';
        break;
    case 'unwatched':
        $whereClause = 'WHERE is_watched = 0';
        break;
    case 'highest':
        $whereClause = 'WHERE personal_rating IS NOT NULL';
        $orderClause = 'ORDER BY personal_rating DESC';
        break;
    case 'recent':
        $orderClause = 'ORDER BY watch_date DESC';
        break;
    default:
        // No filter or 'all' selected
        break;
}

// Prepare and execute the main query with filters
$sql = "SELECT ws.watch_id, ws.film_id, ws.watch_date, ws.is_watched, ws.personal_rating, ws.notes, 
               f.title as film_title
        FROM watchstatus ws
        LEFT JOIN films f ON ws.film_id = f.film_id
        $whereClause 
        $orderClause";

$result = mysqli_query($conn, $sql);

// Handle database query error
if (!$result) {
    $error_message = "Error: " . mysqli_error($conn);
}

// Query to get watch statistics
$statsQuery = "SELECT 
    COUNT(*) as total_films,
    SUM(CASE WHEN is_watched = 1 THEN 1 ELSE 0 END) as watched_films,
    ROUND(AVG(personal_rating), 1) as avg_rating,
    COUNT(DISTINCT film_id) as unique_films
    FROM watchstatus";
    
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Get monthly statistics with better error handling
$monthlyStats = "SELECT 
    DATE_FORMAT(watch_date, '%Y-%m') as month_year,
    COUNT(*) as total,
    SUM(CASE WHEN is_watched = 1 THEN 1 ELSE 0 END) as watched,
    SUM(CASE WHEN is_watched = 0 THEN 1 ELSE 0 END) as not_watched,
    ROUND(AVG(personal_rating), 1) as avg_rating
FROM watchstatus
WHERE watch_date IS NOT NULL
GROUP BY DATE_FORMAT(watch_date, '%Y-%m')
ORDER BY month_year DESC
LIMIT 6";

$monthlyResult = mysqli_query($conn, $monthlyStats);

// Get rating distribution with better error handling
$ratingDistribution = "SELECT 
    CASE 
        WHEN personal_rating BETWEEN 0 AND 2 THEN '0-2'
        WHEN personal_rating BETWEEN 2.1 AND 4 THEN '2.1-4'
        WHEN personal_rating BETWEEN 4.1 AND 6 THEN '4.1-6'
        WHEN personal_rating BETWEEN 6.1 AND 8 THEN '6.1-8'
        WHEN personal_rating BETWEEN 8.1 AND 10 THEN '8.1-10'
        ELSE 'Not Rated'
    END as rating_range,
    COUNT(*) as count
FROM watchstatus
GROUP BY rating_range
ORDER BY FIELD(rating_range, '0-2', '2.1-4', '4.1-6', '6.1-8', '8.1-10', 'Not Rated')";

$ratingResult = mysqli_query($conn, $ratingDistribution);

// Calculate total rated films for percentages
$totalRated = 0;
$ratingData = array();

if ($ratingResult && mysqli_num_rows($ratingResult) > 0) {
    while($ratingRow = mysqli_fetch_assoc($ratingResult)) {
        if ($ratingRow["rating_range"] != 'Not Rated') {
            $totalRated += $ratingRow["count"];
        }
        $ratingData[] = $ratingRow;
    }
}

// Get recently added films (for the "new films" section)
$recentFilmsQuery = "SELECT ws.film_id, f.title, ws.watch_date, ws.is_watched 
                     FROM watchstatus ws
                     JOIN films f ON ws.film_id = f.film_id
                     ORDER BY ws.watch_id DESC LIMIT 5";
$recentFilmsResult = mysqli_query($conn, $recentFilmsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Film Watch Status Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-card {
            transition: all 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .rating-star {
            color: #ffc107;
        }
        .rating-empty {
            color: #e0e0e0;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .watched-badge {
            background-color: #28a745;
        }
        .not-watched-badge {
            background-color: #dc3545;
        }
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
        .progress {
            height: 20px;
        }
        .film-icon-container {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .truncate-text {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notes-tooltip {
            cursor: pointer;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
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
                            <a class="nav-link" href="filim.php" id="films-link">
                                <i class="fas fa-film"></i> Film
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="genre.php" id="genres-link">
                                <i class="fas fa-tags"></i> Genre
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="watch-status.php" id="status-link">
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
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Toast notification for success/error messages -->
                <?php if (isset($success_message)): ?>
                <div class="toast show bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-success text-white">
                        <strong class="me-auto">Success</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        <?php echo $success_message; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Film Watch Status Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add_watch.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Add New Watch
                            </a>
                        </div>
                        <div class="dropdown">
                            <form action="" method="GET" id="filterForm">
                                <select name="filter" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">Filter Options</option>
                                    <option value="all" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="watched" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'watched' ? 'selected' : ''; ?>>Watched</option>
                                    <option value="unwatched" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'unwatched' ? 'selected' : ''; ?>>Unwatched</option>
                                    <option value="highest" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'highest' ? 'selected' : ''; ?>>Highest Rated</option>
                                    <option value="recent" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'recent' ? 'selected' : ''; ?>>Recently Added</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1">Total Records</h6>
                                        <h3 class="mb-0"><?php echo $stats['total_films']; ?></h3>
                                    </div>
                                    <div class="film-icon-container bg-primary bg-opacity-25">
                                        <i class="fas fa-film fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1">Watched</h6>
                                        <h3 class="mb-0"><?php echo $stats['watched_films']; ?></h3>
                                    </div>
                                    <div class="film-icon-container bg-success bg-opacity-25">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1">Avg Rating</h6>
                                        <h3 class="mb-0"><?php echo $stats['avg_rating'] ? $stats['avg_rating'] : '0.0'; ?> <small>/10</small></h3>
                                    </div>
                                    <div class="film-icon-container bg-warning bg-opacity-25">
                                        <i class="fas fa-star fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1">Unique Films</h6>
                                        <h3 class="mb-0"><?php echo $stats['unique_films']; ?></h3>
                                    </div>
                                    <div class="film-icon-container bg-info bg-opacity-25">
                                        <i class="fas fa-fingerprint fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main dashboard content -->
                <div class="row">
                    <div class="col-lg-12">
                        <!-- Watch Status Table -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-table me-1"></i>
                                    Watch Status List
                                </div>
                                <div>
                                    <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search...">
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="watchTable">
                                        <thead>
                                            <tr>
                                                <th>Film</th>
                                                <th>Watch Date</th>
                                                <th>Status</th>
                                                <th>Rating</th>
                                                <th>Notes</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result && mysqli_num_rows($result) > 0) {
                                                while($row = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    echo "<td>" . (isset($row["film_title"]) ? htmlspecialchars($row["film_title"]) : "Film #" . htmlspecialchars($row["film_id"])) . "</td>";
                                                    echo "<td>" . ($row["watch_date"] ? date("M d, Y", strtotime($row["watch_date"])) : "Not set") . "</td>";
                                                    echo "<td>";
                                                    if ($row["is_watched"] == 1) {
                                                        echo '<span class="badge watched-badge">Watched</span>';
                                                    } else {
                                                        echo '<span class="badge not-watched-badge">Not Watched</span>';
                                                    }
                                                    echo "</td>";
                                                    echo "<td>";
                                                    // Display rating stars
                                                    if ($row["personal_rating"]) {
                                                        $rating = $row["personal_rating"];
                                                        $fullStars = floor($rating / 2); // Convert 10-point scale to 5-star scale
                                                        $halfStar = ($rating / 2) - $fullStars >= 0.5;
                                                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                                        
                                                        for ($i = 0; $i < $fullStars; $i++) {
                                                            echo '<i class="fas fa-star rating-star"></i>';
                                                        }
                                                        if ($halfStar) {
                                                            echo '<i class="fas fa-star-half-alt rating-star"></i>';
                                                        }
                                                        for ($i = 0; $i < $emptyStars; $i++) {
                                                            echo '<i class="far fa-star rating-empty"></i>';
                                                        }
                                                        echo " <span class='text-muted'>(" . $rating . "/10)</span>";
                                                    } else {
                                                        echo "<span class='text-muted'>Not rated</span>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>";
                                                    if (!empty($row["notes"])) {
                                                        echo '<span class="truncate-text" title="' . htmlspecialchars($row["notes"]) . '">' . 
                                                            htmlspecialchars(substr($row["notes"], 0, 50)) . 
                                                            (strlen($row["notes"]) > 50 ? '...' : '') . 
                                                            '</span>';
                                                    } else {
                                                        echo "<span class='text-muted'>No notes</span>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>
                                                        <div class='btn-group'>
                                                            <a href='edit_watch.php?id=" . $row["watch_id"] . "' class='btn btn-sm btn-primary'>
                                                                <i class='fas fa-edit'></i>
                                                            </a>
                                                            <button class='btn btn-sm btn-danger delete-btn' data-id='" . $row["watch_id"] . "' data-title='" . (isset($row["film_title"]) ? htmlspecialchars($row["film_title"]) : "Film #" . htmlspecialchars($row["film_id"])) . "'>
                                                                <i class='fas fa-trash'></i>
                                                            </button>
                                                        </div>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='6' class='text-center'>No watch records found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                       
                    </div>
                    <div class="col-lg-8">
                         <!-- Watch Records by Month -->
                         <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-calendar me-1"></i>
                                Watch Records by Month
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Total Records</th>
                                                <th>Watched</th>
                                                <th>Not Watched</th>
                                                <th>Avg Rating</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($monthlyResult && mysqli_num_rows($monthlyResult) > 0) {
                                                while($monthRow = mysqli_fetch_assoc($monthlyResult)) {
                                                    echo "<tr>";
                                                    echo "<td>" . date("F Y", strtotime($monthRow["month_year"] . "-01")) . "</td>";
                                                    echo "<td>" . $monthRow["total"] . "</td>";
                                                    echo "<td>" . $monthRow["watched"] . "</td>";
                                                    echo "<td>" . $monthRow["not_watched"] . "</td>";
                                                    echo "<td>" . ($monthRow["avg_rating"] ? $monthRow["avg_rating"] : "N/A") . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center'>No monthly data available</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Recently Added -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-clock me-1"></i>
                                Recently Added
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php
                                    if ($recentFilmsResult && mysqli_num_rows($recentFilmsResult) > 0) {
                                        while($recentFilm = mysqli_fetch_assoc($recentFilmsResult)) {
                                            echo '<div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">';
                                            echo '<div>';
                                            echo '<h6 class="mb-1">' . htmlspecialchars($recentFilm["title"] ?? "Film #" . $recentFilm["film_id"]) . '</h6>';
                                            echo '<small class="text-muted">' . ($recentFilm["watch_date"] ? date("M d, Y", strtotime($recentFilm["watch_date"])) : "No date") . '</small>';
                                            echo '</div>';
                                            if ($recentFilm["is_watched"] == 1) {
                                                echo '<span class="badge watched-badge">Watched</span>';
                                            } else {
                                                echo '<span class="badge not-watched-badge">Not Watched</span>';
                                            }
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<div class="list-group-item">No recent films</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Distribution -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-star me-1"></i>
                                Rating Distribution
                            </div>
                            <div class="card-body">
                                <?php
                                if (count($ratingData) > 0) {
                                    // Display the distribution
                                    foreach ($ratingData as $row) {
                                        if ($row["rating_range"] != 'Not Rated') {
                                            $percentage = $totalRated > 0 ? round(($row["count"] / $totalRated) * 100, 1) : 0;
                                            
                                            // Define color based on rating range
                                            $barColor = '';
                                            switch ($row["rating_range"]) {
                                                case '0-2': $barColor = 'danger'; break;
                                                case '2.1-4': $barColor = 'warning'; break;
                                                case '4.1-6': $barColor = 'info'; break;
                                                case '6.1-8': $barColor = 'primary'; break;
                                                case '8.1-10': $barColor = 'success'; break;
                                                default: $barColor = 'secondary';
                                            }
                                            
                                            echo '<div class="mb-3">';
                                            echo '<div class="d-flex justify-content-between mb-1">';
                                            echo '<span>' . $row["rating_range"] . ' / 10</span>';
                                            echo '<span>' . $row["count"] . ' films (' . $percentage . '%)</span>';
                                            echo '</div>';
                                            echo '<div class="progress">';
                                            echo '<div class="progress-bar bg-' . $barColor . '" role="progressbar" style="width: ' . $percentage . '%" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100"></div>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                    }
                                    
                                    // Show Not Rated count separately
                                    foreach ($ratingData as $row) {
                                        if ($row["rating_range"] == 'Not Rated') {
                                            echo '<div class="alert alert-secondary mt-3" role="alert">';
                                            echo 'Not Rated: ' . $row["count"] . ' films';
                                            echo '</div>';
                                            break;
                                        }
                                    }
                                } else {
                                    echo '<div class="text-center">No rating data available</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the record for "<span id="deleteItemTitle"></span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript for delete confirmation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss toast after 3 seconds
            var toastElement = document.querySelector('.toast');
            if (toastElement) {
                setTimeout(function() {
                    var toast = bootstrap.Toast.getInstance(toastElement);
                    if (toast) {
                        toast.hide();
                    }
                }, 3000);
            }
            
            // Setup delete confirmation
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            var deleteButtons = document.querySelectorAll('.delete-btn');
            
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var id = this.getAttribute('data-id');
                    var title = this.getAttribute('data-title');
                    
                    document.getElementById('deleteItemTitle').textContent = title;
                    document.getElementById('confirmDeleteBtn').href = 'watch-status.php?delete_id=' + id;
                    
                    deleteModal.show();
                });
            });
            
            // Setup table search functionality
            var searchInput = document.getElementById('tableSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    var filter = this.value.toLowerCase();
                    var table = document.getElementById('watchTable');
                    var tr = table.getElementsByTagName('tr');
                    
                    for (var i = 0; i < tr.length; i++) {
                        if (i === 0) continue; // Skip header row
                        
                        var td = tr[i].getElementsByTagName('td');
                        var found = false;
                        
                        for (var j = 0; j < td.length; j++) {
                            if (td[j].textContent.toLowerCase().indexOf(filter) > -1) {
                                found = true;
                                break;
                            }
                        }
                        
                        tr[i].style.display = found ? '' : 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>