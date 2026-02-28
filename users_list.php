<?php
require_once 'auth.php';
require_login();
// Hard gate: only admins should see the user directory
if (!is_admin()) {
    set_flash_message('Access denied', 'error');
    header('Location: home.php');
    exit;
}
require_once 'db.php';

$flash = get_flash_message();
$users = [];
$loadError = '';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'username';

$sortColumns = [
    'username' => 'username ASC',
    'account_type' => 'account_type ASC',
    'created' => 'created_on DESC',
    'updated' => 'updated_on DESC',
];
$orderSql = $sortColumns[$sort] ?? $sortColumns['username'];

// Pull a simple ordered list of accounts for display
$baseQuery = 'SELECT id, username, account_type, created_on, updated_on FROM users';
$hasSearch = $search !== '';
$sql = $baseQuery;
if ($hasSearch) {
    $sql .= ' WHERE username LIKE ? OR account_type LIKE ?';
}
$sql .= ' ORDER BY ' . $orderSql;

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($hasSearch) {
        $like = '%' . $search . '%';
        $stmt->bind_param('ss', $like, $like);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } else {
        $loadError = 'Unable to load users.';
    }
    $stmt->close();
} else {
    $loadError = 'Unable to load users.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #222; }
        .container { max-width: 960px; margin: 0 auto; }
        h2 { display: flex; justify-content: space-between; align-items: center; margin: 0; }
        a.btn { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #0d6efd; }
        a.btnhome { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #5c5d61; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f6f6f6; }
        .nav { margin-bottom: 12px; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .search-form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .search-form input, .search-form select { padding: 6px 10px; border: 1px solid #bbb; border-radius: 4px; }
        .search-form button { border: none; padding: 6px 12px; border-radius: 4px; background: #198754; color: #fff; cursor: pointer; }
        .alert { padding: 10px 14px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; }
        .alert.success { background: #e2f5e9; color: #1b6b2c; border: 1px solid #b7e2c4; }
        .alert.error { background: #ffe3e3; color: #7a1c1c; border: 1px solid #f0b4b4; }
        .alert.info { background: #e0efff; color: #0f4b8f; border: 1px solid #b7d5ff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a class="btnhome" href="home.php">Back to Homepage</a>
        </div>
        <div class="toolbar">
            <h2>USERS</h2>
            <form class="search-form" method="get" action="users_list.php">
                <input type="text" name="q" placeholder="Search username or role" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
                <select name="sort">
                    <?php
                        $labels = [
                            'username' => 'Username (A-Z)',
                            'account_type' => 'Account Type',
                            'created' => 'Newest Created',
                            'updated' => 'Recently Updated'
                        ];
                        foreach ($labels as $value => $label) {
                            $selected = ($value === ($sort ?? 'username')) ? 'selected' : '';
                            echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                        }
                    ?>
                </select>
                <button type="submit">Search</button>
            </form>
            <a class="btn" href="users_new.php">Add User</a>
        </div>
        
        <?php // Surface any queued flash message from redirects or actions
        if ($flash): ?>
            <div class="alert <?php echo htmlspecialchars($flash['type'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($flash['message'], ENT_QUOTES); ?></div>
        <?php endif; ?>
        <?php // If the query failed, expose a friendly error instead of an empty table
        if ($loadError !== ''): ?>
            <div class="alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES); ?></div>
        <?php endif; ?>
        <table>
            <tr>
                <th>Username</th>
                <th>Account Type</th>
                <th>Created On</th>
                <th>Updated On</th>
                <th>Action</th>
            </tr>
            <?php if (count($users) === 0): ?>
                <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['account_type']), ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($user['created_on'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($user['updated_on'], ENT_QUOTES); ?></td>
                        <td><a href="users_edit.php?id=<?php echo htmlspecialchars($user['id'], ENT_QUOTES); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>