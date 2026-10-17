<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/user/user.php
// Tabel penumpang (bagian R = Read dari CRUD).

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

$role = 'penumpang';

// Hitung total penumpang, lalu ambil 10 baris untuk halaman yang dibuka
$hitung = $koneksi->prepare("SELECT COUNT(*) AS t FROM users WHERE role = ?");
$hitung->bind_param('s', $role);
$hitung->execute();
$total = (int) $hitung->get_result()->fetch_assoc()['t'];
[$per_halaman, $offset] = paginasi($total);

// Ambil hanya user dengan role 'penumpang', yang terbaru di atas
$ambil = $koneksi->prepare(
    "SELECT id, nama, username, email, no_hp FROM users WHERE role = ? ORDER BY id DESC LIMIT ? OFFSET ?"
);
$ambil->bind_param('sii', $role, $per_halaman, $offset);
$ambil->execute();
$hasil = $ambil->get_result();

$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Data Penumpang</h1>
    <p class="page-subtitle">Kelola akun penumpang yang memesan tiket.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Data User</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Data Penumpang</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tambah_ok') : ?>
  <div class="alert alert-success">User baru berhasil ditambahkan.</div>
<?php elseif ($pesan === 'update_ok') : ?>
  <div class="alert alert-success">Data penumpang berhasil diubah.</div>
<?php elseif ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">User berhasil dihapus.</div>
<?php elseif ($pesan === 'dipakai') : ?>
  <div class="alert alert-danger">User tidak bisa dihapus karena sudah punya data pemesanan tiket.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel -->
  <div class="table-header-control">
    <div class="table-pagination-info">Total: <strong><?= $total ?></strong> penumpang</div>
    <div class="table-filter-group">
      <a href="<?= BASE_URL ?>/admin/index.php?page=user&aksi=tambah" class="btn-table-action">
        <i class="bi bi-plus-lg"></i> Tambah Penumpang
      </a>
    </div>
  </div>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>Username</th>
          <th>Email</th>
          <th>No. HP</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="6" class="text-center">Belum ada data penumpang.</td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($u = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($u['nama']) ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['no_hp'] ?? '-') ?></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="<?= BASE_URL ?>/admin/index.php?page=user&aksi=update&id=<?= (int) $u['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <form action="<?= BASE_URL ?>/admin/function/user.php?aksi=hapus" method="post" class="d-inline" onsubmit="return confirm('Yakin hapus penumpang ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button></form>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- tombol halaman (muncul hanya kalau data lebih dari 10) -->
  <?php render_pagination($total); ?>

</div>
<!-- kartu tabel -->