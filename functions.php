<?php
// DIREKTER AUFRUF VERBOTEN
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>Direct access to this file is not allowed.</p></body></html>');
}

// SICHERHEITS-EINSTELLUNGEN
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Session-Sicherheit - VOR session_start() konfigurieren
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
// Falls HTTPS (empfohlen!):
// ini_set('session.cookie_secure', 1);

session_start();

// --- KONFIGURATION ---
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('FORBIDDEN_DIR', __DIR__ . '/uploads/verboten/');
define('ADMIN_PASSWORD', 'C6%p\I{6l*6O£#3#'); // Wird nicht verwendet, siehe ADMIN_HASH
define('ADMIN_HASH', '$2a$16$E1wvy/.QAfFKOlg83XTwKuAH5vg1ZaMuUxxwmfv7tWH0ORNaqAZlG');

// Dateien die ignoriert werden
define('IGNORE_FILES', ['.', '..', '@eaDir', 'Thumbs.db', '.DS_Store', '.htaccess', '.htaccess_synology', '.htaccess_synology_v2', '.htaccess_fallback', 'functions.php', 'functions_wow.php', 'index.php', 'index_wow.php', 'style.css', 'style_wow.css', '.git', '.gitignore', 'composer.json', 'package.json', 'test.php', 'security-test.html']);

// Upload-Sicherheit
define('MAX_FILE_SIZE', 320 * 1024 * 1024); // 320 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'txt', 'md', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'epub']);
define('ALLOWED_MIMES', [
    'application/pdf',
    'text/plain',
    'text/markdown',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/zip',
    'application/x-rar-compressed',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'video/mp4',
    'video/quicktime',
    'application/epub+zip'
]);

// Login Rate Limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5 Minuten

// Initialisierung
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(FORBIDDEN_DIR)) mkdir(FORBIDDEN_DIR, 0755, true);

// --- CSRF PROTECTION ---
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --- HELPER FUNKTIONEN ---
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// --- WOW ITEM QUALITY SYSTEM ---
function getItemQuality($filename, $isForbidden = false) {
    // Verbotene Dateien sind immer Legendary
    if ($isForbidden) {
        return 'legendary';
    }
    
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Common (Grau) - Einfache Textdateien
    if (in_array($ext, ['txt', 'md'])) {
        return 'common';
    }
    
    // Uncommon (Grün) - Office Dokumente
    if (in_array($ext, ['doc', 'docx'])) {
        return 'uncommon';
    }
    
    // Rare (Blau) - PDFs und Spreadsheets
    if (in_array($ext, ['pdf', 'xls', 'xlsx'])) {
        return 'rare';
    }
    
    // Epic (Lila) - Spezielle Formate
    if (in_array($ext, ['epub', 'zip', 'rar', 'mp4', 'mov'])) {
        return 'epic';
    }
    
    // Default: Common für Bilder und unbekannte
    return 'common';
}

function getQualityLabel($quality) {
    $labels = [
        'common' => 'Gewöhnlich',
        'uncommon' => 'Ungewöhnlich',
        'rare' => 'Selten',
        'epic' => 'Episch',
        'legendary' => 'Legendär'
    ];
    return $labels[$quality] ?? 'Gewöhnlich';
}

// --- LOGIN MIT RATE LIMITING ---
function login($inputPassword) {
    // Rate Limiting Check
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $lockoutEnd = $_SESSION['login_lockout_time'] ?? 0;
        if (time() < $lockoutEnd) {
            $remainingTime = ceil(($lockoutEnd - time()) / 60);
            return ['success' => false, 'message' => "Zu viele Versuche. Warte noch {$remainingTime} Minute(n)."];
        } else {
            // Lockout abgelaufen
            unset($_SESSION['login_attempts']);
            unset($_SESSION['login_lockout_time']);
        }
    }

    // Passwort prüfen
    if (password_verify($inputPassword, ADMIN_HASH)) {
        $_SESSION['is_admin'] = true;
        session_regenerate_id(true);
        unset($_SESSION['login_attempts']);
        unset($_SESSION['login_lockout_time']);
        return ['success' => true, 'message' => 'Login erfolgreich!'];
    } else {
        // Fehlversuch zählen
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['login_lockout_time'] = time() + LOGIN_LOCKOUT_TIME;
            return ['success' => false, 'message' => 'Zu viele Fehlversuche! Account für 5 Minuten gesperrt.'];
        }
        
        $remaining = MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts'];
        return ['success' => false, 'message' => "Falsches Zauberwort! Noch {$remaining} Versuch(e)."];
    }
}

function logout() {
    session_destroy();
}

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => '📕', 'txt' => '📜', 'md' => '📝',
        'doc' => '📘', 'docx' => '📘', 'xls' => '📊', 'xlsx' => '📊',
        'zip' => '📦', 'rar' => '📦', 'mp4' => '🎬', 'mov' => '🎬',
        'jpg' => '🖼️', 'png' => '🖼️', 'jpeg' => '🖼️', 'gif' => '🖼️', 'webp' => '🖼️',
        'epub' => '📚'
    ];
    return $icons[$ext] ?? '📄';
}

// UPLOAD MIT VALIDIERUNG
function handleUpload($fileArray, $targetCategory = 'normal') {
    if (!isAdmin()) {
        return ['type' => 'error', 'text' => '🚫 Zugriff verweigert.'];
    }

    // 1. Dateigröße prüfen
    if ($fileArray['size'] > MAX_FILE_SIZE) {
        return ['type' => 'error', 'text' => '⚠️ Datei zu groß! Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB'];
    }

    // 2. Extension prüfen
    $fileName = basename($fileArray['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['type' => 'error', 'text' => '⚠️ Dateityp nicht erlaubt! Nur: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    // 3. MIME-Type prüfen (zusätzliche Sicherheit)
    // FALLBACK: Falls finfo nicht verfügbar ist
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileArray['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_MIMES)) {
            return ['type' => 'error', 'text' => '⚠️ Ungültiger Dateityp erkannt!'];
        }
    }

    // 4. Dateiname säubern
    $fileName = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $fileName);

    // 5. Duplikat-Schutz
    $targetDir = ($targetCategory === 'forbidden') ? FORBIDDEN_DIR : UPLOAD_DIR;
    $targetPath = $targetDir . $fileName;
    
    if (file_exists($targetPath)) {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $counter = 1;
        while (file_exists($targetDir . "{$name}_{$counter}.{$ext}")) {
            $counter++;
        }
        $fileName = "{$name}_{$counter}.{$ext}";
        $targetPath = $targetDir . $fileName;
    }

    // 6. Upload durchführen
    if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
        return ['type' => 'success', 'text' => "✅ Schriftrolle '{$fileName}' erfolgreich archiviert!"];
    } else {
        return ['type' => 'error', 'text' => '❌ Fehler beim Upload. Schreibrechte prüfen!'];
    }
}

// LÖSCHEN
function handleDelete($filename, $category) {
    if (!isAdmin()) {
        return ['type' => 'error', 'text' => '🚫 Zugriff verweigert.'];
    }

    $targetDir = ($category === 'forbidden') ? FORBIDDEN_DIR : UPLOAD_DIR;
    $filePath = $targetDir . basename($filename);

    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            return ['type' => 'success', 'text' => "🔥 '{$filename}' wurde verbrannt!"];
        }
    }
    return ['type' => 'error', 'text' => '❌ Datei nicht gefunden.'];
}

// DATEIEN LADEN
function getFiles($mode = 'normal', $searchQuery = '') {
    $dir = ($mode === 'forbidden') ? FORBIDDEN_DIR : UPLOAD_DIR;
    $webPath = ($mode === 'forbidden') ? 'uploads/verboten/' : 'uploads/';

    if (!is_dir($dir)) return [];
    
    $allFiles = scandir($dir);
    $results = [];

    foreach ($allFiles as $file) {
        // 1. Ignorierte Dateien überspringen
        if (in_array($file, IGNORE_FILES)) continue;

        // 2. Ordner ignorieren
        if (is_dir($dir . $file)) continue;

        // 3. Suche anwenden
        $match = true;
        if ($searchQuery !== '') {
            $match = false;
            if (stripos($file, $searchQuery) !== false) {
                $match = true;
            } elseif (preg_match('/\.(txt|md)$/i', $file)) {
                $content = @file_get_contents($dir . $file);
                if ($content && stripos($content, $searchQuery) !== false) {
                    $match = true;
                }
            }
        }

        if ($match) {
            $filePath = $dir . $file;
            $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
            $isForbidden = ($mode === 'forbidden');
            
            $results[] = [
                'name' => $file,
                'path' => $webPath . rawurlencode($file),
                'icon' => getFileIcon($file),
                'is_image' => $isImage,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'quality' => getItemQuality($file, $isForbidden)
            ];
        }
    }
    return $results;
}

// KATEGORIEÜBERGREIFENDE SUCHE
function getAllFiles($searchQuery = '') {
    $normalFiles = getFiles('normal', $searchQuery);
    $forbiddenFiles = getFiles('forbidden', $searchQuery);
    
    // Markiere die Herkunft
    foreach ($normalFiles as &$file) {
        $file['category'] = 'normal';
        $file['category_label'] = '📚 Normal';
    }
    foreach ($forbiddenFiles as &$file) {
        $file['category'] = 'forbidden';
        $file['category_label'] = '⛔ Verboten';
    }
    
    return array_merge($normalFiles, $forbiddenFiles);
}

// HELPER: Dateigröße formatieren
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

// HELPER: Datum formatieren
function formatDate($timestamp) {
    return date('d.m.Y H:i', $timestamp);
}
?>
