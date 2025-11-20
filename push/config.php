<?php
// push/config.php
// منع التكرار وحل مشكلة الشاشة البيضاء
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);

    session_start();
    error_reporting(E_ALL ^ E_NOTICE);
    
    // التأكد من تحميل المكتبة
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    $db_file = __DIR__ . '/database.sqlite';

    try {
        $pdo = new PDO("sqlite:" . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // إنشاء الجداول (إذا لم تكن موجودة)
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (key_name TEXT PRIMARY KEY, key_value TEXT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password_hash TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS subscribers (id INTEGER PRIMARY KEY AUTOINCREMENT, endpoint TEXT UNIQUE, p256dh TEXT, auth TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, device_type TEXT DEFAULT 'Unknown')");
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications_log (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, body TEXT, url TEXT, status TEXT, sent_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }

    // تغليف الدالة لمنع إعادة التعريف
    if (!function_exists('getSetting')) {
        function getSetting($pdo, $key) {
            $stmt = $pdo->prepare("SELECT key_value FROM settings WHERE key_name = ?");
            $stmt->execute([$key]);
            return $stmt->fetchColumn();
        }
    }
}
?>


