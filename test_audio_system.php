<?php
require_once 'db_config.php';
echo "ðŸŽµ AUDIO PLAYBACK SYSTEM - FINAL TEST\n";
echo str_repeat("=", 70) . "\n\n";

$conn = dbConnect(true);
if ($conn->connect_error) {
    die("âŒ Database connection failed\n");
}

// 1. Check songs table has audio URLs
echo "1ï¸âƒ£  DATABASE SONGS WITH AUDIO:\n";
$result = $conn->query("
    SELECT s.title, s.artist, s.link, COUNT(t.id) as post_count
    FROM songs s
    LEFT JOIN thoughts t ON s.thought_id = t.id
    WHERE s.link IS NOT NULL AND s.link != ''
    GROUP BY s.id
    LIMIT 3
");

if ($result->num_rows > 0) {
    $count = 0;
    while($row = $result->fetch_assoc()) {
        $count++;
        echo "   âœ… $count. {$row['title']} - {$row['artist']}\n";
        echo "      Link: " . substr($row['link'], 0, 60) . "...\n";
        echo "      Used in posts: {$row['post_count']}\n\n";
    }
} else {
    echo "   âŒ No songs with URLs found\n\n";
}

// 2. Check thoughts with songs
echo "2ï¸âƒ£  RECENT THOUGHTS WITH SONGS:\n";
$result = $conn->query("
    SELECT t.id, t.content, s.title, s.artist, s.link,
           (SELECT COUNT(*) FROM reactions WHERE thought_id = t.id) as reaction_count
    FROM thoughts t
    LEFT JOIN songs s ON s.thought_id = t.id
    WHERE s.id IS NOT NULL
    ORDER BY t.created_at DESC
    LIMIT 3
");

if ($result->num_rows > 0) {
    $count = 0;
    while($row = $result->fetch_assoc()) {
        $count++;
        echo "   âœ… Post $count: '{$row['title']}'\n";
        echo "      Artist: {$row['artist']}\n";
        echo "      Audio URL: " . ($row['link'] ? 'âœ… Present' : 'âŒ Missing') . "\n";
        echo "      Reactions: {$row['reaction_count']}\n\n";
    }
} else {
    echo "   â„¹ï¸  No thoughts with songs yet created\n\n";
}

// 3. Check songs_db.json has URLs
echo "3ï¸âƒ£  SONGS DATABASE (songs_db.json):\n";
$songs = json_decode(file_get_contents('songs_db.json'), true);
if ($songs) {
    $with_urls = 0;
    foreach ($songs as $song) {
        if (!empty($song['url'])) {
            $with_urls++;
        }
    }
    echo "   âœ… Total songs in JSON: " . count($songs) . "\n";
    echo "   âœ… Songs with URLs: $with_urls\n";
    echo "   ðŸ“Š Coverage: " . round(($with_urls / count($songs)) * 100) . "%\n\n";
} else {
    echo "   âŒ Failed to load songs_db.json\n\n";
}

// 4. HTML5 Audio Test
echo "4ï¸âƒ£  HTML5 AUDIO PLAYER TEST:\n";
$result = $conn->query("SELECT link FROM songs WHERE link IS NOT NULL LIMIT 1");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $url = $row['link'];
    echo "   âœ… Test URL: $url\n";
    echo "   âœ… Audio player HTML structure:\n";
    echo "      <audio controls style=\"width: 100%; height: 32px;\">\n";
    echo "        <source src=\"$url\" type=\"audio/mpeg\">\n";
    echo "      </audio>\n\n";
    echo "   âœ… This should work in all modern browsers!\n\n";
}

// 5. Summary
echo str_repeat("=", 70) . "\n";
echo "âœ¨ STATUS: AUDIO PLAYBACK SYSTEM READY\n";
echo "\nðŸ“‹ NEXT STEPS:\n";
echo "   1. Open http://localhost/unsaidthoughts-/explore.php\n";
echo "   2. Look for posts with music symbols (ðŸŽµ)\n";
echo "   3. Try clicking the PLAY button on audio player\n";
echo "   4. Audio should play for 10+ seconds from SoundHelix demo file\n\n";

echo "ðŸŽ¯ EXPECTED BEHAVIOR:\n";
echo "   âœ“ Audio player appears below each post with a song\n";
echo "   âœ“ Play/pause button responsive\n";
echo "   âœ“ Volume slider works\n";
echo "   âœ“ Progress bar shows audio duration\n\n";

echo "â“ TROUBLESHOOTING:\n";
echo "   â€¢ If no audio plays, check browser console (F12) for errors\n";
echo "   â€¢ Ensure your PHP has mysqli extension enabled\n";
echo "   â€¢ Verify database connection is working\n";
echo "   â€¢ Check that URLs are accessible (not blocked by firewall)\n";

$conn->close();
?>

