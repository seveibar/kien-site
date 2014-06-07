<?php

// Returns course data path, loads config if necessary
function get_data_path() {
    // Return course data path
    return load_config()["course_data_path"];
}

// Returns submissions path, loads config if necessary
function get_submissions_path(){
    // Return submissions path
    return load_config()["submissions_path"];
}


// Load site configuration file
function load_config($reload=false){
    global $site_config_path;
    global $config;

    if ($config != NULL && !$reload){
        return $config;
    }

    if (!file_exists($site_config_path)) {
        error_out("$site_config_path does not exist. See setup guide");
    }
    $config = load_json($site_config_path);
    return $config;
}

?>
