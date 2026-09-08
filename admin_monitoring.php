<?php
session_start();
require 'koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Query untuk mengambil riwayat global (JOIN riwayat_kupon dan users)
$query_riwayat = "SELECT r.*, u.nama_lengkap, u.nim, u.status_pekerjaan 
                  FROM riwayat_kupon r 
                  JOIN users u ON r.user_id = u.id 
                  ORDER BY r.tanggal_pakai DESC, r.id DESC";
$result = $mysqli->query($query_riwayat);

$riwayat_global = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $riwayat_global[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Aktivitas - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php">
                <i class="fa-solid fa-user-shield me-2 text-warning"></i> Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link fw-semibold" href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard Admin</a></li>
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="admin_monitoring.php"><i class="fa-solid fa-desktop"></i> Monitoring Aktivitas</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link text-danger bg-white px-3 rounded fw-semibold mt-2 mt-lg-0" href="#" id="btn-logout">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container px-4 pb-5">
        <div class="mb-4">
            <h4 class="fw-bold text-dark"><i class="fa-solid fa-satellite-dish text-primary me-2"></i>Live Monitoring</h4>
            <p class="text-muted">Pantau aktivitas pemakaian kupon makan seluruh pengguna secara real-time.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <!-- Card Header & Filter -->
                <div class="card card-custom border-0 shadow-sm mb-4">
                    <div class="card-body p-4 bg-white rounded">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h5 class="fw-bold mb-0 text-secondary"><i class="fa-solid fa-clock-rotate-left me-2"></i>Global Activity Feed</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group shadow-sm rounded-pill overflow-hidden">
                                    <span class="input-group-text bg-white border-end-0 text-primary ps-3"><i class="fa-solid fa-search"></i></span>
                                    <input type="text" id="searchInput" class="form-control border-start-0 bg-white py-2" placeholder="Cari nama pengguna atau keterangan...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feed Container -->
                <div class="feed-container" id="feedContainer">
                    <?php if (count($riwayat_global) > 0): ?>
                        <?php foreach ($riwayat_global as $rg): ?>
                            <!-- Activity Item -->
                            <div class="card mb-3 shadow-sm border-0 border-start border-info border-4 activity-item searchable-item">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Avatar Bulat -->
                                        <div class="flex-shrink-0">
                                            <div style="width:55px;height:55px;border-radius:50%;background:var(--primary-blue);display:flex;align-items:center;justify-content:center;font-size:1.5rem;" class="shadow-sm">
                                                <i class="fa-solid fa-user-check text-white"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Konten -->
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-bold text-dark fs-5 mb-1 searchable-text">
                                                <?php if($rg['status_pekerjaan'] == 'Karyawan'): ?>
                                                    <i class="fa-solid fa-briefcase text-primary me-2" title="Karyawan"></i>
                                                <?php else: ?>
                                                    <i class="fa-solid fa-graduation-cap text-success me-2" title="Mahasiswa"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($rg['nama_lengkap']) ?>
                                            </div>
                                            <p class="text-muted mb-0 lh-lg">
                                                Telah menggunakan <span class="badge bg-danger rounded-pill px-2 py-1 mx-1 shadow-sm fs-6">-<?= $rg['jumlah_pakai'] ?> Kupon</span> 
                                                untuk <strong class="searchable-desc text-dark">"<?= htmlspecialchars($rg['keterangan'] ?? 'Makan') ?>"</strong> 
                                                pada <span class="text-primary fw-semibold"><i class="fa-regular fa-calendar-check mx-1"></i><?= date('d M Y', strtotime($rg['tanggal_pakai'])) ?></span>.
                                            </p>
                                        </div>
                                        
                                        <!-- Status Badge Kanan (Opsional) -->
                                        <div class="flex-shrink-0 text-end d-none d-md-block">
                                            <?php if($rg['status_pekerjaan'] == 'Karyawan'): ?>
                                                <span class="badge bg-info text-dark opacity-75 mb-1 px-3 py-2"><i class="fa-solid fa-user-tie"></i> Karyawan</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary opacity-75 mb-1 px-3 py-2"><i class="fa-solid fa-user-graduate"></i> Mahasiswa</span>
                                            <?php endif; ?>
                                            <div class="small text-muted fw-bold">NIM: <?= htmlspecialchars($rg['nim']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 mt-4">
                            <i class="fa-solid fa-mug-hot fa-4x text-muted opacity-25 mb-3"></i>
                            <h5 class="text-muted">Sistem sedang tenang.</h5>
                            <p class="text-muted small">Belum ada aktivitas pemakaian kupon dari pengguna manapun.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="noResults" class="text-center py-5 mt-4 d-none">
                    <i class="fa-solid fa-search-minus fa-4x text-muted opacity-25 mb-3"></i>
                    <h5 class="text-muted fw-bold">Aktivitas tidak ditemukan.</h5>
                    <p class="text-muted small">Coba gunakan kata kunci pencarian yang lain.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

    <script>
        // Animasi elegan khas Poltek GT
        ScrollReveal().reveal('.card-custom', { delay: 100, distance: '30px', origin: 'top' });
        ScrollReveal().reveal('.activity-item', { delay: 150, distance: '40px', origin: 'left', interval: 90 });

        // Fitur Pencarian Cepat (Live Search Instan)
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.searchable-item');
            let visibleCount = 0;
            
            items.forEach(function(item) {
                let textName = item.querySelector('.searchable-text').textContent.toLowerCase();
                let textDesc = item.querySelector('.searchable-desc').textContent.toLowerCase();
                
                // Cari di Nama Lengkap atau Keterangan/Notes
                if (textName.includes(filter) || textDesc.includes(filter)) {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            });
            
            // Tampilkan ilustrasi/pesan khusus jika tidak ada hasil pencarian yang cocok
            if (visibleCount === 0 && filter !== '') {
                document.getElementById('noResults').classList.remove('d-none');
            } else {
                document.getElementById('noResults').classList.add('d-none');
            }
        });

        // Konfirmasi Logout
        document.getElementById('btn-logout').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Yakin ingin logout?',
                text: 'Sesi Admin Anda akan diakhiri.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#003366',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Keluar!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Terima Kasih Admin!',
                        text: 'Sampai jumpa kembali.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'logout.php';
                    });
                }
            });
        });
    </script>
</body>
</html>
