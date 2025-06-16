<?php

require_once 'config/Database.php';

class CustomFieldModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Create a new custom field
     */
    public function createCustomField($data) {
        try {
            $sql = "INSERT INTO custom_fields
                    (client_id, field_name, field_label, field_type, field_options, is_required, field_order, is_active)
                    VALUES
                    (:client_id, :field_name, :field_label, :field_type, :field_options, :is_required, :field_order, :is_active)";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':client_id' => $data['client_id'],
                ':field_name' => $data['field_name'],
                ':field_label' => $data['field_label'],
                ':field_type' => $data['field_type'],
                ':field_options' => $data['field_options'] ? json_encode($data['field_options']) : null,
                ':is_required' => $data['is_required'] ?? 0,
                ':field_order' => $data['field_order'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1
            ]);
        } catch (PDOException $e) {
            error_log("CustomFieldModel createCustomField error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all custom fields for a client
     */
    public function getCustomFieldsByClient($clientId, $activeOnly = true) {
        try {
            // Ensure client_id is valid
            if (!$clientId || !is_numeric($clientId)) {
                return [];
            }

            $sql = "SELECT * FROM custom_fields WHERE client_id = :client_id";
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            $sql .= " ORDER BY field_order ASC, id ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':client_id' => (int)$clientId]);

            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Decode field_options JSON
            foreach ($fields as &$field) {
                if ($field['field_options']) {
                    $field['field_options'] = json_decode($field['field_options'], true);
                }
            }

            return $fields;
        } catch (PDOException $e) {
            error_log("CustomFieldModel getCustomFieldsByClient error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get custom field by ID
     */
    public function getCustomFieldById($fieldId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM custom_fields WHERE id = :id");
            $stmt->execute([':id' => $fieldId]);
            
            $field = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($field && $field['field_options']) {
                $field['field_options'] = json_decode($field['field_options'], true);
            }
            
            return $field;
        } catch (PDOException $e) {
            error_log("CustomFieldModel getCustomFieldById error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update custom field
     */
    public function updateCustomField($fieldId, $data) {
        try {
            $sql = "UPDATE custom_fields SET 
                    field_name = :field_name,
                    field_label = :field_label,
                    field_type = :field_type,
                    field_options = :field_options,
                    is_required = :is_required,
                    field_order = :field_order,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            
            return $stmt->execute([
                ':id' => $fieldId,
                ':field_name' => $data['field_name'],
                ':field_label' => $data['field_label'],
                ':field_type' => $data['field_type'],
                ':field_options' => $data['field_options'] ? json_encode($data['field_options']) : null,
                ':is_required' => $data['is_required'] ?? 0,
                ':field_order' => $data['field_order'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1
            ]);
        } catch (PDOException $e) {
            error_log("CustomFieldModel updateCustomField error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete custom field (soft delete by setting is_active = 0)
     */
    public function deleteCustomField($fieldId) {
        try {
            $stmt = $this->conn->prepare("UPDATE custom_fields SET is_active = 0 WHERE id = :id");
            return $stmt->execute([':id' => $fieldId]);
        } catch (PDOException $e) {
            error_log("CustomFieldModel deleteCustomField error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save custom field values for a user
     */
    public function saveCustomFieldValues($userId, $fieldValues) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($fieldValues as $fieldId => $value) {
                // Check if value already exists
                $checkStmt = $this->conn->prepare("SELECT id FROM custom_field_values WHERE user_id = :user_id AND custom_field_id = :field_id");
                $checkStmt->execute([':user_id' => $userId, ':field_id' => $fieldId]);
                
                if ($checkStmt->fetch()) {
                    // Update existing value
                    $updateStmt = $this->conn->prepare("UPDATE custom_field_values SET field_value = :value, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id AND custom_field_id = :field_id");
                    $updateStmt->execute([
                        ':value' => $value,
                        ':user_id' => $userId,
                        ':field_id' => $fieldId
                    ]);
                } else {
                    // Insert new value
                    $insertStmt = $this->conn->prepare("INSERT INTO custom_field_values (user_id, custom_field_id, field_value) VALUES (:user_id, :field_id, :value)");
                    $insertStmt->execute([
                        ':user_id' => $userId,
                        ':field_id' => $fieldId,
                        ':value' => $value
                    ]);
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("CustomFieldModel saveCustomFieldValues error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get custom field values for a user
     */
    public function getCustomFieldValues($userId, $clientId = null) {
        try {
            $sql = "SELECT cfv.*, cf.field_name, cf.field_label, cf.field_type, cf.field_options, cf.is_required
                    FROM custom_field_values cfv
                    JOIN custom_fields cf ON cfv.custom_field_id = cf.id
                    WHERE cfv.user_id = :user_id AND cf.is_active = 1";
            
            $params = [':user_id' => $userId];
            
            if ($clientId) {
                $sql .= " AND cf.client_id = :client_id";
                $params[':client_id'] = $clientId;
            }
            
            $sql .= " ORDER BY cf.field_order ASC, cf.id ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode field_options JSON
            foreach ($values as &$value) {
                if ($value['field_options']) {
                    $value['field_options'] = json_decode($value['field_options'], true);
                }
            }
            
            return $values;
        } catch (PDOException $e) {
            error_log("CustomFieldModel getCustomFieldValues error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get next field order for a client
     */
    public function getNextFieldOrder($clientId) {
        try {
            $stmt = $this->conn->prepare("SELECT MAX(field_order) as max_order FROM custom_fields WHERE client_id = :client_id");
            $stmt->execute([':client_id' => $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['max_order'] ?? 0) + 1;
        } catch (PDOException $e) {
            error_log("CustomFieldModel getNextFieldOrder error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Update field order
     */
    public function updateFieldOrder($fieldId, $newOrder) {
        try {
            $stmt = $this->conn->prepare("UPDATE custom_fields SET field_order = :order WHERE id = :id");
            return $stmt->execute([':order' => $newOrder, ':id' => $fieldId]);
        } catch (PDOException $e) {
            error_log("CustomFieldModel updateFieldOrder error: " . $e->getMessage());
            return false;
        }
    }
}
