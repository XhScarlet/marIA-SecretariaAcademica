<?php
include 'api/config.php';
$stmt = $pdo->query("DESCRIBE protocolos");
print_r($stmt->fetchAll());
?>
