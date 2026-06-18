<?php
require_once __DIR__."/../db.php";
Securepg();

$callbackUrl = BASE_URL."/api/callback.php";
$userid = $_SESSION["globaluid"] ?? $_POST["userid"];
$name = $_POST["fullname"] ?? "";
$email = $_POST["email"] ?? "";
$phone = $_POST['phone'] ?? "";
$photo = $_POST["photo"] ?? "";
$plan  = (int) ($_POST["selplan"] ?? 0);
//$requestID = GetRequestID();
$status = "failed"; $data = []; $msg = "";

//////////////////// For Airtime & Data...

  if (!$userid) { toJSON(["status" => "LE01", "message" => "Session expired. Log in and try again!"]); exit; }
  if (empty($phone)) { toJSON(["status" => "error", "message" => "Phone number is required."]); exit; }
  //if (empty($email)) { toJSON(["status" => "error", "message" => "Email address is required."]); exit; }
  if ($plan < 1 || $plan > 6) { toJSON(["status" => "error", "message" => "Invalid plan"]); exit; }

  //Validate phone number
  $phone = "0".getPhone10Digits($phone);
  $network = isNetworkNumber($phone);

  if(!in_array($network,["mtn","glo","airtel","etisalat"])){
    toJSON(["status"=>"BErr00","message"=>"Invalid network phone number."]); exit;
  }
  
  $planname = $Plans[$plan][0];
  $planweight = $Plans[$plan][1] ?? 0;
  $amount = $Plans[$plan][2];
  $slot = $Plans[$plan][3];
  $bonusPoints = (int)( ($Plans[$plan][6] ?? 0) + ($Plans[$plan][7] ?? 0) );

  //Debit User's Wallet
  $newWallet = ChargeWallet($db,$userid,$amount,$bonusPoints,"debit","add");
  if($newWallet === false){ 
      toJSON(["status"=>"BErr02","message"=>"Insufficient fund."]); exit;
  }

  //Buy & Send Data...........
  $dataprice = $Plans[$plan][8];
  $isdatasent = 0;
  //GetPackageData($db,$userid,$plan,$dataprice,$phone,$network);
  $status = "paid";

  //Package bought before?........
  //list($isPackage,$ArrPackage) = GetUserPackageRecord($userid); //check whether user package exist or not
  SaveUserPackage($db,$userid,$name,$plan,$amount,$dataprice,$isdatasent,$status);
  
if($newWallet !== false){ $data["wallet"] = $newWallet; }
toJSON(["status"  => "success", "message" => "Package purchased successfully", "data" => $data]);