<?php

include __DIR__ . "/src/Framework/Database.php";

use Framework\Database;

$db = new Database('mysql', [
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'db_edu'
], 'root', '');
echo "Connected to DB";
$query = "SELECT * FROM products";

$stmt = $db->connection->query($query);

var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
