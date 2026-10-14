/* ==========================================================
 FILE         : penumpang/assets/js/penumpang.js
 FUNGSI       : Script khusus: pilih kursi, info rekening, pratinjau bukti, QR code.
 DIPANGGIL DARI: partials/script.php
 ========================================================== */
/**
 * JS khusus halaman penumpang.
 * Tiap blok hanya jalan jika elemennya ada di halaman.
 */
(function () {
  "use strict";

  const rupiah = (n) => "Rp " + Number(n).toLocaleString("id-ID");

  /* ---------- Pilih kursi (jendela denah bus) ----------
     Bisa memilih beberapa kursi: sebanyak penumpang dewasa (data-dewasa di <form>).
     Kursi pertama yang diklik untuk penumpang 1, kedua untuk penumpang 2, dst.
     Mengklik kursi yang sudah dipilih akan membatalkannya. */
  const formKursi = document.getElementById("form-kursi");
  if (formKursi) {
    const butuh = parseInt(formKursi.dataset.dewasa || "1", 10);      // jumlah kursi yang harus dipilih
    const input = document.getElementById("input-kursi");             // nilai yang dikirim ke server: "5,6,7"
    const tombolLanjut = document.getElementById("btn-lanjut");
    const hint = document.getElementById("hint-lanjut");
    const teksKursi = document.getElementById("teks-kursi");          // ringkasan harga (kanan)
    const statusKursi = document.getElementById("status-kursi");      // baris "Nomor Kursi" (kiri)
    const angkaKursi = document.getElementById("angka-kursi");
    const barisKursi = document.getElementById("btn-buka-kursi");
    const teksModal = document.getElementById("modal-kursi-teks");
    const tombolOk = document.getElementById("btn-konfirmasi-kursi");
    const modalEl = document.getElementById("modal-kursi");
    const seats = formKursi.querySelectorAll(".seat:not(:disabled)");
    const slots = formKursi.querySelectorAll("[data-slot]");           // kotak "Penumpang 1, 2, ..." di jendela

    let terpilih = [];    // pilihan di dalam jendela (belum tentu disimpan)
    let tersimpan = [];   // pilihan yang sudah dikonfirmasi lewat tombol "Lanjutkan"

    // Nama penumpang di jendela mengikuti isian di formulir
    function segarkanNama() {
      slots.forEach((slot, i) => {
        const kolom = document.getElementById("nama-dewasa-" + i);
        const nama = kolom && kolom.value.trim() ? kolom.value.trim() : "Penumpang " + (i + 1);
        slot.querySelector(".slot-nama").textContent = nama;
      });
    }

    function tampilkanPilihan() {
      seats.forEach((s) => s.classList.toggle("dipilih", terpilih.includes(s.dataset.no)));
      slots.forEach((slot, i) => {
        const no = terpilih[i];
        slot.querySelector(".slot-kursi").textContent = no ? "Kursi " + no : "Belum pilih kursi";
        slot.classList.toggle("aktif", i === Math.min(terpilih.length, butuh - 1));
      });
      teksModal.textContent = terpilih.length < butuh
        ? "Dipilih " + terpilih.length + " dari " + butuh + " kursi."
        : "Semua kursi sudah dipilih.";
      tombolOk.disabled = terpilih.length !== butuh;
    }

    seats.forEach((seat) => {
      seat.addEventListener("click", () => {
        const no = seat.dataset.no;
        const idx = terpilih.indexOf(no);
        if (idx >= 0) {
          terpilih.splice(idx, 1);                 // klik lagi = batalkan
        } else if (terpilih.length < butuh) {
          terpilih.push(no);
        } else if (butuh === 1) {
          terpilih = [no];                         // satu penumpang: klik kursi lain = pindah
        } else {
          teksModal.textContent = "Sudah " + butuh + " kursi. Klik kursi terpilih untuk membatalkannya.";
          return;
        }
        tampilkanPilihan();
      });
    });

    tombolOk.addEventListener("click", () => {
      if (terpilih.length !== butuh) return;
      tersimpan = terpilih.slice();
      const daftar = tersimpan.join(", ");
      input.value = tersimpan.join(",");
      teksKursi.textContent = "Kursi " + daftar;
      statusKursi.textContent = "Kursi telah dipilih. Ketuk untuk meninjau atau mengubah.";
      angkaKursi.textContent = daftar;
      angkaKursi.hidden = false;
      barisKursi.classList.add("terpilih");
      tombolLanjut.disabled = false;
      if (hint) hint.textContent = "Siap! Lanjut ke pembayaran.";
      if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });

    modalEl.addEventListener("show.bs.modal", segarkanNama);

    // Tutup jendela tanpa konfirmasi -> kembalikan tampilan ke pilihan yang sudah tersimpan
    modalEl.addEventListener("hidden.bs.modal", () => {
      terpilih = tersimpan.slice();
      tampilkanPilihan();
    });

    tampilkanPilihan();
  }

  /* ---------- Kartu "Jumlah Penumpang": bayi tidak boleh lebih banyak dari dewasa ---------- */
  const pilihDewasa = document.getElementById("pilih-dewasa");
  const pilihBayi = document.getElementById("pilih-bayi");
  if (pilihDewasa && pilihBayi) {
    const sesuaikanBayi = () => {
      const dewasa = parseInt(pilihDewasa.value, 10);
      Array.from(pilihBayi.options).forEach((o) => { o.disabled = parseInt(o.value, 10) > dewasa; });
      if (parseInt(pilihBayi.value, 10) > dewasa) pilihBayi.value = String(dewasa);
    };
    pilihDewasa.addEventListener("change", sesuaikanBayi);
    sesuaikanBayi();
  }

  /* ---------- Form cari: kotak "Penumpang" (dewasa & bayi) ---------- */
  document.querySelectorAll(".tv-pax-pick").forEach((kotak) => {
    const maksDewasa = parseInt(kotak.dataset.maksDewasa || "4", 10);
    const tombol = kotak.querySelector(".tv-pax-btn");
    const pop = kotak.querySelector(".tv-pax-pop");
    const inDewasa = kotak.querySelector(".tv-in-dewasa");
    const inBayi = kotak.querySelector(".tv-in-bayi");
    const teks = kotak.querySelector(".tv-pax-teks");
    const nDewasa = kotak.querySelector('[data-n="dewasa"]');
    const nBayi = kotak.querySelector('[data-n="bayi"]');

    const buka = (ya) => {
      pop.hidden = !ya;
      tombol.setAttribute("aria-expanded", String(ya));
    };
    const tampil = () => {
      const d = parseInt(inDewasa.value, 10);
      const b = parseInt(inBayi.value, 10);
      nDewasa.textContent = d;
      nBayi.textContent = b;
      teks.textContent = d + " Dewasa" + (b > 0 ? ", " + b + " Bayi" : "");
      kotak.querySelector('[data-aksi="kurang"][data-jenis="dewasa"]').disabled = d <= 1;
      kotak.querySelector('[data-aksi="tambah"][data-jenis="dewasa"]').disabled = d >= maksDewasa;
      kotak.querySelector('[data-aksi="kurang"][data-jenis="bayi"]').disabled = b <= 0;
      kotak.querySelector('[data-aksi="tambah"][data-jenis="bayi"]').disabled = b >= d;
    };

    tombol.addEventListener("click", () => buka(pop.hidden));
    kotak.querySelector(".tv-pax-ok").addEventListener("click", () => buka(false));

    kotak.querySelectorAll(".tv-step button").forEach((btn) => {
      btn.addEventListener("click", () => {
        let d = parseInt(inDewasa.value, 10);
        let b = parseInt(inBayi.value, 10);
        const delta = btn.dataset.aksi === "tambah" ? 1 : -1;
        if (btn.dataset.jenis === "dewasa") {
          d = Math.max(1, Math.min(maksDewasa, d + delta));
          if (b > d) b = d;                       // bayi maksimal sebanyak dewasa
        } else {
          b = Math.max(0, Math.min(d, b + delta));
        }
        inDewasa.value = d;
        inBayi.value = b;
        tampil();
      });
    });

    // Klik di luar kotak = tutup; tombol Esc = tutup
    document.addEventListener("click", (ev) => { if (!kotak.contains(ev.target)) buka(false); });
    document.addEventListener("keydown", (ev) => { if (ev.key === "Escape") buka(false); });

    tampil();
  });

  /* ---------- Metode pembayaran: gulir ke panel yang baru terbuka ---------- */
  document.querySelectorAll('input[name="metode"]').forEach((radio) => {
    radio.addEventListener("change", () => {
      const kartu = radio.closest(".tv-metode");
      if (kartu && window.matchMedia("(max-width: 767px)").matches) {
        setTimeout(() => kartu.scrollIntoView({ behavior: "smooth", block: "nearest" }), 120);
      }
    });
  });

  /* ---------- Tombol "Salin" (rekening & nominal) ---------- */
  function toastKecil(teks) {
    const t = document.createElement("div");
    t.className = "tv-toast";
    t.textContent = teks;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add("tampil"));
    setTimeout(() => {
      t.classList.remove("tampil");
      setTimeout(() => t.remove(), 250);
    }, 1600);
  }

  // Cara cadangan untuk browser yang menolak Clipboard API (mis. halaman http di jaringan lokal)
  function salinCadangan(isi) {
    const ta = document.createElement("textarea");
    ta.value = isi;
    ta.setAttribute("readonly", "");
    ta.style.cssText = "position:fixed;top:0;left:0;opacity:0";
    document.body.appendChild(ta);
    ta.select();
    let ok = false;
    try { ok = document.execCommand("copy"); } catch (e) { ok = false; }
    ta.remove();
    return ok;
  }

  document.querySelectorAll(".tv-salin").forEach((tombol) => {
    tombol.addEventListener("click", (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      const isi = tombol.dataset.salin || "";
      const sukses = () => toastKecil("Tersalin: " + isi);
      const gagal = () => toastKecil("Gagal menyalin, salin manual ya.");

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(isi).then(sukses, () => (salinCadangan(isi) ? sukses() : gagal()));
      } else {
        salinCadangan(isi) ? sukses() : gagal();
      }
    });
  });

  /* ---------- Rincian Harga Total (buka / tutup) ---------- */
  const btnRincian = document.getElementById("btn-rincian");
  const boxRincian = document.getElementById("rincian-total");
  if (btnRincian && boxRincian) {
    btnRincian.addEventListener("click", () => {
      const buka = boxRincian.hidden;
      boxRincian.hidden = !buka;
      btnRincian.setAttribute("aria-expanded", String(buka));
      btnRincian.classList.toggle("buka", buka);
    });
  }

  /* ---------- Pratinjau bukti pembayaran ---------- */
  const inputBukti = document.getElementById("input-bukti");
  const previewBukti = document.getElementById("preview-bukti");
  if (inputBukti && previewBukti) {
    const kosongBukti = document.getElementById("drop-kosong");
    const namaBukti = document.getElementById("nama-bukti");
    const areaDrop = document.getElementById("area-drop");

    const reset = () => {
      previewBukti.classList.add("d-none");
      if (namaBukti) namaBukti.classList.add("d-none");
      if (kosongBukti) kosongBukti.classList.remove("d-none");
      if (areaDrop) areaDrop.classList.remove("terisi");
    };

    inputBukti.addEventListener("change", () => {
      const file = inputBukti.files[0];
      if (!file) {
        reset();
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        alert("Ukuran bukti maksimal 2 MB.");
        inputBukti.value = "";
        reset();
        return;
      }
      previewBukti.src = URL.createObjectURL(file);
      previewBukti.classList.remove("d-none");
      if (kosongBukti) kosongBukti.classList.add("d-none");
      if (namaBukti) {
        namaBukti.textContent = file.name + " · klik untuk ganti";
        namaBukti.classList.remove("d-none");
      }
      if (areaDrop) areaDrop.classList.add("terisi");
    });

    if (areaDrop) {
      ["dragenter", "dragover"].forEach((n) =>
        areaDrop.addEventListener(n, (e) => { e.preventDefault(); areaDrop.classList.add("melayang"); })
      );
      ["dragleave", "drop"].forEach((n) =>
        areaDrop.addEventListener(n, () => areaDrop.classList.remove("melayang"))
      );
    }
  }

  /* ---------- QR Code e-ticket ---------- */
  const qr = document.getElementById("qrcode");
  if (qr && typeof QRCode !== "undefined") {
    new QRCode(qr, {
      text: qr.dataset.kode,
      width: 180,
      height: 180,
      correctLevel: QRCode.CorrectLevel.M,
    });
  }
})();