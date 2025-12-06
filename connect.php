<?php
// กำหนดตัวแปรสำหรับฐานข้อมูล
$host = "localhost";
$dbname = "IT_support"; 
$username = "root";    
$password = "";        

try {
    // สร้างการเชื่อมต่อ PDO โดยตรง
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>