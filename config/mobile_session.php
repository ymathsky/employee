<?php
/**
 * Mobile session restoration helper.
 * Auto-prepended before every API file via .htaccess.
 * If the request contains an X-Session-Token header, we call session_id()
 * BEFORE session_start() so PHP restores the correct session.
 */
$token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? '';
if ($token !== '' && preg_match('/^[a-zA-Z0-9,\-]{20,128}$/', $token)) {
    session_id($token);
}
