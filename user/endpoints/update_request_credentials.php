<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$user_id = $_SESSION["user_id"];

try {
    // Get form data
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
    $attachments_to_delete = isset($_POST['attachments_to_delete']) ? trim($_POST['attachments_to_delete']) : '';

    // Validate required fields
    if (empty($request_id) || empty($first_name) || empty($last_name) || empty($email) || empty($contact_number) || empty($address) || empty($purpose)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields'
        ]);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        exit();
    }

    // Validate contact number format (09XXXXXXXXX)
    if (!preg_match('/^09\d{9}$/', $contact_number)) {
        echo json_encode([
            'success' => false,
            'message' => 'Contact number must start with 09 and be 11 digits long'
        ]);
        exit();
    }

    // Verify the request belongs to the user
    $verify_query = "SELECT request_id FROM document_requests WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $request_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found or unauthorized'
        ]);
        exit();
    }

    $request_data = $verify_result->fetch_assoc();
    $request_id_code = $request_data['request_id'];
    $verify_stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update user information in the user table
        $update_user_query = "UPDATE user 
                             SET first_name = ?, 
                                 middle_name = ?, 
                                 last_name = ?, 
                                 email = ?, 
                                 contact_number = ?
                             WHERE id = ?";
        $update_user_stmt = $conn->prepare($update_user_query);
        $update_user_stmt->bind_param("sssssi", 
            $first_name, 
            $middle_name, 
            $last_name, 
            $email, 
            $contact_number, 
            $user_id
        );
        
        if (!$update_user_stmt->execute()) {
            throw new Exception('Failed to update user information');
        }
        $update_user_stmt->close();

        // Update request information in document_requests table
        // Reset status to pending and update purpose
        $update_request_query = "UPDATE document_requests 
                                SET purpose = ?,
                                    status = 'pending',
                                    rejection_reason = NULL,
                                    submitted_date = NOW(),
                                    approved_date = NULL,
                                    released_date = NULL
                                WHERE id = ? AND user_id = ?";
        $update_request_stmt = $conn->prepare($update_request_query);
        $update_request_stmt->bind_param("sii", $purpose, $request_id, $user_id);
        
        if (!$update_request_stmt->execute()) {
            throw new Exception('Failed to update request information');
        }
        $update_request_stmt->close();

        // Handle attachment deletions
        if (!empty($attachments_to_delete)) {
            $attachment_ids = explode(',', $attachments_to_delete);
            foreach ($attachment_ids as $att_id) {
                $att_id = intval($att_id);
                
                // Get file path before deleting
                $get_file_query = "SELECT file_path FROM request_attachments WHERE id = ? AND request_id = ?";
                $get_file_stmt = $conn->prepare($get_file_query);
                $get_file_stmt->bind_param("ii", $att_id, $request_id);
                $get_file_stmt->execute();
                $file_result = $get_file_stmt->get_result();
                
                if ($file_result->num_rows > 0) {
                    $file_data = $file_result->fetch_assoc();
                    $file_path = $file_data['file_path'];
                    
                    // Delete from database
                    $delete_att_query = "DELETE FROM request_attachments WHERE id = ? AND request_id = ?";
                    $delete_att_stmt = $conn->prepare($delete_att_query);
                    $delete_att_stmt->bind_param("ii", $att_id, $request_id);
                    $delete_att_stmt->execute();
                    $delete_att_stmt->close();
                    
                    // Delete physical file
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                $get_file_stmt->close();
            }
        }

        // Handle new attachments
        if (isset($_FILES['new_attachments']) && !empty($_FILES['new_attachments']['name'][0])) {
            // Path from user/endpoints/ to root, then to uploads/document_requests/
            // Going up 2 levels: endpoints -> user -> root
            $upload_dir_relative = '../../uploads/document_requests/';
            
            // Absolute path for file operations
            $absolute_upload_dir = realpath(__DIR__ . '/../../uploads/document_requests/');
            
            // Create directory if it doesn't exist
            if (!is_dir($absolute_upload_dir)) {
                mkdir($absolute_upload_dir, 0777, true);
            }

            $files = $_FILES['new_attachments'];
            $file_count = count($files['name']);

            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $original_filename = $files['name'][$i];
                    $file_size = $files['size'][$i];
                    $file_tmp = $files['tmp_name'][$i];
                    
                    // Validate file size (5MB max)
                    if ($file_size > 5 * 1024 * 1024) {
                        throw new Exception("File {$original_filename} exceeds 5MB limit");
                    }

                    // Get file extension
                    $file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                    
                    // Validate file type
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
                    if (!in_array($file_ext, $allowed_extensions)) {
                        throw new Exception("Invalid file type for {$original_filename}");
                    }

                    // Generate unique filename matching the pattern: BR-2025-XXXXXX_TIMESTAMP_INDEX.ext
                    $timestamp = time();
                    $new_filename = $request_id_code . '_' . $timestamp . '_' . $i . '.' . $file_ext;
                    
                    // Absolute file path for actual file operations
                    $absolute_file_path = $absolute_upload_dir . '/' . $new_filename;
                    
                    // Database path (relative from project root: ../uploads/document_requests/)
                    $db_file_path = '../uploads/document_requests/' . $new_filename;

                    // Move uploaded file
                    if (move_uploaded_file($file_tmp, $absolute_file_path)) {
                        // Insert into database with relative path
                        $insert_att_query = "INSERT INTO request_attachments (request_id, file_name, file_path, file_type) 
                                           VALUES (?, ?, ?, ?)";
                        $insert_att_stmt = $conn->prepare($insert_att_query);
                        $insert_att_stmt->bind_param("isss", $request_id, $original_filename, $db_file_path, $file_ext);
                        
                        if (!$insert_att_stmt->execute()) {
                            throw new Exception('Failed to save attachment information');
                        }
                        $insert_att_stmt->close();
                    } else {
                        throw new Exception("Failed to upload file: {$original_filename}");
                    }
                }
            }
        }

        // Log the activity
        $activity = 'Request Credentials Updated';
        $description = "User updated credentials for request {$request_id_code}. Status changed to pending.";
        $log_query = "INSERT INTO activity_logs (activity, description) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("ss", $activity, $description);
        $log_stmt->execute();
        $log_stmt->close();

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Your request has been updated and resubmitted successfully!'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>