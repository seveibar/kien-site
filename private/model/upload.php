<?php

// Upload HW Assignment to server and unzip
function upload_homework($username, $assignment_id, $homework_file) {
    $data_path = get_data_path();

    // Get config for class
    $class_config = get_class_config();

    // Make sure user is authenticated
    check_session($username);

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

    // Path to upload user assignment to
    $upload_path = get_submissions_path().$assignment_id."/".$username;

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
?>
