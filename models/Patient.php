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

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $sql = "INSERT INTO " . $this->table . " 
                (full_name, age, sex, adress, telephone, groupage) 
                VALUES (:full_name,:NIN, :age, :sex, :adress, :telephone, :groupage)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':NIN',$this->NIN);
        $stmt->bindParam(':age', $this->age);
        $stmt->bindParam(':sex', $this->sex);
        $stmt->bindParam(':adress', $this->adress);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':groupage', $this->groupage);

        return $stmt->execute();
    }

    // Get all patients
    public function readAll() {
        $sql = "SELECT Patients.id_patient,Patients.full_name,Patients.sex,Services.nom_service FROM ". $this->table . " JOIN Sejour on Sejour.id_patient=Patients.id_patient
                                                  JOIN Chambres on Chambres.id_chambre = Sejour.id_chambre
                                                  JOIN Services on Services.id_service = Chambres.id_service;";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne() {
        $sql = "SELECT * FROM " . $this->table . " 
                JOIN Sejour ON Sejour.id_patient = Patients.id_patient
                JOIN Chambres ON Chambres.id_chambre = Sejour.id_chambre
                JOIN Services ON Services.id_service = Chambres.id_service
                WHERE (:id IS NOT NULL AND Patients.id_patient = :id)
                   OR (:full_name IS NOT NULL AND Patients.full_name LIKE :full_name)
                LIMIT 1";
    
        $stmt = $this->conn->prepare($sql);
    
        $id = !empty($this->id_patient) ? $this->id_patient : null;
        $stmt->bindParam(':id', $id);
    
        $full_name = !empty($this->full_name) ? '%' . $this->full_name . '%' : null;
        $stmt->bindParam(':full_name', $full_name);
    
        $stmt->execute();
    
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }
    

    // Update patient
    public function update() {
        $sql = "UPDATE " . $this->table . " 
                SET full_name = :full_name, age = :age, sex = :sex, 
                    adress = :adress, telephone = :telephone, groupage = :groupage 
                WHERE id_patient = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':age', $this->age);
        $stmt->bindParam(':sex', $this->sex);
        $stmt->bindParam(':adress', $this->adress);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':groupage', $this->groupage);
        $stmt->bindParam(':id', $this->id_patient);

        return $stmt->execute();
    }
}
