<?PHP
/**
*   simple chat bot 
*   03.04.2023
*/

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
$error = "";


$SITE_DIR = dirname(__FILE__) . "/";
require_once($SITE_DIR . 'env.php'); 
require_once($SITE_DIR . 'tg.php');

$tgBot = new TgBotClass($BOT_TOKEN, $SQL_SERVER, $SQL_PSWD, $SQL_USER, $SQL_DB, $TABLE);
$mysqli = $tgBot->MYSQLI;

$dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив
$tgBot->get_data($dataInput);
$tgBot->debug($tgBot->MSG_INFO["chat_id"]);
// получаем все данные
$update_id = $data['update_id'];
if (isset($data['message'])) {

    $user_id = isset($data['message']['from']['id']) ? $data['message']['from']['id'] : 0;
    $chat_id = isset($data['message']['chat']['id']) ? $data['message']['chat']['id'] : 0;
    $message_id = $data["message"]["message_id"];
    $from_first_name = isset($data["message"]["from"]['first_name']) ? $data["message"]["from"]['first_name'] : "";
    $from_last_name = isset($data["message"]["from"]['last_name']) ? $data["message"]["from"]['last_name'] : "";
    $from_username = isset($data["message"]["from"]['username']) ? $data["message"]["from"]['username'] : "";
    $type = $data["message"]["chat"]['type'];
    $text = $data['message']["text"];
    $date = $data['message']["date"];       
}
// если был ответ под кнопкой
if (isset($data['callback_query'])) {
    $user_id = isset($data['callback_query']['from']['id']) ? $data['callback_query']['from']['id'] : 0;
    $chat_id = isset($data['callback_query']["message"]['chat']['id']) ? $data['callback_query']["message"]['chat']['id'] : 0;
    $message_id = $data["callback_query"]["message"]["message_id"];
    $from_first_name = isset($data["callback_query"]["from"]['first_name']) ? $data["callback_query"]["from"]['first_name'] : "";
    $from_last_name = isset($data["callback_query"]["from"]['last_name']) ? $data["callback_query"]["from"]['last_name'] : "";
    $from_username = isset($data["callback_query"]["from"]['username']) ? $data["callback_query"]["from"]['username'] : "";
    $type = $data["callback_query"]["chat"]['type'];
    $text = $data["callback_query"]["data"];
    $date = $data["callback_query"]["date"];
}

$name = ($from_first_name !== "") ? $from_first_name . " " . $from_last_name : $from_username;

// если указан пользователь проверяем его наличие в базе
if ($user_id != 0) {
    $sql = "SELECT `id` FROM `users` WHERE `tid` = '" . $user_id . "';";
    $result = $mysqli->query($sql);
    if ($result->num_rows < 1) {
        $sql = "INSERT INTO `users` (`tid`, `username`, `first_name`, `last_name`) VALUE (" . $user_id . ", '" . $from_username  . "', '" . $from_first_name ."', '" . $from_last_name . "');";
        $result = $mysqli->query($sql);
        $sql = "SELECT LAST_INSERT_ID();";
        $result = $mysqli->query($sql);
    }
}else{
    return;
}

$row = $result->fetch_row();
$new_user_id = $row[0];

if ($new_user_id > 0) {
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $message_id . ", " . $new_user_id . ", " . $tgBot->MSG_INFO["chat_id"] . ", '" . $text . "');";
    $result = $mysqli->query($sql);    
}

if (!empty($data['message']['text'])) {
    $chat_id = $data['message']['chat']['id'];
    $user_id = $data['message']['from']['id'];
    $user_name = $data['message']['from']['username'];
    $first_name = $data['message']['from']['first_name'];
    $last_name = $data['message']['from']['last_name'];
    $text = trim($data['message']['text']);
    $text_array = explode(" ", $text);
    
    $text_return = $text;
    
    if ($text == 'Очистить чат') {
        $sql = "SELECT `msg_id` FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 
        while ($row = $result->fetch_row()) {
            $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $row[0]);
        }
        $new_msg = $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], "-.- вжух");
        $sql = "DELETE FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 

        return; 
    }
    
    
    if ($text == 'Правила') {
        $text_return = "Игра для 4х и более человек. Игроки делаятся на две команды и пытаются объяснить друг другу слова. Следуйте подсказкам на экране";
    }

    $reply_markup =  json_encode(array(
        'keyboard' => array(
            array("Начать игру", "Настройка"),
            array("Статистика", "Правила", "Очистить чат"),
        ),
        'resize_keyboard' => true, 
        'one_time_keyboard' => true
        )
    );

    if ($text == 'Начать игру') {
        $reply_markup =  json_encode(array(
            'inline_keyboard' => array(
                array(
                    array(
                        'text' => 'Буду играть',
                        'callback_data' => 'player_agree',
                    ),
                    array(
                        'text' => 'Без меня',
                        'callback_data' => 'player_disagree',
                    ),
                )
            ),
        ));
    }
        // клавиатура под сообщением
        /*
        $reply_markup =  json_encode(array(
            'inline_keyboard' => array(
                array(
                    array(
                        'text' => 'Трудоустройство',
                        'url' => 'https://hh.ru',
                    ),
                    array(
                        'text' => 'Практика',
                        'callback_data' => 'internship',
                    ),
                ),
                array(
                    array(
                        'text' => 'Моя Река',
                        'url' => 'https://my-river.ru',
                    ),
                    array(
                        'text' => 'Наш сайт',
                        'url' => 'https://eipp.ru',
                    ),
                )
            ),
        ));
        */
    $new_msg = $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $message_id . ", " . 0 . ", " . $tgBot->MSG_INFO["chat_id"] . ", '" . $text . "');";
    $result = $mysqli->query($sql);

// этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
}elseif(!empty($data['callback_query'])) {    
    // этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
    // есть есть нажатие кнопок клавиатуры (под сообщением)
    if ($text == 'player_agree') {
        $text_return = $tgBot->MSG_INFO['name'] . " присоединился к игре.";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
    }
    if ($text == 'player_disagree') {
        $text_return = $tgBot->MSG_INFO['name'] . " не готов сейчас играть.";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
    }
}


?>