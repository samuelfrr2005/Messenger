<?php
session_start();
if (!isset($_SESSION["id"])) { http_response_code(401); exit; }

require_once("./db_credentials.php");
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_NAME);
if ($mysqli->connect_errno) { http_response_code(500); exit; }
$mysqli->set_charset("utf8mb4");
3+

$me = (int)$_SESSION["id"];

$stmt = $mysqli->prepare("SELECT idTrainer, username FROM trainers WHERE idTrainer <> ? ORDER BY username ASC");
$stmt->bind_param("i", $me);
$stmt->execute();
$res = $stmt->get_result();

$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;

header("Content-Type: application/json; charset=utf-8");
echo json_encode($users);

$stmt->close();
$mysqli->close();

