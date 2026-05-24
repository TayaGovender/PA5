<?php
// includes/auth.php

function require_role(string $role): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header('Location: /tripistry/login.php');
        exit;
    }

    if ($_SESSION['role'] !== $role) {
        $redirect = $_SESSION['role'] === 'traveller'
            ? '/tripistry/traveller/dashboard.php'
            : '/tripistry/agency/dashboard.php';
        header("Location: $redirect");
        exit;
    }
}