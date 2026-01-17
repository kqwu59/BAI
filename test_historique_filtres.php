<?php
require_once 'config.php';

$theme = $_GET['theme'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM general_ideas WHERE 1=1";
$params = [];
$types = '';

if (!empty($theme)) {
    $sql .= " AND theme = ?";
    $params[] = $theme;
    $types .= 's';
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY submission_date DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$ideas = [];

while ($row = $result->fetch_assoc()) {
    $ideas[] = $row;
}

header('Content-Type: application/json');
echo json_encode($ideas);

$stmt->close();
$conn->close();
?>
