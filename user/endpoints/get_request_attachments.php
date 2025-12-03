<?php
require_once("../../conn/conn.php");
session_start();

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

// Validate request ID
if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Request ID is required"]);
    exit();
}

$request_id = intval($_GET['id']);
$user_id = $_SESSION["user_id"];

try {
    // Verify the request belongs to the user
    $verify_query = "SELECT id FROM document_requests WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Request not found or access denied"]);
        exit();
    }
    $stmt->close();
    
    // Get attachments
    $attachments_query = "SELECT * FROM request_attachments WHERE request_id = ? ORDER BY uploaded_at ASC";
    $stmt = $conn->prepare($attachments_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attachments = [];
    while ($row = $result->fetch_assoc()) {
        $attachments[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        "success" => true,
        "attachments" => $attachments
    ]);
    
} catch (Exception $e) {
    error_log("Get attachments error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred while fetching attachments"]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>