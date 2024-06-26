<?php
require_once "functions.loader.php";	
date_default_timezone_set("Asia/Manila");

use Base32\Base32;

$app = new Slim();

$app->get("/", function () {
	// echo sha1(base64_decode("123".salt()));
	// echo "</br>";
	// echo sha1(base64_decode("12".salt()));
	// echo "</br>";
	// echo sha1(base64_decode("23".salt()));
	// echo "</br>";


	// $response = array();	
	// $results = read_work_schedule();
	
	// foreach($results as $result) {
	// 	$response[] = array(
	// 		"uid" => $result->uid
	// 	);
	// }
	
	// echo jsonify($response);
});

$app->post("/employee/new/" , function(){
    //parameter: token
    $empUid       = xguid();
    $firstname    = utf8_decode($_POST['firstname']);
    $middlename   = utf8_decode($_POST['middlename']);
    $lastname     = utf8_decode($_POST['lastname']);
    $marital      = $_POST['marital'];
    $usertype     = $_POST['usertype'];
    $username     = $_POST['username'];
    $password     = $_POST['password'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $userId       = xguid();
    // $username  = $_POST['username'];
    $check        = checkUsername($username);
    if($check >= 1){
        $response = array(
            "error"        => 1,
            "errorMessage" => "USERNAME EXISTING!"
        );
    }else if($check == 0){
        //$password = sha1(Base32::decode($_POST['password']));
		$password = sha1(base64_decode($_POST['password'].salt()));
        $ivSize   = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv       = mcrypt_create_iv($ivSize, MCRYPT_RAND);

        newEmployee($empUid , $firstname , $middlename , $lastname, $marital, $usertype, $dateCreated , $dateModified);
        newUserAccount($userId , $username , $password , $usertype , $empUid , $dateCreated , $dateModified);
        newUserUniqueKey(xguid(), $userId, $iv , $dateCreated , $dateModified);

        $response = array(
            "error"        => 0,
            "errorMessage" => "SUCCESSFULLY CREATED!"
        );
    }
    echo jsonify($response);
});



$app->post("/employee/status/update/" , function(){
    //parameter: token
    $empUid       = $_POST['empUid'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");
    updateEmployeeStatus($empUid , $dateModified , $status);
});

$app->post("/employee/update/:var" , function($var){
    $param          = explode(".", $var);
    $token          = $param[1];
    
    $empUid         = $param[0];
    $firstname      = utf8_decode($_POST['firstname']);
    $middlename     = utf8_decode($_POST['middlename']);
    $lastname       = utf8_decode($_POST['lastname']);
    $gender         = $_POST['gender'];
    $marital        = $_POST['marital'];
    $nationality    = $_POST['nationality'];
    $bday           = $_POST['bday'];
    $email          = $_POST['email'];
    $nickname       = $_POST['nickname'];
    $driverLicense  = $_POST['driverLicense'];
    $expiryLicense  = $_POST['expiryLicense'];
    $sssNo          = $_POST['sssNo'];
    $taxNo          = $_POST['taxNo'];
    $philhealthNo   = $_POST['philhealthNo'];
    $pagibigNo      = $_POST['pagibigNo'];
    // $dateCreated = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");
    $status         = $_POST['status'];

    updateEmployee($empUid , $firstname , $middlename , $lastname , $gender , $marital , $nationality , $bday , $email , $nickname , $driverLicense , $expiryLicense , $sssNo , $taxNo , $philhealthNo , $pagibigNo , $dateModified , $status);   
});

$app->post("/employee/name/search/" , function(){
    //parameter: token
    $empUid     = xguid();
    $firstname  = utf8_decode($_POST['firstname']);
    $middlename = utf8_decode($_POST['middlename']);
    $lastname   = utf8_decode($_POST['lastname']);
    
    $employee   = getEmployeeByName($firstname , $middlename , $lastname);
    if($employee){
        $empUid = $employee->emp_uid;
        if($employee->status == 1){
            $status = 1;
        }else{
            $status = 0;
        }
    }else{
        $status = 2;
    }

    $response = array(
        "status" => $status,
        "empUid" => $empUid
    );

    echo jsonify($response);
});

$app->get("/attempts/data/", function(){
    $response = array();

    $attempts = getAttemptsData();
    foreach($attempts as $data){
        $response[] = array(
            "empNo"  => $data["username"],
            "emp"    => $data["lastname"] . ", " . $data["firstname"],
            "date"   => date("M d, Y", strtotime($data["sdate"])),
            "hours"  => date("h:i:s A", strtotime($data["stime"])),
            "log"    => $data["log"],
            "code"   => $data["location_code"],
            "device" => $data["device"],
            "ip"     => $data["ip_address"]
        );
    }
    echo jsonify($response);
});

$app->get("/employees/pages/get/:var" , function($var){
    $param     = explode(".", $var);
    $response  = array();
    $employees = getPaginatedEmployees();

    foreach ($employees as $employee) {
        $uid   = $employee->emp_uid;
        $rules = getRuleByEmpUid($uid);
        $costcenter = getSingleCostCenterDataByEmpUid($uid);
        $response[] = array(
            "empUid"     => $employee->emp_uid,
            "empNo"      => $employee->username,
            "firstname"  => utf8_decode($employee->firstname),
            "middlename" => utf8_decode($employee->middlename),
            "lastname"   => utf8_decode($employee->lastname),
            "nickname"   => $employee->nickname?$employee->nickname:"N/A",
            "gender"     => $employee->gender?$employee->gender:"N/A",
            "rule"       => $rules["rule_name"]?$rules["rule_name"]:"N/A",
            "costcenter"       => $costcenter["cost_center_name"]?$costcenter["cost_center_name"]:"N/A",
            "type"       => $employee->type?$employee->type:"N/A"            
        );
    }
    echo jsonify($response);
});

$app->get("/employee/data/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $token    = $param[1];
    $empUid   = $param[0];
    
    $employee = getEmployeeDetailsByUid($empUid);
    $username = getEmloyeeNumberByEmpUid($empUid);
    $type     = getEmployeeType($empUid);

    if($employee->nationality){
        $nationality = $employee->nationality;
    }else{
        $nationality = null;
    }
    $response = array(
        "empUid"        => $employee->emp_uid,
        "firstname"     => utf8_decode($employee->firstname),
        "middlename"    => utf8_decode($employee->middlename),
        "lastname"      => utf8_decode($employee->lastname),
        "gender"        => $employee->gender,
        "marital"       => $employee->marital,
        "nationality"   => $employee->nationality,
        "bday"          => $employee->bday,
        "nationality"   => $nationality,
        "bday"          => $employee->bday,
        "email"         => $employee->email,
        "nickname"      => $employee->nickname,
        "driverLicense" => $employee->drivers_license,
        "expiryLicense" => $employee->expiry_license,
        "sssNo"         => $employee->sss_no,
        "taxNo"         => $employee->tax_no,
        "philhealthNo"  => $employee->philhealth_no,
        "pagibigNo"     => $employee->pagibig_no,
        "empNumber"     => $username,
        "status"        => $employee->status,
        "type"          => $employee->type
    );
    echo jsonify($response);
});

$app->post("/update/emp/number/:uid", function($uid){
    $response     = array();
    $username     = $_POST["empNumber"];
    $check        = checkUsername($username);
    $dateModified = date("Y-m-d H:i:s");

    if($check >= 1){
        $response = array(
            "prompt" => 1,
            "num"    => $username
        );
    }else{
        updateEmpNumber($uid, $username, $dateModified);
        $response = array(
            "prompt" => 0,
            "num"    => $username
        );
    }

    echo jsonify($response);
});

$app->post("/update/emp/type/:uid", function($uid){
    $response     = array();
    $empType      = $_POST["empType"];
    $dateModified = date("Y-m-d H:i:s");

    updateEmployeeType($uid, $empType, $dateModified);
});

$app->get("/employee/nationality/get/" , function(){
    //parameter: token
    $response      = array();
    $nationalities = getNationalities();
    foreach ($nationalities as $nationality) {
        $response[] = array(
            "nationalityUid"  => $nationality->nationality_uid,
            "nationalityName" => $nationality->name
        );
    }

    echo jsonify($response);
});


$app->post("/employee/nationality/new/:var" , function($var){
    //parameter: token
    $nationalityUid  = xguid();
    $nationalityName = $_POST['nationality'];
    $dateCreated     = date("Y-m-d H:i:s");
    $dateModified    = date("Y-m-d H:i:s");
    $status          = 0;

    $newNationality = newNationality($nationalityUid , $nationalityName , $dateCreated , $dateModified);
    if($newNationality){
        $status         = 1;
        $nationality    = getNationalityByName($nationalityName);
        $nationalityUid = $nationality->nationality_uid;
    }

    $response = array(
        "nationalityUid"  => $nationalityUid,
        "nationalityName" => $nationalityName,
        "status"          => $status
    );

    echo jsonify($response);
});

$app->get("/employee/dependent/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param              = explode(".", $var);
    $token              = $param[1];
    $empUid             = $param[0];
    $response           = array();
    
    $employeeDependents = getPaginatedEmployeeDependent($empUid);

    foreach ($employeeDependents as $employeeDependent) {
        $response[] = array(
            "name"                 => $employeeDependent->name,
            "relationship"         => $employeeDependent->relationship,
            "bday"                 => date("M d, Y", strtotime($employeeDependent->bday)),
            "number"               => $employeeDependent->number,
            "employeeDependentUid" => $employeeDependent->emp_dependent_uid,
        );
    }
    echo jsonify($response);
});

$app->post("/employee/dependent/new/:var" , function($var){
    //parameter: term.start.size.token
    $param                    = explode(".", $var);
    
    $empUid                   = $param[0];
    $empDependentUid          = xguid();
    $newDependentName         = $_POST['dependentName'];
    $newDependentRelationship = $_POST['dependentRelationship'];
    $newDependentBday         = $_POST['dependentBday'];
    $newDependentNumber       = $_POST['dependentNumber'];
    $dateCreated              = date("Y-m-d H:i:s");
    $dateModified             = date("Y-m-d H:i:s");

    $newEmployeeDependent = newEmployeeDependent($empDependentUid,$empUid,$newDependentName,$newDependentRelationship,$newDependentNumber,$newDependentBday,$dateCreated,$dateModified);
});

$app->get("/employee/dependent/view/:var" , function($var){
    //parameter: term.start.size.token
    $param     = explode(".", $var);
    
    $empUid    = $param[0];
    $depUid    = $param[1];
    $token     = $param[2];
    $response  = array();
    $dependent = getEmployeeDependentByUid($depUid);
    if($dependent){
        $response = array(
            'name'         => $dependent->name, 
            'relationship' => $dependent->relationship, 
            'bday'         => $dependent->bday, 
            'number'       => $dependent->number,
            "status"       => $dependent->status
        );
    }
    echo jsonify($response);
});

$app->post("/employee/dependent/update/:var" , function($var){
    //parameter: term.start.size.token
    $param                 = explode(".", $var);
    $token                 = $param[2];
    $depUid                = $param[1];
    $empUid                = $param[0];
    
    $dependentName         = $_POST['dependentName'];
    $dependentRelationship = $_POST['dependentRelationship'];
    $dependentBday         = $_POST['dependentBday'];
    $dependentNumber       = $_POST['dependentNumber'];
    $status                = $_POST['status'];
    $dateModified          = date("Y-m-d H:i:s");
    
    $dependent             = getEmployeeDependentByUid($depUid);

    if($dependentName != $dependent->name OR $dependentRelationship != $dependent->relationship OR $dependentBday != $dependent->bday OR $dependentNumber != $dependent->number OR $status != $dependent->status){
        updateEmployeeDependentStatusById($depUid , $dependentName , $dependentRelationship ,  $dependentBday , $dependentNumber ,  $dateModified , $status);
        if(employeeDependentCount($empUid , $dependentName , $dependentRelationship , $dependentBday) > 1){
            updateEmployeeDependentStatusById($depUid , $dependent->name , $dependent->relationship ,  $dependent->bday , $dependent->number ,  $dateModified , $status);
        }
    }
});

$app->get("/employee/phone/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param          = explode(".", $var);
    $empUid         = $param[0];
    $response       = array();
    
    $employeePhones = getPaginatedEmployeePhone($empUid);
    if($employeePhones){
        foreach ($employeePhones as $employeePhone) {
            $response[] = array(
                "phoneType" => getPhoneTypeByUid($employeePhone->phonetype_uid),
                "number"    => $employeePhone->number,
                "phoneUid"  => $employeePhone->phone_uid
            );
        }
    }
    echo jsonify($response);
});

$app->post("/employee/phone/new/:var" , function($var){
    //parameter: term.start.size.token
    $param             = explode(".", $var);
    
    $empUid            = $param[0];
    $phoneUid          = xguid();
    $employeePhoneType = $_POST['employeePhoneType'];
    $employeePhone     = $_POST['employeePhone'];
    $dateCreated       = date("Y-m-d H:i:s");
    $dateModified      = date("Y-m-d H:i:s");
    
    $newEmployeePhone  = newEmployeePhone($phoneUid,$empUid,$employeePhoneType,$employeePhone,$dateCreated,$dateModified);
});

$app->get("/employee/phone/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param         = explode(".", $var);
    $empUid        = $param[0];
    $phoneUid      = $param[1];
    $response      = array();
    
    $employeePhone = getEmployeeContactDetails($phoneUid);
    if($employeePhone){
        $response = array(
            "phoneType"    => getPhoneTypeByUid($employeePhone->phonetype_uid),
            "phoneTypeUid" => $employeePhone->phonetype_uid,
            "number"       => $employeePhone->number,
            "status"       => $employeePhone->status
        );
    }
    echo jsonify($response);
});

$app->post("/employee/phone/status/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[2];
    
    $phoneUid     = $param[1];
    $empUid       = $param[0];
    $phoneType    = $_POST['employeePhoneType'];
    $phoneNumber  = $_POST['employeePhone'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");
    $phone        = getEmployeeContactDetails($phoneUid);
    if($phone->phonetype_uid != $phoneType OR $phone->number != $phone OR $phone->status != $status){
        updateEmployeePhoneById($phoneUid , $dateModified , $status , $phoneType , $phoneNumber);
        if(employeePhoneCount($empUid,$phoneNumber) > 1){
            updateEmployeePhoneById($phoneUid , $dateModified , $status, $phone->phonetype_uid , $phone->number);
        }
    }
});

$app->post("/employee/job/new/:var" , function($var){
    //parameter: empUid.token
    $param            = explode(".", $var);
    
    $empUid           = $param[0];
    $empJobUid        = xguid();
    $jobTitle         = $_POST['jobTitle'];
    $jobCategory      = $_POST['jobCategory'];
    $subunit          = $_POST['subunit'];
    $location         = $_POST['location'];
    $employmentStatus = $_POST['employmentStatus'];
    $startDate        = $_POST['startDate'];
    $endDate          = $_POST['endDate'];
    $dateCreated      = date("Y-m-d H:i:s");
    $dateModified     = date("Y-m-d H:i:s");
    
    $newEmployeeJob   = newEmployeeJob($empJobUid , $jobTitle , $jobCategory , $subunit , $location , $employmentStatus , $empUid , $startDate , $endDate , $dateCreated , $dateModified);
});

$app->get("/employee/job/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $empUid       = $param[0];
    $response     = array();
    
    $employeeJobs = getPaginatedEmployeeJob($empUid);
    
    foreach ($employeeJobs as $employeeJob) {
        $job      = getJobByUid($employeeJob->job_uid);
        $jobTitle = "";
        if($job){
            if($job->status == 1){
                $jobTitle =  $job->title;
            }
        }

        $jobCategory     = getJobCategoryByUid($employeeJob->job_category_uid);
        $jobCategoryName = "";
        if($jobCategory){
            if($jobCategory->status == 1){
                $jobCategoryName =  $jobCategory->name;
            }
        }

        $subunit     = getSubunitByUid($employeeJob->subunit_uid);
        $subunitName = "";
        if($subunit){
            if($subunit->status == 1){
                $subunitName =  $subunit->name;
            }
        }

        $location     = getLocationByUid($employeeJob->location_uid);
        $locationName = "";
        if($location){
            if($location->status == 1){
                $locationName =  $location->name;
            }
        }

        $employmentStatus     = getEmploymentStatusByUid($employeeJob->employment_status_uid);
        $employmentStatusName = "";
        if($employmentStatus){
            if($employmentStatus->status == 1){
                $employmentStatusName =  $employmentStatus->name;
            }
        }

        $response[] = array(
            "jobTitle"         => $jobTitle,
            "jobCategory"      => $jobCategoryName,
            "subunit"          => $subunitName,
            "location"         => $locationName,
            "employmentStatus" => $employmentStatusName,
            "employeeJobUid"   => $employeeJob->employee_job_uid
        );
    }
    echo jsonify($response);
});

$app->get("/employee/job/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param          = explode(".", $var);
    $token          = $param[2];
    $response       = array();
    $employeeJobUid = $param[1];
    $empUid         = $param[0];
    
    $employeeJob    = getEmployeeJobByUid($employeeJobUid);
    if($employeeJob){
        $job            = getJobByUid($employeeJob->job_uid);
        $jobTitle       = "";
        $jobDescription = "";
        if($job){
            if($job->status == 1){
                $jobTitle       =  $job->title;
                $jobDescription = $job->description;
            }
        }

        $jobCategory     = getJobCategoryByUid($employeeJob->job_category_uid);
        $jobCategoryName = "";
        if($jobCategory){
            if($jobCategory->status == 1){
                $jobCategoryName =  $jobCategory->name;
            }
        }

        $subunit     = getSubunitByUid($employeeJob->subunit_uid);
        $subunitName = "";
        if($subunit){
            if($subunit->status == 1){
                $subunitName =  $subunit->name;
            }
        }

        $location     = getLocationByUid($employeeJob->location_uid);
        $locationName = "";
        if($location){
            if($location->status == 1){
                $locationName =  $location->name;
            }
        }

        $employmentStatus     = getEmploymentStatusByUid($employeeJob->employment_status_uid);
        $employmentStatusName = "";
        if($employmentStatus){
            if($employmentStatus->status == 1){
                $employmentStatusName =  $employmentStatus->name;
            }
        }

        $response = array(
            "jobUid"              => $employeeJob->job_uid,
            "jobCategoryUid"      => $employeeJob->job_category_uid,
            "subunitUid"          => $employeeJob->subunit_uid,
            "locationUid"         => $employeeJob->location_uid,
            "employmentStatusUid" => $employeeJob->employment_status_uid,
            "jobTitle"            => $jobTitle,
            "jobDescription"      => $jobDescription,
            "jobCategory"         => $jobCategoryName,
            "subunit"             => $subunitName,
            "location"            => $locationName,
            "employmentStatus"    => $employmentStatusName,
            "startDate"           => $employeeJob->start_date,
            "endDate"             => $employeeJob->end_date,
            "dateExtended"        => $employeeJob->date_extended,
            "status"              => $employeeJob->status
        );
    }
    echo jsonify($response);
});

$app->post("/employee/job/update/:var" , function($var){
    //parameter: term.start.size.token
    $param            = explode(".", $var);
    $token            = $param[2];
    
    $empUid           = $param[0];
    $empJobUid        = $param[1];
    $jobTitle         = $_POST['jobTitle'];
    $jobCategory      = $_POST['jobCategory'];
    $subunit          = $_POST['subunit'];
    $location         = $_POST['location'];
    $employmentStatus = $_POST['employmentStatus'];
    $startDate        = $_POST['startDate'];
    $endDate          = $_POST['endDate'];
    $dateExtended     = $_POST['dateExtend'];
    $dateModified     = date("Y-m-d H:i:s");
    $status           = $_POST['status'];
    $employeeJob      = getEmployeeJobByUid($empJobUid);

    if($jobTitle != $employeeJob->job_uid OR $jobCategory != $employeeJob->job_category_uid OR $subunit != $employeeJob->subunit_uid OR $location != $employeeJob->location_uid OR $employmentStatus != $employeeJob->employment_status_uid OR $startDate != $employeeJob->start_date OR $endDate != $employeeJob->end_date OR $dateExtended != $employeeJob->date_extended OR $status != $employeeJob->status){
        updateEmployeeJobById($empJobUid , $jobTitle , $jobCategory , $subunit , $location , $employmentStatus , $startDate , $endDate , $dateExtended , $dateModified , $status);
    }
});

$app->post("/employee/job/status/update/:var" , function($var){
    //parameter: term.start.size.token
    $param          = explode(".", $var);
    $token          = $param[1];
    
    $employeeJobUid = $param[0];
    $dateModified   = date("Y-m-d H:i:s");
    $status         = $_POST['status'];

    updateEmployeeJobStatusByUid($employeeJobUid,$dateModified,$status);
});


/*---------------------------------employee end--------------------------------------*/


/*---------------------------------Phone Type--------------------------------------*/

$app->get("/phone/type/get/:var" , function($var){
    //parameter: token
    $phoneTypes = getPhoneTypes();
    $response   = array();
    if($phoneTypes){
        foreach ($phoneTypes as $phoneType) {
            $response[] = array(
                "phoneTypeUid" => $phoneType->phonetype_uid,
                "phoneType"    => $phoneType->phone_type,
            );
        }
    }
    echo jsonify($response);
});

$app->post("/phone/type/new/" , function(){
    //parameter: token
    $phoneTypeUid = xguid();
    $phoneType    = $_POST['newPhoneType'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    $status       = 0;
    
    $newPhoneType = newPhoneType($phoneTypeUid,$phoneType,$dateCreated,$dateModified);
    if($newPhoneType){
        $status       = 1;
        $pt           = getPhoneTypeByType($phoneType);
        $phoneTypeUid = $pt->phonetype_uid;
    }

    $response = array(
        "phoneTypeUid" => $phoneTypeUid,
        "phoneType"    => $phoneType,
        "status"       => $status
    );

    echo jsonify($response);
});

/*---------------------------------Phone Type ENd--------------------------------------*/

/*---------------------------------Nationalities--------------------------------------*/

$app->get("/nationalities/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param         = explode(".", $var);
    $response      = array();
    
    $nationalities = getPaginatedNationalities();

    foreach ($nationalities as $nationality) {
        $response[] = array(
            "name"           => $nationality->name,
            "nationalityUid" => $nationality->nationality_uid
        );
    }
    echo jsonify($response);
});


$app->get("/nationality/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param       = explode(".", $var);
    $token       = $param[1];
    
    $uid         = $param[0];
    $response    = array();
    $nationality = getNationalityByUid($uid);
    if($nationality){
        $response = array(
            "nationality" => $nationality->name,
            "status"      => $nationality->status
        );
    }

    echo jsonify($response);
});

$app->post("/nationality/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $uid          = $param[0];
    
    $name         = $_POST['nationality'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");
    $nationality  = getNationalityByUid($uid);

    if($nationality->name != $name OR $nationality->status != $status){
        updateNationality($uid , $name , $dateModified , $status);
        if(nationalityCount($name) > 1){
            updateNationality($uid , $nationality->name , $dateModified , $nationality->status);
        }
    }
});
/*--------------------------------- PAYROLL --------------------------------------*/

$app->post("/reports/print/:var", function($var){
    // parameters: html.token
    $param   = explode(".", $var);
    $token   = $param[0];
    $title   = $_POST["title"];
    // $html = Base32::decode($_POST["html"]);
    $html    = $_POST["html"];

    // echo $html;
    echo $title;
    $dompdf = new DOMPDF();
    $dompdf->set_paper("a4", "landscape");
    $dompdf->load_html($html);
    $dompdf->render();
    $dompdf->stream($title . ".pdf", array('Attachment' => 1));
});

$app->get("/reports/payroll/monthly/all/:var" , function($var){
    //parameter: token
    $param     = explode(".", $var);
    $response  = array();
    $employees = getActiveEmployees();
    foreach ($employees as $employee) {
        $response[] = array(
            "name"   => utf8_decode($employee->firstname) . " " . utf8_decode($employee.lastname),
            "salary" => $employee->base_salary
        );
    }
});

$app->get("/reports/payslip/:var" , function($var){
    //parameter: token
    $param     = explode(".", $var);
    $employee  = $_POST["employeeId"];
    $response  = array();
    $employees = getActiveEmployees();
    foreach ($employees as $employee) {
        $response[] = array(
            "name"   => $employee->firstname . " " . $employee.lastname,
            "salary" => $employee->base_salary
        );
    }
});

//harvey

$app->get("/generate/timesheet/:var",function($var){
    $param = explode(".", $var);
    $sheet = getEmpTimesheet($param[0],$param[1],$param[2]);
    echo jsonify($sheet);

});

$app->get("/reports/payroll/export/employees/:var", function($var){
    $param      = explode(".", $var);
    $startDate  = $param[0];
    $endDate    = $param[1];
    $costss     = $param[2];
    
    $costcenter = getEmployeeByCostCenterUid($costss);
    foreach($costcenter as $cost){
        $emp = $cost["emp_uid"];
        $x   = getTaxByCostCenter($startDate, $endDate, $emp, $costss);

        if($x){
            $response[] = array(
                "emp"             => $x["id"],
                "name"            => $x["name"],
                "empNo"           => $x["empNo"],
                "daySalary"       => $x["daySalary"],
                "hourlySalary"    => $x["hourlySalary"],
                "minutesSalary"   => $x["minutesSalary"],
                "overtimeSalary"  => number_format($x["overtimeSalary"], 2),
                "allowance"       => $x["allowance"],
                "basicSalary"     => $x["basicSalary"],
                "adjustment"      => $x["adjustment"],
                "days"            => $x["days"],
                "cutoffSalary"    => $x["cutoffSalary"],
                "grossSalary"     => $x["grossSalary"],
                "tardySalary"     => $x["tardySalary"],
                "totalSss"        => $x["totalSss"],
                "sssEmployee"     => $x["sssEmployee"],
                "sssEmployer"     => $x["sssEmployer"],
                "totalPhilhealth" => $x["totalPhilhealth"],
                "philEmployee"    => $x["philEmployee"],
                "philEmployer"    => $x["philEmployer"],
                "pagibig"         => $x["pagibig"],
                "totalContri"     => $x["totalContri"],
                "holidayPay"      => $x["holidayPay"],
                "netPay"          => $x["netPay"],
                "tax"             => $x["tax"],
                "loans"           => 0,
                "pettyCash"       => 0
            );
        }//end of checking if employee has income details
        // echo jsonify($x);

    }//end of getting employess by costcenter

    echo jsonify($response);
});

$app->post("/reports/payroll/get/", function(){
    getEmployeeSalary();
    
});

/*---------------------------------PAYROLL END--------------------------------------*/
/*---------------------------------job--------------------------------------*/

$app->post("/job/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $jobUid       = xguid();
    $title        = $_POST['title'];
    $description  = $_POST['description'];
    $note         = $_POST['note'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $newJob       = newJob($jobUid , $title , $description , $note , $dateCreated , $dateModified);
    $response = array(
        "status" => $newJob
    );
    echo jsonify($response);
});

$app->get("/job/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $response = array();
    
    $jobs     = getPaginatedJobs();

    foreach ($jobs as $job) {
        $response[] = array(
            "title"       => $job->title,
            "description" => $job->description,
            "jobUid"      => $job->job_uid
        );
    }
    echo jsonify($response);
});

$app->get("/jobs/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $response = array();
    
    $jobs     = getJobs();
    foreach ($jobs as $job) {
        $response[] = array(
            "title"       => $job->title,
            "description" => $job->description,
            "jobUid"      => $job->job_uid
        );
    }
    echo jsonify($response);
});

$app->get("/job/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param  = explode(".", $var);
    $token  = $param[1];
    $jobUid = $param[0];
    
    $job    = getJobByUid($jobUid);
    if($job){
        $response = array(
            "title"       => $job->title,
            "description" => $job->description,
            "note"        => $job->note,
            "status"      => $job->status
        );
    }else{
        $response = array(
            "title"       => "",
            "description" => "",
            "note"        => "",
            "status"      => ""
        );
    }

    echo jsonify($response);
});

$app->post("/job/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $jobUid       = $param[0];
    $title        = $_POST['title'];
    $description  = $_POST['description'];
    $note         = $_POST['note'];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];
    
    $job          = getJobByUid($jobUid);

    if($title != $job->title OR $description != $job->description OR $note != $job->note OR $status != $job->status){
        updateJobById($jobUid , $title , $description , $note , $dateModified , $status);
        if(jobCount($title)>1){
            updateJobById($jobUid , $job->title , $job->description , $job->note , $dateModified , $status);
        }
    }
});


/*---------------------------------job end--------------------------------------*/


/*---------------------------------employment Status--------------------------------------*/
$app->get("/employment/status/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $response = array();

    $employmentStatus = getEmploymentStatus();
    foreach ($employmentStatus as $es) {
        $response[] = array(
            "name"                => $es->name,
            "employmentStatusUid" => $es->employment_status_uid
        );
    }
    echo jsonify($response);
});

$app->get("/employment/status/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $response = array();

    $employmentStatus = getPaginatedEmploymentStatus();
    
    foreach ($employmentStatus as $es) {
        $response[] = array(
            "employmentStatusUid" => $es->employment_status_uid,
            "name"                => $es->name
        );
    }
    echo jsonify($response);
});

$app->post("/employment/status/new/:var" , function($var){
    //parameter: term.start.size.token
    $param               = explode(".", $var);
    $token               = $param[0];
    
    $employmentStatusUid = xguid();
    $name                = $_POST['name'];
    $dateCreated         = date("Y-m-d H:i:s");
    $dateModified        = date("Y-m-d H:i:s");
    
    $newEmploymentStatus = newEmploymentStatus($employmentStatusUid , $name , $dateCreated , $dateModified);
    $response = array(
        "status" => $newEmploymentStatus
    );
    echo jsonify($response);
});

$app->post("/employment/status/update/:var" , function($var){
    //parameter: term.start.size.token
    $param               = explode(".", $var);
    $token               = $param[1];
    
    $employmentStatusUid = $param[0];
    $name                = $_POST['name'];
    $dateModified        = date("Y-m-d H:i:s");
    $status              = $_POST['status'];
    
    $employmentStatus    = getEmploymentStatusByUid($employmentStatusUid);

    if($name != $employmentStatus->name OR $status != $employmentStatus->status){
        updateEmploymentStatusById($employmentStatusUid , $name , $dateModified , $status);
        if(employmentStatusCount($name)>1){
            updateEmploymentStatusById($employmentStatusUid , $employmentStatus->name , $dateModified , $status);
        }
    }
});

$app->get("/employment/status/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param               = explode(".", $var);
    $token               = $param[1];
    
    $employmentStatusUid = $param[0];
    
    $employmentStatus    = getEmploymentStatusByUid($employmentStatusUid);
    if($employmentStatus){
        $response = array(
            "name"   => $employmentStatus->name,
            "status" => $employmentStatus->status
        );
    }else{
        $response = array(
            "name"   => "",
            "status" => ""
        );
    }

    echo jsonify($response);
});

$app->get("/get/emp/employment/status/:uid", function($uid){
    $response = array();
    $check = checkEmploymentStatusByEmpUidPages($uid);
    $datas    = getEmploymentStatusByEmpUidPages($uid);

    $response = array();
    if($check >= 1){
        foreach($datas as $data){
            $response[] = array(
                "type"         => $data["name"],
                "empStatusUid" => $data["type_uid"],
                "statusUid"    => $data["employment_status_uid"],
                "dateHired"    => date("M d, Y", strtotime($data["date_hired"])),
                "dateResigned" => $data["date_resigned"]
            );
        }
    }
    echo jsonify($response);
});

$app->get("/get/single/emp/employment/status/:uid", function($uid){
    $response = array();
    $data     = getEmploymentStatusByStatusUid($uid);
    
    if($data){
        $employmentData = getEmploymentStatusByUid($data["employment_status_uid"]);
        $response = array(
            "type"         => $data["name"],
            "empStatusUid" => $data["type_uid"],
            "statusUid"    => $data["employment_status_uid"],
            "dateHired"    => $data["date_hired"],
            "dateResigned" => $data["date_resigned"],
            "status"       => $data["status"]
        );
    }
    echo jsonify($response);
});

$app->get("/check/emp/employment/status/:id", function($id){
    $response = array();
    $check    = checkUserEmploymentStatus($id);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        $response = array(
            "prompt" => 0
        );
    }

    echo jsonify($response);
});

$app->post("/set/emp/employment/status/:uid", function($uid){
    $empStatusUid = xguid();
    $empStatus    = $_POST["empStatus"];
    $datehired    = $_POST["datehired"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    $check = checkUserEmploymentStatus($uid);

    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        setEmpEmploymentStatus($empStatusUid, $uid, $empStatus, $datehired, $dateCreated, $dateModified);
        $response = array(
            "prompt" => 0
        );
    }
    echo jsonify($response);
});

$app->post("/update/emp/employment/status/:uid", function($uid){
    $employeeStatus = $_POST["employeeStatus"];
    $dateHired      = $_POST["dateHired"];
    $dateResigned   = $_POST["dateResigned"];
    $status         = $_POST["status"];
    $dateModified   = date("Y-m-d H:i:s");

    if($dateResigned == ""){
        $dateResigned = "0000-00-00";
    }else{
        $dateResigned = $dateResigned;
    }

    updateEmpEmployeeStatus($uid, $employeeStatus, $dateHired, $dateResigned, $dateModified, $status);
});
/*---------------------------------employment Status end--------------------------------------*/

/*------------------------------------Job Category----------------------------------------------*/
$app->get("/job/categories/get/:var" , function($var){
    //parameter: term.start.size.token
    $param         = explode(".", $var);
    $response      = array();
    
    $jobCategories = getJobCategories();
    foreach ($jobCategories as $jobCategory) {
        $response[] = array(
            "jobCategoryUid" => $jobCategory->job_category_uid,
            "name"           => $jobCategory->name
        );
    }
    echo jsonify($response);
});

$app->get("/job/category/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param         = explode(".", $var);
    $response      = array();
    
    $jobCategories = getPaginatedJobCategory();

    foreach ($jobCategories as $jobCategory) {
        $response[] = array(
            "jobCategoryUid" => $jobCategory->job_category_uid,
            "name"           => $jobCategory->name
        );
    }
    echo jsonify($response);
});

$app->post("/job/category/new/:var" , function($var){
    //parameter: term.start.size.token
    $param          = explode(".", $var);
    $token          = $param[0];
    
    $jobCategoryUid = xguid();
    $name           = $_POST['name'];
    $dateCreated    = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");
    
    $newJobCategory = newJobCategory($jobCategoryUid , $name , $dateCreated , $dateModified);
    $response = array(
        "status" => $newJobCategory
    );
    echo jsonify($response);
});

$app->post("/job/category/update/:var" , function($var){
    //parameter: term.start.size.token
    $param          = explode(".", $var);
    $token          = $param[1];
    
    $jobCategoryUid = $param[0];
    $name           = $_POST['name'];
    $dateModified   = date("Y-m-d H:i:s");
    $status         = $_POST['status'];
    $jobCategory    = getJobCategoryByUid($jobCategoryUid);

    if($name != $jobCategory->name OR $status != $jobCategory->status){
        updateJobCategoryById($jobCategoryUid , $name , $dateModified , $status);
        if(jobCategoryCount($name)>1){
            updateJobCategoryById($employmentStatusUid , $employmentStatus->name , $dateModified , $status);
        }
    }
});

$app->get("/job/category/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param          = explode(".", $var);
    $token          = $param[1];
    
    $jobCategoryUid = $param[0];
    
    $jobCategory    = getJobCategoryByUid($jobCategoryUid);
    if($jobCategory){
        $response = array(
            "name"   => $jobCategory->name,
            "status" => $jobCategory->status
        );
    }else{
        $response = array(
            "name"   => "",
            "status" => ""
        );
    }

    echo jsonify($response);
});

/*-----------------------------------Job Category End------------------------------------------*/


/*------------------------------------Country----------------------------------------------*/

$app->get("/countries/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param     = explode(".", $var);
    $term      = $param[0];
    $response  = array();
    
    $countries = getPaginatedCountries();

    foreach ($countries as $country) {
        $response[] = array(
            "code"       => $country->code,
            "name"       => $country->name,
            "iso"        => $country->iso,
            "numCode"    => $country->num_code,
            "countryUid" => $country->country_uid
        );
    }
    echo jsonify($response);
});


$app->get("/countries/get/:var" , function($var){
    //parameter: term.start.size.token
    $param     = explode(".", $var);
    $response  = array();
    
    $countries = getCountries();
    // sort($countries);
    foreach ($countries as $country) {
        $response[] = array(
            "code"       => $country->code,
            "name"       => $country->name,
            "iso"        => $country->iso,
            "numCode"    => $country->num_code,
            "countryUid" => $country->country_uid
        );
    }
    echo jsonify($response);
});

$app->post("/country/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $countryUid   = xguid();
    $code         = $_POST['code'];
    $name         = $_POST['name'];
    $iso          = $_POST['iso'];
    $numCode      = $_POST['numCode'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $newCountry   = newCountry($countryUid , $code , $name , $iso , $numCode , $dateCreated , $dateModified);
    $response     = array(
        "status" => $newCountry
    );
    echo jsonify($response);
});

$app->post("/country/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $countryUid   = $param[0];
    $code         = $_POST['code'];
    $name         = $_POST['name'];
    $iso          = $_POST['iso'];
    $numCode      = $_POST['numCode'];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];
    $country      = getCountryByUid($countryUid);

    if($code != $country->code OR $name != $country->name OR $iso != $country->iso OR $numCode != $country->num_code OR $status != $country->status){
        updateCountryById($countryUid ,  $code , $name , $iso , $numCode , $dateModified , $status);
        if(countriesCount($name)>1){
            updateCountryById($countryUid , $country->code , $country->name , $country->iso , $country->num_code , $dateModified , $status);
        }
    }
});

$app->get("/country/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param      = explode(".", $var);
    $token      = $param[1];
    
    $countryUid = $param[0];
    
    $country    = getCountryByUid($countryUid);
    if($country){
        $response = array(
            "code"    => $country->code,
            "name"    => $country->name,
            "iso"     => $country->iso,
            "numCode" => $country->num_code,
            "status"  => $country->status
        );
    }else{
        $response = array(
            "code"    => "",
            "name"    => "",
            "iso"     => "",
            "numCode" => "",
            "status"  => ""
        );
    }

    echo jsonify($response);
});

/*-----------------------------------Country End------------------------------------------*/

/*-----------------------------------General Information------------------------------------------*/   

$app->get("/general/information/details/:var" , function($var){
    //parameter: term.start.size.token
    $param              = explode(".", $var);
    $token              = $param[0];
    
    $response           = array();
    $generalInformation = getGeneralInformation();
    if($generalInformation){
        $response = array(
            "organizationName"   => $generalInformation->name,
            "taxId"              => $generalInformation->tax_id,
            "registrationNumber" => $generalInformation->registration_number,
            "phone"              => $generalInformation->phone,
            "fax"                => $generalInformation->fax,
            "email"              => $generalInformation->email,
            "country"            => $generalInformation->country,
            "state"              => $generalInformation->province,
            "city"               => $generalInformation->city,
            "zipCode"            => $generalInformation->zip_code,
            "address1"           => $generalInformation->street_1,
            "address2"           => $generalInformation->street_2,
            "note"               => $generalInformation->note,
            "numberOfEmployees"  => getEmployeesCount()
        );
    }

    echo jsonify($response);
});

$app->post("/general/information/update/:var" , function($var){
    //parameter: term.start.size.token
    $param              = explode(".", $var);
    $token              = $param[0];
    
    $organizationName   = $_POST['organizationName'];
    $taxId              = $_POST['taxId'];
    $registrationNumber = $_POST['registrationNumber'];
    $phone              = $_POST['phone'];
    $fax                = $_POST['fax'];
    $email              = $_POST['email'];
    $address1           = $_POST['address1'];
    $address2           = $_POST['address2'];
    $city               = $_POST['city'];
    $state              = $_POST['state'];
    $zipCode            = $_POST['zipCode'];
    $country            = $_POST['country'];
    $note               = $_POST['note'];
    $dateCreated        = date("Y-m-d H:i:s");
    $dateModified       = date("Y-m-d H:i:s");
    
    $generalInformation = getGeneralInformation();
    if(!generalInformationIsExisting()){
        $generalInformationUid = xguid();
        $subUnitUid            = xguid();
        newGeneralInformation($generalInformationUid , $organizationName , $taxId , $registrationNumber , $phone , $fax , $email , $address1 , $address2 , $city , $state , $zipCode , $country , $note , $dateCreated , $dateModified);

        insertSubunit($subUnitUid , "" , $organizationName , "" , "" , 0 , $dateCreated , $dateModified);
    }else{
        $generalInformationUid = $generalInformation->gen_info_uid;
        updateGeneralInformation($generalInformationUid , $organizationName , $taxId , $registrationNumber , $phone , $fax , $email , $address1 , $address2 , $city , $state , $zipCode , $country , $note , $dateModified);

        $subunit = getSubunitByName($organizationName);


        updateSubunitById($subunit->subunit_uid , $organizationName , $subunit->unit_id , $subunit->description , $subunit->lft , $subunit->lgt , $subunit->parent , $dateModified , $subunit->status);
    }
});

/*-----------------------------------General Information End------------------------------------------*/

/*-----------------------------------Location------------------------------------------*/ 

$app->get("/locations/get/:var" , function($var){
    //parameter: term.start.size.token
    $param     = explode(".", $var);
    $token     = $param[0];
    
    $locations = getLocations();
    foreach ($locations as $location) {
        $response[] = array(
            "locationUid" => $location->location_uid,
            "name"        => $location->name
        );
    }
    echo jsonify($response);
});  

$app->get("/location/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param     = explode(".", $var);
    $response  = array();
    $locations = getPaginatedLocation();
    
    foreach ($locations as $location) {
        $response[] = array(
            "name"        => $location->name,
            "city"        => $location->city,
            "country"     => getCountryByUid($location->country_uid)->name,
            "phone"       => $location->phone,
            //kulang
            "locationUid" => $location->location_uid
        );
    }
    echo jsonify($response);
});

$app->post("/location/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $locationUid  = xguid();
    $name         = $_POST['name'];
    $country      = $_POST['country'];
    $province     = $_POST['province'];
    $city         = $_POST['city'];
    $address      = $_POST['address'];
    $zipCode      = $_POST['zipCode'];
    $phone        = $_POST['phone'];
    $tax          = $_POST['tax'];
    $fax          = $_POST['fax'];
    $notes        = $_POST['notes'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newLocation($locationUid , $name , $country , $province , $city , $address , $zipCode , $phone , $tax , $fax , $notes , $dateCreated , $dateModified);
});

$app->get("/location/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param       = explode(".", $var);
    $token       = $param[1];
    $locationUid = $param[0];
    $location    = getLocationByUid($locationUid);
    if($location){
        $country = getCountryByUid($location->country_uid);
        if($country){
            $countryName = $country->name;
        }else{
            $countryName = "";
        }

        $response = array(
            "name"       => $location->name,
            "country"    => $countryName,
            "countryUid" => $location->country_uid,
            "province"   => $location->province,
            "city"       => $location->city,
            "address"    => $location->address,
            "zipCode"    => $location->zip_code,
            "phone"      => $location->phone,
            "tax"        => $location->tax,
            "fax"        => $location->fax,
            "notes"      => $location->notes,
            "status"     => $location->status 
        );
    }else{
        $response = array(
            "name"       => "",
            "country"    => "",
            "countryUid" => "",
            "province"   => "",
            "city"       => "",
            "address"    => "",
            "zipCode"    => "",
            "phone"      => "",
            "tax"        => "",
            "fax"        => "",
            "notes"      => "",
            "status"     => ""
        );
    }

    echo jsonify($response);
});

$app->post("/location/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $locationUid  = $param[0];
    $name         = $_POST['name'];
    $country      = $_POST['country'];
    $province     = $_POST['province'];
    $city         = $_POST['city'];
    $address      = $_POST['address'];
    $zipCode      = $_POST['zipCode'];
    $phone        = $_POST['phone'];
    $tax          = $_POST['tax'];
    $fax          = $_POST['fax'];
    $notes        = $_POST['notes'];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];
    
    $location     = getLocationByUid($locationUid);

    if($name != $location->name OR $country != $location->country_uid OR $province != $location->province OR $city != $location->city OR $address != $location->address OR $zipCode != $location->zip_code OR $phone != $location->phone OR $tax != $location->tax OR $fax != $location->fax OR $notes != $location->notes OR $status != $location->status){
        updateLocationById($locationUid , $name , $country , $province , $city , $address , $zipCode , $phone , $tax , $fax , $notes , $dateModified , $status);
        if(locationCount($name)>1){
            updateLocationById($locationUid , $location->name , $location->country , $location->province , $location->city , $location->address , $location->zip_code , $location->phone , $location->tax , $location->fax , $location->notes , $dateModified , $status);
        }
    }
});

$app->post("/location/status/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $locationUid  = $param[0];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];

    updateLocationStatusByUid($locationUid,$dateModified,$status);
});

/*-----------------------------------Location End------------------------------------------*/

/*-----------------------------------Structure------------------------------------------*/

$app->get("/structure/subunits/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $token    = $param[0];
    
    $subunits = getSubunits();
    foreach ($subunits as $subunit) {
        $response[] = array(
            "subunitUid" => $subunit->subunit_uid,
            "name"       => $subunit->name
        );
    }
    echo jsonify($response);
});

$app->get("/subunits/get/:var" , function($var){
    $param    = explode(".", $var);
    $token    = $param[0];
    $response = array();
    $subunits = displayChildren("" , 0);

    usort($subunits, function($a, $b) {
        return $a['lft'] - $b['lft'];
    });
    echo jsonify($subunits);
});

$app->get("/subunit/get/:var" , function($var){
    $param   = explode(".", $var);
    $token   = $param[1];
    
    $uid     = $param[0];
    $subunit = getSubunit($uid);

    $response = array(
        "name"        => $subunit->name,
        "unitId"      => $subunit->unit_id,
        "description" => $subunit->description,
        "status"      => $subunit->status,
    );

    echo jsonify($response);
});

$app->post("/subunit/new/:var" , function($var){
    $name        = $_POST['name'];
    $unitId      = $_POST['unitId'];
    $description = $_POST['description'];
    
    $subunit     = getSubunit($var);
    $subunitUid  = xguid();
    $dateCreated = date("Y-m-d H:i:s");
    $parent      = $subunit->name;
    $getlft      = $subunit->rgt - 1;

    if(!subUnitIsExisting($name)){
        updateRgtSubunit($getlft);
        updateLftSubunit($getlft);
        $status = insertSubunit($subunitUid , $parent , $name , $unitId , $description , $getlft , $dateCreated , $dateCreated);
    }else{
        $su = getSubunitByName($name);
        if($su->status != 1){
            updateRgtSubunit($getlft);
            updateLftSubunit($getlft);
            $lft = $getlft + 1;
            $rgt = $getlft + 2;
            updateSubunitById($su->subunit_uid , $name , $unitId , $description , $lft , $rgt , $parent , $dateCreated , 1);
        }
    }
    
    rebuildTree(getSubunitsMain(), 1);
});

$app->post("/subunit/edit/:var" , function($var){
    $name         = $_POST['name'];
    $unitId       = $_POST['unitId'];
    $description  = $_POST['description'];
    $status       = $_POST['status'];
    
    $subunit      = getSubunit($var);
    
    $subunitUid   = $var;
    $parent       = $subunit->parent;
    $rgt          = $subunit->rgt;
    $lft          = $subunit->lft;
    
    $dateModified = date("Y-m-d H:i:s");
    if($subunit->name != $name OR $subunit->unit_id != $unitId OR $subunit->description != $description OR $subunit->status != $status){
        if($status == 0){
            deleteSubunitBetweenLftAndRgt($lft , $rgt , $dateModified , $status);
            $rgt = "null";
            $lft = "null";
        }else{
            updateSubunitById($subunitUid , $name , $unitId , $description , $lft , $rgt , $parent , $dateModified , $status);
            if(subUnitCount($name)>1){
                updateSubunitById($subunitUid , $subunit->name , $subunit->unit_id , $subunit->description , $lft , $rgt , $parent , $dateModified , $status);
            }
        }
    }

    rebuildTree(getSubunitsMain(), 1);
});
/*-----------------------------------Structure END------------------------------------------*/

/*-----------------------------------working Experience------------------------------------------*/

$app->post("/work/experience/update/:var" , function($var){

    $param             = explode(".", $var);
    
    $workExperienceUid = $param[0];
    $employer          = $_POST['employerWEx'];
    $jobTitle          = $_POST['jobTitleWEx'];
    $from              = $_POST['fromWEx'];
    $to                = $_POST['toWEx'];
    $dateModified      = date("Y-m-d H:i:s");
    $status            = $_POST['status'];

    updateWorkExperience($employer , $jobTitle , $from , $to , $dateModified , $status , $workExperienceUid);
});


$app->get("/work/experience/details/get/:var" , function($var){

    $param             = explode(".", $var);
    $workExperienceUid = $param[0];
    $token             = $param[1];
    $response          = array();
    $workExperience    = getWorkExperienceByWorkExperienceUid($workExperienceUid);
    if($workExperience){
        $response = array(
            "workExperienceUid" => $workExperience->work_experience_uid,
            "employerWE"        => $workExperience->employer,
            "jobTitleWE"        => $workExperience->job_title,
            "fromWE"            => $workExperience->we_from,
            "status"            => $workExperience->status,
            "toWE"              => $workExperience->we_to
        );
    }
    
    echo jsonify($response);
});

$app->post("/work/experience/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    $empUid       = $param[0];
    
    $empWEUid     = xguid();
    
    $employer     = $_POST['employerWE'];
    $jobTitle     = $_POST['jobTitleWE'];
    $from         = $_POST['fromWE'];
    $to           = $_POST['toWE'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newWorkingExperince($empWEUid , $empUid , $employer , $jobTitle , $from , $to , $dateCreated , $dateModified);
});

/*-----------------------------------working Experience END------------------------------------------*/

$app->get("/employee/job/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $empUid       = $param[0];
    $response     = array();
    
    $employeeJobs = getPaginatedEmployeeJob($empUid);
    
    foreach ($employeeJobs as $employeeJob) {
        $job      = getJobByUid($employeeJob->job_uid);
        $jobTitle = "";
        if($job){
            if($job->status == 1){
                $jobTitle =  $job->title;
            }
        }
        $response[] = array(
            "jobTitle"         => $jobTitle,
            "jobCategory"      => $jobCategoryName,
            "subunit"          => $subunitName,
            "location"         => $locationName,
            "employmentStatus" => $employmentStatusName,
            "employeeJobUid"   => $employeeJob->employee_job_uid
        );
    }
    echo jsonify($response);
});

$app->post("/new/leave/request/:var", function($var){
    $param         = explode(".", $var);
    $token         = $param[0];
    
    $leaveUid      = xguid();
    $leaveNotifUid = xguid();
    $employee      = $_POST['employee'];
    $leaveType     = $_POST['leaveType'];
    $leaveBalance  = "";
    $startDate     = $_POST['startDate'];
    $endDate       = $_POST['endDate'];
    $reason        = $_POST['reason'];
    $requestStatus = $_POST['requestStatus'];
    $dateCreated   = date("Y-m-d H:i:s");
    $dateModified  = date("Y-m-d H:i:s");
    $response      = array();
    $countOfLeave  = getDaysOfWorkByDateRange($startDate, $endDate);

    if(strtotime($startDate) <= strtotime($endDate)){
        $valid = true;
    }else{
        $valid = false;
    }
    $code = getLeaveCodeByUid($leaveType);
    // $checkRequest = checkPayrollSchedBeforeRequest($startDate);
    // if($checkRequest["prompt"]){
        if($valid){
            if($code === "AB" || $code === "W"){
                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                $response = array(
                    "dataError" => 0,
                    "prompt"    => "Successfully Added!"
                );
            }else{
                $checkLeaveCount = checkEmpLeaveCountByEmpUid($employee);
                if($checkLeaveCount){
                    $empLeave   = getEmpLeaveCountByEmp($employee);
                    $leaveCount = leaveCountsByEmpUid($employee);
                    $SL         = $leaveCount["SL"];
                    $BL         = $leaveCount["BL"];
                    $BV         = $leaveCount["BV"];
                    $VL         = $leaveCount["VL"];
                    $ML         = $leaveCount["ML"];
                    $PL         = $leaveCount["PL"];
                    $P          = $leaveCount["P"];
                    switch($code){
                        case "P":
                            if($SL <= 0){
                                $response = array(
                                    "dataError" => 5,
                                    "prompt"    => "You used all your Personal Leave!"
                                );
                            }else{
                                $P = $P - $countOfLeave;
                                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                                $response = array(
                                    "dataError" => 0,
                                    "prompt"    => "Successfully Added! You only have " . $P . " Personal Leave. Please be noted that the approval might take a few days."
                                );
                            }
                            break;
                        case "SL":
                            if($SL <= 0){
                                $response = array(
                                    "dataError" => 5,
                                    "prompt"    => "You used all your Sick Leave!"
                                );
                            }else{
                                $SL = $SL - $countOfLeave;
                                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                                $response = array(
                                    "dataError" => 0,
                                    "prompt"    => "Successfully Added! You only have " . $SL . " Sick Leave. Please be noted that the approval might take a few days."
                                );
                            }
                            break;
                        case "BL":
                            if($BL <= 0){
                                $response = array(
                                    "dataError" => 5,
                                    "prompt"    => "You used all your Birthday Leave!"
                                );
                            }else{
                                $BL = $BL - $countOfLeave;
                                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                                $response = array(
                                    "dataError" => 0,
                                    "prompt"    => "Successfully Added! You only have " . $BL . " Birthday Leave. Please be noted that the approval might take a few days."
                                );
                            }
                            break;
                        case "BV":
                            if($BV <= 0){
                                $response = array(
                                    "dataError" => 5,
                                    "prompt"    => "You used all your Bereavement Leave!"
                                );
                            }else{
                                $BV = $BV - $countOfLeave;
                                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                                $response = array(
                                    "dataError" => 0,
                                    "prompt"    => "Successfully Added! You only have " . $BV . " Bereavement Leave Left. Please be noted that the approval might take a few days."
                                );
                            }
                            break;
                        case "VL":
                            if($VL <= 0){
                                $response = array(
                                    "dataError" => 5,
                                    "prompt"    => "You used all your Vacation Leave!"
                                );
                            }else{
                                $VL = $VL - $countOfLeave;
                                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                                $response = array(
                                    "dataError" => 0,
                                    "prompt"    => "Successfully Added! You only have " . $VL . " Vacation Leave Left. Please be noted that the approval might take a few days."
                                );
                            }
                            break;
                        case "ML":
                            if($ML <= 0){
                                $response = array(
                                    "dataError" => 5,
                                    "prompt"    => "You used all your Maternity Leave!"
                                );
                            }else{
                                $ML = $ML - $countOfLeave;
                                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                                $response = array(
                                    "dataError" => 0,
                                    "prompt"    => "Successfully Added! You only have " . $ML . " Maternity Leave Left. Please be noted that the approval might take a few days."
                                );
                            }
                            break;
                        case "PL":
                            if($PL <= 0){
                                $response = array(
                                    "dataError" => 5,
                                    "prompt"    => "You used all your Paternity Leave!"
                                );
                            }else{
                                $PL = $PL - $countOfLeave;
                                newLeaveRequest($leaveUid, $employee, $leaveType, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
                                addLeaveNotification($leaveNotifUid, $leaveUid, $requestStatus, $dateCreated, $dateModified);
                                $response = array(
                                    "dataError" => 0,
                                    "prompt"    => "Successfully Added! You only have " . $PL . " Paternity Leave Left. Please be noted that the approval might take a few days."
                                );
                            }
                            break;
                    }
                }else{
                    $response = array(
                        "dataError" => 4,
                        "prompt"    => "Employee doesn't have Count of Leave!"
                    );
                }
            }
            
        }else{
            $response = array(
                "dataError" => 3,
                "prompt"    => ""
            );
        }
        
    // }else{
    //     if($valid){
    //         $response = array(
    //             "dataError" => 1,
    //             "prompt" => ""
    //         );
    //     }else{
    //         $response = array(
    //             "dataError" => 3,
    //             "prompt" => ""
    //         );
    //     }
    // }
    echo jsonify($response);
});

$app->get("/get/leave/period/:var", function($var){
    $param        = explode(".", $var);
    $token        = $param[0];
    $leavePeriods = getLeavePeriod();
    foreach ($leavePeriods as $leavePeriod) {
        $response[] = array(
            "uid"   => $leavePeriod->leave_period_uid,
            "month" => $leavePeriod->start_month,
            "day"   => $leavePeriod->start_day,
            "from"  => $leavePeriod->from_period,
            "to"    => $leavePeriod->to_period,
        );
    }
    echo jsonify($response);
});

$app->get("/get/employee/name/:var", function($var){
    $param     = explode(".", $var);
    $token     = $param[0];
    $employees = getActiveEmployees();
    foreach ($employees as $employee) {
        $response[] = array(
            "uid"             => $employee->emp_uid,
            "firstname"       => utf8_decode($employee->firstname),
            "middlename"      => utf8_decode($employee->middlename),
            "lastname"        => utf8_decode($employee->lastname),
            "employee_number" => $employee->username
        );

        
    }
    echo jsonify($response);
});

$app->get("/get/employee/name/without/benefit/:var", function($var){
    $param     = explode(".", $var);
    $token     = $param[0];
    $employees = getEmpWithoutBenefits();
    foreach ($employees as $employee) {
        $response[] = array(
            "empUid"     => $employee->emp_uid,
            "firstname"  => utf8_decode($employee->firstname),
            "middlename" => utf8_decode($employee->middlename),
            "lastname"   => utf8_decode($employee->lastname),
            "empNo"      => $employee->username
        );
    }
    echo jsonify($response);
});

$app->get("/get/leave/types/:var", function($var){
    $param      = explode(".", $var);
    $token      = $param[0];
    $leaveTypes = getPaginatedLeaveTypes();
    foreach ($leaveTypes as $leaveType) {
        $response[] = array(
            "uid"  => $leaveType->leaves_types_uid,
            "name" => $leaveType->leave_name,
        );
    }
    echo jsonify($response);
});

$app->get("/get/leave/types/emp/:var", function($var){
    $param = explode(".", $var);
    $uid   = $param[0];
    $x     = getLeaveTypeDataByUid($uid);
    if($x){
        $response = array(
            "leaveTypesUid" => $x->leaves_types_uid,
            "leaveName"     => $x->leave_name,
            "status"        => $x->status
        );
    }
    echo jsonify($response);
});

/*------------------------------------education---------------------------*/
$app->get("/education/get/:var" , function($var){
    //parameter: token
    $param      = explode(".", $var);
    $empUid     = $param[0];
    $token      = $param[1];
    $response   = array();
    $educations = getEducationByUid($empUid);
    foreach ($educations as $education) {
        $response[] = array(
            "educationLevelUid" => $education->level_name,
            "year"              => $education->year,
            "score"             => $education->score,
            "educationUid"      => $education->education_uid
        );
    }
    echo jsonify($response);
});

$app->get("/skill/detail/get/:var" , function($var){

    $param    = explode(".", $var);
    $empUid   = $param[0];
    $token    = $param[1];
    
    $response = array();
    $skills   = getSkillByUid($empUid);
    foreach ($skills as $skill) {
        $response[] = array(
            "skillUid"        => $skill->skill_type,
            "yearsExperience" => $skill->years_experience,
            "hrisSkillUid"    => $skill->hris_skill_uid
        );
    }
    echo jsonify($response);
});

$app->get("/languages/detail/get/:var" , function($var){

    $param     = explode(".", $var);
    $empUid    = $param[0];
    $token     = $param[1];
    $response  = array();
    $languages = getLanguagesByUid($empUid);
    foreach ($languages as $language) {
        $response[] = array(
            "languagesUid"    => $language->language_name,
            "fluency"         => $language->fluency,
            "competency"      => $language->competency,
            "empLanguagesUid" => $language->emp_languages_uid
        );
    }
    echo jsonify($response);
});

$app->get("/license/detail/get/:var" , function($var){

    $param    = explode(".", $var);
    $empUid   = $param[0];
    $token    = $param[1];
    $response = array();
    $licenses = getLicenseByUid($empUid);
    foreach ($licenses as $license) {
        $response[] = array(
            "licenseUid"     => $license->license_name,
            "issuedDate"     => $license->issued_date,
            "expiryDate"     => $license->expiry_date,
            "hrisLicenseUid" => $license->hris_license_uid
        );
    }
    echo jsonify($response);
});

$app->get("/work/experience/get/:var" , function($var){
    //parameter: token
    $param           = explode(".", $var);
    $empUid          = $param[0];
    $token           = $param[1];
    $response        = array();
    $workExperiences = getWorkExperienceByUid($empUid);
    foreach ($workExperiences as $workExperience) {
        $response[] = array(
            "workExperienceUid" => $workExperience->work_experience_uid,
            "employer"          => $workExperience->employer,
            "jobTitle"          => $workExperience->job_title,
            "from"              => $workExperience->we_from,
            "to"                => $workExperience->we_to
        );
    }
    echo jsonify($response);
});

$app->post("/education/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    $empUid       = $param[0];
    $educationUid = xguid();
    
    $levelDegree  = $_POST['levelDegree'];
    $school       = $_POST['school'];
    $year         = $_POST['year'];
    $major        = $_POST['major'];
    $score        = $_POST['score'];
    $startDate    = $_POST['startDate'];
    $endDate      = $_POST['endDate'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newEducation($educationUid , $empUid , $levelDegree , $school , $year , $major , $score , $startDate , $endDate , $dateCreated , $dateModified);
});

$app->get("/education/level/get/" , function(){
    //parameter: token
    $response        = array();
    $educationLevels = getEducationLevel();
    foreach ($educationLevels as $educationLevel) {
        $response[] = array(
            "educationLevelUid" => $educationLevel->education_level_uid,
            "levelName"         => $educationLevel->level_name
        );
    }

    echo jsonify($response);
});

$app->get("/education/details/get/:var" , function($var){

    $param        = explode(".", $var);
    $educationUid = $param[0];
    $token        = $param[1];
    $response     = array();
    $education    = getEducationByEducationUid($educationUid);
    if($education){
        $response = array(
            "educationUid"      => $education->education_uid,
            "educationLevelUid" => $education->level_name,
            "year"              => $education->year,
            "score"             => $education->score,
            "school"            => $education->school,
            "major"             => $education->major,
            "startDate"         => $education->start_date,
            "endDate"           => $education->end_date,
            "status"            => $education->status
        );
    }
    
    echo jsonify($response);
});

$app->post("/education/update/:var" , function($var){

    $param        = explode(".", $var);
    
    $educationUid = $param[0];
    $levelDegree  = $_POST['levelDegree'];
    $school       = $_POST['school'];
    $year         = $_POST['year'];
    $major        = $_POST['major'];
    $score        = $_POST['score'];
    $startDate    = $_POST['startDate'];
    $endDate      = $_POST['endDate'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");

    updateEducation($levelDegree , $school , $year , $major , $score , $startDate , $endDate , $status , $dateModified , $educationUid);
});

$app->post("/skill/update/:var" , function($var){

    $param           = explode(".", $var);
    $skillUid        = $param[0];
    $skillType       = $_POST['skillType'];
    $yearsExperience = $_POST['yearsExperience'];
    $status          = $_POST['status'];
    $dateModified    = date("Y-m-d H:i:s");

    updateSkill($skillUid , $skillType , $yearsExperience , $status , $dateModified);
});

$app->get("/skill/details/get/:var" , function($var){

    $param    = explode(".", $var);
    $skillUid = $param[0];
    $token    = $param[1];
    $response = array();
    $skill    = getSkillBySkillUid($skillUid);
    if($skill){
        $response = array(
            "skillUid"  => $skill->hris_skill_uid,
            "skillType" => $skill->skill_type,
            "year"      => $skill->years_experience,
            "status"    => $skill->status
        );
    }
    
    echo jsonify($response);
});

$app->get("/skill/type/get/" , function(){

    $response   = array();
    $skillTypes = getSkillType();
    foreach ($skillTypes as $skillType) {
        $response[] = array(
            "skillUid"  => $skillType->skill_uid,
            "skillType" => $skillType->skill_type
        );
    }

    echo jsonify($response);
});

$app->post("/skill/new/:var" , function($var){
    $param           = explode(".", $var);
    $token           = $param[1];
    $empUid          = $param[0];
    $hrisSkillUid    = xguid();
    
    $skillType       = $_POST['skillType'];
    $yearsExperience = $_POST['yearsExperience'];
    $dateCreated     = date("Y-m-d H:i:s");
    $dateModified    = date("Y-m-d H:i:s");

    newHrisSkill($hrisSkillUid , $empUid , $skillType , $yearsExperience , $dateCreated , $dateModified);
});

$app->post("/languages/spoken/new/:var" , function($var){
    $param           = explode(".", $var);
    $token           = $param[1];
    $empUid          = $param[0];
    $empLanguagesUid = xguid();
    
    $languageName    = $_POST['languageName'];
    $fluency         = $_POST['fluency'];
    $competency      = $_POST['competency'];
    $dateCreated     = date("Y-m-d H:i:s");
    $dateModified    = date("Y-m-d H:i:s");

    newLanguagesSpoken($empLanguagesUid , $empUid , $languageName , $fluency , $competency , $dateCreated , $dateModified);
});

$app->get("/language/details/get/:var" , function($var){
    
    $param        = explode(".", $var);
    $languagesUid = $param[0];
    $token        = $param[1];
    $response     = array();
    $language     = getLanguageBylanguagesUid($languagesUid);
    if($language){
        $response = array(
            "languagesUid"    => $language->language_name,
            "fluency"         => $language->fluency,
            "competency"      => $language->competency,
            "status"          => $language->status,
            "empLanguagesUid" => $language->emp_languages_uid
        );
    }
    
    echo jsonify($response);
});

$app->get("/languge/get/" , function(){

    $response  = array();
    $languages = getLanguages();
    foreach ($languages as $language) {
        $response[] = array(
            "languagesUid" => $language->languages_uid,
            "languageName" => $language->language_name
        );
    }

    echo jsonify($response);
});

$app->post("/languages/update/:var" , function($var){

    $param        = explode(".", $var);
    
    $languagesUid = $param[0];
    $languageName = $_POST['languageName'];
    $fluency      = $_POST['fluency'];
    $competency   = $_POST['competency'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");

    updateLanguages($languageName , $fluency , $competency , $status , $dateModified , $languagesUid);
});

$app->get("/license/type/get/" , function(){
    //parameter: token
    $response     = array();
    $licenseTypes = getLicenseType();
    foreach ($licenseTypes as $licenseType) {
        $response[] = array(
            "licenseUid"  => $licenseType->license_uid,
            "LicenseName" => $licenseType->license_name
        );
    }

    echo jsonify($response);
});

$app->post("/license/new/:var" , function($var){
    $param          = explode(".", $var);
    $token          = $param[1];
    $empUid         = $param[0];
    $hrisLicenseUid = xguid();
    
    $licenseType    = $_POST['licenseType'];
    $licenseNo      = $_POST['licenseNo'];
    $licenseIssued  = $_POST['licenseIssued'];
    $licenseExpiry  = $_POST['licenseExpiry'];
    $dateCreated    = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");

    newHrisLicense($hrisLicenseUid , $empUid , $licenseType , $licenseNo , $licenseIssued , $licenseExpiry , $dateCreated , $dateModified);
});

$app->get("/license/details/get/:var" , function($var){
    
    $param      = explode(".", $var);
    $licenseUid = $param[0];
    $token      = $param[1];
    $response   = array();
    $license    = getLicenseByLicenseUid($licenseUid);
    if($license){
        $response = array(
            "licenseUid"     => $license->license_name,
            "licenseNo"      => $license->license_no,
            "issuedDate"     => $license->issued_date,
            "expiryDate"     => $license->expiry_date,
            "status"         => $license->status,
            "hrisLicenseUid" => $license->hris_license_uid
        );
    }
    
    echo jsonify($response);
});

$app->post("/license/update/:var" , function($var){

    $param         = explode(".", $var);
    $licenseUid    = $param[0];
    $licenseType   = $_POST['licenseType'];
    $licenseNo     = $_POST['licenseNo'];
    $licenseIssued = $_POST['licenseIssued'];
    $licenseExpiry = $_POST['licenseExpiry'];
    $status        = $_POST['status'];
    $dateModified  = date("Y-m-d H:i:s");

    updateLicense($licenseUid , $licenseType , $licenseNo , $licenseIssued , $licenseExpiry , $status , $dateModified);
});

$app->get("/employee/salary/get/:var" , function($var){
    //parameter: token
    $param          = explode(".", $var);
    $empUid         = $param[0];
    $token          = $param[1];
    $response       = array();
    $employeeSalary = getSalaryByUid($empUid);
    if ($employeeSalary) {
        $response[] = array(
            "payGradeUid"  => $employeeSalary->paygrade_name,
            "currencyUid"  => $employeeSalary->name,
            "baseSalary"   => $employeeSalary->base_salary,
            "payPeriodUid" => $employeeSalary->pay_period_name,
            "salaryUid"    => $employeeSalary->salary_uid
        );
    }
    echo jsonify($response);
});

$app->get("/employee/salary/paygrade/get/" , function(){
    //parameter: token
    $response  = array();
    $payGrades = getPayGrade();
    foreach ($payGrades as $payGrade) {
        $response[] = array(
            "payGradeUid"  => $payGrade->paygrade_uid,
            "payGradeName" => $payGrade->paygrade_name
        );
    }

    echo jsonify($response);
});

$app->get("/employee/salary/currency/get/" , function(){
    //parameter: token
    $response   = array();
    $currencies = getCurrencies();
    foreach ($currencies as $currency) {
        $response[] = array(
            "currencyUid"  => $currency->currency_uid,
            "currencyName" => $currency->name
        );
    }

    echo jsonify($response);
});

$app->get("/employee/salary/frequency/get/" , function(){
    //parameter: token
    $response    = array();
    $frequencies = getFrequencies();
    foreach ($frequencies as $frequency) {
        $response[] = array(
            "frequencyUid"  => $frequency->pay_period_uid,
            "frequencyName" => $frequency->pay_period_name
        );
    }

    echo jsonify($response);
});

$app->post("/employee/salary/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    
    $empUid       = $param[0];
    $salaryUid    = xguid();
    $baseSalary   = $_POST['baseSalary'];
    $payPeriodUid = $_POST['payPeriodUid'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    $check = checkUserHasSalary($empUid);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        $response = array(
            "prompt" => 0
        );
        newSalary($salaryUid, $empUid, $baseSalary, $payPeriodUid, $dateCreated, $dateModified);
        newEmpType(xguid(), "", $payPeriodUid, $empUid, $dateCreated, $dateModified);
    }
    echo jsonify($response);
});

$app->get("/salary/details/get/:var" , function($var){
    //parameter: token
    $param     = explode(".", $var);
    $salaryUid = $param[0];
    $token     = $param[1];
    $response  = array();
    $salary    = getSalaryBySalaryUid($salaryUid);
    if($salary){
        $response = array(
        "frequencyUid" => $salary->pay_period_uid,
        "baseSalary"   => $salary->base_salary,
        "payPeriodUid" => $salary->pay_period_name,
        "status"       => $salary->status,
        "salaryUid"    => $salary->salary_uid
    );
    }
    
    echo jsonify($response);
});

$app->post("/employee/salary/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $empUid       = $_POST["uid"];
    $salaryUid    = $param[0];
    $baseSalary   = $_POST['baseSalary'];
    $payPeriodUid = $_POST['payPeriodUid'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");

    updateSalary($salaryUid , $dateModified , $baseSalary , $payPeriodUid , $status);
    updateEmpType($empUid, $payPeriodUid, $dateModified);
});

$app->get("/employee/late/get/:var", function($var){
    $param    = explode(".", $var);
    $uid      = $param[0];
    $token    = $param[1];
    $response = array();
    
    $a        = getLateByEmpUid($uid);
    foreach($a as $late){
        $response[] = array(
            "lateUid"  => $late->late_emp_uid,
            "name"     => $late->name,
            "duration" => $late->duration
        );
    }//end of getLateByEmpUid Function

    echo jsonify($response);
});

$app->post("/employee/late/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    
    $empUid       = $param[0];
    $lateEmpUid   = xguid();
    $lateUid      = $_POST['name'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newEmpLate($lateEmpUid, $empUid, $lateUid, $dateCreated, $dateModified);
});

$app->get("/late/details/get/edit/:var" , function($var){
    //parameter: token
    $param    = explode(".", $var);
    $lateUid  = $param[0];
    $token    = $param[1];
    $response = array();
    $late     = getEmpLateByEmpUid($lateUid);
    if($late){
        $response = array(
            "lateUid"  => $late->late_emp_uid,
            "name"     => $late->name,
            "duration" => $late->duration,
            "status"   => $late->status
        );
    }
    
    echo jsonify($response);
});

$app->post("/employee/late/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    
    $uid          = $param[0];
    $lateUid      = $_POST['lateUid'];
    $name         = $_POST['name'];
    $status       = $_POST['status'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    updateEmpLate($lateUid , $name , $status , $dateModified);
});

$app->get("/employee/immigration/get/:var" , function($var){
    //parameter: token
    $param                = explode(".", $var);
    $empUid               = $param[0];
    $token                = $param[1];
    $response             = array();
    $employeeImmigrations = getImmigration($empUid);
    foreach ($employeeImmigrations as $employeeImmigration) {
        $response[] = array(
            "documentType"   => $employeeImmigration->document_type,
            "passportNo"     => $employeeImmigration->passport_no,
            "issuedBy"       => $employeeImmigration->name,
            "issuedDate"     => $employeeImmigration->issued_date,
            "expiryDate"     => $employeeImmigration->expiry_date,
            "immigrationUid" => $employeeImmigration->emp_immigration_uid
        );
    }
    echo jsonify($response);
});

$app->get("/employee/pettycash/get/:var" , function($var){
    //parameter: token
    $param             = explode(".", $var);
    $id                = $param[0];
    $token             = $param[1];
    $response          = array();
    $employeePettycash = getPettyCash($id);
    foreach ($employeePettycash as $pettycash) {
        $response[] = array(
            "amount"   => $pettycash->amount,
            "dueDate"  => date("M d, Y", strtotime($pettycash->due_date)),
            "pettyUid" => $pettycash->pettycash_uid
        );
    }
    echo jsonify($response);
});

$app->post("/employee/pettycash/new/:var" , function($var){
    //parameter: term.start.size.token
    $param           = explode(".", $var);
    
    $empUid          = $param[0];
    $empPettycashUid = xguid();
    $amount          = $_POST['amount'];
    $dueDate         = $_POST['dueDate'];
    $dateCreated     = date("Y-m-d H:i:s");
    $dateModified    = date("Y-m-d H:i:s");

    newPettyCash($empPettycashUid, $empUid, $amount, $dueDate, $dateCreated, $dateModified);
});

$app->get("/pettycash/details/get/edit/:var" , function($var){
    //parameter: token
    $param        = explode(".", $var);
    $pettycashUid = $param[0];
    $token        = $param[1];
    $response     = array();
    $pettycash    = getPettycashByUid($pettycashUid);
    if($pettycash){
        $response = array(
        "amount"       => $pettycash->amount,
        "dueDate"      => $pettycash->due_date,
        "status"       => $pettycash->status,
        "pettycashUid" => $pettycash->pettycash_uid
    );
    }
    
    echo jsonify($response);
});

$app->post("/employee/pettycash/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    
    $pettycashUid = $param[0];
    $amount       = $_POST['amount'];
    $dueDate      = $_POST['dueDate'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");

    updatePettyCash($pettycashUid , $amount , $dueDate , $status , $dateModified);
});

$app->get("/employee/attendance/get/:var" , function($var){
    //parameter: token
    $param                = explode(".", $var);
    $empUid               = $param[0];
    $token                = $param[1];
    $response             = array();
    $employeeImmigrations = getImmigration($empUid);
    foreach ($employeeImmigrations as $employeeImmigration) {
        $response[] = array(
            "documentType"   => $employeeImmigration->document_type,
            "passportNo"     => $employeeImmigration->passport_no,
            "issuedBy"       => $employeeImmigration->name,
            "issuedDate"     => $employeeImmigration->issued_date,
            "expiryDate"     => $employeeImmigration->expiry_date,
            "immigrationUid" => $employeeImmigration->emp_immigration_uid
        );
    }
    echo jsonify($response);
});

$app->post("/employee/immigration/new/:var" , function($var){
    //parameter: term.start.size.token
    $param             = explode(".", $var);
    
    $empUid            = $param[0];
    $empImmigrationUid = xguid();
    $documentType      = $_POST['documentType'];
    $passportNo        = $_POST['passportNo'];
    $issuedDate        = $_POST['issuedDate'];
    $expiryDate        = $_POST['expiryDate'];
    $eligibleStatus    = $_POST['eligibleStatus'];
    $countryUid        = $_POST['countryUid'];
    $reviewDate        = $_POST['reviewDate'];
    $dateCreated       = date("Y-m-d H:i:s");
    $dateModified      = date("Y-m-d H:i:s");

    newImmigration($empImmigrationUid, $documentType, $passportNo, $issuedDate, $expiryDate, $eligibleStatus, $countryUid, $reviewDate, $empUid, $dateCreated, $dateModified);
});

$app->get("/immigration/details/get/edit/:var" , function($var){
    //parameter: token
    $param          = explode(".", $var);
    $immigrationUid = $param[0];
    $token          = $param[1];
    $response       = array();
    $immigration    = getImmigrationByUid($immigrationUid);
    if($immigration){
        $response = array(
        "documentType"   => $immigration->document_type,
        "passportNo"     => $immigration->passport_no,
        "issuedDate"     => $immigration->issued_date,
        "expiryDate"     => $immigration->expiry_date,
        "eligibleStatus" => $immigration->eligible_status,
        "reviewDate"     => $immigration->review_date,
        "countryName"    => $immigration->name,
        "status"         => $immigration->status,
        "immigrationUid" => $immigration->emp_immigration_uid
    );
    }
    
    echo jsonify($response);
});

$app->post("/employee/immigration/update/:var" , function($var){
    //parameter: term.start.size.token
    $param = explode(".", $var);

    $immigrationUid = $param[0];
    $documentType   = $_POST['documentType'];
    $passportNo     = $_POST['passportNo'];
    $issuedDate     = $_POST['issuedDate'];
    $expiryDate     = $_POST['expiryDate'];
    $eligibleStatus = $_POST['eligibleStatus'];
    $countryUid     = $_POST['countryUid'];
    $reviewDate     = $_POST['reviewDate'];
    $status         = $_POST['status'];
    $dateCreated    = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");

    updateImmigration($documentType , $passportNo , $issuedDate , $expiryDate , $eligibleStatus , $countryUid , $reviewDate, $status , $dateModified , $immigrationUid);
});

$app->post("/employee/allowance/new/:var" , function($var){
    //parameter: term.start.size.token
    $param           = explode(".", $var);
    
    $empUid          = $param[0];
    $empAllowanceUid = xguid();
    $meal            = $_POST['meal'];
    $transpo         = $_POST['transpo'];
    $cola            = $_POST['cola'];
    $other           = $_POST['other'];
    $date            = $_POST['date'];
    $dateCreated     = date("Y-m-d H:i:s");
    $dateModified    = date("Y-m-d H:i:s");

    newAllowance($empAllowanceUid, $meal, $transpo, $cola, $other, $date, $dateCreated, $dateModified, $empUid);
});

$app->get("/employee/allowance/get/:var" , function($var){
    //parameter: token
    $param             = explode(".", $var);
    $empUid            = $param[0];
    $token             = $param[1];
    $response          = array();
    $employeeAllowance = getAllowance($empUid);
    foreach ($employeeAllowance as $allowance) {
        $response[] = array(
            "allowanceUid" => $allowance->allowance_uid,
            "meal"         => $allowance->meal,
            "transpo"      => $allowance->transportation,
            "cola"         => $allowance->COLA,
            "other"        => $allowance->other,
            "date"         => date("M d, Y", strtotime($allowance->date_receive))
        );
    }
    echo jsonify($response);
});

$app->get("/allowance/details/get/edit/:var" , function($var){
    //parameter: token
    $param        = explode(".", $var);
    $allowanceUid = $param[0];
    $token        = $param[1];
    $response     = array();
    $allowance    = getAllowanceByUid($allowanceUid);
    if($allowance){
        $response = array(
            "allowanceUid" => $allowance->allowanceUid,
            "meal"         => $allowance->meal,
            "transpo"      => $allowance->transportation,
            "cola"         => $allowance->COLA,
            "other"        => $allowance->other,
            "date"         => $allowance->date_receive,
            "status"       => $allowance->status
        );
    }
    echo jsonify($response);
});

$app->post("/employee/allowance/edit/:var" , function($var){
    //parameter: token
    $param        = explode(".", $var);
    $id           = $param[0];
    $token        = $param[1];
    $meal         = $_POST['meal'];
    $transpo      = $_POST['transpo'];
    $cola         = $_POST['cola'];
    $other        = $_POST['other'];
    $date         = $_POST['date'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");
    
    updateAllowanceById($id, $meal, $transpo, $cola, $other, $date, $dateModified, $status);
});

$app->post("/add/costcenter/", function(){
    $costUid      = xguid();
    $name         = $_POST["name"];
    $desc         = $_POST["desc"];
    $payperiod    = $_POST["payperiod"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $check        = checkCostCenterIfExists($name);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        addNewCostCenter($costUid, $name, $desc, $payperiod, $dateCreated, $dateModified);
        $response = array(
            "prompt" => 0
        );
    }
    echo jsonify($response);
});

$app->get("/get/costcenter/", function(){
    $response = array();
    $cost     = getCostcenter();

    foreach($cost as $costcenter){
        $response[] = array(
            "uid"         => $costcenter->cost_center_uid,
            "name"        => $costcenter->cost_center_name,
            "description" => $costcenter->description,
            "payperiod"   => $costcenter->pay_period_name,
            "status"      => $costcenter->status
        );
    }

    echo jsonify($response);
});

$app->get("/get/employeeschedule/", function(){
    $response = array();
    $indi     = getIndividualEmp();

    foreach($indi as $individual){
        $response[] = array(
            "uid"           => $individual->emp_uid,
            "name"          => $individual->firstname,
            "status"        => $individual->status
        );
    }

    echo jsonify($response);
    echo "Hello";
});

$app->get("/get/costcenter/data/:uid", function($uid){
    $response = array();
    $data     = getCostcenterDataByUid($uid);

    if($data){
        $response = array(
            "costcenterUid" => $data->cost_center_uid,
            "payperiodUid"  => $data->pay_period_uid,
            "ccName"        => $data->cost_center_name,
            "ccDesc"        => $data->description,
            "status"        => $data->status
        );
    }

    echo jsonify($response);
});

$app->post("/update/costcenter/:uid", function($uid){
    $name         = $_POST["name"];
    $desc         = $_POST["desc"];
    $payperiod    = $_POST["payperiod"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateCostCenter($uid, $name, $desc, $payperiod, $dateModified, $status);
    // $response = array(
    //     "prompt" => 0
    // );
    var_dump(updateCostCenter($uid, $name, $desc, $payperiod, $dateModified, $status));
    // echo jsonify($response);
});

$app->post("/set/emp/costcenter/", function(){
    $costUid      = xguid();
    $costcenter   = $_POST["costcenter"];
    $empUid       = $_POST["uid"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $check        = countEmpSetCostCenter($empUid);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        setEmpCostCenter($costUid, $costcenter, $empUid, $dateCreated, $dateModified);
        $response = array(
            "prompt" => 0
        );
    }

    echo jsonify($response);
});

$app->post("/employee/update/costcenter/:uid", function($uid){
    $costcenter   = $_POST["costcenter"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateEmpCostCenter($uid, $costcenter, $dateModified, $status);
});

$app->get("/check/costcenter/:uid", function($uid){
    $check = countEmpSetCostCenter($uid);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        $response = array(
            "prompt" => 0
        );
    }

    echo jsonify($response);
});

$app->get("/check/costcenter/set/:uid", function($uid){
    $check = countEmpSetCostCenter($uid);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        setEmpCostCenter($costUid, $costcenter, $empUid, $dateCreated, $dateModified);
        $response = array(
            "prompt" => 0
        );
    }

    echo jsonify($response);
});

$app->get("/employee/costcenter/data/:uid", function($uid){
    $datas    = getEmpCostCenterDataByEmpUid($uid);
    $response = array();

    foreach($datas as $data){
        $response[] = array(
            "empCostUid" => $data->emp_cost_center_uid,
            "costUid"    => $data->cost_center_uid,
            "emp"        => $data->emp_uid,
            "ccName"     => $data->cost_center_name,
            "ccDesc"     => $data->description,
            "status"     => $data->status
        );
    }

    echo jsonify($response);
});

$app->get("/employee/costcenter/single/data/:uid", function($uid){
    $data = getEmpCostCenterDataByUid($uid);

    if($data){
        $response = array(
            "empCostUid" => $data->emp_cost_center_uid,
            "costUid"    => $data->cost_center_uid,
            "emp"        => $data->emp_uid,
            "ccName"     => $data->cost_center_name,
            "ccDesc"     => $data->description,
            "status"     => $data->status
        );
    }

    echo jsonify($response);
});

$app->get("/get/employee/costcenter/:uid", function($uid){
    $datas    = getEmployeeByCostCenterUid($uid);
    $response = array();

    foreach($datas as $data){
        $response[] = array(
            "empUid"     => $data->emp_uid,
            "firstname"  => utf8_decode($data->firstname),
            "middlename" => utf8_decode($data->middlename),
            "lastname"   => utf8_decode($data->lastname),
            "empNo"      => $data->username
        );
    }

    echo jsonify($response);
});
//------------------------------------------------TIMESHEET------------------------------------------------------//
$app->post("/employee/timesheet/get/:uid", function($uid){
    $startDate = date("Y-m-d", strtotime($_POST["startDate"]));
    $endDate   = date("Y-m-d", strtotime($_POST["endDate"]));

    getAllTimeSheetByEmpUidAndDateRange($uid, $startDate, $endDate);

});

$app->get("/employee/timesheet/details/:var", function($var){
    $param     = explode(".", $var);
    $uid       = $param[0];
    $startDate = date("Y-m-d", strtotime($param[1]));
    $endDate   = date("Y-m-d", strtotime($param[2]));
    $response  = array();
    
    $scheds    = getAllTimeSheetByEmpUidAndDateRange($uid, $startDate, $endDate);
    foreach($scheds as $sched) {
        $type = $sched->type;
        if($type == 0){
            $types = "IN";
        }else{
            $types = "OUT";
        }//end of if-else
        $response[] = array(
            "date" => date('F d, Y', strtotime($sched->date_created)),
            "time" => date('g:i A', strtotime($sched->date_created)),
            "type" => $types
        );
    }
    echo jsonify($response);

});

$app->get("/get/timein/data/:uid", function($uid){
    $check = checkTimeInDataByUid($uid);

    if($check){
        $timeIn = getTimeInDataByUid($uid);

        $response = array(
            "timeIn" => date("Y-m-d h:i A",strtotime($timeIn->date_created)),
            "shift"  => $timeIn->shift_uid,
            "status" => $timeIn->status
        );
    }else{
        $response = array(
            "timeIn" => "",
            "shift"  => "",
            "status" => 1
        );
    }
    

    echo jsonify($response);
});

$app->get("/get/timeout/data/:uid", function($uid){
    $timeOut = getTimeInDataByUid($uid);

    $response = array(
        "timeOut" => date("Y-m-d h:i A",strtotime($timeOut->date_created)),
        "shift"   => $timeOut->shift_uid
    );

    echo jsonify($response);
});

$app->post("/edit/time/in/data/:in", function($in){
    $inUid   = $in;
    $timeIn  = $_POST["timeIn"];
    $empUids = $_POST["empUid"];
    
    $status  = $_POST["status"];
    // $timeInDate = date("Y-m-d", strtotime($timeIn));
    // $timeInHour = date("H:i:s", strtotime($timeIn));
    $shift        = $_POST["shift"];
    $dateModified = date("Y-m-d H:i:s");
    $day          = date("N", strtotime($timeIn));
    $timeIn       = date("Y-m-d H:i",  strtotime($timeIn));

    if(strlen($inUid) <= 1){
        $session = xguid();
        addTimeIn(xguid(), $empUids, $shift, $session, $timeIn, $dateModified);
    }else{
        updateTimeIn($inUid, $timeIn, $shift ,$dateModified, $status);
    }
});

$app->post("/edit/time/out/data/:var", function($var){
    // $inUid = $in;
    $params        = explode(".", $var);
    $inUid         = $params[0];
    $outUid        = $params[1];
    $timeOut       = $_POST["timeOut"];
    $empUids       = $_POST["empUid"];
    
    $status        = $_POST["status"];
    // $timeInDate = date("Y-m-d", strtotime($timeIn));
    // $timeInHour = date("H:i:s", strtotime($timeIn));
    $shift         = $_POST["shift"];
    $dateModified  = date("Y-m-d H:i:s");
    $day           = date("N", strtotime($timeOut));
    $in            = getTimeInDataByUid($inUid);
    $session       = $in["session"];
    $location      = $in["location_uid"];
    $timeOut       = date("Y-m-d H:i",  strtotime($timeOut));

    if($outUid == "No Time Out!"){
        addTimeOut(xguid(), $empUids, $shift, $session, $timeOut, $location, $dateModified);
    }else{
        updateTimeIn($outUid, $timeOut, $shift ,$dateModified, $status);
    }
});

$app->post("/edit/time/data/:var", function($var){
    $param        = explode(".", $var);
    $inUid        = $param[0];
    $outUid       = $param[1];
    
    $timeIn       = $_POST["timeIn"];
    $status       = $_POST["status"];
    
    $timeInDate   = date("Y-m-d", strtotime($timeIn));
    $timeInHour   = date("H:i", strtotime($timeIn));
    $timeOut      = $_POST["timeOut"];
    $timeOutDate  = date("Y-m-d", strtotime($timeOut));
    $timeOutHour  = date("H:i", strtotime($timeOut));
    $shift        = $_POST["shift"];
    $dateModified = date("Y-m-d H:i:s");
    $day          = date("N", strtotime($timeIn));
    
    $timeIn       = date("Y-m-d H:i", strtotime($timeIn));
    $timeOut      = date("Y-m-d H:i", strtotime($timeOut));

    // $getRule = getRuleByRuleUid($rule, $day);

    // if($getRule){
    //     $shift = $getRule->shift_uid;
    // }

    $in            = getTimeInDataByUid($inUid);
    $inDateCreated = $in["date_modified"];
    $inDate        = date("Y-m-d", strtotime($inDateCreated));
    $inHour        = date("H:i", strtotime($inDateCreated));
    $empUid        = $in["emp_uid"];

    $out            = getTimeInDataByUid($outUid);
    $outDateCreated = $out->date_modified;
    $outDate        = date("Y-m-d", strtotime($outDateCreated));
    $outHour        = date("H:i", strtotime($outDateCreated));
    
    $empNumber      = getEmloyeeNumberByEmpUid($empUid);
    $checkIn        = getOtherTimeInData($empNumber, $inDate, $inHour);
    $checkOut       = getOtherTimeOutData($empNumber, $outDate, $outHour);
    // editOtherTimeIn($empNumber, $inDate, $inHour, $timeInDate, $timeInHour);
    // editOtherTimeOut($empNumber, $outDate, $outHour, $timeOutDate, $timeOutHour);
    // editEventLogTimeIn($empUid, $inDate, $inHour, $timeInDate, $timeInHour);
    // editEventLogTimeOut($empUid, $outDate, $outHour, $timeOutDate, $timeOutHour);
    updateTimeIn($inUid, $timeIn, $shift ,$dateModified, $status);
    updateTimeOut($outUid, $timeOut, $shift ,$dateModified, $status);
});

$app->post("/delete/time/data/:var", function($var){
    $param  = explode(".", $var);
    $inUid  = $param[0];
    $outUid = $param[1];

    deleteTimeLogByUid($inUid);
    deleteTimeLogByUid($outUid);
});

$app->get("/timesheet/view/all/emp/", function(){
    $response = array();
    $emp      = getActiveMonthlyEmployees();
    foreach ($emp as $emps) {
        $response[] = array(
            "uid"       => $emps->emp_uid,
            "lastname"  => utf8_decode($emps->lastname),
            "firstname" => utf8_decode($emps->firstname)
        );
    }
    echo jsonify($response);
});

$app->get("/emp/period/:frequencyUid", function($frequencyUid){
    $frequencyUid = $frequencyUid;
    $response     = array();
    $a            = getEmployeesByFrequencyUid($frequencyUid);
    foreach($a as $emp){
        $response[] = array(
            "uid"       => $emp->emp_uid,
            "lastname"  => utf8_decode($emp->lastname),
            "firstname" => utf8_decode($emp->firstname)
        );
    }

    echo jsonify($response);
});

$app->get("/timesheet/view/all/", function(){
    $response = array();
    $emp      = getActiveMonthlyEmployees();
    foreach ($emp as $emps) {
        $response[] = array(
            "uid"       => $emps->emp_uid,
            "lastname"  => utf8_decode($emps->lastname),
            "firstname" => utf8_decode($emps->firstname)
        );
    }
    echo jsonify($response);
});

$app->post("/timesheet/all/daily/", function(){
    // $emp = $_POST["employee"];
    $startDateStr = $_POST["startDate"];
    $endDateStr   = $_POST["endDate"];

    getEmpTimesheet($emp,$startDateStr,$endDateStr);
});

$app->get("/timesheet/all/daily/attendance/:var", function($var){
    $param     = explode(".", $var);
    $emp       = $param[0];
    $startDate = date('Y-m-d', strtotime($param[1]));
    $endDate   = date('Y-m-d', strtotime($param[2]));
    
    $x         = getEmpTimesheet($emp,$startDate,$endDate);
    echo jsonify($x);
});

$app->get("/timesheet/all/view/attendance/:var", function($var){
    $param     = explode(".", $var);
    // $emp    = $param[0];
    $startDate = date('Y-m-d', strtotime($param[0]));
    $endDate   = date('Y-m-d', strtotime($param[1]));
    $id        = $param[2];
    
    $x         = generateTimesheetByEmpUid($startDate, $endDate, $id);
    echo jsonify($x);

    // echo jsonify($x["totalNumDay"]);
});

$app->get("/timesheet/view/summary/:var", function($var){
    $param     = explode(".", $var);
    $startDate = date('Y-m-d', strtotime($param[0]));//strtotime returns seconds of the string since jan 1 1970
    $endDate   = date('Y-m-d', strtotime($param[1]));//simply returns the date in format Y-m-d
    
    $cost      = $param[2];
    
    $x         = timeOrganizedSummary($startDate, $endDate, $cost);
    echo jsonify($x);
});

//------------------------------------------------LOCATION------------------------------------------------------//

$app->get("/get/spes/location/data/:var", function($var){
    $param     = explode(".", $var);
    $long      = $param[0];
    $lat       = $param[1];
    
    $locations = getLocationByCoords($long, $lat);
    if($location){
        $response = array(
            "locUid"       => $locations->uid,
            "name"         => $locations->name,
            "locLongitude" => $locations->longitude,
            "locLatitude"  => $locations->latitude,
            "status"       => $locations->status
        );
    }

    echo jsonify($response);
});

$app->post("/locations/new/", function(){
    $locUid      = xguid();
    $name        = $_POST["name"];
    $fingerprint = $_POST["fingerprint"];

    // $locLongitude = number_format($locLongitude, 5);
    // $locLatitude = number_format($locLatitude, 5);

    $response = array();
    
    $check    = checkLocationExisting($name, $fingerprint);
    if($check >= 1){
        $response = array(
            "error"        => 1,
            "errorMessage" => "Location Existing!"
        );
    }else{
        addLocations($locUid, $name, $fingerprint);
        $response = array(
            "error"        => 0,
            "errorMessage" => "Successfully Added!"
        );
    }
    echo jsonify($response);
});

$app->get("/location/single/data/:uid", function($uid){
    $response  = array();
    $locations = getLocationsByUid($uid);

    $response = array(
        "locUid"      => $locations->uid,
        "name"        => $locations->name,
        "fingerprint" => $locations->fingerprint,
        "status"      => $locations->status
    );

    echo jsonify($response);
});

$app->post("/locations/edit/data/:uid", function($uid){
    $response    = array();
    $name        = $_POST["name"];
    $fingerprint = $_POST["fingerprint"];
    $status      = $_POST["status"];

    // $check = checkLocationExisting($name, $locLongitude, $locLatitude);
    // if($check >= 1){
    //     $response = array(
    //         "error" => 1,
    //         "errorMessage" => "Location Existing!"
    //     );
    // }else{
        editLocations($uid, $name, $fingerprint, $status);
        $response = array(
            "error"        => 0,
            "errorMessage" => "Successfully Edited!"
        );
    // }    
    echo jsonify($response);
});
//------------------------------------------------LOCATION END------------------------------------------------------//

//------------------------------------------------TIMESHEET END------------------------------------------------------//
//------------------------------------------------RULES SETTINGS------------------------------------------------------//
$app->get("/get/rules/number/", function(){
    $response = array();
    
    $rule     = getRuless();
    foreach($rule as $rules){
        $shift      = $rules->name . ": (" . date("h:i A", strtotime($rules->start)) . " - " . date("h:i A", strtotime($rules->end)) .")";
        $response[] = array(
            "ruleUid"  => $rules->rule_uid,
            "ruleName" => $rules->rule_name,
            "day"      => $rules->day,
            "shift"    => $shift,
            "shiftUid" => $rules->shift_uid
        );
    }

    echo jsonify($response);
});

$app->get("/get/shifts/", function(){
    $response = array();
    $shifts   = getPaginatedShift();
    foreach($shifts as $shift){
        $response[] = array(
            "shiftUid"    => $shift->shift_uid,
            "shiftName"   => $shift->name,
            "shiftStart"  => $shift->start,
            "shiftEnd"    => $shift->end,
            "gracePeriod" => $shift->grace_period,
        );
    }

    echo jsonify($response);
});

$app->get("/get/rules/id/:uid", function($uid){
    $response = array();
    $rule     = getRuleByUid($uid);
    foreach($rule as $rules){
        $shift = $rules->name . ": (" . date("h:i A", strtotime($rules->start)) . " - " . date("h:i A", strtotime($rules->end)) .")";

        $response[] = array(
            "ruleUid"  => $rules->rule_uid,
            "ruleName" => $rules->rule_name,
            "day"      => $rules->day,
            "shift"    => $shift
        );
    }
    echo jsonify($response);
});

$app->get("/get/rule/data/:var", function($var){
    $param = explode(".", $var);
    $uid   = $param[0];
    $day   = $param[1];

    $rules = getRuleByUidAndDay($uid, $day);
    if($rules){
        $shift    = $rules->name . ": (" . date("h:i A", strtotime($rules->start)) . " - " . date("h:i A", strtotime($rules->end)) .")";
        $response = array(
            "ruleUid"  => $rules->rule_uid,
            "ruleName" => $rules->rule_name,
            "day"      => $rules->day,
            "shiftUid" => $rules->shift_uid,
            "shift"    => $shift,
            "status"   => $rules->status
        );
    }
    echo jsonify($response);
});

$app->post("/rule/update/", function(){
    $ruleUid      = $_POST["rule"];
    $day          = $_POST["day"];
    $shift        = $_POST["shift"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateRules($ruleUid, $day, $shift, $dateModified, $status);
});

$app->get("/emp/rules/data/:uid", function($uid){
    $response = array();
    $rules    = getRuleByEmpUid($uid);
    if($rules) {
        $response[] = array(
            "ruleUid"  => $rules->rule_assignment_uid,
            "ruleName" => $rules->rule_name,
            "status"   => $rules->status
        );
    }
    echo jsonify($response);
});

$app->get("/employee/rules/data/:uid", function($uid){
    $response = array();
    $rules    = getRulesByUid($uid);
    if($rules){
        $response = array(
            "assignRuleUid" => $rules->rule_assignment_uid,
            "ruleUid"       => $rules->rule_uid,
            "status"        => $rules->status
        );
    }

    echo jsonify($response);
});

$app->get("/get/time/rule/:uid", function($uid){
    $timeLogUid = $uid;
    $response   = array();
    $rule       = getRuleShiftByTimeLogUid($timeLogUid);
    if($rule){
        $response = array(
            "shiftUid" => $rule->shiftUid
        );
    }else{
        $response = array(
            "shiftUid" => "0"
        );
    }
    echo jsonify($response);
});

$app->post("/employee/rules/new/:uid", function($uid){
    $ruleUid      = xguid();
    $rule         = $_POST["rule"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    $response     = array();
    
    $count        = countRuleByEmpUid($uid);
    if($count >= 1){
        $errorMessage = "1 Rule Only";
        $response     = array(
            "errorStatus"  => 1,
            "errorMessage" => $errorMessage
        );
    }else{
        newEmpRule($ruleUid, $rule, $uid, $dateCreated, $dateModified); 
        $response = array(
            "errorStatus"  => 0,
            "errorMessage" => "asd" 
        );
    }

    echo jsonify($response);
});

$app->get("/check/rules/:id", function($id){
    $response = array();
    $count    = countRuleByEmpUid($id);
    if($count >= 1){
        $errorMessage = "1 Rule Only";
        $response     = array(
            "errorStatus"  => 1,
            "errorMessage" => $errorMessage
        );
    }else{
        $response = array(
            "errorStatus"  => 0,
            "errorMessage" => "asd" 
        );
    }

    echo jsonify($response);
});

$app->post("/employee/rules/update/:uid", function($uid){
    $rule         = $_POST["rule"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateRuleAssignment($uid, $rule, $dateModified, $status);
});
//------------------------------------------------RULES SETTINGS END------------------------------------------------------//

//------------------------------------------------OVERTIME SETTINGS------------------------------------------------------//
$app->get("/rate/get/data/:var", function($var){
    $param    = explode(".", $var);
    $response = array();
    $rate     = getHolidayType();
    foreach($rate as $rates){
        $response[] = array(
            "rateUid" =>$rates->holiday_type_uid,
            "name"    =>$rates->holiday_name_type,
            "code"    =>$rates->holiday_code,
            "rate"    =>$rates->rate
        );
    }

    echo jsonify($response);
});

$app->post("/rate/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $rateUid      = xguid();
    $code         = $_POST["code"];
    $rate         = $_POST["rate"];
    $name         = $_POST["name"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addRate($rateUid, $code , $name , $rate , $dateCreated , $dateModified);
});

$app->get("/rate/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param   = explode(".", $var);
    $token   = $param[1];
    $rateUid = $param[0];
    $rate    = getRatesByUid($rateUid);
    if($rate){

        $response = array(
            "rateUid" => $rate->holiday_type_uid,
            "code"    => $rate->holiday_code,
            "name"    => $rate->holiday_name_type,
            "rate"    => $rate->rate,
            "status"  => $rate->status 
        );
    }else{
        $response = array(
            "rateUid" => "",
            "code"    => "",
            "name"    => "",
            "rate"    => "",
            "status"  => ""
        );
    }

    echo jsonify($response);
});

$app->post("/rate/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $rateUid      = $param[0];
    $code         = $_POST['code'];
    $name         = $_POST['name'];
    $rate         = $_POST['rate'];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];
    
    $rates        = getRatesByUid($rateUid);

    if($name != $rates->holiday_name_type OR $code != $rates->holiday_code OR $rates != $rates->rate OR $status != $rates->status){
        updateRateById($rateUid, $code, $name , $rate, $dateModified , $status);
        if(rateCount($name)>1){
            updateRateById($rateUid, $rates->holiday_code ,$rates->holiday_name_type, $rates->rate, $dateModified , $status);
        }
    }
});
//------------------------------------------------OVERTIME SETTINGS END------------------------------------------------------//

//------------------------------------------------LOANS--------------------------------------------------------------//
$app->get("/loans/get/data/:var", function($var){
    $param    = explode(".", $var);
    $response = array();
    $loan     = getLoansDetails();
    foreach($loan as $loans){
        $response[] = array(
            "loanUid"      => $loans->loan_uid,
            "loanName"     => $loans->name,
            "loanInterest" => $loans->interest
        );
    }

    echo jsonify($response);
});

$app->post("/loans/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $loansUid     = xguid();
    $loanName     = $_POST['loanName'];
    $loanInterest = $_POST['loanInterest'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addLoans($loansUid , $loanName, $loanInterest , $dateCreated , $dateModified);
});

$app->get("/loans/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param   = explode(".", $var);
    $token   = $param[1];
    
    $loanUid = $param[0];
    
    $loan    = getLoanByUid($loanUid);
    if($loan){

        $response = array(
            "loanUid"      => $loan->loan_uid,
            "loanName"     => $loan->name,
            "loanInterest" => $loan->interest,
            "status"       => $loan->status 
        );
    }else{
        $response = array(
            "loanUid"      => "",
            "loanName"     => "",
            "loanInterest" => "",
            "status"       => ""
        );
    }

    echo jsonify($response);
});

$app->post("/loans/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $loansUid     = $param[0];
    $loanName     = $_POST['loanName'];
    $loanInterest = $_POST['loanInterest'];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];
    
    $loans        = getLoanByUid($loansUid);

    if($loanName != $loans->name OR $loanInterest != $loans->interest OR $status != $loans->status){
        updateLoansById($loansUid , $loanName , $loanInterest , $dateModified , $status);
        if(loanCount($loanName)>1){
            updateLoansById($loansUid , $loans->name , $loans->interest , $dateModified , $status);
        }
    }
});

$app->get("/loan/type/get/", function(){
    $response = array();
    $loan     = getLoansDetails();
    foreach($loan as $loans){
        $response[] = array(
            "loanUid"      => $loans->loan_uid,
            "loanName"     => $loans->name,
            "loanInterest" => $loans->interest
        );
    }

    echo jsonify($response);
});

$app->get("/employee/loans/get/:var" , function($var){
    //parameter: token
    $param    = explode(".", $var);
    $empUid   = $param[0];
    $token    = $param[1];
    $response = array();
    $empLoans = getLoansByEmpUid($empUid);
    foreach ($empLoans as $loans) {
        $response[] = array(
            "loanName"     => $loans->name,
            "loanInterest" => $loans->interest,
            "loanAmount"   => $loans->amount,
            "loanUid"      => $loans->emp_loans_uid
        );
    }
    echo jsonify($response);
});

$app->post("/loans/edit/:uid", function($uid){
    $loanType     = $_POST["loanName"];
    $loanAmount   = $_POST["loanAmount"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateEmpLoan($uid, $loanType, $loanAmount, $dateModified, $status);
});

$app->get("/employee/loans/data/:var" , function($var){
    $param    = explode(".", $var);
    $uid      = $param[0];
    $token    = $param[1];
    $response = array();
    $loans    = getEmpLoanByUid($uid);
    // print_r($loans);
    if($loans) {
        $response = array(
            "status"     => $loans["status"],
            "loanType"   => $loans["loan_uid"],
            "loanAmount" => $loans["amount"],
            "loanUid"    => $loans["emp_loans_uid"]
        );
    }
    echo jsonify($response);
});

$app->post("/loan/type/new/:var" , function($var){
    //parameter: term.start.size.token
    $param             = explode(".", $var);
    
    $loanDeductionsUid = xguid();
    $empUid            = $param[0];
    $loanType          = $_POST['loanType'];
    $loanAmount        = $_POST['loanAmount'];
    $dateCreated       = date("Y-m-d H:i:s");
    $dateModified      = date("Y-m-d H:i:s");

    addEmpLoans($loanDeductionsUid, $empUid , $loanType, $loanAmount , $dateCreated , $dateModified);
});

$app->get("/loans/details/get/edit/:var" , function($var){
    //parameter: token
    $param    = explode(".", $var);
    $loanUid  = $param[0];
    $response = array();
    $loan     = getLoanDeductionByUid($loanUid);
    if($loan){
        $response = array(
        "loanName"          => $loan->name,
        "loanAmount"        => $loan->amount,
        "status"            => $loan->status,
        "loanDeductionsUid" => $loan->loan_deductions_uid
    );
    }
    
    echo jsonify($response);
});
//------------------------------------------------END OF LOANS--------------------------------------------------------------//

//------------------------------------------------USERS AUTH------------------------------------------------------//

$app->post("/users/verified", function() {
	$uid = $_POST["username"];
	if($uid) {
		$userId  = getUserId($uid);
		if($userId) {
			$response = array(
				"verified" => 1
			);
		}
		else {
			$response = array(
				"verified" => 0
			);
		}
	}
	else{
		$response = array(
			"verified" => 0
		);
	}
	echo jsonify($response);
});

$app->post("/users/authenticate", function () {
    $username = $_POST["username"];
    $userId   = getUserId($username);
    $uxPassword = null;
	if(!$userId){
        $response = array(
            "verified" => 0
        );
    }else{
        $encryption = "AES-256-CBC";
        //$uxPassword = sha1(Base32::decode($_POST["password"]));
		$uxPassword = sha1(base64_decode($_POST["password"].salt()));
        
        $secretKey  = sha1($username . $uxPassword);
        $uniqueKey  = getUniqueKey($userId);
        if ($uniqueKey == null) {
            $ivSize    = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $uniqueKey = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        }
        $password = openssl_encrypt($uxPassword, $encryption, $secretKey, 0, $uniqueKey);
        $response = array(
            "Uid"        => null,
            "Token"      => null,
            "UserId"     => null,
            "EmployeeId" => null,
            "Type"       => null,
            "Username"   => null,
            "location"   => null,
            "verified"   => 0
        );
        // echo $_POST["password"]." = ". Base32::decode($_POST["password"]) ."<br/>";
           
		if(validUserAccount($username, $uxPassword)) {
				//$response["Type"]       = getUserType($username);
                //$response["UserId"]     = $userId;
                //$response["EmployeeId"] = getUserByUid($userId)->emp_uid;            
                //$response["Uid"]        = xguid();
                //$response["Username"]   = getUserByUid($userId)->emp_uid;
                //$response["Token"]      = xguid();
                //$response["verified"]   = 1;
                //$response["location"]   = getCurrentIpAddress($userId);
                //logToken(xguid(), $response["Token"], $userId, date("Y-m-d H:i:s"), 1);
                //logTokenReferrer(xguid(), $response["Token"], $_SERVER['REMOTE_ADDR'], date("Y-m-d H:i:s"));
				
				$token = xguid();
				
				$response = array(
					"Type" => getUserType($username),
					"UserId" => $userId,
					"EmployeeId" =>  getUserByUid($userId)->emp_uid,
					"Uid" => xguid(),
					"Token" => $token,
					"verified" => 1,
					"location" => getCurrentIpAddress($userId),
					"Username" => getUserByUid($userId)->emp_uid //$userId
				);
				
				logToken(xguid(), $token, $userId, date("Y-m-d H:i:s"), 1);
                logTokenReferrer(xguid(), $token, $_SERVER['REMOTE_ADDR'], date("Y-m-d H:i:s"));
        }
		else {
            $response["verified"] = 2; //$uxPassword;
        }
    }
    
    echo jsonify($response);

    // deactivateUserTokens($userId);
});

$app->post("/system/tokens/verify", function() {
    $token    = $_POST["token"];
    $user     = getUserFromToken($token);
    $emp      = getEmpUidByUserId($user);
    $location = getCurrentIpAddress($user);
    $type     = getEmployeeType($emp);
    $verified = 0;
    if (validToken($token)) {
        $verified = 1;
    }
    $response = array(
        "verified" => $verified,
        "location" => $location,
        "type"     => $type
    );
    echo jsonify($response);
});

$app->post("/system/tokens/deactivate/:var", function($var) {
    $param = explode(".", $var);
    if (validToken($token) && count($param) === 1) {
        $token = $_POST["token"];
        $user  = getUserFromToken($token);
        deactivateUserTokens($user);
    }
});

$app->post("/users/logout/:var", function($var) {
    $param = explode(".", $var);
    $token = $param[0];
    if (validToken($token) && count($param) === 1) {
        $userId = $_POST["user"];
        deactivateUserTokens($token);
    }
});

/*---------------------------------Late Level--------------------------------------*/
$app->get("/late/get/:var", function($var){
    $param    = explode(".", $var);
    $token    = $param[0];
    $response = array();

    $a = getLates();
    foreach($a as $late){
        $response[] = array(
            "name"     => $late->name,
            "duration" => $late->duration,
            "lateUid"  => $late->late_uid
        );
    }//end of getLates function

    echo jsonify($response);
});

$app->post("/late/new/:var", function($var){
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $lateUid      = xguid();
    $name         = $_POST["name"];
    $duration     = $_POST["duration"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newLate($lateUid, $name, $duration, $dateCreated, $dateModified);
});

$app->get("/late/details/get/:var", function($var){
    $param    = explode(".", $var);
    $token    = $param[1];
    
    $uid      = $param[0];
    $response = array();
    
    $a        = getLateByUid($uid);
    if($a){
        $response = array(
            "name"     => $a->name,
            "duration" => $a->duration,
            "status"   => $a->status
        );
    }//end of getLateByUid Function

    echo jsonify($response);
});

$app->post("/late/update/:var", function($var){
    $param        = explode(".", $var);
    $uid          = $param[0];
    $token        = $param[1];
    
    $name         = $_POST["name"];
    $duration     = $_POST["duration"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateLate($uid, $name, $duration, $dateModified, $status);
});
/*---------------------------------Late Level End--------------------------------------*/

/*---------------------------------Degree Level--------------------------------------*/

$app->get("/degree/level/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $response = array();

    $degreeLevels = getPaginatedEducationLevel();
    
    foreach ($degreeLevels as $degreeLevel) {
        $response[] = array(
            "educationLevelUid" => $degreeLevel->education_level_uid,
            "name"              => $degreeLevel->level_name
        );
    }
    echo jsonify($response);
});

$app->post("/degree/level/new/:var" , function($var){
    //parameter: term.start.size.token
    $param          = explode(".", $var);
    $token          = $param[0];
    
    $degreeLevelUid = xguid();
    $name           = $_POST['name'];
    $dateCreated    = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");

    newDegreeLevel($degreeLevelUid , $name , $dateCreated , $dateModified);
});

$app->get("/degree/level/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $token    = $param[1];
    
    $uid      = $param[0];
    $response = array();

    $degreeLevel = getDegreeLevelByUid($uid);
    if($degreeLevel){
        $response = array(
            "name"   => $degreeLevel->level_name,
            "status" => $degreeLevel->status
        );
    }

    echo jsonify($response);
});

$app->post("/degree/level/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $uid          = $param[0];
    $token        = $param[1];
    
    
    $name         = $_POST['name'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");

    $degreeLevel = getDegreeLevelByUid($uid);
    if($degreeLevel->level_name != $name OR $degreeLevel->status != $status){
        updateDegreeLevel($uid , $name , $dateModified , $status);
        if(degreeLevelCount($name) > 1){
            updateDegreeLevel($degreeLevel->education_level_uid , $degreeLevel->level_name , $dateModified , $degreeLevel->status);
        }
    }
});

/*---------------------------------Degree Level End--------------------------------------*/

/*---------------------------------Degree Level--------------------------------------*/

$app->get("/skill/type/get/:var" , function($var){
    //parameter: term.start.size.token
    $param      = explode(".", $var);
    $response   = array();
    
    $skillTypes = getPaginatedSkillType();
    
    foreach ($skillTypes as $skillType) {
        $response[] = array(
            "skillUid" => $skillType->skill_uid,
            "name"     => $skillType->skill_type
        );
    }
    echo jsonify($response);
});

$app->post("/skill/type/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $skillUid     = xguid();
    $name         = $_POST['name'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newSkillType($skillUid , $name , $dateCreated , $dateModified);
});

$app->get("/skill/type/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $token    = $param[1];
    
    $uid      = $param[0];
    $response = array();
    
    $skill    = getSkillTypeByUid($uid);
    if($skill){
        $response = array(
            "name"   => $skill->skill_type,
            "status" => $skill->status
        );
    }

    echo jsonify($response);
});

$app->post("/skill/type/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $uid          = $param[0];
    $token        = $param[1];
    
    
    $name         = $_POST['name'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");
    
    $skill        = getSkillTypeByUid($uid);
    if($skill->skill_type != $name OR $skill->status != $status){
        updateSkillType($uid , $name , $dateModified , $status);
        if(skillTypeCount($name) > 1){
            updateSkillType($skill->skill_uid , $skill->skill_type , $dateModified , $skill->status);
        }
    }
});

/*---------------------------------Degree Level End--------------------------------------*/

/*---------------------------------Languages--------------------------------------*/

$app->get("/languages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param     = explode(".", $var);
    $response  = array();
    
    $languages = getPaginatedLanguages();
    
    foreach ($languages as $language) {
        $response[] = array(
            "languageUid" => $language->languages_uid,
            "name"        => $language->language_name
        );
    }
    echo jsonify($response);
});

$app->post("/language/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $uid          = xguid();
    $name         = $_POST['name'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newLanguage($uid , $name , $dateCreated , $dateModified);
});

$app->get("/language/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $token    = $param[1];
    
    $uid      = $param[0];
    $response = array();
    
    $language = getLanguageByUid($uid);
    if($language){
        $response = array(
            "name"   => $language->language_name,
            "status" => $language->status
        );
    }

    echo jsonify($response);
});

$app->post("/language/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $uid          = $param[0];
    $token        = $param[1];
    
    
    $name         = $_POST['name'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");

    $language = getLanguageByUid($uid);
    if($language->language_name != $name OR $language->status != $status){
        updateLanguage($uid , $name , $dateModified , $status);
        if(languagesCount($name) > 1){
            updateLanguage($language->languages_uid , $language->language_name , $dateModified , $language->status);
        }
    }
});

/*---------------------------------Languages End--------------------------------------*/
/*---------------------------------Licenses--------------------------------------*/

$app->get("/license/type/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $response = array();
    
    $licenses = getPaginatedLicensesType();
    
    foreach ($licenses as $license) {
        $response[] = array(
            "licenseUid" => $license->license_uid,
            "name"       => $license->license_name
        );
    }
    echo jsonify($response);
});

$app->post("/license/type/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $uid          = xguid();
    $name         = $_POST['name'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newLicenseType($uid , $name , $dateCreated , $dateModified);
});

$app->get("/license/type/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $token    = $param[1];
    
    $uid      = $param[0];
    $response = array();
    
    $license  = getLicenseTypeByUid($uid);
    if($license){
        $response = array(
            "name"   => $license->license_name,
            "status" => $license->status
        );
    }

    echo jsonify($response);
});

$app->post("/license/type/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $uid          = $param[0];
    $token        = $param[1];
    
    
    $name         = $_POST['name'];
    $status       = $_POST['status'];
    $dateModified = date("Y-m-d H:i:s");
    
    $license      = getLicenseTypeByUid($uid);
    if($license->license_name != $name OR $license->status != $status){
        updateLicenseType($uid , $name , $dateModified , $status);
        if(licenseTypeCount($name) > 1){
            updateLicenseType($license->license_uid , $license->license_name , $dateModified , $license->status);
        }
    }
});

/*---------------------------------Licenses End--------------------------------------*/

/*---------------------------------SSS--------------------------------------*/
$app->get("/sss/get/", function(){

    $response = array();
    $sss      = getSSS();
    foreach ($sss as $getsss) {
        $response[] = array(
            "rangeOfComp"    => $getsss->rangeOfComp,
            "rangeOfCompEnd" => $getsss->rangeOfCompEnd,
            "basicSalary"    => $getsss->basic_salary,
            "sssEr"          => $getsss->sssEr,
            "sssEe"          => $getsss->sssEe,
            "sssTotal"       => $getsss->sssTotal
        );
    }

    echo jsonify($response);
});

/*---------------------------------SSS End--------------------------------------*/
/*---------------------------------TAX--------------------------------------*/
$app->get("/tax/get/:var", function($var){
    $param        = explode(".", $var);
    $frequencyUid = $param[0];
    $response     = array();
    $tax          = getAllTax($frequencyUid);
    foreach ($tax as $taxx) {
        $response[] = array(
            "taxStatus" => $taxx->no_dep_status,
            "taxOne"    => number_format($taxx->no_dep_1, 2),
            "taxTwo"    => number_format($taxx->no_dep_2, 2),
            "taxThree"  => number_format($taxx->no_dep_3, 2),
            "taxFour"   => number_format($taxx->no_dep_4, 2),
            "taxFive"   => number_format($taxx->no_dep_5, 2),
            "taxSix"    => number_format($taxx->no_dep_6, 2),
            "taxSeven"  => number_format($taxx->no_dep_7, 2),
            "taxEight"  => number_format($taxx->no_dep_8, 2)
        );
    }

    echo jsonify($response);
});

$app->get("/tax/get/forEdit/:var", function($var){
    $param        = explode(".", $var);
    $frequencyUid = $param[0];
    $response     = array();
    $tax          = getAllTax($frequencyUid);
    foreach ($tax as $taxx) {
        $response[] = array(
            "taxStatus" => $taxx->no_dep_status,
            "taxOne"    => $taxx->no_dep_1,
            "taxTwo"    => $taxx->no_dep_2,
            "taxThree"  => $taxx->no_dep_3,
            "taxFour"   => $taxx->no_dep_4,
            "taxFive"   => $taxx->no_dep_5,
            "taxSix"    => $taxx->no_dep_6,
            "taxSeven"  => $taxx->no_dep_7,
            "taxEight"  => $taxx->no_dep_8
        );
    }

    echo jsonify($response);
});

$app->get("/tax/exemp/:var", function($var){
    $param        = explode(".", $var);
    $frequencyUid = $param[0];
    $response     = array();
    $exemp        = getExemption($frequencyUid);
    foreach ($exemp as $ex) {
        $response[] = array(
            "exemption" => $ex->exemption,
            "exStatus"  => $ex->status,
        );
    }

    echo jsonify($response);
});

// $app->post("/tax/ex/update/", function(){
//     // $param = explode(".", $var);
//     $frequencyUid = $_POST['frequencyUid'];
//     $i = $_POST['i'];
//     $taxExemp = $_POST['taxExemp'];
//     $taxStat = $_POST['taxStat'];

//     $i++;
//     updateExemption($i, $frequencyUid, $taxStat, $taxExemp);
//     echo $i;
// });


$app->post("/tax/ex/update/", function(){
    // $param = explode(".", $var);
    $frequencyUid = $_POST['frequencyUid'];    
    $items        = $_POST['items'];
    $response     = array();
    $exempts      = getExemptionUid($frequencyUid);

    $i;

    for($i=0; $i<8; $i++) {
        //echo json_encode($exempts[$i]["exemptionUid"] ."<br/>". $items[$i]["taxExemp"] ."<br/>". $items[$i]["taxStatus"]);  
        updateExemption($exempts[$i]["exemptionUid"], $items[$i]["taxExemp"], $items[$i]["taxStatus"]);
    }    
});

$app->post("/tax/ex/update/dailies/", function(){
    $i        = $_POST['i'];
    $taxExemp = $_POST['taxExemp'];
    $taxStat  = $_POST['taxStat'];

    $i++;
    updateExemptionDailies($i, $taxStat, $taxExemp);
    echo $i;
});

$app->post("/tax/taxx/update/", function(){
    $frequencyUid = $_POST['frequencyUid'];
    $items        = $_POST['items'];
    $response     = array();
    $taxx         = getTaxByFreqUid($frequencyUid);

    $i;

    for($i=0; $i<6; $i++){
        // updateTax($i, $frequencyUid ,$taxStatt, $taxOne, $taxTwo, $taxThree, $taxFour, $taxFive, $taxSix, $taxSeven, $taxEight);
        updateTax($taxx[$i]["tax_uid"], $items[$i]["taxStatt"], $items[$i]["taxOne"], $items[$i]["taxTwo"], $items[$i]["taxThree"], $items[$i]["taxFour"], $items[$i]["taxFive"], $items[$i]["taxSix"], $items[$i]["taxSeven"], $items[$i]["taxEight"]);
        // echo json_decode($taxx[$i]["tax_uid"]); 
    }
});

$app->post("/sss/update/", function(){
    $i         = $_POST['i'];
    $sssStart  = $_POST['sssStart'];
    $sssEnd    = $_POST['sssEnd'];
    $sssSalary = $_POST['sssSalary'];
    $sssEr     = $_POST['sssEr'];
    $sssEe     = $_POST['sssEe'];
    $sssTotal  = $_POST['sssTotal'];
        $i++;
        updateSSS($i , $sssStart, $sssEnd, $sssSalary, $sssEr, $sssEe, $sssTotal);

    echo $i;
});

/*---------------------------------TAX End--------------------------------------*/

/*---------------------------------philhealth--------------------------------------*/
$app->get("/philhealth/get/", function(){

    $response   = array();
    $philhealth = getPhilhealth();
    foreach ($philhealth as $phil) {
        $response[] = array(
            "salaryBracket"       => $phil->id,
            "salaryRange"         => $phil->salaryRange,
            "salaryRangeEnd"      => $phil->salaryRangeEnd,
            "salaryBase"          => $phil->salaryBase,
            "employeeShare"       => $phil->employeeShare,
            "employerShare"       => $phil->employerShare,
            "totalMonthlyPremium" => $phil->totalMonthlyPremium
        );
    }

    echo jsonify($response);
});

$app->post("/philhealth/update/", function(){
    $i                   = $_POST['i'];
    $salaryRange         = $_POST['salaryRange'];
    $salaryRangeEnd      = $_POST['salaryRangeEnd'];
    $salaryBase          = $_POST['salaryBase'];
    $employeeShare       = $_POST['employeeShare'];
    $employerShare       = $_POST['employerShare'];
    $totalMonthlyPremium = $_POST['totalMonthlyPremium'];

        $i++;
        updatePhilhealth($i , $salaryRange, $salaryRangeEnd, $salaryBase, $employeeShare, $employerShare, $totalMonthlyPremium);

    echo $i;
});

/*---------------------------------philhealth End--------------------------------------*/

/*---------------------------------pagibig--------------------------------------*/
$app->get("/pagibig/get/", function(){

    $response = array();
    $pagibig  = getPagibig();
    foreach ($pagibig as $love) {
        $response[] = array(
            "pagibigGrossPayRange"    => $love->pagibigGrossPayRange,
            "pagibigGrossPayRangeEnd" => $love->pagibigGrossPayRangeEnd,
            "pagibigEmployer"         => $love->pagibigEmployer,
            "pagibigEmployee"         => $love->pagibigEmployee,
            "pagibigTotal"            => $love->pagibigTotal
        );
    }

    echo jsonify($response);
});

$app->post("/pagibig/update/new/", function(){
    $i        = $_POST['i'];
    $pgGPR    = $_POST['pagibigGPR'];
    $pgGPREnd = $_POST['pagibigGPREnd'];
    $pgEmpr   = $_POST['pagibigEmpr'];
    $pgEmp    = $_POST['pagibigEmp'];
    $pgTotal  = $_POST['pagibigTotal'];
        $i++;
        updatePagibig($i , $pgGPR, $pgGPREnd, $pgEmpr, $pgEmp, $pgTotal);

    echo $i;
});

/*---------------------------------pagibig End--------------------------------------*/

/*---------------------------------Benefits--------------------------------------*/
$app->get("/benefits/pages/get/:var", function($var){
    $token    = $var;
    
    $benefits = getBenefitsPages();
    $response = array();

    foreach($benefits as $benefit){
        $lastname   = utf8_decode($benefit["lastname"]);
        $middlename = utf8_decode($benefit["middlename"]);
        $firstname  = utf8_decode($benefit["firstname"]);

        $response[] = array(
            "benefitUid" => $benefit->emp_benefit_uid,
            "emp"        => $lastname .", ". $firstname ." ". $middlename,
            "empSss"     => number_format($benefit->emp_sss, 2),
            "empPhil"    => number_format($benefit->emp_philhealth, 2),
            "empHDMF"    => number_format($benefit->emp_pagibig, 2)
        );
    }
    echo jsonify($response);
});

$app->get("/benefits/data/:uid", function($uid){

    $benefit  = getBenefitsByUid($uid);
    $response = array();

    if($benefit){
        $response = array(
            "benefitUid" => $benefit->emp_benefit_uid,
            "empSss"     => $benefit->emp_sss,
            "empPhil"    => $benefit->emp_philhealth,
            "empHDMF"    => $benefit->emp_pagibig,
            "status"     => $benefit->status
        );
    }
    echo jsonify($response);
});

$app->post("/benefits/add/:var", function($var){
    $token        = $var;
    $benefitUid   = xguid();
    $emp          = $_POST["emp"];
    $sss          = $_POST["sss"];
    $phil         = $_POST["phil"];
    $hdmf         = $_POST["hdmf"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $check        = checkUserHasBenefits($emp);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        setEmpBenefit($benefitUid, $emp, $sss, $phil, $hdmf, $dateCreated, $dateModified);
        $response = array(
            "prompt" => 0
        );
    }
    echo jsonify($response);
});

$app->post("/benefits/update/:uid", function($uid){
    $sss          = $_POST["sss"];
    $phil         = $_POST["phil"];
    $hdmf         = $_POST["hdmf"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateEmpBenefit($uid, $sss, $phil, $hdmf, $dateModified, $status);
});

/*---------------------------------Benefits End--------------------------------------*/

/*---------------------------------paygrade--------------------------------------*/ 
$app->post("/paygrade/add/", function(){
    $paygradeUid  = xguid();
    $paygradeName = $_POST["paygradeName"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addPaygrade($paygradeUid, $paygradeName, $dateCreated, $dateModified);
});

$app->get("/paygrade/level/get/", function(){
    $response = array();
    $paygrade = getPayGrade();
    foreach ($paygrade as $pg) {
        $response[] = array(
            "paygradeUid"  => $pg->paygrade_uid,
            "paygradeName" => $pg->paygrade_name
        );
    }

    echo jsonify($response);
});

$app->post("/paygrade/level/add/", function(){
    $paygradeLevelUid = xguid();
    $pgUid            = $_POST["pgUid"];
    $pgLevelName      = $_POST["pgLevelName"];
    $pgLevelMin       = $_POST["pgLevelMin"];
    $pgLevelMid       = $_POST["pgLevelMid"];
    $pgLevelMax       = $_POST["pgLevelMax"];
    $pgDateCreated    = date("Y-m-d H:i:s");
    $pgDateModified   = date("Y-m-d H:i:s");

    addPaygradeLevel($paygradeLevelUid, $pgUid, $pgLevelName, $pgLevelMin, $pgLevelMid, $pgLevelMax, $pgDateCreated, $pgDateModified);
});

$app->get("/paygrade/view/", function(){
    $response     = array();
    $paygradeview = paygradeView();
    foreach ($paygradeview as $pgv) {
        $response[] = array(
            "paygradeName"   => $pgv->paygrade_name,
            "pgLevelName"    => $pgv->pgLevelName,
            "pgLevelMinimum" => $pgv->pgLevelMinimum,
            "pgLevelMid"     => $pgv->pgLevelMid,
            "pgLevelMaximum" => $pgv->pgLevelMaximum
        );
    }

    echo jsonify($response);
});

/*---------------------------------paygrade End--------------------------------------*/ 

$app->get("/get/entitlement/:var" , function($var){
    $param = explode(".", $var);
    $token = $param[0];

    $response          = array();
    $leaveEntitlements = getLeaveEntitlementByUid();
    foreach ($leaveEntitlements as $leaveEntitlement) {
        $response[] = array(
            "leaveEntitlementUid" => $leaveEntitlement->leave_entitlement_uid,
            "firstname"           => utf8_decode($leaveEntitlement->firstname),
            "middlename"          => utf8_decode($leaveEntitlement->middlename),
            "lastname"            => utf8_decode($leaveEntitlement->lastname),
            "leaveName"           => $leaveEntitlement->leave_name,
            "from"                => $leaveEntitlement->from_period,
            "to"                  => $leaveEntitlement->to_period,
            "noDays"              => $leaveEntitlement->totaldays
        );
    }
    echo jsonify($response);
});

$app->get("/get/leave/request/:var" , function($var){
    $param = explode(".", $var);
    $token = $param[0];

    $response      = array();
    $leaveRequests = getPaginatedLeaveRequests();
    foreach ($leaveRequests as $leaveRequest) {
        $response[] = array(
            "leaveCode"     =>$leaveRequest->leave_code,
            "empNo"         =>$leaveRequest->username,
            "leaveUid"      => $leaveRequest->leave_uid,
            "startDate"     => date("M d, Y", strtotime($leaveRequest->start_date)),
            "endDate"       => date("M d, Y", strtotime($leaveRequest->end_date)),
            "firstname"     => utf8_decode($leaveRequest->firstname),
            "middlename"    => utf8_decode($leaveRequest->middlename),
            "lastname"      => utf8_decode($leaveRequest->lastname),
            // "noDays"     => $leaveRequest->no_days,
            "reason"        => $leaveRequest->reason,
            "leaveName"     => $leaveRequest->leave_name,
            "requestStatus" => $leaveRequest->leave_request_status,
            "certBy"        =>  $leaveRequest->cert_by,
            "appBy"         =>  $leaveRequest->appr_by
        );
    }
    echo jsonify($response);
});

$app->get("/get/employee/leave/requests/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $emp       = $param[2];

    $response      = array();
    $leaveRequests = getEmployeeLeaveRequestsByDateRange($startDate, $endDate, $emp);

    foreach($leaveRequests as $leave){
        $response[] = array(
            "code"           =>$leave->leave_code,
            "employee_no"    =>$leave->username,
            "uid"            => $leave->leave_uid,
            "from"           => date("M d, Y", strtotime($leave->start_date)),
            "to"             => date("M d, Y", strtotime($leave->end_date)),
            "firstname"      => utf8_decode($leave->firstname),
            "middlename"     => utf8_decode($leave->middlename),
            "lastname"       => utf8_decode($leave->lastname),
            // "noDays"      => $leaveRequest->no_days,
            "reason"         => $leave->reason,
            "name"           => $leave->leave_name,
            "request_status" => $leave->leave_request_status,
            "certBy"         =>  $leave->cert_by,
            "date_created"         =>  date("Y-m-d h:i A", strtotime($leave->date_created)),
            "date_modified"         =>  date("Y-m-d h:i A", strtotime($leave->date_modified)),
            "appBy"          =>  $leave->appr_by
        );
    }
    echo jsonify($response);
});

$app->get("/get/leave/request/date/:var", function($var){
    $param         = explode(".", $var);
    $startDate     = $param[0];
    $endDate       = $param[1];
    $reqStatus     = $param[2];
    
    $response      = array();
    $leaveRequests = getLeaveRequestsByStatusAndDateRange($startDate, $endDate, $reqStatus);

    foreach($leaveRequests as $leave){
        $a = getEmployeeDetailsByUid($leave->emp_uid);
        if($a){
            $lastnames = utf8_decode($a->firstname) . "_" . " ";
            $words = explode("_", $lastnames);
            $name = "";

            foreach ($words as $w) {
              $name .= $w[0];
            }

            $lastname = $name . ". " . utf8_decode($a->lastname);
        }//end of getEmployeeDetailsByUid Function
        $response[] = array(
            "leaveCode"     => $leave->leave_code,
            "empNo"         => $leave->username,
            "leaveUid"      => $leave->leave_uid,
            "startDate"     => date("M d, Y", strtotime($leave->start_date)),
            "endDate"       => date("M d, Y", strtotime($leave->end_date)),
            "firstname"     => utf8_decode($leave->firstname),
            "middlename"    => utf8_decode($leave->middlename),
            "lastname"      => utf8_decode($leave->lastname),
            "name" => $lastname,
            // "noDays"     => $leaveRequest->no_days,
            "reason"        => $leave->reason,
            "leaveName"     => $leave->leave_name,
            "leaveType"     => $leave->leave_code,
            "requestStatus" => $leave->leave_request_status,
            "certBy"        =>  $leave->cert_by,
            "date_created"        =>  date("m-d-y h:i A", strtotime($leave->date_created)),
            "date_modified"        =>  date("m-d-y h:i A", strtotime($leave->date_modified)),
            "appBy"         =>  $leave->appr_by
        );
    }
    echo jsonify($response);
});

$app->get("/get/leave/details/:uid", function($uid){
    $response = array();
    $leave    = getLeaveRequestsByUid($uid);
    if($leave){
        $response = array(
            "uid"            => $leave->leave_uid,
            "from"           => $leave->start_date,
            "to"             => $leave->end_date,
            "firstname"      => utf8_decode($leave->firstname),
            "middlename"     => utf8_decode($leave->middlename),
            "lastname"       => utf8_decode($leave->lastname),
            "noDays"         => $leave->no_days,
            "name"           => $leave->leave_name,
            "status"         => $leave->status,
            "request_status" => $leave->leave_request_status
        );
    }

    echo jsonify($response);
});

$app->get("/get/leave/types/:var", function($var){
    $param = explode(".", $var);
    $token = $param[0];
    
    $x     = getPaginatedLeaveTypes();
    foreach($x as $leave){
        $response[] = array(
            "leaveUid"  => $leave->leave_uid,
            "leaveName" => $leave->leave_name
        );
    }

    echo jsonify($response);
});

$app->post("/leave/type/new/:var", function($var){
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $leaveUid     = xguid();
    $name         = $_POST["name"];
    $code         = $_POST["code"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addLeaveType($leaveUid, $code ,$name ,$dateCreated, $dateModified);
});

$app->get("/remove/leave/request/:uid", function($uid){
    $status = 0;

    removeLeaveRequestByUid($uid, $status);

    $response = array(
        "prompt" => 1
    );

    echo jsonify($response);
});

$app->post("/update/leave/request/:uid", function($uid){
    $leaveStart         = $_POST["leaveStart"];
    $leaveEnd           = $_POST["leaveEnd"];
    $status             = $_POST["status"];
    $admin              = $_POST["admin"];
    $leaveStatus        = $_POST["leaveStatus"];
    $dateModified       = date("Y-m-d H:i:s");
    $notifStatus        = 1;
    
    $leaveStart         = date("Y-m-d", strtotime($leaveStart));
    $leaveEnd           = date("Y-m-d", strtotime($leaveEnd));
    
    $leaveData          = getLeaveRequestsDataByUid($uid);
    $leaveEmpUid        = $leaveData["emp_uid"];
    $empNumber          = getEmloyeeNumberByEmpUid($leaveEmpUid);
    $leaveStartDate     = $leaveData["start_date"];
    $leaveEndDate       = $leaveData["end_date"];
    $leaveReason        = $leaveData["reason"];
    $leaveRequestStatus = $leaveData["leave_request_status"];
    
    $employee           = getEmployeeDetailsByUid($admin);
    // var_dump($emp); 
    if($employee){
        $lastname   = $employee->lastname;
        $firstname  = $employee->firstname;
        $middlename = $employee->middlename;
        $fullname   = $firstname . " " . $middlename . " " . $lastname;
    }else{
        $fullname = "";
    }

    function getInitials($fullname){
        $words=explode(" ",$fullname);
        $inits='';
        foreach($words as $word){
            $inits.=strtoupper(substr($word,0,1));
        }
        return $inits;  
    }

    $user = getInitials($fullname);

    if($leaveStatus == "Certified"){
        $user1 = $user;
        $user2 = "";
        updateLeaveByUid($uid, $leaveStart, $leaveEnd, $leaveStatus, $user1, $user2 ,$dateModified, $status);
    }else if($leaveStatus == "Approved"){
        $user2 = $user;
        $user1 = "";
        updateLeaveByUid($uid, $leaveStart, $leaveEnd, $leaveStatus, $user1, $user2 ,$dateModified, $status);
    }else{
        $user1 = "";
        $user2 = $user;
        updateLeaveByUid($uid, $leaveStart, $leaveEnd, $leaveStatus, $user1, $user2 ,$dateModified, $status);
    }
});

$app->post("/set/emp/leave/count/", function(){
    $response      = array();
    $leaveCountUid = xguid();
    $emp           = $_POST["emp"];
    $sL            = $_POST["sL"];
    $bL            = $_POST["bL"];
    $brL           = $_POST["brL"];
    $vL            = $_POST["vL"];
    $mL            = $_POST["mL"];
    $pL            = $_POST["pL"];
    $dateCreated   = date("Y-m-d H:i:s");
    $dateModified  = date("Y-m-d H:i:s");
    
    $check         = checkEmpLeaveCountByEmpUid($emp);
    if($check){
        $response = array(
            "prompt" => 1
        );
    }else{
        setEmpLeaveCounts($leaveCountUid, $emp, $sL, $bL, $brL, $vL, $mL, $pL, $dateCreated, $dateModified);
        $response = array(
            "prompt" => 0
        );
    }
    echo jsonify($response);
});

$app->get("/get/emp/leave/counts/pages/", function(){
    $x = leaveCounts();
    echo jsonify($x);
});

$app->get("/get/emp/leave/counts/employee/:uid", function($uid){
    $response = array();
    
    $data     = getEmpLeaveCountByUid($uid);
    if($data){
        $response = array(
            "leaveCountUid" => $data->emp_leave_count_uid,
            "SL"            => $data->SL,
            "BL"            => $data->BL,
            "BV"            => $data->BV,
            "VL"            => $data->VL,
            "ML"            => $data->ML,
            "PL"            => $data->PL,
            "status"        => $data->status
        );
    }
    echo jsonify($response);
});

$app->post("/update/emp/leave/counts/:uid", function($uid){
    $sL           = $_POST["sL"];
    $bL           = $_POST["bL"];
    $bV           = $_POST["bV"];
    $vL           = $_POST["vL"];
    $mL           = $_POST["mL"];
    $pL           = $_POST["pL"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateEmpLeaveCounts($uid, $sL, $bL, $bV, $vL, $mL, $pL, $dateModified, $status);
});

$app->post("/entitlement/new/:var", function($var){
    $param          = explode(".", $var);
    $token          = $param[0];
    
    $entitlementUid = xguid();
    $employee       = $_POST['employee'];
    $leaveType      = $_POST['leaveType'];
    $leavePeriod    = $_POST['leavePeriod'];
    $entitlement    = $_POST['entitlement'];
    $dateCreated    = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");

    newEntitlementNew($entitlementUid, $employee, $leaveType, $leavePeriod, $entitlement, $dateCreated, $dateModified);
});

$app->get("/files/get/folder/location/:uid", function($uid){
    set_time_limit(0);

    $response = array();
    $files    = getFilesByEmpUid($uid);
    $stat     = "assets/files";

    while (true){
        $last_ajax_call = isset($_GET["timestamp"]) ? (int)$_GET["timestamp"] : null;
        clearstatcache();
        
        // $date = new DateTime($dateModified);
        // $last_change_in_data_file = $stat['mtime'];
        $last_change_in_data_file = filemtime($stat);
        if ($last_ajax_call == null || $last_change_in_data_file > $last_ajax_call) {
            // $data = file_get_contents($files);
            $files = getFilesByEmpUid($uid);
            if($files){
                $fileUid      = $files->uid;
                $path         = $files->path;
                $empUid       = $files->emp_uid;
                $dateModified = new DateTime($files->date_modified);
                $dateModified = $dateModified->getTimestamp();
            }else{
                $fileUid      = 0;
                $path         = 0;
                $empUid       = $uid;
                $dateModified = 0;
            }
            $response = array(
                "fileUid"   => $fileUid,
                "path"      => $path,
                "empUid"    => $empUid,
                "timestamp" => $last_change_in_data_file,
                "ajax"      => $last_ajax_call
            );
            echo jsonify($response);
            break;
        }else{
            sleep(1);
            continue;
        }      
    }
});

$app->post("/files/multiple/new/:var", function($var){
    $param = explode(".", $var);
    if(validToken($param[2]) && count($param) == 3 && isset($_FILES["attachment"])){
        $empUid    = $param[0];
        $check     = checkFilesIfUserExisting($empUid);
        $reference = $param[2];
        $unique    = $param[1];
        $directory = "assets" . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR;
        if($check){
            $imagePath = getPathByEmpUid($empUid);
            $path2     = $imagePath->path;
            unlink($path2);

            $fileUid      = xguid();
            $tempFilename = isset($_FILES["attachment"]["name"]);
            $mimeType     = isset($_FILES["attachment"]["type"]);
            $size         = isset($_FILES["attachment"]["size"]);
            $dateCreated  = date("Y-m-d H:i:s");
            $extension    = pathinfo($tempFilename, PATHINFO_EXTENSION);
            $directory    = "assets" . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR;
            $path         = $directory . sha1($tempFilename) . "_" . xguid() . "." . $extension;

            move_uploaded_file($_FILES["attachment"]["tmp_name"], $path);


            updateProfilePicture($empUid, $path2, $path, $tempFilename);

            $response[$tempFilename] = $path;
        }else{
            if (isset($_FILES["attachment"])) {
                $response = array();
                $error    = $_FILES["attachment"]["error"];
                //DEACTIVATE OTHER REFERENCE FILES
                if($unique === "1"){
                    updateFileStatusByReferenceFilenameEmpUid($reference , $fileName , $empUid , 0);
                }
                if (!is_array($_FILES["attachment"]['name'])){
                    $fileUid      = xguid();
                    $tempFilename = $_FILES["attachment"]["name"];
                    $mimeType     = $_FILES["attachment"]["type"];
                    $size         = $_FILES["attachment"]["size"];
                    $dateCreated  = date("Y-m-d H:i:s");
                    $extension    = pathinfo($tempFilename, PATHINFO_EXTENSION);
                    $path         = $directory . sha1($tempFilename) . "_" . xguid() . "." . $extension;
                    if(!file_exists($path)){
                        move_uploaded_file($_FILES["attachment"]["tmp_name"], $path);
                    }
                    newReferenceFile($fileUid, $reference, $tempFilename, $path, $mimeType, $size, $dateCreated, $empUid);
                    $response[$tempFilename] = $path;
                }
                else {
                    $fileCount = count($_FILES["attachment"]['name']);
                    for ($i = 0; $i < $fileCount; $i++) {
                        $fileUid      = xguid();
                        $tempFilename = $_FILES["attachment"]["name"];
                        $mimeType     = $_FILES["attachment"]["type"];
                        $size         = $_FILES["attachment"]["size"];
                        $dateCreated  = date("Y-m-d H:i:s");
                        $extension    = pathinfo($tempFilename, PATHINFO_EXTENSION);
                        $path         = $directory . sha1($tempFilename) . "." . $extension;
                        if(!file_exists($path)){
                            move_uploaded_file($_FILES["attachment"]["tmp_name"], $path);
                        }
                        newReferenceFile($fileUid, $reference, $tempFilename, $path, $mimeType, $size, $dateCreated);
                        $response[$tempFilename] = $path;
                    }
                }
            }
        }
        

        echo jsonify($response);
    }
});
/*FOR ADJUSTMENT*/
$app->get("/adjustment/pages/get/", function(){
    $adj      = getAdjustment();
    $response = array();

    foreach($adj as $adjust){
        $lastname   = utf8_decode($adjust["lastname"]);
        $middlename = utf8_decode($adjust["middlename"]);
        $firstname  = utf8_decode($adjust["firstname"]);

        $response[] = array(
            "adjUid"      => $adjust->adjustment_uid,
            "emp"         => $lastname .", ". $firstname ." ". $middlename,
            "payrollDate" => date("M d, Y", strtotime($adjust->payroll_date)),
            "amount"      => $adjust->amount
        );
    }
    echo jsonify($response);
});

$app->post("/adjustment/add/", function(){
    $adjUid       = xguid();
    $adjEmp       = $_POST["emp"];
    $adjAmount    = $_POST["amount"];
    $adjDate      = $_POST["adjDate"];
    
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addAdjustment($adjUid, $adjEmp, $adjAmount, $adjDate, $dateCreated, $dateModified);
});

$app->get("/adjustment/view/details/:uid", function($uid){

    $adj = getAdjustmentByUid($uid);
    if($adj){
        $lastname   = utf8_decode($adj->lastname);
        $firstname  = utf8_decode($adj->firstname);
        $middlename = utf8_decode($adj->middlename);
        $response   = array(
            "adjUid"      => $adj->adjustment_uid,
            "emp"         => $lastname . ", " . $firstname . " " . $middlename,
            "payrollDate" => $adj->payroll_date,
            "amount"      => $adj->amount,
            "status"      => $adj->status
        );
    }else{
        $response = array(
            "adjUid"      => "",
            "emp"         => "",
            "payrollDate" => "",
            "amount"      => "",
            "status"      => ""
        );
    }

    echo jsonify($response);
});

$app->post("/adjustment/update/:var", function($var){
    $param        = explode(".", $var);
    
    $adjUid       = $param[0];
    $amount       = $_POST["amount"];
    $date         = $_POST["date"];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST["status"];

    updateAdjustment($adjUid, $amount, $date, $dateModified, $status);
});

/*FOR ADDING CURRENCY*/
$app->get("/currency/pages/get/:var", function($var){
    $param    = explode(".", $var);
    $cur      = getCurrencies();
    $response = array();
    foreach($cur as $currency){
        $response[] = array(
            "currencyUid"  => $currency->currency_uid,
            "currencyName" => $currency->name
        );
    }
    echo jsonify($response);
});

$app->post("/currency/add/", function(){
    $currencyUid  = xguid();
    $currencyName = $_POST['currency'];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addCurrency($currencyUid, $currencyName, $dateCreated, $dateModified);
});

$app->get("/currency/view/details/:var", function($var){
    $param    = explode(".", $var);
    $uid      = $param[0];
    $token    = $param[1];
    $response = array();
    
    $viewCur  = getCurrencyByUid($uid);
    if($viewCur){
        $response = array(
            "currencyName" => $viewCur->name,
            "status"       => $viewCur->status
        );
    }else{
        $response = array(
            "currencyName" => "",
            "status"       => ""
        );
    }
    echo jsonify($response);
});

$app->post("/currency/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $uid          = $param[0];
    $name         = $_POST['name'];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];
    $currency     = getCurrencyByUid($uid);

    if($name != $currency->name OR $status != $currency->status){
        updateCurrencyByUid($uid , $name , $dateModified , $status);
        if(currencyCount($name)>1){
            updateCurrencyByUid($uid , $currency->name , $dateModified , $status);
        }
    }
});
/*END OF CURRENCY*/

/*HOLIDAY*/
$app->get("/holiday/pages/get/", function(){
    $response = array();
    $year = date("Y");
    $holiday  = getHolidayByYear($year);
    foreach ($holiday as $holidays) {
        $response[] = array(
            "holidayUid" => $holidays->holiday_uid,
            "name"       => $holidays->name,
            "date"       => date("F d, Y", strtotime($holidays->date))
        );
    }

    echo jsonify($response);
});

$app->get("/holiday/type/get/", function(){
    $response = array();
    $type     = getHolidayType();
    foreach($type as $types){
        $response[] = array(
            "holidayTypeUid" => $types->holiday_type_uid,
            "nameType"       => $types->holiday_name_type
        );
    }
    echo jsonify($response);
});

$app->post("/holiday/new/", function(){
    $holidayUid   = xguid();
    $name         = $_POST["name"];
    $type         = $_POST["type"];
    $date         = $_POST["date"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addHoliday($holidayUid, $name, $type, $date, $dateCreated, $dateModified);
});

$app->get("/holiday/details/get/:uid", function($uid){
    $response = array();
    $holiday  = getHolidayByUid($uid);
    if($holiday){
        $response = array(
            "holidayUid" => $holiday->holiday_uid,
            "name"       => $holiday->name,
            "type"       => $holiday->holiday_type_uid,
            "date"       => $holiday->date,
            "status"     => $holiday->status
        );
    }

    echo jsonify($response);
});

$app->post("/holiday/edit/details/:uid", function($uid){
    $type         = $_POST["type"];
    $name         = $_POST["name"];
    $date         = $_POST["date"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateHoliday($uid, $type, $name, $date, $dateModified, $status);
});

/*FOR ADDING FREQUENCY*/
$app->get("/frequency/pages/get/:var" , function($var){
    //parameter: term.start.size.token
    $param       = explode(".", $var);
    $term        = $param[0];
    $response    = array();
    
    $frequencies = getPaginatedFrequency();

    foreach ($frequencies as $frequency) {
        $response[] = array(
            "name"         => $frequency->pay_period_name,
            "frequency"    => $frequency->frequency,
            "frequencyUid" => $frequency->pay_period_uid
        );
    }
    echo jsonify($response);
});

$app->post("/frequency/new/:var", function($var){
    $param = explode(".", $var);
    $token = $param[0];

    $frequencyUid  = xguid();
    $frequencyName = $_POST['name'];
    $frequency     = $_POST['frequency'];
    $dateCreated   = date("Y-m-d H:i:s");
    $dateModified  = date("Y-m-d H:i:s");

    addFrequency($frequencyUid, $frequencyName, $frequency, $dateCreated, $dateModified);
});

$app->get("/frequency/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $frequencyUid = $param[1];
    
    $frequency    = getfrequencyByUid($frequencyUid);
    if($frequency){
        $response = array(
            "name"      => $frequency->pay_period_name,
            "frequency" => $frequency->frequency,
            "status"    => $frequency->status
        );
    }else{
        $response = array(
            "name"      => "",
            "frequency" => "",
            "status"    => ""
        );
    }

    echo jsonify($response);
});

$app->post("/frequency/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $frequencyUid = $param[0];
    $name         = $_POST['name'];
    $frequencies  = $_POST['frequency'];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];
    $frequency    = getfrequencyByUid($frequencyUid);

    if($name != $frequency->name OR $frequencies != $frequency->frequency OR $status != $frequency->status){
        updateFrequencyById($frequencyUid ,  $name , $frequencies , $dateModified , $status);
        if(frequencyCount($name)>1){
            updateFrequencyById($frequencyUid , $frequency->name , $frequency->frequencies , $dateModified , $status);
        }
    }
});
/*END OF FREQUENCY*/

/*FOR ATTENDANCE*/
$app->get("/attendace/view/employee/", function(){
    $response = array();
    $Ae       = getActiveEmployees();
    foreach($Ae as $ActE){
        $response[] = array(
            "empUid"    => utf8_decode($ActE->emp_uid),
            "firstname" => utf8_decode($ActE->firstname),
            "lastname"  => utf8_decode($ActE->lastname)
        );
    }
    echo jsonify($response);
});

$app->get("/timesheet/view/", function(){
    getEmpTimesheet();
});
/*END OF ATTENDANCE*/

/*FOR SETTING SCHEDULE*/
$app->post("/schedule/set/", function(){
    $scheduleUid = xguid();
    $payrollDate = $_POST["payrollDate"];
    $cutoffDate  = $_POST["cutoffDate"];

    setSchedule($scheduleUid, $payrollDate, $cutoffDate);
    echo "success";
});

$app->get("/schedule/view/:var", function($var){
    $param        = explode(".", $var);
    $frequencyUid = $param[0];
    $response     = array();
    $schedule     = getSchedules($frequencyUid);
    if($schedule){
        $response[] = array(
            "uid"         => $schedule->schedule_uid,
            "payrollDate" => date("M d, Y", strtotime($schedule->payroll_date)),
            "cutoffDate"  => date("M d, Y", strtotime($schedule->cutoff_date))
        );
    }
    echo jsonify($response);
});

$app->get("/schedule/data/edit/:id", function($id){
    $response = array();
    $scheds   = getSchedulesByUid($id);
        $response = array(
            "frequencyUid" => $scheds->frequency_uid,
            "payrollDate"  => $scheds->payroll_date,
            "cutoffDate"   => $scheds->cutoff_date
        );
    echo jsonify($response);
});

$app->post("/schedule/edit/data/:id", function($id){
    $status       = $_POST["status"];
    $startDate    = $_POST["startDate"];
    $endDate      = $_POST["endDate"];
    $frequencyUid = $_POST["frequencyUid"];
    $schedUid     = xguid();
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    editScheduleStatus($id, $startDate, $endDate,$status);
    addScheduleData($schedUid, $frequencyUid, $startDate, $endDate, $dateCreated, $dateModified);
});
/*END OF SETTING SCHEDULE*/
$app->get("/system/uid/generate", function(){
    $response = array(
        "uid" => xguid()
    );
    echo jsonify($response);
});

$app->post("/employees/get/username/:var", function(){
    $empUid = xguid();
});

$app->get("/token/generate/", function() {
    echo xguid();
});

/*SHIFT*/
$app->get("/shift/get/data/:var" , function($var){
    $param      = explode(".", $var);
    $response   = array();
    $shiftTypes = getPaginatedShift();
    foreach ($shiftTypes as $shift) {
        $shifts = $shift->name . ": (" . date("h:i A", strtotime($shift->start)) . " - " . date("h:i A", strtotime($shift->end)) .")";

        $response[] = array(
            "shiftUid" => $shift->shift_uid,
            "name"     => $shift->name,
            "start"    => date("h:i A", strtotime($shift->start)),
            "end"      => date("h:i A", strtotime($shift->end)),
            "grace"    => $shift->grace_period,
            "batch"    => $shift->batch,
            "shift"    => $shifts
        );
    }

    echo jsonify($response);
});

$app->post("/emp/shift/set/:uid", function($uid){
    $empShiftUid  = xguid();
    $shift        = $_POST["shift"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    setEmpShift($empShiftUid, $shift, $uid ,$dateCreated, $dateModified);
});

$app->get("/emp/shift/data/:uid", function($uid){
    $response = array();
    $shift    = getShiftByEmpUid($uid);
    if($shift){
        $response = array(
            "empShift" => $shift->emp_shift_uid,
            "shiftUid" => $shift->shift_uid,
            "name"     => $shift->name,
            "start"    => $shift->start,
            "end"      => $shift->end,
            "batch"    => $shift->batch,
            "status"   => $shift->status
        );
    }

    echo jsonify($response);
});


$app->post("/emp/shift/edit/:uid", function($uid){
    $shift        = $_POST["shift"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    updateEmpShift($uid, $shift, $dateModified, $status);
});

$app->post("/shift/new/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[0];
    
    $shiftUid     = xguid();
    $name         = $_POST["name"];
    $start        = $_POST["start"];
    $end          = $_POST["end"];
    $grace        = $_POST["grace"];
    $batch        = $_POST["batch"];
    
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addShift($shiftUid , $name, $start , $end , $grace , $batch ,$dateCreated , $dateModified);
	addRules($shiftUid, $name); // Date Modified: August 24, 2016 - Added this function
});

$app->get("/shift/details/get/:var" , function($var){
    //parameter: term.start.size.token
    $param    = explode(".", $var);
    $token    = $param[1];
    
    $shiftUid = $param[0];
    
    $shift    = getShiftByUid($shiftUid);
    if($shift){
        
        $response = array(
            "shiftUid" => $shift->shift_uid,
            "name"     => $shift->name,
            "start"    => $shift->start,
            "end"      => $shift->end,
            "grace"    => $shift->grace_period,
            "batch"    => $shift->batch,
            "status"   => $shift->status
        );
    }else{
        $response = array(
            "shiftUid" => "",
            "name"     => "",
            "start"    => "",
            "end"      => "",
            "grace"    => "",
            "batch"    => "",
            "status"   => ""
        );
    }

    echo jsonify($response);
});

$app->post("/shift/update/:var" , function($var){
    //parameter: term.start.size.token
    $param        = explode(".", $var);
    $token        = $param[1];
    
    $shiftUid     = $param[0];
    $name         = $_POST["name"];
    $start        = $_POST["start"];
    $end          = $_POST["end"];
    $grace        = $_POST["grace"];
    $batch        = $_POST["batch"];
    $dateModified = date("Y-m-d H:i:s");
    $status       = $_POST['status'];

    updateShiftById($shiftUid , $name , $start , $end , $grace , $batch , $dateModified , $status);
});
/*END OF SHIFT*/

/*PAYSLIP*/
$app->get("/payslip/testing/:var", function($var){
    $params       = explode(".", $var);
    $startDate    = $params[0];
    $endDate      = $params[1];
    $frequencyUid = $params[2];
    $id           = $params[3];
    
    $aa           = getEmpTimeLogByUid($id, $startDate, $endDate, $frequencyUid);
    $b            = getEmpNetPayByEmpUid($id);
    $c            = employeeTax($startDate, $endDate, $frequencyUid);
    $pS           = getSchedules($frequencyUid);

    // echo jsonify($c);

    if($pS){
        $schedStartDate = $pS["payroll_date"];
        $schedEndDate   = $pS["cutoff_date"];
    }//end of getting schedule
    if($startDate == $schedStartDate && $endDate == $schedEndDate){
        // foreach($a as $aa){
        if($aa){
            $sahod     = $aa["sahod"];
            $allowance = $aa["allowance"];
            $overtime  = $aa["oTpay"];
            $grossPay  = $aa["grossPay"];
        }

        foreach($b as $bb){
            $sss         = $bb["sss"];
            $philhealth  = $bb["philhealth"];
            $pagibig     = $bb["pagibig"];
            $totalContri = $bb["totalContri"];
        }

        foreach($c as $cc){
            $taxId         = $cc["id"];
            $deduction     = $cc["deduction"];
            $pettyCash     = $cc["pettyCash"];
            $loan          = $cc["loan"];
            $tax           = $cc["tax"];
            $grossEarnings = $cc["grossEarnings"];
            $tardiness     = $cc["tardiness"];
            $adjustment    = $cc["adjustment"];
            $deduction     = $cc["deduction"];

            // $adjustment = $overtime + $lateSalary;
            $netPay = $grossEarnings - $deduction;

            if($id == $taxId){
                $response[] = array(
                    "sahod"          => $grossPay,
                    "allowance"      => number_format($allowance, 2),
                    "overtime"       => number_format($overtime, 2),
                    "sss"            => number_format($sss, 2),
                    "philhealth"     => number_format($philhealth, 2),
                    "pagibig"        => number_format($pagibig, 2),
                    "netPay"         => number_format($netPay, 2),
                    "tardiness"      => number_format($tardiness, 2),
                    "pettyCash"      => number_format($pettyCash, 2),
                    "loan"           => number_format($loan, 2),
                    "tax"            => number_format($tax, 2),
                    "adjustment"     => number_format($adjustment, 2),
                    "totalDeduction" => number_format($deduction, 2),
                    "grossEarnings"  => number_format($grossEarnings, 2),
                    "error"          => "",
                    "errorStatus"    => 0
                );
            }
        }
    }else{
        $response = array(
            "error"       => "NOT IN PAYROLL SCHEDULE!",
            "errorStatus" => 1
        );
    }
    echo jsonify($response);
});

$app->get("/get/requests/notification/", function(){
    $absentRequestNotificationCount         = getAbsentRequestsNotification();
    $overtimeRequestNotificationCount       = getOvertimeRequestsNotification();
    $leaveRequestNotificationCount          = getLeaveRequestsNotification();
    $offsetRequestNotificationCount         = getOffsetNewRequestNotification();
    $timeAdjustmentRequestNotificationCount = getTimeAdjustmentNewRequestNotification();

    $response = array(
        "absent_request_notification_count"          => $absentRequestNotificationCount,
        "overtime_request_notification_count"        => $overtimeRequestNotificationCount,
        "leave_request_notification_count"           => $leaveRequestNotificationCount,
        "offset_request_notification_count"          => $offsetRequestNotificationCount,
        "time_adjustment_request_notification_count" => $timeAdjustmentRequestNotificationCount
    );

    echo jsonify($response);
});

$app->get("/get/overtime/notification/count/", function(){
    $count     = countOvertimeRequestByStatus("Pending");
    $accept    = countOvertimeRequestByStatus("Accepted");
    $certified = countOvertimeRequestByStatus("Certified");
    $denied    = countOvertimeRequestByStatus("Denied");

    $response = array(
        "pendingCount"   => $count,
        "acceptCount"    => $accept,
        "certifiedCount" => $accept,
        "deniedCount"    => $denied
    );

    echo jsonify($response);
});

$app->get("/get/absent/accepted/count/", function(){
    $count = countAcceptedRequestsOfAbsent();

    $response = array(
        "pendingCount" => $count
    );

    echo jsonify($response);
});

$app->get("/get/overtime/notification/count/date/:var", function($var){
    $params    = explode(".", $var);
    $startDate = $params[0];
    $endDate   = $params[1];
    
    $count     = countOvertimeRequestsByStatusAndDateRange($startDate, $endDate, "Pending");
    $accept    = countOvertimeRequestsByStatusAndDateRange($startDate, $endDate, "Approved");
    $certified = countOvertimeRequestsByStatusAndDateRange($startDate, $endDate, "Certified");
    $denied    = countOvertimeRequestsByStatusAndDateRange($startDate, $endDate, "Denied");

    $response = array(
        "pendingCount"   => $count,
        "acceptedCount"  => $accept,
        "certifiedCount" => $certified,
        "deniedCount"    => $denied
    );

    echo jsonify($response);
});

$app->get("/get/employee/notifications/:uid", function($uid){
    $overtimeAcceptedRequestNotification       = countOvertimeAcceptedRequestsByEmpUid($uid);
    $leaveAcceptedRequestNotification          = countLeaveAcceptedRequestsByEmpUid($uid);
    $offsetAcceptedRequestNotification         = countOffsetAcceptedRequestsByEmpUid($uid);
    $timeAdjustmentAcceptedRequestNotification = countTimeAcceptedRequestsByEmpUid($uid);

    $response = array(
        "overtime_accepted_count"        => $overtimeAcceptedRequestNotification,
        "leave_accepted_count"           => $leaveAcceptedRequestNotification,
        "offset_accepted_count"          => $offsetAcceptedRequestNotification,
        "time_adjustment_accepted_count" => $timeAdjustmentAcceptedRequestNotification
    );

    echo jsonify($response);
});

$app->get("/employee/read/overtime/notification/:uid", function($uid){
    $dateModified = date("Y-m-d H:i:s");
    
    $overtimes        = getEmployeeOvertimeNotification($uid);

    foreach($overtimes as $overtime){
        $overtimeUid = $overtime["overtime_request_uid"];

        updateOvertimeNotificationByUid($overtimeUid, $dateModified);
    }//end of checking
});

$app->get("/employee/pending/overtime/notification/:uid", function($uid){
    $count    = countOvertimeRequestPendingNotificationByEmpUid($uid);
    $response = array();
    if($count){
        $response = array(
            "count" => $count
        );
    }else{
        $response = array(
            "count" => 0
        );
    }

    echo jsonify($response);
});

$app->get("/edit/leave/notif/", function(){
    $notif = editLeaveRequestsNotification();

    $response = array(
        "notifCount" => ""
    );

    echo jsonify($response);
});

$app->get("/employee/read/leave/notification/:uid", function($uid){
    $dateModified = date("Y-m-d H:i:s");
    
    $check        = getEmployeeLeaveNotifications($uid);

    foreach($check as $checks){
        $reqUid = $checks["leave_request_uid"];
        updateLeaveNotificationByLeaveUid($reqUid, $dateModified);
    }//end of checking

        updateEmployeeLeaveNotifications($uid, $dateModified);

});
$app->get("/get/leave/notification/count/", function(){
    $count       = countPendingRequestsOfLeave();
    $countAccept = countAcceptedRequestsOfLeave();
    $certified   = countCertifiedRequestsOfLeave();
    $denied      = countDeniedRequestsOfLeave();

    $response = array(
        "pendingCount"   => $count,
        "acceptedCount"  => $countAccept,
        "certifiedCount" => $certified,
        "deniedCount"    => $denied
    );

    echo jsonify($response);
});

$app->get("/get/notification/leave/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    
    $count     = countPendingRequestsOfLeaveByDate($startDate, $endDate);
    $accept    = countAcceptedRequestsOfLeaveByDate($startDate, $endDate);
    $certified = countCertifiedRequestsOfLeaveByDate($startDate, $endDate);
    $denied    = countDeniedRequestsOfLeaveByDate($startDate, $endDate);
    $response = array(
        "pendingCount"   => $count,
        "acceptedCount"  => $accept,
        "certifiedCount" => $certified,
        "deniedCount"    => $denied
    );

    echo jsonify($response);
});
$app->get("/emp/notif/get/leave/:uid", function($uid){
    $count = countAcceptedLeaveRequestsByEmpUid($uid);

    $response = array(
        "acceptedCount" => $count
    );

    echo jsonify($response);
});

$app->get("/employee/pending/leave/notification/:uid", function($uid){
    $count = countPendingLeaveNotifByEmpUid($uid);

    $response = array();

    if($count){
        $response = array(
            "count" => $count
        );
    }else{
        $response = array(
            "count" => 0
        );
    }

    echo jsonify($response);
});

$app->get("/get/absent/requests/:var", function($var){
    $param    = explode(".", $var);
    $token    = $param[0];
    
    $response = array();
    $a        = getAbsentRequest();

    foreach($a as $overtime){
        $response[] = array(
            "overtimeUid"   => $overtime->overtime_request_uid,
            "lastname"      => utf8_decode($overtime->lastname),
            "firstname"     => utf8_decode($overtime->firstname),
            "empUid"        => $overtime->emp_uid,
            "startDate"     => date("M d, Y", strtotime($overtime->start_date)),
            "startDates"    => $overtime->start_date,
            "endDate"       => $overtime->end_date,
            "hours"         => $overtime->hours,
            "reason"        => $overtime->reason,
            "type"          => $overtime->leave_name,
            "requestStatus" => $overtime->overtime_request_status,
            "status"        => $overtime->status,
            "certBy"        => $overtime->cert_by,
            "appBy"         => $overtime->appr_by
        );
    }
    echo jsonify($response);
});

$app->get("/get/emp/absent/requests/:uid", function($uid){
    // $param = explode(".", $var);
    // $token = $param[0];

    $response = array();
    $a        = getAbsentRequestByEmpUid($uid);

    foreach($a as $overtime){
        $response[] = array(
            "overtimeUid"   => $overtime->overtime_request_uid,
            "lastname"      => utf8_decode($overtime->lastname),
            "firstname"     => utf8_decode($overtime->firstname),
            "empUid"        => $overtime->emp_uid,
            "startDate"     => date("M d, Y", strtotime($overtime->start_date)),
            "startDates"    => $overtime->start_date,
            "endDate"       => $overtime->end_date,
            "hours"         => $overtime->hours,
            "reason"        => $overtime->reason,
            "type"          => $overtime->holiday_name_type,
            "requestStatus" => $overtime->overtime_request_status,
            "status"        => $overtime->status
        );
    }
    echo jsonify($response);
});

$app->post("/absent/requests/new/:var", function($var){
    $param               = explode(".", $var);
    $token               = $param[0];
    
    $leaveUid            = xguid();
    // $overtimeNotifUid = xguid();
    $employee            = $_POST["employee"];
    $code                = $_POST["type"];
    $startDate           = date("Y-m-d", strtotime($_POST["startDate"]));
    $endDate             = $startDate;
    $hours               = "";
    $requestStatus       = $_POST["requestStatus"];
    $dateCreated         = date("Y-m-d H:i:s");
    $dateModified        = date("Y-m-d H:i:s");
    $reason              = $_POST["reason"];
    $type                = getLeaveTypeByCode($code);
    $leaveBalance        = "";

    echo "$type<br/>";

    newLeaveRequest($leaveUid, $employee, $type, $leaveBalance, $startDate, $endDate, $reason ,$requestStatus, $dateCreated, $dateModified);
    // addOvertimeRequestsNotification($overtimeNotifUid, $overtimeRequestUid, $dateCreated, $dateModified);
});

//SAVING SCHEDULING, LISTING 
$app->post("/schedule/requests/new/", function(){

    $scheduletype = $_POST['schedule_type'];
    $scheduledate= $_POST['schedule_date'];


    newSchedule($scheduletype, $scheduledate);
});


$app->get("/get/overtime/requests/:var", function($var){
    $param    = explode(".", $var);
    $token    = $param[0];
    
    $response = array();
    $a        = getOvertimeRequest();

    foreach($a as $overtime){
        $uid        = $overtime->emp_uid;
        $startDate  = $overtime->start_date;
        $startDates = date("Y-m-d", strtotime($startDate));
        $end        = $overtime->end_date;
        $out        = getTimeLogOutByEmpUidAndDate($uid, $startDates, $end);
        if($out){
            $outs   = $out->date_created;
            $endStr = strtotime($end);
            $outStr = strtotime($outs);
            if($outStr >= $endStr){
                $out1 = "Exact!";
            }else{
                $out1 = "Out: " . date("h:i:s A", strtotime($outs));
            }
        }else{
            $out1 = "No Time Out!";
        }
        $response[] = array(
            "overtimeUid"   => $overtime->overtime_request_uid,
            "empNo"         => $overtime->username,
            "lastname"      => utf8_decode($overtime->lastname),
            "firstname"     => utf8_decode($overtime->firstname),
            "empUid"        => $overtime->emp_uid,
            "startDate"     => date("M d, Y", strtotime($overtime->start_date)),
            "startDates"    => $overtime->start_date,
            "endDate"       => $overtime->end_date,
            "hours"         => $overtime->hours,
            "reason"        => $overtime->reason,
            "type"          => $overtime->overtime_type_name,
            "requestStatus" => $overtime->overtime_request_status,
            "status"        => $overtime->status,
            "certBy"        => $overtime->cert_by,
            "apprBy"        => $overtime->appr_by,
            "prompt"        => $out1
        );
    }
    echo jsonify($response);
});

$app->get("/get/employee/overtime/requests/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $emp       = $param[2];
    
    $response  = array();
    $overtime  = getEmployeeOvertimeRequestsByDateRange($startDate, $endDate, $emp);

    foreach($overtime as $overtimes){
        $uid        = $overtimes->emp_uid;
        $startDate  = $overtimes->start_date;
        $startDates = date("Y-m-d", strtotime($startDate));
        $end        = $overtimes->end_date;
        $sessionData = getTimeLogByEmpUidAndDate($uid, $startDates);
        if($sessionData){
            $session = $sessionData["session"];
            // echo "$session<br/>";
            $out     = getTimeLogOutByEmpUidAndSession($uid, $session);
            if($out){
                $outs   = $out->date_created;
                $endStr = strtotime($end);
                $outStr = strtotime($outs);
                if($outStr >= $endStr){
                    $out1 = "Exact!";
                }else{
                    $out1 = "Out: " . date("M d, Y h:i:s A", strtotime($outs));
                }
            }else{
                $out1 = "No Time Out!";
            }

            $response[] = array(
                "uid"            => $overtimes->overtime_request_uid,
                "empNo"          => $overtimes->username,
                "lastname"       => utf8_decode($overtimes->lastname),
                "firstname"      => utf8_decode($overtimes->firstname),
                "empUid"         => $overtimes->emp_uid,
                "from"           => date("M d, Y", strtotime($overtimes->start_date)),
                "startDates"     => $overtimes->start_date,
                "to"             => $overtimes->end_date,
                "hours"          => $overtimes->hours,
                "reason"         => $overtimes->reason,
                "type"           => $overtimes->overtime_type_name,
                "request_status" => $overtimes->overtime_request_status,
                "status"         => $overtimes->status,
                "certBy"         => $overtimes->cert_by,
                "apprBy"         => $overtimes->appr_by,
                "date_created"         => date("Y-m-d h:i A", strtotime($overtimes->date_created)),
                "date_modified"         => date("Y-m-d h:i A", strtotime($overtimes->date_modified)),
                "prompt"         => $out1
            );
        }
    }

    echo jsonify($response);
});

$app->get("/get/overtime/request/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $reqStatus = $param[2];
    
    $response  = array();
    $overtime  = getOvertimeRequestsByDates($startDate, $endDate, $reqStatus);

    foreach($overtime as $overtimes){
        $uid        = $overtimes->emp_uid;
        $startDate  = $overtimes->start_date;
        $startDates = date("Y-m-d", strtotime($startDate));

        $employeeDetail = getEmployeeDetailsByUid($uid);
        $lastname = $employeeDetail->lastname;
        $firstname = $employeeDetail->firstname;
        $middlename = $employeeDetail->middlename;
        $username = getEmployeeUsernameByEmpUid($uid);

        $overtimeTypeDetails = getOvertimeTypeByUid($overtimes->type);
        $overtimeTypeName = $overtimeTypeDetails["overtime_type_name"];

        // echo "$uid = $startDates<br/>";
        $end         = $overtimes->end_date;
        $sessionData = getTimeLogByEmpUidAndDate($uid, $startDates);
        $out1 = "No time log";

        $a = getEmployeeDetailsByUid($uid);
        if($a){
            $lastnames = utf8_decode($a->firstname) . "_" . " ";
            $words = explode("_", $lastnames);
            $name = "";

            foreach ($words as $w) {
              $name .= $w[0];
            }

            $lastname = $name . ". " . utf8_decode($a->lastname);
        }//end of getEmployeeDetailsByUid Function

        if($sessionData){
            $session = $sessionData["session"];
            // echo "$session<br/>";
            $out     = getTimeLogOutByEmpUidAndSession($uid, $session);
            if($out){
                $outs   = $out->date_created;
                $endStr = strtotime($end);
                $outStr = strtotime($outs);
                if($outStr >= $endStr){
                    $out1 = "Exact!";
                }else{
                    $out1 = "Out: " . date("M d, Y h:i:s A", strtotime($outs));
                }
            }else{
                $out1 = "No Time Out!";
            }
        }

        $response[] = array(
            "overtimeUid"   => $overtimes->overtime_request_uid,
            "empNo"         => $username,
            "lastname"      => utf8_decode($lastname),
            "firstname"     => utf8_decode($firstname),
            "name"          => utf8_decode($lastname),
            "empUid"        => $overtimes->emp_uid,
            "startDate"     => date("m-d-y", strtotime($overtimes->start_date)),
            "startDates"    => $overtimes->start_date,
            "endDate"       => $overtimes->end_date,
            "hours"         => $overtimes->hours,
            "reason"        => substr($overtimes->reason, 0, 20) . " ... ",
            "type"          => $overtimeTypeName,
            "requestStatus" => $overtimes->overtime_request_status,
            "status"        => $overtimes->status,
            "certBy"        => $overtimes->cert_by,
            "apprBy"        => $overtimes->appr_by,
            "date_created"  => date("m-d-y h:i A", strtotime($overtimes->date_created)),
            "date_modified" => date("m-d-y h:i A", strtotime($overtimes->date_modified)),
            "prompt"        => $out1
        );
    }

    echo jsonify($response);
});

$app->get("/get/overtime/request/:uid", function($uid){
    $response = array();
    $overtime = getOvertimeRequestsByUid($uid);

    if($overtime){
        $startDate  = $overtime->start_date;
        $startDates = date("Y-m-d", strtotime($startDate));
        $startHour  = date("h:i A", strtotime($startDate));
        
        $endDate    = $overtime->end_date;
        $endDates   = date("Y-m-d", strtotime($endDate));
        $endHour    = date("h:i A", strtotime($endDate));
        $response   = array(
            "overtimeUid"   => $overtime->overtime_request_uid,
            "startDate"     => date("M d, Y", strtotime($overtime->start_date)),
            "startDates"    => $startDates,
            "startHour"     => $startHour,
            "endDate"       => $endDates,
            "endHour"       => $endHour,
            "hours"         => $overtime->hours,
            "reason"        => $overtime->reason,
            "type"          => $overtime->type,
            "requestStatus" => $overtime->overtime_request_status,
            "status"        => $overtime->status,
            "start"         => $startDate,
            "end"           => $endDate,
            "empUid"        => $overtime->emp_uid
        );
    }
    echo jsonify($response);
});

$app->get("/check/out/:var", function($var){
    $var       = rawurldecode($var);
    $param     = explode(".", $var);
    $start     = $param[0];
    $end       = $param[1];
    $uid       = $param[2];
    
    $startDate = date("Y-m-d", strtotime($start));
    $out       = getTimeLogOutByEmpUidAndDate($uid, $startDate);
    if($out){
        $outs   = $out->date_created;
        $endStr = strtotime($end);
        $outStr = strtotime($outs);
        if($outStr >= $endStr){
            $response = array(
                "prompt" => 0,
                "out"    => $outs
            );
        }else{
            $response = array(
                "prompt" => 1,
                "out"    => $outs
            );
        }
    }else{
        $response = array(
            "prompt" => 2,
            "out"    => "No Time Out!"
        );
    }
    

    
    echo jsonify($response);
});

$app->post("/overtime/requests/new/:var", function($var){
    $param = explode(".", $var);
    $token = $param[0];

    $overtimeRequestUid = xguid();
    $overtimeNotifUid   = xguid();
    $employee           = $_POST["employee"];
    $type               = $_POST["type"];
    $reason             = $_POST["reason"];
    $requestStatus      = $_POST["requestStatus"];
    $dateCreated        = date("Y-m-d H:i:s");
    $dateModified       = date("Y-m-d H:i:s");
    $startDate          = date("Y-m-d", strtotime($_POST["startDate"]));
    $startHour          = date("H:i", strtotime($_POST["startHour"]));
    $admin              = $_POST["admin"];
    $startDate          = $startDate . " " . $startHour;
    
    $endDate            = date("Y-m-d", strtotime($_POST["endDate"]));
    $endHour            = date("H:i", strtotime($_POST["endHour"]));
    $endDate            = $endDate . " " . $endHour;
    
    $hours1             = strtotime($startDate);
    $hours2             = strtotime($endDate);
    $hours              = ($hours2 - $hours1) / 3600;
    
    /* EDIT - Waltz on 09-14-15 */
    $hours              = sprintf("%.2f", $hours);
    $ehour              = explode('.', $hours);
    $hour1              = $ehour[0];
    $hour2              = $ehour[1];

    if($hour2 >=50) {
        $hour2 = 5;
    }
    else {
        $hour2 = 0;
    }
    $hours = $hour1 . "." . $hour2;
    /* EDIT - Waltz on 09-14-15 */


    if(strtotime($startDate) <= strtotime($endDate)){
        $valid = true;
    }else{
        $valid = false;
    }

    $checkRequest = checkPayrollSchedBeforeRequest($startDate);
    $atype        = getUserTypeByEmpUid($admin);

    if($atype == "Administrator"){
        if($valid){
            addOvertimeRequest($overtimeRequestUid, $type ,$employee, $startDate, $endDate, $hours,$reason, $requestStatus, $dateCreated, $dateModified);
            addOvertimeRequestsNotification($overtimeNotifUid, $overtimeRequestUid, $dateCreated, $dateModified);
            $response = array(
                "prompt" => 0
            );
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    }else{
        if($checkRequest["prompt"]){
            if($valid){
                addOvertimeRequest($overtimeRequestUid, $type ,$employee, $startDate, $endDate, $hours,$reason, $requestStatus, $dateCreated, $dateModified);
                addOvertimeRequestsNotification($overtimeNotifUid, $overtimeRequestUid, $dateCreated, $dateModified);
                $response = array(
                    "prompt" => 0
                );
            }else{
                $response = array(
                    "prompt" => 3
                );
            }
        }else{
            if($valid){
                $response = array(
                    "prompt" => 1
                );
            }else{
                $response = array(
                    "prompt" => 3
                );
            }
        }
    }
    
    echo jsonify($response);
});

$app->post("/request/overtime/:var", function($var){
    $param              = explode(".", $var);
    $token              = $param[0];
    
    $overtimeRequestUid = xguid();
    $employee           = $_POST["employee"];
    // $type            = "";
    $reason             = $_POST["reason"];
    $overtimeNotifUid   = xguid();
    
    $startDate          = $_POST["startDate"];
    $date               = $startDate;
    $startHour          = $_POST["startHour"];
    $startHour          = date("H:i", strtotime($startHour));
    $startDate          = $startDate . " " . $startHour;
    
    $endDate            = $_POST["endDate"];
    $endHour            = $_POST["endHour"];
    $endHour            = date("H:i", strtotime($endHour));
    $endDate            = $endDate . " " . $endHour;
    
    $requestStatus      = "Pending";
    $dateCreated        = date("Y-m-d H:i:s");
    $dateModified       = date("Y-m-d H:i:s");
    
    $type               = getHolidayTypeByDate($startDate);
    $hours1             = strtotime($startDate);
    $hours2             = strtotime($endDate);
    $hours              = ($hours2 - $hours1) / 3600;
    
    /* EDIT - Waltz on 09-14-15 */
    $hours              = sprintf("%.2f", $hours);
    $ehour              = explode('.', $hours);
    $hour1              = $ehour[0];
    $hour2              = $ehour[1];

    if($hour2 >=50) {
        $hour2 = 5;
    }
    else {
        $hour2 = 0;
    }
    $hours = $hour1 . "." . $hour2;
    /* EDIT - Waltz on 09-14-15 */

    if(strtotime($startDate) <= strtotime($endDate)){
        $valid = true;
    }else{
        $valid = false;
    }

    $checkDate    = checkEmployeeHasOvertimeByDateAndEmpUid($employee, $date);
    
    $checkRequest = checkPayrollSchedBeforeRequest($date);
    if($checkRequest["prompt"]){
        if($valid){
            if($checkDate >= 2){
                $response = array(
                    "prompt" => 4
                );
            }else if($checkDate <= 2){
                addOvertimeRequest($overtimeRequestUid, $type ,$employee, $startDate, $endDate, $hours,$reason ,$requestStatus, $dateCreated, $dateModified);
                addOvertimeRequestsNotification($overtimeNotifUid, $overtimeRequestUid, $dateCreated, $dateModified);
                
                $response = array(
                    "prompt" => 0
                );
            }
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    }else{
        if($valid){
            $response = array(
                "prompt" => 1
            );
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    }

    echo jsonify($response);
});

$app->post("/overtime/request/edit/:uid", function($uid){
    $startDate     = date("Y-m-d", strtotime($_POST["startDate"]));
    $startHour     = date("H:i", strtotime($_POST["startHour"]));
    $startDate     = $startDate . " " . $startHour;
    
    $endDate       = date("Y-m-d", strtotime($_POST["endDate"]));
    $endHour       = date("H:i", strtotime($_POST["endHour"]));
    $endDate       = $endDate . " " . $endHour;
    
    $reason        = $_POST["reason"];
    $status        = $_POST["status"];
    $hours1        = strtotime($startDate);
    $hours2        = strtotime($endDate);
    $hours         = ($hours2 - $hours1) / 3600;
    $hour          = $_POST["hour"];
    $type          = $_POST["type"];
    $admin         = $_POST["admin"];
    
    $requestStatus = $_POST["requestStatus"];
    $dateModified  = date("Y-m-d H:i:s");
    
    $emp           = getEmployeeDetailsByUid($admin);
    if($emp){
        $lastname   = $emp->lastname;
        $firstname  = $emp->firstname;
        $middlename = $emp->middlename;
    }else{
        $lastname   = "";
        $firstname  = "";
        $middlename = "";
    }
    

    $name = $firstname . " " . $middlename . " " . $lastname;

    function getInitials($name){
        $words = explode(" ",$name);
        $inits = '';
        foreach($words as $word){
            $inits.=strtoupper(substr($word,0,1));
        }
        return $inits;  
    }

    $user = getInitials($name);

    switch($requestStatus){
        case "Certified":
            $user1 = $user;
            $user2 = "";
            updateOvertimeRequest($uid, $type,$startDate, $endDate, $reason, $hour ,$requestStatus, $user1, $user2 ,$dateModified, $status);
            updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
            break;
        case "Approved": 
            $user2 = $user;
            $user1 = "";
            updateOvertimeRequest($uid, $type,$startDate, $endDate, $reason, $hour ,$requestStatus, $user1, $user2 ,$dateModified, $status);
            updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
            break;
        default:
            $user1 = "";
            $user2 = $user;
            updateOvertimeRequest($uid, $type,$startDate, $endDate, $reason, $hour ,$requestStatus, $user1, $user2 ,$dateModified, $status);
            updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
            break;
    }
    
});

$app->post("/overtime/request/edit/batch/", function(){
    $uid = $_POST["overtimeUid"];
    $admin = $_POST["admin"];
    $count = $_POST["count"];

    $overtimeRequestDetails = getOvertimeRequestByUid($uid);
    $startDate     = date("Y-m-d H:i:s", strtotime($overtimeRequestDetails["start_date"]));
    $startHour = date("H:i:s", strtotime($overtimeRequestDetails["start_date"]));
    $endDate       = date("Y-m-d H:i:s", strtotime($overtimeRequestDetails["end_date"]));
    $endHour = date("H:i:s", strtotime($overtimeRequestDetails["end_date"]));
    
    $reason        = $overtimeRequestDetails["reason"];
    $status        = $overtimeRequestDetails["status"];
    $hours1        = strtotime($startDate);
    $hours2        = strtotime($endDate);
    $hours         = ($endHour - $startHour) / 3600;
    $hour          = $overtimeRequestDetails["hours"];
    $type          = $overtimeRequestDetails["type"];
    
    $requestStatus = $_POST["action"];
    $dateModified  = date("Y-m-d H:i:s");
    
    $emp           = getEmployeeDetailsByUid($admin);
    if($emp){
        $lastname   = $emp->lastname;
        $firstname  = $emp->firstname;
        $middlename = $emp->middlename;
    }else{
        $lastname   = "";
        $firstname  = "";
        $middlename = "";
    }
    

    $name = $firstname . " " . $middlename . " " . $lastname;
    function getInitials($name){
        $words = explode(" ",$name);
        $inits = '';
        foreach($words as $word){
            $inits.=strtoupper(substr($word,0,1));
        }
        return $inits;  
    }

    $user = getInitials($name);

    switch($requestStatus){
        case "Certified":
            $user1 = $user;
            $user2 = "";
            updateOvertimeRequest($uid, $type,$startDate, $endDate, $reason, $hour ,$requestStatus, $user1, $user2 ,$dateModified, $status);
            updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
            break;
        case "Approved": 
            $user2 = $user;
            $user1 = "";
            updateOvertimeRequest($uid, $type,$startDate, $endDate, $reason, $hour ,$requestStatus, $user1, $user2 ,$dateModified, $status);
            updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
            break;
        default:
            $user1 = "";
            $user2 = $user;
            updateOvertimeRequest($uid, $type,$startDate, $endDate, $reason, $hour ,$requestStatus, $user1, $user2 ,$dateModified, $status);
            updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
            break;
    }

    echo $count;
    
});

$app->get("/remove/overtime/request/:uid", function($uid){
    removeOvertimeRequestByUid($uid);
    $response = array(
        "prompt" => 1
    );

    echo jsonify($response);
});

$app->post("/update/overtime/request/:uid", function($uid){
    $startDate = $_POST["startDate"];
    $startHour = $_POST["startHour"];
    $startHour = date("H:i", strtotime($startHour));
    $startDate = $startDate . " " . $startHour;
    
    $endDate   = $_POST["endDate"];
    $endHour   = $_POST["endHour"];
    $endHour   = date("H:i", strtotime($endHour));
    $endDate   = $endDate . " " . $endHour;
    
    $reason    = $_POST["reason"];
    $status    = $_POST["status"];
    $hours1    = strtotime($startDate);
    $hours2    = strtotime($endDate);
    $hours     = ($hours2 - $hours1) / 3600;
    
    /* EDIT - Waltz on 09-14-15 */
    $hours     = sprintf("%.2f", $hours);
    $ehour     = explode('.', $hours);
    $hour1     = $ehour[0];
    $hour2     = $ehour[1];

    if($hour2 >=50) {
        $hour2 = 5;
    }
    else {
        $hour2 = 0;
    }
    $hours = $hour1 . "." . $hour2;
    /* EDIT - Waltz on 09-14-15 */
    
    $admin = $_POST["admin"];

    $overtimeData = getOvertimeRequestsByUid($uid);
    $type  = $overtimeData["type"];

    $requestStatus = $_POST["requestStatus"];
    $dateModified  = date("Y-m-d H:i:s");

    $emp = getEmployeeDetailsByUid($admin);
    if($emp){
        $lastname   = $emp->lastname;
        $firstname  = $emp->firstname;
        $middlename = $emp->middlename;
    }else{
        $lastname   = "";
        $firstname  = "";
        $middlename = "";
    }
    

    $name = $firstname . " " . $middlename . " " . $lastname;

    function getInitials($name){
        $words = explode(" ",$name);
        $inits = '';
        foreach($words as $word){
            $inits.=strtoupper(substr($word,0,1));
        }
        return $inits;  
    }

    $user = getInitials($name);

    if(strtotime($startDate) <= strtotime($endDate)){
        $valid = true;
    }else{
        $valid = false;
    }

    $checkRequest = checkPayrollSchedBeforeRequest($startDate);
    if($checkRequest["prompt"]){
        if($valid){
            switch($requestStatus){
            case "Certified":
                $user1 = $user;
                $user2 = "";
                updateOvertimeRequest($uid,$type,$startDate, $endDate, $reason, $hours ,$requestStatus, $user1, $user2 ,$dateModified, $status);
                updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
                break;
            case "Approved": 
                $user2 = $user;
                $user1 = "";
                updateOvertimeRequest($uid,$type,$startDate, $endDate, $reason, $hours ,$requestStatus, $user1, $user2 ,$dateModified, $status);
                updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
                break;
            default:
                $user1 = $user;
                $user2 = "";
                updateOvertimeRequest($uid,$type,$startDate, $endDate, $reason, $hours ,$requestStatus, $user1, $user2 ,$dateModified, $status);
                updateOvertimeNotification($uid, $requestStatus, $dateModified, $status);
                break;
            }
            $response = array(
                "prompt" => 0
            );
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    }else{
        if($valid){
            $response = array(
                "prompt" => 1
            );
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    }
    echo jsonify($response);
});

$app->get("/get/overtime/type/", function(){
    $response     = array();
    
    $overtimeType = getOvertimeTypes();
    foreach($overtimeType as $type){
        $response[] = array(
            "uid" => $type->overtime_type_uid,
            "kind"    => $type->overtime_kind,
            "name"    => $type->overtime_type_name,
            "code"    => $type->overtime_type_code,
            "rate"    => $type->rate,
            "additionalRate"  => $type->additional_rate
        );
    }
    echo jsonify($response);
});

$app->post("/add/overtime/type/", function(){
    $uid          = xguid();
    $kind         = $_POST["kind"];
    $name         = $_POST["name"];
    $code         = $_POST["code"];
    $rate         = $_POST["rate"];
    $rateAd       = $_POST["rateAd"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    addOvertimeType($uid, $kind, $name, $code, $rate, $rateAd, $dateCreated, $dateModified);
});

$app->get("/get/overtime/type/data/:uid", function($uid){
    $response = array();
    $x        = getOvertimeTypeByUid($uid);

    $response = array(
        "uid"    => $x->overtime_type_uid,
        "kind"   => $x->overtime_kind,
        "name"   => $x->overtime_type_name,
        "code"   => $x->overtime_type_code,
        "rate"   => $x->rate,
        "rateAd" => $x->additional_rate,
        "status" => $x->status
    );

    echo jsonify($response);
});

$app->post("/edit/overtime/type/:uid", function($uid){
    $kind         = $_POST["kind"];
    $name         = $_POST["name"];
    $code         = $_POST["code"];
    $rate         = $_POST["rate"];
    $rateAd       = $_POST["rateAd"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    editOvertimeType($uid, $kind, $name, $code, $rate, $rateAd, $dateModified, $status);
});

//FOR EMPLOYEES
$app->get("/get/employee/leave/requests/details/:uid", function($uid){
    $response = array();
    $leave    = getEmpLeaveRequestsByEmpUid($uid);

    foreach($leave as $leaves){
        $response[] = array(
            "code"           => $leaves->leave_code,
            "uid"            => $leaves->leave_uid,
            "from"           => date("M d, Y", strtotime($leaves->start_date)),
            "to"             => date("M d, Y", strtotime($leaves->end_date)),
            "name"           => $leaves->leave_name,
            "reason"         => $leaves->reason,
            "request_status" => $leaves->leave_request_status,
            "date_created" => date("Y-m-d h:i A",  strtotime($leaves->date_created)),
            "date_modified" => date("Y-m-d h:i A",  strtotime($leaves->date_modified))
        );
    }

    echo jsonify($response);
});

$app->get("/get/employee/overtime/requests/:uid", function($uid){
    $response = array();
    $a        = getEmployeeOvertimeRequests($uid);

    foreach($a as $overtime){
        $response[] = array(
            "uid"           => $overtime->overtime_request_uid,
            "empUid"        => $overtime->emp_uid,
            "from"          => date("M d, Y", strtotime($overtime->start_date)),
            "reason"        => $overtime->reason,
            "hours"         => number_format($overtime->hours, 2),
            "certBy"        => $overtime->cert_by,
            "appBy"         => $overtime->appr_by,
            "request_status" => $overtime->overtime_request_status,
            "status"        => $overtime->status,
            "date_created"        => date("Y-m-d h:i A", strtotime($overtime->date_created)),
            "date_modified"        => date("Y-m-d h:i A", strtotime($overtime->date_modified))
        );
    }

    echo jsonify($response);
});

$app->get("/get/employee/overtime/request/details/:uid", function($uid){
    $response = array();
    $overtime = getOvertimeRequestsByUid($uid);

    if($overtime){
        $startDate  = $overtime->start_date;
        $startDates = date("Y-m-d", strtotime($startDate));
        $startHour  = date("h:i A", strtotime($startDate));
        
        $endDate    = $overtime->end_date;
        $endDates   = date("Y-m-d", strtotime($endDate));
        $endHour    = date("h:i A", strtotime($endDate));

        $response = array(
            "uid"           => $overtime->overtime_request_uid,
            "empUid"        => $overtime->emp_uid,
            "startDate"     => $startDates,
            "startHour"     => $startHour,
            "endDate"       => $endDates,
            "endHour"       => $endHour,
            "hours"         => $overtime->end_date,
            "reason"        => $overtime->reason,
            "requestStatus" => $overtime->overtime_request_status,
            "status"        => $overtime->status,
            "type"          => $overtime->type
        );
    }

    echo jsonify($response);
});

$app->get("/get/emp/frequency/:uid", function($uid){
    $response = array();
    $freq     = getFrequencyByEmpUid($uid);

    if($freq){
        $response = array(
            "frequencyName" => $freq->pay_period_name,
            "frequencyUid"  => $freq->pay_period_uid
        );
    }

    echo jsonify($response);
});

$app->post("/empshift/", function(){
    $empShiftUid  = $_POST["empShiftUid"];
    $userId       = $_POST["userId"];
    $shift        = $_POST["shift"];
    $batch        = $_POST["batch"];
    $shiftId      = $_POST["shiftId"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $check        = checkEmpShift($empShiftUid, $userId, $batch, $shiftId);
    if($check == 1){
        updateEmpShifts($empShiftUid, $userId, $batch, $shiftId, $dateModified);
    }else{
        insertEmpShift($empShiftUid, $userId, $batch, $shiftId, $dateCreated, $dateModified);
    }
});

$app->post("/empData/", function(){
    $empId    = xguid();
    $userType = $_POST["userType"];
    $userId   = $_POST["userId"];
    $fname    = $_POST["fname"];
    $lname    = $_POST["lname"];
    $mname    = $_POST["mname"];
    $status   = $_POST["status"];

    if($status == "Active"){
        $status = 1;
    }else if($status == "Inactive"){
        $status = 0;
    }

    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $check1       = checkEmpData($userId);
    $check2       = checkEmpUser($userId);

    if($check1 == 1){
        updateEmpData($userId, $fname, $lname, $mname, $dateModified, $status);
        updateEmpUser($userId, $userType, $dateModified, $status);
    }else{
        insertEmpData($userId, $fname, $lname, $mname, $dateCreated, $dateModified, $status);
        insertEmpUser($empId, $userId, $userType, $dateCreated, $dateModified, $status);
    }
    
});

$app->post("/empHolidays/", function(){
    $empId  = xguid();
    $date   = $_POST["date"];
    $name   = $_POST["name"];
    $type   = $_POST["type"];
    $status = $_POST["status"];

    if($status == "Active"){
        $status = 1;
    }else if($status == "Inactive"){
        $status = 0;
    }

    if($type == "Regular"){
        $type = "Regular holiday";
    }else if($type == "Special"){
        $type = "Special Holiday";
    }

    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $types        = getHolidayTypes($type);
    $check        = checkHoliday($date, $name);
    if($check == 1){
        updateEmpHoliday($date, $name, $types, $dateModified, $status);
    }else{
        insertHoliday($empId, $date, $name, $types, $dateCreated ,$dateModified, $status);
    }
});

$app->post("/shiftSettings/", function(){
    $shiftId      = $_POST["shiftId"];
    $timein       = $_POST["timein"];
    $timeout      = $_POST["timeout"];
    $shift        = $_POST["shift"];
    $batch        = $_POST["batch"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    $check = checkShift($shift, $batch);

    if($check == 1){
        updateShifts($shiftId, $timein, $timeout, $shift, $batch, $dateCreated, $dateModified);
    }else{
        insertShift($shiftId, $timein, $timeout, $shift, $batch, $dateCreated, $dateModified);
    }

});

$app->post("/salaryCap/", function(){
    $salaryUid    = xguid();
    $userId       = $_POST["userId"];
    $salary       = $_POST["salary"];
    $type         = $_POST["type"];
    
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $type         = getSalaryType($type);
    $check        = checkEmpSalary($userId, $type);
    
    $empUid       = getUserEmpUid($userId);

    if($check >= 1){
        updateSalaries($userId, $salary, $type, $dateModified);
        updateEmpType($userId, $type, $dateModified);
    }else{
        insertSalary($salaryUid, $empUid, $salary, $type, $dateCreated, $dateModified);
        newEmpType(xguid(), $type, $empUid, $dateCreated, $dateModified);
    }

});

$app->post("/usersJoin/", function(){
    $empUid       = xguid();
    $usertype     = $_POST["type"];
    $userId       = $_POST["userId"];
    $password     = $_POST["password"];
    $firstname    = utf8_decode($_POST["firstname"]);
    $lastname     = utf8_decode($_POST["lastname"]);
    $middlename   = utf8_decode($_POST["middlename"]);
    $status       = $_POST["status"];
    $username     = $userId;
    $marital      = "";
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $userIds      = xguid();
    $password     = sha1(Base32::decode($password));
    $ivSize       = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv           = mcrypt_create_iv($ivSize, MCRYPT_RAND);
    
    $check        = checkIfUserExisted($username);

    if($check >= 1){
        echo "existing";
    }else{
        newEmployee($empUid , $firstname , $middlename , $lastname, $marital, $usertype, $dateCreated , $dateModified);
        newUserAccount($userIds , $username , $password , $usertype , $empUid , $dateCreated , $dateModified);
        newUserUniqueKey(xguid(), $userIds, $iv , $dateCreated , $dateModified);
    }
    
});

$app->get("/restday/get/data/", function(){
    $rest     = getRestDay();
    $response = array();

    foreach($rest as $restDay){
        $response[] = array(
            "restUid" => $restDay->restday_uid,
            "name"    => $restDay->name
        );
    }

    echo jsonify($response);
});

$app->get("/get/restday/data/:uid", function($uid){
    $response = array();
    $rest     = getRestDayByUid($uid);

    if($rest){
        $response = array(
            "restUid"  => $rest->restday_uid,
            "restName" => $rest->name,
            "status"   => $rest->status,
        );
    }

    echo jsonify($response);
});

$app->post("/edit/resday/:uid", function($uid){
    $restDay      = $_POST["restday"];
    $status       = $_POST["status"];
    $dateModified = date("Y-m-d H:i:s");

    editRestDay($uid, $restDay, $dateModified, $status);
});

$app->post("/rest/new/", function(){
    $restDayUid   = xguid();
    $restDay      = $_POST["restDay"];
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    newRestDay($restDayUid, $restDay, $dateCreated, $dateModified);
});

/*-------------------------------------------START COMPLAINT MODULE (JEMUEL/MICHAEL)------------------ */
$app->post("/add/emp/complaint/request/:uid", function($uid){
    $complaintUid = xguid();
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    $subject      = $_POST["subject"];
    $description  = $_POST["description"];
    $imgUrl       = $_POST["imgUrl"];
    
    newComplaintRequest($complaintUid, $uid, $dateCreated, $dateModified, $subject, $description, $imgUrl);
});


$app->post("/edit/emp/complaint/request/:uid", function($uid){
    $subject      = $_POST["subject"];
    $description  = $_POST["description"];
    $imgUrl       = $_POST["imgUrl"];
    $dateModified = date("Y-m-d H:i:s");

    editComplaintsByUid($uid, $dateModified, $subject, $description, $imgUrl);
});

$app->get("/get/emp/complaint/requests/:uid", function($uid){
    $response  = array();
    $complaint = getComplaintsByEmpUid($uid);
    foreach($complaint as $complaints){
         $response[] = array(
            "id"           => $complaints["id"],
            "complaintUid" => $complaints["complaint_uid"],
            "empUid"       =>$complaints["emp_uid"],
            "subject"      => $complaints["subject"],
            "description"  => $complaints["description"],
            "imgUrl"       => $complaints["image_url"],
            "dateCreated"  =>array(
                "specDate" => date("M d, Y", strtotime($complaints["date_created"])),
                "specTime" => date("H:i:s", strtotime($complaints["date_created"]))
                ),
            "dateModified" =>array(
                "specDate" => date("M d, Y", strtotime($complaints["date_modified"])),
                "specTime" => date("H:i:s", strtotime($complaints["date_modified"]))
                ),
            "status"       =>$complaints["status"]
            );
       
    }
    echo jsonify($response);
});

$app->get("/get/emp/complaint/request/:uid",function($uid){
    $response  = array();
    $complaint = getComplaintsByUid($uid);
    if ($complaint) {
        $response = array(
             "id"           => $complaint["id"],
             "complaintUid" => $complaint["complaint_uid"],
             "empUid"       =>$complaint["emp_uid"],
             "subject"      => $complaint["subject"],
             "description"  => $complaint["description"],
             "imgUrl"       => $complaint["image_url"],
             "dateCreated"  =>array(
                "specDate" => date("M d, Y", strtotime($complaint["date_created"])),
                "specTime" => date("H:i:s", strtotime($complaint["date_created"]))
                ),
            "dateModified"  =>array(
                "specDate" => date("M d, Y", strtotime($complaint["date_modified"])),
                "specTime" => date("H:i:s", strtotime($complaint["date_modified"]))
                ),
            "status"        =>$complaint["status"]
            );
    }
    echo jsonify($response);
});


/*--------------------------------------------END COMPLAINT MODULE (JEMUEL/MICHAEL)--------------------*/

/* ---------------------------------------------- OFFSET --------------------------------------------- */

$app->get("/get/notif/offset/", function(){
    $response = array();

    editOffsetNotificationRead();
});

$app->get("/get/notif/offset/count/", function(){
    // $response = array();
    $count     = countRequestsOfOffset("pending");
    $accept    = countRequestsOfOffset("accepted");
    $certified = countRequestsOfOffset("certified");
    $denied    = countRequestsOfOffset("denied");

    $response = array(
        "pendingCount"   => $count,
        "acceptedCount"  => $accept,
        "certifiedCount" => $certified,
        "deniedCount"    => $denied
    );

    echo jsonify($response);
});

$app->get("/get/notif/offset/pending/count/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $count     = countPendingRequestsOfOffsetByDate($startDate, $endDate);

    $response = array(
        "pendingCount" => $count
    );

    echo jsonify($response);
});

$app->get("/get/notif/offset/accepted/count/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $count     = countAcceptedRequestsOfOffsetByDate($startDate, $endDate);

    $response = array(
        "pendingCount" => $count
    );

    echo jsonify($response);
});

$app->get("/get/offset/requests/", function(){
    $response = array();
    $offset   = getOffset();

    foreach($offset as $offsets){
        $name = utf8_decode($offsets["lastname"]) . ", " . utf8_decode($offsets["firstname"]) . " " . utf8_decode($offsets["middlename"]);
        $response[] = array(
            "emp"           => $name,
            "empNo"         => $offsets["username"],
            "offsetUid"     => $offsets["offset_uid"],
            "fromDate"      => date("M d, Y", strtotime($offsets["from_date"])),
            "setDate"       => date("M d, Y", strtotime($offsets["set_date"])),
            "requestStatus" => $offsets["request_status"],
            "certBy"        => $offsets["cert_by"],
            "appBy"         => $offsets["app_by"],
            "reason"        => $offsets["reason"]
        );
    }
    echo jsonify($response);
});

$app->get("/get/offset/requests/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $response  = array();
    $offset    = getOffsetByDate($startDate, $endDate);

    foreach($offset as $offsets){
        $name = utf8_decode($offsets["lastname"]) . ", " . utf8_decode($offsets["firstname"]) . " " . utf8_decode($offsets["middlename"]);
        $response[] = array(
            "emp"           => $name,
            "empNo"         => $offsets["username"],
            "offsetUid"     => $offsets["offset_uid"],
            "fromDate"      => date("M d, Y", strtotime($offsets["from_date"])),
            "setDate"       => date("M d, Y", strtotime($offsets["set_date"])),
            "requestStatus" => $offsets["request_status"],
            "certBy"        => $offsets["cert_by"],
            "appBy"         => $offsets["app_by"],
            "reason"        => $offsets["reason"]
        );
    }
    echo jsonify($response);
});

$app->get("/get/data/offset/request/:uid", function($uid){
    $offset = getOffsetRequestsByUid($uid);

    $response = array(
        "offsetUid"     => $offset["offset_uid"],
        "fromDate"      => $offset["from_date"],
        "setDate"       => $offset["set_date"],
        "reason"        => $offset["reason"],
        "requestStatus" => $offset["request_status"],
        "status"        => $offset["status"]
    );

    echo jsonify($response);
});

$app->post("/edit/offset/data/:uid", function($uid){
    $fromDate      = $_POST["fromDate"];
    $setDate       = $_POST["setDate"];
    $requestStatus = $_POST["requestStatus"];
    $status        = $_POST["status"];
    $admin         = $_POST["admin"];
    $reason        = $_POST["reason"];
    
    $dateModified  = date("Y-m-d H:i:s");
    
    $emp           = getEmployeeDetailsByUid($admin);
    $lastname      = $emp->lastname;
    $firstname     = $emp->firstname;
    $middlename    = $emp->middlename;
    
    $name          = $firstname . " " . $middlename . " " . $lastname;

    function getInitials($name){
        $words = explode(" ",$name);
        $inits = '';
        foreach($words as $word){
            $inits.=strtoupper(substr($word,0,1));
        }
        return $inits;  
    }

    $user = getInitials($name);

    if($requestStatus == "Certified"){
        $user1 = $user;
        $user2 = "";
        editOffset($uid, $fromDate, $setDate, $reason, $requestStatus, $user1, $user2, $dateModified, $status);
        editOffsetNotification($uid, $requestStatus, $dateModified);
    }else if($requestStatus == "Approved"){
        $user2 = $user;
        $user1 = "";
        editOffset($uid, $fromDate, $setDate, $reason, $requestStatus, $user1, $user2, $dateModified, $status);
        editOffsetNotification($uid, $requestStatus, $dateModified);
    }else{
        $user1 = "";
        $user2 = "";
        editOffset($uid, $fromDate, $setDate, $reason, $requestStatus, $user1, $user2, $dateModified, $status);
        editOffsetNotification($uid, $requestStatus, $dateModified);
    }

    
});

$app->post("/add/offset/request/", function(){
    $offsetUid      = xguid();
    $offsetNotifUid = xguid();
    $emp            = $_POST["emp"];
    $reason         = $_POST["reason"];
    $fromDate       = $_POST["fromDate"];
    $setDate        = $_POST["setDate"];
    $dateCreated    = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");

    if(strtotime($fromDate) <= strtotime($setDate)){
        $valid = true;
    }else{
        $valid = false;
    }

    $checkRequest = checkPayrollSchedBeforeRequest($fromDate);
    if($checkRequest["prompt"]){
        if($valid){
            newOffsetRequest($offsetUid, $emp, $fromDate, $setDate, $reason, $dateCreated, $dateModified);
            newOffsetNotification($offsetNotifUid, $offsetUid, $dateCreated, $dateModified);
            $response = array(
                "prompt" => 0
            );
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    }else{
        if($valid){
            $response = array(
                "prompt" => 1
            );
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    }
    echo jsonify($response);
    
});

$app->post("/request/offset/:uid", function($uid){
    $offsetUid      = xguid();
    $offsetNotifUid = xguid();
    $fromDate       = $_POST["fromDate"];
    $setDate        = $_POST["setDate"];
    $reason         = $_POST["reason"];
    
    $dateCreated    = date("Y-m-d H:i:s");
    $dateModified   = date("Y-m-d H:i:s");

    newOffsetRequest($offsetUid, $uid, $fromDate, $setDate, $reason,$dateCreated, $dateModified);
    newOffsetNotification($offsetNotifUid, $offsetUid, $dateCreated, $dateModified);
});

$app->get("/get/offset/request/details/:uid", function($uid){
    $response = array();
    
    $offset   = getEmployeeOffsetRequests($uid);
    foreach($offset as $offsets){
        $response[] = array(
            "uid"            => $offsets["offset_uid"],
            "from"           => date("M d, Y", strtotime($offsets["from_date"])),
            "setDate"        => date("M d, Y", strtotime($offsets["set_date"])),
            "request_status" => $offsets["request_status"],
            "reason"         => $offsets["reason"]

        );
    }

    echo jsonify($response);
});

$app->get("/emp/notif/get/offset/:uid", function($uid){
    $count = countAcceptedOffsetRequestByEmpUid($uid);

    $response = array(
        "acceptedCount" => $count
    );

    echo jsonify($response);
});

$app->get("/employee/read/offset/notification/:uid", function($uid){
    $dateModified = date("Y-m-d H:i:s");
    
    $check        = getOffsetNotificationUidByEmpUid($uid);
    foreach($check as $checks){
        $uid = $checks["offset_uid"];
        updateOffsetNotificationByUid($uid, $dateModified);
    }
});

$app->get("/employee/pending/offset/notification/:uid", function($uid){
    $count    = countOffsetPendingNotificationByEmpUid($uid);
    $response = array();

    if($count){
        $response = array(
            "count" => $count
        );
    }else{
        $response = array(
            "count" => 0
        );
    }

    echo jsonify($response);
});

$app->get("/get/time/offset/request/:var", function($var){
    $token    = $var;
    $response = array();
    $times    = getTimeOffset();

    foreach($times as $time){
        $lastname   = utf8_decode($time["lastname"]);
        $firstname  = utf8_decode($time["firstname"]);
        $middlename = utf8_decode($time["middlename"]);
        $name       = $lastname . ", " . $firstname . " " . $middlename;

        $response[] = array(
            "timeUid"       => $time["time_requests_uid"],
            "empNo"         => $time["username"],
            "employee"      => $name,
            "timeIn"        => date("h:i:s A", strtotime($time["time_in"])),
            "timeOut"       => date("h:i:s A", strtotime($time["time_out"])),
            "dateRequest"   => date("F d, Y", strtotime($time["date_request"])),
            "requestStatus" => $time["request_status"],
            "status"        => $time["status"],
            "certBy"        => $time["cert_by"],
            "appBy"         => $time["app_by"],
            "reason"        => $time["reason"]
        );
    }

    echo jsonify($response);
});

$app->get("/get/employee/adjustment/time/request/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $emp       = $param[2];
    
    $response  = array();
    $times     = getEmployeeTimeRequestsByDateRange($startDate, $endDate, $emp);

    foreach($times as $time){
        $lastname   = utf8_decode($time["lastname"]);
        $firstname  = utf8_decode($time["firstname"]);
        $middlename = utf8_decode($time["middlename"]);
        $name       = $lastname . ", " . $firstname . " " . $middlename;

        $response[] = array(
            "uid"       => $time["time_requests_uid"],
            "empNo"         => $time["username"],
            "employee"      => $name,
            "timeIn"        => date("h:i:s A", strtotime($time["time_in"])),
            "timeOut"       => date("h:i:s A", strtotime($time["time_out"])),
            "dateRequest"   => date("F d, Y", strtotime($time["date_request"])),
            "request_status" => $time["request_status"],
            "status"        => $time["status"],
            "certBy"        => $time["cert_by"],
            "appBy"         => $time["app_by"],
            "date_created"         => date("Y-m-d h:i A", strtotime($time["date_created"])),
            "date_modified"         => date("Y-m-d h:i A", strtotime($time["date_modified"])),
            "reason"        => $time["reason"]
        );
    }

    echo jsonify($response);
});

$app->get("/get/time/offset/request/date/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    $selStatus = $param[2];
    
    $response  = array();
    $times     = getTimeRequestsByDate($startDate, $endDate, $selStatus);


    foreach($times as $time){
        $lastnames = utf8_decode($time["firstname"]) . "_" . " ";

        $words = explode("_", $lastnames);
        $name = "";

        foreach ($words as $w) {
          $name .= $w[0];
        }

        $name = $name . ". " . utf8_decode($time["lastname"]);
    // echo "$startDate, $endDate, $selStatus";

        $username = getEmployeeUsernameByEmpUid($time["emp_uid"]);

        $response[] = array(
            "timeUid"       => $time["time_requests_uid"],
            "empNo"         => $username,
            "employee"      => $name,
            "timeIn"        => date("h:i A", strtotime($time["time_in"])),
            "timeOut"       => date("h:i A", strtotime($time["time_out"])),
            "dateRequest"   => date("M d, y", strtotime($time["date_request"])),
            "requestStatus" => $time["request_status"],
            "status"        => $time["status"],
            "certBy"        => $time["cert_by"],
            "appBy"         => $time["app_by"],
            "date_created"         => date("m-d-y h:i A", strtotime($time["date_created"])),
            "date_modified"         => date("m-d-y h:i A", strtotime($time["date_modified"])),
            "reason"        => $time["reason"]
        );
    }

    echo jsonify($response);
});

$app->get("/get/time/offset/request/edit/:uid", function($uid){
    $response = array();
    $time     = getOffsetTimeRequestByUid($uid);

    if($time){
        $response = array(
            "timeUid"       => $time->time_requests_uid,
            "timeIn"        => date("Y-m-d h:i A", strtotime($time->time_in)),
            "timeOut"       => date("Y-m-d h:i A", strtotime($time->time_out)),
            "dateReq"       => $time->date_request,
            "requestStatus" => $time->request_status,
            "employee"      => $time->emp_uid,
            "status"        => $time->status,
            "reason"        => $time->reason
        );
    }

    echo jsonify($response);
});

$app->post("/set/time/:emp", function($emp){
    $session      = xguid();
    $timeInUid    = xguid();
    $timeOutUid   = xguid();
    
    $timeIn       = $_POST["timeIn"];
    $timeOut      = $_POST["timeOut"];
    $timeDate     = $_POST["timeDate"];
    $typeIn       = 0;
    $typeOut      = 1;
    $timeIn       = date("Y-m-d H:i", strtotime($timeIn));
    $timeOut      = date("Y-m-d H:i", strtotime($timeOut));
    
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    $day          = date("N", strtotime($timeDate));
    $status       = 1;
    
    $rule         = getShiftUidInRules($emp, $day);
    if($rule){
        $shift = $rule["shift"];
    }
    if(strtotime($timeIn) <= strtotime($timeOut)){
        $valid = true;
    }else{
        $valid = false;
    }

    // $check = checkTimeRequest($timeDate, $emp);
    // if($check){
    //     $response = array(
    //         "prompt" => 2
    //     );
    // }else{
        if($valid){
            addTimeSheetIn($timeInUid, $emp, $shift, $session, $typeIn, $timeIn, $status);
            addTimeSheetOut($timeOutUid, $emp, $shift, $session, $typeOut, $timeOut, $status);
            $response = array(
                "prompt" => 0
            );
        }else{
            $response = array(
                "prompt" => 3
            );
        }
    // }

    echo jsonify($response);
});

$app->get("/get/time/log/date/:var", function($var){
    $params  = explode(".", $var);
    $emp     = $params[0];
    $date    = date("Y-m-d", strtotime($params[1]));
    
    $timeIn  = getTimeLogInByEmpAndDate($emp, $date);
    $timeOut = getTimeLogOutByEmpAndDate($emp, $date);
    if($timeIn && $timeOut){
        $response = array(
            "timeIn"           => date("Y-m-d h:i A", strtotime($timeIn->date_created)),
            "timeOut"          => date("Y-m-d h:i A", strtotime($timeOut->date_created)),
            "timeshift"        => $timeIn->name,
            "timeInDisplay"    => date("M d, Y h:i A", strtotime($timeIn->date_created)),
            "timeOutDisplay"   => date("M d, Y h:i A", strtotime($timeOut->date_created)),
            "timeshiftDisplay" => $timeIn->name
        );
    }else if($timeIn && !$timeOut){
        $response = array(
            "timeIn"           => date("Y-m-d h:i:s A", strtotime($timeIn->date_created)),
            "timeOut"          => "",
            "timeshift"        => $timeIn->name,
            "timeInDisplay"    => date("M d, Y h:i:s A", strtotime($timeIn->date_created)),
            "timeOutDisplay"   => "N/A",
            "timeshiftDisplay" => $timeIn->name
        );
    }else{
        $response = array(
            "timeIn"           => "",
            "timeOut"          => "",
            "timeshift"        => "",
            "timeInDisplay"    => "N/A",
            "timeOutDisplay"   => "N/A",
            "timeshiftDisplay" => "N/A"
        );
    }

    echo jsonify($response);
});



$app->post("/request/time/adjustment/", function(){
    $timeUid      = xguid();
    $employee     = $_POST["employee"];
    $timeIn       = $_POST["timeIn"];
    $timeOut      = $_POST["timeOut"];
    $timeDate     = $_POST["timeDate"];
    $reason       = $_POST["reason"];
    $timeIn       = date("Y-m-d H:i", strtotime($timeIn));
    $timeOut      = date("Y-m-d H:i", strtotime($timeOut));
    
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    // $timeOutPlusOneDay = strtotime(date("Y-m-d", $timeIn) . "+1 day");
    if(strtotime($timeIn) <= strtotime($timeOut)){
        $valid = true;
    }else{
        $valid = false;
    }

    $check = checkTimeRequest($timeDate, $employee);
    if($check){
        $response = array(
            "prompt" => 2
        );
    }else{
        $checkRequest = checkPayrollSchedBeforeRequest($timeDate);
        if($checkRequest["prompt"]){
            if($valid){
                $dateIn  = date("Y-m-d", strtotime($timeIn));
                $dateOut = date("Y-m-d", strtotime($timeOut));

                if(strtotime($dateIn) > strtotime($dateOut) ||  strtotime($dateIn) != strtotime($timeDate)){
                    $response = array(
                        "prompt" => 3
                    );
                }else{
                    $checkAbsent = checkAbsentByDateAndEmpUid($employee, $timeDate);
                    if($checkAbsent){
                        $response = array(
                            "prompt" => 5
                        );
                    }else{
                        addTimeRequest($timeUid, $employee, $timeIn, $timeOut, $timeDate, $reason,$dateCreated, $dateModified);
                        addTimeReqNotification(xguid(), $timeUid, $dateCreated, $dateModified);
                        $response = array(
                            "prompt" => 0
                        );
                    }
                }
            }else{
                $response = array(
                    "prompt" => 4
                );
            }
        }else{
            if($valid){
                $response = array(
                    "prompt" => 1,
                    "req" => $checkRequest
                );
            }else{
                $response = array(
                    "prompt" => 4
                );
            }
        }
    }
    echo jsonify($response);
});

$app->get("/read/time/count/", function(){
    $count    = countPendingRequestsOfTimeReq();
    $accepted = countAcceptedRequestsOfTimeReq();
    $notif    = countTimeNotif();

    // if($count >= 300){
    //     $count = "300+";
    // }

    // if($accepted >= 300){
    //     $count = "300+";
    // }
    $dateModified = date("Y-m-d H:i:s");
    
    editRequestsOfTimeReq($dateModified);
    $response = array(
        "pendingCount" => $count,
        "accepted"     => $accepted,
        "notif"        => $notif
    );

    echo jsonify($response);
});


$app->get("/get/time/req/count/:var", function($var){
    $param     = explode(".", $var);
    $startDate = $param[0];
    $endDate   = $param[1];
    
    $count     = countPendingRequestsOfTimeReqByDate($startDate, $endDate);
    $accept    = countAcceptedRequestsOfTimeReqByDate($startDate, $endDate);
    $response = array(
        "pendingCount" => $count,
        "accepted"     => $accept
    );

    echo jsonify($response);
});

$app->get("/time/delete/:uid", function($uid){
    // $status = 0;
    deleteTimeByUid($uid);

    $response = array(
        "prompt" => 1
    );

    echo jsonify($response);
});

$app->post("/edit/time/request/:uid", function($uid){
    $response     = array();
    $timeInUid    = xguid();
    $timeOutUid   = xguid();
    $session      = xguid();
    $timeIn       = $_POST["timeIn"];
    $timeIn       = date("Y-m-d H:i", strtotime($timeIn));
    $typeIn       = 0;
    $logIn        = "IN";
    $timeOut      = $_POST["timeOut"];
    $timeOut      = date("Y-m-d H:i", strtotime($timeOut));
    $typeOut      = 1;
    $logOut       = "OUT";
    $timeDate     = $_POST["timeDate"];
    $reason       = $_POST["reason"];
    $admin        = $_POST["admin"];
    
    $reqStatus    = $_POST["reqStatus"];
    $status       = $_POST["status"];
    $employee     = $_POST["employee"];
    $day          = date("N", strtotime($timeDate));
    $dateModified = date("Y-m-d H:i:s");
    $sTimeIn      = date("H:i:s", strtotime($timeIn));
    $sTimeOut     = date("H:i:s", strtotime($timeOut));
    
    $username     = getEmployeeUsernameByEmpUid($employee);
    
    $emp          = getEmployeeDetailsByUid($admin);
    $lastname     = $emp->lastname;
    $firstname    = $emp->firstname;
    $middlename   = $emp->middlename;
    
    $name         = $firstname . " " . $middlename . " " . $lastname;

    function getInitials($name){
        $words = explode(" ",$name);
        $inits = '';
        foreach($words as $word){
            $inits.=strtoupper(substr($word,0,1));
        }
        return $inits;  
    }

    $user = getInitials($name);

    if(strtotime($timeIn) <= strtotime($timeOut)){
        $valid = true;
    }else{
        $valid = false;
    }

    $checkRequest = checkPayrollSchedBeforeRequest($timeDate);
    $type         = getUserTypeByEmpUid($admin);

    if($type == "Administrator"){
        if($valid){
            $rule = getShiftUidInRules($employee, $day);
            if($rule){
                $shift = $rule["shift"];
                if($reqStatus == "Approved"){
                    $user1 = $user;
                    $user2 = "";
                    $check = checkTimeDateByEmpUid($employee, $timeDate);
                    if($check){
                        removeDateFromTimeInLogByEmpAndDate($employee, $timeDate);
                        removeDateFromTimeOutLogByEmpAndDate($employee, $timeDate);
                        addTimeSheetIn($timeInUid, $employee, $shift, $session, $typeIn, $timeIn, $status);
                        addTimeSheetOut($timeOutUid, $employee, $shift, $session, $typeOut, $timeOut, $status);
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 3
                        );
                    }else{
                        removeDateFromTimeInLogByEmpAndDate($employee, $timeDate);
                        removeDateFromTimeOutLogByEmpAndDate($employee, $timeDate);
                        addTimeSheetIn($timeInUid, $employee, $shift, $session, $typeIn, $timeIn, $status);
                        addTimeSheetOut($timeOutUid, $employee, $shift, $session, $typeOut, $timeOut, $status);
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0
                        );
                    }
                }else if($reqStatus == "Certified"){
                    $user2 = $user;
                    $user1 = "";
                    $check = checkTimeDateByEmpUid($employee, $timeDate);
                    if($check){
                        $response = array(
                            "prompt" => 3
                        );
                    }else{
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0
                        );
                    }
                }
            }else{
                $response = array(
                    "prompt" => 2
                );
            }

            $dateModified = date("Y-m-d H:i:s");

            if($reqStatus == "Approved"){
                $user1 = $user;
                $user2 = "";
                $check = checkTimeDateByEmpUid($employee, $timeDate);
                if($check){
                    $response = array(
                        "prompt" => 3
                    );
                }else{
                    editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                    $response = array(
                        "prompt" => 1
                    );
                }
            }else if($reqStatus == "Certified"){
                $user2 = $user;
                $user1 = "";
                editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                $response = array(
                    "prompt" => 1
                );
            }else{
                $user2 = "";
                $user1 = "";
                editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                $response = array(
                    "prompt" => 1
                );
            }
        }else{
            $response = array(
                "prompt" => 4
            );
        }
    }else{
        if($checkRequest["prompt"]){
            if($valid){
                $rule = getShiftUidInRules($employee, $day);
                if($rule){
                    $shift = $rule["shift"];
                    if($reqStatus == "Approved"){
                        $user1 = $user;
                        $user2 = "";
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0
                        );
                    }else if($reqStatus == "Certified"){
                        $user2 = $user;
                        $user1 = "";
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0
                        );
                    }
                }else{
                    $response = array(
                        "prompt" => 2
                    );
                }

                $dateModified = date("Y-m-d H:i:s");

                if($reqStatus == "Approved"){
                    $user1 = $user;
                    $user2 = "";
                    $check = checkTimeDateByEmpUid($employee, $timeDate);
                    if($check){
                        $response = array(
                            "prompt" => 3
                        );
                    }else{
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 1
                        );
                    }
                }else if($reqStatus == "Certified"){
                    $user2 = $user;
                    $user1 = "";
                    editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                    $response = array(
                        "prompt" => 1
                    );
                }else{
                    $user2 = "";
                    $user1 = "";
                    editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                    $response = array(
                        "prompt" => 1
                    );
                }
            }else{
                $response = array(
                    "prompt" => 4
                );
            }
        }else{
            if($valid){
                $response = array(
                    "prompt" => 5
                );
            }else{
                $response = array(
                    "prompt" => 4
                );
            }
        }
    }
    
    echo jsonify($response);
});

$app->get("/get/employee/adjustment/time/request/:uid", function($uid){
    $response = array();
    $times = getEmployeeTimeAdjustmentRequests($uid);

    foreach($times as $time){
        $response[] = array(
            "uid"            => $time["time_requests_uid"],
            "timeIn"         => date("h:i:s A", strtotime($time["time_in"])),
            "timeOut"        => date("h:i:s A", strtotime($time["time_out"])),
            "dateRequest"    => date("F d, Y", strtotime($time["date_request"])),
            "request_status" => $time["request_status"],
            "status"         => $time["status"],
            "date_created"         => date("Y-m-d h:i A", strtotime($time["date_created"])),
            "date_modified"         => date("Y-m-d h:i A", strtotime($time["date_modified"])),
            "reason"         => $time["reason"]
        );
    }

    echo jsonify($response);
});

/* ---------------------------------------------- END OF OFFSET --------------------------------------------- */

$app->get("/get/user/data/:uid", function($uid){
    $x = getEmployeeDetailsByUid($uid);
    $response = array();
	if($x){
        $name = utf8_decode($x->lastname) . ", " . utf8_decode($x->firstname) . " " . utf8_decode($x->middlename);
        $response = array(
            "name" => $name
        );
    }

    echo jsonify($response);
});

/*---------------------------------------------FOR PRINTING---------------------------------------------*/
$app->post("/get/details/", function(){
    $startDate = $_POST["startDate"];
    $endDate   = $_POST["endDate"];
    $employee  = $_POST["employee"];
    $response  = array();
    
    $startDate = date("F d, Y", strtotime($startDate));
    $endDate   = date("F d, Y", strtotime($endDate));
    
    $emp       = getEmployeeDetailsByUid($employee);
    if($emp){
        $firstname  = utf8_decode($emp["firstname"]);
        $middlename = utf8_decode($emp["middlename"]);
        $lastname   = utf8_decode($emp["lastname"]);
        $name       = $lastname . ", " . $firstname . " " . $middlename;


        $response = array(
            "startDate" => $startDate,
            "endDate"   => $endDate,
            "name"      => $name
        );
    }
    echo jsonify($response);
});

$app->post("/get/timesheet/dates/", function(){
    $startDate = $_POST["startDate"];
    $endDate   = $_POST["endDate"];
    $response  = array();
    
    $startDate = date("F d, Y", strtotime($startDate));
    $endDate   = date("F d, Y", strtotime($endDate));

    $response = array(
        "startDate" => $startDate,
        "endDate"   => $endDate
    );
    echo jsonify($response);
});
$app->post("/get/date/details/", function(){
    $startDate = $_POST["startDate"];
    $endDate   = $_POST["endDate"];
    
    $startDate = date("F d, Y", strtotime($startDate));
    $endDate   = date("F d, Y", strtotime($endDate));

    $response = array(
        "startDate" => $startDate,
        "endDate"   => $endDate
    );
    echo jsonify($response);
});
$app->post("/emp/change/password/:uid", function($uid){
    $response     = array();
    $currentPass  = $_POST["currentPass"];
    $newPass      = $_POST["newPass"];
    $reEnterPass  = $_POST["reEnterPass"];
    
    $uxPassword   = sha1(Base32::decode($_POST["currentPass"]));
    $password     = sha1(Base32::decode($_POST["newPass"]));
    $dateModified = date("Y-m-d H:i:s");
    $check        = checkIfPasswordIsCorrectByEmpUid($uid, $uxPassword);
    if($check){
        updateEmpPassword($uid, $password, $dateModified);
        $response = array(
            "prompt" => 0
        );
    }else{
        $response = array(
            "prompt" => 1
        );
    }

    echo jsonify($response);
});

$app->post("/emp/change/password/admin/:uid", function($uid){
    $response     = array();
    $newPass      = $_POST["newPass"];
    $reEnterPass  = $_POST["reEnterPass"];
    
    $password     = sha1(Base32::decode($_POST["newPass"]));
    $dateModified = date("Y-m-d H:i:s");
    updateEmpPassword($uid, $password, $dateModified);
    $response = array(
        "prompt" => 0
    );

    echo jsonify($response);
});

$app->get("/time/data/cost/center/:var", function($var){
    $param     = explode(".", $var);
    $uid       = $param[2];
    $startDate = date('Y-m-d', strtotime($param[0]));
    $endDate   = date('Y-m-d', strtotime($param[1]));
    
    $x         = generateEmployeesTimesheet($startDate, $endDate, $uid);
    echo jsonify($x);
});

$app->get("/melot/testing/", function(){
    $uid = "49F29221-5EFD-0C46-A2B0-768D617F3C2C";
    $startDate = "2015-09-15";
    $endDate = "2015-09-16";
    $employeeDetails = getEmployeeByCostCenterUid($uid);

    $startDateString = strtotime($startDate);
    $endDateString = strtotime($endDate);

    foreach ($employeeDetails as $data) {
        $employeeUid = $data->emp_uid;
        $employeeUsername = $data->username;

        $work = 0;
        $late = 0;
        $overtime = 0;
        $undertime = 0;

        $employeeDetails = getEmployeeDetailsByUid($employeeUid);
        if($employeeDetails){
            $lastnames = utf8_decode($employeeDetails->firstname) . "_" . " ";
            $words = explode("_", $lastnames);
            $name = "";

            foreach ($words as $w) {
              $name .= $w[0];
            }

            $lastname = $name . ". " . utf8_decode($employeeDetails->lastname);
            
        }//end of getEmployeeDetailsByUid Function

        for($i=$startDateString; $i<=$endDateString; $i+=86400){
            $date =  date("Y-m-d", $i);
            $day = date("l", $i);

            //Get Time In
            $timeInDetails = getTimeIn($employeeUid, $date);
            $timeInDate = date("Y-m-d", strtotime($timeInDetails["date_created"]));
            
            //Get Holiday
            $holidayDetails = getHolidayByDate($date);
            $holidayDate = $holidayDetails["date"];

            //Get Absent
            $absentDetails = getAbsentRequestByDateAndEmpUid($employeeUid, $date);
            if($absentDetails){
                $absentDate = date("Y-m-d", strtotime($absentDetails->start_date));
                $prompt = 5; //ABSENT
            }else{
                $absentDate = 0;
            }

            //Get Rest Day
            $restDayName = 0;
            $restDayDetails = getRestDayByDay($day);
            if($restDayDetails){
                $restDayName = $restDayDetails["name"];
            }//end of getting restDay

            if(date("l", $i) === $restDayName){
                $sun = date("Y-m-d", $i);
                $prompt = 2; //Rest Day
                $time = "Rest Day";
            }//end of comparing day

            //Get Leave
            $leaveDetails = getLeaveRequestsByEmpUidAndDate($employeeUid, $date);
            if($leaveDetails){
                $leaveStartDate = $leaveDetails->start_date;
                $leaveEndDate = $leaveDetails->end_date;

                $leaveDay = date("l", strtotime($date));
                $prompt = 4;
                $time = "LEAVED";
            }//end of getting leave

            if($holidayDate == $date){
                if($holidayDate === $timeInDate){
                    $holidayDate = $timeInDate;
                    $prompt = 1; //HAS TIME IN
                    $time = $timeInDetails["date_created"];
                }else{
                    $prompt = 3; //HOLIDAY
                    $time = "HOLIDAY";
                }
            }else if($absentDate === $date){
                $prompt = 5; //ABSENT
            }else if($timeInDate != $date && $holidayDate != $date){
                $prompt = 0; // ABSENT
                $time = "ABSENT";
            }else{
                $holidayDate = 0;
                $prompt = 1;
                $time = $timeInDetails["date_created"];
            }

            switch ($prompt) {
                case 0: //FOR OFFSET OR ABSENT
                    $offsetDetails = getAcceptedOffsetRequestByEmpUid($employeeUid, $date);
                    // print_r($offsetDetails);

                    if($offsetDetails){
                        $offsetId = $offsetDetails->offset_uid;
                        $offsetEmpUid = $offsetDetails->emp_uid;
                        $offsetFromDate = $offsetDetails->from_date;
                        $offsetSetDate = $offsetDetails->set_date;
                        $offsetDay = date("N", strtotime($offsetSetDate));

                        $OffsetTimeInDetails = getOffsetTimeInByEmpUidAndDate($employeeUid, $offsetFromDate);
                        foreach($OffsetTimeInDetails as $offsetTimeIn){
                            //TIME IN
                            $offsetTimeInUid = $offsetTimeIn["time_log_uid"];
                            $offsetTimeInData = $offsetTimeIn["date_created"];
                            $offsetTimeInDate = date("Y-m-d", strtotime($offsetTimeInData));
                            $offsetTimeInDay = date("N", strtotime($offsetTimeInDate));
                            $offsetTimeInSession = $offsetTimeIn["session"];

                            //TIME OUT
                            $timeOutDetail = getTimeOutByEmpUidAndSessionNoLoc($employeeUid, $offsetTimeInSession);
                            $timeOutUid = $timeOutDetail["time_log_uid"];
                            $timeOut = $timeOutDetail["date_created"];
                            $timeOutDate = date("Y-m-d", strtotime($timeOut));

                            $timeOutHour = date("H:i:s", strtotime($timeOut));
                            $timeInHour = date("H:i:s", strtotime($offsetTimeInData));

                            //SHIFT
                            $shift = getShiftByTimeInUid($offsetTimeInUid);
                            $shiftStart = $shift->start;
                            $shiftEnd = $shift->end;

                            $shiftEnds = $shiftEnd;
                            $shiftStarts = $shiftStart;

                            $shiftDuration = getEmployeeShiftDuration($employeeUid, $shiftStart, $shiftEnd, $timeInHour, $offsetTimeInUid);

                            /*---------------------OVERTIME---------------------*/
                            if(strtotime($shiftEnd) <= strtotime($timeOutHour)){
                                if($offsetTimeInDate === $timeOutDate){
                                    $overtime = (strtotime($timeOutHour) - strtotime($shiftEnd)) / 3600;
                                }else{
                                    $shiftEnds = $timeOutDate . $shiftEnds;
                                    $overtime = (strtotime($out) - strtotime($shiftEnds)) / 3600;
                                }
                            }else if(strtotime($shiftEnd) >= strtotime($timeOutHour)){
                                if($offsetTimeInDate === $timeOutDate){
                                    $overtime = 0;
                                }else{
                                    $shiftEnd = date("Y-m-d", strtotime($offsetTimeInData . "- 0 day")) . " $shiftEnd"; 
                                    $overtime = (strtotime($timeOut) - strtotime($shiftEnd)) / 3600;
                                }
                            }

                            if($overtime > 60){
                                $overtime = 0;
                            }else if($overtime <= -1 ){
                                $overtime = 0;
                            }

                            if($overtime <= 0){
                                $response[] = array(
                                    "id" => $id,
                                    "inId" => 0,
                                    "outId" => 0,
                                    "prompt" => $prompt,
                                    "lastname" => strtoupper($lastname),
                                    "dates" => $date,
                                    "date" => date("M d, Y", strtotime($date)),
                                    "day" => $day,
                                    "in" => "NO TIME IN",
                                    "out" => "NO TIME OUT",
                                    "late" => "--",
                                    "tardiness" => "--",
                                    "overtime" => "--",
                                    "undertime" => "--",
                                    "work" => "--",
                                    "totalWorked" => "--",
                                    "totalLate" => "--",
                                    "totalOvertime" => "--",
                                    "totalUndertime" => "--",
                                    "approveOTStatus" => "0",
                                    "location" => "--=--",
                                    "empNo" => $empNo
                                );
                            }else{
                                if($overtime === $shiftDuration){
                                    $totalOvertime = $shiftDuration;
                                }else if($overtime > $shiftDuration){
                                    $totalOvertime = $shiftDuration;
                                    
                                }else if($overtime < $shiftDuration){
                                    $totalOvertime = $overtime - 1;
                                }//end of getting total overtime
                                    
                                $overtimeHour = floor($totalOvertime);
                                $totalOvertimeMin = (60*($totalOvertime-$overtimeHour));
                                $overtimeMin = floor(60*($totalOvertime-$overtimeHour));
                                $overtimeMin1 = floor($totalOvertimeMin);
                                $overtimeSec = floor(60*($totalOvertimeMin-$overtimeMin1));

                                $overtimes = new dateTime("$overtimeHour:$overtimeMin:$overtimeSec");

                                /*FOR SECOND OUT*/
                                $secondTotalOvertime = $totalOvertime;
                                $secondOvertimeHour = floor($secondTotalOvertime);
                                $secondTotalOvertimeMin = (60*($secondTotalOvertime-$secondOvertimeHour));
                                $secondOvertimeMin = floor(60*($secondTotalOvertime-$secondOvertimeHour));
                                $secondOvertimeMin1 = floor($secondTotalOvertimeMin);
                                $secondOvertimeSec = floor(60*($secondTotalOvertimeMin-$secondOvertimeMin1));
                                $secondOvertime = new dateTime("$secondOvertimeHour:$secondOvertimeMin:$secondOvertimeSec");
                                $secondOvertimeTime = date_format($secondOvertime, "H:i:s");
                                /*---------------------END OF OVERTIME---------------------*/

                                /*---------------------UNDERTIME---------------------*/
                                $secs = strtotime($secondOvertimeTime)-strtotime("00:00:00");

                                $offsetDay = date("N", strtotime($offsetSetDate));
                                $shift = getOffsetShiftByUidAndDay($employeeUid, $offsetDay);

                                $shiftStart = $shift->start;
                                $shiftEnd = $shift->end;

                                $overt = 0;
                                    
                                $secondOut = date("H:i:s", strtotime($shiftStart)+$secs);

                                if(strtotime($secondOut) <= strtotime($shiftEnd)){
                                    $undertime = (strtotime($shiftEnd) - strtotime($secondOut)) / 3600;
                                }if(strtotime($secondOut) >= strtotime($shiftEnd)){
                                    $overt = (strtotime($secondOut) - strtotime($shiftEnd) / 3600);
                                }

                                $totalUndertime = $undertime;
                                $undertimeHour = floor($totalUndertime);
                                $totalUndertimeMin = (60*($totalUndertime-$undertimeHour));
                                $undertimeMin = floor(60*($totalUndertime-$undertimeHour));
                                $undertimeMin1 = floor($totalUndertimeMin);
                                $undertimeSec = floor(60*($totalUndertimeMin-$undertimeMin1));

                                if($undertimeMin >= 60){
                                    $undertimeMin = 0;
                                    $undertimeHour = $undertimeHour + 1;
                                }else{
                                    $undertimeMin = $undertimeMin;
                                }
                                $undertimes = "$undertimeHour:$undertimeMin:00";

                                $totalOvert = $overt;
                                $overtHour = floor($totalOvert);
                                $totalOvertMin = (60*($totalOvert-$overtHour));
                                $overtMin = floor(60*($totalOvert-$overtHour));
                                $overtMin1 = floor($totalOvertMin);
                                $overtSec = floor(60*($totalOvertMin-$overtMin1));
                                if($overtMin >= 60){
                                    $overtMin = 0;
                                    $overtHour = $overtHour + 1;
                                }else{
                                    $overtMin = $overtMin;
                                }
                                $overt = "$overtHour:$overtMin:00";
                                $totalWorked = $totalOvertime - $totalUndertime;
                                $totalWorked = abs($totalWorked);
                                /*---------------------END OF UNDERTIME---------------------*/

                                $response[] = array(
                                    "id" => $employeeUid,
                                    "inId" => 0,
                                    "outId" => 0,
                                    "prompt" => 6,
                                    "lastname" => strtoupper($lastname),
                                    "dates" => $date,
                                    "date" => date("M d, Y", strtotime($date)),
                                    "day" => $day,
                                    "in" => date("h:i:s A", strtotime($shiftStart)),
                                    "out" => date("h:i:s A", strtotime($secondOut)),
                                    "late" => "00:00:00",
                                    "tardiness" => "00:00:00",
                                    "overtime" => "00:00:00",
                                    "undertime" => date("H:i:s", strtotime($undertimes)),
                                    "work" => date_format($overtimes, "H:i:s"),
                                    "totalWorked" => $totalWorked,
                                    "totalLate" => "OFFSET",
                                    "totalOvertime" => "OFFSET",
                                    "totalUndertime" => $totalUndertime,
                                    "approveOTStatus" => "0",
                                    "location" => "--=--",
                                    "empNo" => $employeeUsername

                                );
                            }//end of if-else
                            
                        }//end of getting Offset Time IN
                    }else{
                        $response[] = array(
                            "id" => $employeeUid,
                            "inId" => 0,
                            "outId" => 0,
                            "prompt" => $prompt,
                            "lastname" => strtoupper($lastname),
                            "dates" => $date,
                            "date" => date("M d, Y", strtotime($date)),
                            "day" => $day,
                            "in" => "NO TIME IN",
                            "out" => "NO TIME OUT",
                            "late" => "--",
                            "tardiness" => "--",
                            "overtime" => "--",
                            "undertime" => "--",
                            "work" => "--",
                            "totalWorked" => "--",
                            "totalLate" => "--",
                            "totalOvertime" => "--",
                            "totalUndertime" => "--",
                            "approveOTStatus" => "0",
                            "location" => "--=--",
                            "empNo" => $employeeUsername
                        );
                    }// end of checking offset
                    break;

                case 1: //FOR PRESENT
                    $check = checkTimeInByEmpUidAndDate($employeeUid, $date);
                    if($check){
                        $timeInDetails = getTimeInByEmpUidAndDate($employeeUid, $date);
                    }else{
                        $timeInDetails = getTimeInByEmpUidAndDateNoLoc($employeeUid, $date);
                    }// end of checking

                    foreach($timeInDetails as $timeIn){
                        $timeInUid = $timeIn["time_log_uid"];
                        $timeInData = $timeIn["date_created"];
                        $timeInDate = date("Y-m-d", strtotime($timeInData));
                        $timeInDay = date("N", strtotime($timeInDate));
                        $timeInSession = $timeIn["session"];

                        $inLoc = "--";
                        $timeOutDetails = getTimeOutByEmpUidAndSessionNoLoc($employeeUid, $timeInSession);
                        $outLoc = "--";

                        $locations = getTimeInLocationByTimeUid($timeInUid);
                        if($locations){
                            $timeInLocation = $locations["name"];
                            $timeOutDetails = getTimeOutByEmpUidAndSession($employeeUid, $timeInSession);
                            $timeOutLocation = $timeOutDetails["name"];
                        }

                        $timeOutUid = $timeOutDetails["time_log_uid"];
                        $timeOutData = $timeOutDetails["date_created"];
                        $timeOutDate = date("Y-m-d", strtotime($timeOutData));

                        $timeInHour = date("H:i:s", strtotime($timeInData));
                        $timeOutHour = date("H:i:s", strtotime($timeOutData));

                        $shiftDetails = getShiftByTimeInUid($timeOutUid);
                        if(!$timeOutDetails || !$shiftDetails){
                            $response[] = array(
                                "id" => $id,
                                "inId" => $inId,
                                "outId" => "No Time Out!",
                                "prompt" => "",
                                "lastname" => strtoupper($lastname),
                                "dates" => $date,
                                "date" => date("M d, Y", strtotime($date)),
                                "day" => $day,
                                "in" => date("h:i:s A", strtotime($in)),
                                "out" => "No Time Out!",
                                "late" => "00:00:00",
                                "tardiness" => "",
                                "overtime" => "00:00:00",
                                "undertime" => "00:00:00",
                                "work" => "00:00:00",
                                "totalWorked" => "00:00:00",
                                "totalLate" => "00:00:00",
                                "totalOvertime" => "00:00:00",
                                "totalUndertime" => "00:00:00",
                                "approveOTStatus" => "",
                                "location" => $inLoc . "=--",
                                "empNo" => $empNo
                            );
                        }else{
                            $shiftStart = $shiftDetails->start;
                            $shiftEnd = $shiftDetails->end;
                            $grace = $shiftDetails->grace_period;

                            $shiftEnds = $shiftEnd;
                            $shiftStarts = $shiftStart;

                            if($grace != 0){
                                $dapatIn = date("H:i:s", strtotime("+$grace minutes", strtotime($shiftStart)));
                            }else{
                                $dapatIn = date("H:i:s", strtotime($shiftStart));
                            }

                            $dapatInHour = date("H:i:s", strtotime($dapatIn));

                            //pasted worked f //to be editted
                            if(strtotime($timeOutData) < strtotime($timeInData)){
                                $work = (strtotime($timeInData) - strtotime($out)) / 3600;
                            }else if(strtotime($timeOutData) > strtotime($timeInData)){
                                $work = (strtotime($timeOutData) - strtotime($timeInData)) / 3600;
                            }
                            //end pasted worked

                            //Get Shift
                            $shiftDuration = getEmployeeShiftDuration($employeeUid, $shiftStart, $shiftEnd, $timeInHour, $timeInUid);

                            if($work === $shiftDuration){
                                $totalWork = $shiftDuration;
                            }else if($work > $shiftDuration){
                                $totalWork = $shiftDuration;
                            }else if($work < $shiftDuration){
                                $totalWork = $work;
                            }//end of getting total work

                            if(strtotime($inn) >= strtotime($inHour)){
                                if($late === $lateCount){
                                    for($x=0; $x < count($inArray); $x++){
                                        if(in_array($empDate, $inArray[$x])){
                                            $getFirstIn[] = $inArray[$x];
                                        }//end of checking
                                    }//end of forloop
                                    // $inn = ($getFirstIn[0]["inHour"]);
                                    // $empDate = ($getFirstIn[0]["inDate"]);
                                    if($in1 === $out1){
                                        if(strtotime($inn) >= strtotime($afterBreak)){
                                            $lates = ((strtotime($inn) - strtotime($shiftStarts)) / 3600) - 1;
                                        }else{
                                            $lates = (strtotime($inn) - strtotime($shiftStarts)) / 3600;
                                        }
                                        /*==================== BOGZ ====================*/
                                        $dif = strtotime($outss)- strtotime($inn);
                                        if($dif<3600){
                                            $lates = 0;
                                        }
                                        /*==================== END ====================*/
                                    }else{
                                        $shiftStarts = $in1 . " " . $shiftStarts;
                                        $lates = (strtotime($in) - strtotime($shiftStarts)) / 3600; 
                                    }
                                }
                            }//end of comparison for late
                            /*END OF LATE FUNCTION*/
                        }
                    }//end of forloop

                    break;
            }

        }

    }

    echo jsonify($response);
    // foreach($emps as $emp){
    //         switch ($prompt) {
    //             
    //             case 1:
    //                 foreach($ins as $inss){
    //                     $inId = $inss["time_log_uid"];
    //                     $in = $inss["date_created"];
    //                     $in1 = date("Y-m-d", strtotime($in));
    //                     $inDay = date("N", strtotime($in1));
    //                     $inSession = $inss["session"];
                        
    //                     $locations = getTimeInLocationByEmpUidSessionAndDate($empId, $inSession, $date);
    //                     if($locations){
    //                         $inLoc = $locations["name"];
    //                         $outss = getTimeOutByEmpUidAndSession($empId, $inSession);
    //                         $outLoc = $outss["name"];
    //                     }else{
    //                         $inLoc = "--";
    //                         $outss = getTimeOutByEmpUidAndSessionNoLoc($empId, $inSession);
    //                         $outLoc = "--";
    //                     }
    //                     $outId = $outss["time_log_uid"];
    //                     $out = $outss["date_created"];
    //                     $out1 = date("Y-m-d", strtotime($out));

    //                     $shift = getShiftByUidAndDate($outId, $in1, $inDay);
    //                     if(!$outss || !$shift){
    //                         $response[] = array(
    //                             "id" => $id,
    //                             "inId" => $inId,
    //                             "outId" => "No Time Out!",
    //                             "prompt" => "",
    //                             "lastname" => strtoupper($lastname),
    //                             "dates" => $date,
    //                             "date" => date("M d, Y", strtotime($date)),
    //                             "day" => $day,
    //                             "in" => date("h:i:s A", strtotime($in)),
    //                             "out" => "No Time Out!",
    //                             "late" => "00:00:00",
    //                             "tardiness" => "",
    //                             "overtime" => "00:00:00",
    //                             "undertime" => "00:00:00",
    //                             "work" => "00:00:00",
    //                             "totalWorked" => "00:00:00",
    //                             "totalLate" => "00:00:00",
    //                             "totalOvertime" => "00:00:00",
    //                             "totalUndertime" => "00:00:00",
    //                             "approveOTStatus" => "",
    //                             "location" => $inLoc . "=--",
    //                             "empNo" => $empNo
    //                         );
    //                     }else{
                            
    //                         $shiftStart = $shift->start;
    //                         $shiftEnd = $shift->end;
    //                         $grace = $shift->grace_period;
    //                         $shiftEnds = $shiftEnd;
    //                         $shiftStarts = $shiftStart;

    //                         if($grace != 0){
    //                             $dapatIn = date("H:i:s", strtotime("+$grace minutes", strtotime($shiftStart)));
    //                         }else{
    //                             $dapatIn = date("H:i:s", strtotime($shiftStart));
    //                         }

    //                         $inss = date("H:i:s", strtotime($in));
    //                         $outss = date("H:i:s", strtotime($out));

    //                         /*WORKED FUNCTION*/
    //                         // if(strtotime($out) < strtotime($in)){
    //                         //     $work = (strtotime($in) - strtotime($out)) / 3600;
    //                         // }else if(strtotime($out) > strtotime($in)){
    //                            // $work = (strtotime($out) - strtotime($in)) / 3600;
    //                         // }

    //                         //pasted worked f //to be editted
    //                         if(strtotime($out) < strtotime($in)){
    //                             $work = (strtotime($in) - strtotime($out)) / 3600;
    //                         }else if(strtotime($out) > strtotime($in)){
    //                             $work = (strtotime($out) - strtotime($in)) / 3600;
    //                         }
    //                         //end pasted worked

    //                         if(strtotime($shiftStart) < strtotime($shiftEnd)){
    //                             $shiftDuration = countDurationOfShifts($empId, $in1, $inDay);
    //                             $afterBreak = "13:00:00";
    //                             if(strtotime($inss) >= strtotime($afterBreak)){
    //                                 $shiftDuration = $shiftDuration;
    //                             }else{
    //                                 $shiftDuration = $shiftDuration - 1;
    //                             }
    //                         }else{
    //                             $shiftStart = "2015-02-01 " . $shiftStart;
    //                             $shiftEnd = "2015-02-02 " . $shiftEnd;

    //                             $shiftDuration = countDurationOfShiftsReversed($empId, $shiftStart, $shiftEnd, $inDay, $in1);
    //                             $afterBreak = "00:00:00";
    //                             if(strtotime($inss) <= strtotime($afterBreak)){
    //                                 $shiftDuration = ($shiftDuration);
    //                             }else{
    //                                 $shiftDuration = $shiftDuration - 1;
    //                             }
    //                         }
                            

    //                         if($work === $shiftDuration){
    //                             $totalWork = $shiftDuration;
    //                         }else if($work > $shiftDuration){
    //                             $totalWork = $shiftDuration;
    //                         }else if($work < $shiftDuration){
    //                             $totalWork = $work;
    //                         }//end of getting total work

    //                         $inn = date("H:i:s", strtotime($in));
    //                         $inHour = date("H:i:s", strtotime($dapatIn));
    //                         /*END OF WORKED FUNCTION*/
    //                         $empDates = date("Y-m-d", strtotime($empDate . "+1 day"));
    //                         if($in1 == $empDate){
    //                             $late++;
    //                         }
    //                         if($out1 == $empDate){
    //                             $under++;
    //                         }else if($out1 == $empDates){
    //                             $under++;
    //                         }
    //                         $lates = 0;
    //                         $undertime = 0;
    //                         $over = 0;
    //                         $getFirstIn = array();
    //                         // /*LATE FUNCTION*/
    //                         $inArray[] = array(
    //                             "inHour" => $inn, 
    //                             "inDate" => $empDate
    //                         );
    //                         $lateCount = countDate($empId, $empDate);

    //                         if(strtotime($inn) >= strtotime($inHour)){
                                
    //                             if($late === $lateCount){
    //                                 for($x=0; $x < count($inArray); $x++){
    //                                     if(in_array($empDate, $inArray[$x])){
    //                                         $getFirstIn[] = $inArray[$x];
    //                                     }//end of checking
    //                                 }//end of forloop
    //                                 // $inn = ($getFirstIn[0]["inHour"]);
    //                                 // $empDate = ($getFirstIn[0]["inDate"]);
    //                                 if($in1 === $out1){
    //                                     if(strtotime($inn) >= strtotime($afterBreak)){
    //                                         $lates = ((strtotime($inn) - strtotime($shiftStarts)) / 3600) - 1;
    //                                     }else{
    //                                         $lates = (strtotime($inn) - strtotime($shiftStarts)) / 3600;
    //                                     }
    //                                     /*==================== BOGZ ====================*/
    //                                     $dif = strtotime($outss)- strtotime($inn);
    //                                     if($dif<3600){
    //                                         $lates = 0;
    //                                     }
    //                                     /*==================== END ====================*/
    //                                 }else{
    //                                     $shiftStarts = $in1 . " " . $shiftStarts;
    //                                     $lates = (strtotime($in) - strtotime($shiftStarts)) / 3600; 
    //                                 }
    //                             }
    //                         }//end of comparison for late
    //                         /*END OF LATE FUNCTION*/
    //                         $outHour = date("H:i:s", strtotime($out));

    //                         // /*OVERTIME FUNCTION*/
    //                         $undertimeCounts = countDateOut($empId, $out1);

    //                         // /*UNDERTIME FUNCTION*/ 
    //                         $getLastOut = array();
    //                         if($undertimeCounts === $under){
    //                             if(strtotime($outHour) <= strtotime($shiftEnds)){
    //                                 $undertimeCounts = countDateOut($empId, $out1);
    //                                 $outArray = array(
    //                                     "outHour" => $outHour, 
    //                                     "outDate" => $out1
    //                                 );
                                    
    //                                 $outHour = $outArray["outHour"];
    //                                 $empDate = $outArray["outDate"];
    //                                 $outss = $empDate . " " . $outHour;

    //                                 if($in1 === $out1){
    //                                     $undertime = (strtotime($shiftEnds) - strtotime($outHour)) / 3600;
    //                                 }else{
    //                                     $shiftEnds = $out1 . " " . $shiftEnds;
    //                                     $undertime = (strtotime($shiftEnds) - strtotime($outss)) / 3600;
    //                                 }
                                    
    //                                 // $undertime = (strtotime($shiftEnds) - strtotime($outHour)) / 3600;
    //                             }//end of comparison for undertime
    //                         }
    //                         $request = getOvertimeRequestByEmpUidAndDate($empId, $empDate, $empHolidayDate);
    //                         $requestStartDate = $request["start_date"];
    //                         $requestEmpId = $request["emp_uid"];
    //                         if($out1 === $out1){
    //                             $over++;
    //                         }

    //                         $outArray = array(
    //                             "outHour" => $outHour, 
    //                             "out" => $out, 
    //                             "outDate" => $out1
    //                         );

    //                         // if($undertimeCounts === $over){
    //                             $outHour = $outArray["outHour"];
    //                             $out = $outArray["out"];
    //                             if(strtotime($shiftEnd) <= strtotime($outHour)){
    //                                 if($in1 === $out1){
    //                                     $overtime = (strtotime($outHour) - strtotime($shiftEnd)) / 3600;
    //                                 }else{
    //                                     $shiftEnds = $out1 . $shiftEnds;
    //                                     $overtime = (strtotime($out) - strtotime($shiftEnds)) / 3600;
    //                                 }
    //                             }else if(strtotime($shiftEnd) >= strtotime($outHour)){
    //                                 if($in1 === $out1){
    //                                     $overtime = 0;
    //                                 }else{
    //                                     $shiftEnd = date("Y-m-d", strtotime($in . "- 0 day")) . " $shiftEnd"; 
    //                                     $overtime = (strtotime($out) - strtotime($shiftEnd)) / 3600;
    //                                 }
    //                             }//end of comparison for overtime
    //                             /*END OF UNDERTIME FUNCTION*/ 
    //                         // }//end of comparing count

    //                         if($overtime > 60){
    //                             $overtime = 0;
    //                         }else if($overtime <= -1 ){
    //                             $overtime = 0;
    //                         }
                            
    //                         $check = getOvertimeRequestByEmpUidAndDate($empId, $empDate, $empHolidayDate);
    //                         $checkEmpId = $check["emp_uid"];
    //                         $checkDate = $check["start_date"];
    //                         $oTstatus = 0;
    //                         $approvedDate = 0;

    //                         if(!$checkDate){

    //                         }else{
    //                             $approvedDate = $checkDate;
    //                             $oTstatus = 1;
    //                         }//end of checking
    //                         /*==================== BOGZ ====================*/
    //                         $totalWork = $totalWork - $lates;//- ($lates + $undertime);
    //                         /*==================== END BOGZ ====================*/
    //                         $totalWork = abs($totalWork) + abs($overtime);

    //                         $workHour = floor($totalWork);
    //                         $totalWorkMin = (60*($totalWork-$workHour));
    //                         $workMin = floor(60*($totalWork-$workHour));
    //                         $workMin1 = floor($totalWorkMin);
    //                         $workSec = round(60*($totalWorkMin-$workMin1));

    //                         if($lates < 0){
    //                             $lates = 0;
    //                         }else{
    //                             $lates = $lates;
    //                         }
    //                         $totalLate = $lates;

    //                         $lateHour = floor($totalLate);
    //                         $totalLateMin = (60*($totalLate-$lateHour));
    //                         $lateMin = floor(60*($totalLate-$lateHour));
    //                         $lateMin1 = floor($totalLateMin);
    //                         $lateSec = round(60*($totalLateMin-$lateMin1));
    //                         if($lateMin >= 60){
    //                             $lateHour = $lateHour + 1;
    //                         }else{
    //                             $lateHour = $lateHour;
    //                         }

    //                         if($lateSec >= 60){
    //                             $lateSec = 0;
    //                             $lateMin = $lateMin + 1;
    //                         }else{
    //                             $lateSec = $lateSec;
    //                         }
    //                         $lates = new dateTime("$lateHour:$lateMin:$lateSec");

    //                         $totalOvertime = $overtime;
    //                         $overtimeHour = floor($totalOvertime);
    //                         $totalOvertimeMin = (60*($totalOvertime-$overtimeHour));
    //                         $overtimeMin = floor(60*($totalOvertime-$overtimeHour));
    //                         $overtimeMin1 = floor($totalOvertimeMin);
    //                         $overtimeSec = round(60*($totalOvertimeMin-$overtimeMin1));

    //                         if($overtimeMin >= 60){
    //                             $overtimeHour = $overtimeHour + 1;
    //                         }else{
    //                             $overtimeHour = $overtimeHour;
    //                         }
    //                         if($overtimeSec >= 60){
    //                             $overtimeSec = 0;
    //                             $overtimeMin = $overtimeMin + 1;
    //                         }else{
    //                             $overtimeSec = $overtimeSec;
    //                         }
    //                         $overtimes = "$overtimeHour:$overtimeMin:$overtimeSec";
    //                         if($overtimeHour<24){
    //                             $overtimes = new dateTime("$overtimeHour:$overtimeMin:$overtimeSec");
    //                             $overtimes= date_format($overtimes, "H:i:s");
    //                         }

    //                         if($totalOvertime >= 1){
    //                             $totalUndertime = 0;
    //                         }else{
    //                             $totalUndertime = $undertime;
    //                         }

    //                         $undertimeHour = floor($totalUndertime);
    //                         $totalUndertimeMin = (60*($totalUndertime-$undertimeHour));
    //                         $undertimeMin = floor(60*($totalUndertime-$undertimeHour));
    //                         $undertimeMin1 = floor($totalUndertimeMin);
    //                         $undertimeSec = round(60*($totalUndertimeMin-$undertimeMin1));

    //                         if($undertimeMin >= 60){
    //                             $undertimeMin = 0;
    //                             $undertimeHour = $undertimeHour + 1;
    //                         }else{
    //                             $undertimeMin = $undertimeMin;
    //                         }//end of checking overtime minute

    //                         if($lateSec >= 60){
    //                             $undertimeSec = 0;
    //                             $undertimeMin = $undertimeMin + 1;
    //                         }else{
    //                             $undertimeSec = $undertimeSec;
    //                         }
    //                         $undertimes = "$undertimeHour:$undertimeMin:$undertimeSec";
    //                         $worked = "$workHour:$workMin1:$workSec";

    //                         $check = checkTimeIsRequested($date, $id);

    //                         if($check){
    //                             $response[] = array(
    //                                 "id" => $id,
    //                                 "inId" => $inId,    
    //                                 "outId" => $outId,
    //                                 "prompt" => 7,
    //                                 "lastname" => strtoupper($lastname),
    //                                 "dates" => $date,
    //                                 "date" => date("M d, Y", strtotime($date)),
    //                                 "day" => $day,
    //                                 "in" => date("h:i:s A", strtotime($in)),
    //                                 "out" => date("h:i:s A", strtotime($out)),
    //                                 "late" => date_format($lates, "H:i:s"),
    //                                 "tardiness" => "",
    //                                 "overtime" => $overtimes,
    //                                 "undertime" => date("H:i:s", strtotime($undertimes)),
    //                                 "work" => date("H:i:s", strtotime($worked)),
    //                                 "totalWorked" => $totalWork,
    //                                 "totalLate" => $totalLate,
    //                                 "totalOvertime" => $totalOvertime,
    //                                 "totalUndertime" => $totalUndertime,
    //                                 "approveOTStatus" => $oTstatus,
    //                                 "location" => $inLoc . "=" . $outLoc,
    //                                 "empNo" => $empNo
    //                             );
    //                         }else{
    //                             $response[] = array(
    //                                 "id" => $id,
    //                                 "inId" => $inId,
    //                                 "outId" => $outId,
    //                                 "prompt" => $prompt,
    //                                 "lastname" => strtoupper($lastname),
    //                                 "dates" => $date,
    //                                 "date" => date("M d, Y", strtotime($date)),
    //                                 "day" => $day,
    //                                 "in" => date("h:i:s A", strtotime($in)),
    //                                 "out" => date("h:i:s A", strtotime($out)),
    //                                 "late" => date_format($lates, "H:i:s"), //  date_format($lates, "H:i:s"),
    //                                 "tardiness" => "",
    //                                 "overtime" =>$overtimes,
    //                                 "undertime" => date("H:i:s", strtotime($undertimes)),
    //                                 "work" => date("H:i:s", strtotime($worked)),
    //                                 "totalWorked" => $totalWork,
    //                                 "totalLate" => $totalLate,
    //                                 "totalOvertime" => $totalOvertime,
    //                                 "totalUndertime" => $totalUndertime,
    //                                 "approveOTStatus" => $oTstatus,
    //                                 "location" => $inLoc . "=" . $outLoc,
    //                                 "empNo" => $empNo
    //                             );
    //                         }
    //                     }
    //                 }//end of getTimeInByEmpUidAndDate Function
    //                 break;
    //             case 2:
    //                 $restId = $id;
    //                 $restDate = $date;
    //                 // $restNote = $time;
    //                 $restDay = $day;
    //                 $in = $time;
    //                 $out = $time;

    //                 $checkLoc = checkTimeInByEmpUidAndDate($id, $restDate);
    //                 if($checkLoc){
    //                     $ins = getTimeInByEmpUidAndDate($id, $restDate);
    //                 }else{
    //                     $ins = getTimeInByEmpUidAndDateNoLoc($id, $restDate);
    //                 }
    //                 $check = checkRestDayByDate($restId, $restDate);
                    
    //                 $late = 0;
    //                 $under = 0;

    //                 if($check >= 1){
    //                     foreach($ins as $inss){
    //                         $inId = $inss["time_log_uid"];
    //                         $in = $inss["date_created"];
    //                         $in1 = date("Y-m-d", strtotime($in));
    //                         $inDay = date("N", strtotime($in1));
    //                         $inSession = $inss["session"];

    //                         $locations = getTimeInLocationByEmpUidSessionAndDate($restId, $inSession, $date);
    //                         if($locations){
    //                             $inLoc = $locations["name"];
    //                             $outss = getTimeOutByEmpUidAndSession($restId, $inSession);
    //                             $outLoc = $outss["name"];
    //                         }else{
    //                             $inLoc = "--";
    //                             $outss = getTimeOutByEmpUidAndSessionNoLoc($restId, $inSession);
    //                             $outLoc = "--";
    //                         }
    //                         if(!$outss){
    //                             $response[] = array(
    //                                 "id" => $id,
    //                                 "inId" => $inId,
    //                                 "outId" => "No Time Out!",
    //                                 "prompt" => "",
    //                                 "lastname" => strtoupper($lastname),
    //                                 "dates" => $date,
    //                                 "date" => date("M d, Y", strtotime($date)),
    //                                 "day" => $day,
    //                                 "in" => date("h:i:s A", strtotime($in)),
    //                                 "out" => "No Time Out!",
    //                                 "late" => "00:00:00",
    //                                 "tardiness" => "",
    //                                 "overtime" => "00:00:00",
    //                                 "undertime" => "00:00:00",
    //                                 "work" => "00:00:00",
    //                                 "totalWorked" => "00:00:00",
    //                                 "totalLate" => "00:00:00",
    //                                 "totalOvertime" => "00:00:00",
    //                                 "totalUndertime" => "00:00:00",
    //                                 "approveOTStatus" => "",
    //                                 "location" => $inLoc . "=--",
    //                                 "empNo" => $empNo
    //                             );
    //                         }else{
    //                             $outId = $outss["time_log_uid"];
    //                             $out = $outss["date_created"];

    //                             $out1 = date("Y-m-d", strtotime($out));

    //                             $shift = getShiftByUidAndDate($outId, $date, $inDay);
    //                             $shiftStart = $shift->start;
    //                             $shiftEnd = $shift->end;
    //                             $grace = $shift->grace_period;
    //                             $shiftEnds = $shiftEnd;
    //                             $shiftStarts = $shiftStart;

    //                             if($grace != 0){
    //                                 $dapatIn = date("H:i:s", strtotime("+$grace minutes", strtotime($shiftStart)));
    //                             }else{
    //                                 $dapatIn = date("H:i:s", strtotime($shiftStart));
    //                             }
    //                             $inss = date("H:i:s", strtotime($in));
    //                             $outss = date("H:i:s", strtotime($out));

    //                             /*WORKED FUNCTION*/
    //                             if(strtotime($out) < strtotime($in)){
    //                                 $work = (strtotime($in) - strtotime($out)) / 3600;
    //                             }else if(strtotime($out) > strtotime($in)){
    //                                 $work = (strtotime($out) - strtotime($in)) / 3600;
    //                             }

    //                             if(strtotime($shiftStart) < strtotime($shiftEnd)){
    //                                 $shiftDuration = countDurationOfShifts($restId, $in1, $inDay);
    //                                 $afterBreak = "13:00:00";
    //                                 if(strtotime($inss) >= strtotime($afterBreak)){
    //                                     $shiftDuration = $shiftDuration;
    //                                 }else{
    //                                     $shiftDuration = $shiftDuration - 1;
    //                                 }
    //                             }else{
    //                                 $shiftStart = "2015-02-01 " . $shiftStart;
    //                                 $shiftEnd = "2015-02-02 " . $shiftEnd;

    //                                 $shiftDuration = countDurationOfShiftsReversed($restId, $shiftStart, $shiftEnd, $inDay, $in1);
    //                                 $afterBreak = "00:00:00";
    //                                 if(strtotime($inss) <= strtotime($afterBreak)){
    //                                     $shiftDuration = $shiftDuration;
    //                                 }else{
    //                                     $shiftDuration = $shiftDuration - 1;
    //                                 }
    //                             }


    //                             if($work === $shiftDuration){
    //                                 $totalWork = $shiftDuration;
    //                             }else if($work > $shiftDuration){
    //                                 $totalWork = $shiftDuration;
    //                             }else if($work < $shiftDuration){
    //                                 $totalWork = $work;
    //                             }

    //                             $inn = date("H:i:s", strtotime($in));
    //                             $inHour = date("H:i:s", strtotime($dapatIn));
    //                             /*END OF WORKED FUNCTION*/
    //                             $empDates = date("Y-m-d", strtotime($restDate . "+1 day"));
    //                             if($in1 == $restDate){
    //                                 $late++;
    //                             }
    //                             if($out1 == $restDate){
    //                                 $under++;
    //                             }else if($out1 == $empDates){
    //                                 $under++;
    //                             }
    //                             $lates = 0;
    //                             $undertime = 0;
    //                             $over = 0;
    //                             $getFirstIn = array();
    //                             // /*LATE FUNCTION*/
    //                             $inArray[] = array(
    //                                 "inHour" => $inn, 
    //                                 "inDate" => $restDate
    //                             );
    //                             $lateCount = countDate($restId, $restDate);

    //                             if(strtotime($inn) >= strtotime($inHour)){
                                    
    //                                 if($late === $lateCount){
    //                                     for($x=0; $x < count($inArray); $x++){
    //                                         if(in_array($restDate, $inArray[$x])){
    //                                             $getFirstIn[] = $inArray[$x];
    //                                         }//end of checking
    //                                     }//end of forloop
    //                                     $inn = ($getFirstIn[0]["inHour"]);
    //                                     $empDate = ($getFirstIn[0]["inDate"]);
    //                                     if($in1 === $out1){
    //                                         if(strtotime($inn) >= strtotime($afterBreak)){
    //                                             $lates = ((strtotime($inn) - strtotime($shiftStarts)) / 3600) - 1;
    //                                         }else{
    //                                             $lates = (strtotime($inn) - strtotime($shiftStarts)) / 3600;
    //                                         }
    //                                     }else{
    //                                         $shiftStarts = $in1 . " " . $shiftStarts;
    //                                         $lates = (strtotime($in) - strtotime($shiftStarts)) / 3600;
    //                                     }
    //                                 }
    //                             }//end of comparison for late
    //                             /*END OF LATE FUNCTION*/
    //                             $outHour = date("H:i:s", strtotime($out));

    //                             // /*OVERTIME FUNCTION*/
    //                             $undertimeCounts = countDateOut($restId, $out1);
    //                             // /*UNDERTIME FUNCTION*/ 
    //                             $getLastOut = array();
    //                             if($undertimeCounts === $under){
    //                                 if(strtotime($outHour) <= strtotime($shiftEnds)){
    //                                     $undertimeCounts = countDateOut($restId, $out1);
    //                                     $outArray = array(
    //                                         "outHour" => $outHour, 
    //                                         "outDate" => $out1
    //                                     );
    //                                     $outHour = $outArray["outHour"];
    //                                     $empDate = $outArray["outDate"];
    //                                     $outss = $empDate . " " . $outHour;

    //                                     if($in1 === $out1){
    //                                         $undertime = (strtotime($shiftEnds) - strtotime($outHour)) / 3600;
    //                                     }else{
    //                                         $shiftEnds = $out1 . " " . $shiftEnds;

    //                                         $undertime = (strtotime($shiftEnds) - strtotime($outss)) / 3600;
    //                                     }
    //                                 }//end of comparison for undertime
    //                             }
    //                             $request = getOvertimeRequestByEmpUidAndDate($restId, $restDate, $restDate);
    //                             $requestStartDate = $request["start_date"];
    //                             $requestEmpId = $request["emp_uid"];
    //                             if($out1 === $out1){
    //                                 $over++;
    //                             }

    //                             $outArray = array(
    //                                 "outHour" => $outHour, 
    //                                 "out" => $out, 
    //                                 "outDate" => $out1
    //                             );

    //                             // if($undertimeCounts === $over){
    //                                 $outHour = $outArray["outHour"];
    //                                 $out = $outArray["out"];
    //                                 if(strtotime($shiftEnd) <= strtotime($outHour)){
    //                                     if($in1 === $out1){
    //                                         $overtime = (strtotime($outHour) - strtotime($shiftEnd)) / 3600;
    //                                     }else{
    //                                         $shiftEnds = $out1 . $shiftEnds;
    //                                         $overtime = (strtotime($out) - strtotime($shiftEnds)) / 3600;
    //                                     }
    //                                 }else if(strtotime($shiftEnd) >= strtotime($outHour)){
    //                                     if($in1 === $out1){
    //                                         $overtime = 0;
    //                                     }else{
    //                                         $shiftEnd = date("Y-m-d", strtotime($in . "- 0 day")) . " $shiftEnd"; 
    //                                         $overtime = (strtotime($out) - strtotime($shiftEnd)) / 3600;
    //                                     }
    //                                 }//end of comparison for overtime
    //                                 /*END OF UNDERTIME FUNCTION*/ 
    //                             // }//end of comparing count

    //                             if($overtime > 60){
    //                                 $overtime = 0;
    //                             }else if($overtime <= -1 ){
    //                                 $overtime = 0;
    //                             }
                                
    //                             $check = getOvertimeRequestByEmpUidAndDate($restId, $restDate, $restDate);
    //                             $checkEmpId = $check["emp_uid"];
    //                             $checkDate = $check["start_date"];
    //                             $oTstatus = 0;
    //                             $approvedDate = 0;

    //                             if(!$checkDate){

    //                             }else{
    //                                 $approvedDate = $checkDate;
    //                                 $oTstatus = 1;
    //                             }//end of checking
    //                             $totalWork = $totalWork - ($lates + $undertime);
    //                             $totalWork = abs($totalWork) + abs($overtime);
    //                             $workHour = floor($totalWork);
    //                             $totalWorkMin = (60*($totalWork-$workHour));
    //                             $workMin = floor(60*($totalWork-$workHour));
    //                             $workMin1 = floor($totalWorkMin);
    //                             $workSec = round(60*($totalWorkMin-$workMin1));

    //                             if($lates < 0){
    //                                 $lates = 0;
    //                             }else{
    //                                 $lates = $lates;
    //                             }
    //                             $totalLate = $lates;

    //                             $lateHour = floor($totalLate);
    //                             $totalLateMin = (60*($totalLate-$lateHour));
    //                             $lateMin = floor(60*($totalLate-$lateHour));
    //                             $lateMin1 = floor($totalLateMin);
    //                             $lateSec = round(60*($totalLateMin-$lateMin1));
    //                             if($lateMin >= 60){
    //                                 $lateHour = $lateHour + 1;
    //                             }else{
    //                                 $lateHour = $lateHour;
    //                             }

    //                             if($lateSec >= 60){
    //                                 $lateSec = 0;
    //                                 $lateMin = $lateMin + 1;
    //                             }else{
    //                                 $lateSec = $lateSec;
    //                             }
    //                             $lates = new dateTime("$lateHour:$lateMin:$lateSec");

    //                             $totalOvertime = $overtime;
    //                             $overtimeHour = floor($totalOvertime);
    //                             $totalOvertimeMin = (60*($totalOvertime-$overtimeHour));
    //                             $overtimeMin = floor(60*($totalOvertime-$overtimeHour));
    //                             $overtimeMin1 = floor($totalOvertimeMin);
    //                             $overtimeSec = round(60*($totalOvertimeMin-$overtimeMin1));

    //                             if($overtimeMin >= 60){
    //                                 $overtimeHour = $overtimeHour + 1;
    //                             }else{
    //                                 $overtimeHour = $overtimeHour;
    //                             }
    //                             if($overtimeSec >= 60){
    //                                 $overtimeSec = 0;
    //                                 $overtimeMin = $overtimeMin + 1;
    //                             }else{
    //                                 $overtimeSec = $overtimeSec;
    //                             }

    //                             $overtimes = new dateTime("$overtimeHour:$overtimeMin:$overtimeSec");


    //                             $totalUndertime = $undertime;
    //                             $undertimeHour = floor($totalUndertime);
    //                             $totalUndertimeMin = (60*($totalUndertime-$undertimeHour));
    //                             $undertimeMin = floor(60*($totalUndertime-$undertimeHour));
    //                             $undertimeMin1 = floor($totalUndertimeMin);
    //                             $undertimeSec = round(60*($totalUndertimeMin-$undertimeMin1));

    //                             if($undertimeMin >= 60){
    //                                 $undertimeMin = 0;
    //                                 $undertimeHour = $undertimeHour + 1;
    //                             }else{
    //                                 $undertimeMin = $undertimeMin;
    //                             }//end of checking overtime minute

    //                             if($lateSec >= 60){
    //                                 $undertimeSec = 0;
    //                                 $undertimeMin = $undertimeMin + 1;
    //                             }else{
    //                                 $undertimeSec = $undertimeSec;
    //                             }
    //                             $undertimes = "$undertimeHour:$undertimeMin:$undertimeSec";
    //                             $worked = "$workHour:$workMin1:$workSec";

    //                             $response[] = array(
    //                                 "id" => $id,
    //                                 "inId" => $inId,
    //                                 "outId" => $outId,
    //                                 "prompt" => 1,
    //                                 "lastname" => strtoupper($lastname),
    //                                 "dates" => $date,
    //                                 "date" => date("M d, Y", strtotime($date)),
    //                                 "day" => $day,
    //                                 "in" => date("h:i:s A", strtotime($in)),
    //                                 "out" => date("h:i:s A", strtotime($out)),
    //                                 "late" => date_format($lates, "H:i:s"),
    //                                 "tardiness" => "",
    //                                 "overtime" => date_format($overtimes, "H:i:s"),
    //                                 "undertime" => date("H:i:s", strtotime($undertimes)),
    //                                 "work" => date("H:i:s", strtotime($worked)),
    //                                 "totalWorked" => $totalWork,
    //                                 "totalLate" => $totalLate,
    //                                 "totalOvertime" => $totalOvertime,
    //                                 "totalUndertime" => $totalUndertime,
    //                                 "approveOTStatus" => $oTstatus,
    //                                 "location" => $inLoc . "=" . $outLoc,
    //                                 "empNo" => $empNo

    //                             );
    //                         }
    //                     }//end of getTimeInByEmpUidAndDate Function
    //                 }else{
    //                     $response[] = array(
    //                         "id" => $id,
    //                         "inId" => 0,
    //                         "outId" => 0,
    //                         "prompt" => $prompt,
    //                         "lastname" => strtoupper($lastname),
    //                         "dates" => $date,
    //                         "error" => $time,
    //                         "date" => date("M d, Y", strtotime($date)),
    //                         "day" => $day,
    //                         "in" => "REST DAY",
    //                         "out" => "REST DAY",
    //                         "late" => "REST DAY",
    //                         "tardiness" => "REST DAY",
    //                         "overtime" => "REST DAY",
    //                         "undertime" => "REST DAY",
    //                         "work" => "REST DAY",
    //                         "totalWorked" => "REST DAY",
    //                         "totalLate" => "REST DAY",
    //                         "totalOvertime" => "REST DAY",
    //                         "totalUndertime" => "REST DAY",
    //                         "approveOTStatus" => "0",
    //                         "location" => "--=--",
    //                         "empNo" => $empNo

    //                     );
    //                 }
    //                 break;
    //             case 3:
    //                 $holidayEmpId = $id;
    //                 $holidayDate = $date;
    //                 // $absentNote = $time;
    //                 $holidayDay = $day;
    //                 $in = $time;
    //                 $out = $time;
    //                 $response[] = array(
    //                     "id" => $id,
    //                     "inId" => 0,
    //                     "outId" => 0,
    //                     "prompt" => $prompt,
    //                     "lastname" => strtoupper($lastname),
    //                     "dates" => $date,
    //                     "date" => date("M d, Y", strtotime($date)),
    //                     "day" => $day,
    //                     "in" => "HOLIDAY",
    //                     "out" => "HOLIDAY",
    //                     "late" => "HOLIDAY",
    //                     "tardiness" => "HOLIDAY",
    //                     "overtime" => "HOLIDAY",
    //                     "undertime" => "HOLIDAY",
    //                     "work" => "HOLIDAY",
    //                     "totalWorked" => "HOLIDAY",
    //                     "totalLate" => "HOLIDAY",
    //                     "totalOvertime" => "HOLIDAY",
    //                     "totalUndertime" => "HOLIDAY",
    //                     "approveOTStatus" => "0",
    //                     "location" => "--=--",
    //                     "empNo" => $empNo

    //                 );
    //                 break;
    //             case 4:
    //                 $leaveEmpId = $id;
    //                 $leaveDate = $date;
    //                 // $absentNote = $time;
    //                 $leaveDay = $day;
    //                 $in = $time;
    //                 $out = $time;

    //                 $response[] = array(
    //                     "id" => $id,
    //                     "inId" => 0,
    //                     "outId" => 0,
    //                     "prompt" => $prompt,
    //                     "lastname" => strtoupper($lastname),
    //                     "dates" => $date,
    //                     "date" => date("M d, Y", strtotime($date)),
    //                     "day" => $day,
    //                     "in" => "ON LEAVE",
    //                     "out" => "ON LEAVE",
    //                     "late" => "ON LEAVE",
    //                     "tardiness" => "ON LEAVE",
    //                     "overtime" => "ON LEAVE",
    //                     "undertime" => "ON LEAVE",
    //                     "work" => "ON LEAVE",
    //                     "totalWorked" => "ON LEAVE",
    //                     "totalLate" => "ON LEAVE",
    //                     "totalOvertime" => "ON LEAVE",
    //                     "totalUndertime" => "ON LEAVE",
    //                     "approveOTStatus" => "0",
    //                     "location" => "--=--",
    //                     "empNo" => $empNo

    //                 );
    //                 break;
    //             case 5:

    //                 $response[] = array(
    //                     "id" => $id,
    //                     "inId" => 0,
    //                     "outId" => 0,
    //                     "prompt" => $prompt,
    //                     "lastname" => strtoupper($lastname),
    //                     "dates" => $date,
    //                     "date" => date("M d, Y", strtotime($date)),
    //                     "day" => $day,
    //                     "in" => "ABSENT",
    //                     "out" => "ABSENT",
    //                     "late" => "ABSENT",
    //                     "tardiness" => "ABSENT",
    //                     "overtime" => "ABSENT",
    //                     "undertime" => "ABSENT",
    //                     "work" => "ABSENT",
    //                     "totalWorked" => "ABSENT",
    //                     "totalLate" => "ABSENT",
    //                     "totalOvertime" => "ABSENT",
    //                     "totalUndertime" => "ABSENT",
    //                     "approveOTStatus" => "0",
    //                     "location" => "--=--",
    //                     "empNo" => $empNo

    //                 );
    //                 break;
    //         }//end of switch for prompt
    //     }//end of for-loop

    //     foreach ($response as $k => $v) {
    //         $sort[$k] = $v["dates"];
    //         $sortLastname[$k] = $v["empNo"];

    //     }//end of response

    //     array_multisort($sortLastname, SORT_ASC, $sort, SORT_ASC, $response);
    // }
    // echo jsonify($response);    
});

$app->get("/count/time/data/:var", function($var){
    $param      = explode(".", $var);
    $uid        = $param[2];
    $startDate  = date('Y-m-d', strtotime($param[0]));
    $endDate    = date('Y-m-d', strtotime($param[1]));
    $startDates = strtotime($startDate);
    $endDates   = strtotime($endDate);   
    $count      = 0;
    $response   = array();
    for($i=$startDates; $i<=$endDates; $i+=86400){
        $count++;
    }

    $response = array(
        "count" => $count
    );
    echo jsonify($response);
});

$app->get("/get/leave/emp/counts/:var", function($var){
    $token = $var;
    $x     = leaveCounts();
    echo jsonify($x);
});

$app->get("/get/emp/cost/payslip/:var", function($var){
    $params    = explode(".", $var);
    $startDate = $params[0];
    $endDate   = $params[1];
    $emp       = $params[2];
    $uid       = $params[3];
    $summaries = incomeDetails($startDate, $endDate, $emp ,$uid);
    echo jsonify($summaries);
});

$app->post("/android/add/user/", function(){
    $name     = $_POST["name"];
    $email    = $_POST["email"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    $query    = ORM::forTable("android_user")->create();
        $query->name          = $name;
        $query->email_address = $email;
        $query->username      = $username;
        $query->password      = $password;
    $query->save();
});

$app->get("/employee/leave/count/:emp", function($emp){
    $response = array();
    $leave    = getEmpLeaveCountPagesByEmpUid($emp);

    $response = array();
    $year = date("Y");
    // foreach($leaves as $leave){
    if($leave){
        $leaveCountUid = $leave["emp_leave_count_uid"];
        $id = $leave["emp_uid"];
        $empNo = $leave["username"];
        $name = $leave["lastname"] . ", " . $leave["firstname"];
        $SL = $leave["SL"];
        $BL = $leave["BL"];
        $BV = $leave["BV"];
        $VL = $leave["VL"];
        $ML = $leave["ML"];
        $PL = $leave["PL"];
        // print_r($leave);

        $sickLeave = 0;
        $birthdayLeave = 0;
        $berLeave = 0;
        $vacLeave = 0;
        $noPay = 0;
        $matLeave = 0;
        $patLeave = 0;

        $leaves = getApprovedLeavesByEmpUidByYear($id, $year);
        foreach($leaves as $leave){
            $leaveCode = $leave["leave_code"];
            $leaveStart = $leave["start_date"];
            $leaveEnd = $leave["end_date"];

            switch($leaveCode){
                case "SL":
                    $leaveCount = getDaysOfWorkByDateRange($leaveStart, $leaveEnd);
                    $leaveCount = $leaveCount + 1;
                    $sickLeave += $leaveCount++;
                    break;
                case "BL":
                    $leaveCount = getDaysOfWorkByDateRange($leaveStart, $leaveEnd);
                    $leaveCount = $leaveCount + 1;
                    $birthdayLeave += $leaveCount++;
                    break;
                case "BV":
                    $leaveCount = getDaysOfWorkByDateRange($leaveStart, $leaveEnd);
                    $leaveCount = $leaveCount + 1;
                    $berLeave += $leaveCount++;
                    break;
                case "VL":
                    $leaveCount = getDaysOfWorkByDateRange($leaveStart, $leaveEnd);
                    $leaveCount = $leaveCount + 1;
                    $vacLeave += $leaveCount++;
                    break;
                case "W":
                    $leaveCount = getDaysOfWorkByDateRange($leaveStart, $leaveEnd);
                    $leaveCount = $leaveCount + 1;
                    $noPay += $leaveCount++;
                    break;
                case "ML":
                    $leaveCount = getDaysOfWorkByDateRange($leaveStart, $leaveEnd);
                    $leaveCount = $leaveCount + 1;
                    $matLeave += $leaveCount++;
                    break;
                case "PL":
                    $leaveCount = getDaysOfWorkByDateRange($leaveStart, $leaveEnd);
                    $leaveCount = $leaveCount + 1;
                    $patLeave += $leaveCount++;
                    break;
            }//end of switch
        }//end of getting leave

        // echo "$id = $SL = $sickLeave<br/>";

        $sLTotal = $SL - $sickLeave;
        if($sLTotal < 0){
            $sLTotal = 0;
        }
        $bLTotal = $BL - $birthdayLeave;
        if($bLTotal < 0){
            $bLTotal = 0;
        }
        $bVTotal = $BV - $berLeave;
        if($bVTotal < 0){
            $bVTotal = 0;
        }
        $vLTotal = $VL - $vacLeave;
        if($vLTotal < 0){
            $vLTotal = 0;
        }
        $mLTotal = $ML - $matLeave;
        if($mLTotal < 0){
            $mLTotal = 0;
        }
        $pLTotal = $PL - $patLeave;
        if($pLTotal < 0){
            $pLTotal = 0;
        }

        $response = array(
            "SL" => $sLTotal,
            "BL" => $bLTotal,
            "BV" => $bVTotal,
            "VL" => $vLTotal,
            "ML" => $mLTotal,
            "PL" => $pLTotal
        );
    }else{
        $response = array(
            "SL" => 0,
            "BL" => 0,
            "BV" => 0,
            "VL" => 0,
            "ML" => 0,
            "PL" => 0
        );
    }//end of getEmpLeaveCountPages function

    echo jsonify($response);
});

$app->post("/import/dtr/", function(){
    $row      = 1;
    $response = array();
    $data     = array();
    if (($handle = fopen($_FILES['attachment']['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $num     = count($data);
            $empNo   = trim($data[0]);
            $empNo   = str_pad($empNo, 4, "0", STR_PAD_LEFT);
            $date    = trim($data[1]);
            $timeIn  = trim($data[2]);
            $timeOut = trim($data[3]);
            $session = xguid();
            $uid     = xguid();

            $timeIns  = date("H:i", strtotime(trim($data[2])));
            $timeOuts = date("H:i", strtotime(trim($data[3])));

            $date = date("Y-m-d", strtotime($date));

            $timeInComb  = $date . " " . $timeIn;
            $timeOutComb = $date . " " . $timeOut;

            $timeInDateTimeFormat  = date("Y-m-d H:i", strtotime($timeInComb));
            $timeOutDateTimeFormat = date("Y-m-d H:i", strtotime($timeOutComb));

            $checkEmpNo = usernameIsExistingUsingLike($empNo);//checking if empno ay nasa database
            if($checkEmpNo){//start of if-else
                $empUid = getUserEmpUidByLike($empNo); //pagkuha ng uid
                $day    = date("N", strtotime($date)); //pagkuha ng araw
                if($timeIn){//start of if-else pagcheck kung may time in
                    $shiftData = getOffsetShiftByUidAndDay($empUid, $day);
                    if($shiftData){//start of if-else pagcheck kung may shift
                        $shiftUid = $shiftData->shift_uid; //pagkuha ng uid ng shift
                        $check = checkUserHasTimeIn($empUid, $date);
                        if(!$check){//pagchcheck kapag wala pa sya sa database
                            addTimeIn($uid, $empUid, $shiftUid, $session, $timeInDateTimeFormat, $timeInDateTimeFormat); 
                            $getSession = getTimeLogByEmpUidAndDate($empUid, $date);
                            $session    = $getSession->session;//pagkuha ng session sa previous time in

                            if($timeOut){//start of if-else pagcheck kung may time out
                                addTimeOut(xguid(), $empUid, $shiftUid, $session, $timeOutDateTimeFormat, "", $timeOutDateTimeFormat);
                            }//end of if-else pagcheck kung may time out  
                            $prompt = 0;    
                            $row++;
                        }else{
                            removeDateFromTimeInLogByEmpAndDate($empUid, $date);
                            removeDateFromTimeOutLogByEmpAndDate($empUid, $date);
                            addTimeIn($uid, $empUid, $shiftUid, $session, $timeInDateTimeFormat, $timeInDateTimeFormat); 
                            $getSession = getTimeLogByEmpUidAndDate($empUid, $date);
                            $session    = $getSession->session;//pagkuha ng session sa previous time in

                            if($timeOut){//start of if-else pagcheck kung may time out
                                addTimeOut(xguid(), $empUid, $shiftUid, $session, $timeOutDateTimeFormat, "", $timeOutDateTimeFormat);
                            }//end of if-else pagcheck kung may time out  
                            $prompt = 1;    
                            $row++;
                        }
                        
                    }//end of if-else pagcheck kung may shift
                }//end of if-else pagcheck kung may time in
            }//end of if-else
        }
        fclose($handle);
    }
    $response = array(
        "result" => $prompt,
        "count" => $row
    );
    echo jsonify($response);
});

/*FOR DTR*/
$app->get("/get/location/data/", function(){
    // $param = explode(".", $var);
    // $token = $param[0];
    $response = array();

    $location = getLocation();
    foreach($location as $locations){
        $response[] = array(
            "locUid"      => $locations->uid,
            "name"        => $locations->name,
            "fingerprint" => $locations->fingerprint,
            "status"      => $locations->status
        );
    }

    echo jsonify($response);
});

$app->post("/check/user/", function(){
    $username = $_POST["username"];
    $check    = checkIfUserExisted($username);
    // print_r($check);
    $count    = $check->count;
    $emp      = $check->emp_uid;
    $status   = $check->status;
    if($username){
        if($status === "0"){
            $response = array(
                "prompt" => 0,
                "username" => ""
            );
        }else{
            if($count >= 1){
                $response = array(
                    "prompt" => 1,
                    "username" => $emp
                );
            }else{
                $response = array(
                    "prompt" => 0,
                    "username" => ""
                );
            }
        }
    }else{
        $response = array(
            "prompt" => 2,
            "username" => ""
        );
    }
    

    echo jsonify($response);
});

$app->get("/check/time/log/:username", function($username){

    if(checkPreviousClockLog($username)){
        if(checkIfClockIn($username)){
            $type = 1;
            $action = "OUT";
        }

        if(checkIfClockOut($username)){
            $type = 0;
            $action = "IN";
        }
    }else{
        $type = 0;
        $action = "IN";
    }

    $emp1       = getEmployeeDetailsByUid($username);
    $lastname   = $emp1["lastname"];
    $middlename = $emp1["middlename"];
    $firstname  = $emp1["firstname"];

    $name = $firstname . " " . $middlename . " " . $lastname;
    

    $response = array(
        "action" => $action,
        "name"   => $name
    );

    echo jsonify($response);
});

$app->post("/employee/timesheet/new/", function(){
    date_default_timezone_set("Asia/Manila");
    $emp          = $_POST["username"];
    $len          = strlen($emp);
    $password     = $_POST["password"];
    $timeLogUid   = xguid();
    $session      = xguid();
    $uid          = xguid();
    $response     = array();
    // $shift     = $_POST["shift"];
    $type         = "";
    $locUid       = $_POST["locUid"];
    $device       = $_POST["device"];
    
    //ClientJS
    $fprint       = $_POST["clientjs"];
    
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");

    if($len <= 5){
        $userId = getUserId($emp);
        if(!$userId){
            $response = array(
                "verified" => 0
            );
        }

        $encryption = "AES-256-CBC";
        $uxPassword = sha1(Base32::decode($_POST["password"]));
        $secretKey  = sha1($emp . $uxPassword);
        $uniqueKey  = getUniqueKey($userId);
        if ($uniqueKey == null) {
            $ivSize    = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $uniqueKey = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        }
        $password = openssl_encrypt($uxPassword, $encryption, $secretKey, 0, $uniqueKey);
        
        if (validUserAccount($emp, $uxPassword)) {
            $checkUser = checkIfUserExisted($emp);
            $username  = $checkUser["emp_uid"];
            $count     = $checkUser["count"];
            if(!$username){
                $userPrompt = 2;
                $response   = array(
                    "errorMessage" => "ACCOUNT DOESNT EXIST!",
                    "errorStatus"  => 2,
                    "hour"         => "",
                    "name"         => "",
                    "action"       => ""
                );
            }else{
                if(checkPreviousClockLog($username)){
                    if(checkIfClockIn($username)){
                        $type    = 1;
                        $logType = "OUT";
                        $action  = "CLOCK OUT ";
                        $session = getPreviousTimeSession($username);
                    }

                    if(checkIfClockOut($username)){
                        $type    = 0;
                        $logType = "IN";
                        $action  = "CLOCK IN ";
                    }
                }else{
                    $type    = 0;
                    $logType = "IN";
                    $action  = "CLOCK IN ";
                }
                $emp1       = getEmployeeDetailsByUid($username);
                $lastname   = $emp1["lastname"];
                $middlename = $emp1["middlename"];
                $firstname  = $emp1["firstname"];

                $name  = $firstname . " " . $middlename . " " . $lastname;
                $sDate = date("Y-m-d");
                $sTime = date("H:i:s");
                $day   = date("N", strtotime($dateCreated));
                
                $shifts = getShiftUidInRules($username, $day);
                $hour   = date("h:i:s A", strtotime($dateCreated));
                
                //ClientJS
                // clientJSlog($emp, date("Y-m-d h:i:s A"), $fprint, $logType);

                if($shifts == "0"){
                    $response = array(
                        "errorMessage" => "LAGYAN NG RULE!",
                        "errorStatus"  => 1,
                        "hour"         => $hour,
                        "name"         => utf8_decode($name),
                        "action"       => $action
                    );
                }else{
                    $shift           = $shifts->shift;
                    $getLocationData = getLocationsByUid($locUid);
                    $locationUid     = $getLocationData["uid"];
                    $locName         = $getLocationData["name"];

                    dummyGenerateTimelog($timeLogUid, $username, $session ,$shift, $type, $locationUid, $dateCreated, $dateModified);
                    addTimeStampData($emp, $sDate, $sTime, $logType);
                    addEventLog($username, $sDate, $sTime, $logType, $locName, $locationUid, $dateCreated, $dateModified);

                    $response = array(
                        "errorMessage" => 0,
                        "errorStatus"  => "",
                        "hour"         => $hour,
                        "name"         => utf8_decode($name),
                        "action"       => $action
                    );
                }
            }
        } else {
            $response = array(
                "errorMessage" => "ACCOUNT DOESNT EXIST!",
                "errorStatus"  => 2,
                "hour"         => "",
                "name"         => "",
                "action"       => ""
            );
        }
    }else{
        $userId = getUserIdByEmpUid($emp);
        if(!$userId){
            $response = array(
                "verified" => 0
            );
        }
        $encryption = "AES-256-CBC";
        $uxPassword = sha1(Base32::decode($_POST["password"]));
        $secretKey  = sha1($emp . $uxPassword);
        $uniqueKey  = getUniqueKey($userId);
        if ($uniqueKey == null) {
            $ivSize    = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $uniqueKey = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        }
        $password = openssl_encrypt($uxPassword, $encryption, $secretKey, 0, $uniqueKey);
        
        if (validEmpUserAccount($emp, $uxPassword)) {
            $checkUser = checkEmployeeByUid($emp);
            if(!$checkUser){
                $userPrompt = 2;
                $response = array(
                    "errorMessage" => "ACCOUNT DOESNT EXIST!",
                    "errorStatus"  => 2,
                    "hour"         => "",
                    "name"         => "",
                    "action"       => ""
                );
            }else{
                if(checkPreviousClockLog($emp)){
                    if(checkIfClockIn($emp)){
                        $type    = 1;
                        $logType = "OUT";
                        $action  = "CLOCK OUT ";
                        $session = getPreviousTimeSession($emp);
                    }

                    if(checkIfClockOut($emp)){
                        $type    = 0;
                        $logType = "IN";
                        $action  = "CLOCK IN ";
                    }
                }else{
                    $type    = 0;
                    $logType = "IN";
                    $action  = "CLOCK IN ";
                }
                $emp1       = getEmployeeDetailsByUid($emp);
                $lastname   = $emp1["lastname"];
                $middlename = $emp1["middlename"];
                $firstname  = $emp1["firstname"];
                
                $name       = $firstname . " " . $middlename . " " . $lastname;
                $sDate      = date("Y-m-d");
                $sTime      = date("H:i:s");
                $day        = date("N", strtotime($dateCreated));
                
                $shifts     = getShiftUidInRules($emp, $day);
                $hour       = date("h:i:s A", strtotime($dateCreated));
                if($shifts == "0"){
                    $response = array(
                        "errorMessage" => "LAGYAN NG RULE!",
                        "errorStatus"  => 1,
                        "hour"         => $hour,
                        "name"         => utf8_decode($name),
                        "action"       => $action
                    );
                }else{
                    $shift           = $shifts->shift;
                    $getLocationData = getLocationsByUid($locUid);
                    $locationUid     = $getLocationData["uid"];
                    $locName         = $getLocationData["name"];
                    
                    $uname           = getEmloyeeNumberByEmpUid($emp);

                    dummyGenerateTimelog($timeLogUid, $emp, $session ,$shift, $type, $locationUid, $dateCreated, $dateModified);
                    addTimeStampData($uname, $sDate, $sTime, $logType);
                    addEventLog($emp, $sDate, $sTime, $logType, $locName, $locationUid, $dateCreated, $dateModified);

                    $response = array(
                        "errorMessage" => 0,
                        "errorStatus"  => "",
                        "hour"         => $hour,
                        "name"         => utf8_decode($name),
                        "action"       => $action
                    );
                }
            }
        } else {
            $response = array(
                "errorMessage" => "ACCOUNT DOESNT EXIST!",
                "errorStatus"  => 2,
                "hour"         => "",
                "name"         => "",
                "action"       => ""
            );
        }
    }

    echo jsonify($response);
    // addShift($shiftUid,$emp,$startTime,$duration,$dateCreated, $dateModified);
    //dummyDataSetSchedule($uid,$timeLogUid,$emp,$startTime,$days,$dateCreated, $dateModified);
});

$app->post("/add/attempt/", function(){
    $emp          = $_POST["username"];
    $locUid       = $_POST["locCode"];
    $password     = $_POST["password"];
    $device       = $_POST["device"];
    $ip           = $_POST["ip"];
    $attemptUid   = xguid();
    $dateCreated  = date("Y-m-d H:i:s");
    $dateModified = date("Y-m-d H:i:s");
    
    //ClientJS
    $fprint       = $_POST["clientjs"];
    
    $userId       = getUserId($emp);
    if(!$userId){
        $response = array(
            "verified" => 0
        );
    }

    $encryption = "AES-256-CBC";
    $uxPassword = sha1(Base32::decode($_POST["password"]));
    $secretKey  = sha1($emp . $uxPassword);
    $uniqueKey  = getUniqueKey($userId);
    if ($uniqueKey == null) {
        $ivSize    = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $uniqueKey = mcrypt_create_iv($ivSize, MCRYPT_RAND);
    }
    $password = openssl_encrypt($uxPassword, $encryption, $secretKey, 0, $uniqueKey);
    
    // print_r(validUserAccount($emp, $uxPassword));
    if (validUserAccount($emp, $uxPassword)) {
        $checkUser = checkIfUserExisted($emp);
        $username  = $checkUser["emp_uid"];
        $count     = $checkUser["count"];
        if(!$username){
            $userPrompt = 2;
            $response = array(
                "errorMessage" => "ACCOUNT DOESNT EXIST!",
                "errorStatus"  => 2,
                "hour"         => "",
                "name"         => "",
                "action"       => ""
            );
        }else{
            if(checkPreviousClockLog($username)){
                if(checkIfClockIn($username)){
                    $type    = 1;
                    $logType = "OUT";
                    $action  = "CLOCK OUT ";
                    $session = getPreviousTimeSession($username);
                }

                if(checkIfClockOut($username)){
                    $type    = 0;
                    $logType = "IN";
                    $action  = "CLOCK IN ";
                }
            }else{
                $type    = 0;
                $logType = "IN";
                $action  = "CLOCK IN ";
            }
            $emp1       = getEmployeeDetailsByUid($username);
            $lastname   = $emp1["lastname"];
            $middlename = $emp1["middlename"];
            $firstname  = $emp1["firstname"];
            
            $name       = $firstname . " " . $middlename . " " . $lastname;
            $sDate      = date("Y-m-d");
            $sTime      = date("H:i:s");
            $day        = date("N", strtotime($dateCreated));
            
            $hour       = date("h:i:s A", strtotime($dateCreated));
            $shifts     = getShiftUidInRules($username, $day);
            
            //ClientJS
            // clientJSlog($username, date("Y-m-d h:i:s A"), $fprint, $logType);

            if($shifts == "0"){
                $response = array(
                    "errorMessage" => "LAGYAN NG RULE!",
                    "errorStatus"  => 1,
                    "hour"         => $hour,
                    "name"         => utf8_decode($name),
                    "action"       => $action
                );
            }else{
                addAttemptLog($attemptUid, $username, $sDate, $sTime, $logType, $locUid, $device, $ip, $dateCreated, $dateModified);

                $response = array(
                    "errorMessage" => 0,
                    "errorStatus"  => "",
                    "hour"         => $hour,
                    "name"         => utf8_decode($name),
                    "action"       => $action
                );
            }
        }
    } else {
        $response = array(
            "errorMessage" => "ACCOUNT DOESNT EXIST!",
            "errorStatus"  => 2,
            "hour"         => "",
            "name"         => "",
            "action"       => ""
        );
    }

    echo jsonify($response);
});

$app->get("/timezone/get/", function(){
  $timezoneOffset = getOffsetByTimeZone("Asia/Manila");
  $date           = date("M d, Y");
  $response       = array(
    "offset" => $timezoneOffset,
    "date"   => $date
  );
  echo jsonify($response);
});


$app->post("/backup/database/", function(){
    date_default_timezone_set("Asia/Manila");

    $startDate = $_POST["startDate"];
    $endDate   = $_POST["endDate"];
    EXPORT_TABLES("localhost","root","root","hris", $startDate, $endDate);

    //https://github.com/tazotodua/useful-php-scripts
    function EXPORT_TABLES($host,$user,$pass,$name, $startDate, $endDate, $tables=false, $backup_name=false ){
        $mysqli = new mysqli($host,$user,$pass,$name);
        $mysqli->select_db($name);
        $mysqli->query("SET NAMES 'utf8'");
        $queryTables = $mysqli->query('SHOW TABLES');
        while($row = $queryTables->fetch_row()) {
            // $target_tables[] = $row[0]; 
        }   
        // echo json_encode($target_tables);
        $target_tables = array(
            "leave_requests",
            "overtime_requests",
            "time_request",
            "time_log"
        );

        // echo json_encode($target_table7

        $content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--Database: `".$name."`\r\n\r\n\r\n";
        foreach($target_tables as $table){
            $result        = $mysqli->query("SELECT * FROM ".$table." WHERE date(date_created) >= '" .$startDate. "' AND date(date_created) <= '" . $endDate . "'");    
            $fields_amount = $result->field_count;  
            $rows_num      = $mysqli->affected_rows;    
            $res           = $mysqli->query('SHOW CREATE TABLE '.$table);   
            $TableMLine    = $res->fetch_row();
            $content       .= "\n\n".$TableMLine[1].";\n\n";
            for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter=0) {
                while($row = $result->fetch_row())  { //when started (and every after 100 command cycle):
                    if ($st_counter%100 == 0 || $st_counter == 0 )  {
                        $content .= "\nINSERT INTO ".$table." VALUES";
                    }
                        $content .= "\n(";
                        for($j=0; $j<$fields_amount; $j++){ 
                            $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 

                            if (isset($row[$j])){
                                if($j == 0){
                                    $content .= '""' ; 
                                }else{
                                    $content .= '"'.$row[$j].'"' ; 
                                }
                            }else{
                                $content .= '""';
                            }
                            if ($j<($fields_amount-1)){
                                $content.= ',';
                            }       
                        }
                        $content .=")";
                    //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                    if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) {
                        $content .= ";";
                    }else{
                        $content .= ",";
                    }   
                    $st_counter=$st_counter+1;
                }
            } $content .="\n\n\n";
        }
        $content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
        $backup_name = "Humano (".date('Y-m-d H:i:s A').").sql";
        header('Content-Type: application/octet-stream');   
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"".$backup_name."\"");  
        echo $content; 
        exit;
    }
});

$app->get("/change/date/format/:date", function($date){
  $date = date("Y-m-d", strtotime($date));

  $response = array($date);
  echo jsonify($response);
});

$app->post("/update/time/request/status/batch/", function(){
    $response     = array();
    $timeInUid    = xguid();
    $timeOutUid   = xguid();
    $session      = xguid();
    $uid = $_POST["uid"];
    $count = $_POST["count"];
    $timeRequestData = getOffsetTimeRequestByUid($uid);
    $timeIn       = $timeRequestData["time_in"];
    $timeIn       = date("Y-m-d H:i", strtotime($timeIn));
    $typeIn       = 0;
    $logIn        = "IN";
    $timeOut      = $timeRequestData["time_out"];
    $timeOut      = date("Y-m-d H:i", strtotime($timeOut));
    $typeOut      = 1;
    $logOut       = "OUT";
    $timeDate     = $timeRequestData["date_request"];
    $reason       = $timeRequestData["reason"];
    $admin        = $_POST["admin"];

    $reqStatus    = $_POST["status"];
    $status       = $timeRequestData["status"];
    $employee     = $timeRequestData["emp_uid"];
    $day          = date("N", strtotime($timeDate));
    $dateModified = date("Y-m-d H:i:s");
    $sTimeIn      = date("H:i:s", strtotime($timeIn));
    $sTimeOut     = date("H:i:s", strtotime($timeOut));

    $username     = getEmployeeUsernameByEmpUid($employee);

    $emp          = getEmployeeDetailsByUid($admin);
    $lastname     = $emp->lastname;
    $firstname    = $emp->firstname;
    $middlename   = $emp->middlename;

    $name         = $firstname . " " . $middlename . " " . $lastname;

    function getInitials($name){
    $words = explode(" ",$name);
    $inits = '';
    foreach($words as $word){
        $inits.=strtoupper(substr($word,0,1));
    }
    return $inits;  
    }

    $user = getInitials($name);

    if(strtotime($timeIn) <= strtotime($timeOut)){
        $valid = true;
    }else{
        $valid = false;
    }

    $checkRequest = checkPayrollSchedBeforeRequest($timeDate);
    $type         = getUserTypeByEmpUid($admin);

    if($type == "Administrator"){
        if($valid){
            $rule = getShiftUidInRules($employee, $day);
            if($rule){
                $shift = $rule["shift"];
                if($reqStatus == "Approved"){
                    $user1 = $user;
                    $user2 = "";
                    $check = checkTimeDateByEmpUid($employee, $timeDate);
                    if($check){
                        removeDateFromTimeInLogByEmpAndDate($employee, $timeDate);
                        removeDateFromTimeOutLogByEmpAndDate($employee, $timeDate);
                        addTimeSheetIn($timeInUid, $employee, $shift, $session, $typeIn, $timeIn, $status);
                        addTimeSheetOut($timeOutUid, $employee, $shift, $session, $typeOut, $timeOut, $status);
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 3,
                            "count" => $count
                        );
                    }else{
                        removeDateFromTimeInLogByEmpAndDate($employee, $timeDate);
                        removeDateFromTimeOutLogByEmpAndDate($employee, $timeDate);
                        addTimeSheetIn($timeInUid, $employee, $shift, $session, $typeIn, $timeIn, $status);
                        addTimeSheetOut($timeOutUid, $employee, $shift, $session, $typeOut, $timeOut, $status);
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0,
                            "count" => $count
                        );
                    }
                }else if($reqStatus == "Certified"){
                    $user2 = $user;
                    $user1 = "";
                    $check = checkTimeDateByEmpUid($employee, $timeDate);
                    if($check){
                        $response = array(
                            "prompt" => 3,
                            "count" => $count
                        );
                    }else{
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0,
                            "count" => $count
                        );
                    }
                }
            }else{
                $response = array(
                    "prompt" => 2,
                    "count" => $count
                );
            }

            $dateModified = date("Y-m-d H:i:s");

            if($reqStatus == "Approved"){
                $user1 = $user;
                $user2 = "";
                $check = checkTimeDateByEmpUid($employee, $timeDate);
                if($check){
                    $response = array(
                        "prompt" => 3,
                        "count" => $count
                    );
                }else{
                    editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                    $response = array(
                        "prompt" => 1,
                        "count" => $count
                    );
                }
            }else if($reqStatus == "Certified"){
                $user2 = $user;
                $user1 = "";
                editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                $response = array(
                    "prompt" => 1,
                    "count" => $count
                );
            }else{
                $user2 = "";
                $user1 = "";
                editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                $response = array(
                    "prompt" => 1,
                    "count" => $count
                );
            }
        }else{
            $response = array(
                "prompt" => 4,
                "count" => $count
            );
        }
    }else{
        if($checkRequest["prompt"]){
            if($valid){
                $rule = getShiftUidInRules($employee, $day);
                if($rule){
                    $shift = $rule["shift"];
                    if($reqStatus == "Approved"){
                        $user1 = $user;
                        $user2 = "";
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0,
                            "count" => $count
                        );
                    }else if($reqStatus == "Certified"){
                        $user2 = $user;
                        $user1 = "";
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 0,
                            "count" => $count
                        );
                    }
                }else{
                    $response = array(
                        "prompt" => 2,
                        "count" => $count
                    );
                }

                $dateModified = date("Y-m-d H:i:s");

                if($reqStatus == "Approved"){
                    $user1 = $user;
                    $user2 = "";
                    $check = checkTimeDateByEmpUid($employee, $timeDate);
                    if($check){
                        $response = array(
                            "prompt" => 3,
                            "count" => $count
                        );
                    }else{
                        editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                        $response = array(
                            "prompt" => 1,
                            "count" => $count
                        );
                    }
                }else if($reqStatus == "Certified"){
                    $user2 = $user;
                    $user1 = "";
                    editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                    $response = array(
                        "prompt" => 1,
                        "count" => $count
                    );
                }else{
                    $user2 = "";
                    $user1 = "";
                    editTimeRequest($uid, $timeIn, $timeOut, $timeDate, $reason, $reqStatus, $user1, $user2 ,$dateModified, $status);
                    $response = array(
                        "prompt" => 1,
                        "count" => $count
                    );
                }
            }else{
                $response = array(
                    "prompt" => 4,
                    "count" => $count
                );
            }
        }else{
            if($valid){
                $response = array(
                    "prompt" => 5,
                    "count" => $count
                );
            }else{
                $response = array(
                    "prompt" => 4,
                    "count" => $count
                );
            }
        }
    }

    echo jsonify($response);
});

$app->get("/get/resigned/employees/", function(){
    $response = array();
    $year = date("Y");

    $datas = getResignedEmployeesByYear($year);
    foreach ($datas as $data) {
        $employeeDetails = getEmployeeDetailsByUid($data->emp_uid);

        if($employeeDetails){
            $lastnames = utf8_decode($employeeDetails->firstname) . "_" . " ";
            $words = explode("_", $lastnames);
            $name = "";

            foreach ($words as $w) {
              $name .= $w[0];
            }

            $lastname = $name . ". " . utf8_decode($employeeDetails->lastname);
            
        }//end of getEmployeeDetailsByUid Function

        $employmentStatusDetails = getEmploymentStatusByUid($data->employment_status_uid);
        $response[] = array(
            "employmentStatusUid" => $data->type_uid,
            "employeeName" => $lastname,
            "employeeNo" => $employeeDetails->username,
            "dateHired" => date("m-d-y", strtotime($data->date_hired)),
            "dateResigned" => date("m-d-y", strtotime($data->date_resigned)),
            "employmentStatus" => $employmentStatusDetails->name,
            "dateCreated" => date("m-d-y", strtotime($data->date_created)),
            "dateModified" => date("m-d-y", strtotime($data->date_modified))
        );
    }

    echo jsonify($response);
});

$app->get("/get/new/hired/employees/", function(){
    $response = array();
    $year = date("Y");

    $datas = getNewHiredEmployeesByYear($year);
    foreach ($datas as $data) {
        $employeeDetails = getEmployeeDetailsByUid($data->emp_uid);

        if($employeeDetails){
            $lastnames = utf8_decode($employeeDetails->firstname) . "_" . " ";
            $words = explode("_", $lastnames);
            $name = "";

            foreach ($words as $w) {
              $name .= $w[0];
            }

            $lastname = $name . ". " . utf8_decode($employeeDetails->lastname);
            
        }//end of getEmployeeDetailsByUid Function

        $employmentStatusDetails = getEmploymentStatusByUid($data->employment_status_uid);
        $response[] = array(
            "employmentStatusUid" => $data->type_uid,
            "employeeName" => $lastname,
            "employeeNo" => $employeeDetails->username,
            "dateHired" => date("m-d-y", strtotime($data->date_hired)),
            "dateResigned" => date("m-d-y", strtotime($data->date_resigned)),
            "employmentStatus" => $employmentStatusDetails->name,
            "dateCreated" => date("m-d-y", strtotime($data->date_created)),
            "dateModified" => date("m-d-y", strtotime($data->date_modified))
        );
    }

    echo jsonify($response);
});

$app->get("/get/employee/birthdays/", function(){
    $response = array();
    // $latestDate = "1550-".date("m-d");
    $latestDate = date("m-d");
    $futureDate = date("m-d", strtotime("+3 week"));


    // echo "$latestDate = $futureDate";
    $datas = getUpcomingBirthdays($latestDate, $futureDate);
    
    foreach ($datas as $data) {
        $employeeDetails = getEmployeeDetailsByUid($data->emp_uid);

        if($employeeDetails){
            $lastnames = utf8_decode($employeeDetails->firstname) . "_" . " ";
            $words = explode("_", $lastnames);
            $name = "";

            foreach ($words as $w) {
              $name .= $w[0];
            }

            $lastname = $name . ". " . utf8_decode($employeeDetails->lastname);
            
        }//end of getEmployeeDetailsByUid Function

        $bday = date("Y-m-d", strtotime($data->bday));
        $today = date("Y-m-d");

        $bday = new DateTime($bday);
        $today = new DateTime($today);

        $age = $today->diff($bday);
        $age = ($age->y) + 1;

        $response[] = array(
            "empUid" => $data->emp_uid,
            "employeeName" => $lastname,
            "employeeNo" => $employeeDetails->username,
            "age" => $age,
            "birthday" => date("M d, Y", strtotime($data->bday)),
            "dateCreated" => date("m-d-y", strtotime($data->date_created)),
            "dateModified" => date("m-d-y", strtotime($data->date_modified))
        );
    }

    echo jsonify($response);
});



/* -----------------------------JEN----------------------------------   */


// $app->get("/scheduletype/get/data/:var" , function($var){
//     $response   = array();
//     $scheduletypes = read_schedule_type_all();
//     foreach ($scheduletypes as $scheduletype) {
//         $uid   = $scheduletype->uid;
//         $response[] = array(
//             "typeUid" => $scheduletype->uid,
//             "name"     => $scheduletype->type,
//         );
//     }

//     echo jsonify($response);
// });

$app->get("/department/get/data/:var" , function($var){
    $response   = array();
    $departments = read_department_all();
    foreach ($departments as $department) {
        $uid   = $department->uid;
        $response[] = array(
            "departmentUid" => $department->uid,
            "group"     => $department->group,
        );
    }

    echo jsonify($response);
});


$app->post("/schedule/new/:var" , function($var){
    $param = explode(".", $var);
    $response = array();
    $verified = 0;
    if(count($param) === 1) {

        $schedUid     = xguid();
        $type         = $_POST['type'];
        $date         = $_POST['date'];
        $shift        = $_POST['shift'];
        $name         = $_POST['name'];
        
        $dateCreated  = date("Y-m-d H:i:s");

        create_work_schedule($schedUid , $type, $date, $shift, $name, $dateCreated);
        $verified = 1;
    }
    $response[] = array(
        "verified" => $verified,
        "shift" => $shift
    );

    echo jsonify($response);
});


$app->get("/schedule/get/data/:var" , function($var){

    $response = array();

    $schedules = read_work_schedule();

    foreach ($schedules as $schedule) {
        $uid   = $schedule->uid;
        $response[] = array(
            "schedUid" => $schedule->uid,
            "type" => $schedule->schedule_type,
            "date" => $schedule->schedule_date, 
            "shift" => $schedule->shift_uid          
        );
    }
    echo jsonify($response);
});

$app->get("/workschedule/:var" , function($var){
    $list = array();
    $response = array();

    $verified = 0;
    $schedules = read_work_schedule_by_uid($var);
    foreach ($schedules as $schedule) {
        $verified = 1;
        $list[] = array(
            "schedUid" => $schedule->uid,
            "type" => $schedule->schedule_type,
            "date" => $schedule->schedule_date, 
            "shift" => $schedule->shift_uid,
            "verified" => $verified          
        );
    }

    // $response[] = array(
    //     "result" => $list,
    //     "verified" => $verified
    // );

    echo jsonify($list);
});


$app->get("/get/schedule/data/:uid", function($uid){
    $response = array();
    $schedule     = read_work_schedule_by_uid($uid);

    if($schedule){
        $response = array(
            "schedUid" => $schedule->uid,
            "type"     => $schedule->schedule_type,
            "date"    => $schedule->schedule_date,
            "shift"    => $schedule->shift_uid
        );
    }

    echo jsonify($response);
});



$app->post("/update/schedule/:var", function($var){
    $param        = explode(".", $var);
    $schedUid     = $param[0];
    $response     = array();
    if(count($param) === 1) {
        $verified     = 0;
        $type         = $_POST['type'];
        $date         = $_POST['date'];
        $shift        = $_POST['shift'];
        $dateModified = date("Y-m-d H:i:s");

        //$sched = read_work_schedule_by_uid($schedUid);

        $validate = validateWorkSchedule($schedUid)
        if ($validate) {
            $verified = 1;
            update_work_schedule($schedUid, $type, $date, $shift, $dateModified);
        };

        echo jsonify($response);
    }

});

/*---------------------------------------------END FOR PRINTING---------------------------------------------*/

$app->run();

?>

