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

    // Get single patient by ID
    public function readOne() {
        $sql = "SELECT * FROM " . $this->table . "  JOIN Sejour on Sejour.id_patient=Patients.id_patient
                                                  JOIN Chambres on Chambres.id_chambre = Sejour.id_chambre
                                                  JOIN Services on Services.id_service = Chambres.id_service WHERE Patients.id_patient = :id OR Patients.full_name LIKE :full_name LIMIT 1;";
                                                  $stmt = $this->conn->prepare($sql);

                                                  // Bind parameters
                                                  $stmt->bindParam(':id', $this->id_patient);
                                                  $fullNameParam = '%' . $this->full_name . '%';
                                                  $stmt->bindParam(':full_name', $fullNameParam);
                                                  
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
