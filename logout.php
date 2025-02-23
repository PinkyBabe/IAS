<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], "User logged out");
    session_destroy();
}

header("Location: index.php");
exit();