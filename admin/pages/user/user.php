<?php
// admin/pages/user/user.php
// Tabel daftar user (bagian R = Read dari CRUD).

include_once __DIR__ . '/../../database/koneksi.php';

$hasil = $koneksi->query("SELECT id, nama, email, role FROM users ORDER BY id DESC");
$total = $hasil->num_rows;

$pesan = $_GET['pesan'] ?? '';

// Warna badge tiap role (pakai kelas yang sudah ada di main.css)
$warna_role = ['admin' => 'failed', 'kondektur' => 'pending', 'penumpang' => 'success'];
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Data User</h1>
    <p class="page-subtitle">Kelola akun admin, kondektur, dan penumpang.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Master Data</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Data User</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tambah_ok') : ?>
  <div class="alert alert-success">User baru berhasil ditambahkan.</div>
<?php elseif ($pesan === 'update_ok') : ?>
  <div class="alert alert-success">Data user berhasil diubah.</div>
<?php elseif ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">User berhasil dihapus.</div>
<?php elseif ($pesan === 'user_dipakai') : ?>
  <div class="alert alert-danger">User tidak bisa dihapus karena sudah punya data pemesanan.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel -->
  <div class="table-header-control">
    <div class="table-pagination-info">Total: <strong><?= $total ?></strong> user</div>
    <div class="table-filter-group">
      <a href="/e-ticket-bus/admin/index.php?page=user&aksi=tambah" class="btn-table-action">
        <i class="bi bi-plus-lg"></i> Tambah User
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
          <th>Role</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="5" class="text-center">Belum ada data user.</td>
          </tr>
        <?php endif; ?>

        <?php $no = 1; while ($u = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($u['nama']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge-table <?= $warna_role[$u['role']] ?? 'pending' ?>"><?= htmlspecialchars(ucfirst($u['role'])) ?></span></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="/e-ticket-bus/admin/index.php?page=user&aksi=update&id=<?= (int) $u['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <a href="/e-ticket-bus/admin/function/user.php?aksi=hapus&id=<?= (int) $u['id'] ?>" class="table-btn-action delete" title="Hapus" onclick="return confirm('Yakin hapus user ini?')"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- kartu tabel -->