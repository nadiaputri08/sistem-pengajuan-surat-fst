<?php
// ====== SESUAIKAN BAGIAN INI ======
define('DB_HOST', 'sql301.infinityfree.com');
define('DB_NAME', 'if0_43419650_surat_fst');
define('DB_USER', 'if0_43419650');
define('DB_PASS', 'sandi-akun-hosting-anda');
define('BASE_URL', '');   // tanpa slash di akhir. Isi '' jika aplikasi ada di root domain
define('APP_NAME', 'Layanan Surat FST');
// ==================================
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_UPLOAD', 2 * 1024 * 1024);      // 2 MB
const PRODI = ['Sistem Informasi', 'Matematika', 'Fisika', 'Biologi', 'Kimia', 'Teknik Informatika'];
date_default_timezone_set('Asia/Jakarta');
