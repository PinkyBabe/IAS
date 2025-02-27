<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['activity_name'], $_POST['schedule_date'], $_POST['schedule_time'], $_POST['reminder_time'])) {
        die("Invalid input.");
    }

    $userId = $_SESSION['user_id'];
    $activityName = htmlspecialchars(trim($_POST['activity_name']), ENT_QUOTES, 'UTF-8');
    $scheduleDate = trim($_POST['schedule_date']);
    $scheduleTime = trim($_POST['schedule_time']);
    $reminderTime = intval($_POST['reminder_time']);

    if (!$conn) {
        die("Database connection error.");
    }

    $stmt = $conn->prepare("INSERT INTO schedules (user_id, activity_name, schedule_date, schedule_time, reminder_time) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("isssi", $userId, $activityName, $scheduleDate, $scheduleTime, $reminderTime);

    if ($stmt->execute()) {
        if (function_exists('logActivity')) {
            logActivity($userId, "Added new schedule: $activityName");
        }
        header("Location: dashboard.php");
        exit();
    } else {
        die("Error executing query: " . $stmt->error);
    }
}
?>
