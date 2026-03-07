<?php
    require_once 'auth.php';
    require_staff_or_admin();
    require_once __DIR__ . '/Model/SubjectModel.php';

    $error = '';
    $codeValue = '';
    $titleValue = '';
    $unitValue = '';

    $subjectModel = new Subject();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $codeValue = trim($_POST['subjectCode'] ?? '');
        $titleValue = trim($_POST['subjectTitle'] ?? '');
        $unitValue = trim((string)($_POST['subjectUnit'] ?? ''));
        $result = $subjectModel->create($codeValue, $titleValue, $unitValue);

        if (!empty($result['success'])) {
            set_flash_message('Subject created successfully.', 'success');
            header('Location: subject_list.php');
            exit;
        }

        $error = $result['error'] ?? 'Unable to save subject. Please try again.';
    }
?>
<!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject New</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #222; }
        .container { max-width: 960px; margin: 0 auto; }
        h2 { display: flex; justify-content: space-between; align-items: center; }
        .nav { margin-bottom: 12px; }
        a.link { color: #0d6efd; text-decoration: none; }
        a.btnhome { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #5c5d61;  }
        .toolbar{display: flex; align-items: center; justify-content: space-between}
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
    <div class = "container">
        <div class ="nav">
            <a class = "btnhome" href="home.php">Back to Homepage</a>
        </div>
        <h2>FORM</h2> 
        <div class = "form">
            <?php if ($error !== ''): ?>
                <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
            <?php endif; ?>
            <form action="subject_new.php" method = "post">
                <div class="field">
                    <label for="subjectCode">Please enter the code:</label>
                    <input type="text" id="subjectCode" name="subjectCode" required value="<?php echo htmlspecialchars($codeValue, ENT_QUOTES); ?>">
                </div>
                <div class="field">
                    <label for="subjectTitle">Please enter the title:</label>
                    <input type="text" id="subjectTitle" name="subjectTitle" required value="<?php echo htmlspecialchars($titleValue, ENT_QUOTES); ?>">
                </div>
                <div class="field">
                    <label for="subjectUnit">Please enter the unit:</label>
                    <input type="number" id="subjectUnit" name="subjectUnit" min="1" required value="<?php echo htmlspecialchars($unitValue, ENT_QUOTES); ?>">
                </div>
                <div class="actions">
                    <button type="button" class="btn-cancel" onclick="window.location.href='subject_list.php'">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>

            
        </div>
    </div>
 </body>
 </html>