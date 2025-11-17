<?php
session_start();
require_once __DIR__ . '/db.php';
 // ou __DIR__.'/db.php' si ton db.php est à la racine

// Si déjà connecté → redirige vers la bonne page selon le rôle
if (isset($_SESSION['user'])) {
  header('Location: role_home.php');
  exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['mot_de_passe'] ?? '';

  // Vérifie que les deux champs sont remplis
  if ($email === '' || $pass === '') {
    $erreur = "Veuillez entrer votre email et votre mot de passe.";
  } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifie le mot de passe (haché en SHA2 dans la base)
    if ($user && hash('sha256', $pass) === $user['mot_de_passe']) {
      $_SESSION['user'] = [
        'id'           => (int)$user['id'],
        'nom'          => $user['nom'],
        'email'        => $user['email'],
        'role'         => $user['role'],
        'formateur_id' => $user['formateur_id'] ? (int)$user['formateur_id'] : null,
        'etudiant_id'  => $user['etudiant_id']  ? (int)$user['etudiant_id']  : null,
      ];

      // Redirection unique : on laisse role_home décider du reste
      header('Location: role_home.php');
      exit;
    } else {
      $erreur = "Email ou mot de passe incorrect.";
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion – Centre de formation</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h1>Connexion</h1>
    <?php if($erreur): ?>
      <div class="notice err"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="post" style="max-width:400px;background:#fff;padding:16px;border-radius:8px;border:1px solid #eee;">
      <label>Email</label><br>
      <input type="email" name="email" required style="width:100%;padding:8px;margin-bottom:10px;"><br>
      <label>Mot de passe</label><br>
      <input type="password" name="mot_de_passe" required style="width:100%;padding:8px;margin-bottom:12px;"><br>
      <button class="btn" type="submit">Se connecter</button>
    </form>

    <p style="margin-top:12px;color:#555;">
      Comptes de test :<br>
      admin@mail.com / admin123<br>
      form@mail.com / form123<br>
      etud@mail.com / etud123
    </p>
  </div>
</body>
</html>
