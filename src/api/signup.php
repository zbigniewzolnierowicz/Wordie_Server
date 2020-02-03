<?php
// Include CORS headers
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";

$response;
$postData = json_decode(file_get_contents("php://input"), true);
try {
    // Check if the user already exists
    $alreadyExistsQuery = "SELECT `id` FROM `user` WHERE `name` = '" . $postData['username'] . "'";
    $data = mysqli_query($db, $alreadyExistsQuery);
    $row = mysqli_fetch_assoc($data);
    if ($row) {
        throw new Exception("user_already_exists");
    }
    $newAccountQuery = "INSERT INTO `user` (`name`, `display_name`, `password_hash`) VALUES (";
    if (empty($postData['username'])) {
        throw new Exception("no_username");
    }
    if (empty($postData['password'])) {
        throw new Exception("no_password");
    }
    if (empty($postData['display_name'])) {
        $newAccountQuery .= "'$postData[username]', '$postData[username]', '" . password_hash($postData['password'], PASSWORD_DEFAULT) . "');";
    } else {
        $newAccountQuery .= "'$postData[username]', '$postData[display_name]', '" . password_hash($postData['password'], PASSWORD_DEFAULT) . "');";
    }
    if (!mysqli_query($db, $newAccountQuery)) {
        throw new Exception("table_creation_failed");
    }
    $response['response'] = "sign_up_success";
} catch (\Throwable $th) {
    http_response_code($_SERVER['REQUEST_METHOD'] === "OPTIONS" ? 200 : 500);
    $response['response'] = "sign_up_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>