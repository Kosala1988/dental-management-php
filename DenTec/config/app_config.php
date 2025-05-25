<?php
/**
 * Application Configuration
 * 
 * This file contains sensitive configuration settings.
 * KEEP THIS FILE OUTSIDE OF THE WEB ROOT DIRECTORY
 */

return [
    // API Keys
    'openweathermap_api_key' => 'b981ecc940ea5dbc5bd5a1be1772a981', // Replace with your actual API key
    
    // Database Configuration (Optional - if you prefer to keep it here instead of db_connect.php)
'database' => [
    'host' => $_ENV['MYSQLHOST'] ?? 'localhost',
    'username' => $_ENV['MYSQLUSER'] ?? 'root',
    'password' => $_ENV['MYSQLPASSWORD'] ?? '',
    'name' => $_ENV['MYSQLDATABASE'] ?? 'ddental_management'
],
    
    // Application Settings
    'site_name' => 'DenTec Clinic Management',
    'admin_email' => 'admin@dentec.com',
    'timezone' => 'Asia/Colombo',
    'debug_mode' => true,
    
    // Session Settings
    'session' => [
        'lifetime' => 3600, // 1 hour
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]
];
