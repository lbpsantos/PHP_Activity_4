<?php
//loads the data once to avoid duplicate data load
require_once 'db.php';
require_once 'auth.php';

$_SESSION['logged_in'] = $_SESSION['logged_in'] ?? false;
if (is_logged_in() || $_SESSION['logged_in']) {
    // Prevent logged-in users from hitting the login form again
    header('Location: home.php');
    exit;
}

$error = '';
$usernameValue = trim($_POST['username'] ?? '');
$flash = get_flash_message();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($usernameValue === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        // Look up the account record for the submitted username
        $stmt = $conn->prepare('SELECT id, username, password, account_type FROM users WHERE username = ?');
        if ($stmt) {
            $stmt->bind_param('s', $usernameValue);
            $stmt->execute();
            $stmt->bind_result($userId, $dbUsername, $hash, $accountType);
            if ($stmt->fetch() && password_verify($password, $hash)) {
                // Successful login: refresh session and persist essentials
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $dbUsername;
                $_SESSION['account_type'] = $accountType;
                $_SESSION['logged_in'] = true;
                set_flash_message('Welcome back!', 'success');
                $stmt->close();
                header('Location: home.php');
                exit;
            }
            $stmt->close();
            $error = 'Invalid username or password.';
        } else {
            $error = 'Unable to process login right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #222; background: #f0f2ff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { width: 100%; max-width: 420px; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 24px 58px rgba(36, 54, 156, 0.18); }
        h1 { margin-bottom: 8px; text-align: center; font-size: 28px; }
        p.description { text-align: center; margin-bottom: 24px; color: #555; }
        form { display: flex; flex-direction: column; gap: 18px; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-weight: 600; color: #333; }
        .field input { padding: 11px; border: 1px solid #c6c9da; border-radius: 8px; font-size: 15px; transition: border-color 0.2s, box-shadow 0.2s; }
        .field input:focus { border-color: #3651ff; box-shadow: 0 0 0 3px rgba(54, 81, 255, 0.15); outline: none; }
        button { border: none; border-radius: 999px; padding: 12px; font-size: 16px; cursor: pointer; color: #fff; background: linear-gradient(135deg, #1b5cff, #4d83ff); font-weight: 600; letter-spacing: 0.3px; transition: transform 0.1s ease-in-out; }
        button:hover { transform: translateY(-1px); }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert.error { background: #ffe3e3; color: #7a1c1c; border: 1px solid #f0b4b4; }
        .alert.success { background: #e2f5e9; color: #1b6b2c; border: 1px solid #b7e2c4; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sign In</h1>
        <p class="description">Enter your credentials to access the module.</p>
        <?php if ($flash): ?>
            <div class="alert <?php echo htmlspecialchars($flash['type'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($flash['message'], ENT_QUOTES); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
        <?php endif; ?>
        <form method="post" action="login.php" autocomplete="off">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($usernameValue, ENT_QUOTES); ?>" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
