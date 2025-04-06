<?php
// Database connection
include "database.php"; 

// Filter handling
$whereClause = "";
$orderBy = "ORDER BY watch_date DESC";

if (isset($_GET['filter']) && !empty($_GET['filter'])) {
    switch ($_GET['filter']) {
        case 'watched':
            $whereClause = "WHERE is_watched = 1";
            break;
        case 'unwatched':
            $whereClause = "WHERE is_watched = 0";
            break;
        case 'highest':
            $whereClause = "WHERE personal_rating IS NOT NULL";
            $orderBy = "ORDER BY personal_rating DESC, watch_date DESC";
            break;
        case 'recent':
            $orderBy = "ORDER BY watch_date DESC";
            break;
    }
}

// Search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClause = ($whereClause == "") ? "WHERE " : $whereClause . " AND ";
    $whereClause .= "film_id LIKE '%$search%'";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Main query with pagination
$sql = "SELECT watch_id, film_id, watch_date, is_watched, personal_rating, notes 
        FROM watchstatus 
        $whereClause
        $orderBy
        LIMIT $offset, $perPage";

$result = $conn->query($sql);

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM watchstatus $whereClause";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            position: fixed;
            z-index: 100;
            padding-top: 20px;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #3498db;
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-brand {
            padding: 15px 20px;
            margin-bottom: 20px;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        main {
            margin-left: 230px;
            padding: 20px;
        }
        
        .page-header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .dashboard-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .dashboard-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 25px;
        }
        
        .rating-star {
            color: #f39c12;
        }
        
        .rating-empty {
            color: #e0e0e0;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .watched-badge {
            background-color: #2ecc71;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .not-watched-badge {
            background-color: #e74c3c;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        
        .btn-outline-primary {
            border-color: #3498db;
            color: #3498db;
        }
        
        .btn-outline-primary:hover {
            background-color: #3498db;
            color: white;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
        }
        
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255,255,255,0.3);
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        .search-box {
            border-radius: 20px;
            border: 1px solid #e0e0e0;
            padding: 8px 15px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .search-box:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
            outline: none;
        }
        
        .search-btn {
            border-radius: 20px;
            padding: 8px 20px;
        }
        
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        
        .page-link {
            color: #3498db;
            border-radius: 4px;
            margin: 0 3px;
        }
        
        .page-item.active .page-link {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .notes-ellipsis {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link {
                padding: 15px;
                text-align: center;
            }
            
            .sidebar .nav-link i {
                margin: 0;
                font-size: 1.2rem;
            }
            
            .sidebar-brand {
                display: none;
            }
            
            main {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-brand">
                    <h4><i class="fas fa-film"></i> FilmTracker</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="films.php">
                            <i class="fas fa-film"></i> <span>Films</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="genre.php">
                            <i class="fas fa-tags"></i> <span>Genres</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="watch-status.php">
                            <i class="fas fa-eye"></i> <span>Watch Status</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stats.php">
                            <i class="fas fa-chart-bar"></i> <span>Statistics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <main>
                <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
                    <h1 class="h3 mb-3 mb-md-0">Watch Status Dashboard</h1>
                    <div class="d-flex gap-2">
                        <a href="add_watch.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Watch Record
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-file-export"></i> Export
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="export_watch.php?format=csv">CSV</a></li>
                                <li><a class="dropdown-item" href="export_watch.php?format=pdf">PDF</a></li>
                                <li><a class="dropdown-item" href="export_watch.php?format=excel">Excel</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Search & Filter Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <form action="" method="GET" class="d-flex">
                                    <input type="text" name="search" class="form-control search-box me-2" placeholder="Search by film ID..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    <button type="submit" class="btn btn-primary search-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form action="" method="GET" class="d-flex">
                                    <select name="filter" class="form-select me-2" onchange="this.form.submit()">
                                        <option value="">Filter Options</option>
                                        <option value="all" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="watched" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'watched' ? 'selected' : ''; ?>>Watched</option>
                                        <option value="unwatched" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'unwatched' ? 'selected' : ''; ?>>Unwatched</option>
                                        <option value="highest" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'highest' ? 'selected' : ''; ?>>Highest Rated</option>
                                        <option value="recent" <?php echo isset($_GET['filter']) && $_GET['filter'] == 'recent' ? 'selected' : ''; ?>>Recently Added</option>
                                    </select>
                                    <?php if(isset($_GET['filter']) && !empty($_GET['filter'])): ?>
                                    <a href="watch-status.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="dashboard-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1 opacity-75">Total Records</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_films']; ?></h2>
                                        <p class="mb-0 mt-2 small">Film watch entries</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-film fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1 opacity-75">Watched</h6>
                                        <h2 class="mb-0"><?php echo $stats['watched_films']; ?></h2>
                                        <p class="mb-0 mt-2 small">
                                            <?php 
                                            $watchedPercentage = ($stats['total_films'] > 0) ? 
                                                round(($stats['watched_films'] / $stats['total_films']) * 100) : 0;
                                            echo $watchedPercentage . '% completion';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-check-circle fa-lg"></i>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-light" role="progressbar" style="width: <?php echo $watchedPercentage; ?>%" 
                                         aria-valuenow="<?php echo $watchedPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1 opacity-75">Avg Rating</h6>
                                        <h2 class="mb-0"><?php echo $stats['avg_rating'] ? $stats['avg_rating'] : '0.0'; ?></h2>
                                        <p class="mb-0 mt-2 small">Out of 10 points</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-star fa-lg"></i>
                                    </div>
                                </div>
                                <?php 
                                $ratingPercentage = ($stats['avg_rating']) ? ($stats['avg_rating'] / 10) * 100 : 0;
                                ?>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-light" role="progressbar" style="width: <?php echo $ratingPercentage; ?>%" 
                                         aria-valuenow="<?php echo $ratingPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase fw-bold mb-1 opacity-75">Unique Films</h6>
                                        <h2 class="mb-0"><?php echo $stats['unique_films']; ?></h2>
                                        <p class="mb-0 mt-2 small">Different titles tracked</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-fingerprint fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Watch Status Table -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div><i class="fas fa-table me-2"></i> Watch Records</div>
                                <div class="text-muted small">Showing <?php echo min($perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> records</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
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
                                                    echo "<td><strong>" . htmlspecialchars($row["film_id"]) . "</strong></td>";
                                                    echo "<td>" . ($row["watch_date"] ? date("M d, Y", strtotime($row["watch_date"])) : 
                                                        "<span class='text-muted'>Not set</span>") . "</td>";
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
                                                        echo " <span class='text-muted small'>(" . $rating . ")</span>";
                                                    } else {
                                                        echo "<span class='text-muted'>Not rated</span>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>";
                                                    if (!empty($row["notes"])) {
                                                        echo '<span class="notes-ellipsis" data-bs-toggle="tooltip" title="' . 
                                                            htmlspecialchars($row["notes"]) . '">' . 
                                                            htmlspecialchars(substr($row["notes"], 0, 50)) . 
                                                            (strlen($row["notes"]) > 50 ? '...' : '') . '</span>';
                                                    } else {
                                                        echo "<span class='text-muted'>No notes</span>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>
                                                        <div class='btn-group'>
                                                            <a href='edit_watch.php?id=" . $row["watch_id"] . "' class='btn btn-sm btn-outline-primary' 
                                                               data-bs-toggle='tooltip' title='Edit'>
                                                                <i class='fas fa-edit'></i>
                                                            </a>
                                                            <button type='button' class='btn btn-sm btn-outline-danger delete-btn' 
                                                                data-id='" . $row["watch_id"] . "' data-bs-toggle='tooltip' title='Delete'>
                                                                <i class='fas fa-trash'></i>
                                                            </button>
                                                        </div>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='6' class='text-center py-4'>
                                                    <div class='text-muted'>
                                                        <i class='fas fa-search fa-2x mb-3'></i>
                                                        <p>No watch records found</p>
                                                    </div>
                                                </td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo isset($_GET['filter']) ? '&filter='.$_GET['filter'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.$_GET['search'] : ''; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Recent Activity Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-clock me-2"></i> Watch Activity
                            </div>
                            <div class="card-body p-0">
                                <div class="chart-container p-3">
                                    <canvas id="watchActivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rating Distribution Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-star me-2"></i> Rating Distribution
                            </div>
                            <div class="card-body p-0">
                                <div class="chart-container p-3">
                                    <canvas id="ratingDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Watch Records by Month -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar me-2"></i> Monthly Statistics
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
                                            