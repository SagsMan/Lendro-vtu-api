<?php
require __DIR__."/../db.php";
Securepg();

$uid = $_SESSION["globaluid"];
$clientVer = isset($_POST['ver']) ? trim($_POST['ver']) : null;
$data = [];

//toJSON(["status"=>"failed","data"=>BASE_URL]); exit;

//wallets........
$stmt = $db->prepare("SELECT users.id,users.fullname,users.phone,users.email,wallets.balance,wallets.plan,wallets.bucbalance, wallets.loanlimit, wallets.loancount, wallets.totalscore, wallets.usage_recent, wallets.upoint, wallets.vscore, wallets.repayscore, wallets.ctpoint, (SELECT MAX(w2.usage_recent) FROM wallets w2) AS maxusage FROM users INNER JOIN wallets ON users.id = wallets.userid WHERE users.id = ? ORDER BY users.id LIMIT 1");
$stmt->execute([$uid]);
$rs = $stmt->fetch(PDO::FETCH_ASSOC);
$wallet = [];

if($rs){
  $uid = $rs["id"];
  $name = $rs["fullname"];
  $phone = $rs["phone"];
  $email = $rs["email"];
  $maxusage = ($rs['maxusage'] > 100)?$rs['maxusage']:200;

  $wallet = [
            "balance"=>$rs["balance"] ?? 0,
            "bucbalance"=>$rs["bucbalance"] ?? 0,
            "loanlimit"=>$rs["loanlimit"] ?? 0,
            "totalscore"=>$rs["totalscore"] ?? 0,
            "plan"    => $rs["plan"],
            "ostotalearn"    => $rs["ostotalearn"] ?? 0,
            "osbalance"    => $rs["osbalance"] ?? 0,
            "loancount"=>$rs["loancount"] ?? 0,
            "scores"=>["U"=>$rs["usage_recent"],"UALL"=>$rs["upoint"],"V"=>$rs["vscore"],"R"=>$rs["repayscore"],"C"=>$rs["ctpoint"]],
            "maxusage" => $maxusage
            ];
}

// recent transactions............
$stmt = $db->prepare("SELECT * FROM transactions WHERE userid = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$transactions = [];

if($rows){
  foreach($rows as $rs){
      $timeago = timeAgo($rs["created_at"]); //date("Y-m-d h:i A",$rs["created_at"]);
      $timeago = ($rs["transtype"] === "deposit") ? ucfirst($rs["status"])." - ".$timeago : $timeago;
      $transactions[] = ["id"=>$rs["id"],"type"=>$rs["transtype"],"description"=>$rs["transtitle"],"amount"=>$rs["amount"],"time"=>$timeago,"status"=>$rs["status"] ];
  }
}

// 20 top contenders: leaderboard
$stmt = $db->prepare("SELECT users.fullname,wallets.id,wallets.userid,wallets.usage_recent,wallets.upoint FROM wallets INNER JOIN users ON wallets.userid = users.id WHERE wallets.usage_recent > 0 AND wallets.repayscore >= 0 ORDER BY wallets.usage_recent DESC LIMIT 20");
$stmt->execute([]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$leaderboard = [];

if($rows){
  $rank = 1;
  foreach($rows as $rs){
      $name = ($rs["userid"] == $uid) ? "You":$rs["fullname"];
      if($rank == 1){ $maxUsage = $rs["upoint"]; $icon = "🏆"; }
      if($rank == 2){ $icon = "🥈"; }
      if($rank == 3){ $icon = "🥉"; }
      if($rank > 3){ $icon = "👨"; }
      $leaderboard[] = ["rank"=>$rank, "userid"=>$rs["userid"],"icon"=>$icon,"name"=>$name,"score"=>$rs["usage_recent"] ];
  }
}

$data["wallet"] = $wallet; //my wallet
$data["transactions"] = $transactions; //recent 10 tranx
$data["leaderboard"] = $leaderboard; //top 10 contenders

if (!$clientVer || strtotime($clientVer) < strtotime('today')) { 
  $data["services"] = getServices([]);
}

// === Response ===
toJSON(["status" => "success", "data" => $data ]);
