<?PHP
/**
*   tg game bot v2
*   17.04.2023
**/

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8

$SITE_DIR = dirname(__FILE__) . "/";
require_once($SITE_DIR . 'env.php'); 
require_once($SITE_DIR . 'i18n.php'); 
require_once($SITE_DIR . 'tg.class.php');
require_once($SITE_DIR . 'alias.class.php');

$dict_name = 'basic';
$tgBot = new TgBotClass($BOT_TOKEN, $SQL_SERVER, $SQL_USER, $SQL_PSWD, $SQL_DB, $TABLE);
$gameAlias = new AliasClass($SQL_SERVER, $SQL_USER, $SQL_PSWD, $SQL_DB);
$mysqli = $tgBot->MYSQLI;

$dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив
$tgBot->get_data($dataInput);

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
// если бот в группе - выводим отписку для группы и прекращаем скрипт
if ($tgBot->MSG_INFO["type"] != "private" && $tgBot->MSG_INFO["type"] !== null) {
    $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $ERROR["onlyPrivate"]);
    return;
}
// Сохраняем полученное сообщение в базу 
if ($new_user_id > 0) {
    $sql = "INSERT INTO `messages` (`msg_id`, `user_id`,`chat_id`,`text`) VALUE (" . $tgBot->MSG_INFO["message_id"] . ", " . $new_user_id . ", " . $tgBot->MSG_INFO["chat_id"] . ", '" . $tgBot->MSG_INFO["text"] . "');";
    $result = $mysqli->query($sql);    
}

// получаем статус пользователя
$sql = "SELECT `status`, `game_id` FROM `users` WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
$result = $mysqli->query($sql);
$row = $result->fetch_row();
$status = $row[0];
$room = $row[1];

if ($tgBot->MSG_INFO['msg_type'] == 'message') {
    $reply_markup_start = $tgBot->keyboard([[$BTNS['startGame'], $BTNS['join']],[$BTNS['settings'], $BTNS['rules'], $BTNS['clear']]]);

    // like clear 
    if ($tgBot->MSG_INFO["text"] == $BTNS['back']) {
        $sql = "SELECT `msg_id` FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 
        while ($row = $result->fetch_row()) {
            $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $row[0]);
        }
        $sql = "DELETE FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 

        $text_return = $RETURNTXT['selectAction'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return; 
    }
    if ($tgBot->MSG_INFO["text"] == $BTNS['clear']) {
        $sql = "SELECT `msg_id` FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 
        while ($row = $result->fetch_row()) {
            $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $row[0]);
        }
        $sql = "DELETE FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 

        $text_return = $RETURNTXT['selectAction'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return; 
    }
    if ($tgBot->MSG_INFO["text"] == $BTNS['rules']) {
        $text_return = $RETURNTXT['rules'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return;
    }
    if ($tgBot->MSG_INFO["text"] == $BTNS['settings']) {
        $text_return = $RETURNTXT['developing'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return;
    }
    // начать игру - создать свою комнату
    if ($tgBot->MSG_INFO["text"] == $BTNS['startGame']) {
        $sql = "SELECT `id` FROM `games` WHERE `owner_id` = ". $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);
        if ($result->num_rows>0) {
            while ($row = $result->fetch_row()) {
                $sql = "DELETE FROM `games` WHERE `id` = " . $row[0];
                $mysqli->query($sql);
            }
        }
        
        $gameAlias->create_word_list($dict_name, 20);        
        $pswd = rand(10000, 99999);
        $sql = "INSERT INTO `games` (`owner_id`, `password`, `word_number`, `score1`, `score2`, `active_team`, `team1`, `team2`, `team1_lead`, `team2_lead`, `dictionary_id`, `word_list`) VALUE(" . 
        $tgBot->MSG_INFO['chat_id'] . ", " . $pswd . ", 0, 0, 0, 0, '{\"players\":[]}', '{\"players\":[]}', 0, 0, 1, '" . json_encode($gameAlias->gen_list, JSON_UNESCAPED_UNICODE) . "')";
        $result = $mysqli->query($sql); 
        $sqlID = "SELECT LAST_INSERT_ID();";
        $resultID = $mysqli->query($sqlID); 
        $row = $resultID->fetch_row();        
        $room = $row[0];

        $sql = "UPDATE `users` SET `status` = 1, `game_id` = $room  WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);

        $text_return = $BTNS['startGame'] . ". " . $RETURNTXT['roomCreated'] . $room . " " . $RETURNTXT['roomPswd'] . $pswd; 
        $reply_markup = $tgBot->keyboard([[$BTNS['team1'], $BTNS['team2']],[$BTNS['round'], $BTNS['changeDict']]]);

        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
        return;
    }
    // присоединиться к игре
    if ($tgBot->MSG_INFO["text"] ==  $BTNS['join']) {
        $reply_markup = $tgBot->keyboard([[ $BTNS['back'] ]]);
        $text_return = $RETURNTXT['enter_room'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $sql = "UPDATE `users` SET `status` = 4 WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);
        return;
    }
    // если попытка войти в комнату проверяем номер
    if ($status == 4) {
        $room = $tgBot->MSG_INFO["text"];
        $sql = "SELECT `password` FROM `games` WHERE `id` = " . intval($room) . ";";
        $result = $mysqli->query($sql);
        if ($result->num_rows>0) {
            $text_return = "Комната №" . $tgBot->MSG_INFO["text"] . " введите пароль:";
            $sql = "UPDATE `users` SET `game_id` = " . $room . ", `status` = 3 WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
            $result = $mysqli->query($sql);
        }else{
            $text_return = $tgBot->MSG_INFO["text"] . ": комната не найдена";
        }
        $reply_markup = $tgBot->keyboard([[ $BTNS['back'] ]]);
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
        return;
    }
    // если попытка войти в комнату (шаг 2) проверяем пароль
    if ($status == 3) {
        $pswd = $tgBot->MSG_INFO["text"];
        $sql = "SELECT `id` FROM `games` WHERE `id` = " . intval($room) . " AND `password` = " . $pswd . ";";
        $result = $mysqli->query($sql);
        if ($result->num_rows>0) {
            $text_return = "Вы вошли в комнату №" . $tgBot->MSG_INFO["text"];
            $sql = "UPDATE `users` SET `game_id` = " . $room . ",`status` = 2 WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
            $result = $mysqli->query($sql);
            $reply_markup = $tgBot->inline_keyboard([
                [   ['text' => $BTNS['team1'], 'callback_data' => 'team1'],
                    ['text' => $BTNS['team2'], 'callback_data' => 'team2']],
                [   ['text' => $BTNS['back'], 'callback_data' => 'back']]
            ]);
    
        }else{
            $text_return = $tgBot->MSG_INFO["text"] . ": пароль не правильный";
        }
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
        return;
    }


    //  получение переменных из базы для дальнейших действий с игрой
    $gameAlias->get_game($room);
    if ($gameAlias->game->error) {
        $text_return = " Ошибка:" . $gameAlias->game->error;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
        return;
    }
    
    // присоединиться к первой/второй команде
    if ($tgBot->MSG_INFO["text"] == $BTNS['team1'] || $tgBot->MSG_INFO["text"] == $BTNS['team2'] ) {
        // если игрок уже в команде - находим и удаляем его        
        while (($key = array_search($tgBot->MSG_INFO["user_id"], $gameAlias->game->team1->players)) !== FALSE) {
            array_splice($gameAlias->game->team1->players, $key, 1);
        }
        while (($key = array_search($tgBot->MSG_INFO["user_id"], $gameAlias->game->team2->players)) !== FALSE) {
            array_splice($gameAlias->game->team2->players, $key, 1);
        }

        if ($tgBot->MSG_INFO["text"] == $BTNS['team1']) {
            $text_return = $tgBot->MSG_INFO['name'] . " " . $RETURNTXT['joined1'];
            $gameAlias->game->team1->players[] = $tgBot->MSG_INFO["user_id"];
        }else{
            $text_return = $tgBot->MSG_INFO['name'] . " " . $RETURNTXT['joined2'];
            $gameAlias->game->team2->players[] = $tgBot->MSG_INFO["user_id"];
        }

        $gameAlias->game->players = array_merge($gameAlias->game->team1->players, $gameAlias->game->team2->players);
        $gameAlias->save_game();
        // пишем всем в чат сообщение
        foreach($gameAlias->game->players as $player) {
            $tgBot->msg_to_tg($player, $text_return);
        }

        return;
    }
    // начать раунд
    if ($tgBot->MSG_INFO["text"] == $BTNS['round']) {
        // меняем играющую команду
        $gameAlias->game->active_team = ($gameAlias->game->active_team == 1) ? 2 : 1;

        // ищем ведущего в играющей команде
        if ($gameAlias->game->active_team == 1) {
            $team = $gameAlias->game->team1->players;
            $team_lead = $gameAlias->game->team1_lead;
        }else{
            $team = $gameAlias->game->team2->players;
            $team_lead = $gameAlias->game->team2_lead;
        }    
        /*
        if (count($team) < 2) {
            $text_err = $ERROR["noteam"];
            $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_err);
            return;
        }
        */

        if ($team_lead == 0) {
            $team_lead = $team[0];
        }
        
        $team_lead_key = array_search($team_lead, $team);
        // если текущий ведущий не найден, ставим первого
        if ($team_lead_key === FALSE) { 
            $team_lead_key = 0;
        }else{ // иначе сдвигаем на следующего
            if (count($team) > $team_lead_key + 1) {
                $team_lead_key++;
            }else{
                $team_lead_key = 0;
            }
        }
        $team_lead = $team[$team_lead_key];

        $sql = "SELECT `username`, `first_name`, `last_name` FROM `users` WHERE `tid` = " . $team_lead . ";";
        $result = $mysqli->query($sql); 
        $row = $result->fetch_row();
        $team_lead_name = ($row[0] != "") ? $row[0] : $row[1] . " " . $row[2];

        $text_return = $RETURNTXT['explains'] . " " . $gameAlias->game->active_team . $RETURNTXT['explains2'] . " " . $team_lead_name;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);

        // и задаем сообщение для личного чата ведущего
        $text_return = $RETURNTXT['word'] . " " . $gameAlias->game->word_list[$gameAlias->game->word_number]->word;
        $reply_markup_with_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip']],[$BTNS['desc'] ]]);
        $reply_markup_without_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip']] ]);
        // если нет описания слова в базе, пробуем получить его с сайтов
        if (strlen($gameAlias->game->word_list[$gameAlias->game->word_number]->description) == 0) {
            $description = $gameAlias->get_description($gameAlias->game->word_list[$gameAlias->game->word_number]->word);
            $gameAlias->game->word_list[$gameAlias->game->word_number]->description = $description;
            $gameAlias->save_word_description($dict_name, $gameAlias->game->word_list[$gameAlias->game->word_number]->word, $description); 
            $reply_markup = (strlen($description) > 0) ? $reply_markup_with_desc : $reply_markup_without_desc;
        }
        $gameAlias->save_game();
        $tgBot->msg_to_tg($team_lead, $text_return, $reply_markup);
        return;
    }
    // если запрошено описание слова выводим его 
    if ($tgBot->MSG_INFO["text"] == $BTNS['desc']) {
        $text_return = $gameAlias->game->word_list[$gameAlias->game->word_number]->word . ": " . $gameAlias->game->word_list[$gameAlias->game->word_number]->description;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
        return;
    }
    // если была нажата кнопка выбора словаря
    if ($tgBot->MSG_INFO["text"] == $BTNS['changeDict']) {
        $text_return = $RETURNTXT['developing'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
        return;
    }
    
    // кнопки угадали / пропустить
    if ($tgBot->MSG_INFO["text"] == $BTNS['guessed'] || $tgBot->MSG_INFO["text"] == $BTNS['skip'] ) {
        // TODO check $tgBot->MSG_INFO["chat_id"] == teamlead
        $gameAlias->game->word_number++;
        if ($gameAlias->game->word_number > 19) {
            $text_return = $RETURNTXT['word_limit'];
            $reply_markup = $tgBot->keyboard([[ $BTNS['end_game'], $BTNS['next_round'] ]]);
            $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
            return;
        }
        // и задаем сообщение для личного чата ведущего
        $text_return = $RETURNTXT['nextWord'] . " " . $gameAlias->game->word_list[$gameAlias->game->word_number]->word;
        $reply_markup_with_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip']],[$BTNS['desc'] ]]);
        $reply_markup_without_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip'] ]]);
        // если нет описания слова в базе, пробуем получить его с сайтов        
        if (strlen($gameAlias->game->word_list[$gameAlias->game->word_number]->description) == 0) {
            $description = $gameAlias->get_description($gameAlias->game->word_list[$gameAlias->game->word_number]->word);
            $gameAlias->game->word_list[$gameAlias->game->word_number]->description = $description;
            $gameAlias->save_word_description($dict_name, $gameAlias->game->word_list[$gameAlias->game->word_number]->word, $description); 
            $reply_markup = (strlen($description) > 0) ? $reply_markup_with_desc : $reply_markup_without_desc;
        }else{
            $reply_markup = $reply_markup_with_desc;
        }
        $gameAlias->save_game();
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        return;
    }

    // любое другое сообщение удаляем и предлагаем сделать выбор
    $text_return = "выберите дейстие:";
    $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
    $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
}

?>