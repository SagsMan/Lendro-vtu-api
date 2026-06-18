<?php
require_once __DIR__."/../db.php";
Securepg();

// API for forgot password (sending OTP)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['phone'])) {
  // Logic to send OTP to phone number
  // You can use an SMS gateway like Twilio here
  
  echo json_encode(['status' => 'success']);
}