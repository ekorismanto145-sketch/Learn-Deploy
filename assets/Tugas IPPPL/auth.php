<?php
require_once 'koneksi.php';

function require_login(): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header('Location: login.php');
        exit;
    }
}

function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

function is_admin(): bool
{
    return current_role() === 'Admin';
}

function is_staff(): bool
{
    return in_array(current_role(), ['Admin', 'Karyawan'], true);
}

function can_manage_transactions(): bool
{
    return current_role() === 'Karyawan';
}

function can_create_transactions(): bool
{
    return in_array(current_role(), ['Karyawan', 'User'], true);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
