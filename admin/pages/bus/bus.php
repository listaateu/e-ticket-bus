<?php
// admin/pages/bus/bus.php
// Tabel daftar bus (bagian R = Read dari CRUD).

include_once __DIR__ . '/../../database/koneksi.php';

// Ambil semua bus dari tabel buses, yang terbaru di atas
$hasil = $koneksi->query("SELECT * FROM buses ORDER BY id DESC");
$total = $hasil->num_rows;

// Pesan hasil proses (dikirim lewat alamat: ...&pesan=tambah_ok)
$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Data Bus</h1>
    <p class="page-subtitle">Kelola armada bus: plat nomor, kapasitas kursi, dan kelas.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Master Data</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Data Bus</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tambah_ok') : ?>
  <div class="alert alert-success">Bus baru berhasil ditambahkan.</div>
<?php elseif ($pesan === 'update_ok') : ?>
  <div class="alert alert-success">Data bus berhasil diubah.</div>
<?php elseif ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">Bus berhasil dihapus.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel -->
  <div class="table-header-control">
    <div class="table-pagination-info">Total: <strong><?= $total ?></strong> bus</div>
    <div class="table-filter-group">
      <a href="/e-ticket-bus/admin/index.php?page=bus&aksi=tambah" class="btn-table-action">
        <i class="bi bi-plus-lg"></i> Tambah Bus
      </a>
    </div>
  </div>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Plat Nomor</th>
          <th>Kapasitas Kursi</th>
          <th>Kelas</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="5" class="text-center">Belum ada data bus.</td>
          </tr>
        <?php endif; ?>

        <?php $no = 1; while ($bus = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id"><?= htmlspecialchars($bus['plat_nomor']) ?></td>
            <td><?= (int) $bus['kapasitas_kursi'] ?> kursi</td>
            <td><span class="badge-table success"><?= htmlspecialchars($bus['kelas']) ?></span></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="/e-ticket-bus/admin/index.php?page=bus&aksi=update&id=<?= (int) $bus['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <a href="/e-ticket-bus/admin/function/bus.php?aksi=hapus&id=<?= (int) $bus['id'] ?>" class="table-btn-action delete" title="Hapus" onclick="return confirm('Yakin hapus bus ini?')"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<!-- kartu tabel -->