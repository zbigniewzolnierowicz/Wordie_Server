<?php
// Include CORS headers
header('Content-Type: application/json');
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";
http_response_code(200);

try {
    $response = [];
    session_start();
    // Check if the user is logged in
    $isUserLoggedInQuery = "SELECT `id`, `name`, `display_name` FROM `user` WHERE `id` = (SELECT `user_id` FROM `sessions` WHERE `session_id` = '" . session_id() . "')";
    $result = mysqli_query($db, $isUserLoggedInQuery);
    $data = mysqli_fetch_assoc($result);
    if ($data) {
        $response['response'] = "get_user_info_success";
        $response['user_info'] = $data;
    }
} catch (\Throwable $th) {
    $response['response'] = "get_user_info_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>