<?php
// скрипт для загрузки словарей
$json = $_POST['json']; // json строка со словами '{ "words": ["год", "человек"]}';
$name = $_POST['name']; // название словаря
$user_id = $_POST['user_id']; // telegram id user
$language = isset($_POST['language']) ? $_POST['language'] : "ru"; // язык словаря

if ($json == '') {
    echo "<form action=\"\" method=\"post\">JSON словарь:<textarea name=\"json\"></textarea>";
    echo "Название словаря<input name=\"name\" type=\"text\" />";
    echo "telegram user id <input type=\"text\" name=\"user_id\" value=\"242159096\" />";
    echo "Язык словаря <input type=\"text\" name=\"language\" value=\"ru\" />";
    echo "<input type=\"submit\" value=\"Load\" />";
    echo "</form>";
    return;
}

$SITE_DIR = dirname(__FILE__) . "/";

require_once($SITE_DIR . 'env.php');
$mysqli = new mysqli($SQL_SERVER, $SQL_USER, $SQL_PSWD, $SQL_DB);
if ($mysqli->connect_errno) {
    $error = $mysql->connect_error; 
}

$sql = "INSERT INTO `dictionaries_name` (`dictionary_name`, `creator`, `language`)  VALUES ('" . $name . "'," . $user_id . ",'" . $language . "');";
$result = $mysqli->query($sql);
$sql = "SELECT LAST_INSERT_ID();";
$result = $mysqli->query($sql);
$row = $result->fetch_row();
$did = $row[0];

$dictionary = json_decode($json);
foreach ($dictionary->words as $word) {
    $sql = "INSERT INTO `dictionaries` (`word`, `dictionary_id`, `description`)  VALUES ('" . $word . "'," . $did . ",'');";
    $result = $mysqli->query($sql);
}
echo "Словарь загружен";
?>