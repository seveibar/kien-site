<?php

// Get the test cases from the instructor configuration file
function get_assignment_config($username, $assignment_id) {
    $data_path = get_data_path();
    $file = $data_path."/results/".$assignment_id."/assignment_config.json";
    if (!file_exists($file)) {
        return false;//TODO Handle this case
    }
    return json_decode(file_get_contents($file), true);
}

// Get results from test cases for a student submission
function get_assignment_results($username, $assignment_id, $assignment_version) {
    $data_path = get_data_path();
    $file = $data_path."/results/".$assignment_id."/".$username."/".$assignment_version."/submission.json";
    if (!file_exists($file)) {
        return false;
    }
    return json_decode(file_get_contents($file), true);
}

?>
