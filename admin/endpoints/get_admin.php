<?php
require_once("../../conn/conn.php");

header("Content-Type: application/json");

$id = $_GET["id"] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(["success" => false, "message" => "Invalid admin ID"]);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT 
            id,
            first_name,
            middle_name,
            last_name,
            email,
            image,
            logo,
            gcash_qr,
            updated_at
        FROM admin
        WHERE id = ? LIMIT 1");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result(); // ✅ Fixed: was $result->get_result()
    $admin = $result->fetch_assoc();

    if ($admin) {
        // Ensure nulls are handled properly
        foreach ($admin as $key => $value) {
            if ($value === null) {
                $admin[$key] = null;
            }
        }

        // Ensure image/logo/gcash_qr fields are properly handled
        $admin['image'] = !empty($admin['image']) ? $admin['image'] : null;
        $admin['logo'] = !empty($admin['logo']) ? $admin['logo'] : null;
        $admin['gcash_qr'] = !empty($admin['gcash_qr']) ? $admin['gcash_qr'] : null;

        echo json_encode(["success" => true, "admin" => $admin]);
    } else {
        echo json_encode(["success" => false, "message" => "Admin not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>