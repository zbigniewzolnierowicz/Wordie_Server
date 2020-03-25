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
    $result = mysqli_query($db, $isUserLoggedInQuery);
    $data = mysqli_fetch_assoc($result);
    if ($data) {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $getAllCardsQuery = "
                    SELECT
                        w.id,
                        translation_en.translation en,
                        translation_pl.translation pl,
                        uws.word_status,
                        w.category
                    FROM
                        word w
                    INNER JOIN(
                        SELECT
                            *
                        FROM
                            translatedword
                        WHERE
                            translatedword.languageCode = 'en'
                    ) translation_en
                    ON
                        w.id = translation_en.word_id
                    INNER JOIN(
                        SELECT
                            *
                        FROM
                            translatedword
                        WHERE
                            translatedword.languageCode = 'pl'
                    ) translation_pl
                    ON
                        w.id = translation_pl.word_id
                    INNER JOIN (
                        SELECT
                            word_id,
                            word_status
                        FROM
                            userwordstatistics
                        WHERE
                            user_id = $data[id]
                    ) uws
                    ON
                        w.id = uws.word_id
                ";
                $words = array();
                if ($result = mysqli_query($db, $getAllCardsQuery)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        array_push($words, $row);
                    }
                    mysqli_free_result($result);
                }
                $response["response"] = "get_words_success";
                $response["words"] = $words;
                break;
            case 'POST':
                $postData = json_decode(file_get_contents("php://input"), true);
                $createInitialWordQuery = "INSERT INTO `word`(`created_by`) VALUES ($data[id])";
                if (!empty($postData['id'])) {
                    $getWordQuery = "
                        SELECT
                            w.id,
                            translation_en.translation en,
                            translation_pl.translation pl,
                            uws.word_status
                        FROM
                            word w
                        INNER JOIN(
                            SELECT
                                *
                            FROM
                                translatedword
                            WHERE
                                translatedword.languageCode = 'en'
                        ) translation_en
                        ON
                            w.id = translation_en.word_id
                        INNER JOIN(
                            SELECT
                                *
                            FROM
                                translatedword
                            WHERE
                                translatedword.languageCode = 'pl'
                        ) translation_pl
                        ON
                            w.id = translation_pl.word_id
                        INNER JOIN (
                            SELECT
                                word_id,
                                word_status
                            FROM
                                userwordstatistics
                            WHERE
                                user_id = $data[id]
                        ) uws
                        ON
                            w.id = uws.word_id
                        WHERE
                            w.id = $postData[id];
                    ";
                    $result = mysqli_query($db, $getWordQuery);
                    if ($word = mysqli_fetch_assoc($result)) {
                        $response['response'] = "get_word_success";
                        $response['word'] = $word;
                    }
                } else {
                    if (mysqli_query($db, $createInitialWordQuery)) {
                        $id = mysqli_insert_id($db);
                        $createEnglishTranslation = "INSERT INTO `translatedword`(`word_id`, `translation`, `languageCode`) VALUES ($id,'$postData[translation_en]','en')";
                        $createPolishTranslation = "INSERT INTO `translatedword`(`word_id`, `translation`, `languageCode`) VALUES ($id,'$postData[translation_pl]','pl')";
                        if (mysqli_query($db, $createEnglishTranslation) && mysqli_query($db, $createPolishTranslation)) {
                            $response['response'] = "insert_word_success";
                            $response['word_id'] = $id;
                        } else {
                            throw new Exception("Insertion of the word failed.", 2);
                        }
                    } else {
                        throw new Exception("Insertion of the word failed.", 2);
                    }
                    $response['word'] = $id;
                }
                break;
            case 'PUT':
                $postData = json_decode(file_get_contents("php://input"), true);
                $response['query'] = [];
                if (!empty($postData['id'])) {
                    foreach ($postData['translations'] as $index => $value) {
                        if (!mysqli_query($db, "UPDATE `translatedword` SET `translation`= '$value[translation]' WHERE `languageCode` = '$value[language]' AND `word_id` = $postData[id]")) {
                            throw new Exception("Translation update failed.", 3);
                        }
                    }
                    $response['response'] = "update_word_success";
                }
                break;
            case 'DELETE':
                $postData = json_decode(file_get_contents("php://input"), true);
                $response['query'] = [];
                if (!empty($postData['id'])) {
                    if (!mysqli_query($db, "DELETE FROM `word` WHERE `id` = $postData[id]")) {
                        throw new Exception("Word deletion failed.", 4);
                    }
                    $response['response'] = "delete_word_success";
                }
                break;
            default:
                throw new Exception('Unsupported HTML request.', 1);
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
            $response['response'] = "insert_word_failed";
            break;
        case 3:
            $response['response'] = "update_word_failed";
            break;
        case 4:
            $response['response'] = "delete_word_failed";
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