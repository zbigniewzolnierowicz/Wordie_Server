<?php
    use \Firebase\JWT\JWT;
    require_once "secrets.php";
    $header = array(
        'iss' => 'localhost', // Issuer
        'aud' => 'localhost', // Audience (who will receive the JWT)
        'iat' => time(), // Issued At
        'nbf' => time() + 60 // Not Before
    );
    print_r($header);
    print_r($private_key);
?>