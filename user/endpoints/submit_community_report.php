<?php
/**
 * Submit Community Report Endpoint
 * 
 * Handles submission of various community reports including:
 * - Missed Collection
 * - Road/Infrastructure
 * - Street Lighting
 * - Public Safety
 * - Noise Complaint
 * - Illegal Dumping
 * - Other
 * 
 * @author Jeyfred
 * @version 1.0
 */

session_start();
require_once("../../conn/conn.php");

// Set response header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access. Please login first.'
    ]);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
    exit();
}

try {
    // Get user ID from session
    $user_id = $_SESSION["user_id"];
    
    // Retrieve and sanitize form data
    $report_type = trim($_POST['report-type'] ?? '');
    $incident_date = trim($_POST['incident-date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Optional fields for missed collection
    $collection_date = !empty($_POST['collection-date']) ? trim($_POST['collection-date']) : null;
    $waste_type = !empty($_POST['waste-type']) ? trim($_POST['waste-type']) : null;

    // ================================================================
    // Validation: Required Fields
    // ================================================================
    if (empty($report_type)) {
        throw new Exception('Report type is required');
    }
    
    if (empty($incident_date)) {
        throw new Exception('Incident date is required');
    }
    
    if (empty($location)) {
        throw new Exception('Location is required');
    }
    
    if (empty($description)) {
        throw new Exception('Description is required');
    }

    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $incident_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $incident_date) {
        throw new Exception('Invalid incident date format');
    }

    // Check if incident date is not in the future
    if ($date_obj > new DateTime()) {
        throw new Exception('Incident date cannot be in the future');
    }

    // ================================================================
    // Validation: Missed Collection Specific
    // ================================================================
    if ($report_type === 'Missed Collection') {
        if (empty($collection_date)) {
            throw new Exception('Collection date is required for missed collection reports');
        }
        
        if (empty($waste_type)) {
            throw new Exception('Waste type is required for missed collection reports');
        }
        
        // Validate collection date format
        $collection_date_obj = DateTime::createFromFormat('Y-m-d', $collection_date);
        if (!$collection_date_obj || $collection_date_obj->format('Y-m-d') !== $collection_date) {
            throw new Exception('Invalid collection date format');
        }
        
        // Check if collection date is not in the future
        if ($collection_date_obj > new DateTime()) {
            throw new Exception('Collection date cannot be in the future');
        }
        
        // Validate waste type
        $valid_waste_types = [
            'Biodegradable Waste',
            'Non-Biodegradable Waste',
            'Recyclable Waste',
            'Special/Hazardous Waste'
        ];
        
        if (!in_array($waste_type, $valid_waste_types)) {
            throw new Exception('Invalid waste type selected');
        }
    }

    // Validate report type
    $valid_report_types = [
        'Missed Collection',
        'Road/Infrastructure',
        'Street Lighting',
        'Public Safety',
        'Noise Complaint',
        'Illegal Dumping',
        'Other'
    ];
    
    if (!in_array($report_type, $valid_report_types)) {
        throw new Exception('Invalid report type');
    }

    // ================================================================
    // File Upload Handling
    // ================================================================
    $photo_filename = null;
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/community_reports/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            throw new Exception('Upload directory is not writable');
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed');
        }
        
        // Validate file size (5MB max)
        $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($_FILES['photo']['size'] > $max_file_size) {
            throw new Exception('File size must be less than 5MB');
        }
        
        // Validate file is actually an image
        $image_info = getimagesize($_FILES['photo']['tmp_name']);
        if ($image_info === false) {
            throw new Exception('Uploaded file is not a valid image');
        }
        
        // Generate unique filename
        $photo_filename = 'report_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
        $upload_path = $upload_dir . $photo_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            throw new Exception('Failed to upload photo. Please try again');
        }
        
        // Set proper permissions
        chmod($upload_path, 0644);
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the maximum file size',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the maximum file size',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $error_message = $upload_errors[$_FILES['photo']['error']] ?? 'Unknown upload error';
        throw new Exception($error_message);
    }

    // ================================================================
    // Database Insert
    // ================================================================
    $stmt = $conn->prepare(
        "INSERT INTO community_reports 
        (user_id, report_type, incident_date, location, description, waste_type, collection_date, photo_path, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "isssssss", 
        $user_id, 
        $report_type, 
        $incident_date, 
        $location, 
        $description, 
        $waste_type, 
        $collection_date, 
        $photo_filename
    );
    
    if (!$stmt->execute()) {
        // If database insert fails and we uploaded a photo, delete it
        if ($photo_filename && file_exists($upload_dir . $photo_filename)) {
            unlink($upload_dir . $photo_filename);
        }
        throw new Exception('Failed to submit report: ' . $stmt->error);
    }
    
    $report_id = $stmt->insert_id;
    $stmt->close();
    
    // ================================================================
    // Success Response
    // ================================================================
    echo json_encode([
        'success' => true,
        'message' => 'Your report has been submitted successfully. We will review it shortly.',
        'report_id' => $report_id,
        'report_type' => $report_type
    ]);
    
    // Optional: Log the successful submission
    error_log("Report submitted - ID: $report_id, Type: $report_type, User: $user_id");
    
} catch (Exception $e) {
    // ================================================================
    // Error Response
    // ================================================================
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    // Log the error (optional but recommended)
    error_log("Report submission error: " . $e->getMessage());
}

// Close database connection
$conn->close();
?>