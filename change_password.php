<?php
require_once 'auth.php';
// Any logged-in user may change their password
require_login();
require_once 'db.php';

$user = current_user();
$error = '';
$flash = get_flash_message();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$currentPassword = $_POST['current_password'] ?? '';
	$newPassword = $_POST['new_password'] ?? '';
	$confirmPassword = $_POST['confirm_new_password'] ?? '';

	if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
		$error = 'All fields are required.';
	} elseif (strlen($newPassword) < 8) {
		$error = 'New password must be at least 8 characters long.';
	} elseif ($newPassword !== $confirmPassword) {
		$error = 'New password and confirmation do not match.';
	} else {
		// Check the current password hash before allowing updates
		$stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
		if ($stmt) {
			$stmt->bind_param('i', $user['id']);
			$stmt->execute();
			$stmt->bind_result($storedHash);
			if ($stmt->fetch() && password_verify($currentPassword, $storedHash)) {
				$stmt->close();
				$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
				$update = $conn->prepare('UPDATE users SET password = ?, updated_on = NOW(), updated_by = ? WHERE id = ?');
				if ($update) {
					$update->bind_param('sii', $newHash, $user['id'], $user['id']);
					if ($update->execute()) {
						$update->close();
						set_flash_message('Password updated successfully.', 'success');
						header('Location: home.php');
						exit;
					}
					$update->close();
					$error = 'Unable to update password right now.';
				} else {
					$error = 'Failed to prepare the update statement.';
				}
			} else {
				$stmt->close();
				$error = 'Current password is incorrect.';
			}
		} else {
			$error = 'Unable to load your account details.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Change Password</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 40px; color: #222; background: #f7f8fc; }
		.container { max-width: 600px; margin: 0 auto; }
		h2 { display: flex; justify-content: space-between; align-items: center; }
		.nav { margin-bottom: 12px; }
		a.btnhome { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #5c5d61; }
		.form { border: 1px solid #ddd; border-radius: 12px; padding: 28px; background: #fff; box-shadow: 0 18px 36px rgba(0, 0, 0, 0.05); }
		form { display: flex; flex-direction: column; gap: 18px; }
		label { font-weight: 600; display: block; margin-bottom: 6px; }
		input { width: 100%; padding: 10px; border: 1px solid #bbb; border-radius: 6px; }
		button { border: none; border-radius: 6px; padding: 10px 18px; font-size: 15px; cursor: pointer; background: #198754; color: #fff; }
		.alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; }
		.alert.error { background: #ffe3e3; color: #7a1c1c; border: 1px solid #f0b4b4; }
		.alert.success { background: #e2f5e9; color: #1b6b2c; border: 1px solid #b7e2c4; }
	</style>
</head>
<body>
	<div class="container">
		<div class="nav">
			<a class="btnhome" href="home.php">Back to Homepage</a>
		</div>
		<h2>Change Password</h2>
		<?php if ($flash): ?>
			<div class="alert <?php echo htmlspecialchars($flash['type'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($flash['message'], ENT_QUOTES); ?></div>
		<?php endif; ?>
		<?php if ($error !== ''): ?>
			<div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
		<?php endif; ?>
		<div class="form">
			<form method="post" action="change_password.php" autocomplete="off">
				<div>
					<label for="current_password">Current Password</label>
					<input type="password" id="current_password" name="current_password" required>
				</div>
				<div>
					<label for="new_password">New Password</label>
					<input type="password" id="new_password" name="new_password" minlength="8" required>
				</div>
				<div>
					<label for="confirm_new_password">Confirm New Password</label>
					<input type="password" id="confirm_new_password" name="confirm_new_password" minlength="8" required>
				</div>
				<button type="submit">Update Password</button>
			</form>
		</div>
	</div>
</body>
</html>
