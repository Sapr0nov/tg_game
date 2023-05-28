<?PHP
/**
*   tg game bot v3
*   25.05.2023

Начать игру, Правила, Очистить чат

>Начать игру
Как вы хотите играть, ОНЛАЙН - создать виртуальную комнату и пригласить игроков или с ОДНОГО устройства?

ОНЛАЙН   ОДИН

>онлайн 

Выберите словарь:
> словарь
<Время раунда>

Перешлите это сообщение, чтобы пригласить друзей. 
Друзья могут отправить это приглашение боту и сразу попасть в комнату
Или нажать Присоединиться и ввести номер комнаты и пароль

Выберите команду

Начать Раунд

**/

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
date_default_timezone_set('Europe/Moscow');

$SITE_DIR = dirname(__FILE__) . "/";
require_once($SITE_DIR . 'env.php'); 
require_once($SITE_DIR . 'i18n.php'); 
require_once($SITE_DIR . 'tg.class.php');
require_once($SITE_DIR . 'alias.class.php');

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
    $reply_markup_start = $tgBot->keyboard([[$BTNS['startGame'], $BTNS['rules']], [$BTNS['clear']]]); // $BTNS['join']],[$BTNS['settings'],
    $reply_markup_type = $tgBot->keyboard([[$BTNS['online'], $BTNS['solo']], [$BTNS['clear']]]); 
    $reply_markup_solo = $tgBot->keyboard([[$BTNS['round'], $BTNS['changeDict']], [$BTNS['clear']]]); 
    
    // Скрытая настройка для сброса пользователя
    if ($tgBot->MSG_INFO["text"] == $BTNS['reset']) {
        $sql = "SELECT `msg_id` FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 
        while ($row = $result->fetch_row()) {
            $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $row[0]);
        }
        $sql = "DELETE FROM `messages` WHERE `chat_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 
        $sql = "DELETE FROM `games` WHERE `owner_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 
        $sql = "DELETE FROM `users` WHERE `tid` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 

        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $RETURNTXT['selectAction'], $reply_markup_start);
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

        $sql = "DELETE FROM `games` WHERE `owner_id` = '" . $tgBot->MSG_INFO["chat_id"] . "'";
        $result = $mysqli->query($sql); 

        $sql = "UPDATE `users` SET `status` = 0, `game_id` = NULL  WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);

        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $RETURNTXT['selectAction'], $reply_markup_start);
        return; 
    }
    if ($tgBot->MSG_INFO["text"] == $BTNS['rules']) {
        $text_return = $RETURNTXT['rules'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return;
    }
    // начать игру - создать свою комнату
    if ($tgBot->MSG_INFO["text"] == $BTNS['startGame']) {
        $text_return = $RETURNTXT['startGame'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_type);
        return;
    }

    if ($tgBot->MSG_INFO["text"] == $BTNS['solo']) {
        $sql = "SELECT `id` FROM `games` WHERE `owner_id` = " . $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);
        if ($result->num_rows>0) {
            while ($row = $result->fetch_row()) {
                $sql = "DELETE FROM `games` WHERE `id` = " . $row[0];
                $mysqli->query($sql);
            }
        }
        $pswd = rand(10000, 99999);
        $sql = "INSERT INTO `games` (`owner_id`, `password`, `word_number`, `score1`, `score2`, `active_team`, `team1`, `team2`, `team1_lead`, `team2_lead`, `dictionary_id`, `word_list`, `start_round_at` ) VALUE(" . 
        $tgBot->MSG_INFO['chat_id'] . ", " . $pswd . ", 0, 0, 0, 0, '{\"players\":[]}', '{\"players\":[]}', 0, 0, 1, '" . '[]' . "', NULL)";
        $result = $mysqli->query($sql); 
        $sqlID = "SELECT LAST_INSERT_ID();";
        $resultID = $mysqli->query($sqlID); 
        $row = $resultID->fetch_row();        
        $room = $row[0];

        $sql = "UPDATE `users` SET `status` = 1, `game_id` = $room  WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);

        $text_return = $RETURNTXT['solo'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_solo);
        return;
    }

    if ($tgBot->MSG_INFO["text"] == $BTNS['online']) {
        $reply_markup = $tgBot->keyboard([[$BTNS['createRoom'], $BTNS['join']], [$BTNS['clear']]]);
        $text_return = $RETURNTXT['online'];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        return;
    }
    
    if ($tgBot->MSG_INFO["text"] == $BTNS['createRoom']) {

        $sql = "SELECT `id` FROM `games` WHERE `owner_id` = " . $tgBot->MSG_INFO['chat_id'] . "";
        $result = $mysqli->query($sql);
        if ($result->num_rows>0) {
            while ($row = $result->fetch_row()) {
                $sql = "DELETE FROM `games` WHERE `id` = " . $row[0];
                $mysqli->query($sql);
            }
        }
 
        $pswd = rand(10000, 99999);
        $sql = "INSERT INTO `games` (`owner_id`, `password`, `word_number`, `score1`, `score2`, `active_team`, `team1`, `team2`, `team1_lead`, `team2_lead`, `dictionary_id`, `word_list`, `start_round_at` ) VALUE(" . 
        $tgBot->MSG_INFO['chat_id'] . ", " . $pswd . ", 0, 0, 0, 0, '{\"players\":[]}', '{\"players\":[]}', 0, 0, 1, '" . '[]' . "', NULL)";
        $result = $mysqli->query($sql); 
        $sqlID = "SELECT LAST_INSERT_ID();"; 
        $resultID = $mysqli->query($sqlID); 
        $row = $resultID->fetch_row(); 
        $room = $row[0]; 

        $sql = "UPDATE `users` SET `status` = 2, `game_id` = $room  WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . ""; 
        $result = $mysqli->query($sql); 

        $text_return = $RETURNTXT['roomCreatedSolution']; 
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return); 
        $text_return = $BTNS['startGame'] . ". " . $RETURNTXT['roomCreated'] . $room . " " . $RETURNTXT['roomPswd'] . $pswd; 
        $reply_markup = $tgBot->keyboard([[$BTNS['team1'], $BTNS['team2']],[$BTNS['round'], $BTNS['changeDict']],[$BTNS['clear']]]); 
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup); 
        return; 
    } 
    // присоединиться к игре
    if ($tgBot->MSG_INFO["text"] ==  $BTNS['join']) {
        $reply_markup = $tgBot->keyboard([[ $BTNS['clear'] ]]);
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
        $reply_markup = $tgBot->keyboard([[ $BTNS['clear'] ]]);
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
            $text_return = $RETURNTXT['entered_room'] ." №" . $tgBot->MSG_INFO["text"];
            $sql = "UPDATE `users` SET `game_id` = " . $room . ",`status` = 2 WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
            $result = $mysqli->query($sql);
            $reply_markup = $tgBot->keyboard([[ $BTNS['team1'], $BTNS['team2']], [$BTNS['clear'] ]]);
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

    // если приложение ожидает выбора словаря
    if ($gameAlias->game->status == 2) {
        $dictionary_name = $tgBot->MSG_INFO["text"];
        $text_return = $gameAlias->select_dictionary_by_name($dictionary_name);
        $reply_markup = $tgBot->keyboard([[$BTNS['team1'], $BTNS['team2']],[$BTNS['round'], $BTNS['changeDict']], [$BTNS['clear']]]); 
        if ($status == 1) {
            $reply_markup = $reply_markup_solo;
        }
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
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
        $text_return .= "\r\n\r\n";

        $team1_arr = array();
        $team2_arr = array();

        foreach($gameAlias->game->team1->players as $player_id) {
            $team1_arr[] = $gameAlias->get_user_name($player_id);
        }
        foreach($gameAlias->game->team2->players as $player_id) {
            $team2_arr[] = $gameAlias->get_user_name($player_id);
        }

        $team1 = (count($team1_arr) > 0) ? implode(", ", $team1_arr) : "";
        $team2 = (count($team2_arr) > 0) ? implode(", ", $team2_arr) : "";

        $text_return .= $RETURNTXT['team1'] . $team1 . "\r\n";
        $text_return .= $RETURNTXT['team2'] . $team2 . "\r\n";
        $gameAlias->game->players = array_merge($gameAlias->game->team1->players, $gameAlias->game->team2->players);

        $gameAlias->save_game();
        
        // пишем всем в чат сообщение
        foreach($gameAlias->game->players as $player) {
            $tgBot->msg_to_tg($player, $text_return);
        }
        return;
    }

    // начать раунд
    if ($tgBot->MSG_INFO["text"] == $BTNS['round'] || $tgBot->MSG_INFO["text"] == $BTNS['next_round']) {
        // для SOLO режима
        if ($status == 1) {
            // записываем ведущего в обе команды
            $gameAlias->game->team1->players[] = $tgBot->MSG_INFO["user_id"];
            $gameAlias->game->team2->players[] = $tgBot->MSG_INFO["user_id"];
            $gameAlias->game->players = array_merge($gameAlias->game->team1->players, $gameAlias->game->team2->players);
            $gameAlias->save_game();
        }

        // для ONLINE режима
        if ($status == 2) {
            if (count($gameAlias->game->team1->players) >= 2 && count($gameAlias->game->team2->players) >= 2) {
                $gameAlias->game->players = array_merge($gameAlias->game->team1->players, $gameAlias->game->team2->players);
                $gameAlias->save_game();    
            }else{
                $text_return = $ERROR["noteam"];
                $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
                return;
            }
        }

        // меняем играющую команду
        $gameAlias->game->active_team = ($gameAlias->game->active_team == 1) ? 2 : 1;
        $gameAlias->game->word_number = 0;
        // ищем ведущего в играющей команде
        if ($gameAlias->game->active_team == 1) {
            $team = $gameAlias->game->team1->players;
            $team_lead = $gameAlias->game->team1_lead;
        }else{
            $team = $gameAlias->game->team2->players;
            $team_lead = $gameAlias->game->team2_lead;
        }    

        if ($team_lead == 0) {
            if (!empty($team)) {
                $team_lead = $team[0];
            }else{
                $text_return = $ERROR["noplayer"];
                $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);
                return;
            }
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
        //get_user_name
        $sql = "SELECT `username`, `first_name`, `last_name` FROM `users` WHERE `tid` = " . $team_lead . ";";
        $result = $mysqli->query($sql); 
        $row = $result->fetch_row();
        $team_lead_name = ($row[0] != "") ? $row[0] : $row[1] . " " . $row[2];

        $text_return = $RETURNTXT['explains'] . " " . $gameAlias->game->active_team . $RETURNTXT['explains2'] . " " . $team_lead_name;

        // и задаем сообщение для личного чата ведущего
        $gameAlias->create_word_list($gameAlias->game->dictionary_id, 20);
        $gameAlias->game->word_list = $gameAlias->gen_list;
        $gameAlias->save_game();
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return);    
        
        $score_prop = "score" . $gameAlias->game->active_team;
        $timer = $gameAlias->left_time();
        $gameAlias->game->word_number = 0;
        $text_return = "Слово [" . $gameAlias->game->word_number . "/20]" . " " . "Очки:" . " " . $gameAlias->game->{$score_prop} . " " . "Время:" . " 1:00" . " \r\n" . $RETURNTXT['nextWord'] . " <pre><b>" . mb_strtoupper($gameAlias->game->word_list[$gameAlias->game->word_number]->word) . "</b></pre>";

        $reply_markup_with_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip']],[$BTNS['desc'] ]]);
        $reply_markup_without_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip']] ]);
        // если нет описания слова в базе, пробуем получить его с сайтов
        if (strlen($gameAlias->game->word_list[$gameAlias->game->word_number]->description) == 0) {
            $description = $gameAlias->get_description($gameAlias->game->word_list[$gameAlias->game->word_number]->word);
            $gameAlias->game->word_list[$gameAlias->game->word_number]->description = $description;
            $gameAlias->save_word_description($gameAlias->game->dictionary_id, $gameAlias->game->word_list[$gameAlias->game->word_number]->word, $description); 
            $reply_markup = (strlen($description) > 0) ? $reply_markup_with_desc : $reply_markup_without_desc;
        }else{
            $reply_markup = $reply_markup_without_desc;
        }
        $gameAlias->game->start_round_at = date('Y-m-d H:i:s');
        $gameAlias->save_game();
        $tgBot->msg_to_tg($team_lead, $text_return, $reply_markup);
        return;
    }
    // если запрошено описание слова выводим его 
    if ($tgBot->MSG_INFO["text"] == $BTNS['desc']) {
        $reply_markup = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip'] ]] );
        $text_return = $gameAlias->game->word_list[$gameAlias->game->word_number]->word . ": " . $gameAlias->game->word_list[$gameAlias->game->word_number]->description;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        return;
    }
    // если была нажата кнопка выбора словаря
    if ($tgBot->MSG_INFO["text"] == $BTNS['changeDict']) {
        $dics = $gameAlias->game->dictionaries;
        $text_return = $RETURNTXT['chose_dictionary'];

        $reply_btns = [];
        foreach ($dics as $dic) {
            $reply_btns[] = [$dic->name];
        }

        $reply_markup = $tgBot->keyboard($reply_btns);
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $gameAlias->game->status = '2';
        $gameAlias->save_game();
        return;
    }
    
    // кнопки угадали / пропустить
    if ($tgBot->MSG_INFO["text"] == $BTNS['guessed'] || $tgBot->MSG_INFO["text"] == $BTNS['skip'] ) {
        // TODO check $tgBot->MSG_INFO["chat_id"] == teamlead
        $gameAlias->game->word_number++;
        $score_prop = "score" . $gameAlias->game->active_team;

        if($tgBot->MSG_INFO["text"] == $BTNS['guessed']){
            $gameAlias->game->{$score_prop}++;
        }elseif($tgBot->MSG_INFO["text"] == $BTNS['skip']){
            $gameAlias->game->{$score_prop}--;
        }

        // проверка что кончилось время
        $timer = $gameAlias->left_time();
        if ($timer == 0) {
            $text_return = $RETURNTXT['time_limit'] . " " . "Вы набрали:" . " " . $gameAlias->game->{$score_prop} . " " . "очков";
            $reply_markup = $tgBot->keyboard([[ $BTNS['end_game'], $BTNS['next_round'] ]]);
            $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
            return;
        }
        
        // проверка что кончился запас слов (20)
        if ($gameAlias->game->word_number > 19) {
            $text_return = $RETURNTXT['word_limit'];
            $reply_markup = $tgBot->keyboard([[ $BTNS['end_game'], $BTNS['next_round'] ]]);
            $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
            return;
        }
        // и задаем сообщение для личного чата ведущего
        $text_return = "Слово [" . $gameAlias->game->word_number . "/20]" . " " . "Очки:" . " " . $gameAlias->game->{$score_prop} . " " . "Время:" . " " . $timer . " \r\n" . $RETURNTXT['nextWord'] . " <pre><b>" . mb_strtoupper($gameAlias->game->word_list[$gameAlias->game->word_number]->word) . "</b></pre>";
        $reply_markup_with_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip']],[$BTNS['desc'] ]]);
        $reply_markup_without_desc = $tgBot->keyboard([[ $BTNS['guessed'], $BTNS['skip'] ]]);
        // если нет описания слова в базе, пробуем получить его с сайтов        
        if (strlen($gameAlias->game->word_list[$gameAlias->game->word_number]->description) == 0) {
            $description = $gameAlias->get_description($gameAlias->game->word_list[$gameAlias->game->word_number]->word);
            $gameAlias->game->word_list[$gameAlias->game->word_number]->description = $description;
            $gameAlias->save_word_description($gameAlias->game->dictionary_id, $gameAlias->game->word_list[$gameAlias->game->word_number]->word, $description); 
            $reply_markup = (strlen($description) > 0) ? $reply_markup_with_desc : $reply_markup_without_desc;
        }else{
            $reply_markup = $reply_markup_with_desc;
        }

        $gameAlias->save_game();
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        return;
    }
    // подведение итогов
    if ($tgBot->MSG_INFO["text"] == $BTNS['end_game']) {
        $text_return = $RETURNTXT['team1'] . $gameAlias->game->score1 . $RETURNTXT['team2'] . $gameAlias->game->score2;
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup_start);
        return;

    }
    // пересланное приглашение
    if (preg_match('/Начать игру. Создана комната №(\d+) пароль - (\d+)/', $tgBot->MSG_INFO["text"], $matches)) {
        $room = $matches[1];
        $pswd = $matches[2];
        $sql = "SELECT `id` FROM `games` WHERE `id` = " . intval($room) . " AND `password` = " . $pswd . ";";
        $result = $mysqli->query($sql);
        if ($result->num_rows > 0) {
            $text_return = $RETURNTXT['entered_room'] ." №" . $room;
            $sql = "UPDATE `users` SET `game_id` = " . $room . ",`status` = 2 WHERE `tid` = ". $tgBot->MSG_INFO['chat_id'] . "";
            $result = $mysqli->query($sql);
            $reply_markup = $tgBot->keyboard([[ $BTNS['team1'], $BTNS['team2']], [$BTNS['clear'] ]]);
        }else{
            $text_return = $tgBot->MSG_INFO["text"] . $ERROR["password"];
        }
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $text_return, $reply_markup);
        $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
        return;
    };

    // любое другое сообщение удаляем и предлагаем сделать выбор
    $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], $RETURNTXT['selectAction'], $reply_markup_start);
    $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);
}

?>