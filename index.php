<?php

$url = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$url = $url === '' ? '/' : $url;
$method = $_SERVER['REQUEST_METHOD'];
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);


function routing($method, $url,$queryParams){
    if($method == "GET"){
        switch($url){
            case '/':
            case '/index':
                require "./pages/login/index.html";
                break;
            case '/Agent':
                require "./pages/Agent/Agent.html";
            break;
            case '/Nurse':
                require "./pages/Nurse/nurse.html";
            break;
            case '/patients':
                require './routes/Patient.php';
            break;
            case '/patient/detail':
                require "./routes/Patient.php";
            break;
            case '/prescriptions':
                require './routes/Prescription.php';
            break;
            case '/prescription':
                require './routes/Prescription.php';
            break;
            case "/room":
                require "./routes/Chambre.php";
            exit;
            case "/nurse/room":
                require "./routes/Chambre.php";
            exit;
            case '/nurse/patients':
                require "./routes/Nurse.php";
            break;
            case '/nurse/patient/search':
                require './routes/Nurse.php';
            break;
            case '/patient':
                if (isset($queryParams['id'])) {
                    $_GET['id'] = $queryParams['id']; 
                    require './routes/Patient.php';   
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Missing 'id' parameter."]);
                }
            break;
            case '/admin/patients/all':
                require './routes/Admin.php';
            break;
            case '/sejour/modal-details':
                require './routes/Sejour.php';
            break;
            default:
                http_response_code(404);
                echo "404 - Page not found";
        }
    }
    else if($method == "POST"){
        switch($url){
            case '/login':
                require './routes/User.php';
            break;
            case '/patient':
                require './routes/Patient.php';
            break;
            case '/suivis':
                require "./routes/Suivi.php";
            case '/prescription':
                require './routes/Prescription.php';
            case '/sejour/new':
                require './routes/Sejour.php';
            break;
            default:
                http_response_code(404);
                echo "404 - Not found (POST)";
            break;
        }
    }
    else if($method == "PUT"){
        switch($url){
            case '/patient':
                require './routes/Patient.php';
            break;
            case '/prescription':
                require './routes/Prescription.php';
            break;
            case '/sejour/room':
                require './routes/Sejour.php';
            break;
            case '/sejour/status':
                require './routes/Sejour.php';
            break;
            default:
                http_response_code(404);
                echo "404 - Not found (PUT)";
        }
    }
    else if($method == "DELETE"){
        switch($url){
            case '/prescription':
                require './routes/Prescription.php';
            break;
            default:
                http_response_code(404);
                echo "404 - Not found (DELETE)";
        }
    }
}

routing($method, $url,$queryParams);
