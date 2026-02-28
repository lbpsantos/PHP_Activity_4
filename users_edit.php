<?php
require_once 'auth.php';
// Restrict editing to administrators only
require_admin();
require_once 'db.php';

$currentUser = current_user();
$flash = get_flash_message();
$error = '';
$accountTypes = ['admin', 'staff', 'teacher', 'student'];
$userId = $_GET['id'] ?? $_POST['id'] ?? '';

// Reject malformed or missing identifiers early
if ($userId === '' || !ctype_digit((string) $userId)) {
	set_flash_message('Invalid user specified.', 'error');
	header('Location: users_list.php');
	exit;
}

$userId = (int) $userId;
$usernameValue = '';
$accountTypeValue = 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$usernameValue = trim($_POST['username'] ?? '');
	$accountTypeValue = $_POST['account_type'] ?? '';

	if ($usernameValue === '' || $accountTypeValue === '') {
		$error = 'Username and account type are required.';
	} elseif (!in_array($accountTypeValue, $accountTypes, true)) {
		$error = 'Invalid account type selected.';
	} else {
		// Ensure we're not colliding with another username
		$check = $conn->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
		if ($check) {
			$check->bind_param('si', $usernameValue, $userId);
			$check->execute();
			$check->bind_result($taken);
			$check->fetch();
			$check->close();

			if ($taken > 0) {
				$error = 'Username already exists.';
			} else {
				$adminId = (int) ($currentUser['id'] ?? 0);
				// Persist metadata so we know who updated the account
				$stmt = $conn->prepare('UPDATE users SET username = ?, account_type = ?, updated_on = NOW(), updated_by = ? WHERE id = ?');
				if ($stmt) {
					$stmt->bind_param('ssii', $usernameValue, $accountTypeValue, $adminId, $userId);
					if ($stmt->execute()) {
						$stmt->close();
						set_flash_message('User updated successfully.', 'success');
						header('Location: users_list.php');
						exit;
					}
					$stmt->close();
					$error = 'Unable to update the user right now.';
				} else {
					$error = 'Failed to prepare the update statement.';
				}
			}
		} else {
			$error = 'Unable to check username availability.';
		}
	}
} else {
	$stmt = $conn->prepare('SELECT username, account_type FROM users WHERE id = ?');
	if ($stmt) {
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$stmt->bind_result($usernameValue, $accountTypeValue);
		if (!$stmt->fetch()) {
			$stmt->close();
			set_flash_message('User not found.', 'error');
			header('Location: users_list.php');
			exit;
		}
		$stmt->close();
	} else {
		set_flash_message('Unable to load the requested user.', 'error');
		header('Location: users_list.php');
		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit User</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 40px; color: #222; background: #f7f8fc; }
		.container { max-width: 800px; margin: 0 auto; }
		h2 { display: flex; justify-content: space-between; align-items: center; }
		.nav { margin-bottom: 12px; }
		a.btnhome { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #5c5d61; }
		.form { border: 1px solid #ddd; border-radius: 12px; padding: 28px; background: #fff; box-shadow: 0 18px 36px rgba(0, 0, 0, 0.05); }
		form { display: flex; flex-direction: column; gap: 18px; }
		.field { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
		.field label { width: 220px; font-weight: 600; }
		.field input, .field select { flex: 1; min-width: 240px; padding: 10px; border: 1px solid #bbb; border-radius: 6px; }
		.actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; }
		button { border: none; border-radius: 6px; padding: 10px 18px; font-size: 15px; cursor: pointer; }
		.btn-cancel { background: #d9534f; color: #fff; }
		.btn-save { background: #198754; color: #fff; }
		.alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; }
		.alert.error { background: #ffe3e3; color: #7a1c1c; border: 1px solid #f0b4b4; }
		.alert.success { background: #e2f5e9; color: #1b6b2c; border: 1px solid #b7e2c4; }
	</style>
</head>
<body>
	<div class="container">
		<div class="nav">
			<a class="btnhome" href="users_list.php">Back to Users</a>
		</div>
		<h2>Edit User</h2>
		<?php // Surface any prior operation notice
		if ($flash): ?>
			<div class="alert <?php echo htmlspecialchars($flash['type'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($flash['message'], ENT_QUOTES); ?></div>
		<?php endif; ?>
		<?php // Validation feedback stays close to the form for clarity
		if ($error !== ''): ?>
			<div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
		<?php endif; ?>
		<div class="form">
			<form method="post" action="users_edit.php" autocomplete="off">
				<input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $userId, ENT_QUOTES); ?>">
				<div class="field">
					<label for="username">Username</label>
					<input type="text" id="username" name="username" value="<?php echo htmlspecialchars($usernameValue, ENT_QUOTES); ?>" required>
				</div>
				<div class="field">
					<label for="account_type">Account Type</label>
					<select id="account_type" name="account_type" required>
						<?php foreach ($accountTypes as $type): ?>
							<option value="<?php echo htmlspecialchars($type, ENT_QUOTES); ?>" <?php echo $accountTypeValue === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($type), ENT_QUOTES); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="actions">
					<button type="button" class="btn-cancel" onclick="window.location.href='users_list.php'">Cancel</button>
					<button type="submit" class="btn-save">Update User</button>
				</div>
			</form>
		</div>
	</div>
</body>
</html>
