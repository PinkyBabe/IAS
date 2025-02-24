<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
            $stmt->bind_param("ssi", $otp, $expiry, $row['id']);
            
            if ($stmt->execute()) {
                // Send OTP email
                $otpMessage = "Your OTP code is: $otp\nValid for 5 minutes.";
                if (sendEmail($email, 'Your OTP Code', $otpMessage)) {
                    $_SESSION['temp_user_id'] = $row['id'];
                    header("Location: verify_otp.php");
                    exit();
                } else {
                    $error = "Failed to send OTP email. Please try again.";
                }
            } else {
                $error = "System error. Please try again.";
            }
        } else {
            // Send failed login attempt email
            $failedLoginMessage = "Someone attempted to login to your account with incorrect password.\n You are advised to change your password. \nIP Address: " . $_SERVER['REMOTE_ADDR'];
            sendEmail($email, 'Failed Login Attempt', $failedLoginMessage);
            
            logActivity($row['id'], "Failed login attempt");
            $error = "Invalid credentials";
        }
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Remind</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-sm">
        <h1 class="text-3xl font-extrabold text-gray-800 text-center mb-4">Welcome Back!</h1>
        <p class="text-gray-500 text-center mb-6">Login to <span class="text-blue-500 font-semibold">Remind</span></p>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" required 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
            </div>
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" required 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 pr-10">
                <button type="button" class="absolute right-3 top-9 text-gray-500 hover:text-gray-700" onclick="togglePassword()">
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 4C4 4 0 10 0 10s4 6 10 6 10-6 10-6-4-6-10-6zm0 10a4 4 0 110-8 4 4 0 010 8z"/>
                    </svg>
                </button>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold transition duration-300 hover:bg-blue-700 shadow-md">Login</button>
        </form>

        <p class="mt-4 text-center text-gray-600">
            Don't have an account? <a href="signup.php" class="text-blue-500 hover:text-blue-600 font-semibold">Sign Up</a>
        </p>
    </div>

    <script>
        function togglePassword() {
            let passwordField = document.getElementById("password");
            let eyeIcon = document.getElementById("eye-icon");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.innerHTML = '<path d="M10 4C4 4 0 10 0 10s4 6 10 6 10-6 10-6-4-6-10-6zm0 10a4 4 0 110-8 4 4 0 010 8zM0 10c1-1 4-6 10-6s9 5 10 6c-1 1-4 6-10 6S1 11 0 10z"/>';
            } else {
                passwordField.type = "password";
                eyeIcon.innerHTML = '<path d="M10 4C4 4 0 10 0 10s4 6 10 6 10-6 10-6-4-6-10-6zm0 10a4 4 0 110-8 4 4 0 010 8z"/>';
            }
        }
    </script>
</body>
</html>
