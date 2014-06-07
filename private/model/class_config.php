<?php

// Json object with class configuration details (the class file)
static $course_config;

//Gets the class information for assignments
function get_class_config() {
    global $course_config;

    // If course config hasn't been loaded, load it
    if ($course_config == NULL){
        $course_config = load_json(get_data_path() . "/class.json");
    }

    return $course_config;
}

?>
