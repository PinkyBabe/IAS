<?php
require_once 'config.php';

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = $_POST['otp'];
    $userId = $_SESSION['temp_user_id'];

    $stmt = $conn->prepare("SELECT otp, otp_expiry, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user['otp'] == $otp && strtotime($user['otp_expiry']) > time()) {
        $_SESSION['user_id'] = $userId;
        logActivity($userId, "Successful login");
        header("Location: dashboard.php");
        exit();
    } else {
        // Send breach attempt email
        $breachMessage = "Someone attempted to access your account with an invalid OTP.\nIP Address: " . $_SERVER['REMOTE_ADDR'];
        sendEmail($user['email'], 'Security Alert - Invalid OTP Attempt', $breachMessage);
        
        logActivity($userId, "Invalid OTP attempt");
        $error = "Invalid or expired OTP";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Remind</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h1 class="text-2xl font-bold text-center text-blue-600">🔐 Verify OTP</h1>
        <p class="text-gray-600 text-center mb-6">Enter the 6-digit OTP sent to your email.</p>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Enter OTP</label>
                <input type="text" name="otp" required maxlength="6" pattern="\d{6}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-4 py-2 text-center text-xl tracking-widest"
                    inputmode="numeric" autofocus>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 shadow-md">
                ✅ Verify OTP
            </button>
        </form>

        <p class="mt-4 text-center text-gray-600">
            Didn't receive the OTP?  
            <a href="resend_otp.php" class="text-blue-500 font-semibold hover:text-blue-600">Resend OTP</a>
        </p>
    </div>

</body>
</html>
