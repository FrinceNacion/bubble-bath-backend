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

$order_id = intval($input['order_id'] ?? 0);
$status   = $input['status'] ?? null;

if (!$order_id || !$status) {
    echo json_encode(["success" => false, "error" => "Missing order ID or status"]);
    exit();
}

// Normalize status
$status = str_replace(' ', '_', strtolower($status));

// Validate against allowed statuses
$allowed = ['pending', 'in_progress', 'ready', 'claimed', 'cancelled'];
if (!in_array($status, $allowed)) {
    echo json_encode(["success" => false, "error" => "Invalid status value."]);
    exit();
}

try {
    // Fetch old status before updating for audit log
    $oldStmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = :order_id AND deleted_at IS NULL");
    $oldStmt->execute([':order_id' => $order_id]);
    $oldRow     = $oldStmt->fetch(PDO::FETCH_ASSOC);
    $old_status = $oldRow ? $oldRow['status'] : null;

    $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE order_id = :order_id AND deleted_at IS NULL");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':order_id', $order_id);

    if ($stmt->execute()) {
        // Write audit log entry
        $user_id  = $_SESSION['user_id'] ?? null;
        $logStmt  = $pdo->prepare(
            "INSERT INTO order_audit_logs (order_id, old_status, new_status, changed_by_user_id) 
             VALUES (:order_id, :old_status, :new_status, :user_id)"
        );
        $logStmt->execute([
            ':order_id'   => $order_id,
            ':old_status' => $old_status,
            ':new_status' => $status,
            ':user_id'    => $user_id,
        ]);

        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
