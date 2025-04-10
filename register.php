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
$success = "";

// Proses pendaftaran
if(isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    
    // Validasi input
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Semua field harus diisi";
    } elseif($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok";
    } elseif(strlen($password) < 6) {
        $error = "Password minimal 6 karakter";
    } else {
        // Cek username sudah digunakan atau belum
        $check_query = "SELECT * FROM Users WHERE username = ? OR email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if(mysqli_num_rows($check_result) > 0) {
            $existing_user = mysqli_fetch_assoc($check_result);
            if($existing_user['username'] === $username) {
                $error = "Username sudah digunakan";
            } else {
                $error = "Email sudah terdaftar";
            }
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Tambahkan user baru
            $insert_query = "INSERT INTO Users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $email, $hashed_password, $full_name);
            
            if(mysqli_stmt_execute($insert_stmt)) {
                $success = "Pendaftaran berhasil! Silakan login.";
                // Redirect ke halaman login setelah 2 detik
                header("refresh:2;url=login.php");
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Film Collection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .register-form {
            max-width: 500px;
            padding: 20px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .register-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .register-logo i {
            font-size: 3rem;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-form">
            <div class="register-logo">
                <i class="bi bi-film"></i>
                <h2>Film Collection</h2>
                <p class="text-muted">Daftar untuk mengelola koleksi film</p>
            </div>
            
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="full_name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Password minimal 6 karakter</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="register" class="btn btn-primary w-100">Daftar</button>
                <div class="text-center mt-3">
                    <p>Sudah punya akun? <a href="login.php">Login sekarang</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>