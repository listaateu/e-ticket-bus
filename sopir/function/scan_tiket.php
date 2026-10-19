<?php
/**
 * ============================================================
 * FILE         : sopir/function/scan_tiket.php
 * FUNGSI       : Validasi tiket yang dipindai sopir/kondektur, lalu menandai penumpang SUDAH NAIK
 *                (kolom booking_penumpang.status_naik, waktu_naik, diperiksa_oleh).
 *                Kode yang dipindai = booking_penumpang.kode_tiket, sama dengan isi QR di e-ticket penumpang.
 * DIPANGGIL DARI: sopir/index.php (endpoint JSON scan). Ini SATU-SATUNYA jalan mencatat penumpang naik.
 *
 * URUTAN PEMERIKSAAN:
 *   1. kode ada di database?            -> tidak_ditemukan
 *   2. tiket untuk bus milik sopir ini? -> bukan_jadwal_anda
 *   3. tiket untuk jadwal yang dipilih? -> jadwal_lain
 *   4. pembayaran sudah lunas?          -> belum_lunas
 *   5. (opsional) waktu boarding        -> terlalu_awal / selesai
 *   6. sudah pernah naik?               -> sudah_naik
 *   7. semua lolos                      -> berhasil (dicatat naik)
 *
 * CATATAN      : Pemeriksaan waktu (langkah 5) AKTIF (CEK_WAKTU_NAIK = true): boarding baru dibuka
 *                BOARDING_BUKA_MENIT menit sebelum berangkat. Setelah bus berangkat, penumpang yang belum naik
 *                dianggap tertinggal: QR-nya disembunyikan di halaman penumpang (bukan ditolak di sini).
 *                Untuk demo yang butuh scan kapan saja, ubah CEK_WAKTU_NAIK menjadi false.
 * ============================================================
 */
const CEK_WAKTU_NAIK      = true;    // true = scan hanya boleh mendekati jam berangkat
const BOARDING_BUKA_MENIT = 180;   // 3 jam sebelum berangkat

/** Hasil pemeriksaan waktu: 'terlalu_awal' | 'selesai' | null (boleh) */
function cek_waktu_boarding(int $menit_ke_berangkat, bool $sudah_tiba): ?string
{
    if (!CEK_WAKTU_NAIK) {
        return null;
    }
    if ($sudah_tiba) {
        return 'selesai';
    }
    if ($menit_ke_berangkat > BOARDING_BUKA_MENIT) {
        return 'terlalu_awal';
    }
    return null;
}

/** Susun hasil scan (dikirim ke browser sebagai JSON) */
function hasil_scan(string $status, string $pesan = '', ?array $pax = null): array
{
    $peta = [
        'berhasil'          => ['success', 'Silakan naik',            'Tiket valid. Penumpang tercatat sudah naik.'],
        'sudah_naik'        => ['warning', 'Tiket sudah dipakai',     'Penumpang ini sudah tercatat naik.'],
        'tidak_ditemukan'   => ['danger',  'Tiket tidak dikenal',     'Kode tidak ditemukan di sistem.'],
        'bukan_jadwal_anda' => ['danger',  'Bukan bus Anda',          'Tiket ini untuk bus lain.'],
        'jadwal_lain'       => ['danger',  'Salah jadwal',            'Tiket ini untuk jadwal yang berbeda.'],
        'belum_lunas'       => ['danger',  'Belum lunas',             'Pembayaran tiket ini belum diverifikasi admin.'],
        'terlalu_awal'      => ['warning', 'Boarding belum dibuka',   'Boarding belum dibuka untuk jadwal ini.'],
        'selesai'           => ['danger',  'Perjalanan sudah selesai', 'Jadwal ini sudah selesai.'],
    ];
    [$tone, $judul, $bawaan] = $peta[$status] ?? $peta['tidak_ditemukan'];

    return [
        'ok'        => $status === 'berhasil',
        'status'    => $status,
        'tone'      => $tone,
        'judul'     => $judul,
        'pesan'     => $pesan !== '' ? $pesan : $bawaan,
        'penumpang' => $pax,
    ];
}

/** Data penumpang yang ditampilkan di kartu hasil scan */
function ringkas_penumpang(array $r): array
{
    return [
        'nama'       => (string) $r['nama'],
        'kursi'      => (string) ($r['nomor_kursi'] ?? ''),
        'kode_tiket' => (string) ($r['kode_tiket'] ?? ''),
        'pemesan'    => (string) $r['pemesan'],
        'rute'       => $r['kota_asal'] . ' → ' . $r['kota_tujuan'],
        'berangkat'  => tanggal_indo($r['jam_berangkat']),
        'bus'        => $r['nama_bus'] . ' · ' . $r['plat_nomor'],
        'waktu_naik' => !empty($r['waktu_naik']) ? jam($r['waktu_naik']) : '',
    ];
}

/**
 * Periksa kode tiket lalu catat penumpang naik.
 *   $jadwal_id = jadwal yang sedang dipilih sopir di halaman Scan
 */
function validasi_tiket(mysqli $conn, int $sopir_id, string $sopir_nama, int $jadwal_id, string $kode): array
{
    $kode = trim($kode);
    if ($kode === '' || strlen($kode) > 60) {
        return hasil_scan('tidak_ditemukan');
    }

    $stmt = $conn->prepare(
        "SELECT bp.id, bp.nama, bp.tipe, bp.nomor_kursi, bp.kode_tiket, bp.status_naik, bp.waktu_naik, bp.schedule_id,
                u.nama AS pemesan,
                s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.sopir,
                b.nama_bus, b.plat_nomor,
                TIMESTAMPDIFF(MINUTE, NOW(), s.jam_berangkat) AS menit_ke_berangkat,
                (COALESCE(s.jam_tiba, s.jam_berangkat) < NOW()) AS sudah_tiba,
                EXISTS (SELECT 1 FROM payments py WHERE py.booking_id = bp.booking_id AND py.status = 'lunas') AS lunas
         FROM booking_penumpang bp
         JOIN bookings bk ON bk.id = bp.booking_id
         JOIN users u     ON u.id  = bk.user_id
         JOIN schedules s ON s.id  = bp.schedule_id
         JOIN buses b     ON b.id  = s.bus_id
         WHERE bp.kode_tiket = ?
         LIMIT 1"
    );
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();

    // 1. kode tidak dikenal
    if (!$r) {
        return hasil_scan('tidak_ditemukan');
    }
    $pax = ringkas_penumpang($r);

    // 2. tiket milik bus/sopir lain
    if (strcasecmp(trim((string) $r['sopir']), trim($sopir_nama)) !== 0) {
        return hasil_scan('bukan_jadwal_anda', 'Tiket ini untuk bus lain (sopir ' . $r['sopir'] . '). Arahkan penumpang ke bus yang benar.', $pax);
    }

    // 3. tiket untuk jadwal lain milik sopir yang sama
    if ((int) $r['schedule_id'] !== $jadwal_id) {
        return hasil_scan('jadwal_lain', 'Tiket ini untuk ' . $pax['rute'] . ', ' . $pax['berangkat'] . '. Pilih jadwal yang sesuai di atas.', $pax);
    }

    // 4. belum lunas
    if (!(int) $r['lunas']) {
        return hasil_scan('belum_lunas', '', $pax);
    }

    // 5. waktu boarding (hanya bila CEK_WAKTU_NAIK = true)
    $waktu = cek_waktu_boarding((int) $r['menit_ke_berangkat'], (bool) (int) $r['sudah_tiba']);
    if ($waktu === 'terlalu_awal') {
        $jam_buka = date('H:i', strtotime((string) $r['jam_berangkat']) - BOARDING_BUKA_MENIT * 60);
        return hasil_scan('terlalu_awal', 'Boarding baru dibuka pukul ' . $jam_buka . ' WIB (' . (BOARDING_BUKA_MENIT / 60) . ' jam sebelum berangkat).', $pax);
    }
    if ($waktu === 'selesai') {
        return hasil_scan('selesai', '', $pax);
    }

    // 6. sudah naik
    if ((int) $r['status_naik'] === 1) {
        $teks = !empty($r['waktu_naik']) ? 'Penumpang ini sudah naik pukul ' . jam($r['waktu_naik']) . '.' : '';
        return hasil_scan('sudah_naik', $teks, $pax);
    }

    // 7. catat naik. "AND status_naik = 0" menjaga agar dua sopir yang memindai bersamaan tidak mencatat dobel.
    $ubah = $conn->prepare('UPDATE booking_penumpang SET status_naik = 1, waktu_naik = NOW(), diperiksa_oleh = ? WHERE id = ? AND status_naik = 0');
    $id_pax = (int) $r['id'];
    $ubah->bind_param('ii', $sopir_id, $id_pax);
    $ubah->execute();

    $baca = $conn->prepare('SELECT waktu_naik FROM booking_penumpang WHERE id = ?');
    $baca->bind_param('i', $id_pax);
    $baca->execute();
    $r['waktu_naik'] = $baca->get_result()->fetch_assoc()['waktu_naik'] ?? null;
    $pax = ringkas_penumpang($r);

    if ($ubah->affected_rows === 0) {
        return hasil_scan('sudah_naik', 'Penumpang ini baru saja dicatat naik pukul ' . $pax['waktu_naik'] . '.', $pax);
    }
    return hasil_scan('berhasil', '', $pax);
}

// Tidak ada fungsi naik / batal naik manual: status naik hanya dicatat oleh validasi_tiket() di atas.