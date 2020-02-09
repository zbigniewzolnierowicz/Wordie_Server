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
    // Check if the user is logged in
    $isUserLoggedInQuery = "SELECT `id`, `name` FROM `user` WHERE `id` = (SELECT `user_id` FROM `sessions` WHERE `session_id` = '" . session_id() . "')";
    $result = mysqli_query($db, $isUserLoggedInQuery);
    $data = mysqli_fetch_assoc($result);
    if ($data && $data['name'] == $postData['username']) {
        $updatedDisplayName = empty($postData['display_name']) ? $data['name'] : $postData['display_name'];
        $updateDisplayNameQuery = "UPDATE `user` SET `display_name` = '$updatedDisplayName' WHERE `id` = '$data[id]'";
        if (mysqli_query($db, $updateDisplayNameQuery)) {
            $response['response'] = "display_name_change_success";
        } else {
            throw new Exception("query_fail");
        }
    } else {
        throw new Exception("wrong_user_or_not_logged_in");
    }
} catch (\Throwable $th) {
    $response['response'] = "display_name_change_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>