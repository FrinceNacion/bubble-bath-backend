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

$orderId = $input['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(["success" => false, "error" => "Missing order ID"]);
    exit();
}

try{
    $stmt = $pdo->prepare("SELECT * FROM garments WHERE order_id = :order_id AND deleted_at IS NULL");
    $stmt->bindParam(":order_id", $orderId, PDO::PARAM_INT);
    $stmt->execute();

    $garments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $garments]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}