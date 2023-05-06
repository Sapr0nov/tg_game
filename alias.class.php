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

    public function get_game($id) {
        $this->game = new stdClass();
        $sql = "SELECT `id`, `team1`, `team2`, `active_team`, `team1_lead`, `team2_lead`, `word_number`, `word_list`, `owner_id` FROM `games` WHERE `id` = " . $id . ";";
        $result = $this->MYSQLI->query($sql); 
        if ($result->num_rows < 1) {
            $this->game->error = "game not found" . $sql;
            return NULL;
        }
        $row = $result->fetch_row();
        
        $this->game->id = $row[0];
        $this->game->owner = $row[8];
        $this->game->team1 = json_decode($row[1]);
        $this->game->team2 = json_decode($row[2]);
        $this->game->team1->players = array_unique($this->game->team1->players);
        $this->game->team2->players = array_unique($this->game->team2->players);
        $this->game->players = array_merge($this->game->team1->players, $this->game->team2->players);
        $this->game->active_team = $row[3];
        $this->game->team1_lead = $row[4];
        $this->game->team2_lead = $row[5];
        $this->game->word_number = $row[6];
        $this->game->word_list = json_decode($row[7]);
    
        return $game;
    }
    public function save_game() {
        $sql = "UPDATE `games` SET " .
         "`team1` = '" . json_encode($this->game->team1) .
         "', `team2` = '" . json_encode($this->game->team2) .
         "', `active_team` = '" . $this->game->active_team .
         "', `team1_lead` = '" . $this->game->team1_lead .
         "', `team2_lead` = '" . $this->game->team2_lead .
         "', `word_number` = '" . $this->game->word_number .
         "', `word_list` = '" . json_encode($this->game->word_list, JSON_UNESCAPED_UNICODE) .
         "' WHERE `id` = " . $this->game->id . ";";
        $result = $this->MYSQLI->query($sql); 
    
        return $result;
    }

    public function save_word_description($dictionary, $word, $desc) {
        // ищем id словаря
        $sql = "SELECT `id` FROM `dictionaries_name` WHERE `dictionary_name` = '" . $dictionary . "';";
        $result = $this->MYSQLI->query($sql);
        if ($result->num_rows < 1) {
            $this->gen_list = [];
            return [];
        }
        $row = $result->fetch_row();
        $did = $row[0];

        $sql = "UPDATE `dictionaries` SET `description`='" . $desc . "' WHERE `word` = '" . $word . "' AND `dictionary_id` = " . $did . ";";
        $result = $this->MYSQLI->query($sql);
        return $result;
    }

    public function get_description($word) {
        $myCurl = curl_init();
        curl_setopt_array($myCurl, array(
            CURLOPT_URL => 'https://ru.wiktionary.org/wiki/' . $word,
            CURLOPT_RETURNTRANSFER => true,
        ));
        $response = curl_exec($myCurl);
        curl_close($myCurl);
        $regexp = '/\<ol\*?>(.*?)\<\/ol\>/si';
        $regexp2 = '/\<li\*?>(.*?)\<\/li\>/si';
        // вырезаем блок толкований
        preg_match($regexp, $response, $matches);
        $block = $matches[1];
        $block = preg_replace('~<a(.*?)>(.*?)</a>~usi', '$2', $block);  
        // разделяем по li
        preg_match_all($regexp2, $block, $results);
        $out = "";
        foreach ($results[1] as $key => $res) {
            $res = preg_replace('~(<sup.*?</sup>)~usi', "", $res);
            $res = preg_replace('~(<span class=\"example-block\".*</span>)~usi', "", $res);
            $out .= ($key+1) . " " . strip_tags($res) . "\r\n";
        }
        $out = trim(str_replace("&#9670;", "", $out));
        if (strlen($out) > 10) {
            return $out;
        }else{
            $myCurl = curl_init();
            curl_setopt_array($myCurl, array(
                CURLOPT_URL => 'http://gramota.ru/slovari/dic/?bts=x&word=' . $word,
                CURLOPT_RETURNTRANSFER => true,
          ));
          $response = curl_exec($myCurl);
          curl_close($myCurl);
          $response = mb_convert_encoding($response, 'UTF-8', 'Windows-1251');
          $regexp = '/\<div style=\"padding-left:50px\"\>(.*?)\<\/div\>/si';
          $regexp2 = '/\<b\>\d.\<\/b\>/si';
          preg_match($regexp, $response, $matches);
          $block = $matches[1];
          $results = preg_split($regexp2, $block);
          $out = "";
          foreach ($results as $key => $res) {
            if ($key > 0) {
                $out .= ($key) . " " . strip_tags($res) . "\r\n";
            }
          }
          if (strlen($out) > 10) {
            return $out;
          }else{
            return "";
          }
        }
    }
}

?>