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
    throw new Exception("Username or password were not provided.", 0);
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
    $isUserPasswordSameAsHashedQuery = "SELECT `id`,`name`, `display_name`, `password_hash`, `role` FROM `user` WHERE `id` = '" . $row['id'] . "'";
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
                    'display_name' => $row['display_name'],
                    'role' => $row['role']
                ];
            }
        } else {
            throw new Exception("Could not create a session. Try again.", 1);
        }
    } else {
        throw new Exception("Wrong password.", 2);
    }
} else {
    throw new Exception("The user doesn't exist or was deactivated.", 3);
}
} catch (\Throwable $th) {
    switch ($th->getCode()) {
        case 0:
            $response['response'] = "no_username_or_password";
            break;
        case 1:
            $response['response'] = "could_not_create_session";
            break;
        case 2:
            $response['response'] = "password_not_match";
            break;
        case 3:
            $response['response'] = "user_not_in_database_or_inactive";
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