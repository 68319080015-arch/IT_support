<?php
session_start();
// ตรวจสอบสิทธิ์: ต้องเป็น admin เท่านั้นในการจัดการผู้ใช้
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || 
    $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../connect.php'; 
$conn = $conn;

$message = '';
$current_user_id = $_SESSION['id']; // ID ของ Admin ที่กำลังล็อกอินอยู่

// --- 1. จัดการการลบผู้ใช้ (Delete User) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id_to_delete = $_POST['id'] ?? null;

    // ป้องกันไม่ให้ Admin ลบตัวเอง
    if ($user_id_to_delete && $user_id_to_delete != $current_user_id) {
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id_to_delete]);
            $message = "<div class='alert alert-success'>ลบผู้ใช้ ID #$user_id_to_delete สำเร็จแล้ว</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการลบ: " . $e->getMessage() . "</div>";
        }
    } else if ($user_id_to_delete == $current_user_id) {
        $message = "<div class='alert alert-danger'>ไม่สามารถลบบัญชีผู้ใช้ของคุณเองได้</div>";
    }
}

// --- 2. จัดการการอัปเดตข้อมูลผู้ใช้ (Username, Email, Role) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $user_id_to_update = $_POST['id'] ?? null;
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_role = $_POST['role'] ?? null;

    if (empty($user_id_to_update) || empty($new_username) || empty($new_email) || empty($new_role)) {
        $message = "<div class='alert alert-danger'>กรุณากรอกข้อมูลให้ครบถ้วน: ID ผู้ใช้, ชื่อผู้ใช้, อีเมล, และบทบาท</div>";
    } else if ($user_id_to_update == $current_user_id) {
        // ป้องกันไม่ให้ Admin แก้ไขข้อมูลของตัวเองผ่านหน้านี้ทั้งหมด
        $message = "<div class='alert alert-danger'>ไม่สามารถแก้ไขข้อมูลของผู้ใช้ที่กำลังล็อกอินอยู่ได้</div>";
    } else {
        try {
            // Check for duplicate username/email (excluding the user being updated)
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id");
            $stmt_check->execute(['username' => $new_username, 'email' => $new_email, 'id' => $user_id_to_update]);
            
            if ($stmt_check->rowCount() > 0) {
                 $message = "<div class='alert alert-danger'>ชื่อผู้ใช้หรืออีเมลนี้มีคนใช้อยู่แล้ว กรุณาเลือกชื่อใหม่</div>";
            } else {
                $sql = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'username' => $new_username,
                    'email' => $new_email,
                    'role' => $new_role,
                    'id' => $user_id_to_update
                ]);
                $message = "<div class='alert alert-success'>อัปเดตข้อมูลผู้ใช้ ID #$user_id_to_update สำเร็จแล้ว</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage() . "</div>";
        }
    }
}


// --- 3. ดึงรายการผู้ใช้ทั้งหมด ---
$users = [];
try {
    // ดึงผู้ใช้ทั้งหมด 
    $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
    $stmt = $conn->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการผู้ใช้ | IT Ticket System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background-color: #f8f9fa; }
.card { border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.table-responsive { max-height: 80vh; overflow-y: auto; }
.table-responsive table thead th { position: sticky; top: 0; background-color: #343a40; z-index: 10; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
<div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin_dashboard.php">
        <i class="bi bi-person-gear me-2"></i> Admin Panel
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="#">จัดการผู้ใช้</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_tickets.php">งานซ่อมทั้งหมด</a></li>
        </ul>
        <span class="navbar-text me-3">สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</span>
        <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
    </div>
</div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people-fill me-2"></i> บัญชีผู้ใช้ทั้งหมดในระบบ</h2>
        <!-- ปุ่มเพิ่มผู้ใช้ (ถ้ามี) สามารถเพิ่มตรงนี้ได้ -->
        <!-- <a href="add_user.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> เพิ่มผู้ใช้ใหม่</a> -->
    </div>

    <?php echo $message; ?>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#ID</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>อีเมล</th>
                        <th>บทบาท</th>
                        <th>วันที่สร้างบัญชี</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-exclamation-circle h4 d-block"></i>
                        ยังไม่มีผู้ใช้งานในระบบ
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                            $is_current_user = ($user['id'] == $current_user_id);
                            $row_class = $is_current_user ? 'table-info fw-bold' : '';
                            $role_badge = '';
                            if ($user['role'] == 'admin') $role_badge = 'bg-danger';
                            elseif ($user['role'] == 'technician') $role_badge = 'bg-primary';
                            else $role_badge = 'bg-secondary';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td>#<?php echo htmlspecialchars($user['id']); ?></td> 
                            <td><?php echo htmlspecialchars($user['username']); ?> 
                                <?php if ($is_current_user): ?>
                                    <span class="badge bg-success">คุณ</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge <?php echo $role_badge; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><?php echo (new DateTime($user['created_at']))->format('Y-m-d H:i'); ?></td> 
                            <td class="text-center">
                                <?php if (!$is_current_user): ?>
                                    <!-- ปุ่มแก้ไขผู้ใช้ (เปิด Modal) -->
                                    <button type="button" class="btn btn-sm btn-warning me-1" title="แก้ไขผู้ใช้"
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        data-user-id="<?php echo htmlspecialchars($user['id']); ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-current-role="<?php echo htmlspecialchars($user['role']); ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- ปุ่ม Delete -->
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้ <?php echo $user['username']; ?> นี้?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="ลบผู้ใช้">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>จัดการไม่ได้</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="editUserModalLabel"><i class="bi bi-person-fill-up me-2"></i> แก้ไขข้อมูลผู้ใช้</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="modal-user-id">

            <p>กำลังแก้ไขผู้ใช้ ID: <strong id="modal-user-id-display"></strong> (<strong id="modal-username-display"></strong>)</p>

            <div class="mb-3">
                <label for="username" class="form-label fw-bold">ชื่อผู้ใช้ (Username)</label>
                <input type="text" class="form-control" id="modal-username" name="username" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label fw-bold">อีเมล (Email)</label>
                <input type="email" class="form-control" id="modal-email" name="email" required>
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label fw-bold">บทบาท (Role)</label>
                <select class="form-select" id="modal-role" name="role" required>
                    <option value="user">User (ผู้แจ้งซ่อมทั่วไป)</option>
                    <option value="technician">Technician (ช่างเทคนิค)</option>
                    <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                </select>
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> บันทึกการแก้ไข</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript สำหรับจัดการข้อมูลใน Modal ก่อนแสดงผล
    var editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var userId = button.getAttribute('data-user-id');
        var username = button.getAttribute('data-username');
        var email = button.getAttribute('data-email'); // ดึงค่า email ใหม่
        var currentRole = button.getAttribute('data-current-role');
        
        var inputId = editUserModal.querySelector('#modal-user-id');
        var textIdDisplay = editUserModal.querySelector('#modal-user-id-display');
        var textUsernameDisplay = editUserModal.querySelector('#modal-username-display');
        var inputUsername = editUserModal.querySelector('#modal-username'); // Input field
        var inputEmail = editUserModal.querySelector('#modal-email'); // Input field
        var selectRole = editUserModal.querySelector('#modal-role');

        inputId.value = userId;
        textIdDisplay.textContent = '#' + userId;
        textUsernameDisplay.textContent = username;
        inputUsername.value = username; // ใส่ค่าลงใน input
        inputEmail.value = email; // ใส่ค่าลงใน input
        selectRole.value = currentRole;
    });
</script>
</body>
</html>