<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['request_id']) || !isset($_FILES['receipt'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID and receipt file are required']);
    exit();
}

$request_id = intval($_POST['request_id']);
$user_id = $_SESSION["user_id"];

// Verify the request belongs to the user and get user details
$query = "SELECT dr.*, dt.fee, dt.name as document_name, 
          u.first_name, u.middle_name, u.last_name
          FROM document_requests dr
          INNER JOIN document_types dt ON dr.document_type_id = dt.id
          INNER JOIN users u ON dr.user_id = u.id
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

// Handle file upload
$file = $_FILES['receipt'];
$upload_dir = "../../assets/images/receipt/";

// Create directory if it doesn't exist
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        $stmt->close();
        exit();
    }
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = strtolower($file['type']);
$max_size = 5 * 1024 * 1024; // 5MB

// Also check file extension
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
    $stmt->close();
    exit();
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
    $stmt->close();
    exit();
}

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error: ' . $file['error']]);
    $stmt->close();
    exit();
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'receipt_' . $request_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file. Check folder permissions.']);
    $stmt->close();
    exit();
}

// Delete old receipt if exists
if (!empty($request['payment_receipt']) && file_exists($upload_dir . $request['payment_receipt'])) {
    @unlink($upload_dir . $request['payment_receipt']);
}

// Start transaction for database updates
$conn->begin_transaction();

try {
    // Update document_requests table
    $update_query = "UPDATE document_requests 
                     SET payment_receipt = ?, 
                         payment_date = NOW(),
                         payment_status = 'paid',
                         updated_at = NOW()
                     WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);

    if (!$update_stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $update_stmt->bind_param("si", $filename, $request_id);

    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update payment status: ' . $update_stmt->error);
    }

    // Build full name
    $full_name = trim($request['first_name'] . ' ' .
        ($request['middle_name'] ? $request['middle_name'] . ' ' : '') .
        $request['last_name']);

    // Insert activity log
    $activity = "Payment Paid";
    $description = $full_name . " paid the fee for " . $request['document_name'];

    $log_query = "INSERT INTO activity_logs (activity, description, created_at) 
                  VALUES (?, ?, NOW())";
    $log_stmt = $conn->prepare($log_query);

    if (!$log_stmt) {
        throw new Exception('Activity log prepare error: ' . $conn->error);
    }

    $log_stmt->bind_param("ss", $activity, $description);

    if (!$log_stmt->execute()) {
        throw new Exception('Failed to insert activity log: ' . $log_stmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment receipt uploaded successfully. Your payment will be verified by the administrator.'
    ]);

    $log_stmt->close();
    $update_stmt->close();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Delete uploaded file if database update fails
    if (file_exists($filepath)) {
        @unlink($filepath);
    }

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>