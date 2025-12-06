<?php
session_start();
include 'connect.php'; // ใช้ไฟล์ PDO ของคุณ

// ตรวจสอบ Login ก่อนใช้งาน
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$reporter_name = $_SESSION['username']; // ดึงชื่อผู้ใช้จาก Session
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าจากฟอร์ม
    $device_name = trim($_POST['device_name']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'] ?? 'Low'; 
    $status = 'Pending'; 
    
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
                header("Location: index.php");
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
        body { background-color: #f8f9fa; }
        .form-card { 
            max-width: 600px; 
            margin: 50px auto; 
            padding: 30px; 
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
    </style>
</head>
<body>

<div class="form-card">
    <h3 class="text-center mb-4 text-primary"><i class="bi bi-tools me-2"></i> แจ้งซ่อม/แจ้งปัญหาใหม่</h3>
    <hr>
    
    <?php echo $message; ?>
    
    <form action="create.php" method="post">
        
        <div class="mb-3">
            <label class="form-label fw-bold">ชื่อผู้แจ้ง:</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($reporter_name) ?>" disabled>
            </div>

        <div class="mb-3">
            <label for="device_name" class="form-label fw-bold">ชื่ออุปกรณ์ / ชื่อโปรแกรม:</label>
            <input type="text" id="device_name" name="device_name" class="form-control" placeholder="เช่น: PC ห้อง 301, Printer HP 4500, โปรแกรมบัญชี" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label fw-bold">รายละเอียดปัญหา:</label>
            <textarea id="description" name="description" rows="5" class="form-control" placeholder="อธิบายอาการอย่างละเอียด เช่น: เปิดไม่ติด, จอฟ้า, พิมพ์แล้วกระดาษติด, เข้าสู่ระบบไม่ได้" required></textarea>
        </div>
        
        <div class="mb-4">
            <label for="priority" class="form-label fw-bold">ระดับความสำคัญ:</label>
            <select id="priority" name="priority" class="form-select">
                <option value="Low">Low (รอได้)</option>
                <option value="Medium">Medium (จำเป็นต้องใช้)</option>
                <option value="High">High (งานสำคัญติดขัด)</option>
                <option value="Urgent">Urgent (เร่งด่วนที่สุด)</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3"><i class="bi bi-save me-2"></i> บันทึกการแจ้งซ่อม</button>
        <a href="index.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-left"></i> กลับสู่หน้า Dashboard</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>