<?php
/**
 * ============================================================
 * FILE         : penumpang/function/jadwal.php
 * FUNGSI       : Query jadwal keberangkatan: cari_jadwal(), get_jadwal(), daftar_kota(), kursi_terisi().
 * DIPANGGIL DARI: index.php; dipakai pages/beranda, pages/jadwal/*
 * ============================================================
 */
/** Query jadwal keberangkatan */

const SQL_JADWAL = "SELECT s.id, s.bus_id, s.sopir, s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.jam_tiba, s.harga,
                           b.nama_bus, b.plat_nomor, b.kapasitas_kursi, b.kelas, b.tata_letak, b.fasilitas, b.foto,
                           (SELECT COUNT(*) FROM bookings bk WHERE bk.schedule_id = s.id) AS terisi
                    FROM schedules s
                    JOIN buses b ON b.id = s.bus_id";

function cari_jadwal(mysqli $conn, string $asal = '', string $tujuan = '', string $tanggal = '', int $limit = 0): array
{
    $sql = SQL_JADWAL . ' WHERE s.jam_berangkat >= NOW()';
    $tipe = '';
    $val  = [];

    if ($asal !== '') {
        $sql .= ' AND s.kota_asal LIKE ?';
        $tipe .= 's'; $val[] = "%$asal%";
    }
    if ($tujuan !== '') {
        $sql .= ' AND s.kota_tujuan LIKE ?';
        $tipe .= 's'; $val[] = "%$tujuan%";
    }
    if ($tanggal !== '') {
        $sql .= ' AND DATE(s.jam_berangkat) = ?';
        $tipe .= 's'; $val[] = $tanggal;
    }
    $sql .= ' ORDER BY s.jam_berangkat ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $stmt = $conn->prepare($sql);
    if ($tipe !== '') {
        $stmt->bind_param($tipe, ...$val);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_jadwal(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(SQL_JADWAL . ' WHERE s.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/** Daftar kota untuk datalist pencarian */
function daftar_kota(mysqli $conn): array
{
    $res = $conn->query('SELECT kota_asal AS kota FROM schedules UNION SELECT kota_tujuan FROM schedules ORDER BY kota');
    return array_column($res->fetch_all(MYSQLI_ASSOC), 'kota');
}

/** Nomor kursi yang sudah dipesan pada suatu jadwal */
function kursi_terisi(mysqli $conn, int $schedule_id): array
{
    $stmt = $conn->prepare('SELECT nomor_kursi FROM bookings WHERE schedule_id = ?');
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    return array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'nomor_kursi');
}