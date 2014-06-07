<?php

// Relative to public directory
static $site_config_path = "../config.json";

//This will be changed to whatever exists in the above file
static $config = NULL;

// Path to course data directory, loaded from config
static $data_path = NULL;

// Returns course data path, loads if necessary
function get_data_path() {
    global $data_path;
    global $site_config_path;
    global $config;

    // If config doesn't exist, load it
    if ($config == NULL) {
        if (!file_exists($site_config_path)) {
            error_out("$site_config_path does not exist. See setup guide");
        }

        $config = load_json($site_config_path);

        $data_path = $config["course_data_path"];

    }
    return $get_data_path;
}

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

// Upload HW Assignment to server and unzip
function upload_homework($username, $assignment_id, $homework_file) {
    $data_path = get_data_path();

    // Check user and assignment authenticity
    $class_config = get_class_config($username);
    if ($username !== $_SESSION["id"]) {//Validate the id
        echo "Something really got screwed up with usernames and session ids";
        return array("error"=>"Something really got screwed up with usernames and session ids");
    }
    if (!is_valid_assignment($class_config, $assignment_id)) {
        return array("error"=>"This assignment is not valid");
    }
    $assignment_config = get_assignment_config($username, $assignment_id);
    if (!can_edit_assignment($username, $assignment_id, $assignment_config)) {//Made sure the user can upload to this homework
        return array("error"=>"assignment_closed");
    }
    //VALIDATE HOMEWORK CAN BE UPLOADED HERE
    //ex: homework number, due date, late days

    $max_size = 50000;//CHANGE THIS TO GET VALUE FROM APPROPRIATE FILE
    $allowed = array("zip");
    $filename = explode(".", $homework_file["name"]);
    $extension = end($filename);

    $upload_path = $data_path."/submissions/".$assignment_id."/".$username;//Upload path

    // TODO should support more than zip (.tar.gz etc.)
    if (!($homework_file["type"] === "application/zip")) {//Make sure the file is a zip file
        echo "Incorrect file upload type.  Not a zip, got ".htmlspecialchars($homework_file["type"]);
        return array("error"=>"Incorrect file upload type.  Not a zip, got ".htmlspecialchars($homework_file["type"]));
    }

    // If user path doesn't exist, create new one

    if (!file_exists($upload_path)) {
        // TODO permission level is INCORRECT
        mkdir($upload_path, 0777, true);
    }

    //Find the next homework version number

    $i = 1;
    while (file_exists($upload_path."/".$i)) {
        //Replace with symlink?
        $i++;
    }

    // Attempt to create folder
    if (!mkdir($upload_path."/".$i, 0777, false)) {//Create a new directory corresponding to a new version number
        // TODO permission level is INCORRECT
        echo "Error, failed to make folder ".$upload_path."/".$i;
        return array("error"=>"failed to make folder ".$upload_path."/".$i);
    }
    // Unzip files in folder

    $zip = new ZipArchive;
    $res = $zip->open($homework_file["tmp_name"]);
    if ($res === TRUE) {
      $zip->extractTo($upload_path."/".$i."/");
      $zip->close();
    } else {
        "Error failed to move uploaded file from ".$homework_file["tmp_name"]." to ". $upload_path."/".$i."/".$homework_file["name"];
        return array ("error"=>"failed to move uploaded file from ".$homework_file["tmp_name"]." to ". $upload_path."/".$i."/".$homework_file["name"]);

    }
    $settings_file = $upload_path."/user_assignment_settings.json";
    if (!file_exists($settings_file)) {
        $json = array("selected_assignment"=>1);
        file_put_contents($settings_file, json_encode($json));
    }
    $to_be_compiled = $data_path."/submissions/to_be_compiled.txt";
    if (!file_exists($to_be_compiled)) {
        file_put_contents($to_be_compiled, $assignment_id."/".$username."/".$i."\n");
    } else {
        $text = file_get_contents($to_be_compiled, false);
        $text = $text.$assignment_id."/".$username."/".$i."\n";
        file_put_contents($to_be_compiled, $text);
    }
    return array("success"=>"File uploaded successfully");
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


//Gets the class information for assignments

function get_class_config($username) {
    $data_path = get_data_path();
    $file = $data_path."/results/class.json";
    if (!file_exists($file)) {
        ?><script>alert("Configuration for this class (class.JSON) does not exist.  Quitting");</script>
        <?php exit();
    }
    return json_decode(file_get_contents($file), true);
}


// Find most recent submission from user
function most_recent_assignment_version($username, $assignment_id) {
    $data_path = get_data_path();
    $path = $data_path."/submissions/".$assignment_id."/".$username;
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
    $path = $data_path."/submissions/".$assignment_id."/".$username."/".$assignment_version;
    return file_exists($path);
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


//RESULTS DATA

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



//SUBMITTING VERSION

function get_user_submitting_version($username, $assignment_id) {
    $data_path = get_data_path();
    $file = $data_path."/submissions/".$assignment_id."/".$username."/user_assignment_settings.json";
    if (!file_exists($file)) {
        return 0;
    }
    $json = json_decode(file_get_contents($file), true);
    return $json["selected_assignment"];
}

function change_assignment_version($username, $assignment_id, $assignment_version, $assignment_config) {
    if (!can_edit_assignment($username, $assignment_id, $assignment_config)) {
        return array("error"=>"Error: This assignment ".$assignment_id." is not open.  You may not edit this assignment.");
    }
    if (!is_valid_assignment_version($username, $assignment_id, $assignment_version)) {
        return array("error"=>"This assignment version ".$assignment_version." does not exist");
    }
    $data_path = get_data_path();
    $file = $data_path."/submissions/".$assignment_id."/".$username."/user_assignment_settings.json";
    if (!file_exists($file)) {
        return array("error"=>"Unable to find user settings");
    }
    $json = json_decode(file_get_contents($file), true);
    $json["selected_assignment"] = $assignment_version;
    file_put_contents($file, json_encode($json));
    return array("success"=>"Success");
}

//DIFF FUNCTIONS

// Converts the JSON "diff" field from submission.json to an array containing
// file contents
function get_testcase_diff($username, $assignment_id, $assignment_version, $diff){
    $data_path = get_data_path();

    if (!isset($diff["instructor_file"]) ||
        !isset($diff["student_file"]) ||
        !isset($diff["difference"])) {
        return "";
    }

    $instructor_file_path = "$data_path/".$diff["instructor_file"];
    $student_path = "$data_path/results/$assignment_id/$username/$assignment_version/";

    if (!file_exists($instructor_file_path) ||
        !file_exists($student_path . $diff["student_file"]) ||
        !file_exists($student_path . $diff["difference"])){
        return "";
    }

    $student_content = file_get_contents($student_path.$diff["student_file"]);
    $difference = file_get_contents($student_path.$diff["difference"]);
    $instructor_content = file_get_contents($instructor_file_path);

    return array(
            "student" => $student_content,
            "instructor"=> $instructor_content,
            "difference" => $difference
        );
}
