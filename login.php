<?php
// login.php

// *** ต้องแน่ใจว่าได้ทำการ 'require_once 'config.php';' เพื่อเรียกใช้ $conn ***
// ถ้าไฟล์ config.php ยังไม่มี ให้สร้างขึ้นมาก่อน
require_once 'connect.php'; 

session_start();


$login_err = "";

// 1. ตรวจสอบ Redirect หาก Login อยู่แล้ว
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'technician') {
        header("Location: admin/admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ใช้การตรวจสอบความปลอดภัยที่รัดกุมขึ้น:
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; 

    // 2. ตรรกะการตรวจสอบผู้ใช้
    if (empty($username) || empty($password)) {
        $login_err = "กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = :username";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC); 
                
                if (password_verify($password, $user['password'])) {
                    
                    // Login สำเร็จ: สร้าง Session
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role']; 

                    // Redirect ตาม Role
                    if ($user['role'] === 'admin' || $user['role'] === 'technician') {
                        header("Location: admin/admin_dashboard.php"); 
                    } else {
                        header("Location: index.php"); 
                    }
                    exit;

                } else {
                    // ใช้ข้อความแสดงข้อผิดพลาดแบบทั่วไปเพื่อความปลอดภัย
                    $login_err = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"; 
                }
            } else {
                // ใช้ข้อความแสดงข้อผิดพลาดแบบทั่วไปเพื่อความปลอดภัย
                $login_err = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        
        } catch (PDOException $e) {
            // ควรมีการ log error นี้ใน production แต่แสดงข้อความทั่วไปให้ผู้ใช้
            error_log("SQL Error in login: " . $e->getMessage());
            $login_err = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล โปรดลองใหม่อีกครั้ง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* CSS เพิ่มเติมเพื่อให้ดูดี */
        body {
            background-color: #e9ecef; /* สีพื้นหลังอ่อน ๆ */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px; /* จำกัดความกว้างของ Card */
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px; /* ขอบโค้งมน */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); /* เงาที่ดูมีมิติ */
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004d99;
        }
    </style>
</head>
<body>
<div class="login-card">
    <h3 class="text-center mb-4 text-primary">เข้าสู่ระบบ <span role="img" aria-label="key">🔑</span></h3>
    
    <?php if(!empty($login_err)): ?>
        <div class="alert alert-danger text-center" role="alert">
            <?= htmlspecialchars($login_err) ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="mb-3">
            <label for="username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" id="username" name="username" class="form-control form-control-lg" placeholder="Username" required autofocus> 
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">รหัสผ่าน</label>
            <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">เข้าสู่ระบบ</button>
        <div class="text-center mt-4">
            <p class="mb-0 text-muted">ยังไม่มีบัญชีใช่หรือไม่?</p>
            <a href="register_form.php" class="text-decoration-none">สมัครสมาชิกใหม่</a>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>