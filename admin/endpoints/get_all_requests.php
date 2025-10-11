<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            dr.id,
            dr.request_id,
            dr.purpose,
            dr.status,
            dr.submitted_date,
            dt.name as document_name,
            dt.icon,
            dt.type as document_type,
            dt.fee,
            dt.processing_days,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number
        FROM document_requests dr
        INNER JOIN document_types dt ON dr.document_type_id = dt.id
        INNER JOIN user u ON dr.user_id = u.id
        ORDER BY dr.submitted_date DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'total' => count($requests)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching requests: ' . $e->getMessage()
    ]);
}
?>