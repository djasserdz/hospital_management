<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once './config/database.php';
include_once './models/Suivi.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($url, PHP_URL_PATH);

$input = json_decode(file_get_contents("php://input"), true);


if ($method === "POST") {
    if (strpos($parsed_url, '/suivis') !== false) {
        $suivi = new Suivi($db);

        $suivi->id_sejour = $input['id_sejour'] ?? null;
        $suivi->id_nurse = $input['id_nurse'] ?? null;
        $suivi->etat_santee = $input['etat_santee'] ?? null;
        $suivi->tension = $input['tension'] ?? null;
        $suivi->temperature = $input['temperature'] ?? null;
        $suivi->frequence_quardiaque = $input['frequence_quardiaque'] ?? null;
        $suivi->saturation_oxygene = $input['saturation_oxygene'] ?? null;
        $suivi->glycemie = $input['glycemie'] ?? null;
        $suivi->Remarque = $input['Remarque'] ?? null;
        $suivi->Date_observation = $input['Date_observation'] ?? date('Y-m-d H:i:s');

        if ($suivi->create($input)) {
            http_response_code(200);
            echo json_encode(["message" => "Suivi record created successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create Suivi record"]);
        }
        exit;
    }


} else if ($method === "PUT") {
    if (strpos($parsed_url, '/suivis') !== false) {
        if (!isset($input['id_suivi'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing id_suivi for update"]);
            exit;
        }

        $suivi = new Suivi($db);
        $success = $suivi->update($input);

        if ($success) {
            http_response_code(200);
            echo json_encode(["message" => "Suivi record updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update Suivi record"]);
        }
        exit;
    }


} else if ($method === "GET") {
    if (strpos($parsed_url, '/suivis') !== false && isset($_GET['id_sejour'])) {
     
        echo json_encode(["message" => "GET /suivis?id_sejour=... not implemented yet"]);
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
