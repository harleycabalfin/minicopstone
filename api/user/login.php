<?php
require_once '../../includes/db.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

$response = login($username, $password);
echo json_encode($response);
