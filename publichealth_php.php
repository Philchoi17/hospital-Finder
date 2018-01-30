<?php
/*
    This API is to provide hospital information (name, address, website, number of beds).

    It expects to be provided a province and optionally, minimum bed size. With this information, it will search the list of hospitals for suitable hospitals.

    Input is expected in JSON format, and responses will be provided in JSON format. There may be more than one object in the array, if there are multiple hospitals that match your input parameters.
    Example:
        INPUT: {"province": "강원도", "minBeds": 200 }
        OUTPUT:
        [
            {
                "name":강원도 영월의료원,
                "address":강원도 영월군 영월읍 중앙1로 59,
                "zip":26234,
                "website":http://www.youngwol.org,
                "phone":033-370-9117,
                "department":보건복지부(강원도),
                "numBeds":214
            }
        ]
*/

    $input_str = file_get_contents("php://input");  // Get input from stdin to get message body (JSON)

    // Log input for debugging purposes.
    date_default_timezone_set('Asia/Seoul');
    /*
    file_put_contents("publichealth.in.log"
        , date('m/d/Y H:i:s', time()) . " [{$_SERVER['REMOTE_ADDR']}]: " . $input_str . PHP_EOL
        , FILE_APPEND);
*/
    // Initialize input variables.
    $in_prov = "";
    $in_min_beds = 0;
    $in_name = "";

    // Try to set the variables with the input.
    if ($input_str !== "") {
        $json_obj = json_decode($input_str);
        if ($json_obj !== NULL) {
            $in_prov = $json_obj->province;
            if (isset($json_obj->minBeds) && is_int($json_obj->minBeds)) $in_min_beds = $json_obj->minBeds;
            if (isset($json_obj->name)) $in_name = $json_obj->name;
        }
    }

    $noresults = true;
    $json_str = "[";  // Start of our output, regardless of matches.

    if ($in_prov !== "") {
        $all_str = file_get_contents("Public health medical institutions -201612.csv");
        $all_lines_arr = explode(PHP_EOL, trim($all_str));

        // $matching_arr = [];
        for ($i = 0; $i < count($all_lines_arr); $i++) {
            $this_hospital_str = $all_lines_arr[$i];
            $this_hospital = explode(",", $this_hospital_str);

            $row_id = $this_hospital[0];
            $department = $this_hospital[4];
            $num_beds = $this_hospital[8];

            $pos_name_match = strpos($department, $in_prov);  // Find name in department.
            if ($in_name == "") 
            {
                $name_exist = 1;
            }
            else {
                $name_exist = strpos($this_hospital[1], $in_name);  // Find name in department.
            }

            
            if ($pos_name_match !== FALSE && $num_beds > $in_min_beds && $name_exist !== FALSE) {
                $noresults = false;
                $hospital_name = $this_hospital[1];
                $hospital_zip = $this_hospital[9];
                $hospital_addr = $this_hospital[10];
                $hospital_web = $this_hospital[11];
                $hospital_phone = $this_hospital[12];

                $json_str .= "{";
                $json_str .= "\"name\":\"{$hospital_name}\", ";
                $json_str .= "\"address\":\"{$hospital_addr}\", ";
                $json_str .= "\"zip\":\"{$hospital_zip}\", ";
                $json_str .= "\"website\":\"{$hospital_web}\", ";
                $json_str .= "\"phone\":\"{$hospital_phone}\", ";
                $json_str .= "\"department\":\"{$department}\", ";
                $json_str .= "\"numBeds\":\"{$num_beds}\"";
                $json_str .= "},";
            }
        }
        if (!$noresults) {
            $json_str = substr($json_str, 0, -1);  // Remove the last comma from the last item in the array.
        }
    }

    $json_str .= "]";  // End of output, regardless of matches.

    echo($json_str);
?>