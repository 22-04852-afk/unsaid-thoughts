<?php
/**
 * Create Thought Page
 * Displays form (GET) and processes form submissions (POST)
 */

// Set timezone for consistent timestamp handling
date_default_timezone_set('Asia/Manila');

// Include session config for user tracking
require_once 'config_session.php';
require_once 'db_config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to database
        $conn = dbConnect(true);
        
        $conn->set_charset("utf8mb4");
        
        // Get current user ID
        $current_user_id = getCurrentUserId();
        
        // Get and validate input
        $content = trim($_POST['content'] ?? '');
        $mood = trim($_POST['mood'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $song_title = trim($_POST['song_title'] ?? '');
        $song_artist = trim($_POST['song_artist'] ?? '');
        
        // Validate content (required)
        if (empty($content)) {
            throw new Exception('Thought content is required');
        }
        
        if (strlen($content) > 5000) {
            throw new Exception('Thought content is too long (max 5000 characters)');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Insert thought with user_id
        $stmt = $conn->prepare("
            INSERT INTO thoughts (user_id, content, mood, nickname)
            VALUES (?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        
        $mood_val = $mood ?: null;
        $nickname_val = $nickname ?: null;
        
        $stmt->bind_param("ssss", $current_user_id, $content, $mood_val, $nickname_val);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert thought: ' . $stmt->error);
        }
        
        $thought_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert song if provided
        if (!empty($song_title) && !empty($song_artist)) {
            $song_link = trim($_POST['song_link'] ?? '');
            $song_link = $song_link ?: null;
            
            $song_stmt = $conn->prepare("
                INSERT INTO songs (thought_id, title, artist, link)
                VALUES (?, ?, ?, ?)
            ");
            
            if (!$song_stmt) {
                throw new Exception('Prepare statement failed: ' . $conn->error);
            }
            
            $song_stmt->bind_param("isss", $thought_id, $song_title, $song_artist, $song_link);
            
            if (!$song_stmt->execute()) {
                throw new Exception('Failed to insert song: ' . $song_stmt->error);
            }
            
            $song_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        
        // Redirect on success
        header('Location: explore.php?success=1');
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        if (isset($conn)) {
            $conn->rollback();
            $conn->close();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="/favicon.jpg">
    <link rel="shortcut icon" href="/favicon.jpg">
    <title>Write a Thought - Unsaid Thoughts</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #FFF9FC 0%, #FFF5F8 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding-bottom: 2rem;
        }



        .container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .form-section {
            background: white;
            border: 2px solid #FFE5F0;
            border-radius: 16px;
            padding: 1.2rem;
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.1);
        }

        .form-title {
            font-size: 1.8rem;
            color: #FF69B4;
            margin-bottom: 0.3rem;
            font-weight: 700;
            font-family: 'Caveat', cursive;
            letter-spacing: 0.5px;
        }

        .form-subtitle {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: #FF69B4;
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
            font-family: 'Caveat', cursive;
            letter-spacing: 0.3px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1.5px solid #FFE5F0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FFB6D9;
            box-shadow: 0 0 0 3px rgba(255, 182, 217, 0.1);
            background-color: #FFFBFD;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .char-count {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.3rem;
        }

        .form-divider {
            height: 1px;
            background: #FFE5F0;
            margin: 1.5rem 0;
        }

        .optional-label {
            color: #FF69B4;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 0.3rem;
        }

        /* Song Dropdown Styles */
        .song-dropdown {
            width: 100%;
            padding: 0.8rem;
            border: 1.5px solid #FFE5F0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .song-dropdown:focus {
            outline: none;
            border-color: #FFB6D9;
            box-shadow: 0 0 0 3px rgba(255, 182, 217, 0.1);
            background-color: #FFFBFD;
        }

        .selected-song {
            background: #FFF5F8;
            border-left: 3px solid #FFB6D9;
            padding: 0.8rem;
            border-radius: 4px;
            margin-top: 0.8rem;
            display: none;
        }

        .selected-song.active {
            display: block;
        }

        .selected-song-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .selected-song-artist {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .clear-song-btn {
            background: #FFE5F0;
            color: #FF69B4;
            border: 1px solid #FFB6D9;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clear-song-btn:hover {
            background: #FFB6D9;
            color: white;
        }

        .submit-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 0.9rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit {
            background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 105, 180, 0.3);
        }

        .btn-cancel {
            background: #FFE5F0;
            color: #FF69B4;
            border: 1px solid #FFB6D9;
        }

        .btn-cancel:hover {
            background: #FFB6D9;
            color: white;
        }

        .error-message {
            background-color: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .error-message strong {
            display: block;
            margin-bottom: 0.3rem;
        }

        .info-text {
            background: #FFF5F8;
            border-left: 3px solid #FFB6D9;
            padding: 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
            line-height: 1.5;
        }



        .container {
            padding-bottom: 90px;
        }
    </style>
</head>
<body>
    <?php
        $header_title = '✦ Write a Thought';
        $header_subtitle = 'Everything you never said';
        include 'header.php';
    ?>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <strong>◆ Error:</strong>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2 class="form-title">✦ What's on your mind?</h2>
            <p class="form-subtitle">Share what you can't say out loud.</p>

            <form method="POST" action="create.php">
                <!-- Main thought content -->
                <div class="form-group">
                    <label for="content">◈ Your Thought</label>
                    <textarea 
                        id="content" 
                        name="content" 
                        placeholder="Write what you can't say.."
                        required 
                        maxlength="5000"
                        oninput="updateCharCount(this)"></textarea>
                    <div class="char-count"><span id="charCount">0</span> / 5000</div>
                </div>

                <div class="form-divider"></div>

                <!-- Song section with search -->
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label style="display: flex; align-items: center;">
                        ♫ Listening to...
                        <span class="optional-label">optional</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="song_select">Choose an Available Song</label>
                    <select id="song_select" class="song-dropdown">
                        <option value="">Loading songs...</option>
                    </select>
                </div>

                <!-- Selected song display -->
                <div class="selected-song" id="selectedSong">
                    <div class="selected-song-title" id="selectedSongTitle"></div>
                    <div class="selected-song-artist" id="selectedSongArtist"></div>
                    <button type="button" class="clear-song-btn" onclick="clearSelectedSong()">Clear Selection</button>
                </div>

                <!-- Hidden fields to store song data -->
                <input type="hidden" id="song_title" name="song_title">
                <input type="hidden" id="song_artist" name="song_artist">
                <input type="hidden" id="song_link" name="song_link">

                <div class="form-divider"></div>

                <!-- Mood and nickname -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="mood">✦ Current Mood</label>
                        <select id="mood" name="mood">
                            <option value="">How are you feeling?</option>
                            <option value="happy">😊 Happy</option>
                            <option value="sad">😢 Sad</option>
                            <option value="angry">😠 Angry</option>
                            <option value="worried">😟 Worried</option>
                            <option value="confused">😕 Confused</option>
                            <option value="calm">😌 Calm</option>
                            <option value="hopeful">🤞 Hopeful</option>
                            <option value="lost">😶 Lost</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nickname">⋆ Signed as</label>
                        <input 
                            type="text" 
                            id="nickname" 
                            name="nickname" 
                            placeholder="Anonymous (Optional)"
                            maxlength="100">
                    </div>
                </div>

                <!-- Submit buttons -->
                <div class="submit-group">
                    <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-submit">✧ Post Thought</button>
                </div>

                <div class="info-text">
                    ⊹ Your thought will be shared anonymously with the community. Be kind and authentic!
                </div>
            </form>
        </div>
    </div>

    <script>
        // Prevent double form submission
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = '⏳ Posting...';
                    submitButton.style.opacity = '0.6';
                    submitButton.style.cursor = 'not-allowed';
                }
            });
        }

        const songSelect = document.getElementById('song_select');
        let allSongs = [];

        function updateCharCount(textarea) {
            const count = textarea.value.length;
            document.getElementById('charCount').textContent = count;
        }

        // Load all songs on page load
        async function loadAllSongs() {
            try {
                const response = await fetch(`search_songs.php?all=1`);
                const data = await response.json();
                allSongs = Array.isArray(data.results) ? data.results : [];
                populateSongDropdown(allSongs);
            } catch (error) {
                console.error('Error loading songs:', error);
                populateSongDropdown([]);
            }
        }

        function populateSongDropdown(songs) {
            songSelect.innerHTML = '<option value="">Select a song (optional)</option>';

            if (!songs.length) {
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'No available songs';
                emptyOption.disabled = true;
                songSelect.appendChild(emptyOption);
                return;
            }

            const TOP_PICKS_COUNT = 5;
            const topPicks = songs.slice(0, Math.min(TOP_PICKS_COUNT, songs.length));

            if (topPicks.length) {
                const topPicksGroup = document.createElement('optgroup');
                topPicksGroup.label = 'Top picks';

                topPicks.forEach((song, index) => {
                    const option = document.createElement('option');
                    option.value = String(index);
                    option.textContent = `${song.title} - ${song.artist}`;
                    topPicksGroup.appendChild(option);
                });

                songSelect.appendChild(topPicksGroup);
            }

            const remainingSongs = songs.slice(topPicks.length);
            if (remainingSongs.length) {
                const allSongsGroup = document.createElement('optgroup');
                allSongsGroup.label = 'All songs';

                remainingSongs.forEach((song, index) => {
                    const option = document.createElement('option');
                    option.value = String(index + topPicks.length);
                    option.textContent = `${song.title} - ${song.artist}`;
                    allSongsGroup.appendChild(option);
                });

                songSelect.appendChild(allSongsGroup);
            }
        }

        songSelect.addEventListener('change', function() {
            if (this.value === '') {
                clearSelectedSong(false);
                return;
            }

            const song = allSongs[Number(this.value)];
            if (!song) {
                return;
            }
            
            document.getElementById('song_title').value = song.title;
            document.getElementById('song_artist').value = song.artist;
            document.getElementById('song_link').value = song.url || '';
            
            // Show selected song
            document.getElementById('selectedSongTitle').textContent = song.title;
            document.getElementById('selectedSongArtist').textContent = song.artist;
            document.getElementById('selectedSong').classList.add('active');

            this.blur();
        });

        function clearSelectedSong(shouldFocus = true) {
            document.getElementById('song_title').value = '';
            document.getElementById('song_artist').value = '';
            document.getElementById('song_link').value = '';
            document.getElementById('selectedSong').classList.remove('active');

            if (songSelect) {
                songSelect.value = '';
                if (shouldFocus) {
                    songSelect.focus();
                }
            }
        }

        // Load all songs when page loads
        window.addEventListener('load', function() {
            loadAllSongs();
        });
    </script>

    <?php include 'nav.php'; ?>
</body>
</html>
