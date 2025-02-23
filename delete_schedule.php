<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule_id'])) {
    $userId = $_SESSION['user_id'];
    $scheduleId = $_POST['schedule_id'];

    // Verify ownership before deletion
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $scheduleId, $userId);
    
    if ($stmt->execute()) {
        logActivity($userId, "Deleted schedule #$scheduleId");
    }
}

header("Location: dashboard.php");
exit();
