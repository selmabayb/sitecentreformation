<?php
require_once __DIR__.'/auth_guard.php';
require_role(['formateur']);
require_once __DIR__.'/db.php';
require_once __DIR__.'/header.php';

$formateurId = (int)($_SESSION['user']['formateur_id'] ?? 0);

$sql = "SELECT s.id, f.titre, s.date_debut, s.date_fin, s.salle
        FROM session s
        JOIN formation f ON f.id = s.formation_id
        JOIN affectation a ON a.session_id = s.id
        WHERE a.formateur_id = ?
        ORDER BY s.date_debut DESC";
$mesSessions = [];
if ($formateurId) {
  $st = $pdo->prepare($sql);
  $st->execute([$formateurId]);
  $mesSessions = $st->fetchAll(PDO::FETCH_ASSOC);
}
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<h1>Mon espace formateur</h1>

<?php if(!$mesSessions): ?>
  <p>Aucune session assignée.</p>
<?php else: ?>
  <table>
    <tr><th>Formation</th><th>Début</th><th>Fin</th><th>Salle</th><th>Actions</th></tr>
    <?php foreach($mesSessions as $s): ?>
      <tr>
        <td><?= e($s['titre']) ?></td>
        <td><?= e($s['date_debut']) ?></td>
        <td><?= e($s['date_fin']) ?></td>
        <td><?= e($s['salle']) ?></td>
        <td>
          <a class="btn" href="presences.php?session_id=<?= (int)$s['id'] ?>">Feuille de présence</a>
          <a class="btn" href="export_presence_csv.php?session_id=<?= (int)$s['id'] ?>">Export présence</a>
          <a class="btn" href="export_inscrits_csv.php?session_id=<?= (int)$s['id'] ?>">Export inscrits</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require_once __DIR__.'/footer.php'; ?>
