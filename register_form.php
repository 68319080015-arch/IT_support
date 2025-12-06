<?php
// register.php
session_start(); 
require_once 'connect.php'; // ต้องแน่ใจว่า connect.php สร้างตัวแปร $conn เป็น object MySQLi (เช่น $conn = new mysqli(...))

// ตรวจสอบ Redirect หาก Login อยู่แล้ว (ถ้ามี)
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    
    // รับและทำความสะอาดข้อมูล
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'user'; 

    // ตรวจสอบข้อมูลเบื้องต้น
    if (empty($username) || empty($email) || empty($password)) {
        $message = "<div class='alert alert-danger'>❌ กรุณากรอกข้อมูลให้ครบทุกช่อง!</div>";
    } elseif (strlen($password) < 6) {
        $message = "<div class='alert alert-danger'>⚠️ รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร!</div>";
    } else {

        // Hashing รหัสผ่าน
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // ใช้ SQL Placeholder เป็นเครื่องหมาย ?
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        
        try {
            // 1. เตรียมคำสั่ง
            $stmt = $conn->prepare($sql);
            
            // 2. ใช้ execute() พร้อมส่งค่าทั้งหมดในรูปแบบ array
            if ($stmt->execute([$username, $email, $passwordHash, $role])) {
                $message = "<div class='alert alert-success'>✅ สมัครสมาชิกเรียบร้อยแล้ว! โปรด <a href='login.php' class='alert-link fw-bold'>เข้าสู่ระบบ</a></div>";
            } else {
                // PDO Error Handling (ตรวจสอบ Error Code)
                $error_info = $stmt->errorInfo();
                if ($error_info[1] == 1062) { // 1062 คือ Error Code สำหรับ Duplicate entry (Unique Key)
                    $message = "<div class='alert alert-warning'>⚠️ Username หรือ Email นี้มีผู้ใช้งานอยู่แล้ว!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาด: " . $error_info[2] . "</div>";
                }
            }
            // ไม่จำเป็นต้องใช้ $stmt->close() ใน PDO
            
        } catch (PDOException $e) {
             // จับ Error ของ PDO
             error_log("Registration Error: " . $e->getMessage());
             $message = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาดในการเชื่อมต่อ: โปรดติดต่อผู้ดูแลระบบ</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิกใหม่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style> 
        /* ปรับปรุง CSS ให้ดูนุ่มนวลและทันสมัย */
        body { 
            background-color: #f7f9fc; /* สีพื้นหลังนุ่มนวล */
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh;
        }
        .register-card { 
            max-width: 420px; 
            width: 100%;
            padding: 40px; /* เพิ่ม Padding */
            background: #fff; 
            border-radius: 15px; /* ขอบโค้งมนมากขึ้น */
            box-shadow: 0 12px 30px rgba(0,0,0,0.15); /* เงาที่ดูมีมิติ */
            border-top: 5px solid #198754; /* แถบสีเขียวด้านบน */
        }
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-success:hover {
            background-color: #146c43;
            border-color: #146c43;
        }
    </style>
</head>
<body>
<div class="register-card">
    <h3 class="text-center mb-5 text-success">
        <i class="bi bi-person-plus-fill me-2"></i> 
        <span class="fw-bold">สร้างบัญชีใหม่</span>
    </h3>
    <?= $message; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label fw-bold">ชื่อผู้ใช้ (Username)</label>
            <input type="text" id="username" name="username" class="form-control form-control-lg" placeholder="ตั้งชื่อผู้ใช้" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label fw-bold">อีเมล (Email)</label>
            <input type="email" id="email" name="email" class="form-control form-control-lg" placeholder="email@example.com" required>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label fw-bold">รหัสผ่าน (Password)</label>
            <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="อย่างน้อย 6 ตัวอักษร" required>
        </div>

        <button type="submit" name="register" class="btn btn-success btn-lg w-100 mb-4">สมัครสมาชิก</button>
        <div class="text-center">
            <p class="text-muted mb-1">มีบัญชีอยู่แล้ว?</p>
            <a href="login.php" class="text-primary fw-bold text-decoration-none">เข้าสู่ระบบทันที</a>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>