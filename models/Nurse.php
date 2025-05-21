<?php
class Nurse{
 private $conn;
 private $table="Users";
 public $id_service;
 public $full_name;
 public $email;
 public $password;
 public final $role="nurse";

 public function __construct($db) {
    $this->conn = $db;
}
public function getAllPatients()
{
    include 'Patient.php';
    $db=new Database();
    $patients=new Patient($db);
    $patients->readAll();
}
public function searchPatient($request){

}
}

?>