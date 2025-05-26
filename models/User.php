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

            // Handle id_service based on role
            if (strtolower($this->role) === 'admin') {
                // For admin, id_service can be NULL. If not explicitly provided, bind as NULL.
                $id_service_to_bind = isset($this->id_service) ? $this->id_service : null;
                if ($id_service_to_bind === null) {
                    $stmt->bindValue(':id_service', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':id_service', $id_service_to_bind, PDO::PARAM_INT);
                }
            } else if (strtolower($this->role) === 'nurse') {
                // For nurse, id_service is mandatory
                if (empty($this->id_service)) {
                    throw new PDOException("id_service is required for nurses.");
                }
                $stmt->bindParam(':id_service', $this->id_service, PDO::PARAM_INT);
            } else {
                // Handle other roles or throw error if role is not recognized
                throw new PDOException("Unrecognized user role for id_service handling.");
            }

            $stmt->bindParam(':full_name', $this->full_name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':role', $this->role);

            if ($stmt->execute()) {
                return true;
            }

            return false;
        } catch (PDOException $e) {
            // Consider a more specific HTTP response code if it's a validation error (e.g., 422) vs. general 400
            http_response_code( (strpos($e->getMessage(), "required for nurses") !== false) ? 422 : 400 );
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

    public function getServiceId($user_id) {
        try {
            $query = "SELECT id_service FROM " . $this->table . " WHERE id = :user_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $user['id_service'] : null;
        } catch (PDOException $e) {
            // Log error or handle appropriately
            error_log("Error fetching user service ID: " . $e->getMessage());
            return null;
        }
    }
}
