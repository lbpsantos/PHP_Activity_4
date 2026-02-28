<?php
require_once __DIR__ . '/../db.php';

// Handles CRUD logic for entries in the program table.
class Program
{
   private $conn;

   public function __construct($connection = null)
   {
      global $conn;
      $this->conn = $connection ?? $conn;
   }

   // Inserts a new program and returns ['success' => bool, 'error' => string].
   public function create($codeValue, $titleValue, $yearsValue) {
      // Normalize incoming values before validating or persisting.
      $codeValue = trim((string) $codeValue);
      $titleValue = trim((string) $titleValue);
      $yearsValue = trim((string) $yearsValue);
      $years = is_numeric($yearsValue) ? (int) $yearsValue : null;

      if ($codeValue === '' || $titleValue === '' || $years === null || $years <= 0) {
         return [
            'success' => false,
            'error' => 'All fields are required and years must be a positive number.'
         ];
      }

      // Guard against duplicate program codes before inserting.
      $checkStmt = $this->conn->prepare('SELECT COUNT(*) FROM program WHERE code = ?');
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
            'error' => 'Program code already exists. Please use a different code.'
         ];
      }

      // Persist the new record once validation and uniqueness checks pass.
      $stmt = $this->conn->prepare('INSERT INTO program (code, title, years) VALUES (?, ?, ?)');
      if (!$stmt) {
         return [
            'success' => false,
            'error' => 'Failed to prepare database statement.'
         ];
      }

      $stmt->bind_param('ssi', $codeValue, $titleValue, $years);
      $created = $stmt->execute();
      $stmt->close();

      if (!$created) {
         return [
            'success' => false,
            'error' => 'Unable to save program. Please try again.'
         ];
      }

      return ['success' => true, 'error' => ''];
   }

   // Reads program records. When an id is provided a single program is returned,
   // otherwise a collection is returned with optional search/sort parameters.
   public function read(array $options = []) {
      $response = [
         'success' => false,
         'error' => '',
         'program' => null,
         'programs' => []
      ];

      $programId = isset($options['id']) ? (int) $options['id'] : null;

      if ($programId !== null) {
         // Return a single record when a specific id is requested.
         if ($programId <= 0) {
            $response['error'] = 'Invalid program selected.';
            return $response;
         }

         $stmt = $this->conn->prepare('SELECT program_id, code, title, years FROM program WHERE program_id = ?');
         if (!$stmt) {
            $response['error'] = 'Failed to load program.';
            return $response;
         }

         $stmt->bind_param('i', $programId);
         if (!$stmt->execute()) {
            $stmt->close();
            $response['error'] = 'Failed to load program.';
            return $response;
         }

         $stmt->bind_result($id, $code, $title, $years);
         if ($stmt->fetch()) {
            $response['success'] = true;
            $response['program'] = [
               'program_id' => $id,
               'code' => $code,
               'title' => $title,
               'years' => $years
            ];
         } else {
            $response['error'] = 'Program not found.';
         }

         $stmt->close();
         return $response;
      }

      $search = trim((string) ($options['search'] ?? ''));
      $sort = (string) ($options['sort'] ?? 'title');

      // Build list query with optional filtering.
      $sql = 'SELECT program_id, code, title, years FROM program';
      if ($search !== '') {
         $escaped = $this->conn->real_escape_string($search);
         $like = '%' . $escaped . '%';
         $sql .= " WHERE code LIKE '{$like}' OR title LIKE '{$like}' OR CAST(years AS CHAR) LIKE '{$like}'";
      }

      $validSort = ['title' => 'title', 'code' => 'code', 'years' => 'years'];
      $orderColumn = $validSort[$sort] ?? 'title';
      $sql .= " ORDER BY {$orderColumn}";

      $result = $this->conn->query($sql);
      if (!$result) {
         $response['error'] = 'Failed to load programs.';
         return $response;
      }

      while ($row = $result->fetch_assoc()) {
         $response['programs'][] = $row;
      }

      $result->free();
      $response['success'] = true;
      return $response;
   }

   // Updates a program entry while enforcing validation and unique codes.
   public function update($programId, $codeValue, $titleValue, $yearsValue) {
      $programId = (int) $programId;
      if ($programId <= 0) {
         return [
            'success' => false,
            'error' => 'Invalid program selected.'
         ];
      }

      $codeValue = trim((string) $codeValue);
      $titleValue = trim((string) $titleValue);
      $yearsValue = trim((string) $yearsValue);
      $years = is_numeric($yearsValue) ? (int) $yearsValue : null;

      if ($codeValue === '' || $titleValue === '' || $years === null || $years <= 0) {
         return [
            'success' => false,
            'error' => 'All fields are required and years must be a positive number.'
         ];
      }

      // Ensure the record exists before attempting an update.
      $existsStmt = $this->conn->prepare('SELECT COUNT(*) FROM program WHERE program_id = ?');
      if (!$existsStmt) {
         return [
            'success' => false,
            'error' => 'Failed to validate program.'
         ];
      }

      $existsStmt->bind_param('i', $programId);
      $existsStmt->execute();
      $existsStmt->bind_result($rowCount);
      $existsStmt->fetch();
      $existsStmt->close();

      if ($rowCount === 0) {
         return [
            'success' => false,
            'error' => 'Program not found.'
         ];
      }

      // Enforce uniqueness constraint on program code excluding current record.
      $checkStmt = $this->conn->prepare('SELECT COUNT(*) FROM program WHERE code = ? AND program_id <> ?');
      if (!$checkStmt) {
         return [
            'success' => false,
            'error' => 'Failed to prepare duplicate check statement.'
         ];
      }

      $checkStmt->bind_param('si', $codeValue, $programId);
      $checkStmt->execute();
      $checkStmt->bind_result($dupCount);
      $checkStmt->fetch();
      $checkStmt->close();

      if ($dupCount > 0) {
         return [
            'success' => false,
            'error' => 'Program code already exists. Please use a different code.'
         ];
      }

      $stmt = $this->conn->prepare('UPDATE program SET code = ?, title = ?, years = ? WHERE program_id = ?');
      if (!$stmt) {
         return [
            'success' => false,
            'error' => 'Failed to prepare update statement.'
         ];
      }

      $stmt->bind_param('ssii', $codeValue, $titleValue, $years, $programId);
      $updated = $stmt->execute();
      $stmt->close();

      if (!$updated) {
         return [
            'success' => false,
            'error' => 'Unable to update program. Please try again.'
         ];
      }

      return ['success' => true, 'error' => ''];
   }

   public function delete($programId) {
      $programId = (int) $programId;
      if ($programId <= 0) {
         return [
            'success' => false,
            'error' => 'Invalid program selected.'
         ];
      }

      $existsStmt = $this->conn->prepare('SELECT COUNT(*) FROM program WHERE program_id = ?');
      if (!$existsStmt) {
         return [
            'success' => false,
            'error' => 'Failed to validate program.'
         ];
      }

      $existsStmt->bind_param('i', $programId);
      $existsStmt->execute();
      $existsStmt->bind_result($rowCount);
      $existsStmt->fetch();
      $existsStmt->close();

      if ($rowCount === 0) {
         return [
            'success' => false,
            'error' => 'Program not found.'
         ];
      }

      $stmt = $this->conn->prepare('DELETE FROM program WHERE program_id = ?');
      if (!$stmt) {
         return [
            'success' => false,
            'error' => 'Failed to prepare delete statement.'
         ];
      }

      $stmt->bind_param('i', $programId);
      $deleted = $stmt->execute();
      $stmt->close();

      if (!$deleted) {
         return [
            'success' => false,
            'error' => 'Unable to delete program. Please try again.'
         ];
      }

      return ['success' => true, 'error' => ''];
   }
}

?>