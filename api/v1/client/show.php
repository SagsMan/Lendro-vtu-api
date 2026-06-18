<?php
//get particular service(s) by type or bill->category
require __DIR__."/../db.php";
Securepg();

$type = $_POST["type"] ?? null;
$network = $_POST["network"] ?? null;
$category = $_POST["category"] ?? null;
echo getServicesBy($type,$network,$category);