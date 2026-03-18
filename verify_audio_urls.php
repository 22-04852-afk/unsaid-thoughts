<?php
require_once 'db_config.php';
$conn = dbConnect(true);
if ($conn->connect_error) {
    die('Connection failed');
}

$result = $conn->query('SELECT title, artist, link FROM songs LIMIT 5');
echo "âœ… SONG DATABASE VERIFICATION:\n";
echo str_repeat("=", 60) . "\n\n";

while($row = $result->fetch_assoc()) {
    echo 'âœ“ ' . $row['title'] . ' by ' . $row['artist'] . "\n";
    if ($row['link']) {
        echo '  ðŸŽµ URL: ' . substr($row['link'], 0, 50) . "...\n";
    } else {
        echo '  âŒ URL: NULL (no audio)\n';
    }
    echo "\n";
}

// Check total songs with URLs
$count = $conn->query('SELECT COUNT(*) as total FROM songs WHERE link IS NOT NULL AND link != ""')->fetch_assoc();
echo str_repeat("=", 60) . "\n";
echo "ðŸ“Š Songs with audio URLs: " . $count['total'] . "\n";

$conn->close();
?>

