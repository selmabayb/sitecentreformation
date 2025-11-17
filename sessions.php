<?php
require __DIR__.'/db.php';
require __DIR__.'/auth_guard.php';
require_role(['admin','formateur']);    // ou ['admin','formateur'] si tu veux que les formateurs gèrent les sessions
require __DIR__.'/header.php';

$action = $_GET['action'] ?? 'list';
$msg = $err = null;

/* Filtres / données pour selects */
$formation_filter = isset($_GET['formation_id']) ? (int)$_GET['formation_id'] : 0;
$formations = $pdo->query("SELECT id, titre FROM formation ORDER BY titre")->fetchAll();
$formateurs = $pdo->query("SELECT id, CONCAT(prenom,' ',nom) AS nom FROM formateur ORDER BY nom")->fetchAll();

/* CREATE */
if ($action==='create' && $_SERVER['REQUEST_METHOD']==='POST') {
  try {
    $fid   = (int)$_POST['formation_id'];
    $deb   = $_POST['date_debut'];
    $fin   = $_POST['date_fin'];
    $salle = trim($_POST['salle']);
    $cap   = (int)$_POST['capacite'];
    $form  = (int)$_POST['formateur_id'];
    if (strtotime($fin) < strtotime($deb)) { throw new Exception("La date de fin ne peut pas être avant la date de début."); }

    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO session(formation_id,date_debut,date_fin,salle,capacite,statut) VALUES(?,?,?,?,?, 'PLANIFIEE')")
        ->execute([$fid,$deb,$fin,$salle,$cap]);
    $sid = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO affectation(session_id, formateur_id, role) VALUES(?, ?, 'FORMATEUR_PRINCIPAL')")
        ->execute([$sid,$form]);
    $pdo->commit();
    header('Location: sessions.php?msg=ajoute'); exit;
  } catch(Exception $ex){ if($pdo->inTransaction()) $pdo->rollBack(); $err = $ex->getMessage(); }
}

/* UPDATE */
if ($action==='update' && $_SERVER['REQUEST_METHOD']==='POST') {
  try {
    $id    = (int)$_POST['id'];
    $fid   = (int)$_POST['formation_id'];
    $deb   = $_POST['date_debut'];
    $fin   = $_POST['date_fin'];
    $salle = trim($_POST['salle']);
    $cap   = (int)$_POST['capacite'];
    $stat  = $_POST['statut'];
    $form  = (int)$_POST['formateur_id'];
    if (strtotime($fin) < strtotime($deb)) { throw new Exception("La date de fin ne peut pas être avant la date de début."); }

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE session SET formation_id=?, date_debut=?, date_fin=?, salle=?, capacite=?, statut=? WHERE id=?")
        ->execute([$fid,$deb,$fin,$salle,$cap,$stat,$id]);
    $pdo->prepare("DELETE FROM affectation WHERE session_id=?")->execute([$id]);
    $pdo->prepare("INSERT INTO affectation(session_id, formateur_id, role) VALUES(?, ?, 'FORMATEUR_PRINCIPAL')")
        ->execute([$id,$form]);
    $pdo->commit();
    header('Location: sessions.php?msg=modifie'); exit;
  } catch(Exception $ex){ if($pdo->inTransaction()) $pdo->rollBack(); $err = $ex->getMessage(); }
}

/* DELETE */
if ($action==='delete') {
  try {
    $pdo->prepare("DELETE FROM session WHERE id=?")->execute([(int)$_GET['id']]);
    header('Location: sessions.php?msg=supprime'); exit;
  } catch(PDOException $ex){ $err = $ex->getMessage(); }
}

/* EDIT data */
if ($action==='edit') {
  $st=$pdo->prepare("SELECT * FROM session WHERE id=?"); $st->execute([(int)$_GET['id']]); $s=$st->fetch();
  if(!$s){ $err="Session introuvable."; $action='list'; }
}
?>
<h1>📅 Sessions</h1>
<p>
  <a class="btn" href="formations.php">← Formations</a>
  <a class="btn" href="sessions.php?action=new<?= $formation_filter? '&formation_id='.$formation_filter:''; ?>">➕ Créer une session</a>
</p>

<?php if(isset($_GET['msg']) && $_GET['msg']==='ajoute'): ?><div class="notice ok">Session ajoutée.</div><?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg']==='modifie'): ?><div class="notice ok">Session modifiée.</div><?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg']==='supprime'): ?><div class="notice ok">Session supprimée.</div><?php endif; ?>
<?php if($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<form method="get" style="margin:10px 0">
  <label>Filtrer par formation :</label>
  <select name="formation_id" onchange="this.form.submit()">
    <option value="0">— Toutes —</option>
    <?php foreach($formations as $f): ?>
      <option value="<?= (int)$f['id'] ?>" <?= $formation_filter==$f['id']?'selected':'' ?>><?= e($f['titre']) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn" type="submit">Filtrer</button></noscript>
</form>

<?php if($action==='new'): ?>
  <h2>Créer une session</h2>
  <form method="post" action="sessions.php?action=create">
    <div class="row"><label>Formation *</label>
      <select name="formation_id" required>
        <?php foreach($formations as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= $formation_filter==$f['id']?'selected':'' ?>><?= e($f['titre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row"><label>Date début *</label><input type="date" name="date_debut" required></div>
    <div class="row"><label>Date fin *</label><input type="date" name="date_fin" required></div>
    <div class="row"><label>Salle *</label><input name="salle" required></div>
    <div class="row"><label>Capacité *</label><input type="number" name="capacite" min="1" required></div>
    <div class="row"><label>Formateur *</label>
      <select name="formateur_id" required>
        <?php foreach($formateurs as $fm): ?>
          <option value="<?= (int)$fm['id'] ?>"><?= e($fm['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn">Enregistrer</button>
    <a class="btn" href="javascript:history.back()">⬅ Retour</a>
  </form>

<?php elseif($action==='edit' && isset($s)): 
  $aff = $pdo->prepare("SELECT formateur_id FROM affectation WHERE session_id=? LIMIT 1");
  $aff->execute([(int)$s['id']]); $aff=$aff->fetch();
?>
  <h2>Modifier la session</h2>
  <form method="post" action="sessions.php?action=update">
    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
    <div class="row"><label>Formation *</label>
      <select name="formation_id" required>
        <?php foreach($formations as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= $f['id']==$s['formation_id']?'selected':'' ?>><?= e($f['titre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row"><label>Date début *</label><input type="date" name="date_debut" value="<?= e($s['date_debut']) ?>" required></div>
    <div class="row"><label>Date fin *</label><input type="date" name="date_fin" value="<?= e($s['date_fin']) ?>" required></div>
    <div class="row"><label>Salle *</label><input name="salle" value="<?= e($s['salle']) ?>" required></div>
    <div class="row"><label>Capacité *</label><input type="number" name="capacite" min="1" value="<?= (int)$s['capacite'] ?>" required></div>
    <div class="row"><label>Formateur *</label>
      <select name="formateur_id" required>
        <?php foreach($formateurs as $fm): ?>
          <option value="<?= (int)$fm['id'] ?>" <?= ($aff && $fm['id']==$aff['formateur_id'])?'selected':'' ?>><?= e($fm['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row"><label>Statut</label>
      <select name="statut">
        <?php foreach(['PLANIFIEE','OUVERTE','CLOTUREE','ANNULEE'] as $st): ?>
          <option <?= $st===$s['statut']?'selected':'' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn">Enregistrer</button>
    <a class="btn" href="sessions.php">Annuler</a>
  </form>

<?php else:
  $sql = "SELECT s.*, f.titre AS formation,
          (SELECT COUNT(*) FROM inscription i WHERE i.session_id=s.id AND i.statut<>'ANNULE') AS inscrits,
          s.capacite - (SELECT COUNT(*) FROM inscription i2 WHERE i2.session_id=s.id AND i2.statut<>'ANNULE') AS places_disponibles,
          (SELECT CONCAT(prenom,' ',nom) FROM affectation a JOIN formateur fm ON fm.id=a.formateur_id WHERE a.session_id=s.id LIMIT 1) AS formateur_principal
          FROM session s
          JOIN formation f ON f.id=s.formation_id ";
  $params=[];
  if($formation_filter>0){ $sql.=" WHERE s.formation_id=? "; $params[]=$formation_filter; }
  $sql.=" ORDER BY s.date_debut DESC";
  $st = $pdo->prepare($sql); $st->execute($params); $rows = $st->fetchAll();
?>
  <table>
    <tr><th>Formation</th><th>Début</th><th>Fin</th><th>Salle</th><th>Capacité</th><th>Inscrits</th><th>Restant</th><th>Formateur</th><th>Statut</th><th>Actions</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['formation']) ?></td>
        <td><?= e($r['date_debut']) ?></td>
        <td><?= e($r['date_fin']) ?></td>
        <td><?= e($r['salle']) ?></td>
        <td><?= (int)$r['capacite'] ?></td>
        <td><?= (int)$r['inscrits'] ?></td>
        <td><?= (int)$r['places_disponibles'] ?></td>
        <td><?= e($r['formateur_principal'] ?? '—') ?></td>
        <td><?= e($r['statut']) ?></td>
        <td>
          <a class="btn" href="inscriptions.php?session_id=<?= (int)$r['id'] ?>">👥</a>
          <a class="btn" href="presences.php?session_id=<?= (int)$r['id'] ?>">✔️</a>
          <a class="btn" href="sessions.php?action=edit&id=<?= (int)$r['id'] ?>">✏️</a>
          <a class="btn" href="sessions.php?action=delete&id=<?= (int)$r['id'] ?>" onclick="return confirm('Supprimer cette session ?');">🗑️</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require __DIR__.'/footer.php'; ?>
