<?php
// ========== update_request_status.php ==========
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION["admin_id"];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$requestId = (int) $input['request_id'];
$status = trim($input['status']);
$rejectionReason = trim($input['rejection_reason'] ?? '');
$notes = trim($input['notes'] ?? '');

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

    // Get document info for logging
    $info_stmt = $conn->prepare("
        SELECT dr.id, dr.user_id, dt.name AS document_name
        FROM document_requests dr
        INNER JOIN document_types dt ON dr.document_type_id = dt.id
        WHERE dr.id = ?
    ");
    $info_stmt->bind_param("i", $requestId);
    $info_stmt->execute();
    $result = $info_stmt->get_result();
    $requestInfo = $result->fetch_assoc();
    $info_stmt->close();

    if (!$requestInfo) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }

    // Prepare update query
    $updateFields = "status = ?, updated_at = NOW()";
    $params = [$status];
    $types = "s";

    // Add date fields based on status
    if ($status === 'approved') {
        $updateFields .= ", approved_date = NOW()";
    } elseif ($status === 'completed') {
        $updateFields .= ", released_date = NOW()";
    }

    // Add rejection reason if rejected
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

    // Add WHERE clause
    $params[] = $requestId;
    $types .= "i";

    // Execute update
    $stmt = $conn->prepare("UPDATE document_requests SET $updateFields WHERE id = ?");
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // ✅ Log admin activity
            $activity = "Updated document request status";
            $docName = $requestInfo['document_name'] ?? 'Unknown document';

            // Build readable description
            $description = "Updated the status of request ID {$requestId} for '{$docName}' to '{$status}'.";

            if (!empty($notes)) {
                $description .= " Notes: {$notes}.";
            }

            if ($status === 'rejected' && !empty($rejectionReason)) {
                $description .= " Rejection reason: {$rejectionReason}.";
            }

            $log_stmt = $conn->prepare("
                INSERT INTO activity_logs (activity, description, created_at)
                VALUES (?, ?, NOW())
            ");
            $log_stmt->bind_param("ss", $activity, $description);
            $log_stmt->execute();
            $log_stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Request not found or no changes made']);
        }
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