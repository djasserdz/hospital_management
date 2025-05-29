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

    public function dischargePatient($id_sejour, $date_sortie, $id_chambre_to_vacate) {
        // Sanitize inputs
        $id_sejour = htmlspecialchars(strip_tags($id_sejour));
        $date_sortie_sanitized = htmlspecialchars(strip_tags($date_sortie)); 
        $id_chambre_to_vacate = htmlspecialchars(strip_tags($id_chambre_to_vacate));

        if (empty($date_sortie_sanitized)) {
            error_log("dischargePatient: date_sortie is required. Sejour ID: " . $id_sejour);
            return ['success' => false, 'error' => "Date de sortie est requise."];
        }
        // id_chambre_to_vacate can be 0 or null if patient was not assigned a room,
        // or if it's an old record before room assignment was strict.
        // We only attempt to update the room if $id_chambre_to_vacate is a positive integer.

        try { 
            $this->conn->beginTransaction();

            // 1. Update Sejour with the discharge date AND set id_chambre to NULL
            $query_update_sejour = "UPDATE " . $this->table_name . "
                                    SET Date_sortiee = :date_sortie, id_chambre = NULL
                                    WHERE id_sejour = :id_sejour";
            $stmt_update_sejour = $this->conn->prepare($query_update_sejour);
            $stmt_update_sejour->bindParam(':date_sortie', $date_sortie_sanitized);
            $stmt_update_sejour->bindParam(':id_sejour', $id_sejour);

            if (!$stmt_update_sejour->execute()) {
                $this->conn->rollBack();
                $error_info = $stmt_update_sejour->errorInfo();
                $detailed_error = "Échec de la mise à jour des informations de sortie du séjour: " . ($error_info[2] ?? "Erreur DB inconnue");
                error_log("Failed to update sejour Date_sortiee and id_chambre. Sejour ID: " . $id_sejour . " Error: " . ($error_info[2] ?? "Unknown DB error"));
                return ['success' => false, 'error' => $detailed_error];
            }

            // 2. Make the vacated room available, only if id_chambre_to_vacate is valid
            if (!empty($id_chambre_to_vacate) && filter_var($id_chambre_to_vacate, FILTER_VALIDATE_INT) && $id_chambre_to_vacate > 0) {
                // Set room to available
                $query_room = "UPDATE Chambres SET Available = TRUE WHERE id_chambre = :id_chambre";
                $stmt_room = $this->conn->prepare($query_room);
                $stmt_room->bindParam(':id_chambre', $id_chambre_to_vacate, PDO::PARAM_INT);
                if (!$stmt_room->execute()) {
                    $this->conn->rollBack();
                    $error_info = $stmt_room->errorInfo();
                    $detailed_error = "Erreur lors de la mise à disposition de la chambre: " . ($error_info[2] ?? "Erreur DB inconnue");
                    error_log("Error making room available on discharge: " . implode(", ", $error_info) . " Room ID: " . $id_chambre_to_vacate);
                    return ['success' => false, 'error' => $detailed_error];
                }
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => "Séjour terminé avec succès."];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Database error in dischargePatient for sejour ID " . $id_sejour . ": " . $e->getMessage());
            return ['success' => false, 'error' => "Erreur de base de données: " . $e->getMessage()];
        }
    }

    public function createStayForExistingPatient($id_patient, $id_chambre, $Date_entree) {
        // Sanitize inputs
        $id_patient = htmlspecialchars(strip_tags($id_patient));
        $id_chambre = ($id_chambre !== null) ? htmlspecialchars(strip_tags($id_chambre)) : null;
        $Date_entree = htmlspecialchars(strip_tags($Date_entree));

        // Validate Date_entree format (example: YYYY-MM-DD)
        // More robust validation might be needed depending on expected input
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $Date_entree)) {
            error_log("Invalid Date_entree format for createStayForExistingPatient: " . $Date_entree);
            return false; 
        }

        try {
            $this->conn->beginTransaction();

            // 1. Insert new sejour record
            $query_insert_sejour = "INSERT INTO " . $this->table_name . 
                                   " (id_patient, id_chambre, Date_entree, Date_sortiee) VALUES (:id_patient, :id_chambre, :Date_entree, NULL)";
            $stmt_insert_sejour = $this->conn->prepare($query_insert_sejour);

            $stmt_insert_sejour->bindParam(':id_patient', $id_patient, PDO::PARAM_INT);
            if ($id_chambre !== null && $id_chambre != 0) {
                $stmt_insert_sejour->bindParam(':id_chambre', $id_chambre, PDO::PARAM_INT);
            } else {
                $stmt_insert_sejour->bindValue(':id_chambre', null, PDO::PARAM_NULL);
            }
            $stmt_insert_sejour->bindParam(':Date_entree', $Date_entree);

            if (!$stmt_insert_sejour->execute()) {
                $this->conn->rollBack();
                error_log("Failed to insert new sejour. Error: " . implode(" ", $stmt_insert_sejour->errorInfo()));
                return false;
            }

            // 2. Make the new room unavailable (if a valid room is assigned)
            if ($id_chambre !== null && $id_chambre != 0) {
                $chambre_model = new Chambre($this->conn); // Assumes Chambre model is available
                if (!$chambre_model->updateAvailability($id_chambre, false)) {
                    $this->conn->rollBack();
                    error_log("Failed to make new room unavailable for new sejour. Room ID: " . $id_chambre);
                    return false;
                }
            }
            
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Database error in createStayForExistingPatient: " . $e->getMessage());
            return false;
        }
    }

    public function getCompositeDetailsForModal($id_sejour) {
        $details = [
            'prescriptions' => [],
            'latest_suivi' => null
        ];

        try {
            // 1. Fetch all prescriptions for the sejour
            $query_prescriptions = "SELECT Medicament, Dosage, frequence, instructions FROM Prescription WHERE id_sejour = :id_sejour ORDER BY id_prescription ASC";
            $stmt_prescriptions = $this->conn->prepare($query_prescriptions);
            $stmt_prescriptions->bindParam(':id_sejour', $id_sejour, PDO::PARAM_INT);
            if ($stmt_prescriptions->execute()) {
                $details['prescriptions'] = $stmt_prescriptions->fetchAll(PDO::FETCH_ASSOC);
            } else {
                error_log("Error fetching prescriptions for modal: " . implode(" ", $stmt_prescriptions->errorInfo()));
            }

            // 2. Fetch the latest Suivi record (Remarque, Date_observation, nurse name)
            $query_latest_suivi = "SELECT 
                                       s.Remarque, 
                                       s.Date_observation as last_suivi_date, 
                                       s.etat_santee, 
                                       s.tension, 
                                       s.temperature, 
                                       s.frequence_quardiaque, 
                                       s.saturation_oxygene, 
                                       s.glycemie,
                                       s.poids, 
                                       s.taille,
                                       u.full_name as last_suivi_by_nurse
                                   FROM Suivi s
                                   JOIN Users u ON s.id_nurse = u.id
                                   WHERE s.id_sejour = :id_sejour
                                   ORDER BY s.Date_observation DESC, s.id_suivi DESC
                                   LIMIT 1";
            $stmt_latest_suivi = $this->conn->prepare($query_latest_suivi);
            $stmt_latest_suivi->bindParam(':id_sejour', $id_sejour, PDO::PARAM_INT);
            if ($stmt_latest_suivi->execute()) {
                $details['latest_suivi'] = $stmt_latest_suivi->fetch(PDO::FETCH_ASSOC);
            } else {
                error_log("Error fetching latest suivi for modal: " . implode(" ", $stmt_latest_suivi->errorInfo()));
            }
            
            return $details;

        } catch (PDOException $e) {
            error_log("Database error in getCompositeDetailsForModal: " . $e->getMessage());
            return false; // Indicate failure
        }
    }

    public function reactivateSejour($id_sejour, $id_chambre_for_reactivation) {
        $this->conn->beginTransaction();
        try {
            $id_sejour = htmlspecialchars(strip_tags($id_sejour));
            $id_chambre_selected = htmlspecialchars(strip_tags($id_chambre_for_reactivation));

            if (empty($id_chambre_selected) || !filter_var($id_chambre_selected, FILTER_VALIDATE_INT) || $id_chambre_selected <= 0) {
                $this->conn->rollBack();
                error_log("reactivateSejour: Valid id_chambre_for_reactivation is required. Received: " . $id_chambre_selected . " for sejour ID: " . $id_sejour);
                return ['success' => false, 'message' => 'معرف الغرفة المحدد صالح مطلوب لإعادة تفعيل الإقامة.'];
            }

            // 1. Check if the selected room is available
            $query_check_room = "SELECT Available FROM Chambres WHERE id_chambre = :id_chambre_selected FOR UPDATE"; // Lock the row
            $stmt_check_room = $this->conn->prepare($query_check_room);
            $stmt_check_room->bindParam(':id_chambre_selected', $id_chambre_selected, PDO::PARAM_INT);
            $stmt_check_room->execute();
            $room_status = $stmt_check_room->fetch(PDO::FETCH_ASSOC);

            if (!$room_status) {
                $this->conn->rollBack();
                error_log("reactivateSejour: Selected room ID " . $id_chambre_selected . " not found. Sejour ID: " . $id_sejour);
                return ['success' => false, 'message' => 'الغرفة المختارة غير موجودة.'];
            }
            if ($room_status['Available'] != 1 && $room_status['Available'] !== true) { // Check for both 1 and true
                $this->conn->rollBack();
                error_log("reactivateSejour: Selected room ID " . $id_chambre_selected . " is not available. Sejour ID: " . $id_sejour);
                return ['success' => false, 'message' => 'الغرفة المختارة ليست متاحة حالياً.'];
            }

            // 2. Reactivate sejour: set Date_sortiee to NULL and update id_chambre
            $query_update_sejour = "UPDATE " . $this->table_name . " SET Date_sortiee = NULL, id_chambre = :new_id_chambre WHERE id_sejour = :id_sejour";
            $stmt_update_sejour = $this->conn->prepare($query_update_sejour);
            $stmt_update_sejour->bindParam(':new_id_chambre', $id_chambre_selected, PDO::PARAM_INT);
            $stmt_update_sejour->bindParam(':id_sejour', $id_sejour, PDO::PARAM_INT);

            if (!$stmt_update_sejour->execute()) {
                $this->conn->rollBack();
                error_log("Error reactivating sejour (updating sejour table): " . implode(", ", $stmt_update_sejour->errorInfo()));
                return ['success' => false, 'message' => 'خطأ في إعادة تفعيل الإقامة (تحديث بيانات الإقامة).'];
            }

            // 3. Make the chosen new room unavailable
            $query_make_room_unavailable = "UPDATE Chambres SET Available = FALSE WHERE id_chambre = :id_chambre_selected";
            $stmt_make_room_unavailable = $this->conn->prepare($query_make_room_unavailable);
            $stmt_make_room_unavailable->bindParam(':id_chambre_selected', $id_chambre_selected, PDO::PARAM_INT);

            if (!$stmt_make_room_unavailable->execute()) {
                $this->conn->rollBack();
                error_log("Error making new room unavailable on reactivation: " . implode(", ", $stmt_make_room_unavailable->errorInfo()));
                return ['success' => false, 'message' => 'خطأ في تحديث حالة الغرفة الجديدة عند إعادة التفعيل.'];
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'تمت إعادة تفعيل الإقامة وتخصيص الغرفة المحددة بنجاح.'];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Exception in reactivateSejour: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث استثناء: ' . $e->getMessage()];
        }
    }
}
?> 