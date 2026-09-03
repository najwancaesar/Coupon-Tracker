<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Ambil ID user dari session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

// Default variabel (fallback)
$nama_lengkap = "User";
$username = "";
$status_pekerjaan = "";

// Lakukan query SELECT untuk memastikan data yang ditampilkan selalu yang terbaru dari database
$query = "SELECT nama_lengkap, username, status_pekerjaan FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $nama_lengkap = $user['nama_lengkap'];
        $username = $user['username'];
        $status_pekerjaan = $user['status_pekerjaan'];
    }
    $stmt->close();
} else {
    // Jika query gagal karena suatu hal, kita fallback menggunakan data session saat login
    $nama_lengkap = $_SESSION['nama_lengkap'] ?? 'User';
    $username = $_SESSION['username'] ?? '';
    $status_pekerjaan = $_SESSION['status_pekerjaan'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Kupon Makan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f7f6; 
        }
        .navbar-brand { 
            font-weight: 700; 
            letter-spacing: 0.5px; 
        }
        .profile-wrapper { 
            min-height: calc(100vh - 80px); /* 80px asumsi tinggi navbar */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .profile-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            background-color: #ffffff;
        }
        .profile-header {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            padding: 40px 20px;
            text-align: center;
            color: white;
            position: relative;
        }
        .avatar-placeholder {
            width: 110px;
            height: 110px;
            background-color: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            margin-bottom: 15px;
            border: 4px solid rgba(255,255,255,0.6);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .profile-body {
            padding: 35px;
        }
        .info-group {
            margin-bottom: 22px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 18px;
        }
        .info-group:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            font-size: 0.85rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .info-value {
            font-size: 1.15rem;
            color: #2c3e50;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="dashboard.php">🎟️ Saldo Kupon</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="profile.php">Profile</a></li>
                    <li class="nav-item ms-lg-2"><a class="nav-link text-danger bg-white px-3 rounded fw-semibold mt-2 mt-lg-0" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Profile (Tengah) -->
    <div class="container profile-wrapper">
        <div class="card profile-card">
            
            <!-- Bagian Atas Card (Biru/Gradient) -->
            <div class="profile-header">
                <div class="avatar-placeholder">
                    🧑‍💼
                </div>
                <h3 class="mb-1 fw-bold"><?= htmlspecialchars($nama_lengkap) ?></h3>
                <p class="mb-0 mt-1 opacity-75 fs-5">
                    <?= htmlspecialchars($status_pekerjaan) ?>
                </p>
            </div>
            
            <!-- Bagian Bawah Card (Informasi) -->
            <div class="profile-body">
                
                <div class="info-group">
                    <div class="info-label">Nama Lengkap</div>
                    <div class="info-value"><?= htmlspecialchars($nama_lengkap) ?></div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Username</div>
                    <div class="info-value text-primary">@<?= htmlspecialchars($username) ?></div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Status Pekerjaan / Peran</div>
                    <div class="info-value">
                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill border border-info shadow-sm">
                            <?= htmlspecialchars($status_pekerjaan) ?>
                        </span>
                    </div>
                </div>

                <!-- Tombol Kembali -->
                <div class="mt-5 text-center">
                    <a href="dashboard.php" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm transition">
                        ⬅️ Kembali ke Dashboard
                    </a>
                </div>
                
            </div>
            
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
