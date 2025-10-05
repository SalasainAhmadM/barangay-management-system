<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$requestId = (int) $input['request_id'];
$status = $input['status'];
$rejectionReason = isset($input['rejection_reason']) ? $input['rejection_reason'] : null;
$notes = isset($input['notes']) ? $input['notes'] : null;

// Validate status
$validStatuses = ['pending', 'processing', 'approved', 'ready', 'completed', 'rejected', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// If rejected, rejection reason is required
if ($status === 'rejected' && empty($rejectionReason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit();
}

try {
    $conn->begin_transaction();

    // Prepare update query based on status
    $updateFields = "status = ?, updated_at = NOW()";
    $params = [$status];
    $types = "s";

    // Add date fields based on status
    if ($status === 'approved') {
        $updateFields .= ", approved_date = NOW()";
    } elseif ($status === 'completed') {
        $updateFields .= ", released_date = NOW()";
    }

    // Add rejection reason if status is rejected
    if ($status === 'rejected' && !empty($rejectionReason)) {
        $updateFields .= ", rejection_reason = ?";
        $params[] = $rejectionReason;
        $types .= "s";
    }

    // Add notes if provided
    if (!empty($notes)) {
        $updateFields .= ", notes = ?";
        $params[] = $notes;
        $types .= "s";
    }

    // Add request ID for WHERE clause
    $params[] = $requestId;
    $types .= "i";

    $stmt = $conn->prepare("UPDATE document_requests SET $updateFields WHERE id = ?");
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>