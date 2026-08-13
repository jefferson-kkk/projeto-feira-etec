<?php
require_once __DIR__ . '/../../Controller/Controller.php';

session_start();

$controller = new LoginController();

if (empty($_SESSION['user_id'])) {
    header('Location: acesso.php');
    exit;
}

$currentUser = $controller->getLoginById($_SESSION['user_id']);

if (!$currentUser) {
    session_unset();
    session_destroy();
    header('Location: acesso.php');
    exit;
}

function escapeHtml($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function avatarPath($currentUser) {
    if ($currentUser && $currentUser->getavatar()) {
        $filename = basename($currentUser->getavatar());
        $filePath = __DIR__ . '/../../uploads/avatars/' . $filename;
        if (is_file($filePath)) {
            return '../../uploads/avatars/' . $filename;
        }
    }
    return null;
}

function userInitials($name) {
    $parts = preg_split('/\s+/', trim($name));
    if (!$parts || trim($name) === '') {
        return 'U';
    }
    $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
    $toupper = function_exists('mb_strtoupper') ? 'mb_strtoupper' : 'strtoupper';
    $initials = $substr($parts[0], 0, 1);
    if (count($parts) > 1) {
        $last = end($parts);
        $initials .= $substr($last, 0, 1);
    }
    return $toupper($initials);
}
