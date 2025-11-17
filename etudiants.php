<?php
require __DIR__.'/db.php';
require __DIR__.'/auth_guard.php';
require_role(['admin']);      // gestion des étudiants = admin seulement
require __DIR__.'/header.php';

function e($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

$action = $_GET['action'] ?? 'list';
$msg = $err = null;

/* CREATE */
if ($action==='create' && $_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $pdo->prepare("INSERT INTO etudiant(nom,prenom,email,telephone) VALUES(?,?,?,?)")
        ->execute([trim($_POST['nom']),trim($_POST['prenom']),trim($_POST['email']),trim($_POST['telephone'] ?? '')]);
    header('Location: etudiants.php?msg=ajoute'); exit;
  }catch(PDOException $ex){
    if (str_contains($ex->getMessage(),'uk_etudiant_email') || str_contains($ex->getMessage(),'Duplicate')) $err="Email déjà utilisé.";
    else $err=$ex->getMessage();
  }
}

/* UPDATE */
if ($action==='update' && $_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $pdo->prepare("UPDATE etudiant SET nom=?, prenom=?, email=?, telephone=? WHERE id=?")
        ->execute([trim($_POST['nom']),trim($_POST['prenom']),trim($_POST['email']),trim($_POST['telephone'] ?? ''),(int)$_POST['id']]);
    header('Location: etudiants.php?msg=modifie'); exit;
  }catch(PDOException $ex){
    if (str_contains($ex->getMessage(),'uk_etudiant_email') || str_contains($ex->getMessage(),'Duplicate')) $err="Email déjà utilisé.";
    else $err=$ex->getMessage();
  }
}

/* DELETE */
if ($action==='delete'){
  try{
    $pdo->prepare("DELETE FROM etudiant WHERE id=?")->execute([(int)$_GET['id']]);
    header('Location: etudiants.php?msg=supprime'); exit;
  }catch(PDOException $ex){ $err = "Impossible de supprimer (inscriptions liées)."; }
}

/* EDIT data */
if ($action==='edit'){
  $st=$pdo->prepare("SELECT * FROM etudiant WHERE id=?"); $st->execute([(int)$_GET['id']]); $u=$st->fetch();
  if(!$u){ $err="Étudiant introuvable."; $action='list'; }
}

/* Recherche */
$q = trim($_GET['q'] ?? '');
if ($q!==''){
  $st = $pdo->prepare("SELECT * FROM etudiant WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? ORDER BY nom");
  $st->execute(["%$q%","%$q%","%$q%"]);
  $rows = $st->fetchAll();
} else {
  $rows = $pdo->query("SELECT * FROM etudiant ORDER BY nom")->fetchAll();
}
?>
<h1>👩‍🎓 Étudiants</h1>
<p><a class="btn" href="formations.php">← Formations</a> <a class="btn" href="etudiants.php?action=new">➕ Ajouter</a></p>

<form method="get" style="margin:10px 0">
  <input type="hidden" name="action" value="list">
  <input name="q" placeholder="Rechercher (nom, prénom, email)" value="<?= e($q) ?>">
  <button class="btn" type="submit">Rechercher</button>
</form>

<?php if(isset($_GET['msg'])): ?><div class="notice ok">✅ Action réussie</div><?php endif; ?>
<?php if($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<?php if($action==='new'): ?>
  <h2>Ajouter</h2>
  <form method="post" action="etudiants.php?action=create">
    <div class="row"><label>Nom *</label><input name="nom" required></div>
    <div class="row"><label>Prénom *</label><input name="prenom" required></div>
    <div class="row"><label>Email *</label><input type="email" name="email" required></div>
    <div class="row"><label>Téléphone</label><input name="telephone"></div>
    <button class="btn">Enregistrer</button> <a class="btn" href="javascript:history.back()">⬅ Retour</a>
  </form>

<?php elseif($action==='edit' && isset($u)): ?>
  <h2>Modifier</h2>
  <form method="post" action="etudiants.php?action=update">
    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
    <div class="row"><label>Nom *</label><input name="nom" value="<?= e($u['nom']) ?>" required></div>
    <div class="row"><label>Prénom *</label><input name="prenom" value="<?= e($u['prenom']) ?>" required></div>
    <div class="row"><label>Email *</label><input type="email" name="email" value="<?= e($u['email']) ?>" required></div>
    <div class="row"><label>Téléphone</label><input name="telephone" value="<?= e($u['telephone']) ?>"></div>
    <button class="btn">Enregistrer</button> <a class="btn" href="etudiants.php">Annuler</a>
  </form>

<?php else: ?>
  <table>
    <tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Téléphone</th><th>Actions</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['nom']) ?></td>
        <td><?= e($r['prenom']) ?></td>
        <td><?= e($r['email']) ?></td>
        <td><?= e($r['telephone']) ?></td>
        <td>
          <a class="btn" href="etudiants.php?action=edit&id=<?= (int)$r['id'] ?>">✏️</a>
          <a class="btn" href="etudiants.php?action=delete&id=<?= (int)$r['id'] ?>" onclick="return confirm('Supprimer cet étudiant ?');">🗑️</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require __DIR__.'/footer.php'; ?>
