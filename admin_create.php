<?php
session_start();
include '../connect.php'; // ใช้ไฟล์ PDO ของคุณ

// ตรวจสอบ Login ก่อนใช้งาน
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php"); // แก้ไขเส้นทางไปยัง login.php (สมมติว่า login.php อยู่ใน root)
    exit;
}

$reporter_name = $_SESSION['username']; // ดึงชื่อผู้ใช้จาก Session
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; // ตรวจสอบว่าผู้ใช้เป็น Admin หรือไม่
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าจากฟอร์ม
    $device_name = trim($_POST['device_name']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'] ?? 'Low'; 
    
    // Admin สามารถกำหนด Status ได้เอง, ผู้ใช้ทั่วไปจะเป็น 'Pending' เสมอ
    $status = $is_admin && isset($_POST['status']) ? $_POST['status'] : 'Pending'; 
    
    // ตรวจสอบข้อมูลเบื้องต้น
    if (empty($device_name) || empty($description)) {
        $message = "<div class='alert alert-danger'>❌ กรุณากรอกข้อมูลอุปกรณ์และรายละเอียดปัญหาให้ครบถ้วน.</div>";
    } else {

        // 2. เตรียมคำสั่ง SQL (ใช้ Prepared Statement)
        $sql = "INSERT INTO tickets (reporter_name, device_name, description, priority, status) 
                VALUES (:reporter_name, :device_name, :description, :priority, :status)";
        
        try {
            $stmt = $conn->prepare($sql);
            
            // 3. Bind Parameters
            $stmt->bindParam(':reporter_name', $reporter_name);
            $stmt->bindParam(':device_name', $device_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':status', $status);
            
            // 4. Execute Statement
            if ($stmt->execute()) {
                // *** Redirect ไปหน้า Dashboard เพื่อให้เห็นผลรวมทันที ***
                $_SESSION['success_message'] = "✅ แจ้งซ่อมสำเร็จ! ระบบบันทึกตั๋วของคุณแล้ว กรุณารอเจ้าหน้าที่ดำเนินการ";
                header("Location: ../index.php");
                exit;
            } else {
                $message = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อมอุปกรณ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ปรับปรุงสไตล์ให้ดูทันสมัยและสวยงามขึ้น */
        body { 
            background-color: #e9ecef; /* สีพื้นหลังอ่อนลง */
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-card { 
            max-width: 600px; 
            margin: 20px auto; 
            padding: 40px; 
            background: #fff; 
            border-radius: 15px; /* มุมโค้งมนมากขึ้น */
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* เงาเข้มขึ้น */
            border-left: 8px solid #007bff; /* แถบสีด้านซ้ายหนาขึ้น */
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .text-primary {
            color: #007bff !important;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.4);
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .admin-section {
            background-color: #fcebeb; /* สีแดงอ่อนสำหรับ Admin Section */
            border: 2px dashed #dc3545; /* เส้นประสีแดง */
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="form-card">
    <h3 class="text-center mb-4 text-primary"><i class="bi bi-tools me-2"></i> แจ้งซ่อม/แจ้งปัญหาใหม่</h3>
    <hr>
    
    <?php echo $message; ?>
    
    <!-- ตรวจสอบ action: หากไฟล์นี้อยู่ในโฟลเดอร์ tickets/ และทำงานในตัวเอง action ควรเป็น create.php หรือไม่ระบุ -->
    <form action="" method="post">
        
        <div class="mb-3">
            <label class="form-label fw-bold"><i class="bi bi-person me-1"></i> ชื่อผู้แจ้ง:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($reporter_name) ?>" disabled>
            </div>

        <div class="mb-3">
            <label for="device_name" class="form-label fw-bold"><i class="bi bi-laptop me-1"></i> ชื่ออุปกรณ์ / ชื่อโปรแกรม:</label>
            <input type="text" id="device_name" name="device_name" class="form-control" placeholder="เช่น: PC ห้อง 301, Printer HP 4500, โปรแกรมบัญชี" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> รายละเอียดปัญหา:</label>
            <textarea id="description" name="description" rows="5" class="form-control" placeholder="อธิบายอาการอย่างละเอียด เช่น: เปิดไม่ติด, จอฟ้า, พิมพ์แล้วกระดาษติด, เข้าสู่ระบบไม่ได้" required></textarea>
        </div>
        
        <div class="mb-4">
            <label for="priority" class="form-label fw-bold"><i class="bi bi-speedometer me-1"></i> ระดับความสำคัญ:</label>
            <select id="priority" name="priority" class="form-select">
                <option value="Low">Low (รอได้)</option>
                <option value="Medium">Medium (จำเป็นต้องใช้)</option>
                <option value="High">High (งานสำคัญติดขัด)</option>
                <option value="Urgent">Urgent (เร่งด่วนที่สุด)</option>
            </select>
        </div>

        <?php if ($is_admin): ?>
        <!-- ส่วนนี้จะแสดงเฉพาะผู้ดูแลระบบ (Admin) เท่านั้น -->
        <div class="mb-4 admin-section">
            <label for="status" class="form-label fw-bold text-danger"><i class="bi bi-shield-fill me-2"></i> สถานะเริ่มต้น (สำหรับ Admin เท่านั้น):</label>
            <select id="status" name="status" class="form-select">
                <option value="Pending" selected>Pending (รอดำเนินการ)</option>
                <option value="In Progress">In Progress (กำลังดำเนินการ)</option>
                <option value="Resolved">Resolved (แก้ไขเสร็จสิ้น)</option>
            </select>
            <small class="form-text text-danger mt-2 d-block">⚠️ การกำหนดสถานะเริ่มต้นควรใช้เมื่อ Admin สร้างตั๋วแทนผู้ใช้งานเท่านั้น</small>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3"><i class="bi bi-save me-2"></i> บันทึกการแจ้งซ่อม</button>
        
        <?php 
        // กำหนดเส้นทางกลับตามบทบาทของผู้ใช้
        $back_url = $is_admin ? '../admin/admin_dashboard.php' : '../index.php';
        $back_text = $is_admin ? 'กลับสู่ Admin Dashboard' : 'กลับสู่หน้าหลัก';
        ?>
        <!-- แก้ไขเส้นทางให้กลับไปหน้าหลักตามบทบาทของผู้ใช้ -->
        <a href="<?= $back_url ?>" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left"></i> <?= $back_text ?>
        </a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>