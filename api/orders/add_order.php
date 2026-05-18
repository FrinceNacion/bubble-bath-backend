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

$customerId = $input['customerId'] ?? null;
$dueDate = $input['dueDate'] ?? null;
$orderAmount = $input['totalAmount'] ?? 0;
$garments = $input['garments'] ?? [];

if (!$customerId || !$dueDate || !$garments) {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit();
}

try {
    $trackingId = 'TRK-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

    $stmt = $pdo->prepare("INSERT INTO orders (tracking_id, customer_id, pickup_date, order_amount) VALUES (:tracking_id, :customer_id, :pickup_date, :order_amount)");
    $stmt->bindParam(":tracking_id", $trackingId);
    $stmt->bindParam(":customer_id", $customerId, PDO::PARAM_INT);
    $stmt->bindParam(":pickup_date", $dueDate);
    $stmt->bindParam(":order_amount", $orderAmount);
    $stmt->execute();

    $orderId = $pdo->lastInsertId();

    foreach ($garments as $garment) {
        $stmt = $pdo->prepare("INSERT INTO garments (order_id, type, service, quantity, unit_price) VALUES (:order_id, :type, :service, :quantity, :unit_price)");
        $stmt->bindParam(":order_id", $orderId, PDO::PARAM_INT);
        $stmt->bindParam(":type", $garment["type"]);
        $stmt->bindParam(":service", $garment["service"]);
        $stmt->bindParam(":quantity", $garment["quantity"]);
        $stmt->bindParam(":unit_price", $garment["unit_price"]);
        $stmt->execute();
    }

    // Create billing record
    $stmt = $pdo->prepare("INSERT INTO billings (order_id, subtotal, total_amount, status) VALUES (:order_id, :subtotal, :total_amount, 'unpaid')");
    $stmt->bindParam(":order_id", $orderId, PDO::PARAM_INT);
    $stmt->bindParam(":subtotal", $orderAmount);
    $stmt->bindParam(":total_amount", $orderAmount);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Order added successfully", "order_id" => $orderId, "tracking_id" => $trackingId]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}