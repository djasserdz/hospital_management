<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once './config/database.php';
include_once './models/Nurse.php';

$database = new Database();
$db = $database->getConnection();

$nurse = new Nurse($db);
$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($url, PHP_URL_PATH);

$input = json_decode(file_get_contents("php://input"), true);

if ($method === 'GET') {
    if (strpos($parsed_url, '/nurse/patients/search') !== false && isset($_GET['fullname'])) {
        $fullname = $_GET['fullname'];
        $result = $nurse->searchPatient($fullname);

        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Patient not found"]);
        }
        exit;
    }
    else if (strpos($parsed_url, '/nurse/patients') !== false) {
        try {
            if (!isset($_GET['nurse_id']) || empty($_GET['nurse_id'])) {
                http_response_code(400);
                echo json_encode(["error" => "Nurse ID is required"]);
                exit;
            }
                         
            $nurse->id_user = $_GET['nurse_id'];
            $result = $nurse->getAllPatients();
                         
            if ($result !== false) {
                echo json_encode($result); 
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to retrieve patients"]);
            }
                     
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Internal server error: " . $e->getMessage()]);
        }
        exit;
    }

    else{
        http_response_code(404);
        echo json_encode(["message" => "Invalid endpoint"]);
        exit;
    }
} else if ($method === "PUT") {
    if (strpos($parsed_url, '/suivis') !== false) {
        if (!isset($input['id_suivi'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing id_suivi for update"]);
            exit;
        }

        $updated = $nurse->updateSuivi($input);

        if ($updated) {
            http_response_code(200);
            echo json_encode(["message" => "Suivi record updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update Suivi record"]);
        }
        exit;
    }

    http_response_code(404);
    echo json_encode(["message" => "Invalid endpoint"]);
    exit;

} else if ($method === "OPTIONS") {
    http_response_code(200);
    exit;

} else {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit;
}
