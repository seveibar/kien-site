<?php

// For displaying error and stopping execution
function error_out($info){
    echo "<script>alert(\"" . str_replace("\n","",str_replace("\"","",$info)) . "\");</script>";
    exit();
}

// For loading and parsing json
function load_json($path){
    if (!file_exists($path)){
        error_out("ERROR: Cannot load json from non-existant file $path");
    }

    $json_data = json_decode(file_get_contents($path),true);

    if ($json_data == NULL){
        error_out("ERROR: Cannot parse json $path");
    }

    return $json_data;
}

?>
