<?php
require_once 'auth.php';
require_login();
session_regenerate_id(true); // Rotate session id to reduce fixation risk

// Instruct browsers not to cache the dashboard so back navigation after logout fails
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$user = current_user();
$flash = get_flash_message();
$accountTypeLabels = [
    'admin' => 'Admin',
    'staff' => 'Staff',
    'teacher' => 'Teacher',
    'student' => 'Student',
];
$accountLabel = $accountTypeLabels[$user['account_type'] ?? ''] ?? 'User';
$username = $user['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title>School Encoding Module</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #222; text-align: center; }
        .container { max-width: 720px; margin: 0 auto; }
        h1 { margin-bottom: 16px; font-size: 40px}
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
        .links { display: flex; gap: 12px; margin-top: 12px; justify-content: center; }
        a.btn { display: inline-block; padding: 10px 14px; border-radius: 6px; text-decoration: none; color: #fff; background: #8c4faf; }
        a.btn.secondary { background: #2e9b9b; }
        a.btn.third {background: #1aa9e2 }
        a.btn.warning {background: #f0ad4e; }
        a.btn.logout{background: red}
        p { font-size: 30px; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; text-align: left; }
        .alert.error { background: #ffe3e3; color: #7a1c1c; border: 1px solid #f0b4b4; }
        .alert.success { background: #e2f5e9; color: #1b6b2c; border: 1px solid #b7e2c4; }
        .alert.info { background: #e0efff; color: #0f4b8f; border: 1px solid #b7d5ff; }
    </style>
    </head>
<body>
    <div class="container">
        <h1>School Encoding Module</h1>
        <?php if ($flash): ?>
            <div class="alert <?php echo htmlspecialchars($flash['type'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($flash['message'], ENT_QUOTES); ?></div>
        <?php endif; ?>
        <div class="card">
            <p>Welcome, <?php echo htmlspecialchars($username, ENT_QUOTES); ?> (<?php echo htmlspecialchars($accountLabel, ENT_QUOTES); ?>)</p>
            <div class="links">
                <a class="btn" href="program_list.php">Program List</a>
                <a class="btn secondary" href="subject_list.php">Subject List</a>
                <!---If user is login -->
                <?php if (is_admin()): ?>
                    <a class="btn third" href="users_list.php">Manage Users</a>
                <?php endif; ?>
                <a class="btn warning" href="change_password.php">Change Password</a>
                <a class="btn logout" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    <script>
        // Reload if the page was restored from the back/forward cache after logout
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>