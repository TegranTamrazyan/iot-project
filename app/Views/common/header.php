<?php
$h = static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$page_title=$page_title??'IOT Smart Store';
?>
<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title><?= $h($page_title) ?> | IOT Smart Store</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 <link href="<?= BASE_URL ?>assets/css/store.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<nav class="navbar navbar-expand-lg bg-dark navbar-dark py-3">
 <div class="container flex-wrap gap-2">
  <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>checkout">IOT Smart Store</a>
  <div class="d-flex flex-wrap align-items-center gap-2">
   <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>checkout">Checkout</a>
   <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>fridge-dashboard">Fridges</a>
   <?php if (!empty($_SESSION['customer_id'])): ?>
    <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>account">My account</a>
   <?php else: ?>
    <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>login">Log in</a>
    <a class="btn btn-sm btn-warning" href="<?= BASE_URL ?>adding">Sign up</a>
   <?php endif; ?>
  </div>
 </div>
</nav>
<main class="flex-grow-1">
