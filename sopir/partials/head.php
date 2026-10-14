<?php
/**
 * ============================================================
 * FILE         : sopir/partials/head.php
 * FUNGSI       : Bagian <head>: meta, CSS template, CSS sopir, dan pembuka <body>.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : Judul tab dari $judul_halaman.
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= e($judul_halaman) ?> - Sopir E-Ticket Bus</title>
  <meta name="description" content="Halaman sopir/kondektur: manifest penumpang dan scan tiket">
  <meta name="theme-color" content="#0194f3">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

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

  <!-- Main CSS (template) + CSS khusus sopir (paling akhir) -->
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/sopir.css" rel="stylesheet">
</head>

<body class="<?= e($page) ?>-page">