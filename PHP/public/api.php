<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../src/Database.php';

$db = new Database();
$response = ['status' => 'error', 'message' => 'Ungültige Anfrage.'];

if (isset($_GET['action']) && $_GET['action'] === 'search_devices' && isset($_GET['query'])) {
    $search_query = '%' . $_GET['query'] . '%';

    try {
        // Query, um Geräte nach Name, IP oder MAC zu suchen
        $db->query("SELECT name, ip_address, mac_address, notes, barcode_number FROM devices 
                    WHERE name LIKE :query OR ip_address LIKE :query OR mac_address LIKE :query OR barcode_number LIKE :query");
        $db->bind(':query', $search_query);
        $devices = $db->resultset();
        
        $response = [
            'status' => 'success',
            'data' => $devices
        ];
    } catch (Exception $e) {
        $response['message'] = 'Datenbankfehler: ' . $e->getMessage();
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
