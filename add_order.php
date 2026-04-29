<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

session_start();
require_once 'authenticate.php';

require_once 'connect_db.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit();
}

$customerId = $input['customerId'] ?? null;
$dueDate = $input['dueDate'] ?? null;
$garments = $input['garments'] ?? [];

if (!$customerId || !$dueDate || !$garments) {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit();
}

$stmt = $pdo->prepare("INSERT INTO orders (customer_id, pickup_date) VALUES (:customer_id, :pickup_date)");
$stmt->bindParam(":customer_id", $customerId, PDO::PARAM_INT);
$stmt->bindParam(":pickup_date", $dueDate);
$stmt->execute();

$orderId = $pdo->lastInsertId();

try {
    foreach ($garments as $garment) {
        if ($garment["customer_id"] == $customerId) {
            $stmt = $pdo->prepare("INSERT INTO garments (order_id, type, service, quantity, unit_price) VALUES (:order_id, :type, :service, :quantity, :unit_price)");
            $stmt->bindParam(":order_id", $orderId, PDO::PARAM_INT);
            $stmt->bindParam(":type", $garment["type"]);
            $stmt->bindParam(":service", $garment["service"]);
            $stmt->bindParam(":quantity", $garment["quantity"]);
            $stmt->bindParam(":unit_price", $garment["unit_price"]);
            $stmt->execute();
        }
    }
    echo json_encode(["success" => true, "message" => "Order added successfully"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}