<?php
include_once 'c:/Users/suzx2/Downloads/DMA/DMA-Auto_Electricals/config/database.php';
$stmt = $pdo->query("DESCRIBE customers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
