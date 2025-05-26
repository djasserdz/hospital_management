<?php
require_once __DIR__ . '/../models/Sejour.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Chambre.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$sejour = new Sejour($db);
$user = new User($db);
$chambre = new Chambre($db);

// ADD THESE LINES FOR DEBUGGING
error_log("ROUTES/SEJOUR.PHP: Method=" . $_SERVER["REQUEST_METHOD"] . ", URI=" . $_SERVER['REQUEST_URI']);
// END OF DEBUG LINES

$request_method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// ADD THIS LINE FOR DEBUGGING
error_log("ROUTES/SEJOUR.PHP: Parsed URI array: " . print_r($uri, true));
// END OF DEBUG LINES

// Assuming the route is /sejour/room
if ($uri[1] === 'sejour' && isset($uri[2]) && $uri[2] === 'room' && $request_method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if (
        !empty($data->id_sejour) &&
        isset($data->id_chambre) &&
        !empty($data->id_agent)
    ) {
        $id_sejour = $data->id_sejour;
        $new_id_chambre = $data->id_chambre;
        $id_agent = $data->id_agent;

        $nurse_service_id = $user->getServiceId($id_agent);
        if ($nurse_service_id === null) {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to verify nurse's service."));
            exit;
        }

        $new_chambre_service_id = null;
        if ($new_id_chambre !== null && $new_id_chambre != 0) {
            $new_chambre_service_id = $chambre->getServiceId($new_id_chambre);
            if ($new_chambre_service_id === null) {
                http_response_code(400);
                echo json_encode(array("message" => "Unable to verify new room's service or room does not exist."));
                exit;
            }
            if ($nurse_service_id != $new_chambre_service_id) {
                http_response_code(400);
                echo json_encode(array("message" => "Room assignment failed: Nurse and Room services do not match."));
                exit;
            }
        }
        
        $old_id_chambre = $sejour->getCurrentChambreId($id_sejour);
        
        if ($sejour->updateRoom($id_sejour, $new_id_chambre, $old_id_chambre)) {
            http_response_code(200);
            echo json_encode(array("message" => "Room updated successfully for sejour."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update room for sejour. Check server logs for details."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to update room. Data is incomplete. id_sejour, id_chambre, and id_agent are required."));
    }
} else if ($uri[1] === 'sejour' && isset($uri[2]) && $uri[2] === 'status' && $request_method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->id_sejour) && !empty($data->Date_sortie)) {
        if ($sejour->updateStatus($data->id_sejour, $data->Date_sortie)) {
            http_response_code(200);
            echo json_encode(array("message" => "Sejour status updated successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update sejour status. Check server logs."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to update status. id_sejour and Date_sortie are required."));
    }
} else {
    if ($request_method === 'PUT') {
        http_response_code(404);
        echo json_encode(array("message" => "PUT endpoint not found for sejour."));
    }
}
?> 