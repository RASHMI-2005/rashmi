<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Dashboard - Hospital Management System</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>Hospital Management System</header>
<nav>
    <a href="manage_doctors.php">Doctors</a>
    <a href="manage_staff.php">Staff</a>
    <a href="manage_patients.php">Patients</a>
    <a href="laboratory.php">Laboratory</a>
    <a href="medical_records.php">Medical Records</a>
    <a href="emergency.php">Emergency</a>
</nav>
<main>
    <p>Welcome, <strong><?= htmlspecialchars($_SESSION['user']) ?></strong>!</p>
    <p>Use the navigation above to manage the hospital system.</p>
    <a href="logout.php" class="logout-btn">Logout</a>
</main>
</body>
</html>