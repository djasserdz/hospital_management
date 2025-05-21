<?php
class Suivi{
    private $conn;
    private $table="Suivi";
    public $id_sejour;
    public $id_nurse;
    public $etat_santee;
    public $tension;
    public $temperature;
   public  $frequence_quardiaque;
    public $saturation_oxygene;
     public $glycemie;
     public $Remarque;
     public $Date_observation;

     
 public function __construct($db) {
    $this->conn = $db;
}
public function create($input) {
    $sql = "INSERT INTO " . $this->table . " 
        (id_sejour, id_nurse, etat_santee, tension, temperature, frequence_quardiaque, saturation_oxygene, glycemie, Remarque, Date_observation) 
        VALUES 
        (:id_sejour, :id_nurse, :etat_santee, :tension, :temperature, :frequence_quardiaque, :saturation_oxygene, :glycemie, :Remarque, :Date_observation)";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id_sejour', $input['id_sejour']);
    $stmt->bindParam(':id_nurse', $input['id_nurse']);
    $stmt->bindParam(':etat_santee', $input['etat_santee']);
    $stmt->bindParam(':tension', $input['tension']);
    $stmt->bindParam(':temperature', $input['temperature']);
    $stmt->bindParam(':frequence_quardiaque', $input['frequence_quardiaque']);
    $stmt->bindParam(':saturation_oxygene', $input['saturation_oxygene']);
    $stmt->bindParam(':glycemie', $input['glycemie']);
    $stmt->bindParam(':Remarque', $input['Remarque']);
    $date = $input['Date_observation'] ?? date('Y-m-d H:i:s');
    $stmt->bindParam(':Date_observation', $date);
    return $stmt->execute();
}

public function update($input) {
    
    if (!isset($input['id_suivi'])) {
        return false; 
    }

    $sql = "UPDATE " . $this->table . " SET
        id_sejour = :id_sejour,
        id_nurse = :id_nurse,
        etat_santee = :etat_santee,
        tension = :tension,
        temperature = :temperature,
        frequence_quardiaque = :frequence_quardiaque,
        saturation_oxygene = :saturation_oxygene,
        glycemie = :glycemie,
        Remarque = :Remarque,
        Date_observation = :Date_observation
        WHERE id_suivi = :id_suivi";

    $stmt = $this->conn->prepare($sql);

    // Bind input values
    $stmt->bindParam(':id_sejour', $input['id_sejour']);
    $stmt->bindParam(':id_nurse', $input['id_nurse']);
    $stmt->bindParam(':etat_santee', $input['etat_santee']);
    $stmt->bindParam(':tension', $input['tension']);
    $stmt->bindParam(':temperature', $input['temperature']);
    $stmt->bindParam(':frequence_quardiaque', $input['frequence_quardiaque']);
    $stmt->bindParam(':saturation_oxygene', $input['saturation_oxygene']);
    $stmt->bindParam(':glycemie', $input['glycemie']);
    $stmt->bindParam(':Remarque', $input['Remarque']);
    $stmt->bindParam(':Date_observation', $input['Date_observation']);
    $stmt->bindParam(':id_suivi', $input['id_suivi']);

    // Execute and return result
    return $stmt->execute();
}

}


?>