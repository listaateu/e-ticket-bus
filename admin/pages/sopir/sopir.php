<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/sopir/sopir.php
// Tabel sopir (bagian R = Read dari CRUD).

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';
include_once __DIR__ . '/../../components/pencarian.php';

$role = 'kondektur';

// Pencarian dari kotak cari di topbar (?q=): nama atau email
$cari  = param_cari();
$tipe  = 's';
$param = [$role];
$where = ' WHERE role = ?' . syarat_cari(['nama', 'email'], $cari, $tipe, $param);

// Hitung total sopir (sesuai pencarian), lalu ambil 10 baris untuk halaman yang dibuka
$hitung = $koneksi->prepare("SELECT COUNT(*) AS t FROM users $where");
$hitung->bind_param($tipe, ...$param);
$hitung->execute();
$total = (int) $hitung->get_result()->fetch_assoc()['t'];
[$per_halaman, $offset] = paginasi($total);

// Ambil hanya user dengan role 'kondektur', yang terbaru di atas
$ambil = $koneksi->prepare(
    "SELECT id, nama, email FROM users $where ORDER BY id DESC LIMIT ? OFFSET ?"
);
$tipe .= 'ii'; $param[] = (int) $per_halaman; $param[] = (int) $offset;
$ambil->bind_param($tipe, ...$param);
$ambil->execute();
$hasil = $ambil->get_result();

$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Data Sopir</h1>
    <p class="page-subtitle">Kelola akun sopir/kondektur yang memeriksa dan memvalidasi tiket.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Data User</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Data Sopir</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tambah_ok') : ?>
  <div class="alert alert-success">Sopir baru berhasil ditambahkan.</div>
<?php elseif ($pesan === 'update_ok') : ?>
  <div class="alert alert-success">Data sopir berhasil diubah.</div>
<?php elseif ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">Sopir berhasil dihapus.</div>
<?php elseif ($pesan === 'dipakai') : ?>
  <div class="alert alert-danger">Sopir tidak bisa dihapus karena akunnya masih dipakai data lain.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel -->
  <div class="table-header-control">
    <div class="table-pagination-info">Total: <strong><?= $total ?></strong> sopir</div>
    <div class="table-filter-group">
      <a href="<?= BASE_URL ?>/admin/index.php?page=sopir&aksi=tambah" class="btn-table-action">
        <i class="bi bi-plus-lg"></i> Tambah Sopir
      </a>
    </div>
  </div>

  <?php render_info_cari($cari, $total, BASE_URL . '/admin/index.php?page=sopir'); ?>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>Email</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="4" class="text-center"><?= $cari !== '' ? 'Tidak ada sopir yang cocok dengan pencarian.' : 'Belum ada data sopir.' ?></td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($u = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($u['nama']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="<?= BASE_URL ?>/admin/index.php?page=sopir&aksi=update&id=<?= (int) $u['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <form action="<?= BASE_URL ?>/admin/function/sopir.php?aksi=hapus" method="post" class="d-inline" onsubmit="return confirm('Yakin hapus sopir ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button></form>
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