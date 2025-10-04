<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION["user_id"];
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

// Fetch request details with user information
$query = "SELECT 
            dr.*,
            dt.name as document_name,
            dt.icon,
            dt.type as document_type,
            dt.fee,
            dt.processing_days,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number,
            u.image as user_image,
            CONCAT(
                COALESCE(u.house_number, ''), ' ',
                COALESCE(u.street_name, ''), ', ',
                COALESCE(u.barangay, '')
            ) as address
          FROM document_requests dr
          INNER JOIN document_types dt ON dr.document_type_id = dt.id
          INNER JOIN user u ON dr.user_id = u.id
          WHERE dr.id = ? AND dr.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $request = $result->fetch_assoc();
    echo json_encode(['success' => true, 'request' => $request]);
} else {
    echo json_encode(['success' => false, 'message' => 'Request not found or unauthorized']);
}

$stmt->close();
$conn->close();
?>