<?php
class Nurse {
    private $conn;
    private $table = "Users";

    public $id_user;
    public $id_service;
    public $full_name;
    public $email;
    public $password;
    public $role = "nurse";

    public function __construct($db) {
        $this->conn = $db;
    }

   

    public function getAllPatients($id_infermier) {
        // First, get the service ID for the nurse
        $query1 = "SELECT id_service FROM Users WHERE id = :id_infermier AND role = 'nurse'";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->bindParam(':id_infermier', $id_infermier);
        $stmt1->execute();
        $nurse_data = $stmt1->fetch(PDO::FETCH_ASSOC);

        if (!$nurse_data) {
            error_log("Nurse not found or not a nurse: user_id " . $id_infermier);
            return ["error" => "Nurse not found or not a nurse.", "data" => []];
        }
        $actual_id_service = $nurse_data['id_service'];

        if (!$actual_id_service) {
            error_log("Nurse service ID not found for user_id: " . $id_infermier);
            return ["error" => "Nurse service ID not found.", "data" => []];
        }

        // Then, get all patients in that service with their current sejour and room
        $query2 = "SELECT 
                        P.*, 
                        S.id_sejour, 
                        S.Date_entree, 
                        S.Date_sortiee,
                        CH.id_chambre AS sejour_id_chambre, 
                        CH.numero_cr as room_number, 
                        SRV.nom_service as service_name,
                        P.full_name,
                        LatestSuivi.etat_santee as latest_etat_santee
                    FROM Patients P
                    JOIN Sejour S ON P.id_patient = S.id_patient
                    JOIN Chambres CH ON S.id_chambre = CH.id_chambre
                    JOIN Services SRV ON CH.id_service = SRV.id_service
                    LEFT JOIN (
                        SELECT 
                            sui.id_sejour, 
                            sui.etat_santee,
                            ROW_NUMBER() OVER (PARTITION BY sui.id_sejour ORDER BY sui.Date_observation DESC, sui.id_suivi DESC) as rn
                        FROM Suivi sui
                    ) AS LatestSuivi ON S.id_sejour = LatestSuivi.id_sejour AND LatestSuivi.rn = 1
                    WHERE SRV.id_service = :id_service 
                      AND S.Date_sortiee IS NULL 
                      AND S.id_chambre IS NOT NULL 
                    ORDER BY S.Date_entree DESC";

        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bindParam(':id_service', $actual_id_service, PDO::PARAM_INT);
        
        if ($stmt2->execute()) {
            $patients = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            if (empty($patients)) {
                // Return an empty array if no patients are found, but indicate success.
                return ["message" => "No patients found for this service.", "data" => []];
            }
            return ["data" => $patients];
        } else {
            $errorInfo = $stmt2->errorInfo();
            error_log("Error fetching patients: " . implode(", ", $errorInfo));
            return ["error" => "Error fetching patients: " . implode(", ", $errorInfo), "data" => []];
        }
    }

    
    public function searchPatient($fullname) {
        $query = "SELECT Patients.*, Chambres.Numero_cr, Services.nom_service 
          FROM Patients 
          JOIN Sejour ON Sejour.id_patient = Patients.id_patient
          JOIN Chambres ON Chambres.id_chambre = Sejour.id_chambre
          JOIN Services ON Services.id_service = Chambres.id_service
          WHERE Patients.full_name LIKE :fullname
          LIMIT 1";

$stmt = $this->conn->prepare($query);

$fullname = '%' . $fullname . '%';

$stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
$stmt->execute();

return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateSuivi($data) {
    $query = "UPDATE Suivi SET 
                etat_santee = :etat_santee,
                tension = :tension,
                temperature = :temperature,
                frequence_quardiaque = :frequence_quardiaque,
                saturation_oxygene = :saturation_oxygene,
                glycemie = :glycemie,
                Remarque = :Remarque,
                Date_observation = :Date_observation
              WHERE id_suivi = :id_suivi AND id_nurse = :id_nurse";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':etat_santee', $data['etat_santee']);
    $stmt->bindParam(':tension', $data['tension']);
    $stmt->bindParam(':temperature', $data['temperature']);
    $stmt->bindParam(':frequence_quardiaque', $data['frequence_quardiaque']);
    $stmt->bindParam(':saturation_oxygene', $data['saturation_oxygene']);
    $stmt->bindParam(':glycemie', $data['glycemie']);
    $stmt->bindParam(':Remarque', $data['Remarque']);
    $stmt->bindParam(':Date_observation', $data['Date_observation']);
    $stmt->bindParam(':id_suivi', $data['id_suivi']);
    $stmt->bindParam(':id_nurse', $data['id_nurse']);

    return $stmt->execute();
}
public function createSuivi($data) {
    // Log received data for debugging
    error_log("createSuivi received data: " . print_r($data, true));

    $query = "INSERT INTO Suivi (
                id_sejour, 
                id_nurse, 
                etat_santee, 
                tension, 
                temperature, 
                frequence_quardiaque, 
                saturation_oxygene, 
                glycemie, 
                poids,
                taille,
                Remarque, 
                Date_observation
              ) VALUES (
                :id_sejour, 
                :id_nurse, 
                :etat_santee, 
                :tension, 
                :temperature, 
                :frequence_quardiaque, 
                :saturation_oxygene, 
                :glycemie, 
                :poids,
                :taille,
                :Remarque, 
                :Date_observation
              )";

    $stmt = $this->conn->prepare($query);

    if ($stmt === false) {
        error_log("Failed to prepare statement for createSuivi. SQL: " . $query . " Error: " . implode(" ", $this->conn->errorInfo()));
        return false;
    }

    // Sanitize and prepare data
    $id_sejour = htmlspecialchars(strip_tags($data['id_sejour']));
    $id_nurse = htmlspecialchars(strip_tags($data['id_nurse']));
    $etat_santee = htmlspecialchars(strip_tags($data['etat_santee']));
    $tension = htmlspecialchars(strip_tags($data['tension']));
    $temperature = htmlspecialchars(strip_tags($data['temperature']));
    $frequence_quardiaque = htmlspecialchars(strip_tags($data['frequence_quardiaque']));
    $saturation_oxygene = htmlspecialchars(strip_tags($data['saturation_oxygene']));
    $glycemie = htmlspecialchars(strip_tags($data['glycemie']));
    $poids = htmlspecialchars(strip_tags($data['poids']));
    // Assuming taille is sent in CM from frontend, convert to meters for DB (DECIMAL(4,2))
    $taille_cm = floatval($data['taille']);
    $taille_m = $taille_cm / 100.0;
    $taille_db_format = number_format($taille_m, 2, '.', '');

    $Remarque = htmlspecialchars(strip_tags($data['Remarque']));
    
    // Convert Date_observation from YYYY-MM-DDTHH:MM to YYYY-MM-DD HH:MM:SS
    $date_observation_input = $data['Date_observation'];
    try {
        $date_obj = new DateTime($date_observation_input);
        $date_observation_db_format = $date_obj->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        error_log("Invalid Date_observation format: " . $date_observation_input . " - Error: " . $e->getMessage());
        // Set to current time as a fallback or handle error appropriately
        $date_observation_db_format = date('Y-m-d H:i:s'); 
    }

    // Bind params
    $stmt->bindParam(':id_sejour', $id_sejour);
    $stmt->bindParam(':id_nurse', $id_nurse);
    $stmt->bindParam(':etat_santee', $etat_santee);
    $stmt->bindParam(':tension', $tension);
    $stmt->bindParam(':temperature', $temperature); // PDO will handle type based on column
    $stmt->bindParam(':frequence_quardiaque', $frequence_quardiaque, PDO::PARAM_INT);
    $stmt->bindParam(':saturation_oxygene', $saturation_oxygene, PDO::PARAM_INT);
    $stmt->bindParam(':glycemie', $glycemie); // PDO will handle type based on column
    $stmt->bindParam(':poids', $poids);
    $stmt->bindParam(':taille', $taille_db_format);
    $stmt->bindParam(':Remarque', $Remarque);
    $stmt->bindParam(':Date_observation', $date_observation_db_format);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Failed to execute statement for createSuivi. Error: " . implode(" ", $stmt->errorInfo()));
        error_log("Data for failed createSuivi: id_sejour={$id_sejour}, id_nurse={$id_nurse}, etat_santee={$etat_santee}, tension={$tension}, temperature={$temperature}, frequence_quardiaque={$frequence_quardiaque}, saturation_oxygene={$saturation_oxygene}, glycemie={$glycemie}, poids={$poids}, taille={$taille_db_format}, Remarque={$Remarque}, Date_observation={$date_observation_db_format}");
        return false;
    }
}
}
?>
