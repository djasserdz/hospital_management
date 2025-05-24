<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once "./config/database.php";
include_once "./models/Chambre.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$parsed_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$input = json_decode(file_get_contents("php://input"), true);


if ($method == "GET") {
     if(strpos($parsed_url,'/nurse/room') !== false){
        if(empty($_GET['nurse_id'])){
            http_response_code(400);
            echo json_encode(["message"=>"Id nurse is required"]);
            exit;
        }
        else{
            $room=new Chambre($db);
            $result=$room->getrooms($_GET['nurse_id']);
            echo json_encode($result);
            exit;
        }
    }
     else if (strpos($parsed_url, '/room') !== false) {
        if (isset($_GET['id_service'])) {
            $room = new Chambre($db);
            $room->id_service=$_GET['id_service'];
            $rooms=$room->getAvailableByService();
            echo json_encode($rooms);
        } else {
            echo json_encode(["error" => "Missing id_service parameter"]);
        }
    }
    
    else{
        http_response_code(404);
        echo json_encode(["message"=>"Endpoint does not exist"]);
    }
}
