<?php
    $db = new mysqli('localhost', 'root', '', 'wordie', '3306');
    if ($db->connect_errno) {
        throw new Exception("database_connection_fail");
    }
?>