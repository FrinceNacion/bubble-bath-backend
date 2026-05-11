<?php
require_once __DIR__ . '/../../config/headers.php';

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid JSON"]);
    exit();
}

$name = $input['name'] ?? null;
$email = $input['email'] ?? null;
$password = $input['password'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM users WHERE name = :name OR email = :email AND deleted_at IS NULL");
$stmt->bindParam(':name', $name);
$stmt->bindParam(':email', $email);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo json_encode(['error' => 'name or email already exists']);
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, password, email) VALUES (:name, :password, :email)");
$stmt->bindParam(':name', $name);
$stmt->bindParam(':password', $hashed_password);
$stmt->bindParam(':email', $email);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}