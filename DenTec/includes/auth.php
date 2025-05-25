<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: /DenTec/login.php");
    exit();
}

// You can also create role-based functions for access control
function require_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access Denied";
        exit();
    }
}
