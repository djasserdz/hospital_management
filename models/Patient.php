<?php

class Patient {
    private $conn;
    private $table = "Patients";

    public $id_patient;
    public $full_name;
    public $NIN;
    public $age;
    public $sex;
    public $adress;  
    public $telephone;
    public $groupage;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($id_chambre, $admission_date) {
        try {
            $this->conn->beginTransaction();

            $sql1 = "INSERT INTO " . $this->table . " 
                    (full_name, NIN, age, sex, adress, telephone, groupage, created_at) 
                    VALUES (:full_name, :NIN, :age, :sex, :adress, :telephone, :groupage, :created_at)";
            
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bindParam(':full_name', $this->full_name);
            $stmt1->bindParam(':NIN', $this->NIN);
            $stmt1->bindParam(':age', $this->age);
            $stmt1->bindParam(':sex', $this->sex);
            $stmt1->bindParam(':adress', $this->adress);
            $stmt1->bindParam(':telephone', $this->telephone);
            $stmt1->bindParam(':groupage', $this->groupage);
            $createdAt = $this->created_at ?? date('Y-m-d H:i:s');
            $stmt1->bindParam(':created_at', $createdAt);

            if (!$stmt1->execute()) {
                throw new Exception("Failed to insert patient data");
            }

            $this->id_patient = $this->conn->lastInsertId();

            if (!$this->isRoomAvailable($id_chambre)) {
                throw new Exception("Room {$id_chambre} is not available");
            }

            $sql2 = "INSERT INTO Sejour (id_patient, id_chambre, Date_entree) 
                     VALUES (:id_patient, :id_chambre, :Date_entree)";
            
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bindParam(':id_patient', $this->id_patient);
            $stmt2->bindParam(':id_chambre', $id_chambre);
            $stmt2->bindParam(':Date_entree', $admission_date);

            if (!$stmt2->execute()) {
                throw new Exception("Failed to create sejour record");
            }

            if (!$this->occupyRoom($id_chambre)) {
                throw new Exception("Failed to update room availability");
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in Patient::create(): " . $e->getMessage());
            return false;
        }
    }

    public function readAll() {
        $sql = "
            SELECT 
                Patients.id_patient,
                Patients.full_name,
                Patients.sex,
                Services.nom_service
            FROM " . $this->table . "
            JOIN Sejour ON Sejour.id_patient = Patients.id_patient
            JOIN Chambres ON Chambres.id_chambre = Sejour.id_chambre
            JOIN Services ON Services.id_service = Chambres.id_service
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne() {
        $sql = "
            SELECT *
            FROM " . $this->table . " 
            JOIN Sejour ON Sejour.id_patient = Patients.id_patient
            JOIN Chambres ON Chambres.id_chambre = Sejour.id_chambre
            JOIN Services ON Services.id_service = Chambres.id_service
            WHERE Patients.id_patient = :id
            LIMIT 1
        ";
    
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id_patient);
        $stmt->execute();
    
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id_chambre = null, $admission_date = null) {
        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE " . $this->table . " 
                    SET full_name = :full_name, NIN = :NIN, age = :age, sex = :sex, 
                        adress = :adress, telephone = :telephone, groupage = :groupage 
                    WHERE id_patient = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':full_name', $this->full_name);
            $stmt->bindParam(':NIN', $this->NIN);
            $stmt->bindParam(':age', $this->age);
            $stmt->bindParam(':sex', $this->sex);
            $stmt->bindParam(':adress', $this->adress);
            $stmt->bindParam(':telephone', $this->telephone);
            $stmt->bindParam(':groupage', $this->groupage);
            $stmt->bindParam(':id', $this->id_patient);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update patient information");
            }

            if ($id_chambre !== null || $admission_date !== null) {
                $currentSejour = $this->getCurrentSejour();

                if ($id_chambre !== null && $currentSejour && $currentSejour['id_chambre'] != $id_chambre) {
                    if (!$this->isRoomAvailable($id_chambre)) {
                        throw new Exception("Room {$id_chambre} is not available");
                    }
                    $this->freeRoom($currentSejour['id_chambre']);
                }

                if ($currentSejour) {
                    $sqlSejour = "UPDATE Sejour 
                                  SET id_chambre = :id_chambre,
                                      Date_entree = :Date_entree
                                  WHERE id_patient = :id_patient AND Date_sortie IS NULL";
                } else {
                    $sqlSejour = "INSERT INTO Sejour (id_patient, id_chambre, Date_entree)
                                  VALUES (:id_patient, :id_chambre, :Date_entree)";
                }

                $stmtSejour = $this->conn->prepare($sqlSejour);
                $stmtSejour->bindParam(':id_patient', $this->id_patient);

                $bindRoom = $id_chambre ?? ($currentSejour['id_chambre'] ?? null);
                $stmtSejour->bindParam(':id_chambre', $bindRoom);

                $bindDate = $admission_date ?? ($currentSejour['Date_entree'] ?? date('Y-m-d'));
                $stmtSejour->bindParam(':Date_entree', $bindDate);

                if (!$stmtSejour->execute()) {
                    throw new Exception("Failed to update sejour record");
                }

                if ($id_chambre !== null) {
                    $this->occupyRoom($id_chambre);
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in Patient::update(): " . $e->getMessage());
            return false;
        }
    }

    public function discharge($discharge_date = null) {
        try {
            $this->conn->beginTransaction();

            $currentSejour = $this->getCurrentSejour();
            if (!$currentSejour) {
                throw new Exception("No active sejour found for this patient");
            }

            $dischargeDate = $discharge_date ?? date('Y-m-d');

            $sql = "UPDATE Sejour SET Date_sortie = :Date_sortie WHERE id_patient = :id_patient AND Date_sortie IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':Date_sortie', $dischargeDate);
            $stmt->bindParam(':id_patient', $this->id_patient);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update discharge date");
            }

            if (!$this->freeRoom($currentSejour['id_chambre'])) {
                throw new Exception("Failed to free room");
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in Patient::discharge(): " . $e->getMessage());
            return false;
        }
    }

    private function getCurrentSejour() {
        $sql = "SELECT * FROM Sejour WHERE id_patient = :id_patient AND Date_sortie IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_patient', $this->id_patient);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function isRoomAvailable($id_chambre) {
        $sql = "SELECT Available FROM Chambres WHERE id_chambre = :id_chambre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_chambre', $id_chambre);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['Available'] == 1;
    }

    private function occupyRoom($id_chambre) {
        $sql = "UPDATE Chambres SET Available = 0 WHERE id_chambre = :id_chambre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_chambre', $id_chambre);
        return $stmt->execute();
    }

    private function freeRoom($id_chambre) {
        $sql = "UPDATE Chambres SET Available = 1 WHERE id_chambre = :id_chambre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_chambre', $id_chambre);
        return $stmt->execute();
    }
}

?>
