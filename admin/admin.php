<?php
require_once __DIR__ . '/admin_auth.php';
requireAdminAuth();

$conn = getAdminDbConnection();

$flash = '';
$flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyAdminCsrf($token)) {
        $flash = 'Security token mismatch. Please refresh and try again.';
        $flashType = 'error';
    } else {
        if ($action === 'delete') {
            $thoughtId = (int)($_POST['thought_id'] ?? 0);
            if ($thoughtId > 0) {
                $stmt = $conn->prepare('DELETE FROM thoughts WHERE id = ?');
                $stmt->bind_param('i', $thoughtId);
                $stmt->execute();
                $stmt->close();

                $flash = 'Thought deleted.';
            }
        } else {
            $flash = 'Editing posts is disabled. You can only delete posts here.';
            $flashType = 'error';
        }
    }
}

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$where = '';
$types = '';
$params = [];

if ($search !== '') {
    $where = ' WHERE (t.content LIKE ? OR t.nickname LIKE ? OR t.mood LIKE ? OR t.user_id LIKE ?)';
    $like = '%' . $search . '%';
    $types = 'ssss';
    $params = [$like, $like, $like, $like];
}

$countSql = 'SELECT COUNT(*) AS total FROM thoughts t' . $where;
$countStmt = $conn->prepare($countSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = (int)($countResult->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = '
    SELECT
        t.id,
        t.user_id,
        t.content,
        t.mood,
        t.nickname,
        t.created_at,
        s.title AS song_title,
        s.artist AS song_artist,
        s.link AS song_link,
        COUNT(r.id) AS reactions_count
    FROM thoughts t
    LEFT JOIN songs s ON s.thought_id = t.id
    LEFT JOIN reactions r ON r.thought_id = t.id
' . $where . '
    GROUP BY t.id, t.user_id, t.content, t.mood, t.nickname, t.created_at, s.title, s.artist, s.link
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
';

$stmt = $conn->prepare($sql);

if ($types !== '') {
    $bindTypes = $types . 'ii';
    $bindParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$thoughts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stats = [
    'total_thoughts' => 0,
    'today_thoughts' => 0,
    'total_reactions' => 0
];

$statsResult = $conn->query("SELECT COUNT(*) AS total_thoughts, SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_thoughts FROM thoughts");
if ($statsResult) {
    $statsRow = $statsResult->fetch_assoc();
    $stats['total_thoughts'] = (int)($statsRow['total_thoughts'] ?? 0);
    $stats['today_thoughts'] = (int)($statsRow['today_thoughts'] ?? 0);
}

$reactionStats = $conn->query('SELECT COUNT(*) AS total_reactions FROM reactions');
if ($reactionStats) {
    $reactionRow = $reactionStats->fetch_assoc();
    $stats['total_reactions'] = (int)($reactionRow['total_reactions'] ?? 0);
}

$csrfToken = adminCsrfToken();

$adminHeaderTitle = 'Admin Control Room';
$adminHeaderSubtitle = 'Review and moderate all posted thoughts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Unsaid Thoughts</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            color: var(--admin-ink);
            min-height: 100vh;
        }

        .wrap {
            width: min(1100px, 100% - 2rem);
            margin: 1rem auto 2rem;
        }

        .stats {
            margin-top: 0.9rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.6rem;
        }

        .stat-card {
            border: 1px solid var(--admin-line);
            background: var(--admin-surface);
            border-radius: var(--admin-card-radius);
            padding: 0.75rem 0.85rem;
            box-shadow: var(--admin-card-shadow);
            backdrop-filter: blur(4px);
        }

        .stat-label {
            color: var(--admin-muted);
            font-size: 0.77rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            color: var(--admin-brand-strong);
            font-size: 1.35rem;
            font-weight: 900;
        }

        .search-box {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.5rem;
            margin: 0.95rem 0 0.9rem;
        }

        .search-box input {
            border: 1px solid var(--admin-control-border);
            border-radius: 12px;
            padding: 0.72rem;
            font-size: 0.93rem;
            background: var(--admin-control-bg);
            color: var(--admin-control-text);
        }

        .search-box button {
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--admin-brand) 0%, var(--admin-brand-strong) 100%);
            color: #fff;
            font-weight: 800;
            padding: 0.7rem 1rem;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(188, 26, 112, 0.24);
        }

        .flash {
            border-radius: 12px;
            padding: 0.72rem 0.85rem;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .flash.ok {
            background: #e9fff3;
            border: 1px solid #9de0bd;
            color: #0c7d45;
        }

        .flash.error {
            background: #ffecf4;
            border: 1px solid #f2aecf;
            color: #b21769;
        }

        .cards {
            display: grid;
            gap: 0.95rem;
        }

        .card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-line);
            border-radius: var(--admin-card-radius);
            padding: 0.95rem;
            box-shadow: var(--admin-card-shadow);
            backdrop-filter: blur(4px);
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-bottom: 0.5rem;
        }

        .tag {
            background: rgba(255, 255, 255, 0.12);
            color: var(--admin-brand-strong);
            font-size: 0.75rem;
            padding: 0.24rem 0.58rem;
            border-radius: 999px;
            font-weight: 700;
            border: 1px solid var(--admin-line);
        }

        .content {
            background: var(--admin-control-bg);
            border: 1px solid var(--admin-control-border);
            border-radius: 12px;
            padding: 0.65rem;
            width: 100%;
            min-height: 92px;
            line-height: 1.5;
            color: var(--admin-control-text);
            margin-bottom: 0.45rem;
            white-space: pre-wrap;
        }

        .grid3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
            margin-bottom: 0.45rem;
        }

        .meta-item {
            border: 1px solid var(--admin-control-border);
            border-radius: 10px;
            padding: 0.56rem;
            font-size: 0.86rem;
            background: var(--admin-control-bg);
            color: var(--admin-control-text);
        }

        .meta-label {
            color: var(--admin-muted);
            font-size: 0.72rem;
            margin-bottom: 0.16rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .meta-value {
            font-size: 0.88rem;
            word-break: break-word;
        }

        .search-box input::placeholder {
            color: var(--admin-control-placeholder);
        }

        .actions {
            display: flex;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 0.52rem 0.78rem;
            font-weight: 800;
            cursor: pointer;
            font-size: 0.8rem;
            letter-spacing: 0.2px;
        }

        .btn-del {
            background: #fff2f8;
            border: 1px solid #efb0d2;
            color: #be1d72;
        }

        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(27, 9, 20, 0.55);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            padding: 1rem;
        }

        .confirm-overlay.open {
            display: flex;
        }

        .confirm-modal {
            width: min(460px, 100%);
            border-radius: 16px;
            background: var(--admin-surface);
            border: 1px solid var(--admin-line);
            box-shadow: 0 24px 44px rgba(22, 9, 17, 0.3);
            padding: 1rem;
        }

        .confirm-title {
            color: var(--admin-brand-strong);
            font-size: 1.1rem;
            font-weight: 900;
            margin-bottom: 0.3rem;
        }

        .confirm-text {
            color: var(--admin-muted);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 0.85rem;
        }

        .confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--admin-line);
            color: var(--admin-brand-strong);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff5b9f 0%, #cb2b7f 100%);
            color: #fff;
            box-shadow: 0 10px 20px rgba(186, 29, 106, 0.25);
        }

        .pager {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.1rem;
        }

        .pager a {
            text-decoration: none;
            border: 1px solid var(--admin-line);
            background: rgba(255, 255, 255, 0.92);
            color: var(--admin-brand-strong);
            border-radius: 12px;
            padding: 0.48rem 0.72rem;
            font-weight: 700;
        }

        .muted { color: var(--admin-muted); font-size: 0.78rem; }

        @media (max-width: 720px) {
            .stats { grid-template-columns: 1fr; }
            .grid3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    <?php include __DIR__ . '/nav.php'; ?>

    <div class="wrap">
        <div class="stats">
            <article class="stat-card">
                <p class="stat-label">Total Thoughts</p>
                <p class="stat-value"><?php echo number_format($stats['total_thoughts']); ?></p>
            </article>
            <article class="stat-card">
                <p class="stat-label">Posted Today</p>
                <p class="stat-value"><?php echo number_format($stats['today_thoughts']); ?></p>
            </article>
            <article class="stat-card">
                <p class="stat-label">Total Reactions</p>
                <p class="stat-value"><?php echo number_format($stats['total_reactions']); ?></p>
            </article>
        </div>

        <form class="search-box" method="GET">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search content, mood, nickname, or user id">
            <button type="submit">Search</button>
        </form>

        <?php if ($flash !== ''): ?>
            <div class="flash <?php echo $flashType === 'error' ? 'error' : 'ok'; ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="cards">
            <?php if (empty($thoughts)): ?>
                <div class="card">No thoughts found.</div>
            <?php endif; ?>

            <?php foreach ($thoughts as $thought): ?>
                <article class="card">

                    <div class="row">
                        <span class="tag">ID #<?php echo (int)$thought['id']; ?></span>
                        <span class="muted">Posted: <?php echo htmlspecialchars($thought['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="row">
                        <span class="muted">User ID: <?php echo htmlspecialchars($thought['user_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="muted">Reactions: <?php echo (int)$thought['reactions_count']; ?></span>
                    </div>

                    <div class="content"><?php echo htmlspecialchars($thought['content'], ENT_QUOTES, 'UTF-8'); ?></div>

                    <div class="grid3">
                        <div class="meta-item">
                            <p class="meta-label">Nickname</p>
                            <p class="meta-value"><?php echo htmlspecialchars((string)($thought['nickname'] ?: 'Anonymous'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="meta-item">
                            <p class="meta-label">Mood</p>
                            <p class="meta-value"><?php echo htmlspecialchars((string)($thought['mood'] ?: 'Not set'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="meta-item">
                            <p class="meta-label">Song Title</p>
                            <p class="meta-value"><?php echo htmlspecialchars((string)($thought['song_title'] ?: 'None'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                    <div class="grid3">
                        <div class="meta-item">
                            <p class="meta-label">Song Artist</p>
                            <p class="meta-value"><?php echo htmlspecialchars((string)($thought['song_artist'] ?: 'None'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="meta-item">
                            <p class="meta-label">Song Link</p>
                            <p class="meta-value"><?php echo htmlspecialchars((string)($thought['song_link'] ?: 'None'), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div></div>
                    </div>

                    <form class="actions delete-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="thought_id" value="<?php echo (int)$thought['id']; ?>">
                        <button class="btn btn-del js-open-delete" type="button" name="action" value="delete">Delete Thought</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="pager">
            <?php if ($page > 1): ?>
                <a href="admin.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="admin.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="confirm-overlay" id="deleteConfirmOverlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle">
            <h2 class="confirm-title" id="deleteConfirmTitle">Delete Thought?</h2>
            <p class="confirm-text">This will permanently delete the thought and cannot be undone.</p>
            <div class="confirm-actions">
                <button class="btn btn-cancel" type="button" id="deleteCancelBtn">Cancel</button>
                <button class="btn btn-danger" type="button" id="deleteConfirmBtn">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var overlay = document.getElementById('deleteConfirmOverlay');
            var confirmBtn = document.getElementById('deleteConfirmBtn');
            var cancelBtn = document.getElementById('deleteCancelBtn');
            var openButtons = document.querySelectorAll('.js-open-delete');
            var activeForm = null;

            if (!overlay || !confirmBtn || !cancelBtn || !openButtons.length) {
                return;
            }

            function openModal(form) {
                activeForm = form;
                overlay.classList.add('open');
                overlay.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                overlay.classList.remove('open');
                overlay.setAttribute('aria-hidden', 'true');
                activeForm = null;
            }

            openButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openModal(btn.closest('form'));
                });
            });

            confirmBtn.addEventListener('click', function () {
                if (!activeForm) {
                    closeModal();
                    return;
                }

                var hiddenAction = document.createElement('input');
                hiddenAction.type = 'hidden';
                hiddenAction.name = 'action';
                hiddenAction.value = 'delete';
                activeForm.appendChild(hiddenAction);
                activeForm.submit();
            });

            cancelBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay.classList.contains('open')) {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
