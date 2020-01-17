<?php
// Include CORS headers
require_once "../includes/is_json.php";
require_once "../includes/is_cors.php";
require_once "../includes/database_connection.php";

$response;
try {
    // Check if the user already exists
    $alreadyExistsQuery = "SELECT `id` FROM `user` WHERE `name` = '" . $_POST['username'] . "'";
    $data = mysqli_query($db, $alreadyExistsQuery);
    $row = mysqli_fetch_assoc($data);
    if ($row) {
        throw new Exception("user_already_exists");
    }
    $newAccountQuery = "INSERT INTO `user` (`name`, `display_name`, `password_hash`) VALUES (";
    if (empty($_POST['username'])) {
        throw new Exception("no_username");
    }
    if (empty($_POST['password'])) {
        throw new Exception("no_password");
    }
    if (empty($_POST['display_name'])) {
        $newAccountQuery .= "'$_POST[username]', '$_POST[username]', '" . password_hash($_POST['password'], PASSWORD_DEFAULT) . "');";
    } else {
        $newAccountQuery .= "'$_POST[username]', '$_POST[display_name]', '" . password_hash($_POST['password'], PASSWORD_DEFAULT) . "');";
    }
    if (!mysqli_query($db, $newAccountQuery)) {
        throw new Exception("table_creation_failed");
    }
    $response['response'] = "sign_up_success";
    // $response['description'] = $newAccountQuery;
} catch (\Throwable $th) {
    http_response_code(401);
    $response['response'] = "sign_up_fail";
    $response['description'] = $th->getMessage();
} finally {
    echo json_encode($response);
}
?>