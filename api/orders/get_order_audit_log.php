<?php
require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit();
}

$order_id = intval($input['order_id'] ?? 0);
if (!$order_id) {
    echo json_encode(["success" => false, "error" => "order_id is required."]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        oal.log_id,
        oal.order_id,
        oal.old_status,
        oal.new_status,
        oal.changed_at,
        u.name AS changed_by
    FROM order_audit_logs oal
    LEFT JOIN users u ON u.user_id = oal.changed_by_user_id
    WHERE oal.order_id = :order_id
    ORDER BY oal.changed_at DESC
");
$stmt->execute([':order_id' => $order_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'logs' => $logs]);
