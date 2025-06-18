<?php
/**
 * ID Encryption Helper
 * 
 * Provides secure encryption/decryption for IDs in URLs
 * to prevent direct exposure of database IDs
 */

class IdEncryption {
    
    /**
     * Encryption key - should be stored in environment variables in production
     */
    private static $encryptionKey = 'UnlockYourSkills2024SecureKey!@#';
    
    /**
     * Encryption method
     */
    private static $method = 'AES-256-CBC';
    
    /**
     * Encrypt an ID for use in URLs
     * 
     * @param int $id The ID to encrypt
     * @return string The encrypted ID (URL-safe)
     */
    public static function encrypt($id) {
        if (empty($id) || !is_numeric($id)) {
            throw new InvalidArgumentException('ID must be a valid number');
        }
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$method));
        $encrypted = openssl_encrypt($id, self::$method, self::$encryptionKey, 0, $iv);
        
        // Combine IV and encrypted data, then base64 encode for URL safety
        $combined = base64_encode($iv . $encrypted);
        
        // Make URL-safe by replacing characters
        return str_replace(['+', '/', '='], ['-', '_', ''], $combined);
    }
    
    /**
     * Decrypt an ID from URL
     * 
     * @param string $encryptedId The encrypted ID from URL
     * @return int The original ID
     */
    public static function decrypt($encryptedId) {
        if (empty($encryptedId)) {
            throw new InvalidArgumentException('Encrypted ID cannot be empty');
        }
        
        try {
            // Restore URL-safe characters
            $combined = str_replace(['-', '_'], ['+', '/'], $encryptedId);
            
            // Add padding if needed
            $combined .= str_repeat('=', (4 - strlen($combined) % 4) % 4);
            
            $data = base64_decode($combined);
            
            if ($data === false) {
                throw new Exception('Invalid encrypted ID format');
            }
            
            $ivLength = openssl_cipher_iv_length(self::$method);
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            $decrypted = openssl_decrypt($encrypted, self::$method, self::$encryptionKey, 0, $iv);
            
            if ($decrypted === false) {
                throw new Exception('Failed to decrypt ID');
            }
            
            $id = intval($decrypted);
            
            if ($id <= 0) {
                throw new Exception('Invalid decrypted ID');
            }
            
            return $id;
            
        } catch (Exception $e) {
            error_log("ID Decryption Error: " . $e->getMessage());
            throw new InvalidArgumentException('Invalid encrypted ID');
        }
    }
    
    /**
     * Check if a string looks like an encrypted ID
     * 
     * @param string $value The value to check
     * @return bool True if it looks encrypted, false if it's a plain number
     */
    public static function isEncrypted($value) {
        // If it's a plain number, it's not encrypted
        if (is_numeric($value) && ctype_digit($value)) {
            return false;
        }
        
        // If it contains URL-safe base64 characters, it's likely encrypted
        return preg_match('/^[A-Za-z0-9\-_]+$/', $value) && strlen($value) > 10;
    }
    
    /**
     * Safely get ID from parameter (handles both encrypted and plain IDs)
     * 
     * @param string $value The ID parameter value
     * @return int The actual ID
     */
    public static function getId($value) {
        if (self::isEncrypted($value)) {
            return self::decrypt($value);
        } else {
            // For backward compatibility, allow plain numeric IDs
            if (is_numeric($value) && $value > 0) {
                return intval($value);
            }
        }
        
        throw new InvalidArgumentException('Invalid ID parameter');
    }
    
    /**
     * Generate encrypted URL for a given route and ID
     * 
     * @param string $route The route pattern (e.g., 'users/{id}/edit')
     * @param int $id The ID to encrypt
     * @return string The URL with encrypted ID
     */
    public static function generateUrl($route, $id) {
        require_once __DIR__ . '/UrlHelper.php';
        
        $encryptedId = self::encrypt($id);
        $url = str_replace('{id}', $encryptedId, $route);
        
        return UrlHelper::url($url);
    }
    
    /**
     * Generate multiple encrypted URLs for common user operations
     * 
     * @param int $userId The user ID
     * @return array Array of URLs for different operations
     */
    public static function generateUserUrls($userId) {
        $encryptedId = self::encrypt($userId);
        
        return [
            'edit' => UrlHelper::url("users/{$encryptedId}/edit"),
            'delete' => UrlHelper::url("users/{$encryptedId}/delete"),
            'lock' => UrlHelper::url("users/{$encryptedId}/lock"),
            'unlock' => UrlHelper::url("users/{$encryptedId}/unlock"),
        ];
    }
    
    /**
     * Set encryption key (for production use with environment variables)
     * 
     * @param string $key The encryption key
     */
    public static function setEncryptionKey($key) {
        if (strlen($key) < 16) {
            throw new InvalidArgumentException('Encryption key must be at least 16 characters long');
        }
        
        self::$encryptionKey = $key;
    }
}
?>
