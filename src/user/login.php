<?php
// Include CORS headers
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
try {
// Prepare the response associative array
$response;
// Server receives a POST request from the user
if (isset($_POST['username']) && isset($_POST['hashed_password'])) {
    $response['username'] = $_POST['username'];
    $response['hashed_password'] = $_POST['hashed_password'];
} else {
    throw new Exception("User did not provide username or password.", 1);
}
// Schema of request: 
/*
    "username": <username>
    "hashed_password": <hashed password>
*/
// Server checks if the user is in the database and if the hashed password the user sent matches the hashed password stored in the database
// If it is:
    // 1. Start a new session
session_start();
    // 1.1 Check if the session was created
if (session_id()) {
    // 2. Respond with a 200 code and with the session ID as a cookie
    $response["response"] = "log_in_success";
    $response["description"] = "You have succesfully logged in.";
} else {
    throw new Exception("Could not create new session.", 1);
}
// 3. Mark the user as active in the database
http_response_code(200); // Mark the response as a success
} catch (\Throwable $th) {
    http_response_code(401);
    $response['response'] = "log_in_fail";
    $response['reason'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>