<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once './config/database.php';
include_once './models/User.php';

$database = new Database();
$db=$database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$data = json_decode(file_get_contents("php://input"));

if ($method === 'POST') {

    if (strpos($uri, '/register') !== false) {
        if (!empty($data->id_service) && !empty($data->full_name) && !empty($data->email) && !empty($data->password) && !empty($data->role)) {
            $user->id_service = $data->id_service;
            $user->full_name = $data->full_name;
            $user->email = $data->email;
            $user->password = $data->password;
            $user->role = $data->role;

            if ($user->Create()) {
                http_response_code(201);
                echo json_encode(["message" => "User registered successfully"]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "User registration failed"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
        }
    }


    elseif (strpos($uri, '/login') !== false) {
        if (empty($data->email) && empty($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Email and password required"]);
            
            
        } else {
            $user->email = $data->email;
            $user->password = $data->password;

            $loggedInUser = $user->login();

            if ($loggedInUser) {
                http_response_code(200);
                echo json_encode([
                    "message" => "Login successful",
                    "user" => $loggedInUser
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Invalid email or password"]);
            }
        }
    }
    else {
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
}
