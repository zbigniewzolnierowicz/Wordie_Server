<?php
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";
try {
    session_start();
    if (session_id() != '') { // Check if the session exists
        $deleteSessionQuery = "DELETE FROM `sessions` WHERE `session_id` = '" . session_id() . "';";
        if (!mysqli_query($db, $deleteSessionQuery)) {
            throw new Exception("could_not_delete_session_from_db");
        } else {
            $_SESSION = array();
            setcookie(session_name(), "", time() - 86400, "/");
            session_destroy();
            // Check if the session is destroyed
            if (session_id()) {
                throw new Exception("session_not_destroyed");
            } else {
                http_response_code(200);
                $response['response'] = "log_out_success";
            }
        }
    } else {
        throw new Exception("not_logged_in");
    }
} catch (\Throwable $th) {
    http_response_code($_SERVER['REQUEST_METHOD'] === "OPTIONS" ? 200 : 500);
    $response['response'] = "log_out_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>