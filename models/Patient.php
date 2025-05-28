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
                    (full_name, NIN, age, sex, adress, telephone, groupage) 
                    VALUES (:full_name, :NIN, :age, :sex, :adress, :telephone, :groupage)";
            
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bindParam(':full_name', $this->full_name);
            $stmt1->bindParam(':NIN', $this->NIN);
            $stmt1->bindParam(':age', $this->age);
            $stmt1->bindParam(':sex', $this->sex);
            $stmt1->bindParam(':adress', $this->adress);
            $stmt1->bindParam(':telephone', $this->telephone);
            $stmt1->bindParam(':groupage', $this->groupage);

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
        // Query to get patient details and their LATEST sejour information
        $sql = "SELECT 
                    p.*, 
                    s.id_sejour, s.Date_entree, s.Date_sortiee, s.id_chambre,
                    ch.numero_cr as room_number,
                    srv.nom_service as service_name, srv.id_service as service_id_for_room
                FROM " . $this->table . " p
                LEFT JOIN Sejour s ON p.id_patient = s.id_patient
                LEFT JOIN (
                    SELECT id_patient, MAX(id_sejour) as max_sejour_id
                    FROM Sejour
                    WHERE id_patient = :id
                    GROUP BY id_patient
                ) latest_s ON p.id_patient = latest_s.id_patient AND s.id_sejour = latest_s.max_sejour_id
                LEFT JOIN Chambres ch ON s.id_chambre = ch.id_chambre
                LEFT JOIN Services srv ON ch.id_service = srv.id_service
                WHERE p.id_patient = :id
                LIMIT 1"; // LIMIT 1 is okay here as we are fetching one patient based on p.id_patient
    
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $this->id_patient, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $patient_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($patient_data) {
                // Convert birth_date to YYYY-MM-DD if it's stored as DATETIME or other format
                if (isset($patient_data['birth_date']) && $patient_data['birth_date']) {
                     try {
                        $birth_date_obj = new DateTime($patient_data['birth_date']);
                        $patient_data['birth_date'] = $birth_date_obj->format('Y-m-d');
                    } catch (Exception $e) {
                        // Log error or handle, for now, leave as is if conversion fails
                        error_log("Error converting birth_date in readOne: " . $e->getMessage());
                    }
                }
                // Convert Date_entree similarly if needed by the frontend for the update modal
                if (isset($patient_data['Date_entree']) && $patient_data['Date_entree']) {
                     try {
                        $date_entree_obj = new DateTime($patient_data['Date_entree']);
                        $patient_data['Date_entree'] = $date_entree_obj->format('Y-m-d');
                    } catch (Exception $e) {
                        error_log("Error converting Date_entree in readOne: " . $e->getMessage());
                    }
                }
            }
            return $patient_data;
        } else {
            error_log("Error in Patient::readOne() execution: " . implode(", ", $stmt->errorInfo()));
            return null; // Or handle error as appropriate
        }
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
            
            $stmt->execute();
            
            if (!empty($id_chambre)) {
                $sql_current = "SELECT id_sejour, id_chambre FROM Sejour 
                               WHERE id_patient = :id_patient AND Date_sortiee IS NULL
                               ORDER BY Date_entree DESC LIMIT 1";
                
                $stmt_current = $this->conn->prepare($sql_current);
                $stmt_current->bindParam(':id_patient', $this->id_patient);
                $stmt_current->execute();
                $current_sejour = $stmt_current->fetch(PDO::FETCH_ASSOC);
                
                if ($current_sejour) {
                    $old_chambre_id = $current_sejour['id_chambre'];
                    $sejour_id = $current_sejour['id_sejour'];
                    
                    if (!empty($old_chambre_id)) {
                        $sql_old_room = "UPDATE Chambres SET Available = true WHERE id_chambre = :old_chambre_id";
                        $stmt_old_room = $this->conn->prepare($sql_old_room);
                        $stmt_old_room->bindParam(':old_chambre_id', $old_chambre_id);
                        $stmt_old_room->execute();
                    }
                    
                    $sql_check = "SELECT Available FROM Chambres WHERE id_chambre = :new_chambre_id";
                    $stmt_check = $this->conn->prepare($sql_check);
                    $stmt_check->bindParam(':new_chambre_id', $id_chambre);
                    $stmt_check->execute();
                    $room_status = $stmt_check->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$room_status || !$room_status['Available']) {
                        throw new Exception("Room is not available");
                    }
                    
                    $sql_sejour = "UPDATE Sejour SET id_chambre = :new_chambre_id WHERE id_sejour = :sejour_id";
                    $stmt_sejour = $this->conn->prepare($sql_sejour);
                    $stmt_sejour->bindParam(':new_chambre_id', $id_chambre);
                    $stmt_sejour->bindParam(':sejour_id', $sejour_id);
                    $stmt_sejour->execute();
                    
                    $sql_new_room = "UPDATE Chambres SET Available = false WHERE id_chambre = :new_chambre_id";
                    $stmt_new_room = $this->conn->prepare($sql_new_room);
                    $stmt_new_room->bindParam(':new_chambre_id', $id_chambre);
                    $stmt_new_room->execute();
                } else {
                    if (!empty($admission_date)) {
                        $sql_check = "SELECT Available FROM Chambres WHERE id_chambre = :new_chambre_id";
                        $stmt_check = $this->conn->prepare($sql_check);
                        $stmt_check->bindParam(':new_chambre_id', $id_chambre);
                        $stmt_check->execute();
                        $room_status = $stmt_check->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$room_status || !$room_status['Available']) {
                            throw new Exception("Room is not available");
                        }
                        
                        $sql_new_sejour = "INSERT INTO Sejour (id_patient, id_chambre, Date_entree) 
                                          VALUES (:id_patient, :id_chambre, :admission_date)";
                        $stmt_new_sejour = $this->conn->prepare($sql_new_sejour);
                        $stmt_new_sejour->bindParam(':id_patient', $this->id_patient);
                        $stmt_new_sejour->bindParam(':id_chambre', $id_chambre);
                        $stmt_new_sejour->bindParam(':admission_date', $admission_date);
                        $stmt_new_sejour->execute();
                        
                        $sql_new_room = "UPDATE Chambres SET Available = false WHERE id_chambre = :new_chambre_id";
                        $stmt_new_room = $this->conn->prepare($sql_new_room);
                        $stmt_new_room->bindParam(':new_chambre_id', $id_chambre);
                        $stmt_new_room->execute();
                    } else {
                        throw new Exception("No active admission found and no admission date provided");
                    }
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

    public function getDetails() {
               $query = "SELECT 
                    Patients.*, 
                    Suivi.*
                  FROM Patients 
                  JOIN Sejour ON Sejour.id_patient = Patients.id_patient 
                  JOIN Suivi ON Suivi.id_sejour = Sejour.id_sejour
                  WHERE Patients.id_patient = :id_patient";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_patient", $this->id_patient);
        $stmt->execute();
    
        return $stmt->fetch(PDO::FETCH_ASSOC); // if expecting single result
        // OR use fetchAll() if expecting multiple observations
    }

    public function getAllPatientsForAdmin() {
        // This query aims to get all patients and their latest sejour/room/service information if available.
        $query = "SELECT 
                    p.id_patient, p.full_name, p.NIN, p.age, p.sex, p.adress, p.telephone, p.groupage,
                    ls.id_sejour, ls.Date_entree, ls.Date_sortiee,
                    ch.Numero_cr as room_number, ch.id_chambre as room_id,
                    srv.nom_service as service_name, srv.id_service as service_id
                  FROM " . $this->table . " p
                  LEFT JOIN (
                      SELECT s_inner.*  -- Select all columns from the latest sejour
                      FROM Sejour s_inner
                      INNER JOIN (
                          SELECT id_patient, MAX(id_sejour) AS max_sejour_id
                          FROM Sejour
                          GROUP BY id_patient
                      ) latest_sejour_ids ON s_inner.id_patient = latest_sejour_ids.id_patient AND s_inner.id_sejour = latest_sejour_ids.max_sejour_id
                  ) ls ON p.id_patient = ls.id_patient
                  LEFT JOIN Chambres ch ON ls.id_chambre = ch.id_chambre
                  LEFT JOIN Services srv ON ch.id_service = srv.id_service
                  ORDER BY p.id_patient ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Patient::getAllPatientsForAdmin(): " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
    
}

?>
