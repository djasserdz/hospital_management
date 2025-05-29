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
     public $poids;
     public $taille;
     public $Remarque;
     public $Date_observation;

     
 public function __construct($db) {
    $this->conn = $db;
}
public function create($input) {
    // Log received data for debugging
    error_log("models/Suivi.php->create() received data: " . print_r($input, true));

    $query = "INSERT INTO " . $this->table . " (
                id_sejour, 
                id_nurse, 
                etat_santee, 
                tension, 
                temperature, 
                frequence_quardiaque, 
                saturation_oxygene, 
                glycemie, 
                poids,
                taille,
                Remarque, 
                Date_observation
              ) VALUES (
                :id_sejour, 
                :id_nurse, 
                :etat_santee, 
                :tension, 
                :temperature, 
                :frequence_quardiaque, 
                :saturation_oxygene, 
                :glycemie, 
                :poids,
                :taille,
                :Remarque, 
                :Date_observation
              )";

    $stmt = $this->conn->prepare($query);

    if ($stmt === false) {
        error_log("Failed to prepare statement for Suivi create. SQL: " . $query . " Error: " . implode(" ", $this->conn->errorInfo()));
        return false;
    }

    // Sanitize and prepare data from $input array
    $id_sejour = isset($input['id_sejour']) ? htmlspecialchars(strip_tags($input['id_sejour'])) : null;
    $id_nurse = isset($input['id_nurse']) ? htmlspecialchars(strip_tags($input['id_nurse'])) : null;
    $etat_santee = isset($input['etat_santee']) ? htmlspecialchars(strip_tags($input['etat_santee'])) : null;
    $tension = isset($input['tension']) ? htmlspecialchars(strip_tags($input['tension'])) : null;
    $temperature = isset($input['temperature']) ? htmlspecialchars(strip_tags($input['temperature'])) : null;
    $frequence_quardiaque = isset($input['frequence_quardiaque']) ? htmlspecialchars(strip_tags($input['frequence_quardiaque'])) : null;
    $saturation_oxygene = isset($input['saturation_oxygene']) ? htmlspecialchars(strip_tags($input['saturation_oxygene'])) : null;
    $glycemie = isset($input['glycemie']) ? htmlspecialchars(strip_tags($input['glycemie'])) : null;
    $poids = isset($input['poids']) ? htmlspecialchars(strip_tags($input['poids'])) : null;
    
    $taille_db_format = "0.00"; // Default to "0.00"
    if (isset($input['taille'])) {
        $taille_input = trim($input['taille']);
        if (!empty($taille_input)) {
            $taille_cm = floatval($taille_input);
            if ($taille_cm > 0) { // Ensure taille is positive before converting
                $taille_m = $taille_cm / 100.0;
                $taille_db_format = number_format($taille_m, 2, '.', '');
            }
            // If $taille_cm is 0 or negative, it will remain "0.00" due to the default
        }
    }

    $Remarque = isset($input['Remarque']) ? htmlspecialchars(strip_tags($input['Remarque'])) : null;
    
    $date_observation_db_format = date('Y-m-d H:i:s'); // Default to now
    if (isset($input['Date_observation']) && !empty($input['Date_observation'])) {
        $date_observation_input = $input['Date_observation'];
        try {
            $date_obj = new DateTime($date_observation_input);
            $date_observation_db_format = $date_obj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("Invalid Date_observation format in Suivi create: " . $date_observation_input . " - Error: " . $e->getMessage());
            // Keep default (now) or handle error as appropriate
        }
    }

    // Bind params
    $stmt->bindParam(':id_sejour', $id_sejour);
    $stmt->bindParam(':id_nurse', $id_nurse);
    $stmt->bindParam(':etat_santee', $etat_santee);
    $stmt->bindParam(':tension', $tension);
    $stmt->bindParam(':temperature', $temperature); 
    $stmt->bindParam(':frequence_quardiaque', $frequence_quardiaque, PDO::PARAM_INT);
    $stmt->bindParam(':saturation_oxygene', $saturation_oxygene, PDO::PARAM_INT);
    $stmt->bindParam(':glycemie', $glycemie); 
    $stmt->bindParam(':poids', $poids);
    $stmt->bindParam(':taille', $taille_db_format, PDO::PARAM_STR);
    $stmt->bindParam(':Remarque', $Remarque);
    $stmt->bindParam(':Date_observation', $date_observation_db_format);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Failed to execute statement for Suivi create. Error: " . implode(" ", $stmt->errorInfo()));
        error_log("Data for failed Suivi create: id_sejour={$id_sejour}, id_nurse={$id_nurse}, etat_santee={$etat_santee}, ..."); // Log key data
        return false;
    }
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
        poids = :poids,
        taille = :taille,
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
    $stmt->bindParam(':poids', $input['poids']);
    $stmt->bindParam(':taille', $input['taille']);
    $stmt->bindParam(':Remarque', $input['Remarque']);
    $stmt->bindParam(':Date_observation', $input['Date_observation']);
    $stmt->bindParam(':id_suivi', $input['id_suivi']);

    // Execute and return result
    return $stmt->execute();
}

}


?>