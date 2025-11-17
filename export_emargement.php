<?php
require __DIR__.'/db.php';
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if ($session_id<=0) { http_response_code(400); echo "Session inconnue."; exit; }

$st = $pdo->prepare("SELECT CONCAT(e.prenom,' ',e.nom) AS etudiant, i.statut
                     FROM inscription i JOIN etudiant e ON e.id=i.etudiant_id
                     WHERE i.session_id=? ORDER BY etudiant");
$st->execute([$session_id]);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="emargement_session_'.$session_id.'.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Etudiant','Statut']);
while($row = $st->fetch(PDO::FETCH_NUM)){
  fputcsv($output, $row);
}
fclose($output);
