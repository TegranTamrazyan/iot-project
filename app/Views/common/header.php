<?php




?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <!-- TODO: include your CSS files here -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Did this to block horizontal scroll on all pages because some pages had it and didn't look professional -->
    <style>
        body {
            overflow-x: hidden;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- Navbar Section -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        
    </nav>

    <!-- Flash Messages -->
    <div class="container mt-3">
        <?= \App\Helpers\Core\FlashMessage::render() ?>
    </div>

    <main class="flex-fill">
        <!-- Breadcrumb -->
        <?php if (!empty($breadcrumbs)): ?>
            <nav aria-label="breadcrumb" class="bg-light border-bottom py-2">
                <div class="container">
                    <ol class="breadcrumb mb-0">
                        <?php foreach ($breadcrumbs as $label => $url): ?>
                            <?php if ($url): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?= \App\Helpers\ViewHelper::url($url) ?>"><?= htmlspecialchars($label) ?></a>
                                </li>
                            <?php else: ?>
                                <li class="breadcrumb-item active"><?= htmlspecialchars($label) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </nav>
        <?php endif; ?>
        <!-- Page Content Wrapper
<div class="container mt-4 px-4"> -->
