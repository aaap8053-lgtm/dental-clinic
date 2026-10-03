<?php
if (session_status() === PHP_SESSION_NONE) {
    // ڕێکخستنی کووکییەکان بە شێوەیەکی تۆکمەتر بۆ پاراستنی سێشنەکان
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // چالاککردنی Secure flag ئەگەر پێگەکە بە HTTPS بڕوات
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}

// ١. پاکسازی داتاکان دژی هێرشی XSS
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

// ٢. دروستکردن و پشتڕاستکردنەوەی CSRF Token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)($token ?? ''));
}

// ٣. کۆنترۆڵکردنی چوونەژوورەوە و دەسەڵاتەکان
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireRole($allowed_roles = []) {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
        http_response_code(403);
        die('
            <div style="text-align:center; padding:50px; font-family:sans-serif; direction:rtl;">
                <h2 style="color:red;">تە دەسەڵات نینە بۆ ڤێ لاپەڕەیێ!</h2>
                <a href="../logout.php">زڤڕین</a>
            </div>
        ');
    }
}