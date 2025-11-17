<?php
require __DIR__.'/db.php';
require __DIR__.'/auth_guard.php';
require_role(['admin','formateur']);     // SEUL l'admin peut gérer les formations
require __DIR__.'/header.php';
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }


$action = $_GET['action'] ?? 'list';
$msg = $err = null;

/* CREATE */
if ($action==='create' && $_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $pdo->prepare("INSERT INTO formation(titre,domaine,niveau,description) VALUES(?,?,?,?)")
        ->execute([trim($_POST['titre']),trim($_POST['domaine']),trim($_POST['niveau']),$_POST['description']??null]);
    header('Location: formations.php?msg=ajoute'); exit;
  }catch(PDOException $ex){ $err=$ex->getMessage(); }
}
/* UPDATE */
if ($action==='update' && $_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $pdo->prepare("UPDATE formation SET titre=?, domaine=?, niveau=?, description=? WHERE id=?")
        ->execute([trim($_POST['titre']),trim($_POST['domaine']),trim($_POST['niveau']),$_POST['description']??null,(int)$_POST['id']]);
    header('Location: formations.php?msg=modifie'); exit;
  }catch(PDOException $ex){ $err=$ex->getMessage(); }
}
/* DELETE */
if ($action==='delete'){
  try{
    $pdo->prepare("DELETE FROM formation WHERE id=?")->execute([(int)$_GET['id']]);
    header('Location: formations.php?msg=supprime'); exit;
  }catch(PDOException $ex){
    $err="Impossible de supprimer : sessions liées."; $action='list';
  }
}
/* EDIT */
if ($action==='edit'){
  $st=$pdo->prepare("SELECT * FROM formation WHERE id=?"); $st->execute([(int)$_GET['id']]); $f=$st->fetch();
  if(!$f){ $err="Introuvable."; $action='list'; }
}
?>
<h1>📚 Formations</h1>
<p><a class="btn" href="list_formations.php">← Retour</a> <a class="btn" href="formations.php?action=new">➕ Ajouter</a></p>

<?php if(isset($_GET['msg'])): ?>
  <div class="notice ok">✅ Action réussie</div>
<?php endif; ?>
<?php if($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<?php if($action==='new'): ?>
  <h2>Ajouter</h2>
  <form method="post" action="formations.php?action=create">
    <div class="row"><label>Titre *</label><input name="titre" required></div>
    <div class="row"><label>Domaine *</label><input name="domaine" required></div>
    <div class="row"><label>Niveau *</label><input name="niveau" required></div>
    <div class="row"><label>Description</label><textarea name="description" rows="4"></textarea></div>
    <button class="btn">Enregistrer</button> <a class="btn" href="javascript:history.back()">⬅ Retour</a>
  </form>

<?php elseif($action==='edit' && isset($f)): ?>
  <h2>Modifier</h2>
  <form method="post" action="formations.php?action=update">
    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
    <div class="row"><label>Titre *</label><input name="titre" value="<?= e($f['titre']) ?>" required></div>
    <div class="row"><label>Domaine *</label><input name="domaine" value="<?= e($f['domaine']) ?>" required></div>
    <div class="row"><label>Niveau *</label><input name="niveau" value="<?= e($f['niveau']) ?>" required></div>
    <div class="row"><label>Description</label><textarea name="description" rows="4"><?= e($f['description']) ?></textarea></div>
    <button class="btn">Enregistrer</button> <a class="btn" href="formations.php">Annuler</a>
  </form>

<?php else:
  $rows=$pdo->query("SELECT id,titre,domaine,niveau,description FROM formation ORDER BY created_at DESC")->fetchAll(); ?>
  <table>
    <tr><th>Titre</th><th>Domaine</th><th>Niveau</th><th>Description</th><th>Actions</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['titre']) ?></td>
        <td><?= e($r['domaine']) ?></td>
        <td><?= e($r['niveau']) ?></td>
        <td><?= nl2br(e($r['description'])) ?></td>
        <td>
          <a class="btn" href="formations.php?action=edit&id=<?= (int)$r['id'] ?>">✏️</a>
          <a class="btn" href="formations.php?action=delete&id=<?= (int)$r['id'] ?>" onclick="return confirm('Supprimer ?');">🗑️</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require __DIR__.'/footer.php'; ?>
