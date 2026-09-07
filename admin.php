<?php
session_start();
require 'db.php';

$error = '';

// One-time, safe-to-repeat migration: add the column that tracks whether
// an admin has already seen a message. Existing messages are backfilled as
// "read" the moment the column is created, so the badge only starts
// counting messages that arrive after this feature ships.
$colCheck = $pdo->query("SHOW COLUMNS FROM messages LIKE 'is_read'")->fetchAll();
if (count($colCheck) === 0) {
    $pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("UPDATE messages SET is_read = 1");
}

// ---------- Login ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged'] = true;
    } else {
        $error = "Invalid username or password";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged']);
    header("Location: admin.php");
    exit();
}

$isLoggedIn = isset($_SESSION['admin_logged']);

// Site-wide default accent color (what visitors see unless they pick their
// own for the current visit). Loaded regardless of login state so the admin
// page itself reflects it too.
$defaultAccentRows = $pdo->query("SELECT content_value FROM site_content WHERE content_key = 'default_accent'")->fetchAll(PDO::FETCH_ASSOC);
$defaultAccent = $defaultAccentRows[0]['content_value'] ?? 'blue';
$allowedAccents = ['pink', 'yellow', 'purple', 'blue', 'green'];

// ---------- Everything below requires login ----------
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Called via fetch() when the admin clicks the "Messages" nav link —
    // no page reload, just clears the unread badge for next time.
    if (isset($_POST['mark_messages_read'])) {
        $pdo->exec("UPDATE messages SET is_read = 1 WHERE is_read = 0");
        exit('ok');
    }

    // Set the site-wide default accent color (applies to every visitor)
    if (isset($_POST['set_default_accent'])) {
        $accentValue = $_POST['set_default_accent'];
        if (in_array($accentValue, $allowedAccents, true)) {
            $stmt = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = 'default_accent'");
            $stmt->execute([$accentValue]);
            if ($stmt->rowCount() === 0) {
                $ins = $pdo->prepare("INSERT INTO site_content (content_key, content_value) VALUES ('default_accent', ?)");
                $ins->execute([$accentValue]);
            }
        }
        header("Location: admin.php");
        exit();
    }

    // Update bio/hero text
    if (isset($_POST['update_bio'])) {
        $fields = ['hero_name' => $_POST['hero_name'], 'hero_subtitle' => $_POST['hero_subtitle'], 'hero_bio' => $_POST['hero_bio']];
        $stmt = $pdo->prepare("UPDATE site_content SET content_value = ? WHERE content_key = ?");
        foreach ($fields as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        header("Location: admin.php#bio");
        exit();
    }

    // Add a project
    if (isset($_POST['add_project'])) {
        $stmt = $pdo->prepare("INSERT INTO projects (title, description, tech_stack, project_link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['title'], $_POST['description'], $_POST['tech_stack'], $_POST['project_link']]);
        header("Location: admin.php#projects");
        exit();
    }

    // Update a project
    if (isset($_POST['update_project'])) {
        $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, tech_stack = ?, project_link = ? WHERE id = ?");
        $stmt->execute([$_POST['title'], $_POST['description'], $_POST['tech_stack'], $_POST['project_link'], $_POST['project_id']]);
        header("Location: admin.php#projects");
        exit();
    }

    // Delete a project
    if (isset($_POST['delete_project'])) {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$_POST['project_id']]);
        header("Location: admin.php#projects");
        exit();
    }

    // Add a skill
    if (isset($_POST['add_skill'])) {
        $stmt = $pdo->prepare("INSERT INTO skills (icon, title, description) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['icon'], $_POST['title'], $_POST['description']]);
        header("Location: admin.php#skills");
        exit();
    }

    // Update a skill
    if (isset($_POST['update_skill'])) {
        $stmt = $pdo->prepare("UPDATE skills SET icon = ?, title = ?, description = ? WHERE id = ?");
        $stmt->execute([$_POST['icon'], $_POST['title'], $_POST['description'], $_POST['skill_id']]);
        header("Location: admin.php#skills");
        exit();
    }

    // Delete a skill
    if (isset($_POST['delete_skill'])) {
        $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
        $stmt->execute([$_POST['skill_id']]);
        header("Location: admin.php#skills");
        exit();
    }

    // Delete a message
    if (isset($_POST['delete_message'])) {
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$_POST['message_id']]);
        header("Location: admin.php#messages");
        exit();
    }

}

// ---------- Data for the dashboard ----------
if ($isLoggedIn) {
    $bio = [];
    $bioRows = $pdo->query("SELECT * FROM site_content")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($bioRows as $row) {
        $bio[$row['content_key']] = $row['content_value'];
    }

    $skills = $pdo->query("SELECT * FROM skills ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $projects = $pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $unreadCount = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent="<?= htmlspecialchars($defaultAccent) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Jumana Portfolio</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="site">
        <!-- Ambient interactive background -->
        <div class="bg-orbs" aria-hidden="true">
            <span class="bg-orb bg-orb--1" data-depth="1"></span>
            <span class="bg-orb bg-orb--2" data-depth="1.6"></span>
            <span class="bg-orb bg-orb--3" data-depth="0.8"></span>
        </div>

        <div class="window-header cursor-glow">
            <span class="window-title">
                <span class="path-current">~</span><span class="path-sep">/</span><span class="name-accent">jumana</span><span class="path-sep">/</span><span class="path-current">admin</span>
            </span>
            <div class="window-controls">
                <div class="accent-picker">
                    <button type="button" class="theme-toggle accent-toggle" id="accentToggle" aria-label="Choose accent color" title="Site default accent color (applies to every visitor)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path></svg>
                    </button>
                    <?php if ($isLoggedIn): ?>
                    <form method="POST" class="accent-menu" id="accentMenu" role="menu">
                        <button type="submit" name="set_default_accent" value="pink" class="accent-swatch" data-accent="pink" aria-label="Pink"></button>
                        <button type="submit" name="set_default_accent" value="yellow" class="accent-swatch" data-accent="yellow" aria-label="Yellow"></button>
                        <button type="submit" name="set_default_accent" value="purple" class="accent-swatch" data-accent="purple" aria-label="Purple"></button>
                        <button type="submit" name="set_default_accent" value="blue" class="accent-swatch" data-accent="blue" aria-label="Light blue"></button>
                        <button type="submit" name="set_default_accent" value="green" class="accent-swatch" data-accent="green" aria-label="Green"></button>
                    </form>
                    <?php endif; ?>
                </div>
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>
                <?php if ($isLoggedIn): ?>
                <button type="button" class="theme-toggle logout-btn" id="logoutBtn" data-href="admin.php?logout=true" aria-label="Log out" title="Log out" onclick="return confirmLogout(event, 'Are you sure you want to log out?');">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
                <?php endif; ?>
                <a href="index.php" class="back-link">Back to site</a>
            </div>
        </div>
        <div class="window-body">
            <?php if (!$isLoggedIn): ?>
                <div class="admin-login-center">
                    <h2>Admin Login</h2>
                    <?php if ($error): ?><p class="error-alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
                    <form method="POST" class="contact-form" style="margin-top:15px;">
                        <input type="text" name="username" placeholder="Username" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <button type="submit" name="login" class="glossy-btn">Login</button>
                    </form>
                </div>

            <?php else: ?>
                <h2>Welcome Back, <span class="name-accent">Jumana</span>!</h2>

                <nav class="navbar" style="margin-top:20px;">
                    <a href="#bio">Bio</a>
                    <a href="#skills">Skills</a>
                    <a href="#projects">Projects</a>
                    <a href="#messages">Messages<?php if ($unreadCount > 0): ?> <span class="badge-unread" id="messagesBadge"><?= (int) $unreadCount ?></span><?php endif; ?></a>
                </nav>

                <!-- ===================== BIO ===================== -->
                <section id="bio" class="content-section">
                    <h3>Edit Bio / Hero Section</h3>
                    <form method="POST" class="contact-form" style="margin-top:10px;">
                        <label>Name</label>
                        <input type="text" name="hero_name" value="<?= htmlspecialchars($bio['hero_name'] ?? '') ?>" required>
                        <label>Subtitle</label>
                        <input type="text" name="hero_subtitle" value="<?= htmlspecialchars($bio['hero_subtitle'] ?? '') ?>" required>
                        <label>Bio text</label>
                        <textarea name="hero_bio" rows="4" required><?= htmlspecialchars($bio['hero_bio'] ?? '') ?></textarea>
                        <button type="submit" name="update_bio" class="glossy-btn">Save Bio</button>
                    </form>
                </section>

                <!-- ===================== SKILLS ===================== -->
                <section id="skills" class="content-section">
                    <h3>Manage Skills</h3>

                    <div class="grid-container" style="margin-top:10px;">
                        <?php foreach ($skills as $s): ?>
                            <div class="card cursor-glow">
                                <form method="POST" class="contact-form">
                                    <input type="hidden" name="skill_id" value="<?= $s['id'] ?>">
                                    <label>Icon (emoji)</label>
                                    <input type="text" name="icon" value="<?= htmlspecialchars($s['icon']) ?>" maxlength="10">
                                    <label>Title</label>
                                    <input type="text" name="title" value="<?= htmlspecialchars($s['title']) ?>" required>
                                    <label>Description</label>
                                    <textarea name="description" rows="3" required><?= htmlspecialchars($s['description']) ?></textarea>
                                    <div style="display:flex; gap:8px;">
                                        <button type="submit" name="update_skill" class="glossy-btn">Save</button>
                                        <button type="submit" name="delete_skill" class="glossy-btn glossy-btn-danger" onclick="return confirmDelete(event, 'Delete this skill?');">Delete</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h4 style="margin-top:20px;">Add New Skill</h4>
                    <form method="POST" class="contact-form" style="margin-top:10px; max-width:400px;">
                        <input type="text" name="icon" placeholder="Icon (emoji)" maxlength="10">
                        <input type="text" name="title" placeholder="Title" required>
                        <textarea name="description" rows="3" placeholder="Description" required></textarea>
                        <button type="submit" name="add_skill" class="glossy-btn">Add Skill</button>
                    </form>
                </section>

                <!-- ===================== PROJECTS ===================== -->
                <section id="projects" class="content-section">
                    <h3>Manage Projects</h3>

                    <div class="projects-grid" style="margin-top:10px;">
                        <?php foreach ($projects as $p): ?>
                            <div class="project-card cursor-glow">
                                <form method="POST" class="contact-form">
                                    <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                                    <label>Title</label>
                                    <input type="text" name="title" value="<?= htmlspecialchars($p['title']) ?>" required>
                                    <label>Description</label>
                                    <textarea name="description" rows="3" required><?= htmlspecialchars($p['description']) ?></textarea>
                                    <label>Tech stack</label>
                                    <input type="text" name="tech_stack" value="<?= htmlspecialchars($p['tech_stack'] ?? '') ?>" placeholder="e.g. PHP, MySQL">
                                    <label>Project link</label>
                                    <input type="text" name="project_link" value="<?= htmlspecialchars($p['project_link'] ?? '') ?>" placeholder="https://...">
                                    <div style="display:flex; gap:8px;">
                                        <button type="submit" name="update_project" class="glossy-btn">Save</button>
                                        <button type="submit" name="delete_project" class="glossy-btn glossy-btn-danger" onclick="return confirmDelete(event, 'Delete this project?');">Delete</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h4 style="margin-top:20px;">Add New Project</h4>
                    <form method="POST" class="contact-form" style="margin-top:10px; max-width:400px;">
                        <input type="text" name="title" placeholder="Project Title" required>
                        <textarea name="description" placeholder="Project Description" rows="3" required></textarea>
                        <input type="text" name="tech_stack" placeholder="Tech stack (e.g. PHP, MySQL)">
                        <input type="text" name="project_link" placeholder="Project link (optional)">
                        <button type="submit" name="add_project" class="glossy-btn">Add Project</button>
                    </form>
                </section>

                <!-- ===================== MESSAGES ===================== -->
                <section id="messages" class="content-section">
                    <h3>Received Messages</h3>
                    <?php if (count($messages) === 0): ?>
                        <p style="margin-top:10px;">No messages received yet.</p>
                    <?php else: ?>
                        <div class="messages-list">
                            <?php foreach ($messages as $m): ?>
                                <div class="message-item">
                                    <div class="message-header">
                                        <span class="message-from"><?= htmlspecialchars($m['name']) ?> <span class="muted">— <?= htmlspecialchars($m['email']) ?></span></span>
                                        <span class="message-date"><?= $m['created_at'] ?></span>
                                    </div>
                                    <p class="message-body"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
                                    <form method="POST">
                                        <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                        <button type="submit" name="delete_message" class="glossy-btn glossy-btn-danger" style="padding:6px 14px; font-size:12px;" onclick="return confirmDelete(event, 'Delete this message?');">Delete</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

            <?php endif; ?>
        </div>
    </div>

    <!-- Custom confirm dialog — replaces the native browser confirm() popup -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-box">
            <p id="confirmMessage">Are you sure?</p>
            <div class="confirm-actions">
                <button type="button" class="glossy-btn" id="confirmCancelBtn">Cancel</button>
                <button type="button" class="glossy-btn glossy-btn-danger" id="confirmOkBtn">Delete</button>
            </div>
        </div>
    </div>

    <script src="main.js"></script>
    <script>
        (function () {
            var overlay = document.getElementById('confirmOverlay');
            var messageEl = document.getElementById('confirmMessage');
            var okBtn = document.getElementById('confirmOkBtn');
            var cancelBtn = document.getElementById('confirmCancelBtn');
            var pendingForm = null;
            var pendingButton = null;
            var pendingHref = null;

            function openConfirm(event, opts) {
                event.preventDefault();
                var btn = event.currentTarget;
                pendingButton = btn;
                pendingHref = btn.getAttribute('href') || btn.dataset.href || null;
                pendingForm = pendingHref ? null : btn.closest('form');
                messageEl.textContent = opts.message || 'Are you sure?';
                okBtn.textContent = opts.confirmLabel || 'Delete';
                overlay.classList.add('open');
                return false;
            }

            window.confirmDelete = function (event, message) {
                return openConfirm(event, { message: message, confirmLabel: 'Delete' });
            };

            window.confirmLogout = function (event, message) {
                return openConfirm(event, { message: message, confirmLabel: 'Logout' });
            };

            function closeOverlay() {
                overlay.classList.remove('open');
                pendingForm = null;
                pendingButton = null;
                pendingHref = null;
            }

            okBtn.addEventListener('click', function () {
                if (pendingForm) {
                    if (pendingForm.requestSubmit) {
                        pendingForm.requestSubmit(pendingButton);
                    } else {
                        pendingForm.submit();
                    }
                } else if (pendingHref) {
                    window.location.href = pendingHref;
                }
                closeOverlay();
            });

            cancelBtn.addEventListener('click', closeOverlay);
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeOverlay();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay.classList.contains('open')) closeOverlay();
            });
        })();
    </script>
</body>
</html>