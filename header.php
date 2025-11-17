<?php
// header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

// Base URL (fonctionne même si tu es sur /centre_formation_app/sous/page.php)
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$cssMain  = $baseUrl . '/style.css';
$cssAlt   = $baseUrl . '/assets/css/style.css';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Centre de formation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= $cssMain ?>">
  <!-- <link rel="stylesheet" href="<?= $cssAlt ?>"> -->
</head>
<body>
<header>
  <nav>
    <a href="<?= $baseUrl ?>/index.php">Accueil</a>
    <a href="<?= $baseUrl ?>/formations.php">Formations</a>
    <a href="<?= $baseUrl ?>/sessions.php">Sessions</a>
    <a href="<?= $baseUrl ?>/etudiants.php">Étudiants</a>
    <a href="<?= $baseUrl ?>/inscriptions.php">Inscriptions</a>
    <a href="<?= $baseUrl ?>/presences.php">Présences</a>
    <?php if(!empty($_SESSION['user']) && (($_SESSION['user']['role']??'')==='admin')): ?>
      <a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a>
    <?php endif; ?>
    <?php if(empty($_SESSION['user'])): ?>
      <a href="<?= $baseUrl ?>/login.php">Se connecter</a>
    <?php else: ?>
      <span>👤 <?= e($_SESSION['user']['nom'] ?? 'Utilisateur') ?> (<?= e($_SESSION['user']['role'] ?? '') ?>)</span>
      <a href="<?= $baseUrl ?>/logout.php">Se déconnecter</a>
    <?php endif; ?>
  </nav>
</header>
<div class="container">
