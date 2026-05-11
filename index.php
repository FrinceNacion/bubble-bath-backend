<?php
require_once __DIR__ . '/config/headers.php';

echo json_encode([
    "status" => "online",
    "message" => "Bubble Bath API is running",
    "version" => "2.0.0",
    "environment" => "production"
]);