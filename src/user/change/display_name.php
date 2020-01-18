<?php
// Include CORS headers
require_once "../../includes/is_json.php";
require_once "../../includes/is_cors.php";
require_once "../../includes/database_connection.php";

$response;
try {
    session_start();
    // Check if the user is logged in
    $isUserLoggedInQuery = "SELECT `id`, `name` FROM `user` WHERE `id` = (SELECT `user_id` FROM `sessions` WHERE `session_id` = '" . session_id() . "')";
    $result = mysqli_query($db, $isUserLoggedInQuery);
    $data = mysqli_fetch_assoc($result);
    if ($data && $data['name'] == $_POST['username']) {
        $updatedDisplayName = empty($_POST['display_name']) ? $data['name'] : $_POST['display_name'];
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
    http_response_code(401);
    $response['response'] = "display_name_change_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>