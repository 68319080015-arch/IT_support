<?php
// --- บรรทัดสำหรับ Debugging ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --------------------------------

session_start();

// ตรวจสอบสิทธิ์: ต้องเป็น Technician หรือ Admin เท่านั้น
if (
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'technician')
) {
    // แก้ไข path การ Redirect ให้ถูกต้อง (สมมติไฟล์นี้อยู่ในโฟลเดอร์ admin/ -> ../login.php)
    header("Location: ../login.php");
    exit;
}

// ต้องมั่นใจว่าไฟล์นี้อยู่ระดับเดียวกับ connect.php (เช่น ในโฟลเดอร์ admin/ -> ../connect.php)
require_once '../connect.php';
// เชื่อมต่อฐานข้อมูลสำเร็จ
$conn = $conn;

$ticket_id = $_GET['id'] ?? null;
$message = '';
$ticket = null;
$technicians = [];
$tech_role = $_SESSION['role'];
$tech_id = $_SESSION['id'];

if (!$ticket_id) {
    // ถ้าไม่มี ID ให้กลับไปหน้าแสดงรายการตั๋ว (Admin หรือ Technician)
    $redirect_page = ($tech_role === 'admin') ? 'admin_tickets.php' : 'technician_dashboard.php';
    header("Location: " . $redirect_page);
    exit;
}

// --- 1. อัปเดตสถานะและมอบหมายงาน ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_detail') {
    $new_status = $_POST['status'] ?? null;
    $new_tech_id = $_POST['technician_id'] ?? null;
    $resolution_note = trim($_POST['resolution_note'] ?? '');

    if ($new_status) {
        try {
            $sql = "UPDATE tickets 
                    SET status = :status, resolution_note = :resolution_note, updated_at = NOW()";

            $params = [
                'status' => $new_status,
                'resolution_note' => $resolution_note,
                'id' => $ticket_id
            ];

            // ตรวจสอบและกำหนด technician_id
            if (!empty($new_tech_id)) {
                $sql .= ", technician_id = :tech_id";
                $params['tech_id'] = $new_tech_id;
            } else {
                $sql .= ", technician_id = NULL";
            }

            $sql .= " WHERE id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $message = "<div class='alert alert-success'>อัปเดตรายการแจ้งซ่อม #$ticket_id สำเร็จแล้ว</div>";

            // Re-fetch ticket data after update to show current values
            // (The code below will handle the re-fetch)

        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage() . "</div>";
        }
    }
}

// --- 2. ดึงข้อมูลรายการแจ้งซ่อมและช่างเทคนิค ---
try {
    // ดึงรายชื่อช่างเทคนิค
    $stmt_tech = $conn->query("SELECT id, username FROM users WHERE role = 'technician'");
    $technicians = $stmt_tech->fetchAll(PDO::FETCH_ASSOC);

    // ดึงรายละเอียดตั๋ว (แก้ไขกลับไปใช้ t.user_id ซึ่งเป็นคอลัมน์ Foreign Key ที่เชื่อมไปยังผู้แจ้งในตาราง users)
    $sql = "SELECT t.*, u.username AS reporter_name, tech.username AS technician_name, u.email AS reporter_email
            FROM tickets t
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN users tech ON t.technician_id = tech.id
            WHERE t.id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $message = "<div class='alert alert-warning'>ไม่พบรายการแจ้งซ่อม ID #$ticket_id</div>";
    }
} catch (PDOException $e) {
    // แสดงข้อผิดพลาดจากฐานข้อมูล
    $message = "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
}

// ฟังก์ชันแสดง Badge สถานะ
function get_status_badge($status)
{
    $status_lower = strtolower(str_replace(' ', '', $status));
    if ($status_lower == 'pending') $badge_class = 'bg-warning text-dark';
    elseif ($status_lower == 'inprogress') $badge_class = 'bg-info text-white';
    elseif ($status_lower == 'resolved') $badge_class = 'bg-success text-white';
    else $badge_class = 'bg-secondary text-white';

    return '<span class="badge ' . $badge_class . ' fs-6">' . htmlspecialchars($status) . '</span>';
}

// ป้องกัน $ticket['priority'] ไม่มีค่า
$priority_class = '';
if ($ticket && isset($ticket['priority'])) {
    $priority_class = ($ticket['priority'] === 'Urgent') ? 'text-danger fw-bold' : '';
}

// ป้องกัน technician_id ไม่มีค่า
$current_tech_id = $ticket['technician_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแก้ไข #<?php echo htmlspecialchars($ticket_id); ?> | IT Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card-detail {
            border-left: 5px solid #0d6efd;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .detail-item {
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }

        .detail-item:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo ($tech_role === 'admin') ? 'admin_dashboard.php' : 'technician_dashboard.php'; ?>">
                <i class="bi bi-tools me-2"></i> <?php echo ($tech_role === 'admin') ? 'Admin Panel' : 'Technician Panel'; ?>
            </a>
            <div class="d-flex">
                <a href="<?php echo ($tech_role === 'admin') ? 'admin_tickets.php' : 'technician_dashboard.php'; ?>" class="btn btn-outline-light me-2 btn-sm">
                    <i class="bi bi-arrow-left"></i> กลับไปรายการตั๋ว
                </a>
                <!-- แก้ไข path การออกจากระบบให้ถูกต้อง -->
                <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-journal-text me-2"></i> รายละเอียดรายการแจ้งซ่อม #<?php echo htmlspecialchars($ticket_id); ?></h2>
        </div>

        <?php echo $message; ?>

        <?php if ($ticket): ?>
            <div class="row">
                <!-- รายละเอียดตั๋ว (ซ้าย) -->
                <div class="col-lg-6 mb-4">
                    <div class="card p-4 card-detail h-100">
                        <h5 class="mb-3 text-primary"><i class="bi bi-info-circle me-2"></i> ข้อมูลปัญหา</h5>

                        <div class="detail-item">
                            <span class="fw-bold">สถานะ:</span> <?php echo get_status_badge($ticket['status']); ?>
                        </div>
                        <div class="detail-item">
                            <span class="fw-bold">ลำดับความสำคัญ:</span> <span class="<?php echo $priority_class; ?>"><?php echo htmlspecialchars($ticket['priority']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="fw-bold">ผู้แจ้ง:</span> <?php echo htmlspecialchars($ticket['reporter_name']); ?>
                        </div>
                        <div class="detail-item">
                            <span class="fw-bold">อีเมลผู้แจ้ง:</span> <?php echo htmlspecialchars($ticket['reporter_email']); ?>
                        </div>
                        <div class="detail-item">
                            <span class="fw-bold">ที่ตั้ง:</span> <?php echo htmlspecialchars($ticket['location']); ?>
                        </div>
                        <div class="detail-item">
                            <span class="fw-bold">วันที่แจ้ง:</span> <?php echo (new DateTime($ticket['created_at']))->format('d/m/Y H:i'); ?>
                        </div>
                        <?php if ($ticket['updated_at']): ?>
                            <div class="detail-item">
                                <span class="fw-bold">อัปเดตล่าสุด:</span> <?php echo (new DateTime($ticket['updated_at']))->format('d/m/Y H:i'); ?>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="mb-3">
                            <h6 class="fw-bold text-dark">รายละเอียด:</h6>
                            <p class="border p-3 bg-light rounded"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                        </div>

                        <hr>

                        <!-- บันทึกการแก้ไข (Resolution Note) -->
                        <h5 class="mb-3 text-success"><i class="bi bi-check-circle-fill me-2"></i> บันทึกการแก้ไข (Resolution)</h5>
                        <?php if (!empty($ticket['resolution_note'])): ?>
                            <p class="border border-success p-3 bg-light rounded"><?php echo nl2br(htmlspecialchars($ticket['resolution_note'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">ยังไม่มีบันทึกการแก้ไข</p>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- ฟอร์มจัดการแก้ไข (ขวา) -->
                <div class="col-lg-6 mb-4">
                    <div class="card p-4 h-100">
                        <h5 class="mb-3 text-danger"><i class="bi bi-pencil-square me-2"></i> ฟอร์มจัดการ</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_detail">

                            <div class="mb-3">
                                <label for="status" class="form-label fw-bold">อัปเดตสถานะ</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Pending" <?php echo ($ticket['status'] == 'Pending') ? 'selected' : ''; ?>>Pending (รอการดำเนินการ)</option>
                                    <option value="In Progress" <?php echo ($ticket['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress (กำลังดำเนินการ)</option>
                                    <option value="Resolved" <?php echo ($ticket['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved (แก้ไขเสร็จสิ้น)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="technician_id" class="form-label fw-bold">มอบหมายงานให้ช่าง</label>
                                <select class="form-select" id="technician_id" name="technician_id" <?php echo ($tech_role !== 'admin' ? 'disabled' : ''); ?>>
                                    <option value="">-- ไม่มอบหมาย / ยกเลิกการมอบหมาย --</option>
                                    <?php
                                    $current_tech_id = $ticket['technician_id'];
                                    foreach ($technicians as $tech):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($tech['id']); ?>"
                                            <?php echo ($tech['id'] == $current_tech_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tech['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">ปัจจุบันมอบหมายให้: <strong><?php echo htmlspecialchars($ticket['technician_name'] ?: 'ยังไม่ได้รับมอบหมาย'); ?></strong></small>
                                <?php if ($tech_role !== 'admin'): ?>
                                    <small class="d-block text-danger">เฉพาะ Admin เท่านั้นที่สามารถมอบหมายงานได้</small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label for="resolution_note" class="form-label fw-bold">บันทึกการแก้ไข/หมายเหตุสรุปงาน</label>
                                <textarea class="form-control" id="resolution_note" name="resolution_note" rows="5"
                                    placeholder="บันทึกสิ่งที่ดำเนินการแก้ไข. **สำคัญมากเมื่อสถานะเป็น Resolved**"><?php echo htmlspecialchars($ticket['resolution_note']); ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg"><i class="bi bi-save-fill me-2"></i> บันทึกการจัดการ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>