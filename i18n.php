<?php
(isset($LANG)) ? $LANG : 'ru';
if ($LANG == 'ru') {
    $ERROR["onlyPrivate"] = "Я не работаю в группах - напиши мне лично";
    $ERROR["err"] = "Ошибка:";
    $ERROR["noteam"] = "В команде не достаточно игроков";

    $BTNS['startGame'] = "Начать игру";
    $BTNS['solo'] = "СОЛО";
    $BTNS['online'] = "ОНЛАЙН";
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
    $BTNS['end_game'] = "Завершить игру"; 
    $BTNS['next_round'] = "Следующий раунд";
    
    $BTNS['reset'] = "Сброс";

    $BTNS['guessed'] = 'Угадали';
    $BTNS['skip'] = 'Пропустить';
    $BTNS['desc'] = "Значение";

    $RETURNTXT['startGame'] = "Как вы хотите играть?\r\n ОНЛАЙН - создать виртуальную комнату и пригласить игроков или \r\n СОЛО - с одного устройства?";
    $RETURNTXT['solo'] = "Вы в одном шаге, теперь вы можете выбрать словарь или сразу начать раунд со стандартным словарем";
    $RETURNTXT['online'] = "";
    $RETURNTXT['selectAction'] = "Выберите дейстие:";
    $RETURNTXT['rules'] = "Игра для 4х и более человек. Игроки делаятся на две команды и пытаются объяснить друг другу слова. Следуйте подсказкам на экране";
    $RETURNTXT['developing'] = "В разработке";
    $RETURNTXT['chose_dictionary'] = "Выберите словарь:";
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
    $RETURNTXT['word_limit'] = "Вы перебрали все отведенные вам слова";
    $RETURNTXT['time_limit'] = "Время раунда завершено";
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
    $BTNS['end_game'] = "Finish game"; 
    $BTNS['next_round'] = "Next round";
    
    $BTNS['reset'] = "Reset";

    $BTNS['guessed'] = 'Guessed';
    $BTNS['skip'] = 'Skip';
    $BTNS['desc'] = "Meaning";

    $RETURNTXT['selectAction'] = "Select an action:";
    $RETURNTXT['rules'] = "A game for 4 or more people. Players are divided into two teams and try to explain words to each other. Follow the prompts on the screen";
    $RETURNTXT['developing'] = "In developing";
    $RETURNTXT['chose_dictionary'] = "Chose a dictionary:";
    $RETURNTXT['enter_room'] = "Enter room number:";
    $RETURNTXT['roomCreated'] = "Created room №";
    $RETURNTXT['roomPswd'] = "password - ";
    $RETURNTXT['joined1'] = "joined the first team.";
    $RETURNTXT['joined2'] = "joined the second team.";
    $RETURNTXT['explains'] = "Explains";
    $RETURNTXT['explains2'] = "team. Leader:";
    $RETURNTXT['word'] = "Word for explanation:";
    $RETURNTXT['nextWord'] = "Next word";
    $RETURNTXT['changeWord'] = "Another word:";
    $RETURNTXT['word_limit'] = "Ok, word list is out.";
    $RETURNTXT['time_limit'] = "Round time is out.";
}

?>