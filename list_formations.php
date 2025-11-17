<?php
require __DIR__ . '/db.php';
require __DIR__ . '/header.php';
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$sql = "SELECT id, titre, domaine, niveau, description, created_at
        FROM formation
        ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$formations = $stmt->fetchAll();
?>

<h1>📚 Liste des formations</h1>
<p>
  <a class="btn" href="formations.php">Gérer les formations</a>
  <a class="btn" href="sessions.php">Gérer les sessions</a>
</p>

<?php if (!$formations): ?>
  <div class="notice err">Aucune formation trouvée dans la base.</div>
<?php else: ?>
  <table>
    <tr>
      <th>Titre</th><th>Domaine</th><th>Niveau</th><th>Description</th><th>Créée le</th><th>Actions</th>
    </tr>
    <?php foreach ($formations as $f): ?>
      <tr>
        <td><?= e($f['titre']) ?></td>
        <td><?= e($f['domaine']) ?></td>
        <td><?= e($f['niveau']) ?></td>
        <td><?= nl2br(e($f['description'])) ?></td>
        <td><?= e($f['created_at']) ?></td>
        <td>
          <a class="btn" href="sessions.php?formation_id=<?= (int)$f['id'] ?>">📅 Sessions</a>
          <a class="btn" href="formations.php?action=edit&id=<?= (int)$f['id'] ?>">✏️ Modifier</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
