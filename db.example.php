<?php
/**
 * Fichier : db.example.php
 * Rôle    : Exemple de configuration pour la connexion à la base de données.
 *           À copier sous le nom "db.php" puis à adapter avec vos identifiants.
 */

$host = 'localhost';          // Adresse du serveur MySQL
$dbname = 'centre_formation'; // Nom de la base de données
$username = 'root';           // Identifiant MySQL
$password = '';               // Mot de passe MySQL (laisser vide sous XAMPP)

try {
    // Connexion PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Activer les erreurs PDO sous forme d’exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Message d’erreur clair si la connexion échoue
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
