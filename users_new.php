<?php
require_once 'auth.php';
// Guard the page so only admins can add new accounts
require_admin();
require_once __DIR__ . '/Model/UserModel.php';

$currentUser = current_user();
$flash = get_flash_message();
$error = '';
$usernameValue = '';
$userModel = new User();
$accountTypes = $userModel->getAccountTypes();
$accountTypeValue = 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$usernameValue = trim($_POST['username'] ?? '');
	$accountTypeValue = $_POST['account_type'] ?? 'staff';
	$password = $_POST['password'] ?? '';
	$confirmPassword = $_POST['confirm_password'] ?? '';
	$adminId = (int) ($currentUser['id'] ?? 0);

	$result = $userModel->create($usernameValue, $accountTypeValue, $password, $confirmPassword, $adminId);
	if (!empty($result['success'])) {
		set_flash_message('User created successfully.', 'success');
		header('Location: users_list.php');
		exit;
	}

	$error = $result['error'] ?? 'Unable to save the user right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Add User</title>
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
		<h2>User Form</h2>
		<?php // Echo back any prior action message (e.g., from redirects)
		if ($flash): ?>
			<div class="alert <?php echo htmlspecialchars($flash['type'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($flash['message'], ENT_QUOTES); ?></div>
		<?php endif; ?>
		<?php // Inline validation errors stay near the form for quick fixes
		if ($error !== ''): ?>
			<div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
		<?php endif; ?>
		<div class="form">
			<form method="post" action="users_new.php" autocomplete="off">
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
				<div class="field">
					<label for="password">Password</label>
					<input type="password" id="password" name="password" minlength="8" required>
				</div>
				<div class="field">
					<label for="confirm_password">Confirm Password</label>
					<input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
				</div>
				<div class="actions">
					<button type="button" class="btn-cancel" onclick="window.location.href='users_list.php'">Cancel</button>
					<button type="submit" class="btn-save">Save User</button>
				</div>
			</form>
		</div>
	</div>
</body>
</html>
