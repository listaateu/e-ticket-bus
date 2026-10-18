<?php
/**
 * login/index.php — Login PENUMPANG & SOPIR (satu form).
 * Sopir di database memakai role 'kondektur' (dibuat admin lewat admin/function/sopir.php).
 */
require_once __DIR__ . '/../includes/base_url.php';
require_once __DIR__ . '/../penumpang/database/koneksi.php';   // membuat $conn
require_once __DIR__ . '/../penumpang/function/auth.php';       // session + ambil_redirect_setelah_login()

// role di database => halaman tujuan setelah login
const TUJUAN_ROLE = [
    'penumpang' => BASE_URL . '/penumpang/index.php',
    'kondektur' => BASE_URL . '/sopir/index.php',
];

// Form login SELALU tampil (tidak lagi dilempar ke beranda walau browser ini sedang login).
// Sopir dan penumpang memakai satu session, jadi login berhasil = akun lama otomatis diganti,
// dan tab lain yang masih terbuka ikut refresh sendiri (lihat cek_sesi di sopir/ & penumpang/).

// Token anti-CSRF sederhana
if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(16));
}

/**
 * Cocokkan yang diketik dengan akun hasil query.
 * - Username: HARUS persis sama, huruf besar/kecil dihitung ("kaka" tidak sama dengan "KakA").
 *   (MySQL secara bawaan tidak membedakan besar/kecil, jadi dicek ulang di sini.)
 * - Email: tidak membedakan besar/kecil (memang begitu aturan email).
 */
function login_cocok(array $user, string $login): bool
{
    $username = (string) ($user['username'] ?? '');
    $email    = (string) ($user['email'] ?? '');
    return ($username !== '' && hash_equals($username, $login))
        || ($email !== '' && strcasecmp($email, $login) === 0);
}

$pesan = $_SESSION['pesan_login'] ?? '';   // mis. "Pendaftaran berhasil"
unset($_SESSION['pesan_login']);
$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hanya terima teks (bukan array seperti login[]=x yang bisa membuat error)
    $login    = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf_login'], $_POST['csrf']) || !permintaan_satu_situs()) {
        $error = 'Sesi form habis, silakan coba lagi.';
    } elseif ($login === '' || $password === '') {
        $error = 'Email/username dan password wajib diisi.';
    } else {
        // Hanya role penumpang & kondektur yang boleh lewat sini (admin punya login sendiri)
        $stmt = $conn->prepare(
            "SELECT id, nama, username, email, password, role FROM users
             WHERE (email = ? OR BINARY username = ?) AND role IN ('penumpang', 'kondektur')
             LIMIT 1"
        );
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && login_cocok($user, $login) && password_verify($password, $user['password'])) {
            // Kosongkan data akun sebelumnya (mis. sopir yang masih login) supaya hanya satu akun aktif.
            // Yang dipertahankan hanya token form & alamat tujuan setelah login.
            $simpan_csrf     = $_SESSION['csrf_login'];
            $simpan_redirect = $_SESSION['redirect_setelah_login'] ?? null;
            $_SESSION = [];
            $_SESSION['csrf_login'] = $simpan_csrf;
            if ($simpan_redirect !== null) {
                $_SESSION['redirect_setelah_login'] = $simpan_redirect;
            }

            session_regenerate_id(true);               // cegah session fixation
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'penumpang') {
                // balik ke halaman yang tadi dituju (kalau ada), kalau tidak ke beranda
                $tujuan = ambil_redirect_setelah_login(TUJUAN_ROLE['penumpang']);
            } else {
                unset($_SESSION['redirect_setelah_login']);
                $tujuan = TUJUAN_ROLE[$user['role']];
            }
            header('Location: ' . $tujuan);
            exit;
        }

        // Satu pesan umum, supaya orang tidak bisa menebak akun mana yang ada
        $error = 'Email/username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/penumpang/assets/img/favicon.svg?v=2">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>/penumpang/assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/penumpang/assets/img/apple-touch-icon.png?v=2">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/bootstrap-icons/bootstrap-icons.css">
  <title>Masuk - E-Ticket Bus</title>
  <style>
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:grid;place-items:center;padding:1.25rem;background:#FFE4EE;color:#3B2233;
         font-family:'Poppins','Segoe UI',system-ui,-apple-system,Roboto,Arial,sans-serif;overflow-x:hidden}

    .shell{position:relative;z-index:1;width:100%;max-width:980px;display:grid;grid-template-columns:1.05fr 1fr;
           background:#fff;border-radius:34px;overflow:hidden;box-shadow:0 28px 70px rgba(185,78,119,.20)}

    /* ---------- sisi kiri (ilustrasi) ---------- */
    .hero{position:relative;overflow:hidden;background:#F9B9D1;color:#5A1F3E;padding:2.4rem 2.3rem 0;display:flex;flex-direction:column}
    .hero .ring{position:absolute;border-radius:50%;background:#fff;opacity:.28}
    .hero .r1{width:230px;height:230px;right:-70px;top:-70px}
    .hero .r2{width:120px;height:120px;left:-40px;top:46%}
    .brand{position:relative;display:flex;align-items:center;gap:.7rem;font-weight:700;font-size:1.12rem}
    .brand-logo{width:44px;height:44px;border-radius:14px;background:#fff;color:#B94E77;display:grid;place-items:center;
                font-size:1.35rem;box-shadow:0 8px 18px rgba(185,78,119,.22)}
    .hero h2{position:relative;font-size:1.85rem;font-weight:700;line-height:1.22;margin:1.9rem 0 .65rem;color:#5A1F3E}
    .hero p{position:relative;font-size:.96rem;line-height:1.6;margin:0;color:#7A3A58;max-width:340px}
    .hero svg{position:relative;display:block;width:calc(100% + 4.6rem);max-width:none;height:auto;margin:auto -2.3rem 0}
    .bus{animation:bob 3.4s ease-in-out infinite}
    .cloud1{animation:drift 9s ease-in-out infinite}
    .cloud2{animation:drift 11s ease-in-out infinite reverse}
    .twinkle{animation:twinkle 2.6s ease-in-out infinite;transform-box:fill-box;transform-origin:center}
    @keyframes bob{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
    @keyframes drift{0%,100%{transform:translateX(0)}50%{transform:translateX(14px)}}
    @keyframes twinkle{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.55;transform:scale(.8)}}
    @media (prefers-reduced-motion:reduce){.bus,.cloud1,.cloud2,.twinkle{animation:none}}

    /* ---------- sisi kanan (form) ---------- */
    .panel{padding:3rem 3.1rem 2.6rem;display:flex;flex-direction:column;justify-content:center}
    .brand-top{display:none;align-items:center;gap:.6rem;margin-bottom:1.3rem;font-weight:700;color:#6B2F4D}
    .brand-top .brand-logo{width:38px;height:38px;font-size:1.15rem;background:#FFE9F2;box-shadow:none}
    .chip{display:inline-flex;align-items:center;gap:.4rem;align-self:flex-start;background:#FFE9F2;color:#B94E77;
          font-weight:600;font-size:.78rem;padding:.38rem .85rem;border-radius:999px;margin-bottom:1rem}
    .judul{font-size:1.8rem;font-weight:700;color:#6B2F4D;margin:0 0 .35rem}
    .sub{color:#8B6F80;font-size:.93rem;margin:0 0 1.6rem}

    .field{margin-bottom:1.05rem}
    .field label{display:block;font-size:.84rem;font-weight:600;color:#6B2F4D;margin-bottom:.4rem}
    .wrap{position:relative}
    .wrap>i.lead{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#D98BAA;font-size:1.05rem;pointer-events:none}
    .inp{width:100%;height:50px;border:1.5px solid #F7DCE8;background:#FFF6FA;border-radius:14px;padding:0 1rem 0 2.8rem;
         font:inherit;font-size:.95rem;color:#3B2233;outline:none;transition:border-color .15s,box-shadow .15s,background .15s}
    .inp::placeholder{color:#C9A9B8}
    .inp:focus{border-color:#E27AA0;background:#fff;box-shadow:0 0 0 4px rgba(246,169,199,.35)}
    .inp.has-eye{padding-right:3.1rem}
    .eye{position:absolute;right:.5rem;top:50%;transform:translateY(-50%);width:38px;height:38px;border:0;border-radius:10px;
         background:transparent;color:#B94E77;font-size:1.1rem;cursor:pointer;display:grid;place-items:center}
    .eye:hover{background:#FFE9F2}
    .row2{display:grid;grid-template-columns:1fr 1fr;gap:.9rem}

    .btn-pink{width:100%;height:52px;border:0;border-radius:14px;background:#B94E77;color:#fff;font:inherit;font-weight:600;
              font-size:1rem;margin-top:.5rem;cursor:pointer;box-shadow:0 12px 24px rgba(185,78,119,.30);
              transition:transform .15s,background .15s,box-shadow .15s}
    .btn-pink:hover{background:#A63F68;transform:translateY(-1px);box-shadow:0 16px 28px rgba(185,78,119,.34)}
    .btn-pink:active{transform:translateY(0)}
    .btn-pink:disabled{opacity:.75;cursor:wait;transform:none}
    .alt{text-align:center;color:#8B6F80;font-size:.9rem;margin:1.4rem 0 0}
    .alt+.alt{margin-top:.5rem}
    .alt a{color:#B94E77;font-weight:600;text-decoration:none}
    .alt a:hover{text-decoration:underline}
    .alert{border:0;border-radius:14px;font-size:.88rem;padding:.7rem 1rem;margin-bottom:1.1rem}
    .alert-success{background:#DDF5EC;color:#1E6B53}
    .alert-danger{background:#FFE3E8;color:#A12B4A}

    @media (max-width:860px){
      .shell{grid-template-columns:1fr;max-width:480px;border-radius:28px}
      .hero{display:none}
      .brand-top{display:flex}
      .panel{padding:2rem 1.5rem 1.8rem}
      .row2{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <main class="shell">
    <aside class="hero">
      <span class="ring r1"></span><span class="ring r2"></span>
      <div class="brand"><span class="brand-logo"><i class="bi bi-bus-front-fill"></i></span>E-Ticket Bus</div>
      <h2>Mau ke mana hari ini?</h2>
      <p>Pesan tiket bus antar kota jadi lebih mudah, cepat, dan nyaman. Masuk dulu yuk!</p>
      <svg viewBox="0 0 420 300" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g class="cloud1"><path d="M40 70h70a18 18 0 0 0-6-34 26 26 0 0 0-48-4 20 20 0 0 0-16 38z" fill="#fff" opacity=".85"/></g>
        <g class="cloud2"><path d="M300 104h76a15 15 0 0 0-4-29 22 22 0 0 0-42-3 17 17 0 0 0-30 32z" fill="#fff" opacity=".7"/></g>
        <path class="twinkle" d="M360 40l4 11 11 4-11 4-4 11-4-11-11-4 11-4z" fill="#FFF3A8"/>
        <path class="twinkle" d="M70 140l3 8 8 3-8 3-3 8-3-8-8-3 8-3z" fill="#fff"/>
        <path class="twinkle" d="M388 170c-8-6-8-14-3-14 2 0 3 1 3 2 0-1 1-2 3-2 5 0 5 8-3 14z" fill="#fff"/>
        <ellipse cx="210" cy="284" rx="132" ry="11" fill="#B94E77" opacity=".18"/>
        <rect x="0" y="278" width="420" height="22" fill="#F4A3C2"/>
        <g class="bus">
          <rect x="172" y="38" width="76" height="28" rx="14" fill="#fff"/>
          <rect x="106" y="58" width="208" height="206" rx="48" fill="#fff"/>
          <rect x="132" y="86" width="156" height="76" rx="26" fill="#FFD3E4"/>
          <path d="M152 108h34" stroke="#fff" stroke-width="7" stroke-linecap="round" opacity=".9"/>
          <circle cx="152" cy="204" r="16" fill="#F47FAA"/>
          <circle cx="268" cy="204" r="16" fill="#F47FAA"/>
          <circle cx="157" cy="199" r="4" fill="#fff" opacity=".9"/>
          <circle cx="273" cy="199" r="4" fill="#fff" opacity=".9"/>
          <path d="M188 208q22 24 44 0" fill="none" stroke="#F47FAA" stroke-width="7" stroke-linecap="round"/>
          <ellipse cx="124" cy="222" rx="10" ry="7" fill="#FFB3CE" opacity=".8"/>
          <ellipse cx="296" cy="222" rx="10" ry="7" fill="#FFB3CE" opacity=".8"/>
          <rect x="126" y="246" width="46" height="36" rx="17" fill="#6B2F4D"/>
          <rect x="248" y="246" width="46" height="36" rx="17" fill="#6B2F4D"/>
          <circle cx="149" cy="264" r="7" fill="#FFD3E4"/>
          <circle cx="271" cy="264" r="7" fill="#FFD3E4"/>
        </g>
      </svg>
    </aside>

    <section class="panel">
      <div class="brand-top"><span class="brand-logo"><i class="bi bi-bus-front-fill"></i></span>E-Ticket Bus</div>
      <span class="chip"><i class="bi bi-stars"></i> Penumpang &amp; Sopir</span>
      <h1 class="judul">Selamat datang!</h1>
      <p class="sub">Masuk ke akunmu untuk melanjutkan.</p>

      <?php if ($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_login'] ?>">

        <div class="field">
          <label for="login">Email atau username</label>
          <div class="wrap">
            <i class="bi bi-person lead"></i>
            <input class="inp" id="login" name="login" placeholder="Masukkan email atau username"
                   value="<?= htmlspecialchars($login) ?>" required autofocus>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="wrap">
            <i class="bi bi-lock lead"></i>
            <input class="inp has-eye" type="password" id="password" name="password" placeholder="Masukkan password" required>
            <button class="eye" type="button" data-target="password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
          </div>
        </div>

        <button class="btn-pink">Masuk</button>
      </form>

      <p class="alt">Belum punya akun? <a href="register.php">Daftar sebagai penumpang</a></p>
      <p class="alt"><a href="<?= BASE_URL ?>/penumpang/index.php"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a></p>
    </section>
  </main>

  <script>
    // tombol lihat/sembunyikan password
    document.querySelectorAll('.eye').forEach(function (tombol) {
      tombol.addEventListener('click', function () {
        var isian = document.getElementById(tombol.getAttribute('data-target'));
        var tampil = isian.type === 'password';
        isian.type = tampil ? 'text' : 'password';
        tombol.querySelector('i').className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye';
        tombol.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
      });
    });
  </script>
</body>
</html>