<?php
/**
 * ============================================================
 * FILE         : includes/batas_bayar.php
 * FUNGSI       : Batas waktu pembayaran. Pesanan yang belum dibayar dibatalkan otomatis
 *                setelah BATAS_BAYAR_MENIT, dan kursinya terbuka lagi untuk penumpang lain.
 * DIPANGGIL DARI: penumpang/index.php, admin/index.php (pembersihan otomatis),
 *                penumpang/function/booking.php (mulai hitung mundur),
 *                admin/function/verifikasi_pembayaran.php (hitung mundur baru saat ditolak)
 * CATATAN      : Mau ganti durasi? Ubah angka BATAS_BAYAR_MENIT di bawah. Pesanan yang SUDAH ada
 *                tetap memakai batas lamanya; angka baru berlaku untuk pesanan berikutnya.
 *
 * ATURAN SINGKAT (kolom bookings.batas_bayar):
 *   - Terisi  = pesanan sedang MENUNGGU pembayaran (belum bayar, atau bukti ditolak).
 *   - Kosong  = tidak ada batas: sudah kirim bukti (menunggu admin), sudah lunas, atau pesanan lama.
 *   - Pembayaran yang sudah dikirim penumpang TIDAK pernah dibatalkan otomatis,
 *     karena lambatnya admin memeriksa bukan salah penumpang.
 *   - Pesanan yang BELUM dibayar (atau buktinya ditolak) dan jadwalnya SUDAH berangkat juga
 *     dibatalkan, apa pun isi batas_bayar-nya. Ini menutup pesanan lama yang batas_bayar-nya kosong
 *     dan jadwal yang diubah admin setelah pesanan dibuat.
 * ============================================================
 */

const BATAS_BAYAR_MENIT = 120;   // 120 menit = 2 jam

// Folder bukti transfer (sama dengan FOLDER_BUKTI di penumpang/function/pembayaran.php)
const DIR_BUKTI_BATAS = __DIR__ . '/../uploads/bukti/';

/** Durasi untuk ditampilkan ke penumpang: 120 -> "2 jam", 90 -> "90 menit" */
function teks_durasi_bayar(): string
{
    $menit = BATAS_BAYAR_MENIT;
    if ($menit >= 60 && $menit % 60 === 0) {
        return ($menit / 60) . ' jam';
    }
    return $menit . ' menit';
}

/**
 * Mulai (atau mulai ulang) hitung mundur untuk satu pesanan: sekarang + BATAS_BAYAR_MENIT.
 * Batasnya tidak pernah melewati jam berangkat, karena tiket yang dibayar setelah bus berangkat tidak berguna.
 * Waktu dihitung oleh MySQL (NOW()) supaya sama dengan pembanding di pembersihan,
 * jadi tidak terpengaruh perbedaan zona waktu PHP dan MySQL.
 */
function atur_batas_bayar(mysqli $db, int $booking_id): void
{
    $menit = BATAS_BAYAR_MENIT;
    $stmt = $db->prepare(
        'UPDATE bookings b
         JOIN schedules s ON s.id = b.schedule_id
         SET b.batas_bayar = LEAST(DATE_ADD(NOW(), INTERVAL ? MINUTE), s.jam_berangkat)
         WHERE b.id = ?'
    );
    $stmt->bind_param('ii', $menit, $booking_id);
    $stmt->execute();
}

/**
 * Hapus pesanan yang melewati batas waktu bayar. Mengembalikan jumlah pesanan yang dibatalkan.
 *
 * Yang dibatalkan: tidak ada pembayaran berstatus pending/lunas, DAN salah satu dari:
 *   (a) batas_bayar sudah lewat, atau
 *   (b) jadwal sudah berangkat (jam_berangkat < sekarang), walau batas_bayar kosong / masih di depan.
 * (Pembayaran 'ditolak' ikut dihapus bersama pesanannya.)
 * Aman dipanggil di setiap halaman: kalau tidak ada yang kedaluwarsa, hanya satu query ringan.
 * Tidak pernah melempar error ke halaman; kegagalan hanya dicatat di log PHP.
 */
function batalkan_pesanan_kedaluwarsa(mysqli $db): int
{
    // Syarat yang sama dipakai untuk pengecekan cepat dan untuk pengambilan yang dikunci
    $syarat = "NOT EXISTS (SELECT 1 FROM payments p
                           WHERE p.booking_id = b.id AND p.status IN ('pending', 'lunas'))
               AND (
                    (b.batas_bayar IS NOT NULL AND b.batas_bayar < NOW())
                    OR EXISTS (SELECT 1 FROM schedules s
                               WHERE s.id = b.schedule_id AND s.jam_berangkat < NOW())
               )";

    try {
        // 1. Pengecekan cepat tanpa transaksi (kasus paling sering: tidak ada yang kedaluwarsa)
        if ($db->query("SELECT 1 FROM bookings b WHERE $syarat LIMIT 1")->num_rows === 0) {
            return 0;
        }
    } catch (Throwable $e) {
        error_log('batalkan_pesanan_kedaluwarsa (cek): ' . $e->getMessage());
        return 0;
    }

    $nama_bukti = [];
    $jumlah     = 0;

    try {
        $db->begin_transaction();

        // 2. Ambil + KUNCI pesanan yang kedaluwarsa. Kalau penumpang kebetulan sedang
        //    mengirim bukti, ia menunggu transaksi ini selesai (lalu gagal dengan pesan jelas
        //    di simpan_pembayaran), sehingga tidak ada pembayaran yang "tersangkut" tanpa pesanan.
        $hasil = $db->query("SELECT b.id FROM bookings b WHERE $syarat LIMIT 500 FOR UPDATE");
        $ids   = array_map('intval', array_column($hasil->fetch_all(MYSQLI_ASSOC), 'id'));

        if ($ids) {
            $daftar = implode(',', $ids);   // aman: semuanya sudah angka

            // Catat nama file bukti (pembayaran yang ditolak) untuk dihapus dari folder
            $bukti = $db->query("SELECT bukti_transfer FROM payments WHERE booking_id IN ($daftar)");
            foreach ($bukti->fetch_all(MYSQLI_ASSOC) as $b) {
                if (trim((string) $b['bukti_transfer']) !== '') {
                    $nama_bukti[] = basename($b['bukti_transfer']);
                }
            }

            // Pembayaran dulu (foreign key), baru pesanannya
            $db->query("DELETE FROM payments WHERE booking_id IN ($daftar)");
            $db->query("DELETE FROM bookings WHERE id IN ($daftar)");
            $jumlah = count($ids);
        }

        $db->commit();
    } catch (Throwable $e) {
        try { $db->rollback(); } catch (Throwable $ignored) {}
        error_log('batalkan_pesanan_kedaluwarsa: ' . $e->getMessage());
        return 0;
    }

    // 3. Hapus file bukti yang sudah tidak punya pesanan (setelah database berhasil dibersihkan)
    foreach ($nama_bukti as $nama) {
        if (is_file(DIR_BUKTI_BATAS . $nama)) {
            @unlink(DIR_BUKTI_BATAS . $nama);
        }
    }

    return $jumlah;
}