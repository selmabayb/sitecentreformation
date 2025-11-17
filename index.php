<?php
require __DIR__.'/header.php';
require __DIR__.'/db.php';
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// petits compteurs (facultatif)
try {
  $nbFormations = (int)$pdo->query("SELECT COUNT(*) FROM formation")->fetchColumn();
  $nbSessions   = (int)$pdo->query("SELECT COUNT(*) FROM session")->fetchColumn();
  $nbEtudiants  = (int)$pdo->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();
} catch(Throwable $th) { $nbFormations=$nbSessions=$nbEtudiants=0; }
?>
<h1>Bienvenue 👋</h1>
<p>Gérez les formations, sessions, inscriptions, présences et suivez l’activité du centre.</p>

<?php if (empty($_SESSION['user'])): ?>
  <section style="background:#fff;padding:14px;border-radius:8px;margin:16px 0;border:1px solid #eaeaea;max-width:520px;">
    <h2 style="margin-top:0;">S’identifier</h2>
    <form method="post" action="login.php">
      <div style="margin-bottom:8px;">
        <label>Email</label><br>
        <input type="email" name="email" required style="width:100%;padding:8px;">
      </div>
      <div style="margin-bottom:12px;">
        <label>Mot de passe</label><br>
        <input type="password" name="mot_de_passe" required style="width:100%;padding:8px;">
      </div>
      <button class="btn" type="submit">Se connecter</button>
    </form>
  </section>
<?php else: ?>
  <div class="notice ok">Connecté en tant que <strong><?= e($_SESSION['user']['nom'] ?? 'Utilisateur') ?></strong> (<?= e($_SESSION['user']['role'] ?? '') ?>)</div>
  <p><a class="btn" href="role_home.php">Aller à mon espace</a></p>
<?php endif; ?>

<section style="background:#fff;padding:14px;border-radius:8px;border:1px solid #eaeaea;">
  <h3 style="margin-top:0;">Indicateurs rapides</h3>
  <ul>
    <li>Formations : <strong><?= $nbFormations ?></strong></li>
    <li>Sessions : <strong><?= $nbSessions ?></strong></li>
    <li>Étudiants : <strong><?= $nbEtudiants ?></strong></li>
  </ul>
</section>

<?php require __DIR__.'/footer.php'; ?>
