<?php
// Affiche les erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Réponse en JSON
header('Content-Type: application/json');

require_once 'config.php';

// Récupération des données
$idea_id = $_POST['idea_id'] ?? null;
$comment = $_POST['comment'] ?? null;
$vote_type = $_POST['vote_type'] ?? null;

// Vérification des paramètres requis
if (!$idea_id || !$comment || !$vote_type) {
    echo json_encode(['error' => 'Paramètres manquants.']);
    exit;
}

// Préparation de la requête
$sql = "INSERT INTO downvote_comments (idea_id, comment, vote_type) VALUES (?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Erreur préparation SQL: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iss", $idea_id, $comment, $vote_type);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Erreur exécution: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
