<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT, DELETE, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/Prescription.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];
// Normalize URL path by removing trailing slashes
$parsed_url = rtrim(parse_url($url, PHP_URL_PATH), '/');
$input = json_decode(file_get_contents("php://input"), true);

if ($method === "GET") {
    if ($parsed_url === '/prescription') {
        if (empty($_GET['id_sejour'])){
            http_response_code(400);
            echo json_encode(["message" => "Missing id_sejour parameter"]);
            exit;
        }
        $prescription = new Prescription($db);
        $data = $prescription->readOne($_GET['id_sejour']);
        if ($data) {
            echo json_encode($data);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Prescription not found"]);
        }
        exit;
    }
    else if ($parsed_url === '/prescriptions') {
        $prescription = new Prescription($db);
        $data = $prescription->readAllBySejour($_GET['id_sejour']);
        echo json_encode(["prescriptions" => $data]);
        exit;
    }  
    else {
        http_response_code(404);
        echo json_encode(["message" => "Endpoint does not exist"]);
        exit;
    }
} elseif ($method === "POST") {
    if ($parsed_url === '/prescription') {
        if (empty($input)) {
            http_response_code(400);
            echo json_encode(["message" => "Information needed"]);
            exit;
        }
        $prescription = new Prescription($db);
        $success = $prescription->create($input);
        if ($success) {
            http_response_code(200);
            echo json_encode(["message" => "Prescription created successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create prescription"]);
        }
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Endpoint does not exist"]);
        exit;
    }
} elseif ($method === "PUT") {
    if ($parsed_url === '/prescription') {
        if (empty($input) || !isset($input['id_prescription'])) {
            http_response_code(400);
            echo json_encode(["message" => "Information needed or missing id_prescription"]);
            exit;
        }
        $prescription = new Prescription($db);
        $success = $prescription->update($input);
        if ($success) {
            http_response_code(200);
            echo json_encode(["message" => "Prescription updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update prescription"]);
        }
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Endpoint does not exist"]);
        exit;
    }
} elseif ($method === "DELETE") {
    if ($parsed_url === '/prescription' && isset($_GET['id'])) {
        $prescription = new Prescription($db);
        $success = $prescription->delete($_GET['id']);
        if ($success) {
            http_response_code(200);
            echo json_encode(["message" => "Prescription deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete prescription"]);
        }
        exit;
    }
     else {
        http_response_code(400);
        echo json_encode(["message" => "Missing id for delete"]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => "METHOD NOT ALLOWED"]);
    exit;
}
