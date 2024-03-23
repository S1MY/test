<?php 
require_once 'connect.php';
require_once 'function.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test</title>
    <style>
        section{
            display: flex;
            gap: 15px;
        }
    </style>
</head>
<body>
    <section>
        <div>
            <?php getGroup($conn); ?>
        </div>
        <div>
            <?php
                $idGroups = $_GET['group'] ? intval($_GET['group']) : 0;
                getProduct($conn, $idGroups);
            ?>
        </div>
    </section>
</body>
</html>