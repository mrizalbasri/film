<?php
// Memulai session
session_start();

// Jika sudah login, redirect ke halaman utama
if(isset($_SESSION['user_id'])) {
    header("Location: filim.php");
    exit();
}

// Koneksi ke database
include 'database.php';

$error = "";

// Proses login
if(isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = "Username dan password harus diisi";
    } else {
        // Cek user di database
        $query = "SELECT user_id, username, password, role FROM Users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verifikasi password
            if(password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login
                $update_query = "UPDATE Users SET last_login = NOW() WHERE user_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "i", $user['user_id']);
                mysqli_stmt_execute($update_stmt);
                
                // Redirect ke halaman utama
                header("Location: filim.php");
                exit();
            } else {
                $error = "Password salah";
            }
        } else {
            $error = "Username tidak ditemukan";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Film Collection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .login-form {
            max-width: 400px;
            padding: 20px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 80px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-logo i {
            font-size: 3rem;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="login-logo">
                <i class="bi bi-film"></i>
                <h2>Film Collection</h2>
                <p class="text-muted">Login untuk mengelola koleksi film</p>
            </div>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                <div class="text-center mt-3">
                    <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>