<?php
// config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'idea_user');
define('DB_PASS', 'zN-6PE`Nw.mU}p{#[!');
define('DB_NAME', 'idea_voting');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connexion échouée: ' . $conn->connect_error]);
    exit;
}
?>
