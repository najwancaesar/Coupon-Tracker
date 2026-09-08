# Kebijakan Keamanan & Panduan Pembersihan Repositori

Dokumen ini menjelaskan arsitektur keamanan pada aplikasi **Coupon-Tracker** serta instruksi penting mengenai riwayat Git sebelum mempublikasikan repositori ini.

---

## ⚠️ PERINGATAN PENTING: Riwayat Git Masa Lalu (Sensitive Data in Commit History)

> [!CAUTION]
> **JANGAN LANGSUNG ME-MAKE-PUBLIC REPOSITORI INI TANPA MEMBERSIHKAN HISTORI GIT LAMA!**

Pada versi repositori terdahulu, commit history mengandung data pribadi asli (nama mahasiswa/karyawan, NIM/NIP asli) serta hash password awal. Meskipun file di branch aktif saat ini sudah dibersihkan total dan diganti data fiktif, **seluruh commit lama masih menyimpan data tersebut jika riwayat Git tidak dibersihkan**.

AI Coding Agent secara tegas **TIDAK** menjalankan pembersihan riwayat Git atau `git push --force` secara otomatis demi mencegah rusaknya repositori atau kehilangan data tak terduga. Pemilik repositori wajib melakukan salah satu dari dua opsi tindakan manual berikut:

### Opsi A (Paling Mudah & Sangat Disarankan): Buat Repositori Baru yang Bersih
1. Inisialisasi folder ini ke remote baru tanpa membawa history lama:
   ```bash
   # Hapus folder .git lama
   rm -rf .git  # (atau Remove-Item -Recurse -Force .git di PowerShell)
   
   # Buat repositori baru
   git init
   git add .
   git commit -m "Initial release: Coupon-Tracker with Security Hardening"
   git branch -M main
   git remote add origin https://github.com/<username>/<nama-repo-baru>.git
   git push -u origin main
   ```
2. Pastikan file `config.php` **tidak ikut ter-commit** (sudah diproteksi oleh `.gitignore`).

### Opsi B: Bersihkan Riwayat Git Lama Menggunakan Tooling
Jika harus tetap menggunakan repositori GitHub yang lama:
1. Gunakan tool resmi seperti [`git-filter-repo`](https://github.com/newren/git-filter-repo) atau [BFG Repo-Cleaner](https://rtyley.github.io/bfg-repo-cleaner/) untuk menghapus file SQL lama atau string sensitif dari seluruh histori:
   ```bash
   # Contoh dengan git-filter-repo:
   git filter-repo --invert-paths --path database/db_kupon_makan.sql
   ```
2. Lakukan force push ke remote:
   ```bash
   git push origin --force --all
   ```
3. **Wajib rotasi semua password pengguna**: Karena hash password lama pernah tercatat di riwayat commit, seluruh password lama harus dianggap telah terekspos.

---

## 🔒 Fitur Keamanan yang Diimplementasikan

Aplikasi ini telah melalui proses *security hardening* menyeluruh dengan standar pengamanan berikut:

### 1. Perlindungan SQL Injection
- 100% kueri yang menerima input pengguna atau session dieksekusi menggunakan **Prepared Statements (`mysqli::prepare` + `bind_param`)**.
- Tidak ada interpolasi string variabel langsung ke dalam klausa SQL.

### 2. Perlindungan Cross-Site Request Forgery (CSRF)
- Token CSRF dihasilkan secara kriptografis (`random_bytes(32)`) dan disimpan di session pengguna.
- Helper `csrf.php` memverifikasi token menggunakan fungsi timing-safe `hash_equals()`.
- Seluruh aksi yang mengubah data (`POST`) wajib menyertakan token CSRF yang valid.
- Semua aksi yang sebelumnya berbasis `GET` (`proses_hapus.php`, `proses_selesai.php`, `reset_pass`, `hapus_user`) telah diubah 100% menjadi form `POST`.

### 3. Session Hardening
- Terpusat melalui `session_config.php` dengan parameter cookie:
  - `HttpOnly`: Mencegah pembacaan cookie oleh script JavaScript (mitigasi XSS).
  - `SameSite=Lax`: Mencegah pengiriman cookie pada request lintas domain.
  - `Secure`: Aktif otomatis jika sistem berjalan di atas protokol HTTPS.
- Pemanggilan `session_regenerate_id(true)` saat login sukses untuk mencegah serangan *Session Fixation*.

### 4. Rate Limiting Anti Brute-Force
- Proteksi login di `login.php` menggunakan pencatatan percobaan gagal ke tabel `login_attempts`.
- Identifier unik dibuat dari hash kombinasi `SHA256(NIM + IP Address)`.
- Jika terdapat $\ge 5$ kali percobaan gagal dalam 15 menit, login diblokir sementara.

### 5. Kebijakan Password & Wajib Ganti Password
- Validasi password server-side: minimal 8 karakter, wajib kombinasi huruf dan angka.
- Kolom `must_change_password` (default `1` untuk akun baru dan hasil reset password).
- Modal interaktif SweetAlert2 yang memblokir navigasi dashboard dan mengarahkan pengguna untuk memperbarui kata sandi default mereka.

### 6. Proteksi Web Server (`.htaccess`)
- Menonaktifkan *Directory Browsing* (`Options -Indexes`).
- Memblokir akses HTTP langsung ke ekstensi sensitif (`.sql`, `.env`, `.md`, `.log`).
- Memblokir akses langsung ke `config.php`, folder `database/`, dan direktori `.git/`.

---

## 📢 Melaporkan Kerentanan Keamanan

Jika Anda menemukan celah keamanan (*vulnerability*) pada aplikasi ini, harap **JANGAN** membuat public issue di GitHub. Silakan hubungi pemilik repositori secara privat.
