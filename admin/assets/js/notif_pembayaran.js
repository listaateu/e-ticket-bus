/**
 * admin/assets/js/notif_pembayaran.js
 * Notifikasi pembayaran masuk, tanpa perlu refresh:
 *  - tanya server tiap 5 detik (berhenti saat tab disembunyikan, langsung cek lagi saat tab dibuka)
 *  - angka merah di menu "Verifikasi Pembayaran" + titik di ikon lonceng + daftar di lonceng
 *  - muncul pesan kecil di pojok kanan atas saat ada pembayaran BARU
 *  - di halaman daftar Verifikasi Pembayaran / Data Pemesanan, tabel dimuat ulang otomatis
 */
(function () {
  'use strict';

  var ALAMAT   = (window.BASE_URL || '') + '/admin/function/notif_pembayaran.php';
  var KUNCI    = 'notif_pembayaran_ids';        // id yang sudah dilihat (disimpan per tab browser)
  var JEDA     = 5000;
  var judulAsli = document.title;

  // ---------- gaya ----------
  var gaya = document.createElement('style');
  gaya.textContent =
    '.notif-count{margin-left:auto;min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:#ef4444;color:#fff;font-size:.72rem;font-weight:700;display:none;align-items:center;justify-content:center}' +
    '.notif-toast-area{position:fixed;top:80px;right:20px;z-index:2000;display:flex;flex-direction:column;gap:10px;max-width:340px}' +
    '.notif-toast{background:#fff;border-left:4px solid #16a34a;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.18);padding:12px 14px;font-size:.88rem;animation:notifMasuk .25s ease}' +
    '.notif-toast strong{display:block;margin-bottom:2px}.notif-toast a{font-weight:600;text-decoration:none}' +
    '.notif-kosong{padding:22px 16px;text-align:center;color:#6b7280;font-size:.88rem}' +
    '@keyframes notifMasuk{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:none}}';
  document.head.appendChild(gaya);

  var area = document.createElement('div');
  area.className = 'notif-toast-area';
  document.body.appendChild(area);

  // ---------- bantuan ----------
  function esc(t) {
    var d = document.createElement('div');
    d.textContent = t == null ? '' : String(t);
    return d.innerHTML;
  }
  function rp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

  function baca() {
    try { var v = sessionStorage.getItem(KUNCI); return v === null ? null : JSON.parse(v); }
    catch (e) { return null; }
  }
  function simpan(ids) {
    try { sessionStorage.setItem(KUNCI, JSON.stringify(ids)); } catch (e) {}
  }

  function bunyi() {
    try {
      var C = window.AudioContext || window.webkitAudioContext;
      var ctx = new C(), o = ctx.createOscillator(), g = ctx.createGain();
      o.type = 'sine'; o.frequency.value = 880;
      g.gain.setValueAtTime(0.0001, ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + 0.02);
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
      o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + 0.4);
    } catch (e) { /* browser memblokir suara sebelum ada klik: abaikan */ }
  }

  function toast(judul, isi, href) {
    var t = document.createElement('div');
    t.className = 'notif-toast';
    t.innerHTML = '<strong>' + esc(judul) + '</strong>' + isi +
      (href ? '<div class="mt-1"><a href="' + href + '">Periksa sekarang &rarr;</a></div>' : '');
    area.appendChild(t);
    while (area.children.length > 3) { area.removeChild(area.firstChild); }
    setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 9000);
  }

  // ---------- tampilan ----------
  function gambar(data) {
    var n = data.jumlah;

    // angka di menu samping
    var badge = document.getElementById('badge-verifikasi');
    if (badge) {
      badge.textContent = n > 99 ? '99+' : n;
      badge.style.display = n > 0 ? 'inline-flex' : 'none';
    }
    // titik di lonceng
    var titik = document.getElementById('notif-titik');
    if (titik) titik.style.display = n > 0 ? '' : 'none';
    // judul tab
    document.title = n > 0 ? '(' + n + ') ' + judulAsli : judulAsli;

    // judul & isi daftar di lonceng
    var jd = document.getElementById('notif-judul');
    if (jd) jd.textContent = n > 0 ? n + ' pembayaran menunggu' : 'Notifikasi';
    var lst = document.getElementById('notif-daftar');
    if (lst) {
      if (!data.daftar.length) {
        lst.innerHTML = '<div class="notif-kosong"><i class="bi bi-check-circle fs-4 d-block mb-1"></i>Tidak ada pembayaran yang menunggu.</div>';
      } else {
        lst.innerHTML = data.daftar.map(function (p) {
          return '<a href="' + (window.BASE_URL || '') + '/admin/index.php?page=verifikasi_pembayaran&aksi=detail&id=' + p.id + '" class="notification-item">' +
            '<div class="notification-icon bg-warning text-dark"><i class="bi bi-cash-coin"></i></div>' +
            '<div class="notification-content">' +
              '<p class="notification-text"><strong>' + esc(p.nama) + '</strong> mengirim bukti ' + esc(p.metode) + '</p>' +
              '<span class="notification-time">' + esc(p.kode) + ' &middot; ' + esc(p.rute) + ' &middot; ' + rp(p.total) + '</span>' +
            '</div><span class="notification-unread-dot"></span></a>';
        }).join('');
      }
    }
  }

  // Halaman yang tabelnya perlu dimuat ulang otomatis (bukan halaman detail / form)
  function halamanDaftar() {
    var q = new URLSearchParams(location.search);
    return !q.get('aksi') && (q.get('page') === 'verifikasi_pembayaran' || q.get('page') === 'data_pemesanan');
  }
  function sedangMengetik() {
    var a = document.activeElement;
    return a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName) && a.type !== 'hidden';
  }

  // ---------- inti ----------
  var berjalan = false;
  function cek() {
    if (berjalan || document.hidden) return;
    berjalan = true;

    fetch(ALAMAT, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || data.error) return;
        gambar(data);

        var lama = baca();
        simpan(data.ids);

        if (lama === null) {
          // Pertama kali admin membuka halaman di tab ini: ingatkan sekali kalau ada yang menunggu
          if (data.jumlah > 0) {
            toast(data.jumlah + ' pembayaran menunggu verifikasi', 'Segera periksa bukti transfer penumpang.',
                  (window.BASE_URL || '') + '/admin/index.php?page=verifikasi_pembayaran&status=pending');
          }
          return;
        }

        var baru = data.daftar.filter(function (p) { return lama.indexOf(p.id) === -1; });
        if (!baru.length) return;

        baru.slice().reverse().forEach(function (p) {
          toast('Pembayaran baru masuk', esc(p.nama) + ' &middot; ' + esc(p.rute) + '<br>' + rp(p.total) + ' via ' + esc(p.metode),
                (window.BASE_URL || '') + '/admin/index.php?page=verifikasi_pembayaran&aksi=detail&id=' + p.id);
        });
        bunyi();

        if (halamanDaftar() && !sedangMengetik()) {
          setTimeout(function () { if (!sedangMengetik()) location.reload(); }, 2500);
        }
      })
      .catch(function () { /* koneksi putus sebentar: coba lagi di putaran berikutnya */ })
      .then(function () { berjalan = false; });
  }

  cek();
  setInterval(cek, JEDA);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) cek(); });
})();