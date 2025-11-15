<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
error_log("get_gcash_qr.php called");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit();
}

$request_id = intval($_GET['request_id']);
$user_id = $_SESSION["user_id"];

// Verify the request belongs to the user and get details
$query = "SELECT dr.*, dt.name as document_name, dt.fee
          FROM document_requests dr
          INNER JOIN document_types dt ON dr.document_type_id = dt.id
          WHERE dr.id = ? AND dr.user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    $stmt->close();
    exit();
}

$request = $result->fetch_assoc();

// Check if request is approved and has a fee
if ($request['status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Request is not approved yet']);
    $stmt->close();
    exit();
}

if ($request['fee'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'This request has no fee']);
    $stmt->close();
    exit();
}

// Check if already paid
if ($request['payment_status'] === 'paid') {
    echo json_encode(['success' => false, 'message' => 'Payment already completed']);
    $stmt->close();
    exit();
}

// Get admin GCash QR code - FIX: Only call fetch_assoc() ONCE
$admin_query = "SELECT gcash_qr FROM admin WHERE id = 1 LIMIT 1";
$admin_result = $conn->query($admin_query);

if (!$admin_result) {
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
    $stmt->close();
    exit();
}

if ($admin_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Admin settings not found. Please contact administrator.']);
    $stmt->close();
    exit();
}

// FIX: Call fetch_assoc() only ONCE and store result
$admin = $admin_result->fetch_assoc();

if (empty($admin['gcash_qr'])) {
    echo json_encode(['success' => false, 'message' => 'GCash QR code not available. Please contact administrator.']);
    $stmt->close();
    exit();
}

echo json_encode([
    'success' => true,
    'gcash_qr' => $admin['gcash_qr'],
    'fee' => $request['fee'],
    'document_name' => $request['document_name'],
    'request_id' => $request['request_id']
]);

$stmt->close();
$conn->close();
?>