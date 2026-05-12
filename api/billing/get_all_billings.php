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
    $stmt = $pdo->prepare("
        SELECT 
            b.*, 
            o.order_date, 
            o.pickup_date, 
            c.name as customer_name,
            c.contact_number as customer_contact,
            c.address as customer_address,
            COALESCE(SUM(p.amount_paid), 0) as total_paid,
            (b.total_amount - COALESCE(SUM(p.amount_paid), 0)) as remaining_balance,
            GROUP_CONCAT(DISTINCT p.payment_method SEPARATOR ', ') as payment_methods
        FROM billings b
        JOIN orders o ON b.order_id = o.order_id
        JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN payments p ON b.billing_id = p.billing_id
        GROUP BY b.billing_id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $billings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $billings]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
