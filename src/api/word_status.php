<?php
// Include CORS headers
header('Content-Type: application/json');
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";
http_response_code(200);

$response = [];

try {
    session_start();
    $isUserLoggedInQuery = "SELECT `id`, `name`, `display_name` FROM `user` WHERE `id` = (SELECT `user_id` FROM `sessions` WHERE `session_id` = '" . session_id() . "')";
    $loginResult = mysqli_query($db, $isUserLoggedInQuery);
    $loginData = mysqli_fetch_assoc($loginResult);
    if ($loginData) {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $postData = json_decode(file_get_contents("php://input"), true);
                $statusQuery = "SELECT word_status FROM userwordstatistics WHERE user_id = $loginData[id] AND word_id = $postData[id]";
                if ($result = mysqli_query($db, $statusQuery)) {
                    $row = mysqli_fetch_assoc($result);
                    $response['status'] = $row['word_status'];
                } else {
                    throw new Error("Could not execute query.", 2);
                }
                $response['response'] = "get_word_status_success";
                break;
            case 'PUT':
                $postData = json_decode(file_get_contents("php://input"), true);
                $statusQuery = "UPDATE userwordstatistics SET `word_status` = '$postData[word_status]' WHERE user_id = $loginData[id] AND word_id = $postData[id]";
                if (!mysqli_query($db, $statusQuery)) {
                    throw new Error("Could not execute query.", 2);
                }
                $response['response'] = "set_word_status_success";
                break;
            default:
                throw new Exception("Unsupported request.", 1);
                break;
        }
    } else {
        throw new Exception("User is not logged in or doesn't exist.", 0);
    }
} catch (\Throwable $th) {
    switch ($th->getCode()) {
        case 0:
            $response['response'] = "no_user_or_not_logged_in";
            break;
        case 1:
            $response['response'] = "unsupported_request";
            break;
        case 2:
            $response['response'] = "get_word_status_failed";
            break;
        case 3:
            $response['response'] = "update_word_status_failed";
            break;
        default:
            $response['response'] = "unknown_error";
            break;
    }
    $response['description'] = $th->getMessage();
    $response['code_line'] = $th->getLine();
} finally {
    echo json_encode($response);
}
?>