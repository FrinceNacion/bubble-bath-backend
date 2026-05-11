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

$limit = $input['limit'];

if (!$limit) {
    echo json_encode(["success" => false, "error" => "Missing limit"]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT orders.*, customers.name AS customer FROM orders JOIN customers ON orders.customer_id = customers.customer_id WHERE orders.deleted_at IS NULL ORDER BY orders.order_date DESC LIMIT :limit");
    $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $orders]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}