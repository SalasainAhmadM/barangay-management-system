<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION["user_id"];
    $collection_date = $_POST['collection-date'] ?? '';
    $waste_type = $_POST['waste-type'] ?? '';
    $location = $_POST['location'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validate required fields
    if (empty($collection_date) || empty($waste_type) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }

    // Handle file upload
    $photo_filename = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/waste_reports/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            // Check file size (5MB max)
            if ($_FILES['photo']['size'] <= 5242880) {
                $photo_filename = 'report_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $photo_filename;

                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload photo']);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed']);
            exit();
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO missed_collections (user_id, collection_date, waste_type, location, description, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isssss", $user_id, $collection_date, $waste_type, $location, $description, $photo_filename);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Your report has been submitted successfully. We will review it shortly.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit report. Please try again.']);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>