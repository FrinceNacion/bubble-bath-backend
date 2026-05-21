<?php
require_once __DIR__ . '/../../config/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || empty($input['tracking_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tracking ID is required."]);
    exit();
}

$trackingId = trim($input['tracking_id']);

try {
    // Lookup the order safely
    $stmt = $pdo->prepare("
        SELECT 
            order_id,
            tracking_id, 
            status, 
            DATE(order_date) as order_date, 
            DATE(pickup_date) as estimated_completion 
        FROM orders 
        WHERE tracking_id = :tracking_id
    ");
    $stmt->bindParam(":tracking_id", $trackingId);
    $stmt->execute();

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Order not found."]);
        exit();
    }

    $garmentsStmt = $pdo->prepare("SELECT type, service, quantity FROM garments WHERE order_id = :order_id");
    $garmentsStmt->bindParam(":order_id", $order['order_id'], PDO::PARAM_INT);
    $garmentsStmt->execute();

    $items = $garmentsStmt->fetchAll(PDO::FETCH_ASSOC);

    $responseData = [
        "tracking_id" => $order['tracking_id'],
        "status" => $order['status'],
        "order_date" => $order['order_date'],
        "estimated_completion" => $order['estimated_completion'],
        "items" => $items
    ];

    echo json_encode(["success" => true, "data" => $responseData]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "An error occurred while tracking the order."]);
}
