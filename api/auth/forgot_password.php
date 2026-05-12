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

$email = trim($input['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "error" => "A valid email is required."]);
    exit();
}

// Check user exists
$stmt = $pdo->prepare("SELECT user_id, name, email FROM users WHERE email = :email AND deleted_at IS NULL");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Return success anyway to prevent email enumeration
    echo json_encode(['success' => true, 'message' => 'If that email exists, a reset code has been sent.']);
    exit();
}

// Generate a secure 6-digit OTP and a token
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Remove old tokens for this user
$pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id")->execute([':user_id' => $user['user_id']]);

// Insert reset record
$stmt = $pdo->prepare("INSERT INTO password_resets (user_id, email, token, otp, expires_at) VALUES (:user_id, :email, :token, :otp, :expires_at)");
$stmt->execute([
    ':user_id'    => $user['user_id'],
    ':email'      => $email,
    ':token'      => $token,
    ':otp'        => $otp,
    ':expires_at' => $expires_at,
]);

// In a real production system, you would send an email here.
// For local/demo purposes, we return the OTP in the response so it can be displayed/used.
echo json_encode([
    'success' => true,
    'message' => 'Reset code generated. For demo purposes the code is returned here.',
    'otp'     => $otp,    // DEMO ONLY — remove in production and email instead
    'token'   => $token,
]);
