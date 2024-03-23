<?php
$servername = "localhost";
$username = "root";
$password = "";
$base = "test";

try {
    $conn = new mysqli($servername, $username, $password, $base);
    if ($conn->connect_errno) {
        throw new Exception("Подключение невозможно: " . $conn->connect_error);
    }

    // echo "Подключение успешно!";
} catch(Exception $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}