<?php
require_once '../../includes/db.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$batch = intval($data['batch_number']);
$collected = intval($data['eggs_collected']);
$damaged = intval($data['damaged_eggs']);
$date = $data['production_date'];
$notes = $data['notes'] ?? '';

$db = getDB();

$stmt = $db->prepare("INSERT INTO egg_production (batch_number, eggs_collected, damaged_eggs, production_date, notes)
                      VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiss", $batch, $collected, $damaged, $date, $notes);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Record added successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to add record"]);
}
