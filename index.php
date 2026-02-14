<?php
// SICHERHEITS-HEADER
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once 'functions.php';

$message = null;

// --- ACTIONS ---
if (isset($_POST['login_pw']) && isset($_POST['csrf_token'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $result = login($_POST['login_pw']);
        if ($result['success']) {
            header("Location: index.php");
            exit;
        } else {
            $message = ['type' => 'error', 'text' => $result['message']];
        }
    } else {
        $message = ['type' => 'error', 'text' => '🚫 Ungültige Anfrage (CSRF)!'];
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header("Location: index.php");
    exit;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta name="robots" content="noindex, nofollow">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dämmerhafen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="room-mode">

<!-- TOP NAVIGATION -->
<div class="top-nav">
    <div class="nav-left">
        <a href="index.php" class="nav-wappen" style="color: var(--accent-gold);">
            <img src="wappen.png" alt="" style="height: 35px; margin-right: 10px;">
            <span style="font-family: var(--font-heading); font-size: 1.3rem;">Dämmerhafen</span>
        </a>
    </div>
    
    <div class="nav-center">
        <a href="bibliothek.php" class="nav-link">Die Bibliothek</a>
        <a href="miliz.php" class="nav-link">Die Miliz</a>
        <a href="verwaltung.php" class="nav-link">Die Verwaltung</a>
        <a href="aushaenge.php" class="nav-link">Aushänge</a>
    </div>
    
    <div class="nav-right">
        <?php if (isAdmin()): ?>
            <span class="admin-badge">🔑 Meister</span>
            <a href="?action=logout" class="btn-logout">Abmelden</a>
        <?php else: ?>
            <form method="post" style="margin:0; display:flex; gap:10px; align-items:center;">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="password" name="login_pw" placeholder="Zauberwort..." class="nav-input">
                <button type="submit" class="nav-btn">Login</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- VOLLBILD HINTERGRUNDBILD -->
<img src="dammerhafen.jpg" alt="Dämmerhafen" class="fullscreen-bg">

<!-- HOTSPOTS FÜR DÄMMERHAFEN -->
<!-- Erstellt mit Hotspot-Tool -->

<!-- 📚 Die Bibliothek -->
<a href="bibliothek.php" class="fullscreen-hotspot hotspot" style="top: 33.1144%; left: 45.2857%; width: 5%; height: 5%;">
    <span class="hotspot-label">📚 Die Bibliothek</span>
</a>

<!-- ⚔️ Die Miliz -->
<a href="miliz.php" class="fullscreen-hotspot hotspot" style="top: 38.2211%; left: 45.2143%; width: 5%; height: 5%;">
    <span class="hotspot-label">⚔️ Die Miliz</span>
</a>

<!-- 📋 Die Verwaltung -->
<a href="verwaltung.php" class="fullscreen-hotspot hotspot" style="top: 33.8245%; left: 51.8571%; width: 5%; height: 5%;">
    <span class="hotspot-label">📋 Die Verwaltung</span>
</a>

<!-- 📌 Aushänge -->
<a href="aushaenge.php" class="fullscreen-hotspot hotspot" style="top: 39.3583%; left: 52%; width: 5%; height: 5%;">
    <span class="hotspot-label">📌 Aushänge</span>
</a>

<?php if ($message): ?>
    <div style="position:fixed; top:100px; left:50%; transform:translateX(-50%); z-index:200;" class="msg <?php echo $message['type']; ?>">
        <?php echo $message['text']; ?>
    </div>
<?php endif; ?>

</body>
</html>
