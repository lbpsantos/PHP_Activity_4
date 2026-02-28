<?php
// Shared session helpers for authentication and authorization.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_flash_message(string $message, string $type = 'info'): void
{
    // Persist a one-time message in the session for the next request
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function get_flash_message(): ?array
{
    // Retrieve and clear the stored flash message, if any
    if (!empty($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

function is_logged_in(): bool
{
    // Consider a user logged in when we have their id in the session
    return isset($_SESSION['user_id']);
}

function current_user(): array
{
    // Provide a consistent structure describing the active user
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'account_type' => $_SESSION['account_type'] ?? null,
    ];
}

function require_login(): void
{
    // Gate a page so only authenticated users can proceed
    if (!is_logged_in()) {
        set_flash_message('Please log in to continue.', 'error');
        header('Location: login.php');
        exit;
    }
}

function require_role(array $roles): void
{
    // Ensure the current account type matches one of the allowed roles
    require_login();
    $type = $_SESSION['account_type'] ?? '';
    if (!in_array($type, $roles, true)) {
        set_flash_message('Access denied for your role.', 'error');
        header('Location: home.php');
        exit;
    }
}

function require_admin(): void
{
    require_role(['admin']);
}

function require_staff_or_admin(): void
{
    require_role(['admin', 'staff']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['account_type'] ?? '') === 'admin';
}

function can_manage_catalog(): bool
{
    return is_logged_in() && in_array($_SESSION['account_type'] ?? '', ['admin', 'staff'], true);
}
