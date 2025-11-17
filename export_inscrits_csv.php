<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if (!in_array($_SESSION['user']['role'], ['admin','formateur'])) { http_response_code(403); exit('Accès refusé'); }

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if ($sessionId <= 0) { exit('session_id manquant'); }

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inscrits_session_'.$sessionId.'.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Formation','Date début','Date fin','Salle','Nom','Prénom','Email','Statut']);

$sql = "SELECT f.titre AS formation, s.date_debut, s.date_fin, s.salle,
               e.nom, e.prenom, e.email, i.statut
        FROM inscription i
        JOIN etudiant e   ON e.id = i.etudiant_id
        JOIN session s    ON s.id = i.session_id
        JOIN formation f  ON f.id = s.formation_id
        WHERE i.session_id = ?
          AND i.statut <> 'ANNULE'
        ORDER BY e.nom, e.prenom";

$stmt = $pdo->prepare($sql);
$stmt->execute([$sessionId]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, $row);
}
fclose($out);
exit;
