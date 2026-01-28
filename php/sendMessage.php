<?php
session_start();
if (!isset($_SESSION["id"])) { http_response_code(401); exit; }

if (!isset($_POST["message"])) { http_response_code(400); echo "Missing message"; exit; }

$message = trim($_POST["message"]);
if ($message === "" || mb_strlen($message) > 1000) { http_response_code(400); echo "Invalid message"; exit; }

$isBroadcast = isset($_POST["broadcast"]) && $_POST["broadcast"] === "1";
$receiver = null;

if (!$isBroadcast) {
    if (!isset($_POST["idReceiver"])) { http_response_code(400); echo "Missing receiver"; exit; }
    $receiver = (int)$_POST["idReceiver"];
    if ($receiver <= 0) { http_response_code(400); echo "Invalid receiver"; exit; }
}

require_once("./db_credentials.php");
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_NAME);
if ($mysqli->connect_errno) { http_response_code(500); exit; }
$mysqli->set_charset("utf8mb4");

$idSender = (int)$_SESSION["id"];
$isBroadcastInt = $isBroadcast ? 1 : 0;

$stmt = $mysqli->prepare("INSERT INTO messages (idSender, idReceiver, isBroadcast, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $idSender, $receiver, $isBroadcastInt, $message);

if (!$stmt->execute()) { http_response_code(500); echo "DB insert failed"; exit; }

header("Content-Type: application/json; charset=utf-8");
echo json_encode(["ok" => true, "idMessage" => $stmt->insert_id]);

$stmt->close();
$mysqli->close();
