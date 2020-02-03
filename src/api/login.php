<?php
// Include CORS headers
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";
http_response_code(200);
// Prepare the response associative array
$response = [];
$postData = json_decode(file_get_contents("php://input"), true);
try {
// Server receives a POST request from the user
if (isset($postData['username']) && isset($postData['password'])) {
    $username = $postData['username'];
    $password = $postData['password'];
} else {
    throw new Exception("no_username_or_password");
}
// Schema of request: 
/*
    "username": <username>
    "password": <hashed password>
*/
// Server checks if the user is in the database and if he is active.
$isUserInDatabaseQuery = "SELECT `id`, `is_active` FROM `user` WHERE `name` = '" . $username . "'";
$data = mysqli_query($db, $isUserInDatabaseQuery);
$row = mysqli_fetch_assoc($data);
if ($row && $row['is_active'] == 1) {
// If it is:
    // Check if the password the user sent matches the hashed password stored in the database
    $isUserPasswordSameAsHashedQuery = "SELECT `id`,`name`, `display_name`, `password_hash` FROM `user` WHERE `id` = '" . $row['id'] . "'";
    $data = mysqli_query($db, $isUserPasswordSameAsHashedQuery);
    $row = mysqli_fetch_assoc($data);
    if (password_verify($password, $row['password_hash'])) {
        // If it is:
        // 1. Start a new session
        session_start();
        // 1.1 Check if the session was created
        // 2. Respond with the session ID as a cookie
        if (session_id()) {
            $sessionSaverQuery = "INSERT INTO `sessions`(`session_id`, `user_id`) VALUES ('" . session_id() . "','$row[id]')";
            if (!mysqli_query($db, $sessionSaverQuery)) {
                throw new Exception("could_not_update_session_table");
            } else {
                $response["response"] = "log_in_success";
                $response['user_info'] = [
                    'username' => $row['name'],
                    'display_name' => $row['display_name']
                ];
            }
        } else {
            throw new Exception("could_not_create_session");
        }
    } else {
        throw new Exception("password_not_match");
    }
} else {
    throw new Exception("user_not_in_database_or_inactive");
}
} catch (\Throwable $th) {
    $response['response'] = "log_in_fail";
    $response['description'] = $th->getMessage();
    $response['code_line'] = $th->getLine();
} finally {
    echo json_encode($response);
}
?>