<?php
require 'config.php';

// السماح للموقع الرئيسي بالاتصال
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['endpoint'])) {
    try {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO subscribers (endpoint, p256dh, auth) VALUES (?, ?, ?)");
        $stmt->execute([
            $input['endpoint'],
            $input['keys']['p256dh'],
            $input['keys']['auth']
        ]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>

