<?php
require_once 'auth.php';
require_staff_or_admin();
require_once 'db.php';

$error = '';
//GET the subject_id from the URL
$subjectId = $_GET['subject_id'] ?? $_POST['subject_id'] ?? '';
$codeValue = '';
$titleValue = '';
$unitValue = '';

// Validate incoming id
if ($subjectId === '' || !ctype_digit((string)$subjectId)) {
	$error = 'Invalid subject selected.';
} else {
	$subjectId = (int)$subjectId;
}

// Handle update submission
if ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$codeValue = trim($_POST['subjectCode'] ?? '');
	$titleValue = trim($_POST['subjectTitle'] ?? '');
	$unitValue = trim((string)($_POST['subjectUnit'] ?? ''));
	$unit = is_numeric($unitValue) ? (int)$unitValue : null;

	if ($codeValue === '' || $titleValue === '' || $unit === null) {
		$error = 'All fields are required and unit must be a number.';
	} else {
		// Ensure code is unique except for this record
		$checkStmt = $conn->prepare('SELECT COUNT(*) FROM subject WHERE code = ? AND subject_id <> ?');
		if ($checkStmt) {
			$checkStmt->bind_param('si', $codeValue, $subjectId);
			$checkStmt->execute();
			$checkStmt->bind_result($dupCount);
			$checkStmt->fetch();
			$checkStmt->close();

			if ($dupCount > 0) {
				$error = 'Subject code already exists. Please use a different code.';
			} else {
				// Update subject entry
				$stmt = $conn->prepare('UPDATE subject SET code = ?, title = ?, unit = ? WHERE subject_id = ?');
				if ($stmt) {
					$stmt->bind_param('ssii', $codeValue, $titleValue, $unit, $subjectId);
					if ($stmt->execute()) {
						header('Location: subject_list.php');
						exit;
					}
					$error = 'Unable to update subject. Please try again.';
					$stmt->close();
				} else {
					$error = 'Failed to prepare update statement.';
				}
			}
		} else {
			$error = 'Failed to prepare duplicate check statement.';
		}
	}
}

// Load current values for initial display
if ($error === '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
	$loadStmt = $conn->prepare('SELECT code, title, unit FROM subject WHERE subject_id = ?');
	if ($loadStmt) {
		$loadStmt->bind_param('i', $subjectId);
		$loadStmt->execute();
		$loadStmt->bind_result($codeValue, $titleValue, $unitValue);
		if (!$loadStmt->fetch()) {
			$error = 'Subject not found.';
		}
		$loadStmt->close();
	} else {
		$error = 'Failed to load subject.';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Subject</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 40px; color: #222; }
		.container { max-width: 960px; margin: 0 auto; }
		h2 { display: flex; justify-content: space-between; align-items: center; }
		.nav { margin-bottom: 12px; }
		a.btnhome { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #5c5d61; }
		.form { border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
		.alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; }
		.alert.error { background: #ffe3e3; color: #7a1c1c; border: 1px solid #f0b4b4; }
		form { display: flex; flex-direction: column; gap: 16px; }
		.field { display: flex; align-items: center; gap: 16px; }
		.field label { width: 220px; font-weight: 600; }
		.field input { flex: 1; padding: 8px; border: 1px solid #bbb; border-radius: 4px; }
		.actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; }
		button { border: none; border-radius: 6px; padding: 8px 14px; font-size: 14px; cursor: pointer; }
		.btn-cancel { background: #d9534f; color: #fff; }
		.btn-save { background: #198754; color: #fff; }
	</style>
</head>
<body>
	<div class="container">
		<div class="nav">
			<a class="btnhome" href="subject_list.php">Back to Subjects</a>
		</div>
		<h2>EDIT SUBJECT</h2>
		<div class="form">
			<?php if ($error !== '') { ?>
				<div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
			<?php } ?>
			<?php if ($error === '' || $_SERVER['REQUEST_METHOD'] === 'POST') { ?>
				<form action="subject_edit.php" method="post">
					<input type="hidden" name="subject_id" value="<?php echo htmlspecialchars((string)$subjectId, ENT_QUOTES); ?>">
					<div class="field">
						<label for="subjectCode">Subject code:</label>
						<input type="text" id="subjectCode" name="subjectCode" required value="<?php echo htmlspecialchars($codeValue, ENT_QUOTES); ?>">
					</div>
					<div class="field">
						<label for="subjectTitle">Subject title:</label>
						<input type="text" id="subjectTitle" name="subjectTitle" required value="<?php echo htmlspecialchars($titleValue, ENT_QUOTES); ?>">
					</div>
					<div class="field">
						<label for="subjectUnit">Unit:</label>
						<input type="number" id="subjectUnit" name="subjectUnit" min="1" required value="<?php echo htmlspecialchars($unitValue, ENT_QUOTES); ?>">
					</div>
					<div class="actions">
						<button type="button" class="btn-cancel" onclick="window.location.href='subject_list.php'">Cancel</button>
						<button type="submit" class="btn-save">Update</button>
					</div>
				</form>
			<?php } ?>
		</div>
	</div>
</body>
</html>
