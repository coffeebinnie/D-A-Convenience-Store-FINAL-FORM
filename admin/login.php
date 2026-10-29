<?php
require __DIR__ . "/init.php";

if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$usernameValue = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usernameValue = trim($_POST["username"] ?? "");
    $passwordValue = $_POST["password"] ?? "";
    $token = $_POST["csrf_token"] ?? null;

    if (!verifyCsrf($token)) {
        $error = "Your session expired. Please try again.";
    } elseif ($usernameValue === "" || $passwordValue === "") {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, username, password_hash
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $usernameValue);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($passwordValue, $admin["password_hash"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = (int) $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            header("Location: dashboard.php");
            exit;
        }

        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login | D & A Convenience Store</title>
    <link rel="stylesheet" href="../assets/styles/admin.css?v=3" />
</head>
<body class="admin-login-body">

<div class="login-wrapper">

    <div class="login-brand">
        <img src="../assets/images/logo1.jpg" alt="D & A Logo">
        <h1>
            <span>D &amp; A</span>
            <small>Convenience Store</small>
        </h1>
        <p>Admin Panel</p>
    </div>

    <div class="login-card">
        <h2>Admin Login</h2>
        <p class="login-subtitle">
            This area is for store administrators only.
        </p>

        <?php if ($error !== ""): ?>
            <p class="admin-alert admin-alert-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php" class="admin-form">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <label for="username">
                Username
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($usernameValue) ?>"
                    autocomplete="username"
                    required
                >
            </label>

            <label for="password">
                Password
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </label>

            <button type="submit" class="admin-button">Log In</button>
        </form>

        <a href="../index.html" class="login-store-link">
            &larr; Back to Store
        </a>
    </div>

</div>

</body>
</html>
