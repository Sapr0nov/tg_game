<?php
class AliasClass
{
    public $acitve_team;
    public $word_list;
    public $curr_word;
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
            $this->word_list = [];
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
        $this->word_list = $outArray;
        return json_encode($outArray);
    }
}
?>