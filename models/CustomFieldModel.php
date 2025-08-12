<?php

// Database.php should be included by the calling script
// require_once 'config/Database.php';

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
                    (client_id, field_name, field_label, field_type, field_options, is_required, field_order, is_active, is_deleted)
                    VALUES
                    (:client_id, :field_name, :field_label, :field_type, :field_options, :is_required, :field_order, :is_active, :is_deleted)";

            $stmt = $this->conn->prepare($sql);

            return $stmt->execute([
                ':client_id' => $data['client_id'],
                ':field_name' => $data['field_name'],
                ':field_label' => $data['field_label'],
                ':field_type' => $data['field_type'],
                ':field_options' => (!empty($data['field_options']) && $data['field_options'] !== '') ? json_encode($data['field_options']) : null,
                ':is_required' => $data['is_required'] ?? 0,
                ':field_order' => $data['field_order'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1,
                ':is_deleted' => 0
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

            $sql = "SELECT * FROM custom_fields WHERE client_id = :client_id AND is_deleted = 0";
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
    public function getCustomFieldById($fieldId, $clientId = null) {
        try {
            $sql = "SELECT * FROM custom_fields WHERE id = :id AND is_deleted = 0";
            $params = [':id' => $fieldId];

            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

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
    public function updateCustomField($fieldId, $data, $clientId = null) {
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

            $params = [
                ':id' => $fieldId,
                ':field_name' => $data['field_name'],
                ':field_label' => $data['field_label'],
                ':field_type' => $data['field_type'],
                ':field_options' => $data['field_options'] ? json_encode($data['field_options']) : null,
                ':is_required' => $data['is_required'] ?? 0,
                ':field_order' => $data['field_order'] ?? 0,
                ':is_active' => $data['is_active'] ?? 1
            ];

            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("CustomFieldModel updateCustomField error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete custom field (soft delete by setting is_active = 0)
     */
    public function deleteCustomField($fieldId, $clientId = null) {
        try {
            $sql = "UPDATE custom_fields SET is_active = 0 WHERE id = :id";
            $params = [':id' => $fieldId];

            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
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
            $updatedFields = []; // Track which fields were updated for usage count

            foreach ($fieldValues as $fieldId => $value) {
                // Check if value already exists
                $checkStmt = $this->conn->prepare("SELECT id FROM custom_field_values WHERE user_id = :user_id AND custom_field_id = :field_id");
                $checkStmt->execute([':user_id' => $userId, ':field_id' => $fieldId]);

                $existingRecord = $checkStmt->fetch();

                if ($existingRecord) {
                    // Update existing value
                    $updateStmt = $this->conn->prepare("UPDATE custom_field_values SET field_value = :value, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id AND custom_field_id = :field_id");
                    $updateStmt->execute([
                        ':value' => $value,
                        ':user_id' => $userId,
                        ':field_id' => $fieldId
                    ]);
                } else {
                    // Insert new value (only if value is not empty)
                    if (!empty($value)) {
                        $insertStmt = $this->conn->prepare("INSERT INTO custom_field_values (user_id, custom_field_id, field_value, is_deleted) VALUES (:user_id, :field_id, :value, :is_deleted)");
                        $insertStmt->execute([
                            ':user_id' => $userId,
                            ':field_id' => $fieldId,
                            ':value' => $value,
                            ':is_deleted' => 0
                        ]);
                    }
                }

                // Track this field for usage count update
                $updatedFields[] = $fieldId;
            }

            // Update usage count for all affected fields
            foreach ($updatedFields as $fieldId) {
                $this->updateFieldUsageCount($fieldId);
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

    /**
     * Get all custom fields across all clients (for super admin)
     */
    public function getAllCustomFields($activeOnly = true) {
        try {
            $sql = "SELECT cf.*, c.client_name
                    FROM custom_fields cf
                    LEFT JOIN clients c ON cf.client_id = c.id
                    WHERE cf.is_deleted = 0";

            if ($activeOnly) {
                $sql .= " AND cf.is_active = 1";
            }

            $sql .= " ORDER BY c.client_name, cf.field_order, cf.created_at";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CustomFieldModel getAllCustomFields error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get usage count for a custom field (how many users have values for this field)
     * Now uses the usage_count column directly from custom_fields table
     */
    public function getFieldUsageCount($fieldId) {
        try {
            $stmt = $this->conn->prepare("SELECT usage_count FROM custom_fields WHERE id = :field_id AND is_deleted = 0");
            $stmt->execute([':field_id' => $fieldId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['usage_count'] ?? 0);
        } catch (PDOException $e) {
            error_log("CustomFieldModel getFieldUsageCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update usage count for a custom field
     * Call this when custom field values are added/removed
     */
    public function updateFieldUsageCount($fieldId) {
        try {
            // Count actual usage from custom_field_values table (excluding deleted values)
            $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT user_id) as usage_count FROM custom_field_values WHERE custom_field_id = :field_id AND is_deleted = 0");
            $stmt->execute([':field_id' => $fieldId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $usageCount = (int)($result['usage_count'] ?? 0);

            // Update the usage_count column in custom_fields table
            $updateStmt = $this->conn->prepare("UPDATE custom_fields SET usage_count = :usage_count WHERE id = :field_id AND is_deleted = 0");
            $updateStmt->execute([
                ':usage_count' => $usageCount,
                ':field_id' => $fieldId
            ]);

            return $usageCount;
        } catch (PDOException $e) {
            error_log("CustomFieldModel updateFieldUsageCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update custom field status (activate/deactivate)
     */
    public function updateCustomFieldStatus($fieldId, $isActive) {
        try {
            $stmt = $this->conn->prepare("UPDATE custom_fields SET is_active = :is_active, updated_at = NOW() WHERE id = :id");
            return $stmt->execute([
                ':is_active' => $isActive ? 1 : 0,
                ':id' => $fieldId
            ]);
        } catch (PDOException $e) {
            error_log("CustomFieldModel updateCustomFieldStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete custom field and all its values
     * This marks the field and its values as deleted without removing data
     */
    public function softDeleteCustomField($fieldId, $deletedBy = null) {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Soft delete all custom field values
            $deleteValuesStmt = $this->conn->prepare("
                UPDATE custom_field_values
                SET is_deleted = 1, deleted_at = NOW(), deleted_by = :deleted_by
                WHERE custom_field_id = :field_id AND is_deleted = 0
            ");
            $deleteValuesStmt->execute([
                ':field_id' => $fieldId,
                ':deleted_by' => $deletedBy
            ]);

            // Soft delete the custom field itself and reset usage count
            $deleteFieldStmt = $this->conn->prepare("
                UPDATE custom_fields
                SET is_deleted = 1, deleted_at = NOW(), deleted_by = :deleted_by, usage_count = 0
                WHERE id = :field_id AND is_deleted = 0
            ");
            $deleteFieldStmt->execute([
                ':field_id' => $fieldId,
                ':deleted_by' => $deletedBy
            ]);

            // Commit transaction
            $this->conn->commit();

            $result = $deleteFieldStmt->rowCount() > 0;

            return $result;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("CustomFieldModel softDeleteCustomField error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Permanently delete custom field (only for inactive unused fields)
     */
    public function permanentDeleteCustomField($fieldId) {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // First delete any associated field values (should be none for unused fields)
            $stmt = $this->conn->prepare("DELETE FROM custom_field_values WHERE custom_field_id = :field_id");
            $stmt->execute([':field_id' => $fieldId]);

            // Then delete the custom field itself
            $stmt = $this->conn->prepare("DELETE FROM custom_fields WHERE id = :id");
            $result = $stmt->execute([':id' => $fieldId]);

            // Commit transaction
            $this->conn->commit();

            return $result;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("CustomFieldModel permanentDeleteCustomField error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if field name already exists for a client
     */
    public function checkFieldNameExists($fieldName, $clientId = null, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM custom_fields WHERE field_name = :field_name AND is_deleted = 0";
            $params = [':field_name' => $fieldName];

            // Filter by client if specified
            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            // Exclude specific field ID (for updates)
            if ($excludeId !== null) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['count'] > 0;
        } catch (PDOException $e) {
            error_log("CustomFieldModel checkFieldNameExists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if field label already exists for a client
     */
    public function checkFieldLabelExists($fieldLabel, $clientId = null, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM custom_fields WHERE field_label = :field_label AND is_deleted = 0";
            $params = [':field_label' => $fieldLabel];

            // Filter by client if specified
            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            // Exclude specific field ID (for updates)
            if ($excludeId !== null) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['count'] > 0;
        } catch (PDOException $e) {
            error_log("CustomFieldModel checkFieldLabelExists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete custom field values for a user (when user is deleted)
     * This should be called when a user is deleted to update usage counts
     */
    public function deleteUserCustomFieldValues($userId) {
        try {
            $this->conn->beginTransaction();

            // Get all custom field IDs that this user has values for
            $getFieldsStmt = $this->conn->prepare("
                SELECT DISTINCT custom_field_id
                FROM custom_field_values
                WHERE user_id = :user_id AND is_deleted = 0
            ");
            $getFieldsStmt->execute([':user_id' => $userId]);
            $affectedFields = $getFieldsStmt->fetchAll(PDO::FETCH_COLUMN);

            // Soft delete all custom field values for this user
            $deleteStmt = $this->conn->prepare("
                UPDATE custom_field_values
                SET is_deleted = 1, deleted_at = NOW()
                WHERE user_id = :user_id AND is_deleted = 0
            ");
            $deleteStmt->execute([':user_id' => $userId]);

            // Update usage count for all affected fields
            foreach ($affectedFields as $fieldId) {
                $this->updateFieldUsageCount($fieldId);
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("CustomFieldModel deleteUserCustomFieldValues error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalculate usage counts for all custom fields
     * This can be used to fix any inconsistencies in usage counts
     */
    public function recalculateAllUsageCounts($clientId = null) {
        try {
            $sql = "SELECT id FROM custom_fields WHERE is_deleted = 0";
            $params = [];

            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $updatedCount = 0;
            foreach ($fields as $fieldId) {
                $this->updateFieldUsageCount($fieldId);
                $updatedCount++;
            }

            return $updatedCount;
        } catch (PDOException $e) {
            error_log("CustomFieldModel recalculateAllUsageCounts error: " . $e->getMessage());
            return 0;
        }
    }
}
