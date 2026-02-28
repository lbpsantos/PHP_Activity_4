<?php
//Establish Connections
$conn = new mysqli ("localhost", "root", "", "school");

if ($conn->connect_errno) {
  printf("Unable to connect to the database:<br />%s", $conn->connect_error);
  exit();
}

?>