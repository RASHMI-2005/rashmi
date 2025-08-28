<?php
// signup.php
session_start();
include 'includes/db.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "Username or email already taken.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
                    if ($insert_stmt->execute()) {
                        $_SESSION['user'] = $username;
                        $_SESSION['user_id'] = $conn->insert_id;
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $errors[] = "Failed to register user.";
                    }
                    $insert_stmt->close();
                } else {
                    $errors[] = "Database error (insert): " . $conn->error;
                }
            }
            $stmt->close();
        } else {
            $errors[] = "Database error (select): " . $conn->error;
        }
    }
}
?>
<!-- signup.html -->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Sign Up - Hospital Management System</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-container">
    <h1>Create Account</h1>
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="signup.php">
        <input type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($username) ?>" />
        <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($email) ?>" />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
        <button type="submit">Sign Up</button>
    </form>
    <div class="bottom-text">
        Already have an account? <a href="login.php">Login here</a>.
    </div>
</div>
</body>
</html>