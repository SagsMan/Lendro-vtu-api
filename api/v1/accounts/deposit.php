<?php
require_once __DIR__."/../db.php";
Securepg();

$callbackUrl = BASE_URL."/api/callback.php";
$userid = $_SESSION["globaluid"] ?? null;
$title = $_POST["title"] ?? "";
$category = $_POST["category"];
$status = $_POST["status"]; 
$data = []; $msg = "";

//////////////////// Initialize deposit and generate refno ///////////////
if(strtolower($category) === "deposit" && $status === "pending"){
  $amount = (float) ($_POST["amount"] ?? 0);
  $fee = (float) ($_POST['fee'] ?? ($DWFee * $amount));
  $total = (float) ($_POST['total'] ?? ($amount + $fee));
  $email = $_POST['email'] ?? "favoursdot@gmail.com";
  $refno = GetRefNo($userid);

  if ($amount < 100) { toJSON(["status" => "error", "message" => "Minimum amount is N100"]); exit; }
  //submit pending data...
  $transtype = "deposit"; 
  $transtitle = "Deposit"; 
  $transdesc = "Deposit ".$amount." (".$fee." fee)";

  SaveTransaction($db,$userid,$amount,$transtype,$transtitle,$transdesc,$refno,$status);
  $url = $squard_Endpoint."/transactions/initiate";
  
  toJSON(["status"  => "success", "data" => ["amount"=>$total,"email" => $email,"transaction_ref" => $refno]]);   
}

/////////////////// After payment gateway, verify payment and get status ///////////
if(strtolower($category) === "deposited" && $status === "processed"){
    $total = (float) ($_POST['total'] ?? 0);
    $refno = $_POST["refno"] ?? null;

    if (!$userid) { toJSON(["status" => "error", "message" => "Invalid user account."]); exit; }
    if (!$refno) { toJSON(["status" => "error", "message" => "Invalid reference number!"]); exit; } 

    $res = SendBycURL($squard_Endpoint."/transaction/verify/".$refno,null,"GET",true,"sk");
    $res = json_decode($res,true);

    if ($res['status'] === 200 && strtolower($res['data']['transaction_status']) === "success") {
      $txAmount = (float)$res['data']['transaction_amount'];
      $txAmount = $txAmount / 100;

      try{
          $db->beginTransaction();
          $stmt = $db->prepare("SELECT id,amount,transtype,transtitle,transdesc,status,created_at FROM transactions WHERE userid = ? AND refno = ? FOR UPDATE");

          $stmt->execute([$userid,$refno]);
          $rs = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$rs) { throw new Exception("Record does not exist"); }
          if ($rs["status"] !== "pending") { throw new Exception("Deposit has already been processed"); }

          $dAmount = (float)$rs["amount"];
          $txID = $rs["id"];
          $timeago = timeAgo($rs["created_at"]);

          if($txAmount > $dAmount){
              $nFee = $txAmount - $dAmount;
              $tAmount = $txAmount;
              $nAmount = $dAmount;
          }else{
              $nFee = $txAmount * $DWFee;
              $nAmount = $txAmount - $nFee;
              $tAmount = $txAmount;
          }

          $nstatus = "success";
          $transtitle = "Deposit";
          $transdesc  = "Deposit ".$nAmount." (".$nFee." fee)";
          
          SaveTransaction($db,$userid,$nAmount,"deposit",$transtitle,$transdesc,$refno,$nstatus);
          $myWallet = SaveWallet($db,$nAmount,$userid);
          if ($myWallet === false) { throw new Exception("Wallet update failed"); }

          $db->commit();
          
          $data["wallet"] = $myWallet;
          $data["amount"] = $nAmount;
          $data["fee"] = $nFee;
          
          $data["transactions"] = ["id" => $txID, "type"=>"deposit","description"=>$transtitle,"amount"=>$nAmount,"time"=>ucfirst($nstatus)." - ".$timeago,"status"=>$nstatus ];

          $msg = $CurrencySymbol . $nAmount . " deposit was successful";
          toJSON(["status"=>"success","message"=>$msg,"data" => $data ]);

      }catch(e){
          $db->rollBack();
          toJSON(["status"=>"failed","message"=>$e->getMessage(),"data"=>$data ]);
      }
      
    } else {
      toJSON(["status"=>"failed","message"=>"Payment not successful","data" => $data]);
    }
}