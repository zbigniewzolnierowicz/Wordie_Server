<?php
// Include CORS headers
require_once "../../includes/is_json.php";
require_once "../../includes/is_cors.php";
require_once "../../includes/database_connection.php";

$response;
$postData = json_decode(file_get_contents("php://input"), true);
http_response_code(200);
try {
    session_start();
    if (empty($postData['old_password'])) {
        throw new Exception("no_old_password");
    }
    if (empty($postData['new_password'])) {
        throw new Exception("no_new_password");
    }
    // Check if the user is logged in
    $isUserLoggedInQuery = "SELECT `id`, `name`, `display_name`, `password_hash` FROM `user` WHERE `id` = (SELECT `user_id` FROM `sessions` WHERE `session_id` = '" . session_id() . "')";
    $result = mysqli_query($db, $isUserLoggedInQuery);
    $data = mysqli_fetch_assoc($result);
    if ($data && $data['name'] == $postData['username']) {
        if (password_verify($postData['old_password'], $data['password_hash'])) { // Check if the hashed password in the database matches the old password provided by the user
            $updatedPassword = password_hash($postData['new_password'], PASSWORD_DEFAULT);
            $updatePasswordNameQuery = "UPDATE `user` SET `password_hash` = '$updatedPassword' WHERE `id` = '$data[id]';";
            if (!mysqli_query($db, $updatePasswordNameQuery)) {
                throw new Exception("query_fail", 0);
            }
            $response['response'] = "password_change_success";
        } else {
            throw new Exception("Your old password was incorrect.", 1);
        }
    } else {
        throw new Exception("You're trying to change the password of the wrong user or are not logged in.", 2);
    }
} catch (\Throwable $th) {
    switch ($th->getCode()) {
        case 0:
            $response['response'] = "query_fail";
            break;
        case 1:
            $response['response'] = "wrong_old_password";
            break;
        case 2:
            $response['response'] = "wrong_user_or_not_logged_in";
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