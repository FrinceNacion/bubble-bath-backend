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
$filter = $data['filter'] ?? 'monthly'; // daily, weekly, monthly, yearly

try {
    $labels = [];
    $revenue = [];
    $orders = [];

    if ($filter === 'daily') {
        // Last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M d', strtotime($date));

            $stmt = $pdo->prepare("SELECT SUM(amount_paid) as rev FROM payments WHERE DATE(payment_date) = ?");
            $stmt->execute([$date]);
            $revenue[] = (float) ($stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?? 0);

            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE DATE(order_date) = ? AND deleted_at IS NULL");
            $stmt->execute([$date]);
            $orders[] = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
        }
    } elseif ($filter === 'monthly') {
        // Last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = date('m', strtotime("-$i months"));
            $year = date('Y', strtotime("-$i months"));
            $labels[] = date('M Y', strtotime("-$i months"));

            $stmt = $pdo->prepare("SELECT SUM(amount_paid) as rev FROM payments WHERE MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
            $stmt->execute([$month, $year]);
            $revenue[] = (float) ($stmt->fetch(PDO::FETCH_ASSOC)['rev'] ?? 0);

            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE MONTH(order_date) = ? AND YEAR(order_date) = ? AND deleted_at IS NULL");
            $stmt->execute([$month, $year]);
            $orders[] = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
        }
    }

    echo json_encode([
        "success" => true,
        "labels" => $labels,
        "revenue" => $revenue,
        "orders" => $orders
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
