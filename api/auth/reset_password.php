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

$token       = trim($input['token'] ?? '');
$otp         = trim($input['otp'] ?? '');
$newPassword = $input['new_password'] ?? '';

if (empty($token) || empty($otp) || empty($newPassword)) {
    echo json_encode(["success" => false, "error" => "Token, OTP, and new password are required."]);
    exit();
}

// Enforce minimum password strength
if (strlen($newPassword) < 8) {
    echo json_encode(["success" => false, "error" => "Password must be at least 8 characters."]);
    exit();
}

if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
    echo json_encode(["success" => false, "error" => "Password must contain at least one letter and one number."]);
    exit();
}

// Look up the reset record
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND otp = :otp AND expires_at > NOW()");
$stmt->execute([':token' => $token, ':otp' => $otp]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    echo json_encode(["success" => false, "error" => "Invalid or expired reset code. Please request a new one."]);
    exit();
}

// Update the user's password
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
$stmt->execute([':password' => $hashed, ':user_id' => $reset['user_id']]);

// Invalidate the reset token
$pdo->prepare("DELETE FROM password_resets WHERE token = :token")->execute([':token' => $token]);

echo json_encode(['success' => true, 'message' => 'Password has been reset successfully.']);
