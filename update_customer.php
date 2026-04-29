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

$name = $input['name'] ?? null;
$email = $input['email'] ?? null;
$mobile = $input['mobile'] ?? null;
$address = $input['address'] ?? null;
$customer_id = $input['customer_id'] ?? null;

$stmt = $pdo->prepare("UPDATE customers SET name = :name, email = :email, contact_number = :mobile, address = :address WHERE customer_id = :customer_id AND deleted_at IS NULL");
$stmt->bindParam(':name', $name);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':mobile', $mobile);
$stmt->bindParam(':address', $address);
$stmt->bindParam(':customer_id', $customer_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update customer']);
}
