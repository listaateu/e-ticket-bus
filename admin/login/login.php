// admin/assets/js/login.js — tombol lihat/sembunyikan password & cegah klik ganda di halaman login admin
document.addEventListener('DOMContentLoaded', function () {
  var tombol = document.getElementById('togglePassword');
  var isian  = document.getElementById('password');
  var form   = document.getElementById('formLogin');

  if (tombol && isian) {
    tombol.addEventListener('click', function () {
      var tampil = isian.type === 'password';
      isian.type = tampil ? 'text' : 'password';
      tombol.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
      var ikon = tombol.querySelector('i');
      if (ikon) { ikon.className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye'; }
    });
  }

  if (form) {
    form.addEventListener('submit', function () {
      var kirim = document.getElementById('tombolMasuk');
      if (kirim) { kirim.disabled = true; kirim.textContent = 'Memeriksa...'; }
    });
  }
});