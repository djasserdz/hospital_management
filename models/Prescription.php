<?php
class Prescription {
    private $conn;
    private $table = "Prescription";

    public $id_prescription;
    public $id_sejour;
    public $Medicament;
    public $Dosage;
    public $frequence;
    public $instructions;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($input) {
        $sql = "INSERT INTO " . $this->table . " (id_sejour, Medicament, Dosage, frequence, instructions) VALUES (:id_sejour, :Medicament, :Dosage, :frequence, :instructions)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sejour', $input['id_sejour']);
        $stmt->bindParam(':Medicament', $input['Medicament']);
        $stmt->bindParam(':Dosage', $input['Dosage']);
        $stmt->bindParam(':frequence', $input['frequence']);
        $stmt->bindParam(':instructions', $input['instructions']);
        return $stmt->execute();
    }

    public function readAllBySejour($id_sejour) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id_sejour = :id_sejour";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sejour', $id_sejour);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne($id_prescription) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id_prescription = :id_prescription";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_prescription', $id_prescription);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($input) {
        $sql = "UPDATE " . $this->table . " SET Medicament = :Medicament, Dosage = :Dosage, frequence = :frequence, instructions = :instructions WHERE id_prescription = :id_prescription";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':Medicament', $input['Medicament']);
        $stmt->bindParam(':Dosage', $input['Dosage']);
        $stmt->bindParam(':frequence', $input['frequence']);
        $stmt->bindParam(':instructions', $input['instructions']);
        $stmt->bindParam(':id_prescription', $input['id_prescription']);
        return $stmt->execute();
    }

    public function delete($id_prescription) {
        $sql = "DELETE FROM " . $this->table . " WHERE id_prescription = :id_prescription";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_prescription', $id_prescription);
        return $stmt->execute();
    }
}