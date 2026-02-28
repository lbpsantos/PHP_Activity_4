<?php
require_once 'auth.php';
require_login();

// Immediately clear the session, set a success flash, and redirect to login
session_unset();
session_destroy();
set_flash_message('You have successfully logged out!', 'success');

header('Location: login.php');
exit;