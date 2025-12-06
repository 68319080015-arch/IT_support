<?php
// --- บรรทัดสำหรับ Debugging ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --------------------------------

session_start();

// ตรวจสอบสิทธิ์: ต้องเป็น Admin เท่านั้น (อิงจากโค้ดใหม่ที่คุณให้มา)
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    // Redirect ไปหน้าล็อกอินหากไม่มีสิทธิ์
    header("Location: ../login.php");
    exit;
}

// ต้องมั่นใจว่าไฟล์นี้อยู่ระดับเดียวกับ connect.php
require_once '../connect.php';

$message = '';
$tickets = [];

// --- ตรวจสอบสถานะการลบจาก URL parameter (เพื่อแสดงข้อความแจ้งเตือน) ---
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted' && isset($_GET['ticket_id'])) {
        $message = "<div class='alert alert-success'>✅ ลบรายการแจ้งซ่อม ID #{$_GET['ticket_id']} สำเร็จแล้ว</div>";
    } elseif ($_GET['status'] === 'delete_error') {
        $message = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาดในการลบข้อมูล</div>";
    } elseif ($_GET['status'] === 'db_error' && isset($_GET['message'])) {
        $message = "<div class='alert alert-danger'>❌ Database Error: " . htmlspecialchars($_GET['message']) . "</div>";
    }
}


// --- อัปเดตตั๋วถ้ามี POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    // Technician ID ถูกเพิ่มเข้ามาใน Modal เพื่อเตรียมพร้อมสำหรับฟังก์ชันมอบหมายงานในอนาคต
    $technician_id = isset($_POST['technician_id']) && $_POST['technician_id'] !== '' ? $_POST['technician_id'] : null;


    try {
        // เตรียม SQL สำหรับโครงสร้าง DB ที่รองรับการแก้ไข
        $sql = "UPDATE tickets SET description=:description, priority=:priority, status=:status";

        // หากต้องการรองรับ technician_id ต้องเพิ่มคอลัมน์นี้ในตาราง tickets ก่อน
        /*
        if ($technician_id !== null) {
            $sql .= ", technician_id=:technician_id";
        }
        */

        $sql .= " WHERE id=:id";

        $stmt = $conn->prepare($sql);
        $params = [
            ':description' => $description,
            ':priority' => $priority,
            ':status' => $status,
            ':id' => $ticket_id
        ];

        /*
        if ($technician_id !== null) {
            $params[':technician_id'] = $technician_id;
        }
        */

        $stmt->execute($params);
        $message = "<div class='alert alert-success'>✅ อัปเดตตั๋ว ID {$ticket_id} สำเร็จแล้ว</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
    }
}

// ฟังก์ชันสีตามสถานะ
function getStatusBadge($status)
{
    switch ($status) {
        case 'Pending':
            return 'badge bg-warning text-dark';
        case 'In Progress':
            return 'badge bg-info text-white';
        case 'Resolved':
            return 'badge bg-success text-white';
        default:
            return 'badge bg-secondary';
    }
}

// รายการ priority และ status สำหรับ Modal
$priorities = ['Low', 'Medium', 'High', 'Urgent'];
$statuses = ['Pending', 'In Progress', 'Resolved'];

// --- ดึงตั๋วทั้งหมด ---
try {
    // ดึงข้อมูลตามคอลัมน์ที่มีอยู่ในโครงสร้าง DB (id, reporter_name, device_name, description, status, created_at, priority)
    $sql = "SELECT t.id, t.reporter_name, t.device_name, t.description, t.status, t.created_at, t.priority 
            FROM tickets t 
            ORDER BY t.created_at DESC";
    $stmt = $conn->query($sql);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>❌ Database Error (ดึงข้อมูล): " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | รายการแจ้งซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #e9ecef;
            /* Light gray background */
            font-family: 'Inter', sans-serif;
        }

        .navbar {
            border-bottom: 3px solid #007bff;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .table-dark th {
            border-color: #343a40 !important;
        }

        .table-responsive {
            border-radius: 12px;
            overflow-x: auto;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php">
                <i class="bi bi-gear-fill me-2"></i> Admin Panel
            </a>
            <div class="d-flex">
                <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4 text-dark"><i class="bi bi-journal-text me-3"></i> ตารางจัดการรายการแจ้งซ่อมทั้งหมด</h2>

        <?php echo $message; ?>

        <?php if (empty($tickets)): ?>
            <div class="alert alert-info shadow-sm">
                <i class="bi bi-info-circle me-2"></i> ยังไม่มีตั๋วในระบบ
            </div>
        <?php else: ?>
            <div class="card p-3 p-lg-4">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-primary text-white">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">ผู้แจ้ง</th>
                                <th scope="col">อุปกรณ์</th>
                                <th scope="col">รายละเอียด (ย่อ)</th>
                                <th scope="col">สถานะ</th>
                                <th scope="col">Priority</th>
                                <th scope="col">วันที่แจ้ง</th>
                                <th scope="col">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td>#<?php echo $t['id']; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($t['reporter_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['device_name']); ?></td>
                                    <td><?php echo mb_substr(htmlspecialchars($t['description']), 0, 30) . '...'; ?></td>
                                    <td><span class="<?php echo getStatusBadge($t['status']); ?>"><?php echo $t['status']; ?></span></td>
                                    <td class="<?php echo ($t['priority'] === 'Urgent') ? 'text-danger fw-bold' : ''; ?>"><?php echo htmlspecialchars($t['priority']); ?></td>
                                    <td><?php echo (new DateTime($t['created_at']))->format('d/m/Y H:i'); ?></td>
                                    <td class="text-nowrap">
                                        <!-- ปุ่มแก้ไข Modal -->
                                        <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $t['id']; ?>">
                                            <i class="bi bi-pencil-square"></i> แก้ไข
                                        </button>
                                        <!-- ปุ่มลบ Modal -->
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $t['id']; ?>">
                                            <i class="bi bi-trash"></i> ลบ
                                        </button>

                                        <!-- Modal สำหรับแก้ไข (Edit) -->
                                        <div class="modal fade" id="editModal<?php echo $t['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $t['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $t['id']; ?>"><i class="bi bi-pencil-fill me-2"></i> แก้ไขตั๋ว #<?php echo $t['id']; ?> (<?php echo htmlspecialchars($t['device_name']); ?>)</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">

                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-medium">ผู้แจ้ง</label>
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($t['reporter_name']); ?>" disabled>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-medium">อุปกรณ์</label>
                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($t['device_name']); ?>" disabled>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-medium">รายละเอียด</label>
                                                                <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($t['description']); ?></textarea>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-medium">Priority</label>
                                                                    <select name="priority" class="form-select">
                                                                        <?php foreach ($priorities as $p): ?>
                                                                            <option value="<?php echo $p; ?>" <?php if ($t['priority'] == $p) echo 'selected'; ?>><?php echo $p; ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-medium">สถานะ</label>
                                                                    <select name="status" class="form-select">
                                                                        <?php foreach ($statuses as $s): ?>
                                                                            <option value="<?php echo $s; ?>" <?php if ($t['status'] == $s) echo 'selected'; ?>><?php echo $s; ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> บันทึกการแก้ไข</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Modal Edit -->

                                        <!-- Modal สำหรับลบ (Delete) -->
                                        <!-- Modal สำหรับลบ (Delete) -->
                                        <div class="modal fade" id="deleteModal<?php echo $t['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $t['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $t['id']; ?>">
                                                            <i class="bi bi-exclamation-triangle-fill me-2"></i> ยืนยันการลบ
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>คุณแน่ใจหรือไม่ว่าต้องการลบรายการแจ้งซ่อม:</p>
                                                        <p class="fw-bold">#<?php echo $t['id']; ?> - <?php echo htmlspecialchars($t['device_name']); ?> (ผู้แจ้ง: <?php echo htmlspecialchars($t['reporter_name']); ?>)</p>
                                                        <p class="text-danger">การดำเนินการนี้ไม่สามารถยกเลิกได้!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                                        <!-- Form POST สำหรับลบ -->
                                                        <form method="POST" action="../delete.php" class="d-inline">
                                                            <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="bi bi-trash me-1"></i> ยืนยันการลบ
                                                            </button>
                                                        </form>
                                                                            
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- End Modal Delete -->

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <a href="admin_dashboard.php" class="btn btn-outline-secondary mt-4 mb-5"><i class="bi bi-arrow-left"></i> กลับสู่ Dashboard</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>