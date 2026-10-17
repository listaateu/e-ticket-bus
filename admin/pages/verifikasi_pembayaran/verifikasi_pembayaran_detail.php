<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/verifikasi_pembayaran/verifikasi_pembayaran_detail.php
// Detail satu pembayaran: info pemesanan, bukti transfer, tombol setujui / tolak.
// Setujui mengirim POST ke function/verifikasi_pembayaran.php.
// Tolak membuka form alasan dulu, baru mengirim POST ke file yang sama.
// Total Transfer = harga tiket + kode unik (angka yang harus muncul di mutasi rekening).

include_once __DIR__ . '/../../database/koneksi.php';
require_once __DIR__ . '/../../../includes/alasan_tolak.php';   // daftar alasan penolakan

// ===== SESUAIKAN dengan databasemu =====
$STATUS = [
    'pending' => ['label' => 'Menunggu',  'badge' => 'pending'],
    'lunas'   => ['label' => 'Lunas',     'badge' => 'success'],
    'ditolak' => ['label' => 'Ditolak',   'badge' => 'failed'],
];
$STATUS_MENUNGGU = 'pending';
// Folder tempat file bukti transfer disimpan (sama dengan folder upload di sisi penumpang)
$FOLDER_BUKTI = BASE_URL . '/uploads/bukti/';
// =======================================

$id = param_id('id');

$ambil = $koneksi->prepare(
    "SELECT p.id, p.metode_pembayaran, p.bukti_transfer, p.status, p.alasan_penolakan,
            b.id AS booking_id, b.kode_booking, b.nomor_kursi, b.jumlah_dewasa, b.jumlah_bayi, b.kode_unik,
            u.nama, u.email,
            s.kota_asal, s.kota_tujuan, s.jam_berangkat, (s.jam_berangkat < NOW()) AS sudah_berangkat, s.harga,
            bs.plat_nomor, bs.kelas
     FROM payments p
     JOIN bookings  b  ON b.id  = p.booking_id
     JOIN users     u  ON u.id  = b.user_id
     JOIN schedules s  ON s.id  = b.schedule_id
     JOIN buses     bs ON bs.id = s.bus_id
     WHERE p.id = ?"
);
$ambil->bind_param('i', $id);
$ambil->execute();
$p = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$p) {
    echo '<script>window.location.href = "' . BASE_URL . '/admin/index.php?page=verifikasi_pembayaran";</script>';
    return;
}

$info    = $STATUS[$p['status']] ?? ['label' => $p['status'], 'badge' => 'pending'];
$bukti   = trim((string) $p['bukti_transfer']);
$ext     = strtolower(pathinfo($bukti, PATHINFO_EXTENSION));
$gambar  = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
$url_bukti = $FOLDER_BUKTI . rawurlencode($bukti);
$alasan_tersimpan = trim((string) ($p['alasan_penolakan'] ?? ''));
$pesan   = $_GET['pesan'] ?? '';

// Rincian nominal yang harus ditransfer penumpang
$harga_tiket    = (int) round((float) $p['harga']) * max(1, (int) $p['jumlah_dewasa']);   // harga x jumlah dewasa (bayi gratis)
$kode_unik      = (int) ($p['kode_unik'] ?? 0);   // 0 untuk pemesanan lama
$total_transfer = $harga_tiket + $kode_unik;
$berangkat      = !empty($p['sudah_berangkat']);   // true = bus sudah berangkat

// Daftar penumpang (dewasa dulu, lalu bayi)
$ambil_pax = $koneksi->prepare("SELECT nama, tipe, nomor_kursi FROM booking_penumpang WHERE booking_id = ? ORDER BY (tipe = 'bayi') ASC, id ASC");
$ambil_pax->bind_param('i', $p['booking_id']);
$ambil_pax->execute();
$daftar_pax = $ambil_pax->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Detail Pembayaran</h1>
    <p class="page-subtitle">Cocokkan bukti transfer dan nominalnya (termasuk kode unik) dengan mutasi rekening.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php?page=verifikasi_pembayaran" class="text-decoration-none text-muted-green">Verifikasi Pembayaran</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Detail</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tanpa_bukti') : ?>
  <div class="alert alert-danger">Pembayaran tidak bisa disetujui karena belum ada bukti transfer.</div>
<?php elseif ($pesan === 'sudah_berangkat') : ?>
  <div class="alert alert-danger">Pembayaran tidak bisa disetujui karena bus sudah berangkat. Kembalikan dana ke penumpang, lalu tolak pembayaran ini.</div>
<?php elseif ($pesan === 'alasan_kosong') : ?>
  <div class="alert alert-danger">Pilih alasan penolakan dulu. Kalau memilih "Alasan lain", catatan wajib diisi.</div>
<?php endif; ?>

<div class="row g-3">

  <!-- info pemesanan -->
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Pemesanan</h5>
      <table class="table table-borderless mb-0">
        <tr><td class="text-muted" style="width:40%">Kode Booking</td><td><strong><?= htmlspecialchars($p['kode_booking']) ?></strong></td></tr>
        <tr><td class="text-muted">Penumpang</td><td><?= htmlspecialchars($p['nama']) ?><br><small class="text-muted"><?= htmlspecialchars($p['email']) ?></small></td></tr>
        <tr><td class="text-muted">Rute</td><td><?= htmlspecialchars($p['kota_asal']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= htmlspecialchars($p['kota_tujuan']) ?></td></tr>
        <tr><td class="text-muted">Berangkat</td><td><?= date('d M Y, H:i', strtotime($p['jam_berangkat'])) ?></td></tr>
        <tr><td class="text-muted">Bus</td><td><?= htmlspecialchars($p['plat_nomor']) ?> (<?= htmlspecialchars($p['kelas']) ?>)</td></tr>
        <tr><td class="text-muted">Jumlah Penumpang</td><td><?= (int) $p['jumlah_dewasa'] ?> dewasa<?= (int) $p['jumlah_bayi'] > 0 ? ', ' . (int) $p['jumlah_bayi'] . ' bayi (gratis)' : '' ?></td></tr>
        <tr><td class="text-muted">Nomor Kursi</td><td><?= htmlspecialchars($p['nomor_kursi']) ?></td></tr>
        <?php foreach ($daftar_pax as $px) : ?>
          <tr>
            <td class="text-muted ps-4"><small><?= $px['tipe'] === 'bayi' ? 'Bayi' : 'Dewasa' ?></small></td>
            <td><?= htmlspecialchars($px['nama']) ?><?= $px['tipe'] === 'dewasa' ? ' <small class="text-muted">· Kursi ' . htmlspecialchars((string) $px['nomor_kursi']) . '</small>' : ' <small class="text-muted">· tanpa kursi</small>' ?></td>
          </tr>
        <?php endforeach; ?>
        <tr><td class="text-muted">Harga Tiket</td><td>Rp <?= number_format($harga_tiket, 0, ',', '.') ?></td></tr>
        <?php if ($kode_unik > 0) : ?>
          <tr><td class="text-muted">Kode Unik</td><td>Rp <?= number_format($kode_unik, 0, ',', '.') ?></td></tr>
        <?php endif; ?>
        <tr><td class="text-muted">Total Transfer</td><td><strong>Rp <?= number_format($total_transfer, 0, ',', '.') ?></strong></td></tr>
        <tr><td class="text-muted">Metode</td><td><?= htmlspecialchars($p['metode_pembayaran']) ?></td></tr>
        <tr><td class="text-muted">Status</td><td><span class="badge-table <?= $info['badge'] ?>"><?= htmlspecialchars($info['label']) ?></span></td></tr>
        <?php if ($p['status'] === 'ditolak' && $alasan_tersimpan !== '') : ?>
          <tr><td class="text-muted">Alasan Ditolak</td><td><?= htmlspecialchars($alasan_tersimpan) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <!-- bukti transfer + tombol -->
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Bukti Transfer</h5>

      <?php if ($bukti === '') : ?>
        <div class="alert alert-warning mb-3">Penumpang belum mengunggah bukti transfer.</div>
      <?php elseif ($gambar) : ?>
        <a href="<?= $url_bukti ?>" target="_blank" rel="noopener">
          <img src="<?= $url_bukti ?>" alt="Bukti transfer" class="img-fluid rounded border mb-3" style="max-height:420px">
        </a>
      <?php else : ?>
        <p class="mb-3"><a href="<?= $url_bukti ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i> <?= htmlspecialchars($bukti) ?></a></p>
      <?php endif; ?>

      <?php if ($p['status'] === $STATUS_MENUNGGU) : ?>
        <?php if ($berangkat) : ?>
          <div class="alert alert-danger mb-3">
            <strong>Bus ini sudah berangkat.</strong> Jangan disetujui.
            Kembalikan dana ke penumpang secara manual (Rp <?= number_format($total_transfer, 0, ',', '.') ?>),
            <u>lalu</u> klik Tolak. Pesanan ini akan dihapus dari sistem setelah ditolak.
          </div>
        <?php endif; ?>
        <div class="d-flex gap-2">
          <!-- Setujui -->
          <form action="<?= BASE_URL ?>/admin/function/verifikasi_pembayaran.php?aksi=setujui" method="post"
                onsubmit="return confirm('Setujui pembayaran ini?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" class="btn-custom btn-custom-primary"<?= ($bukti === '' || $berangkat) ? ' disabled' : '' ?>>
              <i class="bi bi-check-lg"></i> Setujui
            </button>
          </form>

          <!-- Tolak: tombol ini hanya membuka form alasan di bawah -->
          <button type="button" class="btn-custom btn-custom-light"
                  data-bs-toggle="collapse" data-bs-target="#form-tolak" aria-expanded="false" aria-controls="form-tolak">
            <i class="bi bi-x-lg"></i> Tolak
          </button>

          <a href="<?= BASE_URL ?>/admin/index.php?page=verifikasi_pembayaran" class="btn-custom btn-custom-light">Kembali</a>
        </div>

        <!-- Form alasan penolakan (tersembunyi sampai tombol Tolak diklik) -->
        <div class="collapse mt-3<?= $berangkat ? ' show' : '' ?>" id="form-tolak">
          <form action="<?= BASE_URL ?>/admin/function/verifikasi_pembayaran.php?aksi=tolak" method="post"
                onsubmit="return confirm('<?= $berangkat ? 'Pastikan dana SUDAH dikembalikan ke penumpang. Pesanan ini akan dihapus dari sistem. Lanjutkan?' : 'Tolak pembayaran ini dan kirim alasannya ke penumpang?' ?>')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">

            <div class="mb-3">
              <label class="form-label" for="alasan">Alasan penolakan</label>
              <select class="form-select" id="alasan" name="alasan" required>
                <option value="">- Pilih alasan -</option>
                <?php foreach (ALASAN_TOLAK as $kode => $teks) : ?>
                  <option value="<?= htmlspecialchars($kode) ?>"<?= ($berangkat && $kode === 'berangkat') ? ' selected' : '' ?>><?= htmlspecialchars($teks) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="catatan">
                Catatan untuk penumpang <span class="text-muted">(wajib jika memilih "Alasan lain")</span>
              </label>
              <textarea class="form-control" id="catatan" name="catatan" rows="2"
                        maxlength="<?= CATATAN_TOLAK_MAKS ?>"
                        placeholder="Contoh: Nominal yang masuk Rp140.000, kurang Rp10.000"></textarea>
            </div>

            <button type="submit" class="btn-custom btn-custom-light">
              <i class="bi bi-send"></i> Kirim penolakan
            </button>
          </form>
        </div>

        <script>
          // Catatan jadi wajib diisi kalau admin memilih "Alasan lain"
          document.getElementById('alasan').addEventListener('change', function () {
            document.getElementById('catatan').required = (this.value === 'lainnya');
          });
        </script>
      <?php else : ?>
        <p class="text-muted mb-3">Pembayaran ini sudah diproses.</p>
        <a href="<?= BASE_URL ?>/admin/index.php?page=verifikasi_pembayaran" class="btn-custom btn-custom-light">Kembali</a>
      <?php endif; ?>
    </div>
  </div>

</div>