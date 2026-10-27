<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request.'
    ]);
    exit;
}

if (!isset($_SESSION['employee_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Session expired.'
    ]);
    exit;
}

include("../database/database.php");

$employee_id = $_SESSION['employee_id'];
$order_id = intval($_POST['order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowed_statuses = [
    'Pending',
    'Assigned',
    'Ready',
    'Collected',
    'Cancelled'
];

if ($order_id <= 0 || !in_array($status, $allowed_statuses)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid data.'
    ]);
    exit;
}

/* Verify that this order belongs to the logged in tailor */
$check = $conn->prepare("
    SELECT 1
    FROM order_details
    WHERE order_id = ?
    AND employee_id = ?
    LIMIT 1
");
$check->bind_param("ii", $order_id, $employee_id);
$check->execute();
$exists = $check->get_result()->num_rows;
$check->close();

if (!$exists) {
    echo json_encode([
        'success' => false,
        'error' => 'You are not assigned to this order.'
    ]);
    exit;
}

/* Update only this tailor's assigned order */
$stmt = $conn->prepare("
    UPDATE orders
    SET order_status = ?
    WHERE order_id = ?
");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
