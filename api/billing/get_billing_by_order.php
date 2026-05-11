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
$orderId = $input['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(["success" => false, "error" => "Order ID is required"]);
    exit();
}

try {
    // Get billing info
    $stmt = $pdo->prepare("
        SELECT b.*, o.order_amount 
        FROM billings b 
        JOIN orders o ON b.order_id = o.order_id 
        WHERE b.order_id = :order_id
    ");
    $stmt->bindParam(":order_id", $orderId, PDO::PARAM_INT);
    $stmt->execute();
    $billing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$billing) {
        echo json_encode(["success" => false, "error" => "Billing record not found"]);
        exit();
    }

    // Get payment history
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE billing_id = :billing_id ORDER BY payment_date DESC");
    $stmt->bindParam(":billing_id", $billing['billing_id'], PDO::PARAM_INT);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true, 
        "billing" => $billing,
        "payments" => $payments
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
