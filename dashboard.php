<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check and send email notifications
$stmt = $conn->prepare("
    SELECT * FROM schedules 
    WHERE user_id = ? 
    AND is_notified = 0 
    AND DATE_SUB(CONCAT(schedule_date, ' ', schedule_time), 
        INTERVAL reminder_time MINUTE) <= NOW()
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$pendingNotifications = $stmt->get_result();

$notificationSent = false;
while ($notification = $pendingNotifications->fetch_assoc()) {
    $emailSubject = 'Reminder: ' . $notification['activity_name'];
    $emailBody = sprintf(
        "Hello %s,\n\nThis is a reminder for your upcoming activity:\n\n
        Activity: %s\n
        Date: %s\n
        Time: %s\n\n
        Best regards,\n
        Remind App",
        $user['name'],
        $notification['activity_name'],
        date('F j, Y', strtotime($notification['schedule_date'])),
        date('g:i A', strtotime($notification['schedule_time']))
    );

    if (sendEmail($user['email'], $emailSubject, $emailBody)) {
        // Update notification status
        $updateStmt = $conn->prepare("UPDATE schedules SET is_notified = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $notification['id']);
        $updateStmt->execute();
        
        // Log the notification activity
        logActivity($userId, "Email notification sent for: " . $notification['activity_name']);
        $notificationSent = true;
    }
}

// If notification was sent, refresh the page
if ($notificationSent) {
    header("Refresh:0");
}

// Fetch upcoming schedules (not notified only)
$stmt = $conn->prepare("
    SELECT * FROM schedules 
    WHERE user_id = ? 
    AND schedule_date >= CURRENT_DATE 
    AND is_notified = 0
    ORDER BY schedule_date, schedule_time 
    LIMIT 10
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$schedules = $stmt->get_result();

// Fetch recent activities (notified schedules)
$stmt = $conn->prepare("
    SELECT * FROM schedules 
    WHERE user_id = ? 
    AND is_notified = 1 
    ORDER BY schedule_date DESC, schedule_time DESC 
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentActivities = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Remind</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Prevent back navigation
    window.onload = function() {
        // Push current state to history
        history.pushState(null, '', window.location.href);
        
        // Handle back button
        window.addEventListener('popstate', function() {
            history.pushState(null, '', window.location.href);
        });
    };
    </script>
    <style>
        .settings-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            z-index: 50;
        }
        
        .settings-container:hover .settings-dropdown {
            display: block;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            display: block;
            color: #4B5563;
            text-decoration: none;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #F3F4F6;
            color: #1D4ED8;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            border-radius: 0.5rem;
        }
    </style>
</script>
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-md">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="text-2xl font-extrabold text-blue-600">Remind</div>
                <div class="flex items-center space-x-6">
                    <span class="text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                    <div class="settings-container relative">
                        <button class="text-gray-600 hover:text-gray-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                        <div class="settings-dropdown">
                            <a href="#" class="dropdown-item" onclick="showChangePasswordModal()">
                                <span class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    Change Password
                                </span>
                            </a>
                            <a href="logout.php" class="dropdown-item" onclick="return confirmLogout()">
                                <span class="flex items-center text-red-600 hover:text-red-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Add New Schedule -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold mb-4 text-blue-600">📅 Add New Schedule</h2>
                <form action="add_schedule.php" method="POST" class="space-y-4" onsubmit="setTimeout(function(){window.location.reload();},1000)">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Activity Name</label>
                        <input type="text" name="activity_name" required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" name="schedule_date" required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time</label>
                        <input type="time" name="schedule_time" required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reminder Time</label>
                        <select name="reminder_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
                            <option value="10">10 minutes before</option>
                            <option value="20">20 minutes before</option>
                            <option value="30">30 minutes before</option>
                            <option value="60">1 hour before</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 shadow-md">Add Schedule</button>
                </form>
            </div>

            <!-- Right Column: Upcoming and Recent -->
            <div class="space-y-6">
                <!-- Upcoming Schedules -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold mb-4 text-blue-600">📆 Upcoming Schedules</h2>
                    <div class="space-y-4">
                        <?php if ($schedules->num_rows > 0): ?>
                            <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg shadow-sm">
                                    <div>
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($schedule['activity_name']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('F j, Y', strtotime($schedule['schedule_date'])); ?> at 
                                            <?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?>
                                        </p>
                                    </div>
                                    <form action="delete_schedule.php" method="POST" class="inline" onsubmit="setTimeout(function(){window.location.reload();},1000)">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" class="text-red-500 font-semibold hover:text-red-600">❌ Delete</button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-600">No upcoming schedules.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold mb-4 text-blue-600">🔔 Recent Activities</h2>
                    <div class="space-y-4">
                        <?php if ($recentActivities->num_rows > 0): ?>
                            <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                                <div class="p-4 bg-gray-50 rounded-lg shadow-sm">
                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php echo date('F j, Y', strtotime($activity['schedule_date'])); ?> at 
                                        <?php echo date('g:i A', strtotime($activity['schedule_time'])); ?>
                                    </p>
                                    <p class="text-xs text-green-600 mt-1">✓ Notification sent</p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-600">No recent activities.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold mb-4">Change Password</h2>
            <form id="changePasswordForm" class="space-y-4">
            <div>
    <label class="block text-sm font-medium text-gray-700">Current Password</label>
    <div class="relative">
        <input type="password" id="currentPassword" required 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
        <span class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer">
            <i class="fas fa-eye toggle-password" data-target="currentPassword"></i>
        </span>
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">New Password</label>
    <div class="relative">
        <input type="password" id="newPassword" required 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
        <span class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer">
            <i class="fas fa-eye toggle-password" data-target="newPassword"></i>
        </span>
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
    <div class="relative">
        <input type="password" id="confirmPassword" required 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
        <span class="absolute right-2 top-1/2 transform -translate-y-1/2 cursor-pointer">
            <i class="fas fa-eye toggle-password" data-target="confirmPassword"></i>
        </span>
    </div>
</div>
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" onclick="hideChangePasswordModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>

        function showChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'block';
        }

        function hideChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
        }

        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords do not match',
                    text: 'Please make sure your new password and confirmation match.'
                });
                return;
            }
            
            // Send password change request
            fetch('change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    currentPassword: currentPassword,
                    newPassword: newPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Your password has been changed successfully.'
                    }).then(() => {
                        hideChangePasswordModal();
                        document.getElementById('changePasswordForm').reset();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to change password. Please try again.'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.'
                });
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('changePasswordModal');
            if (event.target == modal) {
                hideChangePasswordModal();
            }
        }

        // Add this in your script section or external JS file
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            // Toggle password visibility
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });
});
    </script>
</body>
</html>