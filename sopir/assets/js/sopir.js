/* ==========================================================
 FILE         : sopir/assets/js/sopir.js
 FUNGSI       : Script khusus halaman sopir: pencarian di manifest, serta halaman Scan
                (kamera QR, input manual / alat scanner USB, kartu hasil, progres, riwayat).
 DIPANGGIL DARI: partials/script.php
 CATATAN      : Tiap blok hanya jalan jika elemennya ada di halaman. Kamera memakai library
                html5-qrcode (dimuat di partials/script.php hanya untuk halaman Scan).
                Teks dari server selalu dimasukkan lewat textContent (aman dari XSS).
 ========================================================== */
(function () {
  "use strict";

  /* ---------- Manifest: kotak cari (nama, kursi, kode tiket) ---------- */
  const cari = document.getElementById("cari-manifest");
  if (cari) {
    const baris = document.querySelectorAll(".tabel-manifest tbody tr[data-cari]");
    const kosong = document.getElementById("cari-kosong");
    cari.addEventListener("input", function () {
      const kata = cari.value.trim().toLowerCase();
      let tampil = 0;
      baris.forEach(function (tr) {
        const cocok = kata === "" || tr.dataset.cari.toLowerCase().indexOf(kata) !== -1;
        tr.hidden = !cocok;
        if (cocok) { tampil++; }
      });
      if (kosong) { kosong.hidden = tampil > 0; }
    });
  }

  /* ---------- Halaman Scan ---------- */
  const app = document.getElementById("scan-app");
  if (!app) { return; }

  const endpoint = app.dataset.endpoint;
  const csrf = app.dataset.csrf;
  const jadwal = app.dataset.jadwal;

  const hasilEl = document.getElementById("hasil-scan");
  const progresEl = document.getElementById("progres-scan");
  const riwayatEl = document.getElementById("riwayat-list");
  const riwayatKosong = document.getElementById("riwayat-kosong");
  const formManual = document.getElementById("form-manual");
  const inputManual = document.getElementById("kode-manual");
  const readerEl = document.getElementById("reader");
  const infoEl = document.getElementById("kamera-info");
  const btnKamera = document.getElementById("btn-kamera");
  const btnStop = document.getElementById("btn-stop");

  const KELAS = { success: "ok", warning: "warn", danger: "err" };
  const IKON = { success: "bi-check-circle-fill", warning: "bi-exclamation-triangle-fill", danger: "bi-x-octagon-fill" };

  function kosongkan(el) {
    while (el.firstChild) { el.removeChild(el.firstChild); }
  }

  /* Bunyi + getar singkat supaya sopir tahu hasilnya tanpa melihat layar */
  let konteksAudio = null;
  function bunyi(tone) {
    try {
      const AC = window.AudioContext || window.webkitAudioContext;
      if (AC) {
        konteksAudio = konteksAudio || new AC();
        const osc = konteksAudio.createOscillator();
        const gain = konteksAudio.createGain();
        osc.connect(gain);
        gain.connect(konteksAudio.destination);
        osc.frequency.value = tone === "success" ? 880 : (tone === "warning" ? 520 : 220);
        gain.gain.value = 0.08;
        osc.start();
        osc.stop(konteksAudio.currentTime + (tone === "success" ? 0.12 : 0.3));
      }
      if (navigator.vibrate) { navigator.vibrate(tone === "success" ? 80 : [120, 60, 120]); }
    } catch (e) { /* bunyi hanya pelengkap */ }
  }

  /* Kartu hasil (hijau / kuning / merah) */
  function tampilkanHasil(d) {
    const tone = d.tone || "danger";
    hasilEl.className = "hasil-scan " + (KELAS[tone] || "err");

    const ikon = hasilEl.querySelector(".ikon");
    kosongkan(ikon);
    const i = document.createElement("i");
    i.className = "bi " + (IKON[tone] || IKON.danger);
    ikon.appendChild(i);

    hasilEl.querySelector(".judul").textContent = d.judul || "";
    hasilEl.querySelector(".pesan").textContent = d.pesan || "";

    const rincian = hasilEl.querySelector(".rincian");
    kosongkan(rincian);
    const p = d.penumpang;
    if (p) {
      const baris = [
        ["Penumpang", p.nama],
        ["Kursi", p.kursi],
        ["Pemesan", p.pemesan],
        ["Rute", p.rute],
        ["Berangkat", p.berangkat],
        ["Bus", p.bus],
        ["Kode tiket", p.kode_tiket]
      ];
      if (p.waktu_naik) { baris.push(["Naik pukul", p.waktu_naik]); }
      baris.forEach(function (b) {
        if (!b[1]) { return; }
        const dt = document.createElement("dt");
        dt.textContent = b[0];
        const dd = document.createElement("dd");
        dd.textContent = b[1];
        rincian.appendChild(dt);
        rincian.appendChild(dd);
      });
      rincian.hidden = false;
    } else {
      rincian.hidden = true;
    }
    bunyi(tone);
  }

  /* "x / y penumpang naik" ikut berubah tanpa reload */
  function perbaruiProgres(h) {
    if (!h || !progresEl) { return; }
    const persen = h.total > 0 ? Math.round(h.naik / h.total * 100) : 0;
    progresEl.querySelector(".js-naik").textContent = h.naik;
    progresEl.querySelector(".js-total").textContent = h.total;
    const bar = progresEl.querySelector(".js-bar");
    bar.style.width = persen + "%";
    bar.classList.toggle("bg-success", h.total > 0 && h.naik >= h.total);
  }

  /* Tambah penumpang yang baru naik ke daftar "Baru saja naik" */
  function tambahRiwayat(p) {
    if (!p || !riwayatEl) { return; }
    const li = document.createElement("li");
    const kiri = document.createElement("span");
    const nama = document.createElement("strong");
    nama.textContent = p.nama;
    const kursi = document.createElement("small");
    kursi.className = "text-muted";
    kursi.textContent = " · Kursi " + p.kursi;
    kiri.appendChild(nama);
    kiri.appendChild(kursi);
    const waktu = document.createElement("small");
    waktu.className = "text-muted";
    waktu.textContent = p.waktu_naik;
    li.appendChild(kiri);
    li.appendChild(waktu);

    riwayatEl.insertBefore(li, riwayatEl.firstChild);
    if (riwayatKosong) { riwayatKosong.hidden = true; }
    const item = riwayatEl.querySelectorAll("li:not(#riwayat-kosong)");
    for (let n = item.length - 1; n >= 8; n--) { riwayatEl.removeChild(item[n]); }
  }

  /* Kirim kode ke server (sopir/index.php?page=scan&aksi=validasi) */
  let sibuk = false;
  async function kirimKode(kode) {
    kode = (kode || "").trim();
    if (!kode || sibuk) { return; }
    sibuk = true;
    try {
      const fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("jadwal", jadwal);
      fd.append("kode", kode);
      const res = await fetch(endpoint, {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: { "X-Requested-With": "fetch" }
      });
      const data = await res.json();
      tampilkanHasil(data);
      perbaruiProgres(data.hitung);
      if (data.ok) { tambahRiwayat(data.penumpang); }
    } catch (err) {
      tampilkanHasil({
        tone: "danger",
        judul: "Gagal memproses",
        pesan: "Sesi mungkin berakhir atau server tidak terjangkau. Muat ulang halaman lalu coba lagi.",
        penumpang: null
      });
    } finally {
      sibuk = false;
    }
  }

  /* Input manual / alat scanner USB (alat scanner mengetik kode lalu menekan Enter) */
  formManual.addEventListener("submit", async function (ev) {
    ev.preventDefault();
    await kirimKode(inputManual.value);
    inputManual.value = "";
    inputManual.focus();
  });

  /* Kamera */
  let pemindai = null;
  let kodeTerakhir = "";
  let waktuTerakhir = 0;
  const teksInfo = infoEl.querySelector("div");

  function tampilInfo(teks) {
    readerEl.hidden = true;
    infoEl.hidden = false;
    teksInfo.textContent = teks;
  }

  btnKamera.addEventListener("click", async function () {
    if (typeof Html5Qrcode === "undefined") {
      tampilInfo("Library kamera gagal dimuat (perlu internet). Gunakan input manual di bawah.");
      return;
    }
    try {
      pemindai = pemindai || new Html5Qrcode("reader");
      readerEl.hidden = false;
      infoEl.hidden = true;
      await pemindai.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 240, height: 240 } },
        function (teks) {
          // QR yang sama terbaca berkali-kali selama masih di depan kamera: abaikan 3 detik
          const sekarang = Date.now();
          if (teks === kodeTerakhir && sekarang - waktuTerakhir < 3000) { return; }
          kodeTerakhir = teks;
          waktuTerakhir = sekarang;
          kirimKode(teks);
        },
        function () { /* gagal membaca satu frame: normal, abaikan */ }
      );
      btnKamera.hidden = true;
      btnStop.hidden = false;
    } catch (err) {
      const alasan = err && err.message ? err.message : String(err);
      tampilInfo("Kamera tidak bisa dibuka: " + alasan + ". Gunakan input manual di bawah.");
    }
  });

  btnStop.addEventListener("click", async function () {
    try {
      if (pemindai) { await pemindai.stop(); }
    } catch (err) { /* sudah berhenti */ }
    tampilInfo("Kamera belum aktif");
    btnStop.hidden = true;
    btnKamera.hidden = false;
    inputManual.focus();
  });
})();