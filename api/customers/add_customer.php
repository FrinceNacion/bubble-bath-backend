<?php
require_once __DIR__ . '/../../config/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../middleware/authenticate.php';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sanitizer.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit();
}

$name = sanitize_input($input['name'] ?? null);
$email = sanitize_input($input['email'] ?? null);
$mobile = sanitize_input($input['mobile'] ?? null);
$address = sanitize_input($input['address'] ?? null);

if (!$name || !$mobile) {
    echo json_encode(["success" => false, "error" => "Name and mobile are required"]);
    exit();
}

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