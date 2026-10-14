<?php
/**
 * ============================================================
 * FILE         : sopir/function/jadwal_sopir.php
 * FUNGSI       : Query jadwal milik sopir yang login dan daftar manifest penumpangnya.
 *                Jadwal milik sopir = baris schedules yang kolom "sopir"-nya sama dengan users.nama
 *                (admin memilih sopir lewat dropdown di menu Jadwal & Rute, jadi namanya selalu sama).
 *                Manifest hanya berisi penumpang yang pemesanannya SUDAH LUNAS (diverifikasi admin).
 * DIPANGGIL DARI: sopir/index.php; dipakai pages/beranda, pages/jadwal/*, pages/scan
 * ============================================================
 */

// Syarat "pemesanan sudah lunas" untuk tabel booking_penumpang (alias bp)
const SQL_LUNAS = "EXISTS (SELECT 1 FROM payments py WHERE py.booking_id = bp.booking_id AND py.status = 'lunas')";

// Satu jadwal + bus + hitungan penumpang (dewasa lunas, yang sudah naik, bayi)
const SQL_JADWAL_SOPIR = "SELECT s.id, s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.jam_tiba, s.harga, s.sopir,
        b.nama_bus, b.plat_nomor, b.kelas, b.kapasitas_kursi,
        (s.jam_berangkat < NOW()) AS sudah_berangkat,
        (COALESCE(s.jam_tiba, s.jam_berangkat) < NOW()) AS sudah_tiba,
        TIMESTAMPDIFF(MINUTE, NOW(), s.jam_berangkat) AS menit_ke_berangkat,
        (SELECT COUNT(*) FROM booking_penumpang bp
          WHERE bp.schedule_id = s.id AND bp.tipe = 'dewasa' AND " . SQL_LUNAS . ") AS total_dewasa,
        (SELECT COUNT(*) FROM booking_penumpang bp
          WHERE bp.schedule_id = s.id AND bp.tipe = 'dewasa' AND bp.status_naik = 1 AND " . SQL_LUNAS . ") AS total_naik,
        (SELECT COUNT(*) FROM booking_penumpang bp
          WHERE bp.schedule_id = s.id AND bp.tipe = 'bayi' AND " . SQL_LUNAS . ") AS total_bayi
     FROM schedules s
     JOIN buses b ON b.id = s.bus_id";

/**
 * Daftar jadwal milik sopir.
 *   $filter: 'hari_ini' (berangkat hari ini) | 'akan' (belum tiba, termasuk yang sedang jalan) | 'selesai' (sudah tiba)
 */
function jadwal_sopir(mysqli $conn, string $nama, string $filter = 'akan'): array
{
    $syarat = [
        'hari_ini' => 'DATE(s.jam_berangkat) = CURDATE()',
        'akan'     => 'COALESCE(s.jam_tiba, s.jam_berangkat) >= NOW()',
        'selesai'  => 'COALESCE(s.jam_tiba, s.jam_berangkat) < NOW()',
    ][$filter] ?? 'COALESCE(s.jam_tiba, s.jam_berangkat) >= NOW()';
    $urut = $filter === 'selesai' ? 'DESC' : 'ASC';

    $stmt = $conn->prepare(
        SQL_JADWAL_SOPIR . " WHERE LOWER(TRIM(s.sopir)) = LOWER(TRIM(?)) AND $syarat
         ORDER BY s.jam_berangkat $urut, s.id $urut LIMIT 60"
    );
    $stmt->bind_param('s', $nama);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Satu jadwal, HANYA kalau memang milik sopir ini (null kalau bukan miliknya / tidak ada) */
function get_jadwal_sopir(mysqli $conn, int $jadwal_id, string $nama): ?array
{
    $stmt = $conn->prepare(SQL_JADWAL_SOPIR . ' WHERE s.id = ? AND LOWER(TRIM(s.sopir)) = LOWER(TRIM(?))');
    $stmt->bind_param('is', $jadwal_id, $nama);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/** Manifest satu jadwal: dewasa urut nomor kursi, lalu bayi. Hanya pemesanan yang sudah lunas. */
function manifest_jadwal(mysqli $conn, int $jadwal_id): array
{
    $stmt = $conn->prepare(
        "SELECT bp.id, bp.nama, bp.tipe, bp.nomor_kursi, bp.kode_tiket, bp.status_naik, bp.waktu_naik,
                k.nama AS pemeriksa, bk.kode_booking, u.nama AS pemesan
         FROM booking_penumpang bp
         JOIN bookings bk ON bk.id = bp.booking_id
         JOIN users u     ON u.id  = bk.user_id
         LEFT JOIN users k ON k.id = bp.diperiksa_oleh
         WHERE bp.schedule_id = ? AND " . SQL_LUNAS . "
         ORDER BY (bp.tipe = 'bayi') ASC, CAST(bp.nomor_kursi AS UNSIGNED) ASC, bp.id ASC"
    );
    $stmt->bind_param('i', $jadwal_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Jumlah pemesanan di jadwal ini yang belum lunas (tidak masuk manifest) */
function jumlah_belum_lunas(mysqli $conn, int $jadwal_id): int
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS n FROM bookings bk
         WHERE bk.schedule_id = ?
           AND NOT EXISTS (SELECT 1 FROM payments py WHERE py.booking_id = bk.id AND py.status = 'lunas')"
    );
    $stmt->bind_param('i', $jadwal_id);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['n'];
}

/** [sudah naik, total dewasa lunas] untuk satu jadwal */
function hitung_naik(mysqli $conn, int $jadwal_id): array
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total, COALESCE(SUM(bp.status_naik = 1), 0) AS naik
         FROM booking_penumpang bp
         WHERE bp.schedule_id = ? AND bp.tipe = 'dewasa' AND " . SQL_LUNAS
    );
    $stmt->bind_param('i', $jadwal_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return ['naik' => (int) $r['naik'], 'total' => (int) $r['total']];
}

/** Penumpang yang baru saja dinaikkan di jadwal ini (terbaru di atas) */
function riwayat_naik(mysqli $conn, int $jadwal_id, int $batas = 8): array
{
    $stmt = $conn->prepare(
        "SELECT nama, nomor_kursi, waktu_naik FROM booking_penumpang
         WHERE schedule_id = ? AND status_naik = 1 AND waktu_naik IS NOT NULL
         ORDER BY waktu_naik DESC, id DESC LIMIT ?"
    );
    $stmt->bind_param('ii', $jadwal_id, $batas);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}