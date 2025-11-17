<?php
require __DIR__.'/db.php';
require __DIR__.'/auth_guard.php';
require_role(['admin']);      // ✅ tableau, plus l’erreur
require __DIR__.'/header.php';


$nbFormations = (int)$pdo->query("SELECT COUNT(*) FROM formation")->fetchColumn();
$nbSessions   = (int)$pdo->query("SELECT COUNT(*) FROM session")->fetchColumn();
$nbEtudiants  = (int)$pdo->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();

$rows = $pdo->query("SELECT taux_occupation FROM v_session_occupation")->fetchAll(PDO::FETCH_COLUMN);
$tauxMoyen = ($rows && count($rows)) ? round(array_sum(array_map('floatval',$rows))/count($rows), 1) : 0.0;

$sql = "SELECT s.*, f.titre AS formation
        FROM session s
        JOIN formation f ON f.id = s.formation_id
        WHERE s.date_debut > CURDATE()
          AND s.date_debut <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        ORDER BY s.date_debut ASC";
$sessionsAVenir = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<h1>Tableau de bord (Admin)</h1>

<ul>
  <li>Formations : <strong><?= $nbFormations ?></strong></li>
  <li>Sessions : <strong><?= $nbSessions ?></strong></li>
  <li>Étudiants : <strong><?= $nbEtudiants ?></strong></li>
  <li>Taux d’occupation moyen : <strong><?= $tauxMoyen ?>%</strong></li>
</ul>

<h2>Sessions à venir (14 jours)</h2>
<?php if(!$sessionsAVenir): ?>
  <p>Aucune session dans les 14 prochains jours.</p>
<?php else: ?>
  <table>
    <tr><th>Formation</th><th>Début</th><th>Fin</th><th>Salle</th><th>Capacité</th><th>Inscrits</th><th>%</th></tr>
    <?php foreach($sessionsAVenir as $s):
      $st = $pdo->prepare("SELECT COUNT(*) FROM inscription WHERE session_id=? AND statut <> 'ANNULE'");
      $st->execute([(int)$s['id']]); $inscrits = (int)$st->fetchColumn();
      $pct = ($s['capacite']>0) ? round(($inscrits/$s['capacite'])*100,1) : 0;
    ?>
    <tr>
      <td><?= e($s['formation']) ?></td>
      <td><?= e($s['date_debut']) ?></td>
      <td><?= e($s['date_fin']) ?></td>
      <td><?= e($s['salle']) ?></td>
      <td><?= (int)$s['capacite'] ?></td>
      <td><?= $inscrits ?></td>
      <td><?= $pct ?>%</td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require_once __DIR__.'/footer.php'; ?>
