<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
include 'includes/db.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name || !$specialty || !$phone) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO doctors (name, specialty, phone) VALUES (?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("sss", $name, $specialty, $phone);
            if ($stmt->execute()) {
                $success = "Doctor added successfully.";
            } else {
                $errors[] = "Failed to add doctor: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$doctors = [];
$result = $conn->query("SELECT id, name, specialty, phone FROM doctors ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    $result->free();
} else {
    $errors[] = "Failed to fetch doctors: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Manage Doctors - Hospital Management System</title>
<style>
body {
    font-family: Arial, sans-serif;
    background:#f0f4f8;
    margin:0;
    padding:20px;
}
h1 {
    color:#2c3e50;
    margin-bottom:20px;
}
.container {
    max-width:900px;
    margin:0 auto;
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 8px 25px rgba(0,0,0,0.1);
}
form {
    margin-bottom:30px;
}
input[type="text"] {
    padding:10px;
    margin-right:15px;
    border:1.8px solid #ddd;
    border-radius:6px;
    font-size:16px;
    width:200px;
}
button {
    background:#4a90e2;
    color:#fff;
    padding:10px 18px;
    border:none;
    border-radius:6px;
    font-weight:600;
    cursor:pointer;
    font-size:16px;
}
button:hover {
    background:#357ABD;
}
table {
    width:100%;
    border-collapse: collapse;
}
th, td {
    border:1.5px solid #ddd;
    padding:12px;
    text-align:left;
}
th {
    background:#4a90e2;
    color:#fff;
}
.message {
    padding:15px;
    margin-bottom:20px;
    border-radius:6px;
    font-size:15px;
}
.error {
    background:#f8d7da;
    color:#842029;
}
.success {
    background:#d1e7dd;
    color:#0f5132;
}
a.back-btn {
    display:inline-block;
    margin-top:15px;
    text-decoration:none;
    color:#4a90e2;
    font-weight:600;
}
a.back-btn:hover {
    text-decoration:underline;
}
</style>
</head>
<body>
<div class="container">
    <h1>Manage Doctors</h1>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="manage_doctors.php" autocomplete="off">
        <input type="text" name="name" placeholder="Doctor Name" required />
        <input type="text" name="specialty" placeholder="Specialty" required />
        <input type="text" name="phone" placeholder="Phone Number" required />
        <button type="submit">Add Doctor</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Specialty</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($doctors)): ?>
                <tr><td colspan="3" style="text-align:center;">No doctors found.</td></tr>
            <?php else: ?>
                <?php foreach ($doctors as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['name']) ?></td>
                        <td><?= htmlspecialchars($doc['specialty']) ?></td>
                        <td><?= htmlspecialchars($doc['phone']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
</div>
</body>
</html>
