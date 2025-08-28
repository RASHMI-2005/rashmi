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
    $role = trim($_POST['role'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$name || !$role || !$phone || !$email) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("INSERT INTO staff (name, role, phone, email) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $role, $phone, $email);
            if ($stmt->execute()) {
                $success = "Staff added successfully.";
            } else {
                $errors[] = "Failed to add staff: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// Fetch existing staff to display
$staff_members = [];
$result = $conn->query("SELECT id, name, role, phone, email FROM staff ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $staff_members[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Manage Staff - Hospital Management System</title>
<style>
    body {font-family: Arial, sans-serif; background:#f0f4f8; margin:0; padding:20px;}
    h1 {color:#2c3e50; margin-bottom:20px;}
    .container {max-width:900px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 8px 25px rgba(0,0,0,0.1);}
    form {margin-bottom:30px;}
    input[type="text"], input[type="email"] {padding:10px; margin-right:15px; border:1.8px solid #ddd; border-radius:6px; font-size:16px; width:200px;}
    button {background:#4a90e2; color:#fff; padding:10px 18px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-size:16px;}
    button:hover {background:#357ABD;}
    table {width:100%; border-collapse: collapse;}
    th, td {border:1.5px solid #ddd; padding:12px; text-align:left;}
    th {background:#4a90e2; color:#fff;}
    .message {padding:15px; margin-bottom:20px; border-radius:6px; font-size:15px;}
    .error {background:#f8d7da; color:#842029;}
    .success {background:#d1e7dd; color:#0f5132;}
    a.back-btn {display:inline-block; margin-top:15px; text-decoration:none; color:#4a90e2; font-weight:600;}
    a.back-btn:hover {text-decoration:underline;}
</style>
</head>
<body>
<div class="container">
    <h1>Manage Staff</h1>

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

    <form method="POST" action="manage_staff.php" autocomplete="off">
        <input type="text" name="name" placeholder="Staff Name" required />
        <input type="text" name="role" placeholder="Role" required />
        <input type="text" name="phone" placeholder="Phone Number" required />
        <input type="email" name="email" placeholder="Email" required />
        <button type="submit">Add Staff</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staff_members)): ?>
                <tr><td colspan="4" style="text-align:center;">No staff found.</td></tr>
            <?php else: ?>
                <?php foreach ($staff_members as $staff): ?>
                    <tr>
                        <td><?= htmlspecialchars($staff['name']) ?></td>
                        <td><?= htmlspecialchars($staff['role']) ?></td>
                        <td><?= htmlspecialchars($staff['phone']) ?></td>
                        <td><?= htmlspecialchars($staff['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
</div>
</body>
</html>
