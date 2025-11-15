<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get document type and ID from URL
$document_type = isset($_GET['type']) ? $_GET['type'] : '';
$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch document details
$query = "SELECT * FROM document_types WHERE id = ? AND type = ? AND is_active = TRUE";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $document_id, $document_type);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();
$stmt->close();

if (!$document) {
    header("Location: certificates.php");
    exit();
}

// Fetch user details for pre-filling
$user_query = "SELECT * FROM user WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate file upload first
    if (!isset($_FILES['attachments']) || empty($_FILES['attachments']['name'][0])) {
        $error_message = "no_files_uploaded";
    } else {
        $purpose = $_POST['purpose'];
        $additional_info = isset($_POST['additional_info']) ? $_POST['additional_info'] : '';

        // Generate unique request ID
        $request_id = 'BR-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

        // Calculate expected date based on processing days
        $processing_days = intval(filter_var($document['processing_days'], FILTER_SANITIZE_NUMBER_INT));
        $expected_date = date('Y-m-d', strtotime("+$processing_days days"));

        // Insert request
        $insert_query = "INSERT INTO document_requests (request_id, user_id, document_type_id, purpose, additional_info, expected_date) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("siisss", $request_id, $user_id, $document_id, $purpose, $additional_info, $expected_date);

        if ($stmt->execute()) {
            $new_request_id = $stmt->insert_id;
            $stmt->close();

            // Handle file uploads
            $upload_dir = "../uploads/document_requests/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['attachments']['name'] as $key => $filename) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['attachments']['tmp_name'][$key];
                    $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
                    $new_filename = $request_id . '_' . time() . '_' . $key . '.' . $file_ext;
                    $file_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $attach_query = "INSERT INTO request_attachments (request_id, file_name, file_path, file_type) 
                                VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($attach_query);
                        $stmt->bind_param("isss", $new_request_id, $filename, $file_path, $file_ext);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            $today = date('Y-m-d');
            $limit_query = "INSERT INTO user_daily_request_limits (user_id, request_date, certificate_count) 
                        VALUES (?, ?, 1) 
                        ON DUPLICATE KEY UPDATE certificate_count = certificate_count + 1";
            $limit_stmt = $conn->prepare($limit_query);
            $limit_stmt->bind_param("is", $user_id, $today);
            $limit_stmt->execute();
            $limit_stmt->close();

            header("Location: certificates.php?success=request_submitted");
            exit();
        } else {
            $error_message = "Failed to submit request. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../components/header_links.php'; ?>
    <?php include '../components/user_side_header.php'; ?>
</head>

<body>
    <?php include '../components/navbar.php'; ?>

    <main class="main-content">
        <div class="application-container">
            <div class="document-header">
                <div class="document-icon-large">
                    <i class="fas <?php echo htmlspecialchars($document['icon']); ?>"></i>
                </div>
                <div class="document-info">
                    <h1><?php echo htmlspecialchars($document['name']); ?></h1>
                    <p><?php echo htmlspecialchars($document['description']); ?></p>
                    <div class="document-meta-info">
                        <span><i class="fas fa-clock"></i> Processing:
                            <?php echo htmlspecialchars($document['processing_days']); ?></span>
                        <span><i class="fas fa-peso-sign"></i> Fee:
                            <?php echo $document['fee'] == 0 ? 'Free' : '₱' . number_format($document['fee'], 2); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($document['requirements']): ?>
                <div class="info-box">
                    <h4><i class="fas fa-clipboard-list"></i> Requirements</h4>
                    <p><?php echo nl2br(htmlspecialchars($document['requirements'])); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="requestForm">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname"
                        value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']); ?>"
                        readonly>
                </div>

                <div class="form-group">
                    <label for="purpose">Purpose <span style="color: red;">*</span></label>
                    <input type="text" id="purpose" name="purpose"
                        placeholder="e.g., Employment, Business, Government Transaction" required>
                </div>

                <div class="form-group">
                    <label for="additional_info">Additional Information</label>
                    <textarea id="additional_info" name="additional_info"
                        placeholder="Provide any additional details that might be relevant to your request"></textarea>
                </div>

                <div class="form-group">
                    <label>Supporting Documents <span style="color: red;">*</span></label>
                    <div class="file-upload-area" onclick="document.getElementById('attachments').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4>Click to upload files</h4>
                        <p>You can upload multiple files (PDF, JPG, PNG)</p>
                    </div>
                    <input type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png"
                        style="display: none;" onchange="showFileNames()">
                    <div id="file-names" style="margin-top: 10px; color: #666;"></div>
                </div>

                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="history.back()">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php include '../components/cdn_scripts.php'; ?>
    <?php include '../components/footer.php'; ?>

    <script>
        function showFileNames() {
            const input = document.getElementById('attachments');
            const fileNames = document.getElementById('file-names');
            const files = Array.from(input.files).map(f => f.name);

            if (files.length > 0) {
                fileNames.innerHTML = '<strong>Selected files:</strong><br>' + files.join('<br>');
            } else {
                fileNames.innerHTML = '';
            }
        }

        // Form validation with SweetAlert
        document.getElementById('requestForm').addEventListener('submit', function (e) {
            const fileInput = document.getElementById('attachments');

            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'No Files Uploaded',
                    text: 'Please upload at least one supporting document before submitting your request.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                });

                return false;
            }
        });

        // Show SweetAlert if there's an error from server-side validation
        <?php if (isset($error_message) && $error_message === 'no_files_uploaded'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Upload Required',
                text: 'Please upload at least one supporting document to proceed with your request.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6'
            });
        <?php endif; ?>
    </script>
</body>

</html>