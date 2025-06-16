<?php
require_once 'config/Database.php';

class SSOModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get SSO providers
     */
    public function getAllProviders() {
        $sql = "SELECT * FROM sso_providers WHERE is_active = 1 ORDER BY provider_name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get SSO configuration for client and provider
     */
    public function getClientSSOConfig($clientId, $providerId) {
        $sql = "SELECT csc.*, sp.provider_name, sp.provider_type
                FROM client_sso_configurations csc
                JOIN sso_providers sp ON csc.sso_provider_id = sp.id
                WHERE csc.client_id = ? AND csc.sso_provider_id = ? AND csc.is_enabled = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId, $providerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save or update SSO configuration
     */
    public function saveSSOConfig($clientId, $providerId, $config) {
        $sql = "INSERT INTO client_sso_configurations 
                (client_id, sso_provider_id, is_enabled, configuration, metadata_url, entity_id, sso_url, slo_url, 
                 certificate, private_key, client_id_oauth, client_secret_oauth, redirect_uri, scope, 
                 authorization_endpoint, token_endpoint, userinfo_endpoint, ldap_server, ldap_port, 
                 ldap_base_dn, ldap_bind_dn, ldap_bind_password, attribute_mapping)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                is_enabled = VALUES(is_enabled),
                configuration = VALUES(configuration),
                metadata_url = VALUES(metadata_url),
                entity_id = VALUES(entity_id),
                sso_url = VALUES(sso_url),
                slo_url = VALUES(slo_url),
                certificate = VALUES(certificate),
                private_key = VALUES(private_key),
                client_id_oauth = VALUES(client_id_oauth),
                client_secret_oauth = VALUES(client_secret_oauth),
                redirect_uri = VALUES(redirect_uri),
                scope = VALUES(scope),
                authorization_endpoint = VALUES(authorization_endpoint),
                token_endpoint = VALUES(token_endpoint),
                userinfo_endpoint = VALUES(userinfo_endpoint),
                ldap_server = VALUES(ldap_server),
                ldap_port = VALUES(ldap_port),
                ldap_base_dn = VALUES(ldap_base_dn),
                ldap_bind_dn = VALUES(ldap_bind_dn),
                ldap_bind_password = VALUES(ldap_bind_password),
                attribute_mapping = VALUES(attribute_mapping),
                updated_at = NOW()";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $clientId,
            $providerId,
            $config['is_enabled'] ?? 0,
            json_encode($config),
            $config['metadata_url'] ?? null,
            $config['entity_id'] ?? null,
            $config['sso_url'] ?? null,
            $config['slo_url'] ?? null,
            $config['certificate'] ?? null,
            $config['private_key'] ?? null,
            $config['client_id_oauth'] ?? null,
            $config['client_secret_oauth'] ?? null,
            $config['redirect_uri'] ?? null,
            $config['scope'] ?? null,
            $config['authorization_endpoint'] ?? null,
            $config['token_endpoint'] ?? null,
            $config['userinfo_endpoint'] ?? null,
            $config['ldap_server'] ?? null,
            $config['ldap_port'] ?? null,
            $config['ldap_base_dn'] ?? null,
            $config['ldap_bind_dn'] ?? null,
            $config['ldap_bind_password'] ?? null,
            json_encode($config['attribute_mapping'] ?? [])
        ]);
    }

    /**
     * Generate SAML authentication request
     */
    public function generateSAMLRequest($config, $relayState = '') {
        $requestId = '_' . bin2hex(random_bytes(16));
        $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
        
        $samlRequest = '<?xml version="1.0" encoding="UTF-8"?>
        <samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                           xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                           ID="' . $requestId . '"
                           Version="2.0"
                           IssueInstant="' . $issueInstant . '"
                           Destination="' . htmlspecialchars($config['sso_url']) . '"
                           AssertionConsumerServiceURL="' . htmlspecialchars($config['redirect_uri']) . '"
                           ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
            <saml:Issuer>' . htmlspecialchars($config['entity_id']) . '</saml:Issuer>
        </samlp:AuthnRequest>';
        
        return [
            'request_id' => $requestId,
            'saml_request' => base64_encode($samlRequest),
            'relay_state' => $relayState,
            'sso_url' => $config['sso_url']
        ];
    }

    /**
     * Generate OAuth 2.0 authorization URL
     */
    public function generateOAuthURL($config, $state = '') {
        $params = [
            'client_id' => $config['client_id_oauth'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $config['scope'] ?? 'openid profile email',
            'state' => $state ?: bin2hex(random_bytes(16))
        ];
        
        return $config['authorization_endpoint'] . '?' . http_build_query($params);
    }

    /**
     * Exchange OAuth code for token
     */
    public function exchangeOAuthCode($config, $code) {
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $config['client_id_oauth'],
            'client_secret' => $config['client_secret_oauth'],
            'redirect_uri' => $config['redirect_uri'],
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['token_endpoint']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }

    /**
     * Get user info from OAuth provider
     */
    public function getOAuthUserInfo($config, $accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['userinfo_endpoint']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }

    /**
     * Authenticate with LDAP
     */
    public function authenticateLDAP($config, $username, $password) {
        if (!function_exists('ldap_connect')) {
            return ['success' => false, 'message' => 'LDAP extension not available'];
        }
        
        $ldapConn = ldap_connect($config['ldap_server'], $config['ldap_port']);
        if (!$ldapConn) {
            return ['success' => false, 'message' => 'Could not connect to LDAP server'];
        }
        
        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        
        try {
            // Bind with service account
            if (!ldap_bind($ldapConn, $config['ldap_bind_dn'], $config['ldap_bind_password'])) {
                return ['success' => false, 'message' => 'LDAP bind failed'];
            }
            
            // Search for user
            $searchFilter = "(&(objectClass=user)(|(sAMAccountName=$username)(mail=$username)))";
            $searchResult = ldap_search($ldapConn, $config['ldap_base_dn'], $searchFilter);
            
            if (!$searchResult) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $entries = ldap_get_entries($ldapConn, $searchResult);
            if ($entries['count'] === 0) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $userDN = $entries[0]['dn'];
            
            // Authenticate user
            if (ldap_bind($ldapConn, $userDN, $password)) {
                $userInfo = $this->extractLDAPUserInfo($entries[0], $config['attribute_mapping'] ?? []);
                return ['success' => true, 'user_info' => $userInfo];
            } else {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'LDAP authentication error: ' . $e->getMessage()];
        } finally {
            ldap_close($ldapConn);
        }
    }

    /**
     * Extract user information from LDAP entry
     */
    private function extractLDAPUserInfo($ldapEntry, $attributeMapping) {
        $userInfo = [];
        
        $defaultMapping = [
            'email' => 'mail',
            'full_name' => 'displayname',
            'first_name' => 'givenname',
            'last_name' => 'sn',
            'username' => 'samaccountname'
        ];
        
        $mapping = array_merge($defaultMapping, $attributeMapping);
        
        foreach ($mapping as $localAttr => $ldapAttr) {
            if (isset($ldapEntry[$ldapAttr][0])) {
                $userInfo[$localAttr] = $ldapEntry[$ldapAttr][0];
            }
        }
        
        return $userInfo;
    }
}
?>
