<?PHP
/**
*   simple chat bot 
*   03.04.2023
*   для установки вебхука https://api.telegram.org/bot0000000000:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/setWebhook?url=https://your_site.org/your_bot_code.php
*/

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
$error = "";


$SITE_DIR = dirname(__FILE__) . "/" ; // путь к скрипту
require_once($SITE_DIR . 'env.php'); // наши токены и пароли

$mysqli = new mysqli($SQL_SERVER, $SQL_USER, $SQL_PSWD, $SQL_DB);
if ($mysqli->connect_errno) {
    $error = $mysqli->connect_error;
}

$dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив

//  <DEBUG>   
$file_message = file_get_contents($SITE_DIR . 'message.txt');
file_put_contents($SITE_DIR . 'message.txt',  $file_message . PHP_EOL . json_encode($data) . PHP_EOL);
//  </DEBUG>  

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
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $message_id . ", " . $new_user_id . ", " . $chat_id . ", '" . $text . "');";
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
        $sql = "SELECT `msg_id` FROM `messages` WHERE `chat_id` = '" . $chat_id . "'";
        $result = $mysqli->query($sql); 
        while ($row = $result->fetch_row()) {
            delete_msg_tg($BOT_TOKEN, $chat_id, $row[0]);
            $file_message = file_get_contents($SITE_DIR . 'message.txt');
            file_put_contents($SITE_DIR . 'message.txt',  $file_message . PHP_EOL . $sql . PHP_EOL . $row[0]);
        }
        $new_msg = msg_to_tg($BOT_TOKEN, $chat_id, "-.- вжух");
        $sql = "DELETE FROM `messages` WHERE `chat_id` = '" . $chat_id . "'";
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
    $new_msg = msg_to_tg($BOT_TOKEN, $chat_id, $text_return, $reply_markup);
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $message_id . ", " . 0 . ", " . $chat_id . ", '" . $text . "');";
    $result = $mysqli->query($sql);

// этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
}elseif(!empty($data['callback_query'])) {    
    // этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
    // есть есть нажатие кнопок клавиатуры (под сообщением)
    if ($text == 'player_agree') {
        $text_return = $name . " присоединился к игре.";
        msg_to_tg($BOT_TOKEN, $chat_id, $text_return);
    }
    if ($text == 'player_disagree') {
        $text_return = $name . " не готов сейчас играть.";
        msg_to_tg($BOT_TOKEN, $chat_id, $text_return);
    }
}


// функция отправки сообщени от бота в диалог с юзером
function msg_to_tg($BOT_TOKEN, $chat_id, $text, $reply_markup = '') {

    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => 'https://api.telegram.org/bot' . $BOT_TOKEN . '/sendMessage',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $text,
            'reply_markup' => $reply_markup,
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    $new_msg = curl_exec($ch);
    curl_close($ch);
    /*  <DEBUG>   */
    $file_message = file_get_contents($SITE_DIR . 'update.txt');
    file_put_contents($SITE_DIR . 'update.txt',  "newmsg = " . $new_msg . PHP_EOL );
    /*  </DEBUG>   */
    

    return $new_msg;
}


function delete_msg_tg($BOT_TOKEN, $chat_id, $msg_id) {

    $ch = curl_init();
    $ch_post = [
        CURLOPT_URL => 'https://api.telegram.org/bot' . $BOT_TOKEN . '/deleteMessage?chat_id=' . $chat_id . '&message_id=' . $msg_id,
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'text' => $text,
            'reply_markup' => $reply_markup,
        ]
    ];

    curl_setopt_array($ch, $ch_post);
    curl_exec($ch);
    curl_close($ch);
}


?>