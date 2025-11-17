<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie qu'on est connecté
function require_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

// Vérifie que le rôle est autorisé pour la page
function require_role(array $allowed_roles) {
    require_login();

    $role = $_SESSION['user']['role'] ?? null;

    if (!in_array($role, $allowed_roles, true)) {
        // Redirige selon le rôle
        if ($role === 'etudiant') {
            header('Location: etudiant_home.php');
        } elseif ($role === 'formateur') {
            header('Location: formateur_home.php');
        } else { // admin ou inconnu
            header('Location: index.php');
        }
        exit;
    }
}
