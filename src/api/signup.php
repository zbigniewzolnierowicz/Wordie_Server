<?php
// Include CORS headers
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";

$response;
$postData = json_decode(file_get_contents("php://input"), true);
http_response_code(200);
try {
    // Check if the user already exists
    $alreadyExistsQuery = "SELECT `id` FROM `user` WHERE `name` = '" . $postData['username'] . "'";
    $data = mysqli_query($db, $alreadyExistsQuery);
    $row = mysqli_fetch_assoc($data);
    if ($row) {
        throw new Exception("This user already exists.", 0);
    }
    $newAccountQuery = "INSERT INTO `user` (`name`, `display_name`, `password_hash`) VALUES (";
    if (empty($postData['username'])) {
        throw new Exception("No username provided.", 1);
    }
    if (empty($postData['password'])) {
        throw new Exception("No username provided.", 2);
    }
    if (empty($postData['display_name'])) {
        $newAccountQuery .= "'$postData[username]', '$postData[username]', '" . password_hash($postData['password'], PASSWORD_DEFAULT) . "');";
    } else {
        $newAccountQuery .= "'$postData[username]', '$postData[display_name]', '" . password_hash($postData['password'], PASSWORD_DEFAULT) . "');";
    }
    if (!mysqli_query($db, $newAccountQuery)) {
        throw new Exception("Could not create the account.", 3);
    }
    $response['response'] = "sign_up_success";
} catch (\Throwable $th) {
    switch ($th->getCode()) {
        case 0:
            $response['response'] = "user_already_exists";
            break;
        case 1:
            $response['response'] = "no_username";
            break;
        case 2:
            $response['response'] = "no_password";
            break;
        case 3:
            $response['response'] = "table_creation_failed";
            break;
        default:
            $response['response'] = "unknown_error";
            break;
    }
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>