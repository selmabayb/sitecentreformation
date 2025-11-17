<?php
require __DIR__.'/db.php';
require __DIR__.'/auth_guard.php';
require_role(['admin','formateur']);  // ⚠️ ici : pas d'étudiants !
require __DIR__.'/header.php';

function e($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$ok = $err = null;

/* Charger la session + formation */
$session = null;
if ($session_id>0){
  $st=$pdo->prepare("SELECT s.*, f.titre AS formation FROM session s JOIN formation f ON f.id=s.formation_id WHERE s.id=?");
  $st->execute([$session_id]); $session=$st->fetch();
}

/* Enregistrement des présences */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save']) && $session_id>0){
  try{
    $pdo->beginTransaction();
    foreach($_POST['present'] ?? [] as $etudiant_id => $val){
      $present = ($val==='1') ? 1 : 0;
      $remarque = $_POST['remarque'][$etudiant_id] ?? null;
      $sql = "INSERT INTO presence(session_id, etudiant_id, present, remarque)
              VALUES(?,?,?,?)
              ON DUPLICATE KEY UPDATE present=VALUES(present), remarque=VALUES(remarque)";
      $st = $pdo->prepare($sql);
      $st->execute([$session_id, (int)$etudiant_id, $present, $remarque]);
    }
    $pdo->commit();
    $ok = "Présences enregistrées.";
  } catch(PDOException $ex){
    $pdo->rollBack();
    $err = $ex->getMessage();
  }
}

/* Liste des étudiants inscrits (hors annulés) + présences */
$liste=[];
if ($session_id>0){
  $sql="SELECT e.id AS etudiant_id, CONCAT(e.prenom,' ',e.nom) AS etudiant,
               COALESCE(p.present,0) AS present, p.remarque
        FROM inscription i
        JOIN etudiant e ON e.id=i.etudiant_id
        LEFT JOIN presence p ON p.session_id=i.session_id AND p.etudiant_id=i.etudiant_id
        WHERE i.session_id=? AND i.statut <> 'ANNULE'
        ORDER BY etudiant";
  $st=$pdo->prepare($sql); $st->execute([$session_id]); $liste=$st->fetchAll();
}
?>
<h1>✔️ Présences</h1>
<p><a class="btn" href="sessions.php">← Sessions</a></p>

<?php if($ok): ?><div class="notice ok"><?= e($ok) ?></div><?php endif; ?>
<?php if($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<form method="get" style="margin:10px 0">
  <label>Choisir une session</label>
  <select name="session_id" onchange="this.form.submit()">
    <option value="0">— Sélectionner —</option>
    <?php
    $slist = $pdo->query("SELECT s.id, CONCAT(f.titre,' — ',s.date_debut) AS label FROM session s JOIN formation f ON f.id=s.formation_id ORDER BY s.date_debut DESC")->fetchAll();
    foreach($slist as $s){ ?>
      <option value="<?= (int)$s['id'] ?>" <?= $session_id==$s['id']?'selected':'' ?>><?= e($s['label']) ?></option>
    <?php } ?>
  </select>
  <noscript><button class="btn" type="submit">Voir</button></noscript>
</form>

<?php if($session): ?>
  <h2><?= e($session['formation']) ?> — <?= e($session['date_debut']) ?> → <?= e($session['date_fin']) ?></h2>

  <form method="post">
    <table>
      <tr><th>Étudiant</th><th>Présent ?</th><th>Remarque</th></tr>
      <?php foreach($liste as $l): ?>
        <tr>
          <td><?= e($l['etudiant']) ?></td>
          <td style="text-align:center">
            <select name="present[<?= (int)$l['etudiant_id'] ?>]">
              <option value="0" <?= $l['present']? '' : 'selected' ?>>Absent</option>
              <option value="1" <?= $l['present']? 'selected' : '' ?>>Présent</option>
            </select>
          </td>
          <td><input type="text" name="remarque[<?= (int)$l['etudiant_id'] ?>]" value="<?= e($l['remarque'] ?? '') ?>"></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <p><button class="btn" type="submit" name="save" value="1">Enregistrer</button></p>
  </form>
<?php endif; ?>

<?php require __DIR__.'/footer.php'; ?>
