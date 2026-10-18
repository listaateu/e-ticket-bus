<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/components/pencarian.php
// Bantuan pencarian, dipakai oleh semua halaman daftar (bus, jadwal, user, sopir, pembayaran, pemesanan).
// Kotak cari di topbar mengirim ?q=... ke halaman yang sedang dibuka, lalu tiap halaman memakai
// fungsi di bawah ini supaya cara kerjanya SAMA di mana-mana.

/** Teks pencarian dari alamat (?q=). Hanya teks biasa, maksimal 100 karakter. */
if (!function_exists('param_cari')) {
    function param_cari(): string
    {
        return param_str('q', '', 'GET', 100);
    }
}

/**
 * Susun syarat WHERE untuk pencarian. Hasilnya potongan SQL " AND (...) AND (...)" (kosong kalau tidak mencari).
 *  - Teks dipecah per kata (maks. 6 kata). SETIAP kata harus cocok dengan salah satu kolom,
 *    jadi mengetik "bandung jakarta" atau "budi lunas" tetap ketemu.
 *  - Huruf besar/kecil tidak berpengaruh.
 *  - Angka berformat ribuan ("110.000") dicocokkan sebagai 110000 (sama seperti yang tersimpan di database).
 *  - Karakter % dan _ yang diketik dianggap huruf biasa.
 *  - Nilainya selalu dikirim lewat bind_param (tipe & isi ditambahkan ke $tipe dan $param), bukan disambung ke SQL.
 * $kolom = daftar kolom/ekspresi SQL yang BUKAN dari input user (ditulis langsung di kode).
 */
if (!function_exists('syarat_cari')) {
    function syarat_cari(array $kolom, string $cari, string &$tipe, array &$param): string
    {
        $kata = preg_split('/\s+/u', trim($cari), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $kata = array_slice($kata, 0, 6);
        if (!$kolom || !$kata) {
            return '';
        }
        $sql = '';
        foreach ($kata as $k) {
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $k)) {
                $k = str_replace('.', '', $k);      // "110.434" -> "110434"
            }
            $like = '%' . addcslashes($k, '%_\\') . '%';
            $sql .= ' AND (' . implode(' OR ', array_map(function ($c) { return "$c LIKE ?"; }, $kolom)) . ')';
            foreach ($kolom as $_) {
                $tipe .= 's';
                $param[] = $like;
            }
        }
        return $sql;
    }
}

/** Kotak info "Hasil pencarian untuk ..." + tombol hapus pencarian. Tidak tampil kalau tidak sedang mencari. */
if (!function_exists('render_info_cari')) {
    function render_info_cari(string $cari, int $total, string $url_reset): void
    {
        if ($cari === '') {
            return;
        }
        ?>
        <div class="px-3 pb-3 text-muted small">
          Hasil pencarian untuk <strong>&ldquo;<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>&rdquo;</strong>:
          <?= (int) $total ?> data ditemukan.
          <a href="<?= htmlspecialchars($url_reset, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">Hapus pencarian</a>
        </div>
        <?php
    }
}