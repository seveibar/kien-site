<?php

// Relative to public directory
static $site_config_path = "../config.json";

// Enable errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Load configuration files for server
require_once("../private/model/site_config.php");
load_config();

if (dev_mode()){
    require_once("../dev/inject.php");
}else{
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (!isset($_SESSION["id"])) {
    //Should direct to login instead
    echo "ERROR: This page requires login";
    exit();
}

if (isset($_GET["page"])) {
    $page = htmlspecialchars($_GET["page"]);
} else {
    $page = "homework";
}

if ($page == "upload") {
    require_once("../private/controller/upload.php");
} else if ($page == "update") {
    require_once("../private/controller/update.php");
} else {
    require_once("../private/controller/homework.php");
}
?>
