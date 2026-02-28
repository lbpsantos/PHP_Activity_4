<?php
require_once 'auth.php';
require_staff_or_admin();
require_once 'db.php';

$error = '';
$programId = $_GET['program_id'] ?? $_POST['program_id'] ?? '';
$codeValue = '';
$titleValue = '';
$yearsValue = '';

// Validate incoming id
if ($programId === '' || !ctype_digit((string)$programId)) {
    $error = 'Invalid program selected.';
} else {
    $programId = (int)$programId;
}

// Handle update submission
if ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeValue = trim($_POST['programCode'] ?? '');
    $titleValue = trim($_POST['programTitle'] ?? '');
    $yearsValue = trim((string)($_POST['programYears'] ?? ''));
    $years = is_numeric($yearsValue) ? (int)$yearsValue : null;

    if ($codeValue === '' || $titleValue === '' || $years === null || $years <= 0) {
        $error = 'All fields are required and years must be a positive number.';
    } else {
        // Ensure code is unique except for this record
        $checkStmt = $conn->prepare('SELECT COUNT(*) FROM program WHERE code = ? AND program_id <> ?');
        if ($checkStmt) {
            $checkStmt->bind_param('si', $codeValue, $programId);
            $checkStmt->execute();
            $checkStmt->bind_result($dupCount);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($dupCount > 0) {
                $error = 'Program code already exists. Please use a different code.';
            } else {
                // Update program entry
                $stmt = $conn->prepare('UPDATE program SET code = ?, title = ?, years = ? WHERE program_id = ?');
                if ($stmt) {
                    $stmt->bind_param('ssii', $codeValue, $titleValue, $years, $programId);
                    if ($stmt->execute()) {
                        header('Location: program_list.php');
                        exit;
                    }
                    $error = 'Unable to update program. Please try again.';
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
    $loadStmt = $conn->prepare('SELECT code, title, years FROM program WHERE program_id = ?');
    if ($loadStmt) {
        $loadStmt->bind_param('i', $programId);
        $loadStmt->execute();
        $loadStmt->bind_result($codeValue, $titleValue, $yearsValue);
        if (!$loadStmt->fetch()) {
            $error = 'Program not found.';
        }
        $loadStmt->close();
    } else {
        $error = 'Failed to load program.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Program</title>
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
        .disabled { opacity: 0.7; pointer-events: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a class="btnhome" href="program_list.php">Back to Programs</a>
        </div>
        <h2>EDIT PROGRAM</h2>
        <div class="form">
            <?php if ($error !== '') { ?>
                <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
            <?php } ?>
            <?php if ($error === '' || $_SERVER['REQUEST_METHOD'] === 'POST') { ?>
                <form action="program_edit.php" method="post">
                    <input type="hidden" name="program_id" value="<?php echo htmlspecialchars((string)$programId, ENT_QUOTES); ?>">
                    <div class="field">
                        <label for="programCode">Program code:</label>
                        <input type="text" id="programCode" name="programCode" required value="<?php echo htmlspecialchars($codeValue, ENT_QUOTES); ?>">
                    </div>
                    <div class="field">
                        <label for="programTitle">Program title:</label>
                        <input type="text" id="programTitle" name="programTitle" required value="<?php echo htmlspecialchars($titleValue, ENT_QUOTES); ?>">
                    </div>
                    <div class="field">
                        <label for="programYears">Number of years:</label>
                        <input type="number" id="programYears" name="programYears" min="1" required value="<?php echo htmlspecialchars($yearsValue, ENT_QUOTES); ?>">
                    </div>
                    <div class="actions">
                        <button type="button" class="btn-cancel" onclick="window.location.href='program_list.php'">Cancel</button>
                        <button type="submit" class="btn-save">Update</button>
                    </div>
                </form>
                <?php } ?>
        </div>
    </div>
</body>
</html>