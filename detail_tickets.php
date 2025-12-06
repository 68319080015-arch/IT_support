<?php
// เปิดการแสดง error สำหรับ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// เนื่องจากไฟล์นี้คาดว่าจะอยู่ในระดับเดียวกับ Admin หรือ User folder ให้แก้ไข path ให้ถูกต้อง
require_once 'connect.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$user_role = $_SESSION['role'] ?? 'user';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id || !is_numeric($ticket_id)) {
    die("<div class='alert alert-danger p-3 text-center'>
            ❌ ไม่พบ ID ตั๋ว
            <a href='view_tickets.php'>กลับ</a>
        </div>");
}

$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = :id");
$stmt->execute(['id' => $ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("<div class='alert alert-danger p-3 text-center'>
            ❌ ไม่พบตั๋ว #$ticket_id
            <a href='view_tickets.php'>กลับ</a>
        </div>");
}

// User ดูตั๋วตัวเองเท่านั้น
if ($user_role === 'user' && $ticket['reporter_name'] !== $username) {
    die("<div class='alert alert-danger p-3 text-center'>
            🚫 คุณไม่มีสิทธิ์เข้าตั๋วนี้
            <a href='view_tickets.php'>กลับ</a>
        </div>");
}

function getStatusBadge($status){
    switch($status){
        case 'Pending': return 'badge bg-warning text-dark';
        case 'In Progress': return 'badge bg-info text-white';
        case 'Resolved': return 'badge bg-success text-white';
        default: return 'badge bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ตั๋ว #<?php echo $ticket['id']; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body {
        background-color: #f4f7f6; /* Light background for detail page */
        font-family: 'Inter', sans-serif;
    }
    .navbar {
        border-bottom: 3px solid #17a2b8; /* Info color border */
    }
    .card-ticket {
        border-radius: 15px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        border: none;
    }
    .card-header-status {
        border-radius: 15px 15px 0 0;
        padding: 1rem 1.5rem;
        background-color: #17a2b8; /* Info color */
        color: white;
    }
    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.3rem;
        display: block;
    }
</style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="tickets.php">
                <i class="bi bi-ticket-detailed me-2"></i> รายการตั๋วของฉัน
            </a>
            <div class="d-flex">
                <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
            </div>
        </div>
    </nav>

<div class="container mt-5 mb-5">
    <div class="card card-ticket">
        <div class="card-header-status">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i> รายละเอียดตั๋ว #<?php echo $ticket['id']; ?></h4>
                <span class="<?php echo getStatusBadge($ticket['status']); ?> fs-6 p-2 rounded-pill"><?php echo $ticket['status']; ?></span>
            </div>
        </div>
        <div class="card-body p-4">
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="detail-label">ชื่อผู้แจ้ง</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($ticket['reporter_name']); ?>" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="detail-label">อุปกรณ์ที่แจ้ง</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($ticket['device_name']); ?>" disabled>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="detail-label">รายละเอียดปัญหา</label>
                <textarea class="form-control bg-light" rows="6" disabled><?php echo htmlspecialchars($ticket['description']); ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="detail-label">ระดับความสำคัญ</label>
                        <input type="text" class="form-control bg-light <?php echo ($ticket['priority'] === 'Urgent') ? 'text-danger fw-bold' : ''; ?>" value="<?php echo htmlspecialchars($ticket['priority']); ?>" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="detail-label">วันที่แจ้ง</label>
                        <input type="text" class="form-control bg-light" value="<?php echo (new DateTime($ticket['created_at']))->format('d/m/Y H:i'); ?>" disabled>
                    </div>
                </div>
            </div>

            <!-- ส่วนข้อมูลช่าง (หากมีคอลัมน์ technician_name/id)
            <div class="mb-3">
                <label class="detail-label">ผู้รับผิดชอบ</label>
                <input type="text" class="form-control bg-light" value="[ชื่อช่าง]" disabled>
            </div>
            -->

            <div class="d-flex justify-content-end mt-4">
                <a href="view_tickets.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับไปหน้ารายการตั๋ว</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>