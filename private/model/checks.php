<?php

// Security check for that makes sure the session id matches the username
function check_session($username){
    if ($username !== $_SESSION["id"]){
        error_out("Invalid Session!");
    }
}

// Check if user has permission to edit homework
function can_edit_assignment($username, $assignment_id, $assignment_config) {
    $data_path = get_data_path();
    date_default_timezone_set('America/New_York');
    $file = $data_path."/results/".$assignment_id."/".$username."/user_assignment_config.json";
    if (file_exists($file)) {
        $json = json_decode(file_get_contents($file), true);
        if (isset($json["due_date"]) && $json["due_date"] != "default") {
            $date = new DateTime($json["due_date"]);
            $now = new DateTime("NOW");
            return $now <= $date;
        }
        return; //TODO
    }
    $date = new DateTime($assignment_config["due_date"]);
    $now = new DateTime("NOW");
    return $now <= $date;
}

// Check to make sure instructor has added this assignment
function is_valid_assignment($class_config, $assignment_id) {
    $assignments = $class_config["assignments"];
    foreach ($assignments as $one) {
        if ($one["assignment_id"] == $assignment_id) {
            return true;
        }
    }
    return false;
}

// Make sure student has actually submitted this version of an assignment
function is_valid_assignment_version($username, $assignment_id, $assignment_version) {
    $data_path = get_data_path();
    $path = get_submissions_path().$assignment_id."/".$username."/".$assignment_version;
    return file_exists($path);
}


?>
