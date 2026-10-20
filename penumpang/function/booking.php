<?php
/**
 * ============================================================
 * FILE         : penumpang/function/booking.php
 * FUNGSI       : Menyimpan pemesanan kursi (simpan_booking) dan mengambil daftar tiket penumpang.
 *                Setiap pemesanan mendapat KODE UNIK (1-999) yang ditambahkan ke harga tiket,
 *                supaya admin mudah mencocokkan nominal transfer dengan mutasi rekening.
 *                Setiap pemesanan juga punya BATAS WAKTU BAYAR (bookings.batas_bayar, lihat includes/batas_bayar.php).
 *                Satu pemesanan bisa untuk 1-4 penumpang DEWASA (masing-masing dapat kursi) + BAYI (gratis, tanpa kursi,
 *                maksimal 1 bayi per dewasa). Data tiap penumpang disimpan di tabel booking_penumpang.
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
function buat_kode_unik(mysqli $conn, int $harga): int   // $harga = total tiket (harga x jumlah dewasa)
{
    $min = $harga + 1;
    $maks = $harga + 999;

    $stmt = $conn->prepare(
        "SELECT s.harga * bk.jumlah_dewasa + bk.kode_unik AS total
         FROM bookings bk
         JOIN schedules s ON s.id = bk.schedule_id
         LEFT JOIN payments p ON p.booking_id = bk.id
         WHERE COALESCE(p.status, 'belum') <> 'lunas'
           AND s.harga * bk.jumlah_dewasa + bk.kode_unik BETWEEN ? AND ?"
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

/** Batas penumpang dewasa dalam satu pemesanan (bayi maksimal sama dengan jumlah dewasa) */
const MAKS_DEWASA = 4;

/**
 * Membaca jumlah penumpang dari alamat/formulir ($_GET), lalu merapikannya:
 * dewasa 1-4, bayi 0 sampai jumlah dewasa. Mengembalikan [dewasa, bayi].
 */
function jumlah_penumpang_dari(array $sumber): array
{
    $dewasa = max(1, min(MAKS_DEWASA, (int) ($sumber['dewasa'] ?? 1)));
    $bayi   = max(0, min($dewasa, (int) ($sumber['bayi'] ?? 0)));
    return [$dewasa, $bayi];
}

/** "2 Dewasa, 1 Bayi" */
function teks_penumpang(int $dewasa, int $bayi = 0): string
{
    return $dewasa . ' Dewasa' . ($bayi > 0 ? ', ' . $bayi . ' Bayi' : '');
}

/** Jumlah dewasa pada satu pemesanan (pemesanan lama otomatis 1) */
function jumlah_dewasa(array $b): int
{
    return max(1, (int) ($b['jumlah_dewasa'] ?? 1));
}

/** Harga tiket seluruh penumpang dewasa (bayi gratis) */
function harga_tiket_total(array $b): int
{
    return (int) round((float) $b['harga']) * jumlah_dewasa($b);
}

/** Total yang harus ditransfer penumpang = harga tiket x jumlah dewasa + kode unik */
function total_bayar(array $b): int
{
    return harga_tiket_total($b) + (int) ($b['kode_unik'] ?? 0);
}

/** Nama penumpang: huruf, spasi, titik, koma, apostrof, strip; 2-100 karakter */
function nama_penumpang_valid(string $nama): bool
{
    // preg dengan flag /u menghitung huruf (bukan byte), jadi tidak butuh ekstensi mbstring
    return preg_match('/^.{2,100}$/us', $nama) === 1 && preg_match("/^[\\p{L}\\s.,'\\-]+$/u", $nama) === 1;
}

/**
 * Simpan pemesanan untuk beberapa penumpang. Mengembalikan [berhasil(bool), pesan|id_booking].
 *   $nama_dewasa : nama tiap penumpang dewasa (1-4 orang)
 *   $nama_bayi   : nama tiap bayi (0 sampai sebanyak dewasa), bayi tidak memakai kursi
 *   $kursi       : nomor kursi, jumlahnya HARUS sama dengan jumlah dewasa; kursi ke-1 untuk dewasa ke-1, dst.
 * Cek kursi dilakukan di dalam transaksi + lock supaya tidak dobel.
 */
function simpan_booking(mysqli $conn, int $user_id, int $schedule_id, array $nama_dewasa, array $nama_bayi, array $kursi): array
{
    $jadwal = get_jadwal($conn, $schedule_id);
    if (!$jadwal) {
        return [false, 'Jadwal tidak ditemukan.'];
    }
    if (strtotime($jadwal['jam_berangkat']) < time()) {
        return [false, 'Jadwal ini sudah berangkat.'];
    }

    // ---- Rapikan & periksa jumlah penumpang ----
    $nama_dewasa = array_values(array_map(fn($n) => trim(preg_replace('/\s+/', ' ', (string) $n)), $nama_dewasa));
    $nama_bayi   = array_values(array_map(fn($n) => trim(preg_replace('/\s+/', ' ', (string) $n)), $nama_bayi));
    $dewasa = count($nama_dewasa);
    $bayi   = count($nama_bayi);

    if ($dewasa < 1 || $dewasa > MAKS_DEWASA) {
        return [false, 'Jumlah penumpang dewasa harus 1 sampai ' . MAKS_DEWASA . ' orang.'];
    }
    if ($bayi > $dewasa) {
        return [false, 'Jumlah bayi tidak boleh lebih banyak dari jumlah dewasa (1 dewasa maksimal membawa 1 bayi).'];
    }
    foreach (array_merge($nama_dewasa, $nama_bayi) as $nama) {
        if (!nama_penumpang_valid($nama)) {
            return [false, 'Nama penumpang wajib diisi dengan benar (huruf saja, minimal 2 karakter).'];
        }
    }

    // ---- Periksa kursi ----
    $kursi = array_values(array_map(fn($k) => trim((string) $k), $kursi));
    if (count($kursi) !== $dewasa || count(array_unique($kursi)) !== $dewasa) {
        return [false, 'Pilih ' . $dewasa . ' kursi yang berbeda, sesuai jumlah penumpang dewasa.'];
    }
    foreach ($kursi as $k) {
        if (!ctype_digit($k) || (int) $k < 1 || (int) $k > (int) $jadwal['kapasitas_kursi']) {
            return [false, 'Nomor kursi tidak valid.'];
        }
    }
    $kursi = array_map(fn($k) => (string) (int) $k, $kursi);   // "05" -> "5"

    $conn->begin_transaction();
    try {
        // Kunci kursi-kursi yang diminta, lalu pastikan belum ada yang memesan
        $tanda = implode(',', array_fill(0, $dewasa, '?'));
        $cek   = $conn->prepare("SELECT nomor_kursi FROM booking_penumpang WHERE schedule_id = ? AND nomor_kursi IN ($tanda) FOR UPDATE");
        $cek->bind_param('i' . str_repeat('s', $dewasa), $schedule_id, ...$kursi);
        $cek->execute();
        $diambil = array_column($cek->get_result()->fetch_all(MYSQLI_ASSOC), 'nomor_kursi');
        if ($diambil) {
            $conn->rollback();
            return [false, 'Maaf, kursi ' . implode(', ', $diambil) . ' baru saja dipesan orang lain. Pilih kursi lain.'];
        }

        $kode        = buat_kode_booking();
        $total_tiket = (int) round((float) $jadwal['harga']) * $dewasa;
        $kode_unik   = buat_kode_unik($conn, $total_tiket);

        $urut_kursi = array_map('intval', $kursi);
        sort($urut_kursi);
        $teks_kursi = implode(', ', $urut_kursi);   // contoh "5, 6, 12" (hanya untuk tampilan)

        $ins = $conn->prepare('INSERT INTO bookings (user_id, schedule_id, nomor_kursi, jumlah_dewasa, jumlah_bayi, kode_booking, kode_unik) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->bind_param('iisiisi', $user_id, $schedule_id, $teks_kursi, $dewasa, $bayi, $kode, $kode_unik);
        $ins->execute();
        $id = $conn->insert_id;

        $pax = $conn->prepare('INSERT INTO booking_penumpang (booking_id, schedule_id, nama, tipe, nomor_kursi) VALUES (?, ?, ?, ?, ?)');
        foreach ($nama_dewasa as $i => $nama) {
            $tipe = 'dewasa';
            $pax->bind_param('iisss', $id, $schedule_id, $nama, $tipe, $kursi[$i]);
            $pax->execute();
        }
        foreach ($nama_bayi as $nama) {
            $tipe = 'bayi';
            $tanpa_kursi = null;
            $pax->bind_param('iisss', $id, $schedule_id, $nama, $tipe, $tanpa_kursi);
            $pax->execute();
        }

        atur_batas_bayar($conn, $id);   // mulai hitung mundur batas waktu bayar
        $conn->commit();
        return [true, $id];
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        // Kode 1062 = kursi yang sama dipesan orang lain pada detik yang sama
        if ($e instanceof mysqli_sql_exception && (int) $e->getCode() === 1062) {
            return [false, 'Maaf, ada kursi yang baru saja dipesan orang lain. Pilih kursi lain.'];
        }
        return [false, 'Gagal menyimpan pemesanan. Coba lagi.'];
    }
}

/** Daftar penumpang satu pemesanan: dewasa dulu (urut kursi), lalu bayi */
function daftar_penumpang_booking(mysqli $conn, int $booking_id): array
{
    $stmt = $conn->prepare("SELECT nama, tipe, nomor_kursi FROM booking_penumpang WHERE booking_id = ? ORDER BY (tipe = 'bayi') ASC, id ASC");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// alasan_tolak = alasan penolakan dari admin (kosong kalau belum pernah ditolak)
// kode_unik    = angka tambahan di nominal transfer (0 untuk pemesanan lama)
// batas_bayar  = batas akhir pembayaran (kosong = tidak ada batas)
// sisa_detik   = sisa waktu sampai batas_bayar, dihitung MySQL supaya tidak terpengaruh beda zona waktu PHP
// sudah_berangkat = 1 bila jam berangkat sudah lewat (tiket lunas yang sudah lewat tampil "Selesai" dan QR-nya tidak ditampilkan lagi)
// menit_ke_berangkat = selisih menit dari sekarang ke jam berangkat (dipakai untuk menentukan kapan QR e-ticket muncul)
// belum_naik      = jumlah penumpang dewasa di pemesanan ini yang belum discan naik (dipakai untuk peringatan "tertinggal bus")
const SQL_BOOKING = "SELECT bk.id, bk.user_id, bk.schedule_id, bk.nomor_kursi, bk.jumlah_dewasa, bk.jumlah_bayi, bk.kode_unik, bk.kode_booking,
                            bk.batas_bayar, TIMESTAMPDIFF(SECOND, NOW(), bk.batas_bayar) AS sisa_detik,
                            (s.jam_berangkat < NOW()) AS sudah_berangkat,
                            TIMESTAMPDIFF(MINUTE, NOW(), s.jam_berangkat) AS menit_ke_berangkat,
                            (SELECT COUNT(*) FROM booking_penumpang bpx WHERE bpx.booking_id = bk.id AND bpx.tipe = 'dewasa' AND bpx.status_naik = 0) AS belum_naik,
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

/** true bila tiket sudah lunas tetapi bus sudah berangkat (QR tidak berlaku lagi) */
function tiket_selesai(array $b): bool
{
    return status_tiket($b) === 'lunas' && !empty($b['sudah_berangkat']);
}

/**
 * QR e-ticket baru muncul QR_MUNCUL_JAM jam sebelum berangkat (seperti KAI Access).
 * Catatan: scan sopir/kondektur baru dibuka BOARDING_BUKA_MENIT (180 menit = 3 jam) sebelum berangkat,
 * jadi QR sudah tampil lebih dulu daripada jendela scan. Dua angka ini sengaja dibuat berbeda.
 */
const QR_MUNCUL_JAM = 5;

/** Hanya untuk teks info di e-ticket. Samakan dengan BOARDING_BUKA_MENIT di sopir/function/scan_tiket.php (180 menit = 3 jam). */
const SCAN_BUKA_JAM = 3;

/** true bila QR sudah boleh ditampilkan: tiket lunas, bus belum berangkat, dan sudah masuk jendela QR_MUNCUL_JAM jam */
function tiket_qr_terbuka(array $b): bool
{
    if (status_tiket($b) !== 'lunas' || !empty($b['sudah_berangkat'])) {
        return false;
    }
    return (int) ($b['menit_ke_berangkat'] ?? PHP_INT_MAX) <= QR_MUNCUL_JAM * 60;
}

/** Jam QR akan muncul, mis. "14/10 01:00 WIB" atau "01:00 WIB" kalau hari ini */
function teks_qr_muncul(string $jam_berangkat): string
{
    $t = strtotime($jam_berangkat) - QR_MUNCUL_JAM * 3600;
    return (date('Y-m-d', $t) === date('Y-m-d') ? date('H:i', $t) : date('d/m H:i', $t)) . ' WIB';
}

/** true bila bus sudah berangkat padahal masih ada penumpang dewasa di pemesanan ini yang belum naik (tertinggal bus) */
function tiket_tertinggal(array $b): bool
{
    return tiket_selesai($b) && (int) ($b['belum_naik'] ?? 0) > 0;
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