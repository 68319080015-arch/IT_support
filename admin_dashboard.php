<?php
session_start();

// ตรวจสอบการ Login และ Role
// อนุญาตให้ 'admin' หรือ 'technician' เข้าถึง Dashboard จัดการนี้ได้
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'technician')) {
    header("Location: ../login.php");
    exit;
}
 
// 1. นำเข้าไฟล์เชื่อมต่อ PDO
require_once '../connect.php'; 
// $conn ถูกกำหนดค่าไว้ในไฟล์ connect.php แล้ว (เป็น PDO object)

$logged_in_user = $_SESSION['username'];
$logged_in_role = $_SESSION['role'];

// กำหนดตัวแปรเริ่มต้น
$total_tickets = 0;
$pending_tickets = 0;
$in_progress_tickets = 0;
$resolved_tickets = 0;
$urgent_tickets = 0;
$recent_tickets = [];

try {
    // 2. ดึงสถิติรวม (Total Tickets)
    $stmt_total = $conn->query("SELECT COUNT(*) AS total FROM tickets");
    $total_tickets = $stmt_total->fetchColumn(); 

    // 3. ดึงสถิติแยกตามสถานะ (Status Counts)
    $stmt_status = $conn->query("SELECT status, COUNT(*) AS count FROM tickets GROUP BY status");
    while ($row = $stmt_status->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['status'];
        $count = $row['count'];
        
        // เปรียบเทียบตามค่าสถานะในฐานข้อมูล
        if ($status === 'Pending') $pending_tickets += $count;
        elseif ($status === 'In Progress') $in_progress_tickets += $count;
        elseif ($status === 'Resolved') $resolved_tickets += $count;
    }

    // 4. ดึงสถิติเร่งด่วน (Urgent Tickets)
    $stmt_urgent = $conn->query("SELECT COUNT(*) AS urgent_count FROM tickets WHERE priority='Urgent'");
    $urgent_tickets = $stmt_urgent->fetchColumn();

    // 5. ดึงรายการแจ้งซ่อมล่าสุด 5 รายการ
    $sql_recent = "SELECT id, description, status, created_at 
                   FROM tickets 
                   ORDER BY created_at DESC 
                   LIMIT 5";
    $stmt_recent = $conn->query($sql_recent);
    $recent_tickets = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // แสดงข้อความข้อผิดพลาดถ้ามีปัญหาในการดึงข้อมูล
    echo "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | IT Ticket System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
/* CSS Styles */
body { background-color: #f8f9fa; }
.kpi-card { border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
.kpi-card:hover { transform: translateY(-3px); }
.bg-pending { background-color: #ffc107 !important; color: #333 !important; }
.bg-urgent { background-color: #dc3545 !important; color: #fff !important; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
<div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">
        <i class="bi bi-tools me-2"></i> Admin Dashboard
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item"><a class="nav-link active" href="#">Dashboard</a></li>
            <?php if ($logged_in_role === 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="admin_users.php">จัดการผู้ใช้</a></li>
            <?php endif; ?>
            <!-- admin_tickets.php ในที่นี้จะชี้ไปยัง technician_dashboard.php ที่เป็นมุมมองตารางงานซ่อมทั้งหมดสำหรับ Admin/Tech -->
            <li class="nav-item"><a class="nav-link" href="technician_dashboard.php">งานซ่อมทั้งหมด</a></li>
        </ul>
        <span class="navbar-text me-3">สวัสดี, <?php echo htmlspecialchars($logged_in_user); ?> (<?php echo ucfirst($logged_in_role); ?>)</span>
        <!-- การออกจากระบบควรชี้ไปที่ root ของระบบ -->
        <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
    </div>
</div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>ภาพรวมระบบ</h2>
        <!-- ลิงก์แจ้งซ่อมใหม่ ชี้ไปที่โฟลเดอร์ user/create.php -->
        <a href="../user/create.php" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle-fill me-2"></i> แจ้งซ่อมใหม่</a>
    </div>

    <div class="row mb-5">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card kpi-card text-dark bg-light p-3">
                <div class="card-body">
                    <i class="bi bi-ticket-detailed-fill h3 float-end text-primary"></i>
                    <h5 class="card-title text-muted">รวมตั๋วทั้งหมด</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo number_format($total_tickets); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card kpi-card bg-pending p-3">
                <div class="card-body">
                    <i class="bi bi-hourglass-split h3 float-end"></i>
                    <h5 class="card-title text-dark">รอการดำเนินการ</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo number_format($pending_tickets); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card kpi-card bg-info text-white p-3">
                <div class="card-body">
                    <i class="bi bi-tools h3 float-end"></i>
                    <h5 class="card-title">กำลังดำเนินการ</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo number_format($in_progress_tickets); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card kpi-card bg-urgent p-3">
                <div class="card-body">
                    <i class="bi bi-lightning-fill h3 float-end"></i>
                    <h5 class="card-title">เร่งด่วน</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo number_format($urgent_tickets); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card kpi-card p-3 h-100">
                <h5 class="card-title mb-3"><i class="bi bi-bar-chart-fill me-2 text-secondary"></i> สถิติสถานะ</h5>
                <ul class="list-unstyled mt-3">
                    <li>รอการดำเนินการ: **<?php echo $pending_tickets; ?>**</li>
                    <li>กำลังดำเนินการ: **<?php echo $in_progress_tickets; ?>**</li>
                    <li>แก้ไขเสร็จสิ้น: **<?php echo $resolved_tickets; ?>**</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card kpi-card p-3 h-100">
                <h5 class="card-title mb-3"><i class="bi bi-clock-history me-2 text-secondary"></i> แจ้งซ่อมล่าสุด</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#ID</th>
                                <th>ปัญหา</th>
                                <th>สถานะ</th>
                                <th>แจ้งเมื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recent_tickets)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">ยังไม่มีรายการล่าสุด</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_tickets as $ticket): ?>
                                <?php
                                    // ฟังก์ชันนี้ไม่ได้ถูก include ต้องเขียนใหม่หรือใช้ logic ตรงนี้
                                    $status_lower = strtolower(str_replace(' ', '', $ticket['status']));
                                    if ($status_lower == 'pending') $badge_class = 'badge bg-warning text-dark';
                                    elseif ($status_lower == 'inprogress') $badge_class = 'badge bg-info text-white';
                                    elseif ($status_lower == 'resolved') $badge_class = 'badge bg-success text-white';
                                    else $badge_class = 'badge bg-secondary text-white';
                                ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($ticket['id']); ?></td> 
                                    <td><?php echo htmlspecialchars(mb_substr($ticket['description'], 0, 30, 'UTF-8')) . (mb_strlen($ticket['description'], 'UTF-8') > 30 ? '...' : ''); ?></td>
                                    <td><span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($ticket['status']); ?></span></td>
                                    <td><?php echo (new DateTime($ticket['created_at']))->format('Y-m-d H:i'); ?></td> 
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- ลิงก์ไปยังหน้าจัดการตั๋ว (technician_dashboard.php) -->
                <div class="text-end mt-2"><a href="technician_dashboard.php">ดูทั้งหมด <i class="bi bi-arrow-right-short"></i></a></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>