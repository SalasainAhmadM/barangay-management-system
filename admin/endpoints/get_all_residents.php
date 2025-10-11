<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, contact_number, 
                                   house_number, street_name, barangay, status, created_at
                            FROM user 
                            ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $residents = [];
    while ($row = $result->fetch_assoc()) {
        $residents[] = $row;
    }

    echo json_encode([
        'success' => true,
        'residents' => $residents,
        'total' => count($residents)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching residents: ' . $e->getMessage()
    ]);
}
?>