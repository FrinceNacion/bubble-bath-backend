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
    // 1. Stat Cards Data
    // Today's Revenue
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) as revenue FROM payments WHERE DATE(payment_date) = CURDATE()");
    $stmt->execute();
    $todayRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

    // Monthly Revenue
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) as revenue FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $stmt->execute();
    $monthlyRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

    // Total Orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE deleted_at IS NULL");
    $stmt->execute();
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Pending Orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending' AND deleted_at IS NULL");
    $stmt->execute();
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Completed/Claimed Orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'claimed' AND deleted_at IS NULL");
    $stmt->execute();
    $completedOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Active Customers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE status = 'active' AND deleted_at IS NULL");
    $stmt->execute();
    $activeCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // 2. Trend Calculation (Current Month vs Previous Month Revenue)
    $stmt = $pdo->prepare("SELECT SUM(amount_paid) as revenue FROM payments WHERE MONTH(payment_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(payment_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $stmt->execute();
    $prevMonthRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

    $revenueTrend = 0;
    if ($prevMonthRevenue > 0) {
        $revenueTrend = (($monthlyRevenue - $prevMonthRevenue) / $prevMonthRevenue) * 100;
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "today_revenue" => (float) $todayRevenue,
            "monthly_revenue" => (float) $monthlyRevenue,
            "total_orders" => (int) $totalOrders,
            "pending_orders" => (int) $pendingOrders,
            "completed_orders" => (int) $completedOrders,
            "active_customers" => (int) $activeCustomers,
            "revenue_trend" => round($revenueTrend, 2)
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
