<?php
// role_home.php — redirige vers la bonne “home” selon le rôle
require __DIR__.'/auth_guard.php';

$role = $_SESSION['user']['role'] ?? '';
if ($role === 'admin') {
  header('Location: dashboard.php');        // accueil admin
} elseif ($role === 'formateur') {
  header('Location: formateur_home.php');   // accueil formateur
} else {
  header('Location: etudiant_home.php');    // accueil étudiant
}
exit;
