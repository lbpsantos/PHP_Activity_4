<?php
require_once __DIR__ . '/../db.php';

// Handles CRUD logic for entries in the subject table.
class Subject
{
    private $conn;

    public function __construct($connection = null)
    {
        global $conn;
        $this->conn = $connection ?? $conn;
    }

    // Inserts a new subject and returns ['success' => bool, 'error' => string].
    public function create($codeValue, $titleValue, $unitValue) {
        $codeValue = trim((string) $codeValue);
        $titleValue = trim((string) $titleValue);
        $unitValue = trim((string) $unitValue);
        $unit = is_numeric($unitValue) ? (int) $unitValue : null;

        if ($codeValue === '' || $titleValue === '' || $unit === null || $unit <= 0) {
            return [
                'success' => false,
                'error' => 'All fields are required and unit must be a positive number.'
            ];
        }

        // Ensure subject code stays unique.
        $checkStmt = $this->conn->prepare('SELECT COUNT(*) FROM subject WHERE code = ?');
        if (!$checkStmt) {
            return [
                'success' => false,
                'error' => 'Failed to prepare duplicate check statement.'
            ];
        }

        $checkStmt->bind_param('s', $codeValue);
        $checkStmt->execute();
        $checkStmt->bind_result($existingCount);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($existingCount > 0) {
            return [
                'success' => false,
                'error' => 'Subject code already exists. Please use a different code.'
            ];
        }

        $stmt = $this->conn->prepare('INSERT INTO subject (code, title, unit) VALUES (?, ?, ?)');
        if (!$stmt) {
            return [
                'success' => false,
                'error' => 'Failed to prepare database statement.'
            ];
        }

        $stmt->bind_param('ssi', $codeValue, $titleValue, $unit);
        $created = $stmt->execute();
        $stmt->close();

        if (!$created) {
            return [
                'success' => false,
                'error' => 'Unable to save subject. Please try again.'
            ];
        }

        return ['success' => true, 'error' => ''];
    }

    // Reads subject records or a single subject when id is provided.
    public function read(array $options = []) {
        $response = [
            'success' => false,
            'error' => '',
            'subject' => null,
            'subjects' => []
        ];

        $subjectId = isset($options['id']) ? (int) $options['id'] : null;

        if ($subjectId !== null) {
            if ($subjectId <= 0) {
                $response['error'] = 'Invalid subject selected.';
                return $response;
            }

            $stmt = $this->conn->prepare('SELECT subject_id, code, title, unit FROM subject WHERE subject_id = ?');
            if (!$stmt) {
                $response['error'] = 'Failed to load subject.';
                return $response;
            }

            $stmt->bind_param('i', $subjectId);
            if (!$stmt->execute()) {
                $stmt->close();
                $response['error'] = 'Failed to load subject.';
                return $response;
            }

            $stmt->bind_result($id, $code, $title, $unit);
            if ($stmt->fetch()) {
                $response['success'] = true;
                $response['subject'] = [
                    'subject_id' => $id,
                    'code' => $code,
                    'title' => $title,
                    'unit' => $unit
                ];
            } else {
                 $response['error'] = 'Subject not found.';
            }

            $stmt->close();
            return $response;
        }

        $search = trim((string) ($options['search'] ?? ''));
        $sort = (string) ($options['sort'] ?? 'title');

        $sql = 'SELECT subject_id, code, title, unit FROM subject';
        if ($search !== '') {
            $escaped = $this->conn->real_escape_string($search);
            $like = '%' . $escaped . '%';
            $sql .= " WHERE code LIKE '{$like}' OR title LIKE '{$like}' OR CAST(unit AS CHAR) LIKE '{$like}'";
        }

        $validSort = ['title' => 'title', 'code' => 'code', 'unit' => 'unit'];
        $orderColumn = $validSort[$sort] ?? 'title';
        $sql .= " ORDER BY {$orderColumn}";

        $result = $this->conn->query($sql);
        if (!$result) {
            $response['error'] = 'Failed to load subjects.';
            return $response;
        }

        while ($row = $result->fetch_assoc()) {
            $response['subjects'][] = $row;
        }

        $result->free();
        $response['success'] = true;
        return $response;
    }

    // Updates an existing subject enforcing validation and unique codes.
    public function update($subjectId, $codeValue, $titleValue, $unitValue) {
        $subjectId = (int) $subjectId;
        if ($subjectId <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid subject selected.'
            ];
        }

        $codeValue = trim((string) $codeValue);
        $titleValue = trim((string) $titleValue);
        $unitValue = trim((string) $unitValue);
        $unit = is_numeric($unitValue) ? (int) $unitValue : null;

        if ($codeValue === '' || $titleValue === '' || $unit === null || $unit <= 0) {
            return [
                'success' => false,
                'error' => 'All fields are required and unit must be a positive number.'
            ];
        }

        $existsStmt = $this->conn->prepare('SELECT COUNT(*) FROM subject WHERE subject_id = ?');
        if (!$existsStmt) {
            return [
                'success' => false,
                'error' => 'Failed to validate subject.'
            ];
        }

        $existsStmt->bind_param('i', $subjectId);
        $existsStmt->execute();
        $existsStmt->bind_result($rowCount);
        $existsStmt->fetch();
        $existsStmt->close();

        if ($rowCount === 0) {
            return [
                'success' => false,
                'error' => 'Subject not found.'
            ];
        }

        $checkStmt = $this->conn->prepare('SELECT COUNT(*) FROM subject WHERE code = ? AND subject_id <> ?');
        if (!$checkStmt) {
            return [
                'success' => false,
                'error' => 'Failed to prepare duplicate check statement.'
            ];
        }

        $checkStmt->bind_param('si', $codeValue, $subjectId);
        $checkStmt->execute();
        $checkStmt->bind_result($dupCount);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($dupCount > 0) {
            return [
                'success' => false,
                'error' => 'Subject code already exists. Please use a different code.'
            ];
        }

        $stmt = $this->conn->prepare('UPDATE subject SET code = ?, title = ?, unit = ? WHERE subject_id = ?');
        if (!$stmt) {
            return [
                'success' => false,
                'error' => 'Failed to prepare update statement.'
            ];
        }

        $stmt->bind_param('ssii', $codeValue, $titleValue, $unit, $subjectId);
        $updated = $stmt->execute();
        $stmt->close();

        if (!$updated) {
            return [
                'success' => false,
                'error' => 'Unable to update subject. Please try again.'
            ];
        }

        return ['success' => true, 'error' => ''];
    }

    public function delete($subjectId) {
        $subjectId = (int) $subjectId;
        if ($subjectId <= 0) {
            return [
                'success' => false,
                'error' => 'Invalid subject selected.'
            ];
        }

        $existsStmt = $this->conn->prepare('SELECT COUNT(*) FROM subject WHERE subject_id = ?');
        if (!$existsStmt) {
            return [
                'success' => false,
                'error' => 'Failed to validate subject.'
            ];
        }

        $existsStmt->bind_param('i', $subjectId);
        $existsStmt->execute();
        $existsStmt->bind_result($rowCount);
        $existsStmt->fetch();
        $existsStmt->close();

        if ($rowCount === 0) {
            return [
                'success' => false,
                'error' => 'Subject not found.'
            ];
        }

        $stmt = $this->conn->prepare('DELETE FROM subject WHERE subject_id = ?');
        if (!$stmt) {
            return [
                'success' => false,
                'error' => 'Failed to prepare delete statement.'
            ];
        }

        $stmt->bind_param('i', $subjectId);
        $deleted = $stmt->execute();
        $stmt->close();

        if (!$deleted) {
            return [
                'success' => false,
                'error' => 'Unable to delete subject. Please try again.'
            ];
        }

        return ['success' => true, 'error' => ''];
    }
}

?>