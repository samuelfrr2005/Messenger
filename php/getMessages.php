<?php
session_start();
if (!isset($_SESSION["id"])) { http_response_code(401); exit; }

require_once("./db_credentials.php");
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PW, DB_NAME);
if ($mysqli->connect_errno) { http_response_code(500); exit; }
$mysqli->set_charset("utf8mb4");

$me = (int)$_SESSION["id"];
$afterId = isset($_GET["afterId"]) ? (int)$_GET["afterId"] : 0;
$withId  = isset($_GET["withId"]) ? (int)$_GET["withId"] : 0;

if ($withId > 0) {
    $sql = "
      SELECT m.idMessage, m.idSender, s.username AS senderName,
             m.idReceiver, r.username AS receiverName,
             m.isBroadcast, m.message, m.createdAt
      FROM messages m
      JOIN trainers s ON s.idTrainer = m.idSender
      LEFT JOIN trainers r ON r.idTrainer = m.idReceiver
      WHERE m.idMessage > ?
        AND (
              m.isBroadcast = 1
              OR (m.idSender = ? AND m.idReceiver = ?)
              OR (m.idSender = ? AND m.idReceiver = ?)
            )
      ORDER BY m.createdAt ASC, m.idMessage ASC
      LIMIT 200
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iiiii", $afterId, $me, $withId, $withId, $me);
} else {
    $sql = "
      SELECT m.idMessage, m.idSender, s.username AS senderName,
             m.idReceiver, r.username AS receiverName,
             m.isBroadcast, m.message, m.createdAt
      FROM messages m
      JOIN trainers s ON s.idTrainer = m.idSender
      LEFT JOIN trainers r ON r.idTrainer = m.idReceiver
      WHERE m.idMessage > ?
        AND (m.isBroadcast = 1 OR m.idSender = ? OR m.idReceiver = ?)
      ORDER BY m.createdAt ASC, m.idMessage ASC
      LIMIT 200
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iii", $afterId, $me, $me);
}

$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    // Output-sicher machen (XSS)
    $row["message"] = htmlspecialchars($row["message"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $out[] = $row;
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($out);

$stmt->close();
$mysqli->close();
