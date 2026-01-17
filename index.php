<?php
require_once 'config.php';

$view = $_GET['view'] ?? 'idees';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

$where_clause = ($view === 'historique_idees') ? "WHERE gi.status = 'clos'" : "WHERE gi.status != 'clos'";
$order_by = ($view === 'historique_idees') ? "ORDER BY gi.closed_at DESC" : "ORDER BY gi.created_at DESC";

$sql_ideas = "SELECT
    gi.*,
    (SELECT COUNT(*) FROM votes_general WHERE idea_id = gi.id AND vote_type = 'up') as up_votes,
    (SELECT COUNT(*) FROM votes_general WHERE idea_id = gi.id AND vote_type = 'down') as down_votes,
    (SELECT GROUP_CONCAT(dc.comment SEPARATOR ' || ') FROM downvote_comments dc WHERE dc.idea_id = gi.id) as down_vote_comments,
    (SELECT vote_type FROM votes_general WHERE idea_id = gi.id AND ip_address = '$ip_address') as user_vote
FROM general_ideas gi
$where_clause";

if ($view === 'historique_idees') {
    $filters = [];
    if (!empty($_GET['theme_filter'])) {
        $theme_filter = $conn->real_escape_string($_GET['theme_filter']);
        $filters[] = "gi.theme = '$theme_filter'";
    }
    if (!empty($_GET['date_filter'])) {
        $date_filter = $conn->real_escape_string($_GET['date_filter']);
        $filters[] = "YEAR(gi.closed_at) = YEAR('$date_filter')";
    }
    if (count($filters) > 0) {
        $sql_ideas .= ' AND ' . implode(' AND ', $filters);
    }
}

$sql_ideas .= " $order_by";
$result_ideas = $conn->query($sql_ideas);

$themes = [
    "Conseil de service",
    "Idées générales",
    "Mardi SSI",
    "QSE",
    "QVT"
];
sort($themes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="default-src 'unsafe-inline' https://bai.ad.dr18.cnrs.fr; style-src 'self' 'unsafe-inline'  https://bai.ad.dr18.cnrs.fr;">
    <title>Boîte à Idées</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1>Boîte à Idées</h1>
    <nav>
        <a href="?view=idees" class="<?= $view === 'idees' ? 'active' : '' ?>">Proposition d'idées</a>
        <a href="?view=historique_idees" class="<?= $view === 'historique_idees' ? 'active' : '' ?>">Historique Idées</a>
    </nav>
</header>
<main>

<?php if ($view === 'idees'): ?>
<section>
    <button id="toggle-idea-form">Proposer une Idée</button>
    <div id="idea-form-container" style="display: none;">
        <form method="post" action="submit_general_idea.php">
            <input type="text" name="title" placeholder="Titre de l'idée" required>
            <textarea name="description" placeholder="Description" required></textarea>
            <select name="theme" required>
                <option value="">-- Choisir un thème --</option>
                <?php foreach ($themes as $theme): ?>
                    <option value="<?= htmlspecialchars($theme) ?>"><?= htmlspecialchars($theme) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Soumettre</button>
        </form>
    </div>

    <h3>Suggestions en cours</h3>
    <ul>
    <?php if ($result_ideas && $result_ideas->num_rows > 0):
        while($row = $result_ideas->fetch_assoc()):
            $total = max($row["up_votes"] + $row["down_votes"], 1);
            $up_pct = ($row["up_votes"] / $total) * 100;
            $down_pct = ($row["down_votes"] / $total) * 100;
    ?>
        <li class="general-idea">
            <div class="idea-title"><?= htmlspecialchars($row["title"]) ?></div>
            <div class="idea-description"><?= nl2br(htmlspecialchars($row["description"])) ?></div>
            <div class="idea-meta">
                Créée le : <?= htmlspecialchars($row["created_at"]) ?> | Thème : <?= htmlspecialchars($row["theme"]) ?>
            </div>

            <div class="vote-counts">
                👍 <?= $row["up_votes"] ?> | 👎 <?= $row["down_votes"] ?>
            </div>

            <div class="vote-bar">
                <div class="positive" style="width: <?= $up_pct ?>%"></div>
                <div class="negative" style="width: <?= $down_pct ?>%"></div>
            </div>

            <?php if (!$row['user_vote'] && $row['status'] !== 'clos'): ?>
                <div class="vote-buttons">
                    <button onclick="submitVote('up', <?= $row['id'] ?>)">👍</button>
                    <button onclick="document.getElementById('comment-<?= $row['id'] ?>').style.display='block'">👎</button>
                </div>
                <div class="comment-box" id="comment-<?= $row['id'] ?>">
                    <textarea id="comment-text-<?= $row['id'] ?>" placeholder="Commentaire requis"></textarea>
                    <button onclick="submitVote('down', <?= $row['id'] ?>)">Soumettre</button>
                </div>
            <?php endif; ?>

            <?php if (!empty($row["down_vote_comments"])): ?>
                <button class="toggle-comments" onclick="toggleComments('dc<?= $row['id'] ?>')">Afficher les commentaires</button>
                <div id="dc<?= $row['id'] ?>" class="down-comments">
                    <?php foreach (explode(' || ', $row["down_vote_comments"]) as $c): ?>
                        - <?= htmlspecialchars($c) ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </li>
    <?php endwhile; else: ?>
        <li>Aucune idée disponible.</li>
    <?php endif; ?>
    </ul>
</section>

<?php elseif ($view === 'historique_idees'): ?>

<section>
    <h2>Historique des Idées</h2>
    <form method="get">
        <input type="hidden" name="view" value="historique_idees">
        <select name="theme_filter">
            <option value="">-- Tous les thèmes --</option>
            <?php foreach ($themes as $theme): ?>
                <option value="<?= htmlspecialchars($theme) ?>" <?= ($_GET['theme_filter'] ?? '') === $theme ? 'selected' : '' ?>><?= htmlspecialchars($theme) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_filter" value="<?= htmlspecialchars($_GET['date_filter'] ?? '') ?>">
        <button type="submit">Filtrer</button>
    </form>
    <ul>
    <?php if ($result_ideas && $result_ideas->num_rows > 0):
        // Reset pointer for reuse
        mysqli_data_seek($result_ideas, 0);
        while($row = $result_ideas->fetch_assoc()):
            $total = max($row["up_votes"] + $row["down_votes"], 1);
            $up_pct = ($row["up_votes"] / $total) * 100;
            $down_pct = ($row["down_votes"] / $total) * 100;
    ?>
        <li class="general-idea">
            <div class="idea-title"><?= htmlspecialchars($row["title"]) ?></div>
            <div class="idea-description"><?= nl2br(htmlspecialchars($row["description"])) ?></div>
            <div class="idea-meta">
                Créée le : <?= htmlspecialchars($row["created_at"]) ?> | Clôturée le : <?= htmlspecialchars($row["closed_at"]) ?> | Thème : <?= htmlspecialchars($row["theme"]) ?>
            </div>
            <div class="vote-counts">
                👍 <?= $row["up_votes"] ?> | 👎 <?= $row["down_votes"] ?>
            </div>
            <div class="vote-bar">
                <div class="positive" style="width: <?= $up_pct ?>%"></div>
                <div class="negative" style="width: <?= $down_pct ?>%"></div>
            </div>
        </li>
    <?php endwhile; else: ?>
        <li>Aucune idée dans l'historique.</li>
    <?php endif; ?>
    </ul>
</section>

<?php endif; ?>

</main>

<script>
function toggleComments(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = (el.style.display === 'block') ? 'none' : 'block';
}

function submitVote(type, ideaId) {
    const formData = new FormData();
    formData.append('idea_id', ideaId);
    formData.append('vote_type', type);
    if (type === 'down') {
        const comment = document.getElementById('comment-text-' + ideaId).value;
        if (!comment.trim()) {
            alert('Un commentaire est requis pour un vote négatif.');
            return;
        }
        formData.append('comment', comment);
    }
    fetch('vote_general.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erreur inconnue');
        }
    })
    .catch(() => alert('Erreur réseau'));
}

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.down-comments').forEach(el => el.style.display = 'none');
    const toggleBtn = document.getElementById('toggle-idea-form');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const form = document.getElementById('idea-form-container');
            form.style.display = (form.style.display === 'none') ? 'block' : 'none';
        });
    }
});
</script>

</body>
</html>
