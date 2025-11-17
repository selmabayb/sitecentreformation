<?php
require_once __DIR__.'/auth_guard.php';
require_role(['etudiant']);
require_once __DIR__.'/db.php';
require_once __DIR__.'/header.php';

$etudiantId = (int)($_SESSION['user']['etudiant_id'] ?? 0);

$sql = "SELECT i.statut, s.date_debut, s.date_fin, s.salle, f.titre
        FROM inscription i
        JOIN session s   ON s.id = i.session_id
        JOIN formation f ON f.id = s.formation_id
        WHERE i.etudiant_id = ?
        ORDER BY s.date_debut DESC";
$mesInscriptions = [];
if ($etudiantId) {
  $st = $pdo->prepare($sql);
  $st->execute([$etudiantId]);
  $mesInscriptions = $st->fetchAll(PDO::FETCH_ASSOC);
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>

<h1>Mon espace étudiant</h1>

<!-- 🔗 Bouton vers la page mes_cours.php -->
<p>
  <a href="mes_cours.php" class="btn">📚 Voir mes cours et m'inscrire</a>
</p>

<?php if(!$mesInscriptions): ?>
  <p>Vous n’êtes inscrit(e) à aucune session.</p>
<?php else: ?>
  <table>
    <tr><th>Formation</th><th>Début</th><th>Fin</th><th>Salle</th><th>Statut</th></tr>
    <?php foreach($mesInscriptions as $i): ?>
      <tr>
        <td><?= e($i['titre']) ?></td>
        <td><?= e($i['date_debut']) ?></td>
        <td><?= e($i['date_fin']) ?></td>
        <td><?= e($i['salle']) ?></td>
        <td><?= e($i['statut']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require_once __DIR__.'/footer.php'; ?>
