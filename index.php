<?php
require 'db.php';

$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);
        $success_msg = "Your message has been sent successfully! Thank you for reaching out.";
    }
}

// Hero/bio content
$bio = [];
$bioRows = $pdo->query("SELECT * FROM site_content")->fetchAll(PDO::FETCH_ASSOC);
foreach ($bioRows as $row) {
    $bio[$row['content_key']] = $row['content_value'];
}

// Site-wide default accent color, set by the admin. Visitors can still
// override it for themselves for the current browsing session (see main.js).
$defaultAccent = $bio['default_accent'] ?? 'blue';

// Split the hero name so the first name can be styled/typed distinctly
$heroNameRaw = $bio['hero_name'] ?? '';
$heroNameParts = explode(' ', $heroNameRaw, 2);
$heroFirst = $heroNameParts[0] ?? '';
$heroRest = isset($heroNameParts[1]) ? ' ' . $heroNameParts[1] : '';

// Skills
$skills = $pdo->query("SELECT * FROM skills ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Projects
$projects_stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent="<?= htmlspecialchars($defaultAccent) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($bio['hero_name'] ?? 'Jumana Ahmad Al-Thaibi') ?> | Portfolio</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="assets/img/newicon.ico" type="image/x-icon">
</head>
<body>
    <div class="site">
        <!-- Ambient interactive background -->
        <div class="bg-orbs" aria-hidden="true">
            <span class="bg-orb bg-orb--1" data-depth="1"></span>
            <span class="bg-orb bg-orb--2" data-depth="1.6"></span>
            <span class="bg-orb bg-orb--3" data-depth="0.8"></span>
        </div>

        <!-- Top bar -->
        <div class="window-header cursor-glow">
            <span class="window-title">
                <span class="path-current">~</span><span class="path-sep">/</span><span class="name-accent">jumana</span><span class="path-sep">/</span><span class="path-current">portfolio</span>
            </span>
            <div class="window-controls">
                <div class="accent-picker">
                    <button type="button" class="theme-toggle accent-toggle" id="accentToggle" aria-label="Choose accent color" title="Accent color (just for you, this visit)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path></svg>
                    </button>
                    <div class="accent-menu" id="accentMenu" role="menu">
                        <button type="button" class="accent-swatch" data-accent="pink" aria-label="Pink"></button>
                        <button type="button" class="accent-swatch" data-accent="yellow" aria-label="Yellow"></button>
                        <button type="button" class="accent-swatch" data-accent="purple" aria-label="Purple"></button>
                        <button type="button" class="accent-swatch" data-accent="blue" aria-label="Light blue"></button>
                        <button type="button" class="accent-swatch" data-accent="green" aria-label="Green"></button>
                    </div>
                </div>
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>
            </div>
        </div>

        <div class="window-body">
            <!-- Navigation Bar -->
            <nav class="navbar">
                <a href="#about">About</a>
                <a href="#skills">Skills & Experience</a>
                <a href="#projects">Projects</a>
                <a href="#contact">Contact</a>
            </nav>

            <!-- Hero Section -->
            <header id="about" class="hero-section cursor-glow">
                <div class="hero-content">
                    <h1 id="heroName" aria-label="<?= htmlspecialchars($heroNameRaw) ?>" data-first="<?= htmlspecialchars($heroFirst) ?>" data-rest="<?= htmlspecialchars($heroRest) ?>"><span class="name-accent" id="heroNameFirst" aria-hidden="true"></span><span id="heroNameRest" aria-hidden="true"></span><span class="typing-cursor" aria-hidden="true"></span></h1>
                    <p class="subtitle"><?= htmlspecialchars($bio['hero_subtitle'] ?? '') ?></p>
                    <p class="bio"><?= nl2br(htmlspecialchars($bio['hero_bio'] ?? '')) ?></p>
                </div>
            </header>

            <!-- Skills & Experience Section -->
            <section id="skills" class="content-section">
                <h2>Skills & Experience</h2>
                <div class="grid-container">
                    <?php foreach ($skills as $s): ?>
                        <div class="card cursor-glow">
                            <h3><span class="icon-badge"><?= htmlspecialchars($s['icon']) ?></span> <?= htmlspecialchars($s['title']) ?></h3>
                            <p><?= htmlspecialchars($s['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Projects Section -->
            <section id="projects" class="content-section">
                <h2>Featured Projects</h2>
                <div class="projects-grid">
                    <?php foreach ($projects as $p): ?>
                        <div class="project-card cursor-glow">
                            <h3><?= htmlspecialchars($p['title']) ?></h3>
                            <p><?= htmlspecialchars($p['description']) ?></p>
                            <?php if (!empty($p['tech_stack'])): ?>
                                <span class="tech-chip"><?= htmlspecialchars($p['tech_stack']) ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($p['project_link'])): ?>
                                <p style="margin-top:8px;"><a href="<?= htmlspecialchars($p['project_link']) ?>" target="_blank" style="font-size:13px;">View project</a></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Contact Section -->
            <section id="contact" class="content-section contact-section">
                <h2>Get in Touch</h2>
                <p class="alt-contact">Prefer email? Reach me directly at <a href="mailto:jumthaibi@gmail.com" class="alt-contact-link">jumthaibi@gmail.com</a></p>
                <?php if (!empty($success_msg)): ?>
                    <p class="success-alert"><?= htmlspecialchars($success_msg) ?></p>
                <?php endif; ?>
                <form action="#contact" method="POST" class="contact-form contact-form--wide">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <input type="email" name="email" placeholder="Your Email" required>
                    <textarea name="message" rows="4" placeholder="Write your message here..." required></textarea>
                    <button type="submit" name="send_message" class="glossy-btn">Send Message</button>
                </form>
            </section>
        </div>
    </div>

    <script src="main.js"></script>
</body>
</html>