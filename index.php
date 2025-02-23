<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remind - Your Personal Scheduler</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-sm text-center">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-4">Welcome to <span class="text-blue-500">Remind</span></h1>
        <p class="text-gray-500 mb-6">Your smart scheduling assistant.</p>
        <div class="space-y-4">
            <a href="login.php" class="block w-full bg-blue-600 text-white py-3 rounded-xl font-semibold transition duration-300 hover:bg-blue-700 shadow-md">Login</a>
            <a href="signup.php" class="block w-full bg-green-500 text-white py-3 rounded-xl font-semibold transition duration-300 hover:bg-green-600 shadow-md">Sign Up</a>
        </div>
    </div>
</body>
</html>
