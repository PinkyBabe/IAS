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

    // Validate schedule date and time are in the future
    $scheduleDateTime = new DateTime($scheduleDate . ' ' . $scheduleTime);
    $now = new DateTime();
    
    if ($scheduleDateTime <= $now) {
        $_SESSION['schedule_error'] = "Schedule time must be in the future.";
        header("Location: dashboard.php");
        exit();
    }

    if (!$conn) {
        die("Database connection error.");
    }

    // Format the time to ensure consistent storage with dashboard display
    $formattedTime = date('H:i:s', strtotime($scheduleTime));

    $stmt = $conn->prepare("INSERT INTO schedules (user_id, activity_name, schedule_date, schedule_time, reminder_time) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("isssi", $userId, $activityName, $scheduleDate, $formattedTime, $reminderTime);

    if ($stmt->execute()) {
        if (function_exists('logActivity')) {
            logActivity($userId, "Added new schedule: $activityName");
        }
        $_SESSION['schedule_success'] = "Schedule added successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['schedule_error'] = "Failed to add schedule. Please try again.";
        header("Location: dashboard.php");
        exit();
    }
}

// If no post data, redirect back to dashboard
header("Location: dashboard.php");
exit();
?>