<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

// Default variabel
$nama_lengkap = "User";
$nim = "";
$status_pekerjaan = "";

// Lakukan query SELECT terbaru
$query = "SELECT nama_lengkap, nim, status_pekerjaan FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $nama_lengkap = $user['nama_lengkap'];
        $nim = $user['nim'];
        $status_pekerjaan = $user['status_pekerjaan'];
    }
    $stmt->close();
} else {
    $nama_lengkap = $_SESSION['nama_lengkap'] ?? 'User';
    $nim = $_SESSION['nim'] ?? '';
    $status_pekerjaan = $_SESSION['status_pekerjaan'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Sistem Kupon Makan</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tema Khusus Poltek GT -->
    <link rel="stylesheet" href="style.css">
    
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 CDN CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    
    <style>
        .profile-header {
            background: var(--primary-blue);
            color: white;
            text-align: center;
            padding: 40px 20px;
            border-radius: 15px 15px 0 0;
            position: relative;
        }
        .avatar-circle {
            width: 100px;
            height: 100px;
            background-color: var(--accent-yellow);
            color: var(--primary-blue);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 15px;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        /* Styling Input Group */
        .input-group-text {
            background-color: transparent;
            border-right: none;
            color: var(--primary-blue);
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }
        .input-group {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .input-group:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-5" style="background-color: var(--primary-blue) !important;">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fa-solid fa-ticket-alt me-2 text-warning"></i> Saldo Kupon
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link fw-semibold" href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link text-danger bg-white px-3 rounded fw-semibold mt-2 mt-lg-0" href="#" id="btn-logout">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                
                <div class="card card-custom p-0 mb-4">
                    <!-- Bagian Atas Card -->
                    <div class="profile-header">
                        <div class="avatar-circle">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <h3 class="fw-bold mb-1"><?= htmlspecialchars($nama_lengkap) ?></h3>
                        <p class="mb-0 text-white-50 fw-semibold"><i class="fa-solid fa-briefcase me-1"></i> <?= htmlspecialchars($status_pekerjaan) ?></p>
                    </div>
                    
                    <!-- Bagian Bawah Card -->
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- Informasi Pribadi -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="info-label"><i class="fa-solid fa-id-card me-1"></i> NIM</div>
                                <div class="info-value text-primary fs-5"><?= htmlspecialchars($nim) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label"><i class="fa-regular fa-id-badge me-1"></i> Nama Lengkap</div>
                                <div class="info-value fs-5"><?= htmlspecialchars($nama_lengkap) ?></div>
                            </div>
                        </div>

                        <hr class="my-5 text-muted opacity-25">

                        <!-- Form Ganti Password -->
                        <h5 class="fw-bold mb-4" style="color: var(--primary-blue);"><i class="fa-solid fa-lock me-2"></i> Ganti Password</h5>
                        
                        <form action="proses_edit_password.php" method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary small">PASSWORD LAMA</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                    <input type="password" name="password_lama" class="form-control py-2" required placeholder="Masukkan password lama Anda">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary small">PASSWORD BARU</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="password_baru" class="form-control py-2" required placeholder="Masukkan password baru">
                                </div>
                            </div>
                            <div class="mb-5">
                                <label class="form-label fw-semibold text-secondary small">KONFIRMASI PASSWORD BARU</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock-open"></i></span>
                                    <input type="password" name="konfirmasi_password_baru" class="form-control py-2" required placeholder="Ulangi password baru Anda">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-poltek w-100 py-3 shadow-sm rounded-pill fw-bold">
                                <i class="fa-solid fa-save me-1"></i> Simpan Password Baru
                            </button>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

    <script>
        // Logika Notifikasi menggunakan SweetAlert2
        <?php if (isset($_SESSION['sukses'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= addslashes($_SESSION['sukses']) ?>',
                confirmButtonColor: 'var(--primary-blue)',
                confirmButtonText: 'Oke',
                showClass: { popup: 'animate__animated animate__fadeInDown' }
            });
            <?php unset($_SESSION['sukses']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '<?= addslashes($_SESSION['error']) ?>',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Tutup',
                showClass: { popup: 'animate__animated animate__shakeX' }
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        // Konfirmasi Logout
        document.getElementById('btn-logout').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Yakin ingin logout?',
                text: 'Sesi Anda akan diakhiri.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#003366',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Keluar!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        });
    </script>
</body>
</html>
