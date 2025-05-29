<?php
error_log("ENTERING routes/Nurse.php - METHOD: " . $_SERVER['REQUEST_METHOD'] . " URI: " . $_SERVER['REQUEST_URI']); // DEBUG

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/Nurse.php';

$database = new Database();
$db = $database->getConnection();

$nurse = new Nurse($db);
$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($url, PHP_URL_PATH);

$input = json_decode(file_get_contents("php://input"), true);

if ($method === 'GET') {
    if($parsed_url === "/nurse/patient/search"){
        if(empty($_GET['nurse_id'] ) || empty($_GET['search'])){
           http_response_code(400);
           echo json_encode(["message"=>"missing ID nurse / search"]);
           exit;
        }
        $nurse_model = new Nurse($db);
        $result = $nurse_model->searchPatient($_GET['search'], $_GET['nurse_id']);
        http_response_code(200);
        echo json_encode($result);
        exit;

  }
    else if (strpos($parsed_url, '/nurse/patients') !== false) {
        error_log("Attempting to get /nurse/patients. Nurse ID from GET: " . (isset($_GET['nurse_id']) ? $_GET['nurse_id'] : 'NOT SET')); // DEBUG
        try {
            if (!isset($_GET['nurse_id']) || empty($_GET['nurse_id'])) {
                http_response_code(400);
                error_log("Nurse ID missing or empty."); // DEBUG
                echo json_encode(["error" => "Nurse ID is required"]);
                exit;
            }

            $nurse->id_user = $_GET['nurse_id'];
            error_log("Calling nurse->getAllPatients() for nurse_id: " . $nurse->id_user); // DEBUG
            $result = $nurse->getAllPatients($_GET['nurse_id']);
            error_log("Result from getAllPatients(): " . print_r($result, true)); // DEBUG

            if ($result !== false) { // An empty array is a valid result (no patients)
                http_response_code(200); // Ensure 200 for valid (even if empty) results
                echo json_encode($result); 
            } else {
                // This 'else' might not be hit if getAllPatients returns [] on error as per recent model changes.
                // Kept for robustness, but errors in model should be logged there.
                http_response_code(500);
                error_log("getAllPatients returned false. This might indicate a deeper issue if not expected."); // DEBUG
                echo json_encode(["error" => "Failed to retrieve patients (model returned false)"]);
            }
        } catch (Throwable $e) { // Catch Throwable for broader error coverage
            http_response_code(500);
            error_log("Error in /nurse/patients route: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString()); // DEBUG
            echo json_encode(["error" => "Internal server error: " . $e->getMessage()]);
        }
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Invalid endpoint"]);
        exit;
    }

} else if ($method === 'POST') {
    
    if (strpos($parsed_url, '/suivis') !== false) {
        error_log("POST /suivis received data: " . print_r($input, true)); // DEBUG
        // Required fields check - updated
        $required_fields = ['id_sejour', 'id_nurse', 'etat_santee', 'tension', 'temperature', 'frequence_quardiaque', 'saturation_oxygene', 'glycemie', 'Remarque', 'Date_observation', 'poids', 'taille'];

        foreach ($required_fields as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                $error_message = "Missing required field: $field";
                error_log($error_message . " - Payload: " . print_r($input, true)); // DEBUG
                echo json_encode(["message" => $error_message]);
                exit;
            }
        }

        $created = $nurse->createSuivi($input);

        if ($created) {
            http_response_code(201);
            echo json_encode(["message" => "Suivi record created successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create Suivi record"]);
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
