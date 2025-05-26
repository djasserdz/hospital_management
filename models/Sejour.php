<?php
class Sejour {
    private $conn;
    private $table_name = "Sejour";

    public $id_sejour;
    public $id_patient;
    public $id_chambre;
    public $Date_entree;
    public $Date_sortiee;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getCurrentChambreId($id_sejour) {
        try {
            $query = "SELECT id_chambre FROM " . $this->table_name . " WHERE id_sejour = :id_sejour LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_sejour', $id_sejour);
            $stmt->execute();
            $sejour = $stmt->fetch(PDO::FETCH_ASSOC);
            return $sejour ? $sejour['id_chambre'] : null;
        } catch (PDOException $e) {
            error_log("Error fetching current chambre ID for sejour: " . $e->getMessage());
            return null;
        }
    }

    // Update the room for a sejour
    public function updateRoom($id_sejour, $new_id_chambre, $old_id_chambre = null) {
        // Sanitize inputs
        $id_sejour = htmlspecialchars(strip_tags($id_sejour));
        $new_id_chambre = htmlspecialchars(strip_tags($new_id_chambre));
        if ($old_id_chambre !== null) {
            $old_id_chambre = htmlspecialchars(strip_tags($old_id_chambre));
        }

        try {
            $this->conn->beginTransaction();

            // 1. Make the old room available (if it exists and is different from the new one)
            if ($old_id_chambre !== null && $old_id_chambre != $new_id_chambre) {
                $query_update_old_chambre = "UPDATE Chambres SET Available = true WHERE id_chambre = :old_id_chambre";
                $stmt_update_old_chambre = $this->conn->prepare($query_update_old_chambre);
                $stmt_update_old_chambre->bindParam(':old_id_chambre', $old_id_chambre);
                if (!$stmt_update_old_chambre->execute()) {
                    $this->conn->rollBack();
                    error_log("Failed to make old room available. Old Room ID: " . $old_id_chambre);
                    return false;
                }
            }

            // 2. Update Sejour with the new room
            $query_update_sejour = "UPDATE " . $this->table_name . "
                                    SET id_chambre = :new_id_chambre
                                    WHERE id_sejour = :id_sejour";
            $stmt_update_sejour = $this->conn->prepare($query_update_sejour);
            $stmt_update_sejour->bindParam(':new_id_chambre', $new_id_chambre);
            $stmt_update_sejour->bindParam(':id_sejour', $id_sejour);

            if (!$stmt_update_sejour->execute()) {
                $this->conn->rollBack();
                error_log("Failed to update sejour room. Sejour ID: " . $id_sejour . ", New Room ID: " . $new_id_chambre);
                return false;
            }

            // 3. Make the new room unavailable (if it's not null)
            if ($new_id_chambre !== null) {
                $query_update_new_chambre = "UPDATE Chambres SET Available = false WHERE id_chambre = :new_id_chambre";
                $stmt_update_new_chambre = $this->conn->prepare($query_update_new_chambre);
                $stmt_update_new_chambre->bindParam(':new_id_chambre', $new_id_chambre);
                if (!$stmt_update_new_chambre->execute()) {
                    $this->conn->rollBack();
                    error_log("Failed to make new room unavailable. New Room ID: " . $new_id_chambre);
                    return false;
                }
            }
            
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Database error in updateRoom: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($id_sejour, $date_sortie) {
        // Sanitize inputs
        $id_sejour = htmlspecialchars(strip_tags($id_sejour));
        $date_sortie = htmlspecialchars(strip_tags($date_sortie));

        try {
            $this->conn->beginTransaction();

            // 1. Get the current room ID for the sejour
            $current_chambre_id = $this->getCurrentChambreId($id_sejour);

            // 2. Update Sejour with the discharge date
            $query_update_sejour = "UPDATE " . $this->table_name . "
                                    SET Date_sortiee = :date_sortie
                                    WHERE id_sejour = :id_sejour";
            $stmt_update_sejour = $this->conn->prepare($query_update_sejour);
            $stmt_update_sejour->bindParam(':date_sortie', $date_sortie);
            $stmt_update_sejour->bindParam(':id_sejour', $id_sejour);

            if (!$stmt_update_sejour->execute()) {
                $this->conn->rollBack();
                error_log("Failed to update sejour status (Date_sortiee). Sejour ID: " . $id_sejour);
                return false;
            }

            // 3. Make the room available if a room was associated
            if ($current_chambre_id) {
                // We need an instance of Chambre model. Assuming it's available or can be instantiated.
                // For simplicity, let's assume a global $db or pass $this->conn to a new Chambre instance.
                // This part might need adjustment based on how Chambre model is typically accessed.
                $chambre_model = new Chambre($this->conn); 
                if (!$chambre_model->updateAvailability($current_chambre_id, true)) {
                    $this->conn->rollBack();
                    error_log("Failed to make room available when updating sejour status. Chambre ID: " . $current_chambre_id);
                    return false;
                }
            }
            
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Database error in updateStatus for sejour: " . $e->getMessage());
            return false;
        }
    }
}
?> 