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

  /* ---------- Pilih kursi ---------- */
  const formKursi = document.getElementById("form-kursi");
  if (formKursi) {
    const input = document.getElementById("input-kursi");
    const teks = document.getElementById("teks-kursi");
    const tombol = document.getElementById("btn-lanjut");
    const seats = formKursi.querySelectorAll(".seat:not(:disabled)");

    seats.forEach((seat) => {
      seat.addEventListener("click", () => {
        const sudahAktif = seat.classList.contains("dipilih");
        seats.forEach((s) => s.classList.remove("dipilih"));

        if (sudahAktif) {
          input.value = "";
          teks.textContent = "Belum dipilih";
          tombol.disabled = true;
        } else {
          seat.classList.add("dipilih");
          input.value = seat.dataset.no;
          teks.textContent = "Kursi " + seat.dataset.no;
          tombol.disabled = false;
        }
      });
    });
  }

  /* ---------- Metode pembayaran: tampilkan info rekening ---------- */
  const teksRek = document.getElementById("teks-rek");
  document.querySelectorAll('input[name="metode"]').forEach((radio) => {
    radio.addEventListener("change", () => {
      if (teksRek) teksRek.textContent = radio.dataset.rek;
    });
  });

  /* ---------- Pratinjau bukti pembayaran ---------- */
  const inputBukti = document.getElementById("input-bukti");
  const previewBukti = document.getElementById("preview-bukti");
  if (inputBukti && previewBukti) {
    inputBukti.addEventListener("change", () => {
      const file = inputBukti.files[0];
      if (!file) {
        previewBukti.classList.add("d-none");
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        alert("Ukuran bukti maksimal 2 MB.");
        inputBukti.value = "";
        previewBukti.classList.add("d-none");
        return;
      }
      previewBukti.src = URL.createObjectURL(file);
      previewBukti.classList.remove("d-none");
    });
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