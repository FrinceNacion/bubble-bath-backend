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

$stmt = $pdo->prepare("INSERT INTO customers (name, email, contact_number, address) VALUES (:name, :email, :mobile, :address)");
$stmt->bindParam(':name', $name);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':mobile', $mobile);
$stmt->bindParam(':address', $address);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add customer']);
}