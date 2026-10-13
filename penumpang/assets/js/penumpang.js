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

  /* ---------- Pilih kursi (jendela denah bus) ---------- */
  const formKursi = document.getElementById("form-kursi");
  if (formKursi) {
    const input = document.getElementById("input-kursi");          // nilai yang dikirim ke server
    const tombolLanjut = document.getElementById("btn-lanjut");
    const hint = document.getElementById("hint-lanjut");
    const teksKursi = document.getElementById("teks-kursi");        // ringkasan harga (kanan)
    const statusKursi = document.getElementById("status-kursi");    // baris "Nomor Kursi" (kiri)
    const angkaKursi = document.getElementById("angka-kursi");
    const barisKursi = document.getElementById("btn-buka-kursi");
    const teksModal = document.getElementById("modal-kursi-teks");
    const tombolOk = document.getElementById("btn-konfirmasi-kursi");
    const modalEl = document.getElementById("modal-kursi");
    const seats = formKursi.querySelectorAll(".seat:not(:disabled)");

    let terpilih = "";     // pilihan di dalam jendela (belum tentu disimpan)
    let tersimpan = "";    // pilihan yang sudah dikonfirmasi lewat tombol "Lanjutkan"

    function tampilkanPilihan(no) {
      seats.forEach((s) => s.classList.toggle("dipilih", s.dataset.no === no));
      teksModal.textContent = no ? "Kursi " + no : "Belum pilih kursi";
      tombolOk.disabled = !no;
    }

    seats.forEach((seat) => {
      seat.addEventListener("click", () => {
        terpilih = terpilih === seat.dataset.no ? "" : seat.dataset.no;
        tampilkanPilihan(terpilih);
      });
    });

    tombolOk.addEventListener("click", () => {
      if (!terpilih) return;
      tersimpan = terpilih;
      input.value = tersimpan;
      teksKursi.textContent = "Kursi " + tersimpan;
      statusKursi.textContent = "Kursi telah dipilih. Ketuk untuk meninjau atau mengubah.";
      angkaKursi.textContent = tersimpan;
      angkaKursi.hidden = false;
      barisKursi.classList.add("terpilih");
      tombolLanjut.disabled = false;
      if (hint) hint.textContent = "Siap! Lanjut ke pembayaran.";
      if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });

    // Tutup jendela tanpa konfirmasi -> kembalikan tampilan ke pilihan yang sudah tersimpan
    modalEl.addEventListener("hidden.bs.modal", () => {
      terpilih = tersimpan;
      tampilkanPilihan(tersimpan);
    });
  }

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