<?php
require_once __DIR__ . '/../db.php';

// Encapsulates CRUD operations for the users table.
class User
{
	private $conn;
	private $accountTypes = ['admin', 'staff', 'teacher', 'student'];

	public function __construct($connection = null)
	{
		global $conn;
		$this->conn = $connection ?? $conn;
	}

	public function getAccountTypes()
	{
		return $this->accountTypes;
	}

	public function create($usernameValue, $accountTypeValue, $passwordValue, $confirmPasswordValue, $adminId)
	{
		$username = trim((string) $usernameValue);
		$accountType = trim((string) $accountTypeValue);
		$password = (string) $passwordValue;
		$confirmPassword = (string) $confirmPasswordValue;
		$adminId = (int) $adminId;

		if ($adminId <= 0) {
			return ['success' => false, 'error' => 'Invalid administrator context.'];
		}

		if ($username === '') {
			return ['success' => false, 'error' => 'Username is required.'];
		}

		if (!$this->isValidAccountType($accountType)) {
			return ['success' => false, 'error' => 'Invalid account type selected.'];
		}

		if ($password === '' || strlen($password) < 8) {
			return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
		}

		if ($password !== $confirmPassword) {
			return ['success' => false, 'error' => 'Password confirmation does not match.'];
		}

		$checkStmt = $this->conn->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
		if (!$checkStmt) {
			return ['success' => false, 'error' => 'Unable to check username availability.'];
		}

		$checkStmt->bind_param('s', $username);
		$checkStmt->execute();
		$checkStmt->bind_result($existingCount);
		$checkStmt->fetch();
		$checkStmt->close();

		if ($existingCount > 0) {
			return ['success' => false, 'error' => 'Username already exists.'];
		}

		$hash = password_hash($password, PASSWORD_DEFAULT);
		$stmt = $this->conn->prepare('INSERT INTO users (username, password, account_type, created_on, created_by, updated_on, updated_by) VALUES (?, ?, ?, NOW(), ?, NULL, NULL)');
		if (!$stmt) {
			return ['success' => false, 'error' => 'Failed to prepare the insert statement.'];
		}

		$stmt->bind_param('sssi', $username, $hash, $accountType, $adminId);
		$created = $stmt->execute();
		$stmt->close();

		if (!$created) {
			return ['success' => false, 'error' => 'Unable to save the user right now.'];
		}

		return ['success' => true, 'error' => ''];
	}

	public function read(array $options = [])
	{
		$response = [
			'success' => false,
			'error' => '',
			'user' => null,
			'users' => []
		];

		if (isset($options['id'])) {
			$userId = (int) $options['id'];
			if ($userId <= 0) {
				$response['error'] = 'Invalid user selected.';
				return $response;
			}

			$stmt = $this->conn->prepare('SELECT id, username, account_type, created_on, updated_on FROM users WHERE id = ?');
			if (!$stmt) {
				$response['error'] = 'Unable to load the requested user.';
				return $response;
			}

			$stmt->bind_param('i', $userId);
			if (!$stmt->execute()) {
				$stmt->close();
				$response['error'] = 'Unable to load the requested user.';
				return $response;
			}

			$stmt->bind_result($id, $username, $accountType, $createdOn, $updatedOn);
			if ($stmt->fetch()) {
				$response['success'] = true;
				$response['user'] = [
					'id' => $id,
					'username' => $username,
					'account_type' => $accountType,
					'created_on' => $createdOn,
					'updated_on' => $updatedOn
				];
			} else {
				$response['error'] = 'User not found.';
			}

			$stmt->close();
			return $response;
		}

		$search = trim((string) ($options['search'] ?? ''));
		$sort = (string) ($options['sort'] ?? 'username');

		$sortColumns = [
			'username' => 'username ASC',
			'account_type' => 'account_type ASC',
			'created' => 'created_on DESC',
			'updated' => 'updated_on DESC'
		];
		$orderSql = $sortColumns[$sort] ?? $sortColumns['username'];

		$sql = 'SELECT id, username, account_type, created_on, updated_on FROM users';
		$hasSearch = $search !== '';
		if ($hasSearch) {
			$sql .= ' WHERE username LIKE ? OR account_type LIKE ?';
		}
		$sql .= ' ORDER BY ' . $orderSql;

		$stmt = $this->conn->prepare($sql);
		if (!$stmt) {
			$response['error'] = 'Unable to load users.';
			return $response;
		}

		if ($hasSearch) {
			$like = '%' . $search . '%';
			$stmt->bind_param('ss', $like, $like);
		}

		if (!$stmt->execute()) {
			$stmt->close();
			$response['error'] = 'Unable to load users.';
			return $response;
		}

		$result = $stmt->get_result();
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$response['users'][] = $row;
			}
			$result->free();
		}

		$stmt->close();
		$response['success'] = true;
		return $response;
	}

	public function update($userIdValue, $usernameValue, $accountTypeValue, $adminId)
	{
		$userId = (int) $userIdValue;
		$username = trim((string) $usernameValue);
		$accountType = trim((string) $accountTypeValue);
		$adminId = (int) $adminId;

		if ($userId <= 0) {
			return ['success' => false, 'error' => 'Invalid user selected.'];
		}

		if ($adminId <= 0) {
			return ['success' => false, 'error' => 'Invalid administrator context.'];
		}

		if ($username === '') {
			return ['success' => false, 'error' => 'Username is required.'];
		}

		if (!$this->isValidAccountType($accountType)) {
			return ['success' => false, 'error' => 'Invalid account type selected.'];
		}

		$existsStmt = $this->conn->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
		if (!$existsStmt) {
			return ['success' => false, 'error' => 'Failed to validate user.'];
		}

		$existsStmt->bind_param('i', $userId);
		$existsStmt->execute();
		$existsStmt->bind_result($rowCount);
		$existsStmt->fetch();
		$existsStmt->close();

		if ($rowCount === 0) {
			return ['success' => false, 'error' => 'User not found.'];
		}

		$checkStmt = $this->conn->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
		if (!$checkStmt) {
			return ['success' => false, 'error' => 'Unable to check username availability.'];
		}

		$checkStmt->bind_param('si', $username, $userId);
		$checkStmt->execute();
		$checkStmt->bind_result($dupCount);
		$checkStmt->fetch();
		$checkStmt->close();

		if ($dupCount > 0) {
			return ['success' => false, 'error' => 'Username already exists.'];
		}

		$stmt = $this->conn->prepare('UPDATE users SET username = ?, account_type = ?, updated_on = NOW(), updated_by = ? WHERE id = ?');
		if (!$stmt) {
			return ['success' => false, 'error' => 'Failed to prepare the update statement.'];
		}

		$stmt->bind_param('ssii', $username, $accountType, $adminId, $userId);
		$updated = $stmt->execute();
		$stmt->close();

		if (!$updated) {
			return ['success' => false, 'error' => 'Unable to update the user right now.'];
		}

		return ['success' => true, 'error' => ''];
	}

	public function delete($userIdValue)
	{
		$userId = (int) $userIdValue;
		if ($userId <= 0) {
			return ['success' => false, 'error' => 'Invalid user selected.'];
		}

		$existsStmt = $this->conn->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
		if (!$existsStmt) {
			return ['success' => false, 'error' => 'Failed to validate user.'];
		}

		$existsStmt->bind_param('i', $userId);
		$existsStmt->execute();
		$existsStmt->bind_result($rowCount);
		$existsStmt->fetch();
		$existsStmt->close();

		if ($rowCount === 0) {
			return ['success' => false, 'error' => 'User not found.'];
		}

		$stmt = $this->conn->prepare('DELETE FROM users WHERE id = ?');
		if (!$stmt) {
			return ['success' => false, 'error' => 'Failed to prepare the delete statement.'];
		}

		$stmt->bind_param('i', $userId);
		$deleted = $stmt->execute();
		$stmt->close();

		if (!$deleted) {
			return ['success' => false, 'error' => 'Unable to delete the user right now.'];
		}

		return ['success' => true, 'error' => ''];
	}

	private function isValidAccountType($accountType)
	{
		return in_array($accountType, $this->accountTypes, true);
	}
}

?>
