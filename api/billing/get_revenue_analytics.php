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
    // Today's Revenue
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) as revenue FROM payments WHERE DATE(payment_date) = CURDATE()");
    $stmt->execute();
    $todayRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

    // Weekly Revenue
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) as revenue FROM payments WHERE YEARWEEK(payment_date, 1) = YEARWEEK(CURDATE(), 1)");
    $stmt->execute();
    $weeklyRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

    // Monthly Revenue
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) as revenue FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $stmt->execute();
    $monthlyRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

    // Pending Payments (Total amount - Total paid for unpaid/partially_paid)
    $stmt = $pdo->prepare("
        SELECT SUM(total_amount - (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE payments.billing_id = billings.billing_id)) as pending
        FROM billings
        WHERE status IN ('unpaid', 'partially_paid')
    ");
    $stmt->execute();
    $pendingPayments = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;

    // Total Transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments");
    $stmt->execute();
    $totalTransactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Recent Payments
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as customer_name, b.order_id
        FROM payments p
        JOIN billings b ON p.billing_id = b.billing_id
        JOIN orders o ON b.order_id = o.order_id
        JOIN customers c ON o.customer_id = c.customer_id
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "analytics" => [
            "today_revenue" => $todayRevenue,
            "weekly_revenue" => $weeklyRevenue,
            "monthly_revenue" => $monthlyRevenue,
            "pending_payments" => $pendingPayments,
            "total_transactions" => $totalTransactions,
            "recent_payments" => $recentPayments
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
