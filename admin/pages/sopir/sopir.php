<?php
// admin/pages/sopir/sopir.php
// Tabel sopir (bagian R = Read dari CRUD).

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

$role = 'kondektur';

// Hitung total sopir, lalu ambil 10 baris untuk halaman yang dibuka
$hitung = $koneksi->prepare("SELECT COUNT(*) AS t FROM users WHERE role = ?");
$hitung->bind_param('s', $role);
$hitung->execute();
$total = (int) $hitung->get_result()->fetch_assoc()['t'];
[$per_halaman, $offset] = paginasi($total);

// Ambil hanya user dengan role 'kondektur', yang terbaru di atas
$ambil = $koneksi->prepare(
    "SELECT id, nama, email FROM users WHERE role = ? ORDER BY id DESC LIMIT $per_halaman OFFSET $offset"
);
$ambil->bind_param('s', $role);
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
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Master Data</li>
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
      <a href="/e-ticket-bus/admin/index.php?page=sopir&aksi=tambah" class="btn-table-action">
        <i class="bi bi-plus-lg"></i> Tambah Sopir
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
          <th>Email</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="4" class="text-center">Belum ada data sopir.</td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($u = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($u['nama']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="/e-ticket-bus/admin/index.php?page=sopir&aksi=update&id=<?= (int) $u['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <a href="/e-ticket-bus/admin/function/sopir.php?aksi=hapus&id=<?= (int) $u['id'] ?>" class="table-btn-action delete" title="Hapus" onclick="return confirm('Yakin hapus sopir ini?')"><i class="bi bi-trash"></i></a>
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