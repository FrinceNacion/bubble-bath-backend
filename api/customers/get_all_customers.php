<?php
require_once __DIR__ . '/../../config/headers.php';

require_once __DIR__ . '/../../middleware/authenticate.php';
require_once __DIR__ . '/../../config/database.php';

$stmt = $pdo->prepare("SELECT customer_id, name, address, email, contact_number FROM customers WHERE deleted_at IS NULL");

if ($stmt->execute()) {
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);     
    echo json_encode([
        'success' => true,
        'count' => count($customers),
        'customers' => $customers
    ]);
    exit();
}else{
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to retrieve customers']);
}


