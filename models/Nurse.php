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
        $find_service="SELECT id_service FROM Users WHERE id=:id ";
        $stmt1=$this->conn->prepare($find_service);
        $stmt1->bindParam(":id",$this->id_user);

        $id_service=$stmt1->execute();

        $query = "SELECT Patients.*,Sejour.*,Chambres.*,Services.* FROM Patients
                  JOIN Sejour ON Sejour.id_patient = Patients.id_patient
                  JOIN Chambres ON Chambres.id_chambre = Sejour.id_chambre
                  JOIN Services ON Services.id_service = Chambres.id_service
                  WHERE Services.id_service = $id_service";

        $stmt = $this->conn->prepare($query);
        //$stmt->bindParam(':id_service', $this->id_service);
        $stmt->execute();

        $result=$stmt->fetchAll();

        return $result;
    }

    
    public function searchPatient($fullname) {
        $query = "SELECT Patients.*, Chambres.nom_chambre, Services.nom_service FROM Patients 
                  JOIN Sejour ON Sejour.id_patient = Patients.id_patient
                  JOIN Chambres ON Chambres.id_chambre = Sejour.id_chambre
                  JOIN Services ON Services.id_service = Chambres.id_service
                  WHERE Patients.full_name = :fullname
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fullname', $fullname);
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
