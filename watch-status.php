<?php
// Database connection
include "database.php"; 

// Fixed query - using only the watchstatus table
$sql = "SELECT watch_id, film_id, watch_date, is_watched, personal_rating, notes 
        FROM watchstatus 
        ORDER BY watch_date DESC";

$result = $conn->query($sql);

// Query to get watch statistics
$statsQuery = "SELECT 
    COUNT(*) as total_films,
    SUM(CASE WHEN is_watched = 1 THEN 1 ELSE 0 END) as watched_films,
    ROUND(AVG(personal_rating), 1) as avg_rating,
    COUNT(DISTINCT film_id) as unique_films
    FROM watchstatus";
    
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
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
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
                        <li class="nav-item active">
                            <a class="nav-link" href="watch-status.php" id="status-link">
                                <i class="fas fa-eye"></i> Watch Status
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Film Watch Status Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="add_watch.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Add New Watch
                            </a>
                            <a href="export_watch.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-file-export"></i> Export
                            </a>
                        </div>
                        <div class="dropdown">
                            <form action="" method="GET">
                                <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
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

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1">Total Films</h6>
                                        <h3 class="mb-0"><?php echo $stats['total_films']; ?></h3>
                                    </div>
                                    <i class="fas fa-film fa-2x"></i>
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
                                    <i class="fas fa-check-circle fa-2x"></i>
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
                                    <i class="fas fa-star fa-2x"></i>
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
                                    <i class="fas fa-fingerprint fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Watch Status Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Watch Status List
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Film ID</th>
                                        <th>Watch Date</th>
                                        <th>Status</th>
                                        <th>Rating</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row["film_id"]) . "</td>";
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
                                                echo " (" . $rating . "/10)";
                                            } else {
                                                echo "Not rated";
                                            }
                                            echo "</td>";
                                            echo "<td>" . (empty($row["notes"]) ? "No notes" : '<span class="text-truncate d-inline-block" style="max-width: 150px;" title="' . htmlspecialchars($row["notes"]) . '">' . htmlspecialchars(substr($row["notes"], 0, 50)) . (strlen($row["notes"]) > 50 ? '...' : '') . '</span>') . "</td>";
                                            echo "<td>
                                                <div class='btn-group'>
                                                    <a href='edit_watch.php?id=" . $row["watch_id"] . "' class='btn btn-sm btn-primary'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                    <a href='delete_watch.php?id=" . $row["watch_id"] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this record?\");'>
                                                        <i class='fas fa-trash'></i>
                                                    </a>
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
                                    // Get monthly statistics
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
                                    
                                    $monthlyResult = $conn->query($monthlyStats);
                                    
                                    if ($monthlyResult->num_rows > 0) {
                                        while($monthRow = $monthlyResult->fetch_assoc()) {
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

                <!-- Rating Distribution -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-star me-1"></i>
                        Rating Distribution
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Rating Range</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                        <th>Visual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get rating distribution
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
                                    ORDER BY rating_range";
                                    
                                    $ratingResult = $conn->query($ratingDistribution);
                                    
                                    // Calculate total rated films
                                    $totalRated = 0;
                                    $ratingData = array();
                                    
                                    if ($ratingResult->num_rows > 0) {
                                        while($ratingRow = $ratingResult->fetch_assoc()) {
                                            if ($ratingRow["rating_range"] != 'Not Rated') {
                                                $totalRated += $ratingRow["count"];
                                            }
                                            $ratingData[] = $ratingRow;
                                        }
                                        
                                        // Display the distribution
                                        foreach ($ratingData as $row) {
                                            echo "<tr>";
                                            echo "<td>" . $row["rating_range"] . "</td>";
                                            echo "<td>" . $row["count"] . "</td>";
                                            
                                            if ($row["rating_range"] != 'Not Rated' && $totalRated > 0) {
                                                $percentage = round(($row["count"] / $totalRated) * 100, 1);
                                                echo "<td>" . $percentage . "%</td>";
                                                
                                                // Create a simple bar chart
                                                $barColor = '';
                                                switch ($row["rating_range"]) {
                                                    case '0-2': $barColor = 'danger'; break;
                                                    case '2.1-4': $barColor = 'warning'; break;
                                                    case '4.1-6': $barColor = 'info'; break;
                                                    case '6.1-8': $barColor = 'primary'; break;
                                                    case '8.1-10': $barColor = 'success'; break;
                                                    default: $barColor = 'secondary';
                                                }
                                                
                                                echo "<td>
                                                    <div class='progress'>
                                                        <div class='progress-bar bg-" . $barColor . "' role='progressbar' style='width: " . $percentage . "%' aria-valuenow='" . $percentage . "' aria-valuemin='0' aria-valuemax='100'></div>
                                                    </div>
                                                </td>";
                                            } else {
                                                echo "<td>N/A</td>";
                                                echo "<td>N/A</td>";
                                            }
                                            
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center'>No rating data available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap CSS only (no JS) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>