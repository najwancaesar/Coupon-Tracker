<?php
session_start();
require 'koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Mengambil data untuk statistik
// 1. Total User Terdaftar (Kecuali Admin)
$query_users = $mysqli->query("SELECT COUNT(id) as total_users FROM users WHERE role != 'admin'");
$total_users = $query_users->fetch_assoc()['total_users'];

// 2. Total Kupon Beredar (Total sisa_kupon)
$query_kupon_beredar = $mysqli->query("SELECT SUM(sisa_kupon) as total_beredar FROM pemasukan_kupon");
$total_kupon_beredar = $query_kupon_beredar->fetch_assoc()['total_beredar'] ?? 0;

// 3. Total Kupon Terpakai (Total dari riwayat_kupon)
$query_kupon_terpakai = $mysqli->query("SELECT SUM(jumlah_pakai) as total_terpakai FROM riwayat_kupon");
$total_kupon_terpakai = $query_kupon_terpakai->fetch_assoc()['total_terpakai'] ?? 0;

// Mengambil data user untuk tabel
$query_all_users = $mysqli->query("SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC");
$all_users = [];
while($row = $query_all_users->fetch_assoc()) {
    $all_users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Kupon</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tema Khusus Poltek GT -->
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CDN CSS -->
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
                    <li class="nav-item"><a class="nav-link active fw-semibold" href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard Admin</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_monitoring.php"><i class="fa-solid fa-desktop"></i> Monitoring Aktivitas</a></li>
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

    <div class="container-fluid px-4 pb-5">
        
        <div class="mb-4">
            <h4 class="fw-bold text-dark">Dashboard Administrator</h4>
            <p class="text-muted">Ringkasan sistem dan manajemen pengguna.</p>
        </div>

        <!-- Section 1: Cards Statistik Global -->
        <div class="row mb-4 align-items-stretch">
            <div class="col-12 col-md-4 mb-3">
                <div class="card card-custom h-100 p-3 border-start border-primary border-5">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold text-uppercase">Total User</h6>
                                <div class="stat-value text-primary"><?= number_format($total_users) ?></div>
                            </div>
                            <div class="text-primary opacity-50"><i class="fa-solid fa-users fa-3x"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4 mb-3">
                <div class="card card-custom h-100 p-3 border-start border-info border-5">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold text-uppercase">Kupon Beredar</h6>
                                <div class="stat-value text-info"><?= number_format($total_kupon_beredar) ?></div>
                            </div>
                            <div class="text-info opacity-50"><i class="fa-solid fa-ticket fa-3x"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4 mb-3">
                <div class="card card-custom h-100 p-3 border-start border-warning border-5">
                    <div class="card-body text-left">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold text-uppercase">Total Terpakai</h6>
                                <div class="stat-value text-warning"><?= number_format($total_kupon_terpakai) ?></div>
                            </div>
                            <div class="text-warning opacity-50"><i class="fa-solid fa-fire fa-3x"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Tabel Manajemen User -->
        <div class="row">
            <div class="col-12">
                <div class="card card-custom h-100 p-0">
                    <div class="card-header bg-white fw-bold text-secondary py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa-solid fa-users-gear me-2"></i> Kelola Pengguna
                        </div>
                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                            <i class="fa-solid fa-plus me-1"></i> Tambah User Baru
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">No</th>
                                        <th>NIM / NIP</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Status Pekerjaan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($all_users) > 0): ?>
                                        <?php $no = 1; foreach ($all_users as $u): ?>
                                            <tr>
                                                <td class="px-4"><?= $no++ ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($u['nim']) ?></td>
                                                <td><?= htmlspecialchars($u['username']) ?></td>
                                                <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                                <td>
                                                    <?php if($u['status_pekerjaan'] == 'Karyawan'): ?>
                                                        <span class="badge bg-primary"><i class="fa-solid fa-user-tie"></i> Karyawan</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><i class="fa-solid fa-user-graduate"></i> Mahasiswa</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-edit" 
                                                            data-id="<?= $u['id'] ?>" 
                                                            data-nim="<?= htmlspecialchars($u['nim']) ?>" 
                                                            data-username="<?= htmlspecialchars($u['username']) ?>" 
                                                            data-nama="<?= htmlspecialchars($u['nama_lengkap']) ?>" 
                                                            data-status="<?= htmlspecialchars($u['status_pekerjaan']) ?>" 
                                                            title="Edit User">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning btn-reset-pass" data-href="proses_admin_user.php?aksi=reset_pass&id=<?= $u['id'] ?>&nim=<?= htmlspecialchars($u['nim']) ?>" title="Reset Password">
                                                            <i class="fa-solid fa-key"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-hapus-user" data-href="proses_admin_user.php?aksi=hapus&id=<?= $u['id'] ?>" title="Hapus User">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fa-solid fa-user-slash fa-2x mb-2 d-block"></i>Belum ada data pengguna.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Tambah User -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalTambahUserLabel"><i class="fa-solid fa-user-plus me-2"></i>Tambah User Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_admin_user.php?aksi=tambah" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-solid fa-id-card me-1"></i> NIM / NIP</label>
                            <input type="text" name="nim" class="form-control" placeholder="Masukkan NIM atau NIP" required>
                            <small class="text-muted d-block mt-1">Gunakan NIM untuk Mahasiswa, atau NIP untuk Karyawan.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-solid fa-at me-1"></i> Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Pilih username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-regular fa-id-badge me-1"></i> Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Ketik nama lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-solid fa-briefcase me-1"></i> Status Pekerjaan</label>
                            <select name="status_pekerjaan" class="form-select" required>
                                <option value="Mahasiswa">Mahasiswa</option>
                                <option value="Karyawan">Karyawan</option>
                            </select>
                        </div>
                        <div class="alert alert-info py-2 small mb-0 rounded-3">
                            <i class="fa-solid fa-circle-info me-1"></i> Password default akun akan disamakan dengan NIM.
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-poltek rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_admin_user.php?aksi=edit" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-solid fa-id-card me-1"></i> NIM / NIP</label>
                            <input type="text" name="nim" id="edit_nim" class="form-control" placeholder="Masukkan NIM atau NIP" required>
                            <small class="text-muted d-block mt-1">Gunakan NIM untuk Mahasiswa, atau NIP untuk Karyawan.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-solid fa-at me-1"></i> Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-regular fa-id-badge me-1"></i> Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small text-uppercase"><i class="fa-solid fa-briefcase me-1"></i> Status Pekerjaan</label>
                            <select name="status_pekerjaan" id="edit_status" class="form-select" required>
                                <option value="Mahasiswa">Mahasiswa</option>
                                <option value="Karyawan">Karyawan</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Update Data</button>
                    </div>
                </form>
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
        // Animasi ScrollReveal
        ScrollReveal().reveal('.card-custom', { 
            delay: 100, 
            distance: '30px', 
            origin: 'bottom', 
            interval: 100 
        });

        // Pop-up Welcome Khusus Admin
        <?php if (isset($_SESSION['welcome_alert'])): ?>
            Swal.fire({ 
                title: 'Selamat Datang, Admin!', 
                text: 'Selamat bekerja dan pantau terus sistem kupon.', 
                icon: 'success', 
                timer: 2000, 
                showConfirmButton: false 
            });
            <?php unset($_SESSION['welcome_alert']); ?>
        <?php endif; ?>

        // Notifikasi Global Admin
        <?php if (isset($_SESSION['sukses'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= addslashes($_SESSION['sukses']) ?>',
                confirmButtonColor: 'var(--primary-blue)',
                confirmButtonText: 'Oke'
            });
            <?php unset($_SESSION['sukses']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: '<?= addslashes($_SESSION['error']) ?>',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Tutup'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        // Open Modal Edit & Fill Data
        const modalEdit = new bootstrap.Modal(document.getElementById('modalEditUser'));
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nim').value = this.getAttribute('data-nim');
                document.getElementById('edit_username').value = this.getAttribute('data-username');
                document.getElementById('edit_nama').value = this.getAttribute('data-nama');
                document.getElementById('edit_status').value = this.getAttribute('data-status');
                modalEdit.show();
            });
        });

        // Reset Password Konfirmasi
        document.querySelectorAll('.btn-reset-pass').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetUrl = this.getAttribute('data-href');
                Swal.fire({
                    title: 'Reset Kata Sandi?',
                    text: 'Password akun ini akan dikembalikan menjadi sama dengan NIM-nya.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Reset Password!'
                }).then((result) => {
                    if(result.isConfirmed) window.location.href = targetUrl;
                });
            });
        });

        // Hapus Data Konfirmasi
        document.querySelectorAll('.btn-hapus-user').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetUrl = this.getAttribute('data-href');
                Swal.fire({
                    title: 'Hapus User Permanen?',
                    text: 'Perhatian! Semua data pemasukan & pemakaian kupon dari user ini akan ikut musnah dan tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus Semua!'
                }).then((result) => {
                    if(result.isConfirmed) window.location.href = targetUrl;
                });
            });
        });

        // Konfirmasi Logout yang Elegan (seperti di user dashboard)
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
