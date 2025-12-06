<?php
// Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // login.php อยู่ folder เดียวกับ delete.php
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once 'connect.php'; // connect.php อยู่ folder เดียวกับ delete.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = (int) $_POST['ticket_id'];

    try {
        $sql = "DELETE FROM tickets WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $ticket_id, PDO::PARAM_INT);
        $stmt->execute();

        // Redirect กลับไป admin_tickets.php
        header("Location: admin/admin_tickets.php?status=deleted&ticket_id=" . $ticket_id);
        exit;
    } catch (PDOException $e) {
        header("Location: admin/admin_tickets.php?status=db_error&message=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: admin/admin_tickets.php");
    exit;
}
?>
