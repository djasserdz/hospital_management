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
        $stmt_find_service->bindParam(":id_user", $this->id_user, PDO::PARAM_INT);
        
        if (!$stmt_find_service->execute()) {
            error_log("Failed to execute query to find nurse's service ID for nurse: " . $this->id_user);
            return []; // Or handle error appropriately
        }
        
        $nurse_data = $stmt_find_service->fetch(PDO::FETCH_ASSOC);
        
        if (!$nurse_data || !isset($nurse_data['id_service'])) {
            error_log("Nurse not found or no service ID for nurse: " . $this->id_user);
            return []; // Or handle error appropriately
        }
        
        $actual_id_service = $nurse_data['id_service'];

        // Step 2: Fetch patients based on the nurse's actual service ID
        $query = "SELECT Patients.id_patient, Patients.full_name, Patients.NIN, Patients.age, Patients.sex, 
                         Sejour.id_sejour, Sejour.Date_entree, Sejour.Date_sortiee,
                         Chambres.Numero_cr as room_number, Chambres.id_chambre as room_id,
                         Services.nom_service as service_name, Services.id_service as service_id
                  FROM Patients
                  JOIN Sejour ON Sejour.id_patient = Patients.id_patient
                  JOIN Chambres ON Chambres.id_chambre = Sejour.id_chambre
                  JOIN Services ON Services.id_service = Chambres.id_service
                  WHERE Services.id_service = :actual_id_service";

        $stmt_patients = $this->conn->prepare($query);
        $stmt_patients->bindParam(':actual_id_service', $actual_id_service, PDO::PARAM_INT);
        
        if (!$stmt_patients->execute()) {
            error_log("Failed to execute query to fetch patients for service ID: " . $actual_id_service);
            return []; // Or handle error appropriately
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
    $query = "INSERT INTO Suivi (
                id_patient, 
                id_nurse, 
                etat_santee, 
                tension, 
                temperature, 
                frequence_quardiaque, 
                saturation_oxygene, 
                glycemie, 
                Remarque, 
                Date_observation
              ) VALUES (
                :id_patient, 
                :id_nurse, 
                :etat_santee, 
                :tension, 
                :temperature, 
                :frequence_quardiaque, 
                :saturation_oxygene, 
                :glycemie, 
                :Remarque, 
                :Date_observation
              )";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':id_patient', $data['id_patient']);
    $stmt->bindParam(':id_nurse', $data['id_nurse']);
    $stmt->bindParam(':etat_santee', $data['etat_santee']);
    $stmt->bindParam(':tension', $data['tension']);
    $stmt->bindParam(':temperature', $data['temperature']);
    $stmt->bindParam(':frequence_quardiaque', $data['frequence_quardiaque']);
    $stmt->bindParam(':saturation_oxygene', $data['saturation_oxygene']);
    $stmt->bindParam(':glycemie', $data['glycemie']);
    $stmt->bindParam(':Remarque', $data['Remarque']);
    $stmt->bindParam(':Date_observation', $data['Date_observation']);

    return $stmt->execute();
              } 
}
?>
