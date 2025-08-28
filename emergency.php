<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$errors = [];
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_proof = trim($_POST['patient_proof'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $emergency_reason = trim($_POST['emergency_reason'] ?? '');
    $assigned_doctor = trim($_POST['assigned_doctor'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';

    if (!$patient_name || !$patient_proof) {
        $errors[] = "Patient name and proof are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO emergency_cases 
            (patient_name, patient_proof, contact_phone, emergency_reason, assigned_doctor, priority) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $patient_name, $patient_proof, $contact_phone, $emergency_reason, $assigned_doctor, $priority);

        if ($stmt->execute()) {
            $success = "Emergency case registered successfully.";
        } else {
            $errors[] = "Failed to register emergency case.";
        }
        $stmt->close();
    }
}

// Handle search
$search = trim($_GET['search'] ?? '');
$cases = [];

if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM emergency_cases WHERE patient_name LIKE ? OR patient_proof LIKE ? ORDER BY reported_at DESC");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $cases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM emergency_cases ORDER BY reported_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cases[] = $row;
        }
        $result->free();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Emergency Cases - Hospital Management System</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f0f4f8; margin:0; padding:20px; }
        .container { max-width:1000px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 8px 25px rgba(0,0,0,0.1); }
        h1 { color:#c0392b; margin-bottom:20px; }
        form input, form textarea, form select { width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:1.8px solid #ddd; font-size:16px; }
        form button { background:#c0392b; color:#fff; padding:12px 20px; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-size:18px; }
        form button:hover { background:#992d22; }
        table { width:100%; border-collapse: collapse; margin-top:30px; }
        th, td { border:1.5px solid #ddd; padding:12px; text-align:left; }
        th { background:#c0392b; color:#fff; }
        .message { padding:15px; margin-bottom:20px; border-radius:6px; font-size:15px; }
        .error { background:#f8d7da; color:#842029; }
        .success { background:#d1e7dd; color:#0f5132; }
        a.back-btn { display:inline-block; margin-top:15px; text-decoration:none; color:#c0392b; font-weight:600; }
        a.back-btn:hover { text-decoration:underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>Emergency Case Registration</h1>

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

    <form method="POST" action="emergency.php" autocomplete="off">
        <input type="text" name="patient_name" placeholder="Patient Name" required />
        <input type="text" name="patient_proof" placeholder="Patient Proof (ID, Document Number)" required />
        <input type="text" name="contact_phone" placeholder="Contact Phone (optional)" />
        <textarea name="emergency_reason" placeholder="Reason for Emergency (optional)" rows="3"></textarea>
        <input type="text" name="assigned_doctor" placeholder="Doctor Assigned (optional)" />
        <select name="priority" required>
            <option value="High">High Priority</option>
            <option value="Medium" selected>Medium Priority</option>
            <option value="Low">Low Priority</option>
        </select>
        <button type="submit">Register Emergency Case</button>
    </form>

    <h2>Search Emergency Cases</h2>
    <form method="GET">
        <input type="text" name="search" placeholder="Search by patient name or proof" value="<?= htmlspecialchars($search) ?>" />
        <button type="submit">Search</button>
    </form>

    <h2>All Emergency Cases</h2>
    <table>
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Proof</th>
                <th>Phone</th>
                <th>Reason</th>
                <th>Doctor</th>
                <th>Priority</th>
                <th>Reported At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cases)): ?>
                <tr><td colspan="7" style="text-align:center;">No emergency cases found.</td></tr>
            <?php else: ?>
                <?php foreach ($cases as $case): ?>
                    <tr>
                        <td><?= htmlspecialchars($case['patient_name']) ?></td>
                        <td><?= htmlspecialchars($case['patient_proof']) ?></td>
                        <td><?= htmlspecialchars($case['contact_phone']) ?></td>
                        <td><?= nl2br(htmlspecialchars($case['emergency_reason'])) ?></td>
                        <td><?= htmlspecialchars($case['assigned_doctor']) ?></td>
                        <td><?= htmlspecialchars($case['priority']) ?></td>
                        <td><?= htmlspecialchars($case['reported_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="back-btn">&larr; Back to Dashboard</a>
</div>
</body>
</html>
