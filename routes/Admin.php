<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS"); // Admins typically GET data, OPTIONS for preflight
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// It's crucial to implement proper authentication and authorization here
// For example, check if the logged-in user has an 'admin' role.
// This is a placeholder for where that check should go.
/*
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access denied. Admin role required."]);
    exit;
}
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Patient.php';

$request_method = $_SERVER["REQUEST_METHOD"];

if ($request_method === "OPTIONS") {
    http_response_code(200);
    exit();
}

if ($request_method === 'GET') {
    $database = new Database();
    $db = $database->getConnection();

    $patient_model = new Patient($db);
    
    $patients_list = $patient_model->getAllPatientsForAdmin();

    if ($patients_list !== false) { // getAllPatientsForAdmin returns [] on error, not false, but check is fine
        http_response_code(200);
        echo json_encode($patients_list);
    } else {
        // This path might not be reached if getAllPatientsForAdmin always returns [] on error
        http_response_code(500);
        echo json_encode(["message" => "Failed to retrieve patient list for admin."]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Method not allowed for this endpoint."]);
}
?> 