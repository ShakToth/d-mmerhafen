<?php
// SICHERHEITS-HEADER
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once 'functions.php';
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aushänge - Dämmerhafen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="room-mode">

<!-- TOP NAVIGATION -->
<div class="top-nav">
    <div class="nav-left">
        <a href="index.php" class="nav-wappen">
            <img src="wappen.png" alt="" style="height: 35px; margin-right: 10px;">
            <span style="font-family: var(--font-heading); font-size: 1.3rem;">Dämmerhafen</span>
        </a>
    </div>
    
    <div class="nav-center">
        <a href="bibliothek.php" class="nav-link">Die Bibliothek</a>
        <a href="miliz.php" class="nav-link">Die Miliz</a>
        <a href="verwaltung.php" class="nav-link">Die Verwaltung</a>
        <a href="aushaenge.php" class="nav-link" style="color: var(--accent-gold);">Aushänge</a>
    </div>
    
    <div class="nav-right">
        <?php if (isAdmin()): ?>
            <span class="admin-badge">🔑 Meister</span>
            <a href="index.php?action=logout" class="btn-logout">Abmelden</a>
        <?php else: ?>
            <form method="post" action="index.php" style="margin:0; display:flex; gap:10px; align-items:center;">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="password" name="login_pw" placeholder="Zauberwort..." class="nav-input">
                <button type="submit" class="nav-btn">Login</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- VOLLBILD HINTERGRUNDBILD -->
<img src="aushaenge.jpg" alt="Aushänge" class="fullscreen-bg">

<!-- HOTSPOTS FÜR AUSHÄNGE -->
<!-- Diese kannst du später hinzufügen -->

<!-- Beispiel: Zurück Button als Hotspot -->
<a href="index.php" class="fullscreen-hotspot hotspot" style="top: 10%; left: 10%; width: 15%; height: 8%;">
    <span class="hotspot-label">⌂ Raum verlassen</span>
</a>

</body>
</html>
