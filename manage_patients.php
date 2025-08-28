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
    $name = trim($_POST['patient_name'] ?? '');
    $proof = trim($_POST['patient_proof'] ?? '');
    $phone = trim($_POST['contact_phone'] ?? '');
    $reason = trim($_POST['emergency_reason'] ?? '');
    $doctor = trim($_POST['assigned_doctor'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';

    if (!$name || !$proof) {
        $errors[] = "Patient name and proof are required.";
    } else {
        // Insert into patients table
        $stmt1 = $conn->prepare("INSERT INTO patients (name, proof, contact_phone) VALUES (?, ?, ?)");
        if (!$stmt1) {
            $errors[] = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        } else {
            $stmt1->bind_param("sss", $name, $proof, $phone);
            if ($stmt1->execute()) {
                $patient_id = $stmt1->insert_id;

                // If high priority, insert into emergency_cases
                if ($priority === 'High') {
                    $stmt2 = $conn->prepare("INSERT INTO emergency_cases (patient_id, patient_name, patient_proof, contact_phone, emergency_reason, assigned_doctor, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if (!$stmt2) {
                        $errors[] = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                    } else {
                        $stmt2->bind_param("issssss", $patient_id, $name, $proof, $phone, $reason, $doctor, $priority);
                        if ($stmt2->execute()) {
                            $success = "Emergency case registered successfully.";
                        } else {
                            $errors[] = "Patient saved but failed to register emergency case.";
                        }
                        $stmt2->close();
                    }
                } else {
                    $success = "Patient registered successfully as a normal case.";
                }
            } else {
                $errors[] = "Failed to save patient data.";
            }
            $stmt1->close();
        }
    }
}

// Fetch emergency patients (joined with patients for safety)
$emergency_patients = [];
$result1 = $conn->query("SELECT * FROM emergency_cases ORDER BY reported_at DESC");
if ($result1) {
    $emergency_patients = $result1->fetch_all(MYSQLI_ASSOC);
    $result1->free();
}

// Fetch normal patients who are NOT in emergency_cases
$normal_patients = [];
$result2 = $conn->query("
    SELECT * FROM patients 
    WHERE id NOT IN (SELECT patient_id FROM emergency_cases)
    ORDER BY id DESC
");
if ($result2) {
    $normal_patients = $result2->fetch_all(MYSQLI_ASSOC);
    $result2->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Patients - Hospital Management</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            margin: 0; padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #2c3e50;
        }
        form input, form select, form textarea {
            width: 100%;
            padding: 12px 15px;
            margin: 12px 0;
            border-radius: 6px;
            border: 1.5px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        form input:focus, form select:focus, form textarea:focus {
            border-color: #2980b9;
            outline: none;
        }
        form button {
            background: #2980b9;
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        form button:hover {
            background: #1f6391;
        }
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
        }
        .error {
            background: #fcebea;
            color: #cc1f1a;
            border: 1px solid #f5c6cb;
        }
        .success {
            background: #e6ffed;
            color: #1e4620;
            border: 1px solid #c3f0b1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            padding: 14px 18px;
            border: 1.5px solid #ddd;
            text-align: left;
            font-size: 15px;
        }
        th {
            background: #34495e;
            color: white;
            user-select: none;
        }
        tr:nth-child(even) {
            background: #f7f9fc;
        }
        tr:hover {
            background: #e1e9f5;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Register Patient</h1>

    <?php if ($errors): ?>
        <div class="message error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" novalidate>
        <input type="text" name="patient_name" placeholder="Patient Name" required />
        <input type="text" name="patient_proof" placeholder="Patient Proof (ID or Document)" required />
        <input type="text" name="contact_phone" placeholder="Contact Phone (optional)" />
        <textarea name="emergency_reason" placeholder="Reason for Emergency (only if High priority)" rows="3"></textarea>
        <input type="text" name="assigned_doctor" placeholder="Doctor Assigned (optional)" />
        <select name="priority" required>
            <option value="High">High Priority</option>
            <option value="Medium" selected>Medium Priority</option>
            <option value="Low">Low Priority</option>
        </select>
        <button type="submit">Register Patient</button>
    </form>

    <h2>Emergency Patients (High Priority)</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Proof</th>
                <th>Phone</th>
                <th>Doctor</th>
                <th>Priority</th>
                <th>Reported At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($emergency_patients)): ?>
                <tr><td colspan="6" style="text-align:center;">No emergency cases found.</td></tr>
            <?php else: ?>
                <?php foreach ($emergency_patients as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['patient_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['patient_proof'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['contact_phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['assigned_doctor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['priority'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['reported_at'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Normal Patients (Medium/Low Priority)</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Proof</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($normal_patients)): ?>
                <tr><td colspan="3" style="text-align:center;">No normal patients found.</td></tr>
            <?php else: ?>
                <?php foreach ($normal_patients as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['proof'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['contact_phone'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
