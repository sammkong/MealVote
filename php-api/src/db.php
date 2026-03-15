<?php
$host = getenv('DB_HOST') ?: 'mariadb';
$user = getenv('DB_USER') ?: 'mv';
$pass = getenv('DB_PASS') ?: 'mvpass';
$name = getenv('DB_NAME') ?: 'mealvote';
$dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$pdo = new PDO($dsn, $user, $pass, $options);
?>