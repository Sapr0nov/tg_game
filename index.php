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
        $sql = "DELETE FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 

        return; 
    }    
    if ($tgBot->MSG_INFO["text"] == 'Правила') {
        $text_return = "Игра для 4х и более человек. Игроки делаятся на две команды и пытаются объяснить друг другу слова. Следуйте подсказкам на экране";
    }
    if ($tgBot->MSG_INFO["text"] == 'Настройки') {
        $text_return = "В разработке";
    }
    if ($tgBot->MSG_INFO["text"] == 'Статистика') {
        $text_return = "Появится в следующих выпусках";
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
        $sql = "SELECT `id` FROM `games` WHERE `chat_id` = ". $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);
        if ($result->num_rows>0) {
            while ($row = $result->fetch_row()) {
                $sql = "DELETE FROM `games` WHERE `id` = " . $row[0];
                $mysqli->query($sql);
            }
        }

        $sql = "INSERT INTO `games` (`chat_id`, `status`, `score1`, `score2`, `active_team`, `team1`, `team2`, `team1_lead`, `team2_lead`, `dictionary_id`) VALUE(" . 
        $tgBot->MSG_INFO['chat_id']  . ", 0, 0, 0, 0, '{\"players\":[]}', '{\"players\":[]}', 0, 0, 1)";
        $result = $mysqli->query($sql); 

        $text_return = "ожидаем сбора участников";
        $reply_markup =  json_encode(array(
            'inline_keyboard' => array(
                array(
                    array(
                        'text' => 'Играть в 1ой команде',
                        'callback_data' => 'team1',
                    ),
                    array(
                        'text' => 'Играть во 2ой команде',
                        'callback_data' => 'team2',
                    ),
                ),
                array(
                    array(
                        'text' => 'Первый раунд',
                        'callback_data' => 'play',
                    ),
                    array(
                        'text' => 'Сменить словарь',
                        'callback_data' => 'dictionary',
                    ),
                )

            ),
        ));
    }

    $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
    
// этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
}elseif($tgBot->MSG_INFO['msg_type'] == 'callback') {   
    // этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
    // есть есть нажатие кнопок клавиатуры (под сообщением)

    $sql = "SELECT `id`, `team1`, `team2`,`active_team`, `team1_lead`, `team2_lead` FROM `games` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
    $result = $mysqli->query($sql); 
    $row = $result->fetch_row();
    $id = $row[0];
    $team1 = json_decode($row[1]);
    $team2 = json_decode($row[2]);
    $team1->players = array_unique($team1->players);
    $team2->players = array_unique($team2->players);
    $active_team = $row[3];
    $team1_lead = $row[4];
    $team2_lead = $row[5];

    // если были нажаты кнопки присоединться к команде
    if ($tgBot->MSG_INFO["text"] == 'team1' || $tgBot->MSG_INFO["text"] == 'team2') {        
        while (($key = array_search($tgBot->MSG_INFO["user_id"], $team1->players)) !== FALSE) {
            array_splice($team1->players,$key,1);
        }
        while (($key = array_search($tgBot->MSG_INFO["user_id"], $team2->players)) !== FALSE) {
            array_splice($team2->players,$key,1);
        }

        if ($tgBot->MSG_INFO["text"] == 'team1') {
            $text_return = $tgBot->MSG_INFO['name'] . " присоединился к первой команде.";
            $team1->players[] = $tgBot->MSG_INFO["user_id"];
        }else{
            $text_return = $tgBot->MSG_INFO['name'] . " присоединился ко второй команде.";
            $team2->players[] = $tgBot->MSG_INFO["user_id"];
        }

        $sql = "UPDATE `games` SET `team1` = '" . json_encode($team1) . "', `team2` = '" . json_encode($team2) . "' WHERE `id` = '" . $id . "'";
        $result = $mysqli->query($sql); 
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
    }
    
    // если была нажата кнопка выбора словаря
    if ($tgBot->MSG_INFO["text"] == 'dictionary') {
        $text_return = "Выбор словаря пока не доступен. Функция в разработке.";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
    }
    // если была нажата кнопка начала раунда
    //TODO remove btn присоединиться к команде
    if ($tgBot->MSG_INFO["text"] == 'play') {
        // меняем играющую команду
        $active_team = ($active_team == 1) ? 2 : 1;
        
        // ищем следующего ведущего в играющей команде
        if ($active_team == 1) {
            $team = $team1;
            $team_lead = $team1_lead;
        }else{
            $team = $team2;
            $team_lead = $team2_lead;
        }

        $team_lead_key = array_search($team_lead, $team->players);
        // если текущий ведущий не найден, ставим первого
        if ($team_lead_key === FALSE) { 
            $team_lead_key = 0;
        }else{ // иначе сдвигаем на следующего
            if (count($team->players) > $team_lead_key + 1) {
                $team_lead_key++;
            }else{
                $team_lead_key = 0;
            }
        }
        $team_lead = $team->players[$team_lead_key];


        $sql = "UPDATE `games` SET `active_team` = " . $active_team . ", `team" . $active_team . "_lead` = " . $team_lead . " WHERE `id` = '" . $id . "'";
        $result = $mysqli->query($sql); 

        $sql = "SELECT `username`, `first_name`, `last_name` FROM `users` WHERE `tid` = " . $team_lead . ";";
        $result = $mysqli->query($sql); 
        $row = $result->fetch_row();
        $team_lead_name = ($row[0] != "") ? $row[0] : $row[1] . " " . $row[2];

        $text_return = "Объясняет " . $active_team . "я команда. Ведущий: " . $team_lead_name;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);

        $text = "слово для объяснения:" . "ПОДУШКА";
        $tgBot->msg_to_tg($team1->players[0], $text);
    }    
}

?>