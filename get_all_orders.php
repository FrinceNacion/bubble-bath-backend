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

require_once 'authenticate.php';    

require_once 'connect_db.php';

try {
    $stmt = $pdo->prepare("SELECT orders.*, customers.name AS customer, g.order_item_count FROM orders JOIN customers ON orders.customer_id = customers.customer_id JOIN (SELECT order_id, COUNT(*) AS order_item_count FROM garments GROUP BY order_id) g ON orders.order_id = g.order_id WHERE orders.deleted_at IS NULL ORDER BY orders.order_date DESC;");
    $stmt->execute();

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($orders);

    echo json_encode(["success" => true, "orders" => $orders, "count" => $count]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
