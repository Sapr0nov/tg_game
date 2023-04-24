<?PHP
/**
*   tg game bot v2
*   17.04.2023
**/

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
$error = "";


$SITE_DIR = dirname(__FILE__) . "/";
require_once($SITE_DIR . 'env.php'); 
require_once($SITE_DIR . 'tg.class.php');
require_once($SITE_DIR . 'alias.class.php');

$tgBot = new TgBotClass($BOT_TOKEN, $SQL_SERVER, $SQL_USER, $SQL_PSWD, $SQL_DB, $TABLE);
$gameAlias = new AliasClass($SQL_SERVER, $SQL_USER, $SQL_PSWD, $SQL_DB);
$mysqli = $tgBot->MYSQLI;

$dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив
$tgBot->get_data($dataInput);

$tgBot->debug(json_encode($tgBot->MSG_INFO));
// если указан пользователь проверяем его наличие в базе
if ($tgBot->MSG_INFO["user_id"] != 0) {
    $sql = "SELECT `id` FROM `users` WHERE `tid` = '" . $tgBot->MSG_INFO["user_id"] . "';";
    $result = $mysqli->query($sql);
    if ($result->num_rows < 1) { // если не найден - добавляем
        $sql = "INSERT INTO `users` (`tid`, `username`, `first_name`, `last_name`) VALUE (" . $tgBot->MSG_INFO["user_id"] . ", '" . $tgBot->MSG_INFO["from_username"]  . "', '" . $tgBot->MSG_INFO["from_first_name"] ."', '" . $tgBot->MSG_INFO["from_last_name"] . "');";
        $result = $mysqli->query($sql);
        $sql = "SELECT LAST_INSERT_ID();";
        $result = $mysqli->query($sql);                
    }
}else{
    // пользователь не передан - выходим
    return;
}
// получаем данные или для SELECT или для LAST INSERT
$row = $result->fetch_row();
$new_user_id = $row[0];

// выводим отписку для группы и прекращаем скрипт
if ($tgBot->MSG_INFO["type"] != "private" && $tgBot->MSG_INFO["type"] !== null) {
    $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], "Я не работаю в группах - напиши мне лично");
    return;
}

if ($new_user_id > 0) {
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $tgBot->MSG_INFO["message_id"] . ", " . $new_user_id . ", " . $tgBot->MSG_INFO["chat_id"] . ", '" . $tgBot->MSG_INFO["text"] . "');";
    $result = $mysqli->query($sql);    
}


if ($tgBot->MSG_INFO['msg_type'] == 'message') {
    $reply_markup_start = $tgBot->keyboard([["Начать игру", "Настройки"],["Статистика", "Правила", "Очистить чат"]]);
    $reply_markup_room = $tgBot->keyboard([["Мои комнаты", "Создать комнату"],["Присоединиться"]]);

    if ($tgBot->MSG_INFO["text"] == 'Очистить чат') {
        $sql = "SELECT `msg_id` FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 
        while ($row = $result->fetch_row()) {
            $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $row[0]);
        }
        $sql = "DELETE FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 

        $text_return = "выберите дейстие:";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
    
        return; 
    }    
    if ($tgBot->MSG_INFO["text"] == 'Правила') {
        $text_return = "Игра для 4х и более человек. Игроки делаятся на две команды и пытаются объяснить друг другу слова. Следуйте подсказкам на экране";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return;
    }
    if ($tgBot->MSG_INFO["text"] == 'Настройки') {
        $text_return = "В разработке";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return;
    }
    if ($tgBot->MSG_INFO["text"] == 'Статистика') {
        $text_return = "Появится в следующих выпусках";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return;
    }

    if ($tgBot->MSG_INFO["text"] == 'Присоединиться') {
        $reply_markup = $tgBot->keyboard([["Войти", "Назад"]]);
        $text_return = "Введите номер комнаты:";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        return;
    }

    if ($tgBot->MSG_INFO["text"] == 'Создать комнату') {
        $reply_markup = $tgBot->keyboard([["Войти", "Удалить"]]);
        $text_return = "Создана комната № " . 1231;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        return;
    }

    
    if ($tgBot->MSG_INFO["text"] == 'Начать игру') {
        $sql = "SELECT `id` FROM `games` WHERE `chat_id` = ". $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);
        if ($result->num_rows>0) {
            while ($row = $result->fetch_row()) {
                $sql = "DELETE FROM `games` WHERE `id` = " . $row[0];
                $mysqli->query($sql);
            }
        }
        
        $gameAlias->create_word_list('basic',20);        
        
        $sql = "INSERT INTO `games` (`chat_id`, `word_number`, `score1`, `score2`, `active_team`, `team1`, `team2`, `team1_lead`, `team2_lead`, `dictionary_id`, `word_list`) VALUE(" . 
        $tgBot->MSG_INFO['chat_id']  . ", 0, 0, 0, 0, '{\"players\":[]}', '{\"players\":[]}', 0, 0, 1, '" . json_encode($gameAlias->gen_list, JSON_UNESCAPED_UNICODE) . "')";
        $result = $mysqli->query($sql); 

        $text_return = "начать игру:"; //TODO
        $reply_markup = $tgBot->inline_keyboard([
            [   ['text' => 'Играть в 1ой команде', 'callback_data' => 'team1'],
                ['text' => 'Играть во 2ой команде', 'callback_data' => 'team2']],
            [   ['text' => 'Первый раунд', 'callback_data' => 'play'],
                ['text' => 'Сменить словарь', 'callback_data' => 'dictionary']]
        ]);
         
        
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
        return;
    }

    // любое другое сообщение удаляем и предлагаем сделать выбор
    $text_return = "выберите дейстие:";
    $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
    $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
   
    
// этот кусок отлавливает нажатия кнопок под сообщением (если они были посланы)
}elseif($tgBot->MSG_INFO['msg_type'] == 'callback') {   
    //  получение переменных из базы
    $gameAlias->get_game($tgBot->MSG_INFO["chat_id"]);
    if ($gameAlias->game->error) {
        $text_return = $tgBot->MSG_INFO["text"] . "Ошибка:" . $gameAlias->game->error;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
        return;
    }
    // Кнопки под сообщения (отгадали / пропустить / определение)
    $reply_markup =  json_encode(array(
        'inline_keyboard' => array(
            array(
                array(
                    'text' => 'Угадали',
                    'callback_data' => 'win',
                ),
                array(
                    'text' => 'Пропустить',
                    'callback_data' => 'lose',
                ),
                array(
                    'text' => 'Значение',
                    'callback_data' => 'description',
                ),
            ),
        ),
    ));
    $reply_markup_without_desc =  json_encode(array(
        'inline_keyboard' => array(
            array(
                array(
                    'text' => 'Угадали',
                    'callback_data' => 'win',
                ),
                array(
                    'text' => 'Пропустить',
                    'callback_data' => 'lose',
                ),
            ),
        ),
    ));


    // если были нажаты кнопки присоединться к команде >|
    if ($tgBot->MSG_INFO["text"] == 'team1' || $tgBot->MSG_INFO["text"] == 'team2') {
        while (($key = array_search($tgBot->MSG_INFO["user_id"], $gameAlias->game->team1->players)) !== FALSE) {
            array_splice($gameAlias->game->team1->players,$key, 1);
        }
        while (($key = array_search($tgBot->MSG_INFO["user_id"], $gameAlias->game->team2->players)) !== FALSE) {
            array_splice($gameAlias->game->team2->players,$key, 1);
        }

        if ($tgBot->MSG_INFO["text"] == 'team1') {
            $text_return = $tgBot->MSG_INFO['name'] . " присоединился к первой команде.";
            $gameAlias->game->team1->players[] = $tgBot->MSG_INFO["user_id"];
        }else{
            $text_return = $tgBot->MSG_INFO['name'] . " присоединился ко второй команде.";
            $gameAlias->game->team2->players[] = $tgBot->MSG_INFO["user_id"];
        }

        $sql = "UPDATE `games` SET `team1` = '" . json_encode($gameAlias->game->team1, JSON_UNESCAPED_UNICODE) . "', `team2` = '" . json_encode($gameAlias->game->team2, JSON_UNESCAPED_UNICODE) . "' WHERE `id` = '" . $gameAlias->game->id . "'";
        $result = $mysqli->query($sql); 
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
        return;
    }
    
    // если была нажата кнопка выбора словаря >|
    if ($tgBot->MSG_INFO["text"] == 'dictionary') {
        $text_return = "Выбор словаря пока не доступен. Функция в разработке.";
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
        return;
    }

    // если запрошено описание слова выводим его >|
    if ($tgBot->MSG_INFO["text"] == 'description') {
        $text_return = $gameAlias->game->word_list[$gameAlias->game->word_number]->word . ": " . $gameAlias->game->word_list[$gameAlias->game->word_number]->description;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
        return;
    }

    // если была нажата кнопка начала раунда
    //TODO remove btn присоединиться к команде
    // Блоки win и lose имеют общий вывод в конце
    if ($tgBot->MSG_INFO["text"] == 'play') {
        // меняем играющую команду
        $gameAlias->game->active_team = ($gameAlias->game->active_team == 1) ? 2 : 1;

        // ищем следующего ведущего в играющей команде
        if ($gameAlias->game->active_team == 1) {
            $team = $gameAlias->game->team1;
            $team_lead = $gameAlias->game->team1_lead;
        }else{
            $team = $gameAlias->game->team2;
            $team_lead = $gameAlias->game->team2_lead;
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

        $sql = "UPDATE `games` SET `active_team` = " . $gameAlias->game->active_team . ", `team" . $gameAlias->game->active_team . "_lead` = " . $team_lead . " WHERE `id` = '" . $gameAlias->game->id . "'";
        $result = $mysqli->query($sql); 

        $sql = "SELECT `username`, `first_name`, `last_name` FROM `users` WHERE `tid` = " . $team_lead . ";";
        $result = $mysqli->query($sql); 
        $row = $result->fetch_row();
        $team_lead_name = ($row[0] != "") ? $row[0] : $row[1] . " " . $row[2];


        $text_return = "Объясняет " . $gameAlias->game->active_team . "я команда. Ведущий: " . $team_lead_name;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return . $team_lead);
        // и задаем сообщение для личного чата ведущего
        $text_return = "слово для объяснения: " . $gameAlias->game->word_list[$gameAlias->game->word_number]->word;

        if (strlen($gameAlias->game->word_list[$gameAlias->game->word_number]->description) > 0) {
            $tgBot->msg_to_tg($team_lead, $text_return, $reply_markup);
        }else{
            $tgBot->msg_to_tg($team_lead, $text_return, $reply_markup_without_desc);
        }
    
        return;
    }


    // если была нажата кнопка Угадали
    if ($tgBot->MSG_INFO["text"] == 'win') {
        $gameAlias->game->word_number++;
        $text_return = "Следующее слово: " . $gameAlias->game->word_list[$gameAlias->game->word_number]->word;
    }

    // если была нажата кнопка Пропустить
    if ($tgBot->MSG_INFO["text"] == 'lose') {
        $gameAlias->game->word_number++;
        $text_return = "Слово на замену: " . $gameAlias->game->word_list[$gameAlias->game->word_number]->word;
    }

    $team_lead = ($gameAlias->game->active_team == 2) ? $gameAlias->game->team2_lead : $gameAlias->game->team1_lead;

    $sql = "UPDATE `games` SET `word_number` = " . $gameAlias->game->word_number . " WHERE `id` = '" . $gameAlias->game->id . "';";
    $result = $mysqli->query($sql);
    
    if (strlen($gameAlias->game->word_list[$gameAlias->game->word_number]->description) > 0) {
        $tgBot->msg_to_tg($team_lead, $text_return, $reply_markup);
    }else{
        $tgBot->msg_to_tg($team_lead, $text_return, $reply_markup_without_desc);
    }

}

?>