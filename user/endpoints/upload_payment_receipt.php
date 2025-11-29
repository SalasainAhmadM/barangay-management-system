<?php
require_once("../../conn/conn.php");
session_start();
header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit();
}

// Validate required data
if (!isset($_POST['request_id']) || !isset($_FILES['receipt'])) {
    echo json_encode(["success" => false, "message" => "Missing required data"]);
    exit();
}

$request_id = intval($_POST['request_id']);
$user_id = $_SESSION["user_id"];

try {
    // Verify the request belongs to the user and get request details
    $query = "SELECT dr.*, dt.fee, dt.name as document_name, 
              u.first_name, u.middle_name, u.last_name
              FROM document_requests dr
              INNER JOIN document_types dt ON dr.document_type_id = dt.id
              INNER JOIN user u ON dr.user_id = u.id
              WHERE dr.id = ? AND dr.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Request not found or access denied"]);
        exit();
    }

    $request = $result->fetch_assoc();
    $stmt->close();

    // Verify request is in approved status
    if ($request['status'] !== 'approved') {
        echo json_encode(["success" => false, "message" => "Payment can only be made for approved requests"]);
        exit();
    }

    // Verify payment is not already made
    if ($request['payment_status'] === 'paid') {
        echo json_encode(["success" => false, "message" => "Payment has already been submitted for this request"]);
        exit();
    }

    // Handle receipt file upload
    if ($_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "Error uploading receipt file"]);
        exit();
    }

    // Validate file type
    $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
    $fileType = $_FILES["receipt"]["type"];

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(["success" => false, "message" => "Only JPEG, PNG, and GIF images are allowed"]);
        exit();
    }

    // Check file size (5MB limit)
    if ($_FILES["receipt"]["size"] > 5 * 1024 * 1024) {
        echo json_encode(["success" => false, "message" => "Receipt file size must be less than 5MB"]);
        exit();
    }

    // Generate unique filename
    $extension = pathinfo($_FILES["receipt"]["name"], PATHINFO_EXTENSION);
    $receiptName = "receipt_" . $request_id . "_" . time() . "." . $extension;
    $uploadPath = "../../assets/images/receipt/" . $receiptName;

    // Create receipts directory if it doesn't exist
    if (!file_exists("../../assets/images/receipt/")) {
        mkdir("../../assets/images/receipt/", 0777, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($_FILES["receipt"]["tmp_name"], $uploadPath)) {
        echo json_encode(["success" => false, "message" => "Failed to upload receipt file"]);
        exit();
    }

    // Delete old receipt if it exists
    if (!empty($request['payment_receipt'])) {
        $oldReceiptPath = "../../assets/images/receipt/" . $request['payment_receipt'];
        if (file_exists($oldReceiptPath)) {
            unlink($oldReceiptPath);
        }
    }

    // Update document_requests table
    $update_query = "UPDATE document_requests 
                     SET payment_receipt = ?, 
                         payment_date = NOW(),
                         payment_status = 'paid',
                         updated_at = NOW()
                     WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $receiptName, $request_id);

    if (!$update_stmt->execute()) {
        // Delete uploaded file if database update fails
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        echo json_encode(["success" => false, "message" => "Failed to update payment status"]);
        exit();
    }
    $update_stmt->close();

    // Build full name
    $full_name = trim($request['first_name'] . ' ' .
        ($request['middle_name'] ? $request['middle_name'] . ' ' : '') .
        $request['last_name']);

    // Insert activity log
    $activity = "Payment Submitted";
    $description = $full_name . " submitted payment receipt for " . $request['document_name'] . " (Request ID: " . $request['request_id'] . ")";

    $log_query = "INSERT INTO activity_logs (activity, description, created_at) 
                  VALUES (?, ?, NOW())";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("ss", $activity, $description);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Payment receipt uploaded successfully! Your payment is now pending verification.",
        "receipt_filename" => $receiptName
    ]);

} catch (Exception $e) {
    error_log("Receipt upload error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred while processing your payment receipt"]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>