<?php
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
try {
    if (session_id() != '') { // Check if the session exists
        // Initialize the session.
        session_start();

        // Unset all of the session variables.
        $_SESSION = array();

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        $response = [];
        // Finally, destroy the session.
        session_destroy();

        // Check if the session is destroyed
        if (session_id()) {
            throw new Exception("session_not_destroyed");
        } else {
            http_response_code(200);
            $response['response'] = "log_out_success";
            $response['description'] = "log_out_success";
        }
    } else {
        throw new Exception("not_logged_in");
    }
} catch (\Throwable $th) {
    http_response_code(500);
    $response['response'] = "log_out_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>