<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once 'authenticate.php';
require_once 'connect_db.php';

$stmt = $pdo->prepare("SELECT customer_id, name, address, email, contact_number FROM customers WHERE deleted_at IS NULL");

if ($stmt->execute()) {
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);     
    echo json_encode([
        'success' => true,
        'count' => count($customers),
        'customers' => $customers
    ]);
    exit();
}else{
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve customers']);
}


