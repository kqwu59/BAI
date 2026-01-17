<?php
// Affichage des erreurs (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Récupération des données POST
$title = $_POST["title"] ?? '';
$description = $_POST["description"] ?? '';
$theme = $_POST["theme"] ?? '';

// Vérification des champs obligatoires
if (empty(trim($title)) || empty(trim($description)) || empty(trim($theme))) {
    die("Erreur : tous les champs sont requis.");
}

// Préparation de l'insertion
$stmt = $conn->prepare("INSERT INTO general_ideas (title, description, theme, status) VALUES (?, ?, ?, 'ouvert')");
if (!$stmt) {
    die("Erreur de préparation SQL : " . $conn->error);
}

$stmt->bind_param("sss", $title, $description, $theme);

// Exécution
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: index.php?view=idees");
    exit;
} else {
    die("Erreur lors de l'exécution : " . $stmt->error);
}
