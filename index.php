<?php

$url = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$url = $url === '' ? '/' : $url;
$method = $_SERVER['REQUEST_METHOD'];

function routing($method, $url){
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
            case '/patients':
                require './routes/Patient.php';
            break;
               default: http_response_code(404);
                echo "404 - Not found (POST)";
        }
    }
    else if($method == "PUT"){
        switch($url){
            default:
                http_response_code(404);
                echo "404 - Not found (PUT)";
        }
    }
    else if($method == "DELETE"){
        switch($url){
            default:
                http_response_code(404);
                echo "404 - Not found (DELETE)";
        }
    }
}

routing($method, $url);
