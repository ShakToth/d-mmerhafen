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
    <title>Dämmerhafen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="room-mode">

<!-- TOP NAVIGATION -->
<div class="top-nav">
    <div class="nav-left">
        <a href="daemmerhafen.php" class="nav-wappen" style="color: var(--accent-gold);">
            <img src="wappen.png" alt="" style="height: 35px; margin-right: 10px;">
            <span style="font-family: var(--font-heading); font-size: 1.3rem;">Dämmerhafen</span>
        </a>
    </div>
    
    <div class="nav-center">
        <a href="index.php" class="nav-link">Die Bibliothek</a>
        <a href="miliz.php" class="nav-link">Die Miliz</a>
        <a href="verwaltung.php" class="nav-link">Die Verwaltung</a>
        <a href="aushaenge.php" class="nav-link">Aushänge</a>
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
<img src="dammerhafen.jpg" alt="Dämmerhafen" class="fullscreen-bg">

<!-- OPTIONALE HOTSPOTS FÜR SPÄTERE ERWEITERUNGEN -->
<!-- Hier kannst du später klickbare Bereiche hinzufügen -->

</body>
</html>
