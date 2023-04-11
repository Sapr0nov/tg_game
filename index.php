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

// если указан пользователь проверяем его наличие в базе
if ($tgBot->MSG_INFO["user_id"] != 0) {
    $sql = "SELECT `id` FROM `users` WHERE `tid` = '" . $tgBot->MSG_INFO["user_id"] . "';";
    $result = $mysqli->query($sql);
    if ($result->num_rows < 1) {
        $sql = "INSERT INTO `users` (`tid`, `username`, `first_name`, `last_name`) VALUE (" . $tgBot->MSG_INFO["user_id"] . ", '" . $tgBot->MSG_INFO["from_username"]  . "', '" . $tgBot->MSG_INFO["from_first_name"] ."', '" . $tgBot->MSG_INFO["from_last_name"] . "');";
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
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $tgBot->MSG_INFO["message_id"] . ", " . $new_user_id . ", " . $tgBot->MSG_INFO["chat_id"] . ", '" . $tgBot->MSG_INFO["text"] . "');";
    $result = $mysqli->query($sql);    
}


if ($tgBot->MSG_INFO['msg_type'] == 'message') {
 
    $text_return = $tgBot->MSG_INFO["text"];
    
    if ($tgBot->MSG_INFO["text"] == 'Очистить чат') {
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
    
    
    if ($tgBot->MSG_INFO["text"] == 'Правила') {
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

    if ($tgBot->MSG_INFO["text"] == 'Начать игру') {
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

    $new_msg = $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $tgBot->MSG_INFO["message_id"] . ", " . 0 . ", " . $tgBot->MSG_INFO["chat_id"] . ", '" . $tgBot->MSG_INFO["text"] . "');";
    $result = $mysqli->query($sql);

// этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
}elseif($tgBot->MSG_INFO['msg_type'] == 'callback') {   
    $tgBot->debug($tgBot->MSG_INFO["text"]);
    // этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
    // есть есть нажатие кнопок клавиатуры (под сообщением)
    
    if ($tgBot->MSG_INFO["text"] == 'player_agree') {
        $text_return = $tgBot->MSG_INFO['name'] . " присоединился к игре.";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
    }
    if ($tgBot->MSG_INFO["text"] == 'player_disagree') {
        $text_return = $tgBot->MSG_INFO['name'] . " не готов сейчас играть.";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
    }
}

?>