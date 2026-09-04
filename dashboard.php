<?php
session_start();
require 'koneksi.php';

// Cek session login, proteksi akses
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];
$nama_lengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User';

// 1. Hitung Saldo Kupon Aktif & Sisa Kupon
$total_aktif = 0;
$sisa_kupon = 0;
$query_saldo = "SELECT SUM(jumlah_kupon) as total_kupon, SUM(sisa_kupon) as total_sisa FROM pemasukan_kupon WHERE user_id = ? AND tanggal_expired >= CURRENT_DATE()";
$stmt = $mysqli->prepare($query_saldo);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $total_aktif = $row['total_kupon'] ? $row['total_kupon'] : 0;
        $sisa_kupon = $row['total_sisa'] ? $row['total_sisa'] : 0;
    }
    $stmt->close();
}

// 2. Hitung Total Kupon Terpakai (Bulan/Periode Berjalan)
// Secara logis, terpakai = Jatah awal - Sisa bersih saat ini
$total_terpakai = $total_aktif - $sisa_kupon;

// --- START LOGIKA INSIGHT (TREN PEMAKAIAN) ---
$bulan_ini = date('m');
$tahun_ini = date('Y');

// Penanganan pergantian tahun untuk bulan lalu
if ($bulan_ini == 1) {
    $bulan_lalu = 12;
    $tahun_lalu = $tahun_ini - 1;
} else {
    $bulan_lalu = $bulan_ini - 1;
    $tahun_lalu = $tahun_ini;
}

// 2A. Pemakaian Bulan Ini
$pakai_bulan_ini = 0;
$query_pakai_ini = "SELECT SUM(jumlah_pakai) as total FROM riwayat_kupon WHERE user_id = ? AND MONTH(tanggal_pakai) = ? AND YEAR(tanggal_pakai) = ?";
$stmt = $mysqli->prepare($query_pakai_ini);
if ($stmt) {
    $stmt->bind_param("iii", $user_id, $bulan_ini, $tahun_ini);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pakai_bulan_ini = $row['total'] ? $row['total'] : 0;
    }
    $stmt->close();
}

// 2B. Pemakaian Bulan Lalu
$pakai_bulan_lalu = 0;
$query_pakai_lalu = "SELECT SUM(jumlah_pakai) as total FROM riwayat_kupon WHERE user_id = ? AND MONTH(tanggal_pakai) = ? AND YEAR(tanggal_pakai) = ?";
$stmt = $mysqli->prepare($query_pakai_lalu);
if ($stmt) {
    $stmt->bind_param("iii", $user_id, $bulan_lalu, $tahun_lalu);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pakai_bulan_lalu = $row['total'] ? $row['total'] : 0;
    }
    $stmt->close();
}

// 2C. Hitung Tren (Selisih)
$selisih_pakai = $pakai_bulan_ini - $pakai_bulan_lalu;
$teks_tren = "";
$ikon_tren = "";
$warna_tren = "";

if ($selisih_pakai > 0) {
    $teks_tren = "Naik $selisih_pakai kupon dari bulan lalu";
    $ikon_tren = "fa-arrow-trend-up";
    $warna_tren = "text-danger"; // Pemakaian meningkat
} elseif ($selisih_pakai < 0) {
    $selisih_positif = abs($selisih_pakai);
    $teks_tren = "Turun $selisih_positif kupon dari bulan lalu";
    $ikon_tren = "fa-arrow-trend-down";
    $warna_tren = "text-success"; // Lebih hemat
} else {
    $teks_tren = "Sama persis dengan bulan lalu";
    $ikon_tren = "fa-equals";
    $warna_tren = "text-secondary";
}
// --- END LOGIKA INSIGHT ---

// 4. Ambil Data Riwayat Pemasukan Kupon
$riwayat_pemasukan = [];
$query_rp = "SELECT * FROM pemasukan_kupon WHERE user_id = ? ORDER BY tanggal_input DESC";
$stmt = $mysqli->prepare($query_rp);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $riwayat_pemasukan[] = $row;
    }
    $stmt->close();
}

// 5. Ambil Data Riwayat Pemakaian Kupon
$riwayat_pemakaian = [];
$query_rk = "SELECT * FROM riwayat_kupon WHERE user_id = ? ORDER BY tanggal_pakai DESC";
$stmt = $mysqli->prepare($query_rk);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $riwayat_pemakaian[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Saldo Kupon</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tema Khusus Poltek GT -->
    <link rel="stylesheet" href="style.css">
    
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 CDN CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    
    <style>
        .stat-value { font-size: 2.5rem; font-weight: bold; }
        .stat-sisa { font-size: 3.5rem; font-weight: 800; color: var(--accent-yellow); }
        .table-container { border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        /* Override primary navbar color to Poltek theme */
        .bg-primary { background-color: var(--primary-blue) !important; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fa-solid fa-ticket-alt me-2 text-warning"></i> Saldo Kupon
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link text-danger bg-white px-3 rounded fw-semibold mt-2 mt-lg-0" href="#" id="btn-logout">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        
        <div class="mb-4">
            <h4 class="fw-bold text-dark">Halo, <?= htmlspecialchars($nama_lengkap) ?>!</h4>
            <p class="text-muted">Kelola saldo kupon makan Anda secara real-time.</p>
        </div>

        <!-- Section 1: Cards Statistik -->
        <div class="row mb-4 align-items-stretch">
            <div class="col-12 col-md-4 mb-3">
                <div class="card card-custom h-100 p-3 border-start border-primary border-5">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold text-uppercase">Kupon Aktif</h6>
                                <div class="stat-value text-primary"><?= $total_aktif ?></div>
                            </div>
                            <div class="text-primary opacity-50"><i class="fa-solid fa-tags fa-3x"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4 mb-3">
                <div class="card card-custom h-100 p-3 border-start border-warning border-5">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold text-uppercase">Terpakai</h6>
                                <div class="stat-value text-warning"><?= $total_terpakai ?></div>
                            </div>
                            <div class="text-warning opacity-50"><i class="fa-solid fa-utensils fa-3x"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4 mb-3">
                <div class="card card-custom h-100 p-3 border-start border-success border-5" style="background-color: var(--primary-blue); color: white;">
                    <div class="card-body text-center">
                        <h6 class="text-white fw-bold text-uppercase mb-1"><i class="fa-solid fa-wallet"></i> Sisa Kupon</h6>
                        <div class="stat-sisa"><?= $sisa_kupon ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Form Input -->
        <div class="row mb-5">
            <!-- Form Tambah Jatah -->
            <div class="col-12 col-md-6 mb-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold text-primary py-3">
                        <i class="fa-solid fa-circle-plus"></i> Input Jatah Kupon
                    </div>
                    <div class="card-body p-4">
                        <form action="proses_tambah_jatah.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Tanggal Input</label>
                                <input type="date" name="tanggal_input" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Jumlah Kupon</label>
                                <input type="number" name="jumlah_kupon" class="form-control" placeholder="Contoh: 22" min="1" max="31" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-muted">Tanggal Kedaluwarsa</label>
                                <input type="date" name="tanggal_expired" class="form-control" value="<?= date('Y-m-t') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-poltek w-100"><i class="fa-solid fa-save"></i> Simpan Jatah</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Form Pemakaian Kupon -->
            <div class="col-12 col-md-6 mb-4">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold text-warning py-3">
                        <i class="fa-solid fa-bell-concierge"></i> Catat Pemakaian Kupon
                    </div>
                    <div class="card-body p-4">
                        <form action="proses_pakai_kupon.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Tanggal Pakai</label>
                                <input type="date" name="tanggal_pakai" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Jumlah Pakai</label>
                                <input type="number" name="jumlah_pakai" class="form-control" value="1" min="1" max="<?= ($sisa_kupon > 0) ? $sisa_kupon : 1 ?>" required>
                                <?php if ($sisa_kupon <= 0): ?>
                                    <small class="text-danger mt-1 d-block"><i class="fa-solid fa-circle-exclamation"></i> Saldo habis, tidak bisa mencatat pemakaian.</small>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-muted">Keterangan / Notes</label>
                                <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Makan siang, Beli es krim, dll" required>
                            </div>
                            <button type="submit" class="btn btn-poltek w-100" <?= ($sisa_kupon <= 0) ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-check-circle"></i> Catat Pemakaian
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2.5: Insight & Informasi -->
        <div class="row mb-5">
            <!-- Card Insight -->
            <div class="col-12 col-md-6 mb-4 mb-md-0">
                <div class="card card-custom h-100 p-2 border-start border-info border-5">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-muted fw-bold text-uppercase mb-0"><i class="fa-solid fa-chart-line me-2 text-info"></i> Insight Bulan Ini</h6>
                        </div>
                        <h2 class="fw-bold mb-1 text-dark"><?= $pakai_bulan_ini ?> <span class="fs-5 text-muted fw-normal">Kupon Dipakai</span></h2>
                        <div class="d-flex align-items-center mt-3">
                            <span class="<?= $warna_tren ?> fw-bold"><i class="fa-solid <?= $ikon_tren ?> me-1"></i> <?= $teks_tren ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Info FEFO -->
            <div class="col-12 col-md-6">
                <div class="card card-custom h-100 p-2" style="background-color: #fcf9f2; border-left: 5px solid var(--accent-yellow);">
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-lightbulb text-warning me-2"></i> Informasi Kupon</h6>
                        <p class="text-muted small mb-0 lh-lg">
                            Pastikan untuk selalu mengecek masa aktif kupon. Kupon akan hangus secara otomatis <strong>2 bulan</strong> setelah tanggal didapatkan menggunakan sistem <strong>FEFO</strong> <em>(First Expired, First Out)</em>.
                            <br><br>
                            Sistem <strong>FEFO</strong> memastikan kupon yang masa kedaluwarsanya paling dekat akan diprioritaskan untuk dipotong lebih dulu saat Anda makan di kantin, sehingga tidak ada kupon yang terbuang sia-sia.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Tabel Riwayat -->
        <div class="row">
            <!-- Tabel Pemasukan -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card card-custom h-100 p-0">
                    <div class="card-header bg-white fw-bold text-secondary py-3 border-bottom-0">
                        <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pemasukan
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th class="px-3">Tgl Input</th>
                                    <th>Jumlah</th>
                                    <th>Tgl Expired</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($riwayat_pemasukan) > 0): ?>
                                    <?php foreach ($riwayat_pemasukan as $rp): ?>
                                        <tr>
                                            <td class="px-3"><?= date('d/m/Y', strtotime($rp['tanggal_input'] ?? $rp['created_at'])) ?></td>
                                            <td class="fw-bold text-primary">+<?= $rp['jumlah_kupon'] ?></td>
                                            <td>
                                                <?php 
                                                $exp = $rp['tanggal_expired'] ?? null;
                                                if($exp) {
                                                    $is_expired = strtotime($exp) < strtotime(date('Y-m-d'));
                                                    echo $is_expired ? '<span class="badge bg-danger">'.date('d/m/Y', strtotime($exp)).'</span>' : date('d/m/Y', strtotime($exp));
                                                } else {
                                                    echo "-";
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada riwayat.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tabel Pemakaian -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card card-custom h-100 p-0">
                    <div class="card-header bg-white fw-bold text-secondary py-3 border-bottom-0">
                        <i class="fa-solid fa-list-check"></i> Riwayat Pemakaian
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-warning">
                                <tr>
                                    <th class="px-3">Tgl Pakai</th>
                                    <th>Jumlah Dipakai</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($riwayat_pemakaian) > 0): ?>
                                    <?php foreach ($riwayat_pemakaian as $rk): ?>
                                        <tr>
                                            <td class="px-3"><?= date('d/m/Y', strtotime($rk['tanggal_pakai'])) ?></td>
                                            <td class="fw-bold text-danger">-<?= $rk['jumlah_pakai'] ?></td>
                                            <td><?= htmlspecialchars($rk['keterangan'] ?? '-') ?></td>
                                            <td><span class="badge bg-success"><i class="fa-solid fa-check"></i> Tercatat</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada riwayat.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- ScrollReveal JS CDN -->
    <script src="https://unpkg.com/scrollreveal"></script>
    
    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

    <script>
        // Animasi ScrollReveal untuk Class card-custom
        ScrollReveal().reveal('.card-custom', { 
            delay: 200, 
            distance: '50px', 
            origin: 'bottom', 
            interval: 100 
        });

        // Pop-up Welcome (Muncul saat baru login)
        <?php if (isset($_SESSION['welcome_alert'])): ?>
            Swal.fire({ 
                title: 'Selamat Datang!', 
                text: 'Halo, selamat datang kembali di Sistem Kupon!', 
                icon: 'success', 
                timer: 2000, 
                showConfirmButton: false 
            });
            <?php unset($_SESSION['welcome_alert']); ?>
        <?php endif; ?>

        // SweetAlert2 Notifikasi (Pengganti Alert Bootstrap)
        <?php if (isset($_SESSION['sukses'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= addslashes($_SESSION['sukses']) ?>',
                confirmButtonColor: 'var(--primary-blue)',
                confirmButtonText: 'Oke'
            });
            <?php 
                unset($_SESSION['sukses']); 
                unset($_SESSION['pesan']); // Clear fallback
                unset($_SESSION['tipe_pesan']); 
            ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: '<?= addslashes($_SESSION['error']) ?>',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Tutup'
            });
            <?php 
                unset($_SESSION['error']); 
                unset($_SESSION['pesan']); // Clear fallback
                unset($_SESSION['tipe_pesan']); 
            ?>
        <?php elseif (isset($_SESSION['pesan'])): ?>
            Swal.fire({
                icon: '<?= ($_SESSION['tipe_pesan'] == 'danger') ? 'error' : (($_SESSION['tipe_pesan'] == 'success') ? 'success' : 'info') ?>',
                title: 'Info',
                text: '<?= addslashes($_SESSION['pesan']) ?>',
                confirmButtonColor: 'var(--primary-blue)',
                confirmButtonText: 'Oke'
            });
            <?php unset($_SESSION['pesan'], $_SESSION['tipe_pesan']); ?>
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
