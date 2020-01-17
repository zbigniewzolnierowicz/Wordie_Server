<?php
// Include CORS headers
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";
try {
// Prepare the response associative array
$response = [];
// Server receives a POST request from the user
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
} else {
    throw new Exception("no_username_or_password");
}
// Schema of request: 
/*
    "username": <username>
    "password": <hashed password>
*/
// Server checks if the user is in the database and if he is active.
$isUserInDatabaseQuery = "SELECT `id` FROM `user` WHERE `name` = '" . $username . "'";
$data = mysqli_query($db, $isUserInDatabaseQuery);
$row = mysqli_fetch_assoc($data);
if ($row['is_active'] == 1) {
// If it is:
    // Check if the password the user sent matches the hashed password stored in the database
    $isUserPasswordSameAsHashedQuery = "SELECT `password_hash` FROM `user` WHERE `id` = '" . $row['id'] . "'";
    $data = mysqli_query($db, $isUserPasswordSameAsHashedQuery);
    $row = mysqli_fetch_assoc($data);
    if (password_verify($password, $row['password_hash'])) {
        // If it is:
        // 1. Start a new session
        session_start();
        // 1.1 Check if the session was created
        // 2. Respond with a 200 code and with the session ID as a cookie
        http_response_code(200); // Mark the response as a success
        if (session_id()) {
            $response["response"] = "log_in_success";
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
    http_response_code(401);
    $response['response'] = "log_in_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>