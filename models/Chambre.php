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
        $sql = "SELECT id_chambre, numero_cr, numero_lit 
                FROM " . $this->table . " 
                WHERE available = TRUE AND id_service = :id_service";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_service', $this->id_service);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
