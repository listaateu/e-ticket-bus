<?php
/**
 * ============================================================
 * FILE         : penumpang/function/booking.php
 * FUNGSI       : Menyimpan pemesanan kursi (simpan_booking) dan mengambil daftar tiket penumpang.
 *                Setiap pemesanan mendapat KODE UNIK (1-999) yang ditambahkan ke harga tiket,
 *                supaya admin mudah mencocokkan nominal transfer dengan mutasi rekening.
 *                Setiap pemesanan juga punya BATAS WAKTU BAYAR (bookings.batas_bayar, lihat includes/batas_bayar.php).
 * DIPANGGIL DARI: index.php; dipakai pages/jadwal/pilih_kursi.php, pages/tiket/*
 * ============================================================
 */
/** Pemesanan tiket & daftar tiket penumpang */

function buat_kode_booking(): string
{
    return 'TKT-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

/**
 * Memilih kode unik 1-999 untuk pemesanan baru.
 * Kode dipilih acak dari yang MASIH KOSONG, yaitu yang tidak membuat total transfer
 * sama dengan pemesanan lain yang belum lunas (menunggu bayar / menunggu verifikasi / ditolak).
 * Pemesanan yang sudah lunas tidak dihitung, jadi kodenya boleh dipakai lagi.
 */
function buat_kode_unik(mysqli $conn, int $harga): int
{
    $min = $harga + 1;
    $maks = $harga + 999;

    $stmt = $conn->prepare(
        "SELECT s.harga + bk.kode_unik AS total
         FROM bookings bk
         JOIN schedules s ON s.id = bk.schedule_id
         LEFT JOIN payments p ON p.booking_id = bk.id
         WHERE COALESCE(p.status, 'belum') <> 'lunas'
           AND s.harga + bk.kode_unik BETWEEN ? AND ?"
    );
    $stmt->bind_param('ii', $min, $maks);
    $stmt->execute();

    $terpakai = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $baris) {
        $terpakai[(int) round((float) $baris['total'])] = true;
    }

    $kosong = [];
    for ($k = 1; $k <= 999; $k++) {
        if (!isset($terpakai[$harga + $k])) {
            $kosong[] = $k;
        }
    }

    // Hampir mustahil semua 999 kode terpakai; kalau terjadi, pakai kode acak saja
    return $kosong ? $kosong[array_rand($kosong)] : random_int(1, 999);
}

/** Total yang harus ditransfer penumpang = harga tiket + kode unik */
function total_bayar(array $b): int
{
    return (int) round((float) $b['harga']) + (int) ($b['kode_unik'] ?? 0);
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

        $kode      = buat_kode_booking();
        $kode_unik = buat_kode_unik($conn, (int) round((float) $jadwal['harga']));

        $ins = $conn->prepare('INSERT INTO bookings (user_id, schedule_id, nomor_kursi, kode_booking, kode_unik) VALUES (?, ?, ?, ?, ?)');
        $ins->bind_param('iissi', $user_id, $schedule_id, $nomor_kursi, $kode, $kode_unik);
        $ins->execute();
        $id = $conn->insert_id;
        atur_batas_bayar($conn, $id);   // mulai hitung mundur batas waktu bayar
        $conn->commit();
        return [true, $id];
    } catch (Throwable $e) {
        $conn->rollback();
        return [false, 'Gagal menyimpan pemesanan. Coba lagi.'];
    }
}

// alasan_tolak = alasan penolakan dari admin (kosong kalau belum pernah ditolak)
// kode_unik    = angka tambahan di nominal transfer (0 untuk pemesanan lama)
// batas_bayar  = batas akhir pembayaran (kosong = tidak ada batas)
// sisa_detik   = sisa waktu sampai batas_bayar, dihitung MySQL supaya tidak terpengaruh beda zona waktu PHP
// sudah_berangkat = 1 bila jam berangkat sudah lewat (tiket lunas yang sudah lewat tampil "Selesai")
const SQL_BOOKING = "SELECT bk.id, bk.user_id, bk.schedule_id, bk.nomor_kursi, bk.kode_unik, bk.kode_booking,
                            bk.batas_bayar, TIMESTAMPDIFF(SECOND, NOW(), bk.batas_bayar) AS sisa_detik,
                            (s.jam_berangkat < NOW()) AS sudah_berangkat,
                            s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.jam_tiba, s.harga, s.sopir,
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
    // Urutan: yang BELUM berangkat dulu, dari keberangkatan paling awal ke paling akhir.
    // Yang SUDAH berangkat ditaruh di bawah, dari yang paling baru lewat ke yang paling lama.
    $stmt = $conn->prepare(
        SQL_BOOKING . ' WHERE bk.user_id = ?
         ORDER BY (s.jam_berangkat < NOW()) ASC,
                  IF(s.jam_berangkat >= NOW(), UNIX_TIMESTAMP(s.jam_berangkat), -UNIX_TIMESTAMP(s.jam_berangkat)) ASC,
                  bk.id ASC'
    );
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

/** true bila tiket sudah lunas tetapi bus sudah berangkat (perjalanan selesai) */
function tiket_selesai(array $b): bool
{
    return status_tiket($b) === 'lunas' && !empty($b['sudah_berangkat']);
}

/**
 * true bila tiket ini sedang dihitung mundur: belum dibayar / bukti ditolak, dan punya batas waktu.
 * (Yang sudah kirim bukti atau lunas tidak punya batas, jadi tidak pernah dibatalkan otomatis.)
 */
function batas_bayar_aktif(array $b): bool
{
    return !empty($b['batas_bayar']) && in_array(status_tiket($b), ['belum_bayar', 'gagal'], true);
}

/** Batas bayar untuk teks singkat: "14:30 WIB", atau "13/10 01:00 WIB" kalau bukan hari ini */
function teks_batas_bayar(string $datetime): string
{
    $t = strtotime($datetime);
    $hari_ini = date('Y-m-d') === date('Y-m-d', $t);
    return ($hari_ini ? '' : date('j/n', $t) . ' ') . date('H:i', $t) . ' WIB';
}