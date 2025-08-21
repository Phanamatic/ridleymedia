<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Google Sheets Configuration
const SHEET_ID = '1ndb7FfVh05Zaw7eZVC_1RJMzVySfPIF3SH53jls9IO4';
const SERVICE_ACCOUNT_FILE = 'data/service-account.json';

$timestamp = date('Y-m-d H:i:s');
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Check if email already exists and add to Google Sheets
if (addToGoogleSheets($email, $timestamp, $ipAddress)) {
    echo json_encode(['success' => true, 'message' => 'Email added successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save email']);
}

function addToGoogleSheets($email, $timestamp, $ipAddress) {
    if (!file_exists(SERVICE_ACCOUNT_FILE)) {
        error_log('Service account file not found');
        return false;
    }
    
    try {
        // Get access token
        $accessToken = getGoogleAccessToken();
        if (!$accessToken) {
            return false;
        }
        
        // Check if email already exists
        if (emailExistsInSheet($email, $accessToken)) {
            return false; // Email already exists
        }
        
        // Add new row to sheet
        $values = [[$email, $timestamp, $ipAddress]];
        $url = "https://sheets.googleapis.com/v4/spreadsheets/" . SHEET_ID . "/values/A:C:append";
        
        $data = [
            'values' => $values,
            'majorDimension' => 'ROWS'
        ];
        
        $options = [
            'http' => [
                'header' => [
                    "Authorization: Bearer " . $accessToken,
                    "Content-Type: application/json"
                ],
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url . "?valueInputOption=USER_ENTERED", false, $context);
        
        return $result !== false;
        
    } catch (Exception $e) {
        error_log('Google Sheets error: ' . $e->getMessage());
        return false;
    }
}

function getGoogleAccessToken() {
    $serviceAccount = json_decode(file_get_contents(SERVICE_ACCOUNT_FILE), true);
    
    $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $now = time();
    $payload = json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = '';
    $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
    openssl_sign($base64Header . '.' . $base64Payload, $signature, $privateKey, 'SHA256');
    openssl_free_key($privateKey);
    
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded",
            'method' => 'POST',
            'content' => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ])
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
    
    if ($result) {
        $token = json_decode($result, true);
        return $token['access_token'] ?? null;
    }
    
    return null;
}

function emailExistsInSheet($email, $accessToken) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/" . SHEET_ID . "/values/A:A";
    
    $options = [
        'http' => [
            'header' => "Authorization: Bearer " . $accessToken,
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result) {
        $data = json_decode($result, true);
        $values = $data['values'] ?? [];
        
        foreach ($values as $row) {
            if (isset($row[0]) && strtolower(trim($row[0])) === strtolower(trim($email))) {
                return true;
            }
        }
    }
    
    return false;
}
?>