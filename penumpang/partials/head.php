<?php
/**
 * ============================================================
 * FILE         : penumpang/partials/head.php
 * FUNGSI       : Bagian <head>: meta, CSS template, CSS penumpang, CSS Traveloka, dan pembuka <body>.
 * DIPANGGIL DARI: index.php
 * CATATAN      : Judul tab dari $judul_halaman.
 * ============================================================
 */
/** @var string $judul_halaman Judul halaman, diisi di index.php */
/** @var string $page Nama halaman aktif, diisi di index.php */
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= e($judul_halaman) ?> - E-Ticket Bus</title>
  <meta name="description" content="Pesan tiket bus interkota online dengan mudah">
  <meta name="theme-color" content="#F47FAA">

  <!-- Favicons -->
  <link href="assets/img/favicon.svg?v=2" rel="icon" type="image/svg+xml">
  <link href="assets/img/favicon.png?v=2" rel="icon" type="image/png" sizes="32x32">
  <link href="assets/img/apple-touch-icon.png?v=2" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS (template) + CSS khusus penumpang + CSS gaya Traveloka (paling akhir) -->
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/penumpang.css" rel="stylesheet">
  <link href="assets/css/traveloka.css" rel="stylesheet">
</head>

<body class="<?= e($page) ?>-page">