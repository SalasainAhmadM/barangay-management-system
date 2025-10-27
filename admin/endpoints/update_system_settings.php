<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

try {
    // Validate required fields
    if (!isset($_POST['id']) || !isset($_POST['system_name'])) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields"
        ]);
        exit;
    }

    $id = intval($_POST['id']);
    $systemName = trim($_POST['system_name']);

    if (empty($systemName)) {
        echo json_encode([
            "success" => false,
            "message" => "System name cannot be empty"
        ]);
        exit;
    }

    // Initialize variables for file uploads
    $logoFilename = null;
    $bgFilename = null;

    // Handle system logo upload
    if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
        $logoFile = $_FILES['system_logo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($logoFile['type'], $allowedTypes)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid logo file type. Only JPG, PNG, GIF, and WEBP are allowed."
            ]);
            exit;
        }

        // Check file size (max 5MB)
        if ($logoFile['size'] > 5 * 1024 * 1024) {
            echo json_encode([
                "success" => false,
                "message" => "Logo file is too large. Maximum size is 5MB."
            ]);
            exit;
        }

        // Generate unique filename
        $extension = pathinfo($logoFile['name'], PATHINFO_EXTENSION);
        $logoFilename = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
        $logoPath = '../../assets/images/settings/' . $logoFilename;

        // Create directory if it doesn't exist
        if (!is_dir('../../assets/images/settings')) {
            mkdir('../../assets/images/settings', 0755, true);
        }

        // Delete old logo if exists
        $stmt = $conn->prepare("SELECT system_logo FROM system_settings WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $oldLogo = $row['system_logo'];
            if (!empty($oldLogo) && file_exists('../../assets/images/settings/' . $oldLogo)) {
                unlink('../../assets/images/settings/' . $oldLogo);
            }
        }
        $stmt->close();

        // Move uploaded file
        if (!move_uploaded_file($logoFile['tmp_name'], $logoPath)) {
            echo json_encode([
                "success" => false,
                "message" => "Failed to upload logo file"
            ]);
            exit;
        }
    }

    // Handle login background upload
    if (isset($_FILES['login_bg']) && $_FILES['login_bg']['error'] === UPLOAD_ERR_OK) {
        $bgFile = $_FILES['login_bg'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($bgFile['type'], $allowedTypes)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid background file type. Only JPG, PNG, GIF, and WEBP are allowed."
            ]);
            exit;
        }

        // Check file size (max 10MB for background)
        if ($bgFile['size'] > 10 * 1024 * 1024) {
            echo json_encode([
                "success" => false,
                "message" => "Background file is too large. Maximum size is 10MB."
            ]);
            exit;
        }

        // Generate unique filename
        $extension = pathinfo($bgFile['name'], PATHINFO_EXTENSION);
        $bgFilename = 'bg_' . time() . '_' . uniqid() . '.' . $extension;
        $bgPath = '../../assets/images/settings/' . $bgFilename;

        // Create directory if it doesn't exist
        if (!is_dir('../../assets/images/settings')) {
            mkdir('../../assets/images/settings', 0755, true);
        }

        // Delete old background if exists
        $stmt = $conn->prepare("SELECT login_bg FROM system_settings WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $oldBg = $row['login_bg'];
            if (!empty($oldBg) && $oldBg !== 'bg.jpg' && file_exists('../../assets/images/settings/' . $oldBg)) {
                unlink('../../assets/images/settings/' . $oldBg);
            }
        }
        $stmt->close();

        // Move uploaded file
        if (!move_uploaded_file($bgFile['tmp_name'], $bgPath)) {
            echo json_encode([
                "success" => false,
                "message" => "Failed to upload background file"
            ]);
            exit;
        }
    }

    // Build update query dynamically
    $updates = ["system_name = ?"];
    $params = [$systemName];
    $types = "s";

    if ($logoFilename !== null) {
        $updates[] = "system_logo = ?";
        $params[] = $logoFilename;
        $types .= "s";
    }

    if ($bgFilename !== null) {
        $updates[] = "login_bg = ?";
        $params[] = $bgFilename;
        $types .= "s";
    }

    $params[] = $id;
    $types .= "i";

    $sql = "UPDATE system_settings SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "System settings updated successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to update system settings"
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>