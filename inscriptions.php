<?php
require __DIR__.'/db.php';
require __DIR__.'/auth_guard.php';
require_role(['admin','formateur']);  // ⚠️ ici : pas d'étudiants !
require __DIR__.'/header.php';


$action = $_GET['action'] ?? 'list';
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$msg = $err = null;

/* liste des sessions pour le select */
$sessions = $pdo->query("SELECT s.id, CONCAT(f.titre,' — ',s.date_debut,'→',s.date_fin) AS label
                         FROM session s JOIN formation f ON f.id=s.formation_id
                         ORDER BY s.date_debut DESC")->fetchAll();

/* actions */
if ($action==='add' && $_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $session_id = (int)$_POST['session_id'];
    $etudiant_id = (int)$_POST['etudiant_id'];
    $dupl=$pdo->prepare("SELECT 1 FROM inscription WHERE session_id=? AND etudiant_id=? AND statut <> 'ANNULE'");
    $dupl->execute([$session_id,$etudiant_id]);
    if($dupl->fetch()){ throw new Exception("Étudiant déjà inscrit à cette session."); }
    $pdo->prepare("INSERT INTO inscription(session_id, etudiant_id, statut) VALUES(?, ?, 'PREINSCRIT')")
        ->execute([$session_id,$etudiant_id]); // le trigger gère la capacité
    header("Location: inscriptions.php?session_id=$session_id&msg=ajoute"); exit;
  }catch(Exception $ex){ $err=$ex->getMessage(); }
}

if ($action==='cancel'){
  $pdo->prepare("UPDATE inscription SET statut='ANNULE' WHERE id=?")->execute([(int)$_GET['inscription_id']]);
  header("Location: inscriptions.php?session_id=".$session_id."&msg=annule"); exit;
}

/* info session + inscrits + places */
$info = null; $inscrits=[]; $places=null; $etudiantsNonInscrits=[];
if ($session_id>0){
  $st=$pdo->prepare("SELECT s.*, f.titre FROM session s JOIN formation f ON f.id=s.formation_id WHERE s.id=?");
  $st->execute([$session_id]); $info=$st->fetch();

  $st=$pdo->prepare("SELECT i.id, e.id AS etudiant_id, CONCAT(e.prenom,' ',e.nom) AS etudiant, i.statut
                     FROM inscription i JOIN etudiant e ON e.id=i.etudiant_id
                     WHERE i.session_id=? ORDER BY etudiant");
  $st->execute([$session_id]); $inscrits=$st->fetchAll();

  $st=$pdo->prepare("SELECT s.capacite AS capacite,
                        (SELECT COUNT(*) FROM inscription i WHERE i.session_id=s.id AND i.statut<>'ANNULE') AS inscrits,
                        s.capacite - (SELECT COUNT(*) FROM inscription i2 WHERE i2.session_id=s.id AND i2.statut<>'ANNULE') AS places_disponibles
                     FROM session s WHERE s.id=?");
  $st->execute([$session_id]); $places=$st->fetch();

  $st=$pdo->prepare("SELECT e.id, CONCAT(e.prenom,' ',e.nom,' — ',e.email) AS label
                     FROM etudiant e
                     WHERE NOT EXISTS (SELECT 1 FROM inscription i WHERE i.session_id=? AND i.etudiant_id=e.id AND i.statut <> 'ANNULE')
                     ORDER BY e.nom, e.prenom");
  $st->execute([$session_id]); $etudiantsNonInscrits=$st->fetchAll();
}
?>
<h1>🧾 Inscriptions</h1>
<p><a class="btn" href="sessions.php">← Sessions</a></p>

<?php if(isset($_GET['msg']) && $_GET['msg']==='ajoute'): ?><div class="notice ok">Étudiant inscrit.</div><?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg']==='annule'): ?><div class="notice ok">Inscription annulée.</div><?php endif; ?>
<?php if($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<form method="get">
  <label>Choisir une session</label>
  <select name="session_id" onchange="this.form.submit()">
    <option value="0">— Sélectionner —</option>
    <?php foreach($sessions as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= $session_id==$s['id']?'selected':'' ?>><?= e($s['label']) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn" type="submit">Voir</button></noscript>
</form>

<?php if($session_id>0 && $info): ?>
  <h2><?= e($info['titre']) ?> — <?= e($info['date_debut']) ?> → <?= e($info['date_fin']) ?></h2>
  <?php if($places): ?>
    <p>Capacité : <strong><?= (int)$places['capacite'] ?></strong> — Inscrits : <strong><?= (int)$places['inscrits'] ?></strong> — Restant : <strong><?= (int)$places['places_disponibles'] ?></strong></p>
  <?php endif; ?>

  <h3>Ajouter un étudiant</h3>
  <form method="post" action="inscriptions.php?action=add">
    <input type="hidden" name="session_id" value="<?= (int)$session_id ?>">
    <select name="etudiant_id" required>
      <?php foreach($etudiantsNonInscrits as $et): ?>
        <option value="<?= (int)$et['id'] ?>"><?= e($et['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Inscrire</button>
  </form>

  <h3>Étudiants inscrits</h3>

  <!-- Liens d'export (AJOUT) -->
  <p style="margin:8px 0;">
    <a class="btn" href="export_inscrits_csv.php?session_id=<?= (int)$session_id ?>">
      📄 Exporter les inscrits (CSV)
    </a>
    <a class="btn" href="export_presence_csv.php?session_id=<?= (int)$session_id ?>">
      📝 Exporter feuille d’émargement (CSV)
    </a>
  </p>

  <table>
    <tr><th>Étudiant</th><th>Statut</th><th>Actions</th></tr>
    <?php foreach($inscrits as $i): ?>
      <tr>
        <td><?= e($i['etudiant']) ?></td>
        <td><?= e($i['statut']) ?></td>
        <td>
          <?php if($i['statut']!=='ANNULE'): ?>
            <a class="btn" href="inscriptions.php?action=cancel&session_id=<?= (int)$session_id ?>&inscription_id=<?= (int)$i['id'] ?>" onclick="return confirm('Annuler cette inscription ?');">Annuler</a>
          <?php else: ?> — <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require __DIR__.'/footer.php'; ?>
