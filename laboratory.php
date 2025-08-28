<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = "";

// Fetch patients for dropdown
$patients = [];
$pResult = $conn->query("SELECT id, name FROM patients ORDER BY name");
if ($pResult) {
    $patients = $pResult->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_name = trim($_POST['test_name']);
    $patient_id = intval($_POST['patient_id']);
    $result = trim($_POST['result']);
    $test_date = $_POST['test_date'];

    if (empty($test_name) || empty($patient_id) || empty($result) || empty($test_date)) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO laboratory (test_name, patient_id, result, test_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $test_name, $patient_id, $result, $test_date);
        if ($stmt->execute()) {
            $success = "Laboratory record added successfully.";
        } else {
            $errors[] = "Failed to insert laboratory record. Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch lab records
$result = $conn->query("SELECT * FROM laboratory ORDER BY id DESC");
$records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laboratory - Hospital Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f4f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        form {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fbfd;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        form input, form select {
            padding: 10px;
            margin: 8px 0;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        form button {
            padding: 10px 20px;
            background: #2980b9;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        form button:hover {
            background: #1f6391;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .success {
            background: #dff0d8;
            color: #3c763d;
        }

        .error {
            background: #f2dede;
            color: #a94442;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #ccc;
        }

        th {
            background-color: #2980b9;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .no-records {
            text-align: center;
            color: #888;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Laboratory Management</h1>

        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Test Name</label>
            <input type="text" name="test_name" required>

            <label>Patient</label>
            <select name="patient_id" required>
                <option value="">-- Select Patient --</option>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['name']) ?> (ID: <?= $patient['id'] ?>)</option>
                <?php endforeach; ?>
            </select>

            <label>Result</label>
            <input type="text" name="result" required>

            <label>Test Date</label>
            <input type="date" name="test_date" required>

            <button type="submit">Add Record</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Test Name</th>
                    <th>Patient ID</th>
                    <th>Result</th>
                    <th>Test Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($records) > 0): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['test_name']) ?></td>
                            <td><?= htmlspecialchars($row['patient_id']) ?></td>
                            <td><?= htmlspecialchars($row['result']) ?></td>
                            <td><?= htmlspecialchars($row['test_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="no-records">No laboratory records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
