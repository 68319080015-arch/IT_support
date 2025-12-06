<?php
session_start();

// ตรวจสอบ Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// *** ต้องเรียกใช้ไฟล์เชื่อมต่อ PDO ***
require_once 'connect.php';

$username = $_SESSION['username'];

// ตัวแปรสถิติของผู้ใช้
$total_tickets = 0;
$pending_tickets = 0;
$in_progress_tickets = 0;
$resolved_tickets = 0;
$recent_tickets = [];

// --- ส่วนที่ 1: ดึงข้อมูลสถิติของผู้ใช้ (Simple PDO) ---
try {
    // รวมทุกตั๋วของผู้ใช้
    // บรรทัด 31 ถูกแก้ไขแล้ว
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE reporter_name = :username");
    $stmt->execute(['username' => $username]);
    $total_tickets = $stmt->fetchColumn();

    // แยกตามสถานะ
    // บรรทัด 42 ถูกแก้ไขแล้ว
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count 
                            FROM tickets WHERE reporter_name = :username GROUP BY status");
    $stmt->execute(['username' => $username]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        switch ($row['status']) {
            case 'Pending':
                $pending_tickets = $row['count'];
                break;
            case 'In Progress':
                $in_progress_tickets = $row['count'];
                break;
            case 'Resolved':
                $resolved_tickets = $row['count'];
                break;
        }
    }


    // --- ส่วนที่ 2: ดึง 5 ตั๋วล่าสุดของผู้ใช้ (Simple PDO) ---
    // บรรทัด 61 ถูกแก้ไขแล้ว
    $stmt = $conn->prepare("SELECT id, device_name, description, status, created_at 
                            FROM tickets WHERE reporter_name = :username ORDER BY created_at DESC LIMIT 5");
    $stmt->execute(['username' => $username]);
    $recent_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { // ใช้ PDOException เพื่อจับ Error
    die("Database Error: " . $e->getMessage());
}

// ฟังก์ชันสีตามสถานะ (ใช้ Bootstrap Badge)
function getStatusBadge($status)
{
    switch ($status) {
        case 'Pending':
            return 'badge bg-warning text-dark';
        case 'In Progress':
            return 'badge bg-info text-white';
        case 'Resolved':
            return 'badge bg-success';
        default:
            return 'badge bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ระบบแจ้งซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ... (Styles) ... */
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-tools me-2"></i> IT Ticket Center</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                <span class="navbar-text me-3 text-white">
                    สวัสดี <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <div class="row mb-5">
            <div class="col-12 text-center">
                <a href="create.php" class="btn btn-primary btn-lg shadow-lg" style="padding: 15px 50px; font-size: 1.5rem;">
                    <i class="bi bi-headset me-3"></i> แจ้งปัญหา/แจ้งซ่อมใหม่ที่นี่!
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card kpi-card bg-light p-3 shadow border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-muted">ตั๋วทั้งหมด</h5>
                        <p class="fs-2 fw-bold text-primary"><?php echo $total_tickets; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card kpi-card bg-warning p-3 shadow text-dark">
                    <div class="card-body">
                        <h5 class="card-title text-dark">รอดำเนินการ</h5>
                        <p class="fs-2 fw-bold"><?php echo $pending_tickets; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card kpi-card bg-success p-3 shadow text-white">
                    <div class="card-body">
                        <h5 class="card-title text-white">แก้ไขแล้ว</h5>
                        <p class="fs-2 fw-bold"><?php echo $resolved_tickets; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5><i class="bi bi-clock-history me-2 text-primary"></i> 5 รายการแจ้งซ่อมล่าสุดของฉัน</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>อุปกรณ์</th>
                                <th>อาการ</th>
                                <th>สถานะ</th>
                                <th>วันที่แจ้ง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_tickets) > 0): ?>
                                <?php foreach ($recent_tickets as $ticket): ?>
                                    <tr>
                                        <td>
                                            <a href="edit.php?id=<?php echo $ticket['id']; ?>">
                                                #<?php echo $ticket['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($ticket['device_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($ticket['description'], 0, 50)) . '...'; ?></td>
                                        <td><span class="<?php echo getStatusBadge($ticket['status']); ?>"><?php echo $ticket['status']; ?></span></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">ยังไม่มีรายการแจ้งซ่อม</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>    
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="view_tickets.php" class="text-decoration-none text-primary fw-bold">ดูสถานะและรายละเอียดทั้งหมด <i class="bi bi-arrow-right-short"></i></a>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>