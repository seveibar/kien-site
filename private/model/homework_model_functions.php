<?php

//This will be changed to whatever exists in the above file
static $config = NULL;

require_once("io.php");
require_once("site_config.php");
require_once("class_config.php");
require_once("checks.php");
require_once("results.php");
require_once("diff_parser.php");
require_once("upload.php");


// Find most recent submission from user
function most_recent_assignment_version($username, $assignment_id) {
    $data_path = get_data_path();
    $path = get_submissions_path().$assignment_id."/".$username;
    $i = 1;
    while (file_exists($path."/".$i)) {
        $i++;
    }
    return $i - 1;

}

// Get name for assignment
function name_for_assignment_id($class_config, $assignment_id) {
    $assignments = $class_config["assignments"];
    foreach ($assignments as $one) {
        if ($one["assignment_id"] == $assignment_id) {
            return $one["assignment_name"];
        }
    }
    return "";//TODO Error handling
}

// Get TA grade for assignment
function TA_grade($username, $assignment_id) {
    //TODO
    return false;
}

function version_in_grading_queue($username, $assignment_id, $assignment_version) {
    $data_path = get_data_path();
    if (!is_valid_assignment_version($username, $assignment_id, $assignment_version)) {//If its not in the submissions folder
        return false;
    }
    $file = $data_path."/results/".$assignment_id."/".$username."/".$assignment_version;
    if (file_exists($file)) {//If the version has already been graded
        return false;
    }
    return true;
}

// Get the number representing the version of the code the user is submitting
function get_user_submitting_version($username, $assignment_id) {
    $file_path = get_submissions_path().$assignment_id."/".$username."/user_assignment_settings.json";
    return load_json($file_path)["selected_assignment"];
}

// Change the version of the assignment the user is submitting
function change_assignment_version($username, $assignment_id, $assignment_version, $assignment_config) {
    if (!can_edit_assignment($username, $assignment_id, $assignment_config)) {
        return array("error"=>"Error: This assignment ".$assignment_id." is not open.  You may not edit this assignment.");
    }
    if (!is_valid_assignment_version($username, $assignment_id, $assignment_version)) {
        return array("error"=>"This assignment version ".$assignment_version." does not exist");
    }
    $file_path = get_submissions_path().$assignment_id."/".$username."/user_assignment_settings.json";
    $json = load_json($file_path);
    $json["selected_assignment"] = $assignment_version;
    file_put_contents($file, json_encode($json));
    return array("success"=>"Success");
}
