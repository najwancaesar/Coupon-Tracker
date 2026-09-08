<?php
require 'session_config.php';
require 'koneksi.php';
require 'csrf.php';

$error = '';

// Mencegah user yang sudah login masuk halaman ini lagi
if (isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// --- Bersihkan log attempt lama (lebih dari 1 jam) setiap kali halaman ini dibuka ---
$mysqli->query("DELETE FROM login_attempts WHERE waktu < NOW() - INTERVAL 1 HOUR");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nim      = trim($_POST['nim'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- 1.5. Rate Limiting: Cek menggunakan identifier gabungan NIM + IP ---
    // Ini mencegah brute-force dari 1 perangkat terhadap banyak NIM berbeda
    $ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $identifier  = hash('sha256', $nim . '|' . $ip); // Hash gabungan agar tidak simpan PII mentah
    $window_menit = 15;
    $max_attempt  = 5;

    $stmt_rate = $mysqli->prepare(
        "SELECT COUNT(*) as jumlah FROM login_attempts 
         WHERE identifier = ? AND waktu >= NOW() - INTERVAL ? MINUTE"
    );
    $stmt_rate->bind_param("si", $identifier, $window_menit);
    $stmt_rate->execute();
    $rate_result   = $stmt_rate->get_result()->fetch_assoc();
    $jumlah_attempt = (int)($rate_result['jumlah'] ?? 0);
    $stmt_rate->close();

    if ($jumlah_attempt >= $max_attempt) {
        $error = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam beberapa menit.";
    } else {
        // --- Proses login normal ---
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE nim = ?");

        if ($stmt) {
            $stmt->bind_param("s", $nim);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    // Login sukses — cegah session fixation
                    session_regenerate_id(true);

                    $_SESSION['id']               = $user['id'];
                    $_SESSION['user_id']          = $user['id'];
                    $_SESSION['nim']              = $user['nim'];
                    $_SESSION['username']         = $user['username'];
                    $_SESSION['nama_lengkap']     = $user['nama_lengkap'];
                    $_SESSION['status_pekerjaan'] = $user['status_pekerjaan'];
                    $_SESSION['role']             = $user['role'];
                    $_SESSION['must_change_password'] = (int)$user['must_change_password'];
                    $_SESSION['welcome_alert']    = true;

                    // Routing berdasarkan role
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Password yang Anda masukkan salah.";
                    // Catat percobaan gagal
                    $stmt_log = $mysqli->prepare("INSERT INTO login_attempts (identifier, waktu) VALUES (?, NOW())");
                    $stmt_log->bind_param("s", $identifier);
                    $stmt_log->execute();
                    $stmt_log->close();
                }
            } else {
                $error = "NIM tidak ditemukan di sistem.";
                // Catat percobaan gagal
                $stmt_log = $mysqli->prepare("INSERT INTO login_attempts (identifier, waktu) VALUES (?, NOW())");
                $stmt_log->bind_param("s", $identifier);
                $stmt_log->execute();
                $stmt_log->close();
            }
            $stmt->close();
        } else {
            $error = "Terjadi kesalahan koneksi database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kupon Makan Poltek GT</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tema Khusus Poltek GT -->
    <link rel="stylesheet" href="style.css">
    
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 CDN CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    
    <style>
        /* Modifikasi untuk form input-group agar seamless */
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 15px;
        }
        .header-title {
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 0.1rem;
            font-size: 1.8rem;
        }
        .header-subtitle {
            color: var(--accent-yellow);
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .input-group {
            border: 1px solid #ced4da;
            border-radius: 8px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            overflow: hidden;
            background: #fff;
        }
        .input-group:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 51, 102, 0.15);
        }
        .input-group-text {
            background-color: transparent;
            border: none;
            color: var(--primary-blue);
            padding-right: 5px;
        }
        .form-control {
            border: none;
            padding-left: 10px;
        }
        .form-control:focus {
            box-shadow: none;
        }
    </style>
</head>
<body class="vh-100 d-flex justify-content-center align-items-center bg-light">

    <div class="login-container">
        <!-- Penggunaan class card-custom dari style.css -->
        <div class="card card-custom p-4">
            <div class="card-body p-1">
                
                <!-- Header/Branding -->
                <div class="text-center mb-5 mt-2">
                    <i class="fa-solid fa-utensils fa-3x mb-3" style="color: var(--primary-blue);"></i>
                    <h2 class="header-title">Sistem Kupon Makan</h2>
                    <div class="header-subtitle">Politeknik Gajah Tunggal</div>
                </div>

                <!-- Form -->
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="nim" class="form-label text-secondary fw-bold small">NIM / USERNAME</label>
                        <div class="input-group py-1">
                            <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                            <input type="text" class="form-control" id="nim" name="nim" placeholder="Ketik NIM atau Username Admin" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <label for="password" class="form-label text-secondary fw-bold small">PASSWORD</label>
                        <div class="input-group py-1">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Ketik password Anda" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-poltek w-100 py-3 mb-2 d-flex align-items-center justify-content-center gap-2 shadow-sm rounded-pill">
                        Masuk ke Aplikasi <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

    <script>
        <?php if (!empty($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Login',
                text: '<?= addslashes($error) ?>',
                confirmButtonColor: 'var(--primary-blue)',
                confirmButtonText: 'Coba Lagi',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
