<?php defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php ?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - E-Ticket Bus</title>

  <!-- SEO Optimization -->
  <meta name="description" content="Dashboard admin E-Ticket Bus">
  <meta name="author" content="E-Ticket Bus">

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/admin/assets/images/favicon-admin.svg?v=2">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>/admin/assets/images/favicon-admin.png?v=2">
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/admin/assets/images/apple-touch-icon-admin.png?v=2">

  <!-- Local Third-Party Libraries (100% Offline Compatible) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/apexcharts/apexcharts.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/flatpickr/flatpickr.min.css">

  <!-- Main Design System & Custom Stylesheet -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/main.css">

  <style>
  /* sembunyikan panah atas-bawah di input angka */
  input[type=number]::-webkit-outer-spin-button,
  input[type=number]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  input[type=number] {
    -moz-appearance: textfield; /* Firefox */
    appearance: textfield;
  }
</style>
</head>