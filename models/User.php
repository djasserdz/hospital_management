<?php

class User {
    private $conn;
    private $table = "Users";

    public $id_service;
    public $full_name;
    public $email;
    public $password;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function Create() {
        try {
            $sql_insert = "INSERT INTO " . $this->table . " 
                (id_service, full_name, email, password, role) 
                VALUES (:id_service, :full_name, :email, :password, :role)";
            
            $stmt = $this->conn->prepare($sql_insert);
            $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

            $stmt->bindParam(':id_service', $this->id_service);
            $stmt->bindParam(':full_name', $this->full_name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role', $this->role);

            if ($stmt->execute()) {
                return true;
            }

            return false;
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode([
                'message' => "Error happened: " . $e->getMessage()
            ]);
            return false;
        }
    }

    public function login() {
        try {
            $sql_query_login = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
            $stmt=$this->conn->prepare($sql_query_login);
            $stmt->bindParam(':email', $this->email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($this->password, $user['password'])) {
                unset($user['password']);
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode([
                'message' => "Error happened: " . $e->getMessage()
            ]);
            return false;
        }
    }
}
