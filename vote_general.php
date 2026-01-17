<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once 'config.php';

$idea_id = $_POST['idea_id'] ?? null;
$vote_type = $_POST['vote_type'] ?? null;
$comment = $_POST['comment'] ?? null;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (!$idea_id || !in_array($vote_type, ['up', 'down'])) {
    echo json_encode(['error' => 'Paramètres invalides']);
    exit;
}

if ($vote_type === 'down' && empty(trim($comment))) {
    echo json_encode(['error' => 'Un commentaire est obligatoire pour un vote négatif.']);
    exit;
}

// ✅ Vérifie que l'idée est toujours ouverte
$status_sql = "SELECT status FROM general_ideas WHERE id = ?";
$status_stmt = $conn->prepare($status_sql);
$status_stmt->bind_param("i", $idea_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$status_row = $status_result->fetch_assoc();
$status_stmt->close();

if (!$status_row || $status_row['status'] !== 'ouvert') {
    echo json_encode(['error' => 'Vous ne pouvez pas voter sur une idée fermée.']);
    exit;
}

// ✅ Insère le vote avec IP
$vote_sql = "INSERT INTO votes_general (idea_id, vote_type, ip_address) VALUES (?, ?, ?)";
$stmt = $conn->prepare($vote_sql);

if (!$stmt) {
    echo json_encode(['error' => 'Erreur préparation SQL: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iss", $idea_id, $vote_type, $ip_address);

if (!$stmt->execute()) {
    if ($stmt->errno === 1062) {
        echo json_encode(['error' => 'Vous avez déjà voté pour cette idée.']);
    } else {
        echo json_encode(['error' => 'Erreur enregistrement vote: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// 💬 Si vote négatif, insérer le commentaire
if ($vote_type === 'down') {
    $comment_sql = "INSERT INTO downvote_comments (idea_id, comment, vote_type) VALUES (?, ?, ?)";
    $comment_stmt = $conn->prepare($comment_sql);

    if (!$comment_stmt) {
        echo json_encode(['error' => 'Erreur préparation commentaire: ' . $conn->error]);
        exit;
    }

    $comment_stmt->bind_param("iss", $idea_id, $comment, $vote_type);

    if (!$comment_stmt->execute()) {
        echo json_encode(['error' => 'Erreur enregistrement commentaire: ' . $comment_stmt->error]);
        $comment_stmt->close();
        $conn->close();
        exit;
    }

    $comment_stmt->close();
}

$conn->close();
echo json_encode(['success' => true]);
?>
