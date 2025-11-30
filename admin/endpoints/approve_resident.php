<?php
session_start();
require_once("../../conn/conn.php");
header("Content-Type: application/json");

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input["id"] ?? 0);
$action = trim($input["action"] ?? "");

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid resident ID"]);
    exit();
}

if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(["success" => false, "message" => "Invalid action"]);
    exit();
}

try {
    // Get resident details
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, is_approved FROM user WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Resident not found"]);
        exit();
    }

    $resident = $result->fetch_assoc();
    $stmt->close();

    // Check if already processed
    if ($resident['is_approved'] !== 'pending') {
        echo json_encode(["success" => false, "message" => "This registration has already been processed"]);
        exit();
    }

    // Update approval status
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE user SET is_approved = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $id);

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to update resident status"]);
        exit();
    }
    $stmt->close();

    // Create default notification preferences if user is approved and preferences don't exist
    if ($action === 'approve') {
        $checkPrefStmt = $conn->prepare("SELECT id FROM notification_preferences WHERE user_id = ? LIMIT 1");
        $checkPrefStmt->bind_param("i", $id);
        $checkPrefStmt->execute();
        $prefResult = $checkPrefStmt->get_result();

        if ($prefResult->num_rows === 0) {
            // No preferences exist, create default ones
            $insertPrefStmt = $conn->prepare("INSERT INTO notification_preferences (user_id, waste_reminders, request_updates, announcements, sms_notifications, created_at, updated_at) VALUES (?, 1, 1, 1, 1, NOW(), NOW())");
            $insertPrefStmt->bind_param("i", $id);
            $insertPrefStmt->execute();
            $insertPrefStmt->close();
        }
        $checkPrefStmt->close();
    }

    // Log activity
    $fullName = trim($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']);
    $activityType = ($action === 'approve') ? "Resident Approved" : "Resident Rejected";
    $description = "Admin " . $_SESSION['admin_name'] . " has " . ($action === 'approve' ? 'approved' : 'rejected') .
        " the registration of {$fullName} ({$resident['email']})";

    $logStmt = $conn->prepare("INSERT INTO activity_logs (activity, description, created_at) VALUES (?, ?, NOW())");
    $logStmt->bind_param("ss", $activityType, $description);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Resident registration has been " . ($action === 'approve' ? 'approved' : 'rejected')
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>