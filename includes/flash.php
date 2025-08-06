<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="alert alert-' . htmlspecialchars($f['type']) . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($f['msg']) .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return '';
}