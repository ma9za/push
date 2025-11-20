<?php
require 'config.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_key') {
    $pk = getSetting($pdo, 'public_key');
    echo json_encode(['publicKey' => $pk ?: '']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['endpoint'])) {
        try {
            // محاولة تخمين نوع الجهاز من الـ endpoint
            $device = 'Desktop/Chrome';
            if (strpos($input['endpoint'], 'google') !== false && strpos($input['endpoint'], 'android') !== false) $device = 'Android';
            if (strpos($input['endpoint'], 'apple') !== false) $device = 'iOS/Safari';

            $stmt = $pdo->prepare("INSERT OR IGNORE INTO subscribers (endpoint, p256dh, auth, device_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $input['endpoint'],
                $input['keys']['p256dh'],
                $input['keys']['auth'],
                $device
            ]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error']);
        }
    }
    exit;
}
?>


