<?php
class Nurse{
 private $conn;
 private $table="Users";
 public $id_service;
 public $full_name;
 public $email;
 public $password;
 public $role="nurse";

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
public function searchPatient($fullname) {
    include_once 'Patient.php';
     $db =new Database();
    $patient = new Patient($db); 
    if ($patient->readOne($fullname)) {
        
        return [
            'id_patient' => $patient->id_patient,
            'full_name' => $patient->full_name,
            'age' => $patient->age,
            'sex' => $patient->sex,
            'adress' => $patient->adress,
            'telephone' => $patient->telephone,
            'groupage' => $patient->groupage
        ];
    } else {
        return false;
    }
}


}

?>