<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

try {
    // Get system settings (assuming there's only one row)
    $stmt = $conn->prepare("SELECT id, system_name, system_logo, login_bg FROM system_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $settings = $result->fetch_assoc();

        // Set default background if empty or null
        if (empty($settings['login_bg'])) {
            $settings['login_bg'] = '';
            $settings['login_bg_display'] = 'bg.jpg'; // For display purposes
        } else {
            $settings['login_bg_display'] = $settings['login_bg'];
        }

        // Set default logo if empty
        if (empty($settings['system_logo'])) {
            $settings['system_logo_display'] = '';
        } else {
            $settings['system_logo_display'] = $settings['system_logo'];
        }

        echo json_encode([
            "success" => true,
            "settings" => $settings
        ]);
    } else {
        // If no settings exist, create default entry
        $defaultName = "BMS";
        $insertStmt = $conn->prepare("INSERT INTO system_settings (system_name, system_logo, login_bg) VALUES (?, '', '')");
        $insertStmt->bind_param("s", $defaultName);
        $insertStmt->execute();
        $newId = $insertStmt->insert_id;
        $insertStmt->close();

        echo json_encode([
            "success" => true,
            "settings" => [
                "id" => $newId,
                "system_name" => $defaultName,
                "system_logo" => "",
                "system_logo_display" => "",
                "login_bg" => "",
                "login_bg_display" => "bg.jpg"
            ]
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>