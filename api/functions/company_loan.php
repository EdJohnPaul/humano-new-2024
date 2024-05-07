<?php

function create_company_loan_entry($value)
{
    $keys = set_of_metakeys("company_loan_entry");
    $uid = xguid();
    create_company_loan($uid);
    for($i=0; $i<count($keys); $i++)
    {
        $sql = ORM::forTable('company_loan_entry')->create();
        $sql->uid = $uid;
        $sql->company_loan_key = $keys[$i];
        $sql->company_loan_value = $value[$i];
        $sql->save();
    }
    
}

function create_company_loan($uid)
{
    $sql = ORM::forTable('company_loan')->create();
    $sql->uid = $uid;
    $sql->loans_type = get_loan_type('Company Loan');
    $sql->status = 1;
    $sql->date_created = date("Y-m-d H:i:s");
    $sql->date_modified = date("Y-m-d H:i:s");
    $sql->save();
}

function get_loan_type($loanName)
{
    $sql = ORM::forTable('loans')->where('name',$loanName)->findOne();
    return $sql->uid;
}

function read_company_loan()
{
    $sql = ORM::forTable('company_loan')->findMany();
    return $sql;
}

function read_company_loan_status_enabled()
{
    $sql = ORM::forTable('company_loan')->where("status",1)->findMany();
    return $sql;
}

function read_company_loan_entry_by_keys($uid,$key)
{
    $sql = ORM::for_table("company_loan_entry")->where("uid",$uid)->where("company_loan_key",$key)->findOne();
    return $sql;
}

function read_company_loan_entry($uid)
{
    $data = array();
    $keys = set_of_metakeys("company_loan_entry");
    foreach($keys as $key)
    {
        $result = read_company_loan_entry_by_keys($uid,$key);
        if($result)
        {
            $data[] = array($key => $result->company_loan_value);
        }
    }
    return $data;
    // echo jsonify($data);
}

function read_enabled_company_loan_entry($uid)
{
    $data = array();
    $keys = set_of_metakeys("company_loan_entry");
    $results = read_company_loan_status_enabled();
    $info = null;
    $data = array();
    foreach($results as $result)
    {
        $data[] = array("uid"=>$result->uid,"companyLoanInfo"=>read_company_loan_entry($result->uid));
    }
    return $data;
}

function  read_all_company_loan()
{
    $results = read_company_loan();
    $info = null;
    $data = array();
    foreach($results as $result)
    {
        $data[] = array("uid"=>$result->uid,"companyLoanInfo"=>read_company_loan_entry($result->uid));
    }
    return $data;
    // echo jsonify($data);
}

function update_company_loan_entry($uid,$values)
{
    $keys = set_of_metakeys("company_loan_entry");
    for($i=0; $i<count($keys); $i++)
    {
        $sql = read_company_loan_entry_by_keys($uid,$keys[$i]);
        $sql->company_loan_value = $values[$i];
        $sql->save();
    }
}

function update_company_loan_status($uid,$status)
{
    $sql = ORM::forTable("company_loan")->where("uid",$uid)->findOne();
    $sql->status = $status;
    $sql->save();
}

function create_company_loan_request_entry($req_uid,$emp_uid,$loan_uid,$application_no,$empEmail,$loanAmount,$loanPeriod,$amortization,$interest,$payment)
{
    $keys = set_of_metakeys("company_loan_request_entry");
    $values = array($emp_uid,$loan_uid,$application_no,$empEmail,$loanAmount,$loanPeriod,$amortization,$interest,$payment,date('Y/m/d h:i:s')," ",date('Y/m/d h:i:s'),date('Y/m/d h:i:s'),"Pending","","","","","","","");

    for($i=0; $i<count($keys); $i++)
    {
        $sql = ORM::forTable("company_loan_request_entry")->create();
        $sql->company_loan_request_uid = $req_uid;
        $sql->company_loan_entry_key = $keys[$i];
        $sql->company_loan_entry_value = $values[$i];
        $sql->save();
    }
}

function create_company_loan_request($req_uid,$emp_uid,$loan_uid)
{
    $sql = ORM::forTable("company_loan_request")->create();
    $sql->request_uid = $req_uid;
    $sql->emp_uid = $emp_uid;
    $sql->loan_uid = $loan_uid;
    $sql->date_created = date('Y/m/d h:i:s');
    $sql->date_modified = date('Y/m/d h:i:s');
    $sql->active = 0;
    $sql->request_status = 1;
    $sql->save();
}

function read_company_loan_request()
{
    $sql = ORM::forTable("company_loan_request")->findMany();
    return $sql;
}

function read_company_loan_by_emp_uid($emp_uid)
{
    $sql = ORM::forTable("company_loan_request")->where("emp_uid",$emp_uid)->where("request_status",1)->findMany();
    return $sql;
}

function read_company_loan_request_by_emp_uid($uid)
{
    $sql = ORM::forTable("company_loan_request")->where("emp_uid",$uid)->findOne();
    return $sql->request_uid;
}

function read_company_loan_request_entry_by_keys($uid,$key)
{
    $sql = ORM::for_table("company_loan_request_entry")->where("company_loan_request_uid",$uid)->where("company_loan_entry_key",$key)->findOne();
    return $sql;
}

function read_all_company_loan_request()
{
    $results = read_company_loan_request();
    $data = array();
    $keys = set_of_metakeys("company_loan_request_entry");

    foreach($results as $result)
    {
        // $data[] = array("uid"=>$result->request_uid,"companyLoanInfo"=>read_company_loan_request_entry($result->request_uid));
        $info = read_company_loan_request_entry($result->request_uid);
        $data[] = array("uid"=>$result->request_uid,
                        $keys[0]=>$info[0][$keys[0]],
                        $keys[1]=>$info[1][$keys[1]],
                        $keys[2]=>$info[2][$keys[2]],
                        $keys[3]=>$info[3][$keys[3]],
                        $keys[4]=>$info[4][$keys[4]],
                        $keys[5]=>$info[5][$keys[5]],
                        $keys[6]=>$info[6][$keys[6]],
                        $keys[7]=>$info[7][$keys[7]],
                        $keys[8]=>$info[8][$keys[8]],
                        $keys[9]=>$info[9][$keys[9]],
                        $keys[10]=>$info[10][$keys[10]],
                        $keys[11]=>$info[11][$keys[11]],
                        $keys[12]=>$info[12][$keys[12]],
                        $keys[13]=>$info[13][$keys[13]],
                        $keys[14]=>$info[14][$keys[14]],
                        $keys[15]=>$info[15][$keys[15]],
                        $keys[16]=>$info[16][$keys[16]],
                        $keys[17]=>$info[17][$keys[17]],
                        $keys[18]=>$info[18][$keys[18]],
                        $keys[19]=>$info[19][$keys[19]]
                    );
    }
    return $data;
    // echo jsonify($data);
}

function read_company_loan_request_entry($uid)
{
    $data = array();
    $keys = set_of_metakeys("company_loan_request_entry");
    foreach($keys as $key)
    {
        $result = read_company_loan_request_entry_by_keys($uid,$key);
        if($result)
        {
            $data[] = array($key => $result->company_loan_entry_value);
        }
    }
    return $data;
    // echo jsonify($data);
}

function read_emp_company_loans($emp_uid)
{
    $results = read_company_loan_by_emp_uid($emp_uid); 
    $keys = set_of_metakeys("company_loan_request_entry");
    $data = array();

    foreach($results as $result)
    {
        $info = read_company_loan_request_entry($result->request_uid);
        $data[] = array("uid"=>$result->request_uid,
                        $keys[0]=>$info[0][$keys[0]],
                        $keys[1]=>$info[1][$keys[1]],
                        $keys[2]=>$info[2][$keys[2]],
                        $keys[3]=>$info[3][$keys[3]],
                        $keys[4]=>$info[4][$keys[4]],
                        $keys[5]=>$info[5][$keys[5]],
                        $keys[6]=>$info[6][$keys[6]],
                        $keys[7]=>$info[7][$keys[7]],
                        $keys[8]=>$info[8][$keys[8]],
                        $keys[9]=>$info[9][$keys[9]],
                        $keys[10]=>$info[10][$keys[10]],
                        $keys[11]=>$info[11][$keys[11]],
                        $keys[12]=>$info[12][$keys[12]],
                        $keys[13]=>$info[13][$keys[13]],
                        $keys[14]=>$info[14][$keys[14]],
                        $keys[15]=>$info[15][$keys[15]],
                        $keys[16]=>$info[16][$keys[16]],
                        $keys[17]=>$info[17][$keys[17]],
                        $keys[18]=>$info[18][$keys[18]],
                        $keys[19]=>$info[19][$keys[19]]
                    );

    }
    return $data;
    // echo jsonify($data);
}

function read_all_company_loan_request_by_status($status)
{
    $results = read_company_loan_request_by_status($status);
    $data = array();
    $keys = set_of_metakeys("company_loan_request_entry");

    foreach($results as $result)
    {
        // $data[] = array("uid"=>$result->request_uid,"companyLoanInfo"=>read_company_loan_request_entry($result->request_uid));
        $info = read_company_loan_request_entry($result->request_uid);
        $data[] = array("uid"=>$result->request_uid,
                        $keys[0]=>$info[0][$keys[0]],
                        $keys[1]=>$info[1][$keys[1]],
                        $keys[2]=>$info[2][$keys[2]],
                        $keys[3]=>$info[3][$keys[3]],
                        $keys[4]=>$info[4][$keys[4]],
                        $keys[5]=>$info[5][$keys[5]],
                        $keys[6]=>$info[6][$keys[6]],
                        $keys[7]=>$info[7][$keys[7]],
                        $keys[8]=>$info[8][$keys[8]],
                        $keys[9]=>$info[9][$keys[9]],
                        $keys[10]=>$info[10][$keys[10]],
                        $keys[11]=>$info[11][$keys[11]],
                        $keys[12]=>$info[12][$keys[12]],
                        $keys[13]=>$info[13][$keys[13]],
                        $keys[14]=>$info[14][$keys[14]],
                        $keys[15]=>$info[15][$keys[15]],
                        $keys[16]=>$info[16][$keys[16]],
                        $keys[17]=>$info[17][$keys[17]],
                        $keys[18]=>$info[18][$keys[18]],
                        $keys[19]=>$info[19][$keys[19]]
                    );
    }
    return $data;
    // echo jsonify($data);
}

function read_company_loan_request_by_status($status)
{
    switch($status)
    {
        case "Active":
            $sql = ORM::forTable("company_loan_request")->where('active',1)->findMany();
            break;
        case "Pending":
            $sql = ORM::forTable("company_loan_request")->where('request_status',1)->findMany();
            break;
        case "Denied":
            $sql = ORM::forTable("company_loan_request")->where('denied',1)->findMany();
            break;
        case "Paid":
            $sql = ORM::forTable("company_loan_request")->where('archived',1)->findMany();
            break;
    }
    return $sql;
}

