<?php

$host = "localhost:3308";
$database = "social_media";
$chatset = "utf8mb4";
$username = "root";
$password = "";
$dsn = "mysql:host=$host;dbname=$database;charset=$chatset";

$pdo = new PDO($dsn, $username, $password);