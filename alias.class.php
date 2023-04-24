<?php
class AliasClass
{
    public $game;
    public $gen_list;
    public $MYSQLI;

    function __construct($server='localhost', $user='', $pswd='', $db=''){
        if ($user !== '') {
            $this->MYSQLI = new mysqli($server, $user, $pswd, $db);
            if ($this->MYSQLI->connect_errno) {
                $error = $this->MYSQLI->connect_error; // TODO output error or save
            }
        }
    }


    // получаем нужное количество слов для раунда из словаря
    public function create_word_list($dictionary, $limit) {
        $sql = "SELECT `id` FROM `dictionaries_name` WHERE `dictionary_name` = '" . $dictionary . "';";
        $result = $this->MYSQLI->query($sql);
        if ($result->num_rows < 1) {
            $this->gen_list = [];
            return [];
        }
        $row = $result->fetch_row();
        $did = $row[0];

        $outArray = [];
        $sql = "SELECT `word`, `description` FROM `dictionaries` WHERE `dictionary_id` = " . $did . " ORDER BY RAND() LIMIT " . $limit . ";";
        $result = $this->MYSQLI->query($sql);

        while ($row = $result->fetch_row()) {
            $outArray[] = (object) array('word' => $row[0], 'description' => $row[1]);
        }
        $this->gen_list = $outArray;
        return json_encode($outArray);
    }

    public function get_game($chat_id) {
        $this->game = new stdClass();
        
        $sql = "SELECT `id`, `team1`, `team2`, `active_team`, `team1_lead`, `team2_lead`, `word_number`, `word_list`, `owner_id` FROM `games` WHERE `owner_id` = '" . $chat_id . "' OR `team1_lead` = '" . $chat_id . "' OR `team2_lead` = '" . $chat_id . "';";
        $result = $this->MYSQLI->query($sql); 
        if ($result->num_rows < 1) {
            $this->game->error = "Не удалось получить информацию об игре (sql)" . $sql;
            return NULL;
        }
        $row = $result->fetch_row();
        
        $this->game->id = $row[0];
        $this->game->owner = $row[8];
        $this->game->team1 = json_decode($row[1]);
        $this->game->team2 = json_decode($row[2]);
        $this->game->team1->players = array_unique($this->game->team1->players);
        $this->game->team2->players = array_unique($this->game->team2->players);
        $this->game->active_team = $row[3];
        $this->game->team1_lead = $row[4];
        $this->game->team2_lead = $row[5];
        $this->game->word_number = $row[6];
        $this->game->word_list = json_decode($row[7]);
    
        return $game;
    }
}

?>