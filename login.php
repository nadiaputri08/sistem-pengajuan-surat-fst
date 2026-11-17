<?php
require __DIR__ . '/includes/bootstrap.php';

// Jika pengguna sudah login, langsung arahkan ke dashboard sesuai perannya.
if (user()) {
    redirect(user()['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/dashboard.php');
}

$role = $_POST['role'] ?? ($_GET['role'] ?? 'admin');
if (!in_array($role, ['admin', 'mahasiswa'], true)) $role = 'admin';
$error = '';
$identitas = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $now = time();
    $_SESSION['login_attempts'] = array_values(array_filter(
        $_SESSION['login_attempts'] ?? [],
        fn($time) => $time > $now - 300
    ));

    if (count($_SESSION['login_attempts']) >= 5) {
        $error = 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam 5 menit.';
    } else {
        $identitas = trim($_POST['identitas'] ?? '');
        $password = $_POST['kata_sandi'] ?? '';
        $kolom = $role === 'admin' ? 'username' : 'nim';

        // Admin masuk dengan username/email, mahasiswa dengan NIM/email.
        $stmt = q($pdo,
            "SELECT * FROM pengguna WHERE role = ? AND ($kolom = ? OR email = ?) LIMIT 1",
            [$role, $identitas, $identitas]
        );
        $akun = $stmt->fetch();

        if ($akun && password_verify($password, $akun['kata_sandi'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $akun['id_pengguna'],
                'nama' => $akun['nama'],
                'role' => $akun['role']
            ];
            unset($_SESSION['login_attempts']);
            redirect($akun['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/dashboard.php');
        }

        $_SESSION['login_attempts'][] = $now;
        $error = ($role === 'admin' ? 'Username/email' : 'NIM/email') . ' atau kata sandi salah.';
    }
}

$isAdmin = $role === 'admin';
$judul = $isAdmin ? 'Masuk sebagai admin' : 'Masuk sebagai mahasiswa';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> - <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        html,body{margin:0;min-height:100%;font-family:Inter,Arial,sans-serif}
        body{min-height:100vh;background:#fff;color:#111}
        .login-screen{min-height:100vh;display:grid;grid-template-columns:53.35% 46.65%}
        .intro-panel{background:#4b5320;color:#fff;display:flex;align-items:flex-start;justify-content:center;padding:19.4vh 7.2% 50px}
        .intro-content{width:100%;max-width:460px}
        .brand{display:flex;align-items:center;gap:9px;color:#fff;text-decoration:none;font-size:14px;font-weight:600;margin:0 0 6px 5px}
        .brand-icon{width:33px;height:33px;border-radius:10px;background:#627238;display:grid;place-items:center}
        .brand-icon svg{width:23px;height:23px;fill:#fff}
        .role-label{display:inline-block;min-width:184px;text-align:center;background:#b5a900;color:#fff;border-radius:20px;padding:4px 17px;font-size:11px;margin-bottom:8px}
        .intro-heading{font-size:14px;line-height:1.35;font-weight:500;margin:0 0 9px 10px;max-width:280px}
        .feature-list{display:grid;gap:21px}
        .feature{min-height:50px;background:rgba(117,145,76,.36);border-radius:17px;display:flex;align-items:center;gap:12px;padding:13px 19px;color:#f4f5ed;font-size:11px;line-height:1.35}
        .feature svg{width:13px;height:13px;flex:0 0 13px;stroke:#e8eddb;fill:none;stroke-width:1.5}
        .form-panel{display:flex;align-items:center;justify-content:center;padding:50px 30px;background:#fff}
        .form-content{width:100%;max-width:274px;margin-top:2px}
        .form-content h1{font-size:23px;line-height:1.25;font-weight:700;margin:0 0 4px;letter-spacing:-.55px}
        .subtitle{font-size:11px;color:#929292;margin:0 0 38px}
        .alert{font-size:11px;line-height:1.45;padding:9px 11px;border-radius:8px;margin:-20px 0 18px}
        .alert.err{background:#fff0ef;color:#a32720;border:1px solid #f2d0cd}
        .alert.ok{background:#eef8ec;color:#276b20}
        .field{margin-bottom:14px}
        .field label{display:block;color:#777;font-size:9px;margin:0 0 5px 14px}
        .field input{width:100%;height:28px;border:1px solid #dedede;border-radius:20px;padding:0 14px;font-family:inherit;font-size:12px;outline:none;background:#fff;transition:border-color .15s,box-shadow .15s}
        .field input:focus{border-color:#64743b;box-shadow:0 0 0 2px rgba(75,83,32,.09)}
        .submit-btn{width:100%;height:28px;border:0;border-radius:20px;background:#596b32;color:#fff;font-family:inherit;font-size:9px;font-weight:700;cursor:pointer;margin-top:21px;transition:background .15s}
        .submit-btn:hover{background:#414a1d}
        .switch-link{font-size:9px;color:#888;text-align:left;margin:15px 0 0 14px}
        .switch-link a{color:#4b5320;text-decoration:none;font-weight:600}
        .switch-link a:hover{text-decoration:underline}
        .prototype-note{font-size:9px;line-height:1.35;color:#888;margin:5px 0 0 14px;max-width:250px}
        .register-note{font-size:9px;color:#888;margin:9px 0 0 14px}
        .register-note a{color:#4b5320;text-decoration:none;font-weight:600}
        @media(max-width:700px){
            .login-screen{grid-template-columns:1fr}
            .intro-panel{min-height:260px;padding:35px 8%;}
            .intro-content{max-width:460px}
            .feature-list{gap:9px}
            .feature{min-height:42px}
            .form-panel{padding:48px 25px 60px}
            .form-content{max-width:360px}
            .subtitle{margin-bottom:28px}
        }
        @media(max-width:380px){.intro-panel{padding:28px 6%}.feature{padding:11px 13px}}
    </style>
</head>
<body>
<div class="login-screen">
    <aside class="intro-panel">
        <div class="intro-content">
            <a class="brand" href="<?= url('index.php') ?>">
                <span class="brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M21.7 3.3a1 1 0 0 0-1.05-.18L2.8 10.2a1 1 0 0 0 .08 1.9l7.1 2.1 2.1 7.1a1 1 0 0 0 1.9.08l7.1-17.85a1 1 0 0 0-.38-1.23ZM11 12.9l-5.1-1.5 11.7-4.7-6.6 6.2Zm2.6 5.2-1.5-5.1 6.2-6.6-4.7 11.7Z"/></svg>
                </span>
                <span>Surat Mahasiswa</span>
            </a>
            <div class="role-label"><?= $isAdmin ? 'Khusus staf akademik' : 'Khusus mahasiswa' ?></div>
            <p class="intro-heading"><?= $isAdmin ? 'Kelola pengajuan surat mahasiswa dengan rapi.' : 'Ajukan dan pantau surat mahasiswa dengan mudah.' ?></p>
            <div class="feature-list">
                <div class="feature"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg><span><?= $isAdmin ? 'Periksa kelengkapan dokumen mahasiswa.' : 'Ajukan surat dan unggah dokumen persyaratan.' ?></span></div>
                <div class="feature"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg><span><?= $isAdmin ? 'Proses, setujui, atau tolak pengajuan dengan catatan.' : 'Pantau proses pengajuan dan catatan dari admin.' ?></span></div>
                <div class="feature"><svg viewBox="0 0 24 24"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4 18-7Z"/></svg><span><?= $isAdmin ? 'Terbitkan surat dan pantau laporan.' : 'Unduh surat yang telah selesai diterbitkan.' ?></span></div>
            </div>
        </div>
    </aside>

    <main class="form-panel">
        <div class="form-content">
            <h1><?= e($judul) ?></h1>
            <p class="subtitle"><?= $isAdmin ? 'Gunakan email staf akademik.' : 'Gunakan NIM atau email mahasiswa.' ?></p>

            <?php if (!empty($_SESSION['flash'])): [$flashType, $flashMessage] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
                <div class="alert <?= e($flashType) ?>"><?= e($flashMessage) ?></div>
            <?php endif; ?>
            <?php if ($error): ?><div class="alert err" role="alert"><?= e($error) ?></div><?php endif; ?>

            <form method="post" action="<?= url('login.php') ?>" autocomplete="on">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="<?= e($role) ?>">
                <div class="field">
                    <label for="identitas"><?= $isAdmin ? 'Email staf' : 'NIM atau email mahasiswa' ?></label>
                    <input id="identitas" type="text" name="identitas" value="<?= e($identitas) ?>" required autofocus autocomplete="username">
                </div>
                <div class="field">
                    <label for="kata_sandi">Kata sandi</label>
                    <input id="kata_sandi" type="password" name="kata_sandi" required autocomplete="current-password">
                </div>
                <button class="submit-btn" type="submit">Masuk ke portal <?= $isAdmin ? 'admin' : 'mahasiswa' ?></button>
            </form>

            <p class="switch-link">
                <?= $isAdmin ? 'Mahasiswa?' : 'Staf akademik?' ?>
                <a href="<?= url('login.php?role=' . ($isAdmin ? 'mahasiswa' : 'admin')) ?>">Masuk sebagai <?= $isAdmin ? 'mahasiswa' : 'admin' ?></a>
            </p>
            <?php if ($isAdmin): ?>
                <p class="prototype-note">Prototipe: isi data akun admin yang sudah terdaftar di database.</p>
            <?php else: ?>
                <p class="register-note">Belum memiliki akun? <a href="<?= url('register.php') ?>">Daftar sebagai mahasiswa</a></p>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
