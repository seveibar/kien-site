<?php

// This script is injected into the index.php script when development mode is enabled

if (isset($_GET["id"])){
    $_SESSION["id"] = $_GET["id"];
}

?>
