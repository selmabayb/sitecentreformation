<?php
require __DIR__.'/db.php';
require __DIR__.'/auth_guard.php';
require_role(['etudiant']);   // ❗ seulement les étudiants
require __DIR__.'/header.php';

function e($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

$etudiantId = $_SESSION['user']['etudiant_id'] ?? null;
if (!$etudiantId) {
  echo "<p>Erreur : aucun étudiant lié à ce compte.</p>";
  require __DIR__.'/footer.php';
  exit;
}

$msg = $err = null;

/* ---- TRAITEMENT DES ACTIONS (inscription / annulation) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action    = $_POST['action'] ?? '';
  $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;

    if ($action === 'inscrire' && $sessionId > 0) {
    try {
      // On regarde s'il existe déjà UNE inscription (peu importe le statut)
      $st = $pdo->prepare("SELECT statut FROM inscription 
                           WHERE session_id = ? AND etudiant_id = ?
                           LIMIT 1");
      $st->execute([$sessionId, $etudiantId]);
      $row = $st->fetch(PDO::FETCH_ASSOC);

      if ($row) {
        // Il existe déjà une inscription
        if ($row['statut'] === 'ANNULE') {
          // On "réactive" l'ancienne inscription
          $st = $pdo->prepare("UPDATE inscription 
                               SET statut = 'PREINSCRIT'
                               WHERE session_id = ? AND etudiant_id = ?");
          $st->execute([$sessionId, $etudiantId]);
          $msg = "Inscription réactivée.";
        } else {
          // Déjà inscrit avec un statut actif
          $err = "Vous êtes déjà inscrit à cette session.";
        }
      } else {
        // Aucune inscription => on crée une nouvelle ligne
        $st = $pdo->prepare("INSERT INTO inscription(session_id, etudiant_id, statut)
                             VALUES(?, ?, 'PREINSCRIT')");
        $st->execute([$sessionId, $etudiantId]);
        $msg = "Inscription enregistrée.";
      }
    } catch (Exception $ex) {
      $err = $ex->getMessage();
    }
  }


  if ($action === 'annuler' && $sessionId > 0) {
    try {
      // L'étudiant ne peut annuler QUE SES inscriptions
      $st = $pdo->prepare("UPDATE inscription SET statut='ANNULE'
                           WHERE session_id=? AND etudiant_id=?");
      $st->execute([$sessionId, $etudiantId]);
      $msg = "Inscription annulée.";
    } catch (Exception $ex) {
      $err = $ex->getMessage();
    }
  }
}

/* ---- MES COURS (sessions où l'étudiant est inscrit) ---- */
$sql = "SELECT s.id AS session_id,
               f.titre,
               s.date_debut,
               s.date_fin,
               s.salle,
               i.statut
        FROM inscription i
        JOIN session s   ON s.id = i.session_id
        JOIN formation f ON f.id = s.formation_id
        WHERE i.etudiant_id = ?
          AND i.statut <> 'ANNULE'
        ORDER BY s.date_debut DESC";
$st = $pdo->prepare($sql);
$st->execute([$etudiantId]);
$mesCours = $st->fetchAll();

/* ---- SESSIONS DISPONIBLES POUR S'INSCRIRE ---- */
/* (sessions futures où il n'est pas déjà inscrit) */
$sql2 = "SELECT s.id,
                f.titre,
                s.date_debut,
                s.date_fin,
                s.salle,
                s.capacite,
                COUNT(i2.etudiant_id) AS nb_inscrits
         FROM session s
         JOIN formation f ON f.id = s.formation_id
         LEFT JOIN inscription i2
           ON i2.session_id = s.id AND i2.statut <> 'ANNULE'
         WHERE s.date_fin >= CURDATE()
           AND NOT EXISTS (
             SELECT 1 FROM inscription i
             WHERE i.session_id = s.id
               AND i.etudiant_id = ?
               AND i.statut <> 'ANNULE'
           )
         GROUP BY s.id, f.titre, s.date_debut, s.date_fin, s.salle, s.capacite
         ORDER BY s.date_debut ASC";
$st2 = $pdo->prepare($sql2);
$st2->execute([$etudiantId]);
$sessionsDispo = $st2->fetchAll();
?>

<h1>Mes cours</h1>

<?php if($msg): ?><div class="notice ok"><?= e($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="notice err"><?= e($err) ?></div><?php endif; ?>

<h2>📚 Mes sessions actuelles</h2>
<?php if(!$mesCours): ?>
  <p>Vous n'êtes inscrit à aucune session.</p>
<?php else: ?>
  <table>
    <tr>
      <th>Formation</th>
      <th>Date début</th>
      <th>Date fin</th>
      <th>Salle</th>
      <th>Statut</th>
      <th>Action</th>
    </tr>
    <?php foreach($mesCours as $c): ?>
      <tr>
        <td><?= e($c['titre']) ?></td>
        <td><?= e($c['date_debut']) ?></td>
        <td><?= e($c['date_fin']) ?></td>
        <td><?= e($c['salle']) ?></td>
        <td><?= e($c['statut']) ?></td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="annuler">
            <input type="hidden" name="session_id" value="<?= (int)$c['session_id'] ?>">
            <button class="btn" type="submit" onclick="return confirm('Annuler cette inscription ?');">
              Annuler
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<h2 style="margin-top:30px;">📝 S'inscrire à une nouvelle session</h2>
<?php if(!$sessionsDispo): ?>
  <p>Aucune session disponible pour le moment.</p>
<?php else: ?>
  <table>
    <tr>
      <th>Formation</th>
      <th>Date début</th>
      <th>Date fin</th>
      <th>Salle</th>
      <th>Places</th>
      <th>Action</th>
    </tr>
    <?php foreach($sessionsDispo as $s): ?>
      <tr>
        <td><?= e($s['titre']) ?></td>
        <td><?= e($s['date_debut']) ?></td>
        <td><?= e($s['date_fin']) ?></td>
        <td><?= e($s['salle']) ?></td>
        <td>
          <?php if($s['capacite']): ?>
            <?= (int)$s['nb_inscrits'] ?> / <?= (int)$s['capacite'] ?>
          <?php else: ?>
            Illimité
          <?php endif; ?>
        </td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="inscrire">
            <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
            <button class="btn" type="submit">M'inscrire</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require __DIR__.'/footer.php'; ?>
