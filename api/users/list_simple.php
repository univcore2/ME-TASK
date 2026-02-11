<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();

header('Content-Type: application/json');

try {

    // Fetch active users only
    $stmt = $conn->prepare("
        SELECT id, name 
        FROM users 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");

    $stmt->execute();
    $stmt->bind_result($id, $name);

    $users = [];

    while ($stmt->fetch()) {
        $users[] = [
            'id'   => $id,
            'name' => $name
        ];
    }

    $stmt->close();

    echo json_encode([
        "ok" => true,
        "users" => $users
    ]);

} catch (Exception $e) {

    echo json_encode([
        "ok" => false,
        "message" => "Error loading users"
    ]);

}
