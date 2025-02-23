<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        logActivity($userId, "User registered");
        
        // Send welcome email using the centralized email function
        $welcomeMessage = "Welcome $name! Your account has been created successfully.";
        if (sendEmail($email, 'Welcome to Schedule System', $welcomeMessage)) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Account created but failed to send welcome email. Please continue to login.";
            header("Location: login.php");
            exit();
        }
    } else {
        $error = "Failed to create account. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Remind</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-sm">
        <h1 class="text-3xl font-extrabold text-gray-800 text-center mb-4">Create an Account</h1>
        <p class="text-gray-500 text-center mb-6">Join <span class="text-blue-500 font-semibold">Remind</span> today!</p>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4" onsubmit="return validateForm()">
            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2" 
                    pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" 
                    title="Please enter a valid email address">
            </div>
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 pr-10">
                <button type="button" class="absolute right-3 top-9 text-gray-500 hover:text-gray-700" onclick="togglePassword('password', 'eye-icon')">
                    <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 4C4 4 0 10 0 10s4 6 10 6 10-6 10-6-4-6-10-6zm0 10a4 4 0 110-8 4 4 0 010 8z"/>
                    </svg>
                </button>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold transition duration-300 hover:bg-blue-700 shadow-md">Sign Up</button>
        </form>

        <p class="mt-4 text-center text-gray-600">
            Already have an account? <a href="login.php" class="text-blue-500 hover:text-blue-600 font-semibold">Login</a>
        </p>
    </div>

    <script>
        function togglePassword(passwordId, eyeId) {
            let passwordField = document.getElementById(passwordId);
            let eyeIcon = document.getElementById(eyeId);

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.innerHTML = '<path d="M10 4C4 4 0 10 0 10s4 6 10 6 10-6 10-6-4-6-10-6zm0 10a4 4 0 110-8 4 4 0 010 8zM0 10c1-1 4-6 10-6s9 5 10 6c-1 1-4 6-10 6S1 11 0 10z"/>';
            } else {
                passwordField.type = "password";
                eyeIcon.innerHTML = '<path d="M10 4C4 4 0 10 0 10s4 6 10 6 10-6 10-6-4-6-10-6zm0 10a4 4 0 110-8 4 4 0 010 8z"/>';
            }
        }

        function validateForm() {
            let password = document.getElementById("password").value;
            let confirmPassword = document.getElementById("confirm_password").value;
            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
