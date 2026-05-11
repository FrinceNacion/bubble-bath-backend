<?php
require_once __DIR__ . '/../../config/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../middleware/authenticate.php';

require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit();
}

$order_id = $input['order_id'] ?? null;
$status = $input['status'] ?? null;

if (!$order_id || !$status) {
    echo json_encode(["success" => false, "error" => "Missing order ID or status"]);
    exit();
}

// lower case and replace space with underscore
$status = str_replace(' ', '_', strtolower($status));

try {
    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE order_id = :order_id AND deleted_at IS NULL");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':order_id', $order_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
