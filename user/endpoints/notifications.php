<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION["user_id"];
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_all':
        getNotifications($conn, $user_id);
        break;

    case 'mark_read':
        markAsRead($conn, $user_id);
        break;

    case 'mark_all_read':
        markAllAsRead($conn, $user_id);
        break;

    case 'clear_all':
        clearAll($conn, $user_id);
        break;

    case 'update_preference':
        updatePreference($conn, $user_id);
        break;

    case 'get_unread_count':
        getUnreadCount($conn, $user_id);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getNotifications($conn, $user_id)
{
    $filter = $_GET['filter'] ?? 'all';

    $query = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";

    // Apply filters
    if ($filter === 'unread') {
        $query .= " AND is_read = 0";
    } elseif (in_array($filter, ['waste', 'request', 'announcement', 'alert', 'success', 'safety'])) {
        $query .= " AND type = ?";
        $params[] = $filter;
        $types .= "s";
    }

    $query .= " ORDER BY created_at DESC LIMIT 50";

    $stmt = $conn->prepare($query);

    if (count($params) === 1) {
        $stmt->bind_param($types, $params[0]);
    } else {
        $stmt->bind_param($types, $params[0], $params[1]);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
}

function markAsRead($conn, $user_id)
{
    $notification_id = $_POST['notification_id'] ?? 0;

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read'
    ]);
}

function markAllAsRead($conn, $user_id)
{
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'All notifications marked as read' : 'Failed to mark notifications as read'
    ]);
}

function clearAll($conn, $user_id)
{
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'All notifications cleared' : 'Failed to clear notifications'
    ]);
}

function updatePreference($conn, $user_id)
{
    $preference_type = $_POST['preference_type'] ?? '';
    $value = $_POST['value'] ?? 0;

    // Validate preference type
    $valid_preferences = ['waste_reminders', 'request_updates', 'announcements', 'sms_notifications'];
    if (!in_array($preference_type, $valid_preferences)) {
        echo json_encode(['success' => false, 'message' => 'Invalid preference type']);
        return;
    }

    // Check if preferences exist
    $stmt = $conn->prepare("SELECT id FROM notification_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();
    $stmt->close();

    if ($exists) {
        // Update existing preferences
        $query = "UPDATE notification_preferences SET $preference_type = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $value, $user_id);
    } else {
        // Insert new preferences
        $stmt = $conn->prepare("INSERT INTO notification_preferences (user_id, $preference_type) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $value);
    }

    $success = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Preference updated' : 'Failed to update preference'
    ]);
}

function getUnreadCount($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'count' => $row['count']
    ]);
}
?>