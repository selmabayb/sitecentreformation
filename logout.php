<?php
session_start();
session_destroy();
header('Location: index.php'); // retour à la belle page d’accueil
exit;
