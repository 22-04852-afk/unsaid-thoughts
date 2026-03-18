<?php
require_once 'db_config.php';
$conn = dbConnect(true);
$result = $conn->query('SELECT id, title, artist, link FROM songs LIMIT 5');
if ($result) {
    echo "Songs in database:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "Song: " . $row['title'] . " by " . $row['artist'] . "\n";
        echo "Link: " . ($row['link'] ?? 'NULL') . "\n";
        echo "---\n";
    }
} else {
    echo 'Error: ' . $conn->error . '\n';
}
$conn->close();
?>

