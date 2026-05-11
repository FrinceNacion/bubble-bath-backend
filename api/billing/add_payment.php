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
$billingId = $input['billing_id'] ?? null;
$amountPaid = $input['amount_paid'] ?? 0;
$paymentMethod = $input['payment_method'] ?? 'cash';
$notes = $input['notes'] ?? '';

if (!$billingId || $amountPaid <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid payment data"]);
    exit();
}

try {
    $pdo->beginTransaction();

    // Insert payment
    $stmt = $pdo->prepare("INSERT INTO payments (billing_id, amount_paid, payment_method, notes) VALUES (:billing_id, :amount_paid, :payment_method, :notes)");
    $stmt->bindParam(":billing_id", $billingId, PDO::PARAM_INT);
    $stmt->bindParam(":amount_paid", $amountPaid);
    $stmt->bindParam(":payment_method", $paymentMethod);
    $stmt->bindParam(":notes", $notes);
    $stmt->execute();

    // Calculate total paid so far
    $stmt = $pdo->prepare("SELECT total_amount, (SELECT SUM(amount_paid) FROM payments WHERE billing_id = :b1) as total_paid FROM billings WHERE billing_id = :b2");
    $stmt->bindParam(":b1", $billingId, PDO::PARAM_INT);
    $stmt->bindParam(":b2", $billingId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalAmount = $result['total_amount'];
    $totalPaid = $result['total_paid'];

    // Update billing status
    $newStatus = 'partially_paid';
    if ($totalPaid >= $totalAmount) {
        $newStatus = 'paid';
    } elseif ($totalPaid <= 0) {
        $newStatus = 'unpaid';
    }

    $stmt = $pdo->prepare("UPDATE billings SET status = :status WHERE billing_id = :billing_id");
    $stmt->bindParam(":status", $newStatus);
    $stmt->bindParam(":billing_id", $billingId, PDO::PARAM_INT);
    $stmt->execute();

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Payment recorded successfully", "new_status" => $newStatus]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
