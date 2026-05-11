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
