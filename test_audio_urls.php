<?php
require_once 'db_config.php';
echo "ðŸ” Testing Audio URLs...\n\n";

$urls = [
    'https://www.sample-videos.com/audio/mp3/crowd-cheering.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
];

foreach ($urls as $url) {
    echo "Testing: $url\n";
    
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $headers = @get_headers($url, 1, $context);
    
    if ($headers && isset($headers[0])) {
        if (strpos($headers[0], '200') !== false) {
            echo "  âœ… Working (200 OK)\n";
        } else {
            echo "  âŒ Failed: " . $headers[0] . "\n";
        }
    } else {
        echo "  âŒ No response\n";
    }
    echo "\n";
}

// Check what's in database
echo "\n" . str_repeat("=", 60) . "\n";
echo "Current URLs in database:\n\n";

$conn = dbConnect(true);
$result = $conn->query("SELECT DISTINCT link FROM songs LIMIT 3");

while ($row = $result->fetch_assoc()) {
    echo "â€¢ " . substr($row['link'], 0, 70) . "...\n";
}

$conn->close();
?>

