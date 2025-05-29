<?php

class Chambre {
    private $conn;
    private $table = "Chambres";

    public $id_chambre;
    public $id_service;
    public $numero_cr;
    public $numero_lit;
    public $available;

    public function __construct($db) {
        $this->conn = $db;
    }
    public function getAvailableByService() {
        $sql = "SELECT id_chambre, numero_cr, numero_lit, available
                FROM " . $this->table . " 
                WHERE available = true AND id_service = :id_service";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_service', $this->id_service);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getrooms($id_nurse){
        $sql="SELECT id_service from Users WHERE id=:id_nurse";
        $stmt=$this->conn->prepare($sql);
        $stmt->bindParam(":id_nurse", $id_nurse);
        $stmt->execute();

        $user_data = $stmt->fetch();

        if ($user_data && isset($user_data['id_service'])) {
            $this->id_service = $user_data['id_service'];
        return $this->getAvailableByService();
        } else {
            // User not found or user has no service_id, return empty array
            error_log("Nurse not found or no service ID for nurse ID: " . $id_nurse);
            return [];
        }
    }

    public function getServiceId($chambre_id) {
        try {
            $query = "SELECT id_service FROM " . $this->table . " WHERE id_chambre = :chambre_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':chambre_id', $chambre_id);
            $stmt->execute();
            $chambre = $stmt->fetch(PDO::FETCH_ASSOC);
            return $chambre ? $chambre['id_service'] : null;
        } catch (PDOException $e) {
            error_log("Error fetching chambre service ID: " . $e->getMessage());
            return null;
        }
    }

    public function updateAvailability($id_chambre, $available) {
        try {
            $query = "UPDATE " . $this->table . " SET available = :available WHERE id_chambre = :id_chambre";
            $stmt = $this->conn->prepare($query);

            // Sanitize
            $id_chambre = htmlspecialchars(strip_tags($id_chambre));
            $available = filter_var($available, FILTER_VALIDATE_BOOLEAN);

            // Bind params
            $stmt->bindParam(':available', $available, PDO::PARAM_BOOL);
            $stmt->bindParam(':id_chambre', $id_chambre);

            if ($stmt->execute()) {
                return true;
            }
            error_log("Error executing updateAvailability for chambre ID: " . $id_chambre . ". Error: " . implode(" ", $stmt->errorInfo()));
            return false;
        } catch (PDOException $e) {
            error_log("PDOException in updateAvailability for chambre ID: " . $id_chambre . ". Error: " . $e->getMessage());
            return false;
        }
    }
}
