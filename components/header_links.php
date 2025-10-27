<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Management System</title>
<link rel="stylesheet" href="../css/style.css">
<?php
// Fetch system settings for display
$settingsQuery = $conn->query("SELECT system_name, system_logo FROM system_settings LIMIT 1");
$systemSettings = $settingsQuery->fetch_assoc();

$systemName = $systemSettings['system_name'] ?? 'BMS';
$systemLogo = !empty($systemSettings['system_logo'])
    ? "../assets/images/settings/" . $systemSettings['system_logo']
    : "../assets/logo/bms.png";
?>
<link rel="icon" href="<?= htmlspecialchars($systemLogo) ?>" type="image/icon type">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">