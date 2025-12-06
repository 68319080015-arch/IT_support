<?php
session_start();

// ล้างค่าตัวแปร Session ทั้งหมด
$_SESSION = array();

// ถ้ามีการใช้ Session Cookies ให้ลบ Session Cookie ด้วย
// การทำลาย Session ID บนเบราว์เซอร์ของผู้ใช้
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย Session
session_destroy();

// Redirect ไปที่หน้า Login
header("Location: login.php");
exit;
?>