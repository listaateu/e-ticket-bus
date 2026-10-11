<?php
/**
 * ============================================================
 * FILE         : penumpang/function/booking.php
 * FUNGSI       : Menyimpan pemesanan kursi (simpan_booking) dan mengambil daftar tiket penumpang.
 * DIPANGGIL DARI: index.php; dipakai pages/jadwal/pilih_kursi.php, pages/tiket/*
 * ============================================================
 */
/** Pemesanan tiket & daftar tiket penumpang */

function buat_kode_booking(): string
{
    return 'TKT-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

/**
 * Simpan pemesanan. Mengembalikan [berhasil(bool), pesan|id_booking].
 * Cek kursi dilakukan di dalam transaksi + lock supaya tidak dobel.
 */
function simpan_booking(mysqli $conn, int $user_id, int $schedule_id, string $nomor_kursi): array
{
    $jadwal = get_jadwal($conn, $schedule_id);
    if (!$jadwal) {
        return [false, 'Jadwal tidak ditemukan.'];
    }
    if (strtotime($jadwal['jam_berangkat']) < time()) {
        return [false, 'Jadwal ini sudah berangkat.'];
    }
    $nomor_kursi = trim($nomor_kursi);
    if (!ctype_digit($nomor_kursi) || (int) $nomor_kursi < 1 || (int) $nomor_kursi > (int) $jadwal['kapasitas_kursi']) {
        return [false, 'Nomor kursi tidak valid.'];
    }

    $conn->begin_transaction();
    try {
        $cek = $conn->prepare('SELECT id FROM bookings WHERE schedule_id = ? AND nomor_kursi = ? FOR UPDATE');
        $cek->bind_param('is', $schedule_id, $nomor_kursi);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $conn->rollback();
            return [false, 'Maaf, kursi ' . $nomor_kursi . ' baru saja dipesan orang lain. Pilih kursi lain.'];
        }

        $kode = buat_kode_booking();
        $ins = $conn->prepare('INSERT INTO bookings (user_id, schedule_id, nomor_kursi, kode_booking) VALUES (?, ?, ?, ?)');
        $ins->bind_param('iiss', $user_id, $schedule_id, $nomor_kursi, $kode);
        $ins->execute();
        $id = $conn->insert_id;
        $conn->commit();
        return [true, $id];
    } catch (Throwable $e) {
        $conn->rollback();
        return [false, 'Gagal menyimpan pemesanan. Coba lagi.'];
    }
}

// alasan_tolak = alasan penolakan dari admin (kosong kalau belum pernah ditolak)
const SQL_BOOKING = "SELECT bk.id, bk.user_id, bk.schedule_id, bk.nomor_kursi, bk.kode_booking,
                            s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.harga, s.sopir,
                            b.nama_bus, b.plat_nomor, b.kelas,
                            u.nama AS nama_penumpang,
                            p.id AS payment_id, p.metode_pembayaran, p.bukti_transfer,
                            p.status AS status_bayar, p.alasan_penolakan AS alasan_tolak
                     FROM bookings bk
                     JOIN schedules s ON s.id = bk.schedule_id
                     JOIN buses b     ON b.id = s.bus_id
                     JOIN users u     ON u.id = bk.user_id
                     LEFT JOIN payments p ON p.booking_id = bk.id";

/** Ambil satu booking milik user (dipakai di pembayaran & e-ticket) */
function get_booking(mysqli $conn, int $id, int $user_id): ?array
{
    $stmt = $conn->prepare(SQL_BOOKING . ' WHERE bk.id = ? AND bk.user_id = ?');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function daftar_tiket_user(mysqli $conn, int $user_id): array
{
    $stmt = $conn->prepare(SQL_BOOKING . ' WHERE bk.user_id = ? ORDER BY s.jam_berangkat DESC, bk.id DESC');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Status tampilan: belum_bayar | pending | lunas | gagal */
function status_tiket(array $b): string
{
    $status = $b['status_bayar'] ?? 'belum_bayar';

    // Admin menyimpan penolakan sebagai 'ditolak', sedangkan tampilan penumpang memakai 'gagal'.
    // Baris ini menerjemahkannya supaya badge "Ditolak" dan tombol upload ulang muncul.
    return $status === 'ditolak' ? 'gagal' : $status;
}