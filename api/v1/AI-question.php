this is my api code for now, help me update, add, complete it to production-ready code. do not remove what you suppose not to remove and don't omit anyting out excess unnnessary code. Make everything work, from requesting services from the two providers saving and normalizing the services. to sending the normalized services to the app UI for user to choose from. Then, display it on UI, user clicking it, receiving request from UI, send request to backend to purchase/order, and so on.

Below is the database structures
====================================
-- Database: `dbmlendro`
-- --------------------------------------------------------

CREATE TABLE `commissions` (
  `id` int(40) NOT NULL,
  `userid` varchar(45) DEFAULT NULL,
  `sprice` decimal(10,2) DEFAULT NULL,
  `requestid` varchar(50) DEFAULT NULL,
  `prodtype` varchar(45) DEFAULT NULL,
  `cprice` decimal(10,2) DEFAULT NULL,
  `supplier_cost` decimal(10,2) DEFAULT NULL,
  `commission` decimal(10,2) DEFAULT NULL,
  `sprofit` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `premiumusers` (
  `id` int(45) NOT NULL,
  `userid` varchar(45) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `plan` varchar(45) DEFAULT NULL,
  `amtpaid` int(45) DEFAULT 0,
  `dataprice` decimal(45,0) DEFAULT 0,
  `isdatasent` varchar(20) DEFAULT '0',
  `regdate` varchar(45) DEFAULT NULL,
  `updated` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `providers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `provider_services` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `provider_code` varchar(100) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `priority` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `repayments` (
  `id` int(11) NOT NULL,
  `loanid` int(11) NOT NULL,
  `amtrepaid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('pending','completed','overdue') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `savings` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `savings_type` enum('flex','fixed') NOT NULL,
  `status` enum('active','completed','withdrawn') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `network` varchar(50) DEFAULT NULL,
  `type` enum('airtime','data','bill') DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL COMMENT 'electricity,cabletv,education,betting,insurance',
  `price` decimal(10,2) DEFAULT NULL,
  `duration` int(45) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transtype` varchar(45) DEFAULT NULL COMMENT 'credit, debit',
  `refno` varchar(50) DEFAULT NULL,
  `transtitle` varchar(50) DEFAULT NULL,
  `transdesc` varchar(200) DEFAULT NULL,
  `status` varchar(45) DEFAULT 'pending' COMMENT 'pending, success, completed, defaulted, failed',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `ccode` varchar(35) DEFAULT '+234',
  `phone` varchar(15) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `bvn` varchar(45) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `fullname` varchar(65) DEFAULT NULL,
  `bank` varchar(50) DEFAULT NULL,
  `bankcode` varchar(35) DEFAULT NULL,
  `accno` varchar(45) DEFAULT NULL,
  `accname` varchar(50) DEFAULT NULL,
  `isverify` varchar(20) DEFAULT '0' COMMENT 'phone=1,bvn=2',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `bucbalance` decimal(10,8) DEFAULT 0.00000000,
  `loanlimit` int(50) DEFAULT 0,
  `totalscore` int(40) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `loancount` int(45) DEFAULT 0,
  `plan` varchar(25) DEFAULT NULL,
  `ostotalearn` decimal(10,2) DEFAULT 0.00,
  `osbalance` decimal(10,2) DEFAULT 0.00,
  `vscore` int(45) DEFAULT 0,
  `usage_recent` int(45) DEFAULT 0,
  `upoint` int(45) DEFAULT 0,
  `repayscore` int(45) DEFAULT 0,
  `ctpoint` int(45) DEFAULT 0,
  `bucwallet` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `wallet_logs` (
  `id` int(11) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `type` enum('credit','debit') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `commissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `requestid` (`requestid`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

ALTER TABLE `premiumusers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userid` (`userid`);

ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `provider_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_map` (`provider_id`,`service_id`);

ALTER TABLE `repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loanid` (`loanid`);

ALTER TABLE `savings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

  ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `refno` (`refno`),
  ADD KEY `userid` (`userid`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userid` (`userid`);

ALTER TABLE `wallet_logs`
  ADD PRIMARY KEY (`id`);

  ALTER TABLE `commissions`
  MODIFY `id` int(40) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  ALTER TABLE `premiumusers`
  MODIFY `id` int(45) NOT NULL AUTO_INCREMENT;

ALTER TABLE `providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `provider_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `repayments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `savings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `wallet_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`);

ALTER TABLE `repayments`
  ADD CONSTRAINT `repayments_ibfk_1` FOREIGN KEY (`loanid`) REFERENCES `loans` (`id`);

ALTER TABLE `savings`
  ADD CONSTRAINT `savings_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`);
COMMIT;




==========================

db.php (this is database connection file.)
=============
<?php
error_reporting(0);
//session_set_cookie_params(['lifetime' => 86400,'path' => '/', 'domain' => $_SERVER['HTTP_HOST'], 'secure' => false,'httponly' => true]);
//session_name("SESSION");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__."/configs.php";

// Error handling mode (VERY IMPORTANT for production debugging)
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // clean fetch
            PDO::ATTR_EMULATE_PREPARES => false // real prepared statements
        ]
    );

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

require_once __DIR__."/helpers/helpers.php";
?>


cronjob\index.php (for cron job to connect to third-party endpoints and pull response services)
========
<?php
//get services from platforms, normalize, and store them in database
//cron job every hour: 0 * * * * php sync_services.php
require __DIR__."/../db.php";
require __DIR__."/../ProviderFactory.php";
require __DIR__."/../Normalizer.php";
require __DIR__."/../helpers/helpers.php";

$providers = ["cheapdatahub", "connectbridge"];

foreach ($providers as $name) {

    $provider = ProviderFactory::make($name); //get provider configs...
    $rawServices = $provider->getServices();

    $services = $provider->normalizeServices($rawServices);

    /* Normalize
    if ($name == "cheapdatahub") {
        $services = Normalizer::normalizeCheapDataHub($rawServices);
    } else {
        $services = Normalizer::normalizeConnectBridge($rawServices);
    }*/

    foreach ($services as $srv) {
        // 1. Check if service already exists
        $stmt = $db->prepare("SELECT id FROM services WHERE name=? AND network=? AND type=?");
        $stmt->execute([$srv['name'], $srv['network'], $srv['type']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($service) {
            $service_id = $service['id'];
        } else {
            $markup = MARKUP;
            $price = (strtolower($srv['type']) == 'airtime') ? $srv['cost_price'] : round($srv['cost_price'] * (1 + $markup), 2);

            // 2. Insert new service //////////////
            $stmt = $db->prepare("INSERT INTO services (name, network, type, category, price) VALUES (?,?,?,?,?)");
            $stmt->execute([$srv['name'], $srv['network'], $srv['type'], $srv['category'], $price]);
            $service_id = $db->lastInsertId();
        }

        //3. Insert provider mapping
        $provider_id = getProviderId($name);

        $stmt = $db->prepare("INSERT INTO provider_services (provider_id, service_id, provider_code, cost_price) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE cost_price = VALUES(cost_price)");

        $stmt->execute([$provider_id, $service_id, $srv['provider_code'], $srv['cost_price']]);
    }
}
?>

helpers\helpers.php
===============
<?php
function toJSON($jsonstr,$isEcho=true){
    if($isEcho){
        echo json_encode($jsonstr);
    }else{
        return json_encode($jsonstr);
    }    
}
///////////
function Securepg(){
    if(!isset($_SESSION["globaluid"])){
        toJSON(["status"=>"failed","message"=>"Account could not be found. Try login!"]); exit;
    }
    return true;
}
///////////////////////////////////
function getStatus($code){
    $Status = ["08011111111"=>"Successful","201000000000"=>"Pending","500000000000"=>"Unexpected Response","400000000000"=>"No Response","300000000000"=>"Timeout"];
    return $Status[$code];
}
////////////////
function getNetworkFromCode($serviceID){
    $code = explode("-",$serviceID); //0-1-2
    if(count($code) > 2) return $code[0]."-".$code[1];
    return $code[0];
}
/////////////
function getPhone10Digits($phone){
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    if (strpos($phone, '+234') === 0) { // remove +234
        $phone = substr($phone, 4); //'0' . 
    }
    if (strpos($phone, '234') === 0 && strlen($phone) === 13) { // Convert 234xxxxxxxxxx to xxxxxxxxxx
        $phone = substr($phone, 3); //'0' . 
    }
    if (preg_match('/^\d{11}$/', $phone) && str_starts_with($phone, "0")) { // If 11 digits, remove leading 0
        $phone = substr($phone, 1);
    }
    return $phone;
}
/////////////////
function isNetworkNumber($phone, $network = null){
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone); // Remove spaces and symbols
    if (strpos($phone, '+234') === 0) { // Convert +234 to 0
        $phone = '0' . substr($phone, 4);
    }    
    if (strpos($phone, '234') === 0 && strlen($phone) === 13) { // Convert 234xxxxxxxxxx to 0xxxxxxxxxx
        $phone = '0' . substr($phone, 3);
    }
    if (preg_match('/^\d{10}$/', $phone)) { // If 10 digits, assume missing leading 0
        $phone = '0' . $phone;
    }
    $patterns = [
        'mtn' => '/^(0703|0706|0803|0806|0810|0813|0814|0816|0903|0906|0913|0916)\d{7}$/',
        'airtel' => '/^(0701|0708|0802|0808|0812|0901|0902|0904|0907|0912)\d{7}$/',
        'glo' => '/^(0705|0805|0807|0811|0815|0905|0915)\d{7}$/',
        'etisalat' => '/^(0809|0817|0818|0909|0908)\d{7}$/'
    ]; //data: airtel-data,mtn-data,glo-data,etisalat-data,glo-sme-data,spectranet,smile-direct
    // If network is provided
    if ($network !== null) {
        $network = strtolower($network);
        if (!isset($patterns[$network])) {
            return false; // unknown network
        }
        return preg_match($patterns[$network], $phone) === 1;
    }
    // If no network specified, detect network
    foreach ($patterns as $net => $pattern) {
        if (preg_match($pattern, $phone)) {
            return $net; // return detected network
        }
    }
    return false;
}
////////////////////
function GetRefNo($prefix){
    $n = ($prefix != "")? $prefix."-".time()."-".uniqid() : time()."-".uniqid();
    return $n;
}
/////////////////////
function GetRequestID(){
    $n = date("YmdHi").'L'.uniqid();
    return $n;
}
/////////////////
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    if ($timestamp === false) return "Invalid date";
    $diff = $now - $timestamp;
    if ($diff < 0) return "Just now";
    $units = [31536000 => "yr",2592000 => "mth", 604800 => "wk", 86400 => "day", 3600 => "hr", 60 => "min", 1 => "sec"];
    foreach ($units as $seconds => $name) {
        if ($diff >= $seconds) {
            $value = floor($diff / $seconds);
            return $value . " " . $name . ($value > 1 ? "s" : "") . " ago";
        }
    }
    return "Just now";
}
//////////
function SendBycURL($url, $params = null, $method = "GET",$useBearer = false,$Key2Use=null) {
global $G_PKEY,$G_SKEY,$G_APIKEY,$squard_SK,$squard_PK,$squard_Merchant;
    $ch = curl_init();
    
    if($useBearer){
        $Key2Use = ($Key2Use == "pk") ? $squard_PK:$squard_SK;
        $headers_post = ["Authorization: Bearer $Key2Use", "Content-Type: application/json", "accept: application/json"];
        $headers_get = $headers_post;
    }else{
        $headers_post = ["api-key: {$G_APIKEY}","secret-key: {$G_SKEY}", "Content-Type: application/json", "accept: application/json"];
        $headers_get = ["api-key: {$G_APIKEY}","public-key: {$G_PKEY}", "Content-Type: application/json", "accept: application/json"];
    }    

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, false);
    // Attach payload only for POST / PUT
    if ($params && in_array(strtoupper($method), ["POST", "PUT"])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_post);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }else{
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_get);
    }
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch); curl_close($ch);
        return json_encode([ "status" => "error","message" => $error ]);
    }
    curl_close($ch); flush(); 
    return $response;
}
////////////////////////////////////////////
/////////// DB FUNCTIONS //////////////////
//////////////////////////////////////////
function GetUserPackageRecord(PDO $db, $uid){
    $stmt = $db->prepare("SELECT * FROM premiumusers WHERE userid = ?")->execute([$userid]);
    $squard_SK = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rs) { return [false,[]]; }
    return [true,["id"=>$rs["id"],"name"=>$rs["name"],"plan"=>$rs["plan"],"amtpaid"=>$rs["amtpaid"],"dataprice"=>$rs["dataprice"],"isdatasent"=>$rs["isdatasent"],"regdate"=>$rs["regdate"],"status"=>$rs["status"] ]];
}
///////////////////////////
function SaveUserPackage(PDO $db,$userid,$name,$plan,$amtpaid,$dataprice,$isdatasent,$status){
    $updated = date("Y-m-d H:i:s");
        $stmt = $db->prepare("INSERT INTO premiumusers (userid,name,plan,amtpaid,dataprice,isdatasent,regdate,updated,status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE plan = VALUES(plan), amtpaid = VALUES(amtpaid), dataprice = VALUES(dataprice), isdatasent = VALUES(isdatasent), status = VALUES(status), updated = VALUES(updated)");
        
        $stmt->execute([$userid, $name, $plan, $amtpaid,$dataprice,$isdatasent,$updated,$updated,$status]);
        ///////
        $db->prepare("UPDATE wallets SET plan = ? WHERE userid = ?")->execute([$plan,$userid]);
}
/////////////
function SaveTransaction(PDO $db,$userid,$amount,$transtype,$transtitle,$transdesc,$refno,$status){
    $created_at = date("Y-m-d H:i:s");
    $updated_at = $created_at;
    $stmt = $db->prepare("INSERT INTO transactions (userid, amount, transtype, refno, transtitle, transdesc, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount), transtitle = VALUES(transtitle), transdesc = VALUES(transdesc), status = VALUES(status), updated_at = VALUES(updated_at)");
    $stmt->execute([$userid, $amount, $transtype, $refno,$transtitle,$transdesc,$status,$created_at,$updated_at]);
}
//////////////////////
function SaveCommission(PDO $db,$userid, $prodtype, $sprice, $cprice, $rate, $supplier_cost, $commission, $sprofit, $requestID){
    $created_at = date("Y-m-d H:i:s");
    $stmt = $db->prepare("INSERT INTO  commissions (userid, sprice, requestid, prodtype, cprice, commission_rate, supplier_cost, commission, sprofit, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE sprice = VALUES(sprice), prodtype = VALUES(prodtype), cprice = VALUES(cprice), commission_rate = VALUES(commission_rate), supplier_cost = VALUES(supplier_cost), commission = VALUES(commission), sprofit = VALUES(sprofit)");
    $stmt->execute([$userid, $sprice, $requestID, $prodtype, $cprice, $rate, $supplier_cost, $commission, $sprofit, $created_at]);
}
/////////////
function getProviderId($slug) { //make sure slug is unique in providers table
    global $db;
        $stmt = $db->prepare("SELECT id FROM providers WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { throw new Exception("Provider not found: " . $slug); }
    return $row['id'];
}
///////////
function ChargeWallet(PDO $db,$userid, $amount,$point=0,$type="debit",$DoPoint=null){
//$DoPoint =>null,add,remove
    if ($amount <= 0) return false;

    try {
        // Lock row
        $stmt = $db->prepare("SELECT *, (SELECT MAX(usage_recent) FROM wallets) AS maxusage FROM wallets WHERE userid = ? FOR UPDATE");
        $stmt->execute([$userid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) { return false; }
        if($type === "credit"){
            $newbalance = $row["balance"] + $amount;
        }elseif($type === "debit"){
            if ($row["balance"] < $amount) { return false; }
            $newbalance = $row["balance"] - $amount;            
        }else{
            $newbalance = $row["balance"];
        }

        if($DoPoint === "add"){
            $usage_recent = $row["usage_recent"] + $point;
            $upoint = $row["upoint"] + $point;
            $totalscore = $upoint + ($row["vscore"] ?? 0);
        }elseif($DoPoint === "remove"){
            $usage_recent = ($row["usage_recent"] >= $point) ? $row["usage_recent"] - $point : 0;
            $upoint = ($row["upoint"] >= $point) ? $row["upoint"] - $point : 0;
            $totalscore = ($row["totalscore"] >= $point) ?  $row["totalscore"] - $point : 0;
        }else{
            $usage_recent = $row["usage_recent"];
            $upoint = $row["upoint"];
            $totalscore = $row["totalscore"];
        }

        $updated_at = date("Y-m-d H:i:s");

        $stmt = $db->prepare("UPDATE wallets SET balance = ?, totalscore = ?, usage_recent = ?, upoint = ?, updated_at = ? WHERE userid = ?");
        $stmt->execute([$newbalance, $totalscore, $usage_recent, $upoint, $updated_at, $userid]);

        return [
            "balance"      => $newbalance,
            "bucbalance"   => $row["bucbalance"],
            "loanlimit"    => $row["loanlimit"],
            "totalscore"   => $totalscore,
            "plan"    => $row["plan"],
            "ostotalearn"    => $row["ostotalearn"],
            "osbalance"    => $row["osbalance"],
            "loancount"    => $row["loancount"],
            "scores" => [
                "U"    => $usage_recent, //usage point this year
                "UALL" => $upoint, //usage point
                "V"=>$row["vscore"], //verification score/point
                "R"=>$row["repayscore"], //repayment score
                "C"=>$row["ctpoint"] //community trust score
            ],
            "maxusage" => $row["maxusage"] ];
    } catch (Exception $e) {
        return false;
    }
}
//////////////////////
////////////////////////
function getAllServices(){
global $db;
    $stmt = $db->prepare("SELECT * FROM services WHERE status = 1 ORDER BY type, network, duration ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = ["airtime" => [], "data" => [], "bill" => []];

    // group data
    foreach ($rows as $row) {
        $type = $row['type'];
        $network = $row['network'] ?: "general";
        $category = $row['category'] ?: "general";

        // initialize structure
        if (!isset($response[$type][$network])) {
            $response[$type][$network] = [];
        }

        $response[$type][$network][] = [
            "id" => $row['id'],
            "name" => $row['name'],
            "price" => $row['price'],
            "category" => $row['category'],
            "duration" => $row['duration'] ?? null
        ];
    }

    return toJSON(["status" => "success","data" => $response],false);
}
//////////////////////
function getServices($type = []){
    if(count($type) > 0){
        
    }
}
////////////////////////////
function getServicesBy($type,$network=null,$category=null){
    global $db;
    if(!empty($type) && empty($network) && empty($category)){
        $sql = "SELECT * FROM services WHERE type = ? ORDER BY type,network,duration ASC";
        $sqlArr = [$type];
    }elseif(!empty($type) && !empty($network) && empty($category)){
        $sql = "SELECT * FROM services WHERE type = ? AND network = ? ORDER BY type,network,duration ASC";
        $sqlArr = [$type,$network];
    }elseif(!empty($type) && empty($network) && !empty($category)){
        $sql = "SELECT * FROM services WHERE type = ? AND category = ? ORDER BY type,network,duration ASC";
        $sqlArr = [$type,$category];
    }else{
        return toJSON(["status"=>"failed","message"=>"Type of service not specified."]); exit;
    }

    $stmt = $db->prepare($sql)->execute($sqlArr);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [$type => []];

    // group data
    foreach ($rows as $row) {
        $type = $row['type'];
        $network = $row['network'] ?: "general";
        $category = $row['category'] ?: "general";

        // initialize structure
        if (!isset($response[$type][$network])) {
            $response[$type][$network] = [];
        }

        $response[$type][$network][] = [
            "id" => $row['id'],
            "name" => $row['name'],
            "price" => $row['price'],
            "category" => $row['category'],
            "duration" => $row['duration'] ?? null
        ];
    }
    return toJSON(["status" => "success","data" => $response],false);
}
?>

providers\BaseProvider.php
==============

<?php
abstract class BaseProvider {

    protected $baseUrl;
    protected $apiKey;

    public function __construct($config) {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->apiKey = $config['api_key'];
    }

    protected function request($endpoint, $payload = [], $method = 'GET') {

        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        // GET params
        if ($method === 'GET' && !empty($payload)) {
            $url .= '?' . http_build_query($payload);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json"
            ]);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                "status" => "error",
                "message" => curl_error($ch)
            ];
        }

        curl_close($ch);

        $decoded = json_decode($response, true);

        // Handle invalid JSON
        if (!$decoded) {
            return [
                "status" => "error",
                "message" => "Invalid response from provider",
                "raw" => $response
            ];
        }

        return $decoded;
    }
}
?>

providers\ProviderA.php (code for a provider let say CheapDataHub)
=========
<?php
//$baseUrl,$apiKey; from BaseProvider
//$headers = ["Authorization: Bearer XXXXX"];
require_once __DIR__ . "/BaseProvider.php";

class ProviderA extends BaseProvider implements ProviderInterface { // cheapdatahub

    public function getServices() { // CheapDataHub uses apikey in query
        $endpoint = "/services?apikey=" . $this->apiKey;
        return $this->request($endpoint, [], 'GET');
    }

    public function purchase($data) {
        $endpoint = "/purchase?apikey=" . $this->apiKey;
        $payload = [
            "service" => $data['provider_code'],
            "phone" => $data['phone'],
            "amount" => $data['amount'],
            "ref" => uniqid("ldr_")
        ];

        return $this->request($endpoint, $payload, 'POST');
    }
}
?>

providers\ProviderB.php (code for second provider let say connectbridge)
=========
<?php
require_once __DIR__ . "/BaseProvider.php";

class ProviderB extends BaseProvider implements ProviderInterface { // connectbridge

    public function getServices() { // connectbridge uses apikey in query
        $endpoint = "/services?apikey=" . $this->apiKey;
        return $this->request($endpoint, [], 'GET');
    }

    public function purchase($data) {
        $endpoint = "/purchase?apikey=" . $this->apiKey;
        $payload = [
            "service" => $data['provider_code'],
            "phone" => $data['phone'],
            "amount" => $data['amount'],
            "ref" => uniqid("ldr_")
        ];

        return $this->request($endpoint, $payload, 'POST');
    }
}
?>

services\index.php (code to get all or some services to be display on app ui for use)
=========
<?php //get/list all services...
require __DIR__."/../db.php";
Securepg();

//$userid = $_SESSION["globaluid"] ?? null;
echo getAllServices();
?>

services\order.php (code to process orders from the app ui)
=========
<?php
//order service and charge user wallet
require __DIR__."/../db.php";
Securepg();

//$type = $_POST["type"] ?? null;
//$network = $_POST["network"] ?? null;
//$category = $_POST["category"] ?? null;
?>

Normalizer.php (code to normalize services response from provider. but you said it can now be a helper and the normalizer can be in each provider class)
=========
<?php
class Normalizer {

    public static function normalizeCheapDataHub($data) {
        $services = [];

        foreach ($data as $item) {
            $services[] = [
                "type" => "data",
                "category" => "data", //daily,weekly,monthly
                "duration" => $duration, // 1,2,7,30 in days
                "network" => $item['network'],
                "name" => $item['plan'],
                "provider_code" => $item['code'],
                "cost_price" => $item['price']
            ];
        }

        return $services;
    }

    public static function normalizeConnectBridge($data) {
        $services = [];

        foreach ($data as $item) {
            $services[] = [
                "type" => $item['type'], // airtime/data
                "network" => $item['network'],
                "name" => $item['name'],
                "provider_code" => $item['id'],
                "cost_price" => $item['amount']
            ];
        }

        return $services;
    }
}
?>

ProviderFactory.php
============
<?php
require_once __DIR__ . "/providers/ProviderA.php";
require_once __DIR__ . "/providers/ProviderB.php";

class ProviderFactory {
    public static function make($providerName) {
        global $db;

        if (!$providerName) { throw new Exception("Provider name is required");  }

        // 1. Fetch provider config from DB
        $stmt = $db->prepare("SELECT * FROM providers WHERE slug = ? AND status = 1 LIMIT 1");
        $stmt->execute([$providerName]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) { throw new Exception("Provider not found or inactive: " . $providerName); }

        // 2. Validate required config fields
        if (empty($config['base_url']) || empty($config['api_key'])) {
            throw new Exception("Invalid provider config for: " . $providerName);
        }

        // 3. Return correct provider instance
        switch ($providerName) {
            case 'cheapdatahub':
                return new ProviderA($config);
            case 'connectbridge':
                return new ProviderB($config);
            default:
                throw new Exception("Unsupported provider: " . $providerName);
        }
    }

    // Optional: Get provider config only (without instantiating class)

    public static function getConfig($providerName) {
        global $db;
        $stmt = $db->prepare("SELECT * FROM providers WHERE slug = ? AND status = 1 LIMIT 1");
        $stmt->execute([$providerName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Optional: Get provider slug by ID (used in transactions)

    public static function getSlugById($providerId) {
        global $db;
        $stmt = $db->prepare("SELECT slug FROM providers WHERE id = ? LIMIT 1");
        $stmt->execute([$providerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['slug'] ?? null;
    }
}
?>

ProviderInterface.php
============
<?php
interface ProviderInterface {
    //public function getServices();
    //public function purchase($data);
    public function getServices(): array;
    public function purchase($data): array;
    public function normalizeServices(array $raw): array;
}
?>

ServiceManager.php
============
<?php

class ServiceManager {

    // Get cheapest available provider for a service
    public static function getBestProvider($service_id) {
        global $db;
        $stmt = $db->prepare("SELECT ps.*, p.slug FROM provider_services ps JOIN providers p ON ps.provider_id = p.id
            WHERE ps.service_id = ? AND p.status = 1 ORDER BY ps.priority ASC, ps.cost_price ASC LIMIT 1");
        $stmt->execute([$service_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {throw new Exception("No active provider found for service ID: " . $service_id); }
        return $result;
    }

    // Get ALL providers for fallback logic
    public static function getAllProviders($service_id) {
        global $db;
        $stmt = $db->prepare("SELECT ps.*, p.slug FROM provider_services ps JOIN providers p ON ps.provider_id = p.id WHERE ps.service_id = ? AND p.status = 1 ORDER BY ps.priority ASC, ps.cost_price ASC");
        $stmt->execute([$service_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

TransactionService.php
============
<?php

class TransactionService {
    public static function process($user_id, $service_id, $phone, $amount) {
        global $db;
        try {

            // 1. Start DB transaction
            $db->beginTransaction();

            // 2. Check wallet balance
            $stmt = $db->prepare("SELECT wallet FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['wallet'] < $amount) {
                throw new Exception("Insufficient balance");
            }

            // 3. Deduct wallet
            self::deductWallet($user_id, $amount);

            // 4. Get all providers (for fallback)
            $providers = ServiceManager::getAllProviders($service_id);

            $finalResponse = null;
            $finalProviderId = null;
            $status = "failed";

            foreach ($providers as $providerData) {

                $provider = ProviderFactory::make($providerData['slug']);

                $response = $provider->purchase([
                    "provider_code" => $providerData['provider_code'],
                    "phone" => $phone,
                    "amount" => $amount
                ]);

                // Normalize status
                $providerStatus = strtolower($response['status'] ?? 'failed');

                if ($providerStatus === 'success') {
                    $status = "success";
                    $finalResponse = $response;
                    $finalProviderId = $providerData['provider_id'];
                    break;
                }
            }

            // 5. If all providers failed → refund
            if ($status !== "success") {
                self::refundWallet($user_id, $amount);
            }

            // 6. Save transaction
            $stmt = $db->prepare("
                INSERT INTO transactions 
                (user_id, service_id, provider_id, amount, phone, status, reference, response)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $reference = uniqid("lendro_");

            $stmt->execute([
                $user_id,
                $service_id,
                $finalProviderId,
                $amount,
                $phone,
                $status,
                $reference,
                json_encode($finalResponse)
            ]);

            // 7. Commit DB transaction
            $db->commit();

            return [
                "status" => $status,
                "reference" => $reference,
                "data" => $finalResponse
            ];

        } catch (Exception $e) {

            // Rollback everything
            $db->rollBack();

            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    private static function deductWallet($user_id, $amount) {

        global $db;

        $stmt = $db->prepare("
            UPDATE users 
            SET wallet = wallet - ? 
            WHERE id = ?
        ");

        $stmt->execute([$amount, $user_id]);
    }

    private static function refundWallet($user_id, $amount) {

        global $db;

        $stmt = $db->prepare("
            UPDATE users 
            SET wallet = wallet + ? 
            WHERE id = ?
        ");

        $stmt->execute([$amount, $user_id]);
    }
}
?>

============
