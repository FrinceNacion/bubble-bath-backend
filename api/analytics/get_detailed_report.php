<?php
require_once __DIR__ . '/../../config/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../middleware/authenticate.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$type = $data['type'] ?? 'revenue'; // revenue, orders, customers, transactions
$startDate = $data['start_date'] ?? date('Y-m-01');
$endDate = $data['end_date'] ?? date('Y-m-d');

try {
    $results = [];

    if ($type === 'revenue') {
        $stmt = $pdo->prepare("
            SELECT b.billing_id, o.order_id, c.name as customer, b.total_amount, b.status, o.order_date
            FROM billings b
            JOIN orders o ON b.order_id = o.order_id
            JOIN customers c ON o.customer_id = c.customer_id
            WHERE DATE(o.order_date) BETWEEN ? AND ?
            AND b.deleted_at IS NULL
            ORDER BY o.order_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type === 'orders') {
        $stmt = $pdo->prepare("
            SELECT o.order_id, c.name as customer, o.order_date, o.status, o.order_amount
            FROM orders o
            JOIN customers c ON o.customer_id = c.customer_id
            WHERE DATE(o.order_date) BETWEEN ? AND ?
            AND o.deleted_at IS NULL
            ORDER BY o.order_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type === 'transactions') {
        $stmt = $pdo->prepare("
            SELECT p.payment_id, b.order_id, c.name as customer, p.amount_paid, p.payment_method, p.payment_date
            FROM payments p
            JOIN billings b ON p.billing_id = b.billing_id
            JOIN orders o ON b.order_id = o.order_id
            JOIN customers c ON o.customer_id = c.customer_id
            WHERE DATE(p.payment_date) BETWEEN ? AND ?
            ORDER BY p.payment_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        "success" => true,
        "data" => $results
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
