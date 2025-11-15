<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

$id = $_GET["id"] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(["success" => false, "message" => "Invalid resident ID"]);
    exit();
}

try {
    // ✅ Select all required fields including verification documents
    $stmt = $conn->prepare("SELECT 
            id, 
            first_name, 
            middle_name, 
            last_name, 
            email, 
            contact_number, 
            date_of_birth, 
            gender, 
            civil_status, 
            occupation, 
            house_number, 
            street_name, 
            barangay, 
            status, 
            image, 
            selfie_image,
            gov_id_type,
            gov_id_image,
            is_approved,
            created_at, 
            updated_at
        FROM user
        WHERE id = ? LIMIT 1");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();

    if ($resident) {
        // Ensure nulls are handled properly
        foreach ($resident as $key => $value) {
            if ($value === null) {
                $resident[$key] = null;
            }
        }

        // Ensure image fields are properly handled (null if empty)
        $resident['image'] = !empty($resident['image']) ? $resident['image'] : null;
        $resident['selfie_image'] = !empty($resident['selfie_image']) ? $resident['selfie_image'] : null;
        $resident['gov_id_image'] = !empty($resident['gov_id_image']) ? $resident['gov_id_image'] : null;
        $resident['gov_id_type'] = !empty($resident['gov_id_type']) ? $resident['gov_id_type'] : null;
        $resident['is_approved'] = !empty($resident['is_approved']) ? $resident['is_approved'] : 'pending';

        echo json_encode(["success" => true, "resident" => $resident]);
    } else {
        echo json_encode(["success" => false, "message" => "Resident not found"]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>