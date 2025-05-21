<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once './config/database.php';
include_once './models/Patient.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($url, PHP_URL_PATH);

$input = json_decode(file_get_contents("php://input"), true);

if ($method === "GET") {
    if (strpos($parsed_url, '/patients') !== false) {
        $patients = new Patient($db);
        $data = $patients->readAll(); // get all patients

        echo json_encode(["patients" => $data]);
        exit;
    } else if (strpos($parsed_url, '/patient') !== false) {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing patient id"]);
            exit;
        }
        $patient = new Patient($db);
        $patient->id_patient = $_GET['id'];
        $data = $patient->readOne(); // should return associative array with patient details

        if ($data) {
            echo json_encode($data);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Patient not found"]);
        }
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Endpoint does not exist"]);
        exit;
    }
} else if ($method === "POST") {
    if (strpos($parsed_url, '/patients') !== false) {
        $patient = new Patient($db);
        $patient->full_name = $input['full_name'] ?? null;
        $patient->age = $input['age'] ?? null;
        $patient->sex = $input['sex'] ?? null;
        $patient->adress = $input['adress'] ?? null;
        $patient->telephone = $input['telephone'] ?? null;
        $patient->groupage = $input['groupage'] ?? null;

        $patient->create();
        http_response_code(200);
        echo json_encode(["message" => "New Patient Created"]);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Endpoint does not exist"]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => "METHOD NOT ALLOWED"]);
    exit;
}
