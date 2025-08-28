<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $record_date = $_POST['record_date'] ?? date('Y-m-d');

    if (!$patient_id || !$diagnosis || !$treatment) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, diagnosis, treatment, record_date) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("isss", $patient_id, $diagnosis, $treatment, $record_date);
            if ($stmt->execute()) {
                $success = "Medical record added successfully.";
            } else {
                $errors[] = "Failed to add record: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch patients for dropdown
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Fetch all records
$records = $conn->query("
    SELECT mr.*, p.name AS patient_name
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.id
    ORDER BY mr.record_date DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Records</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; }
        .form-section input, .form-section textarea, .form-section select {
            width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ccc;
        }
        .form-section button {
            padding: 10px 20px; background-color: #27ae60; color: white; border: none;
            border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .form-section button:hover { background-color: #1e8449; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #2c3e50; color: white; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
<div class="container">
    <h1>Medical Records Management</h1>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-section">
        <label>Patient:</label>
        <select name="patient_id" required>
            <option value="">Select Patient</option>
            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Diagnosis:</label>
        <textarea name="diagnosis" rows="3" required></textarea>

        <label>Treatment:</label>
        <textarea name="treatment" rows="3" required></textarea>

        <label>Date:</label>
        <input type="date" name="record_date" value="<?= date('Y-m-d') ?>" required>

        <button type="submit">Add Medical Record</button>
    </form>

    <h2>All Medical Records</h2>
    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="4">No records found.</td></tr>
        <?php else: ?>
            <?php foreach ($records as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['patient_name']) ?></td>
                    <td><?= htmlspecialchars($r['diagnosis']) ?></td>
                    <td><?= htmlspecialchars($r['treatment']) ?></td>
                    <td><?= htmlspecialchars($r['record_date']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
