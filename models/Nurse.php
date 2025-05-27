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

   

    public function getAllPatients() {
        // Step 1: Get the service ID for the current nurse ($this->id_user must be set prior to calling this)
        $find_service_query = "SELECT id_service FROM Users WHERE id = :id_user LIMIT 1";
        $stmt_find_service = $this->conn->prepare($find_service_query);
        
        if ($stmt_find_service === false) {
            error_log("Failed to prepare query to find nurse's service ID. SQL: " . $find_service_query . " Error: " . implode(" ", $this->conn->errorInfo()));
            return [];
        }
        $stmt_find_service->bindParam(":id_user", $this->id_user, PDO::PARAM_INT);
        
        if (!$stmt_find_service->execute()) {
            error_log("Failed to execute query to find nurse's service ID for nurse: " . $this->id_user . " Error: " . implode(" ", $stmt_find_service->errorInfo()));
            return [];
        }
        
        $nurse_data = $stmt_find_service->fetch(PDO::FETCH_ASSOC);
        
        if (!$nurse_data || !isset($nurse_data['id_service'])) {
            error_log("Nurse not found or no service ID for nurse: " . $this->id_user);
            return [];
        }
        
        $actual_id_service = $nurse_data['id_service'];

        // Step 2: Fetch patients based on the nurse's actual service ID and with active sejours, including latest etat_santee
        $query = "SELECT P.id_patient, P.full_name, P.NIN, P.age, P.sex,
                         Sj.id_sejour, Sj.Date_entree, Sj.Date_sortiee,
                         C.Numero_cr as room_number, C.id_chambre as room_id,
                         Svcs.nom_service as service_name, Svcs.id_service as service_id,
                         LatestSuivi.etat_santee
                  FROM Patients P
                  JOIN Sejour Sj ON Sj.id_patient = P.id_patient
                  JOIN Chambres C ON C.id_chambre = Sj.id_chambre
                  JOIN Services Svcs ON Svcs.id_service = C.id_service
                  LEFT JOIN Suivi LatestSuivi ON LatestSuivi.id_suivi = (
                      SELECT s_inner.id_suivi
                      FROM Suivi s_inner
                      WHERE s_inner.id_sejour = Sj.id_sejour
                      ORDER BY s_inner.Date_observation DESC, s_inner.id_suivi DESC
                      LIMIT 1
                  )
                  WHERE Svcs.id_service = :actual_id_service AND Sj.Date_sortiee IS NULL";

        $stmt_patients = $this->conn->prepare($query);

        if ($stmt_patients === false) {
            error_log("Failed to prepare query to fetch patients. SQL: " . $query . " Error: " . implode(" ", $this->conn->errorInfo()));
            return [];
        }

        $stmt_patients->bindParam(':actual_id_service', $actual_id_service, PDO::PARAM_INT);
        
        if (!$stmt_patients->execute()) {
            error_log("Failed to execute query to fetch patients for service ID: " . $actual_id_service . " Error: " . implode(" ", $stmt_patients->errorInfo()));
            return [];
        }

        return $stmt_patients->fetchAll(PDO::FETCH_ASSOC);
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
