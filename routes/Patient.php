<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once './config/database.php';
include_once './models/Patient.php';

$database=new Database();
$db=$database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($url, PHP_URL_PATH);

$input = json_decode(file_get_contents("php://input"));


if($method === "GET"){
    if(strpos($parsed_url,'/patients') !=="false"){
        $patients=new Patient($db);
        $data = $patients->readAll(); // get the data

    
    echo json_encode(["patients"=>$data]);
    }
    else{
        http_response_code(404);
        echo json_encode(["message"=>"End point does not exist"]);
    }
}
else if($method ==="POST"){
    if(strpos($parsed_url,'/patients') !=="false"){
        $patient=new Patient($db);
        $patient->full_name=$input['full_name'];
        $patient->age=$input['age'];
        $patient->sex=$input['sex'];
        $patient->adress=$input['adress'];
        $patient->telephone=$input['telephone'];
        $patient->groupage=$input['groupage'];

        $patient->create();
        http_response_code(200);
        echo json_encode(["message"=>"New Patient Created"]);
    }
    else{
        http_response_code(404);
        echo json_encode(["message"=>"End point does not exist"]);
    }
}
else{
    http_response_code(405);
    echo json_encode(['message'=>"METHOD NOT ALLOWED"]);
}


?>