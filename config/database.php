<?php 

class Database{

   private $server="localhost";
   private $user="root";
   private $password="159753";
   private $dbname="hospital_management";

   private $conn;

   public function __construct(){
     try{
         $this->conn=new PDO("mysql:host=".$this->server.";dbname=".$this->dbname ."",$this->user,$this->password);
         $this->conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
         $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
         http_response_code(200);
         $server_response_good=array(
            'message'=>"Connected to database",
         );

         //echo json_encode($server_response_good);
     }catch(PDOException $e){
         http_response_code(500);
         $server_error=array(
            'message'=>'Error happend :'.$e->getMessage(),
         );

         //echo json_encode($server_error);
     }
   }

   public function getConnection() {
      return $this->conn;
  }
}
