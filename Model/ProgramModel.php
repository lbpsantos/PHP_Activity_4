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

   public function read(){

   }

   public function update(){

   }

   public function delete(){
    
   }
}

?>