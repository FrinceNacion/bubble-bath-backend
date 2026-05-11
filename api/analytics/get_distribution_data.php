<?php
require_once __DIR__ . '/../../config/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../middleware/authenticate.php';
require_once __DIR__ . '/../../config/database.php';

try {
    // 1. Order Status Distribution
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders WHERE deleted_at IS NULL GROUP BY status");
    $stmt->execute();
    $orderStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Service Type Distribution (from garments)
    $stmt = $pdo->prepare("SELECT service, COUNT(*) as count FROM garments WHERE deleted_at IS NULL GROUP BY service");
    $stmt->execute();
    $serviceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Payment Method Distribution
    $stmt = $pdo->prepare("SELECT payment_method, COUNT(*) as count FROM payments GROUP BY payment_method");
    $stmt->execute();
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "order_status" => $orderStatus,
        "service_types" => $serviceTypes,
        "payment_methods" => $paymentMethods
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
