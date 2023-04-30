<?php
(isset($LANG)) ? $LANG : 'ru';
if ($LANG == 'ru') {
    $ERROR["onlyPrivate"] = "Я не работаю в группах - напиши мне лично";
    $ERROR["err"] = "Ошибка:";
    $ERROR["noteam"] = "В команде не достаточно игроков";

    $BTNS['startGame'] = "Начать игру";
    $BTNS['settings'] = "Настройки";
    $BTNS['stats'] = "Статистика";
    $BTNS['rules'] = "Правила";
    $BTNS['clear'] = "Очистить чат";
    $BTNS['rooms'] = "Мои комнаты";
    $BTNS['createRoom'] = "Создать комнату";
    $BTNS['join'] = "Присоединиться";
    $BTNS['enter'] = "Войти";
    $BTNS['back'] = "Назад";
    $BTNS['delete'] = "Удалить";
    
    $BTNS['team1'] = "Играть в 1ой команде";
    $BTNS['team2'] = "Играть во 2ой команде";
    $BTNS['round'] = "Начать раунд";
    $BTNS['changeDict'] = "Сменить словарь";

    $BTNS['guessed'] = 'Угадали';
    $BTNS['skip'] = 'Пропустить';
    $BTNS['desc'] = "Значение";

    $RETURNTXT['selectAction'] = "Выберите дейстие:";
    $RETURNTXT['rules'] = "Игра для 4х и более человек. Игроки делаятся на две команды и пытаются объяснить друг другу слова. Следуйте подсказкам на экране";
    $RETURNTXT['developing'] = "В разработке";
    $RETURNTXT['enter_room'] = "Введите номер комнаты:";
    $RETURNTXT['roomCreated'] = "Создана комната №";
    $RETURNTXT['roomPswd'] = "пароль - ";
    $RETURNTXT['joined1'] = "присоединился к первой команде.";
    $RETURNTXT['joined2'] = "присоединился ко второй команде.";
    $RETURNTXT['explains'] = "Объясняет";
    $RETURNTXT['explains2'] = "я команда. Ведущий:";
    $RETURNTXT['word'] = "слово для объяснения:";
    $RETURNTXT['nextWord'] = "Следующее слово:";
    $RETURNTXT['changeWord'] = "Слово на замену:";
}

if ($LANG == 'en') {
    $ERROR["onlyPrivate"] = "I do not work in groups - write to me personally";
    $ERROR["err"] = "Error:";
    $ERROR["noteam"] = "Not enough people on the team";

    $BTNS['startGame'] = "Start";
    $BTNS['settings'] = "Settings";
    $BTNS['stats'] = "Statistics";
    $BTNS['rules'] = "Rules";
    $BTNS['clear'] = "Clear chat";
    $BTNS['rooms'] = "Rooms";
    $BTNS['createRoom'] = "Create room";
    $BTNS['join'] = "join";
    $BTNS['enter'] = "Enter";
    $BTNS['back'] = "Back";
    $BTNS['delete'] = "Delete";

    $BTNS['team1'] = "Play in 1st team";
    $BTNS['team2'] = "Play in 2nd team";
    $BTNS['round'] = "Start round";
    $BTNS['changeDict'] = "change Dictionary";

    $BTNS['guessed'] = 'Guessed';
    $BTNS['skip'] = 'Skip';
    $BTNS['desc'] = "Meaning";

    $RETURNTXT['selectAction'] = "Select an action:";
    $RETURNTXT['rules'] = "A game for 4 or more people. Players are divided into two teams and try to explain words to each other. Follow the prompts on the screen";
    $RETURNTXT['developing'] = "In developing";
    $RETURNTXT['enter_room'] = "Enter room number:";
    $RETURNTXT['roomCreated'] = "Created room №";
    $RETURNTXT['roomPswd'] = "password - ";
    $RETURNTXT['joined1'] = "joined the first team.";
    $RETURNTXT['joined2'] = "joined the second team.";
    $RETURNTXT['explains'] = "Explains";
    $RETURNTXT['explains2'] = "team. Leader:";
    $RETURNTXT['word'] = "Word for explanation:";
    $RETURNTXT['nextWord'] = "Next word";
}

?>