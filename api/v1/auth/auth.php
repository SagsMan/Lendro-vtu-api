<?php
require __DIR__."/../db.php";

if (isset($_SESSION['globaluid'])) {
    toJSON([ "status" => "success", "loggedin" => true ]);
} else {
    toJSON(["status" => "LE01", "loggedin" => false ]);
}
