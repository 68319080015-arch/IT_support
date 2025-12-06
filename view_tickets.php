<?php
session_start();
require_once 'connect.php'; 

// ตรวจสอบ Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$all_tickets = [];
$error_message = '';

try {
    // ดึงตั๋วทั้งหมดของผู้ใช้ โดยให้ตั๋วล่าสุดอยู่บนสุด
    $sql = "SELECT id, device_name, description, status, priority, created_at 
            FROM tickets WHERE reporter_name = :username ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['username' => $username]);
    $all_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
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
    <title>รายการตั๋วทั้งหมด | ระบบแจ้งซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-tools me-2"></i> IT Ticket Center</a>
        <span class="navbar-text me-3 text-white">
            สวัสดี, **<?php echo htmlspecialchars($_SESSION['username']); ?>**
        </span>
        <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
    </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4"><i class="bi bi-list-task me-2 text-primary"></i> รายการแจ้งซ่อมทั้งหมดของฉัน</h1>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>อุปกรณ์</th>
                            <th>ระดับความสำคัญ</th>
                            <th>สถานะ</th>
                            <th>วันที่แจ้ง</th>
                            <th>รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_tickets) > 0): ?>
                            <?php foreach ($all_tickets as $ticket): ?>
                                <tr>
                                    <td>#<?php echo $ticket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['device_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['priority']); ?></td>
                                    <td><span class="<?php echo getStatusBadge($ticket['status']); ?>"><?php echo $ticket['status']; ?></span></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></td>
                                    <td>
                                        <a href="detail_tickets.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info text-white">
                                            <i class="bi bi-search"></i> ดู
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-3 text-muted">คุณยังไม่มีรายการแจ้งซ่อม</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับสู่ Dashboard</a>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> แจ้งซ่อมใหม่</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>