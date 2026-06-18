<?php
require_once __DIR__."/../db.php";
Securepg();

$userid = $_SESSION["globaluid"];
//$clientVer = isset($_POST['ver']) ? trim($_POST['ver']) : null;
////////////////////////////////////////////////

// 20 top contenders: leaderboard
$stmt = $db->prepare("SELECT users.fullname,wallets.id,wallets.plan,wallets.plan,wallets.userid,wallets.usage_recent,wallets.upoint FROM wallets INNER JOIN users ON wallets.userid = users.id WHERE wallets.usage_recent > 0 AND wallets.repayscore >= 0 ORDER BY wallets.usage_recent DESC LIMIT 50");
$stmt->execute([]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$leaderboard = [];
$isInTop20 = false;

if($rows){
  $rank = 1; 
  foreach($rows as $rs){
      if($rs["userid"] == $userid){  $name = "You"; $isInTop20 = true; }else{ $rs["fullname"]; }

      if($rank == 1){ $maxUsage = $rs["upoint"]; $icon = "🏆"; }
      if($rank == 2){ $icon = "🥈"; }
      if($rank == 3){ $icon = "🥉"; }
      if($rank > 3){ $icon = "👨"; }
      $leaderboard[] = ["rank"=>$rank, "userid"=>$rs["userid"],"icon"=>$icon,"name"=>$name,"score"=>$rs["usage_recent"] ];
  }
}
////////////////////////
if(!$isInTop20){
    $stmt = $db->prepare("SELECT COUNT(*)+1 AS rank, wallets.userid,wallets.usage_recent,wallets.upoint FROM wallets INNER JOIN users ON wallets.userid = users.id WHERE wallets.usage_recent > (SELECT usage_recent FROM wallets WHERE userid = :uid) AND wallets.repayscore >= 0");
    $stmt->execute(['uid' => $userid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row){
        $icon = "👤"; $rank = $row["rank"]; $usage = $row["usage_recent"];
        if($usage > 0){
            $leaderboard[] = ["rank"=>$rank, "userid"=>$userid,"icon"=>$icon,"name"=>"You","score"=>$usage ];
        }        
    }
}

///////////////////
$data["leaderboard"] = $leaderboard;
toJSON(["status" => "success", "data" => $data ]);