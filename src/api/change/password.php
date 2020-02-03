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
                throw new Exception("query_fail");
            }
            $response['response'] = "password_change_success";
        } else {
            throw new Exception("wrong_old_password");
        }
    } else {
        throw new Exception("wrong_user_or_not_logged_in");
    }
} catch (\Throwable $th) {
    $response['response'] = "password_change_fail";
    $response['description'] = $updatePasswordNameQuery;
} finally {
    echo json_encode($response);
}
?>