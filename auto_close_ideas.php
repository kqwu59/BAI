<?php
require_once 'config.php';

$recipient_email = "assistance.dr18@cnrs.fr";
$sender_email = "noreply.boiteaidee@cnrs.fr";

$sql = "SELECT * FROM general_ideas WHERE status = 'ouvert' AND created_at <= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $title = $row['title'];
        $theme = $row['theme'];
        $created_at = $row['created_at'];
        $description = $row['description'];

        $conn->query("UPDATE general_ideas SET status = 'clos', closed_at = NOW() WHERE id = $id");

        $subject = "Clôture automatique de l'idée : $title";
        $message = "L'idée suivante a été automatiquement clôturée :\n\n" .
                   "Titre : $title\n" .
                   "Thème : $theme\n" .
                   "Date de création : $created_at\n" .
                   "Description :\n$description\n";

        $headers = "From: $sender_email\r\n" .
                   "Reply-To: $sender_email\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        mail($recipient_email, $subject, $message, $headers);
    }
}
$conn->close();
?>
