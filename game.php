<?php

require_once('token.php');
require_once('bd.php');
require_once('typebot.php');

// Game
$GAME_CUBIC = 1; // Игра "Игральный кубик"
$GAME_BOWLING = 2; // Игра "Боулинг"
$GAME_DARTS = 3; // Игра "Дартс"
$GAME_FOOTBALL = 4; // Игра "Футбол"
$GAME_BASKETBALL = 5; // Игра "Баскетбол"
$GAME_CASINO = 6; // Игра "Казино"

// Role
$ADMIN = 1;
$MODER = 2;
$USER = 3;

// Action
$SEND_SMS_ROOM = 2; // Создается диалог и пишем друг другу
$FORM_REQUISITES = 3; // Пишет свои реквизиты для рефералки
$CREATE_USER_OBMEN = 4; // Пользователь создает обмен (Совершить обмен)

$banUserTime = time() + 60 * 5; // Бан на 5 минут;
$banTimeString = '5 минут'; // Текст для бана
$countProdGame = 1; // Время, через которое можно отправлять сообщение

# Принимаем запрос
$data = json_decode(file_get_contents('php://input'), TRUE);
//file_put_contents('file.txt', '$data: '.print_r($data, 1)."\n", FILE_APPEND); // Посмотреть что пришло от сервера


//https://api.telegram.org/bot*Токен бота*/setwebhook?url=*ссылка на бота*


# Обрабатываем ручной ввод или нажатие на кнопку
$data = $data['callback_query'] ? $data['callback_query'] : $data['message'];

$referal = $data['text'];

//$maskText = '[^A-Za-zА-Яа-я0-9 !@#$%^&*()№;%]';

# Важные константы
define('TOKEN', $token);

# Записываем сообщение пользователя
$message = $data['text'] ? $data['text'] : $data['data'];


$botToken = $token;
$botAPI = "https://api.telegram.org/bot" . $botToken;
$update = json_decode(file_get_contents('php://input'), TRUE);

$message_id = $data['message_id'];

// Данные пользователя
$user_id = $data['from']['id'];
$first_name = $data['from']['first_name'];
// first_name - без смайликов
$username = $data['from']['username'];
$emoji = $data['dice']['emoji'];
$emoji_result = $data['dice']['value'];
$date_send_msg = $data['date'];

//Данные группы
$group_id = $data['chat']['id'];
$group_title = $data['chat']['title'];
$group_type = $data['chat']['type'];

// Добавление и удаление из групп
$group_new_id = $update['my_chat_member']['chat']['id'];
$group_new_title = $update['my_chat_member']['chat']['title'];
$group_new_type = $update['my_chat_member']['chat']['type'];
$group_new_status = $update['my_chat_member']['new_chat_member']['status'];

$typeMessage = $data['forward_from'];

// Если пишем сообщение с самого бота, то выдается id пользователя, если в группе, то id группы
$all_chat_id = $data['chat']['id'];

$dbh =  new PDO('mysql:host='.$host.';charset=utf8;dbname='.$dbName, $bdUser, $bdPassword);
$dbh->query('SET NAMES utf8mb4');

if(isset($group_new_id)){
    if($group_new_type == 'group' || $group_new_type == 'supergroup'){
        if($group_new_status == 'member'){

            $data = http_build_query([
                'chat_id' => $group_new_id,
                'video' => 'https://game.cryptopushbot.ru/1.mp4',
                'caption' => 'Привет\\, я игровой бот для чатов\\! 👋

В игре есть 6 основных emoji\\: 

🎯 \\- Попадай в мишень
🎳 \\- Выбивай страйки
⚽️ \\- Забивай голы
🎰 \\- Выбивай ДжекПоты
🏀 \\- Попадай в корзину
🎲 \\- Выбивай max число в кубик

Telegram гарантирует честную игру за счёт рандома, а [«Игровой бот»](https://t.me/stickersgame_bot) будет учитывать победы и составлять топ среди участников и чатов\\.

СТАНЬ ТОП 1 ИГРОКОМ ИЛИ ПОБЕЖДАЙ КОМАНДОЙ\\!🥇'
            ]);

            $inline_button1 = array("text"=>"⚽","callback_data" => 'football_'.$user_id);
            $inline_button2 = array("text"=>"🎳","callback_data" => 'bowling_'.$user_id);
            $inline_button3 = array("text"=>"🎯","callback_data" => 'darts_'.$user_id);
            $inline_button4 = array("text"=>"🏀","callback_data" => 'basketball_'.$user_id);
            $inline_button5 = array("text"=>"🎲","callback_data" => 'cubic_'.$user_id);
            $inline_button6 = array("text"=>"🎰","callback_data" => 'casino_'.$user_id);
            $inline_keyboard = [[$inline_button1, $inline_button2, $inline_button3, $inline_button4, $inline_button5, $inline_button6]];
            $keyboard=array('inline_keyboard'=>$inline_keyboard);
            $replyMarkup = json_encode($keyboard);
            file_get_contents($botAPI . "/sendVideo?{$data}&reply_markup=".$replyMarkup."&parse_mode=MarkdownV2");

            $checkUser = $dbh->query('SELECT * FROM users WHERE role_id = '.$ADMIN)->fetchAll();
            foreach ($checkUser as $item){
                $data = http_build_query([
                    'text' => 'В '.$group_new_type.' - "'.$group_new_title.'" был добавлен бот!',
                    'chat_id' => $item['userid']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }

            $checkGroup = $dbh->query('SELECT * FROM group_users WHERE groupid = \''.$group_new_id.'\'')->fetchAll();
            if (is_array($checkGroup) && count($checkGroup) > 0) {
                $dbh->query('UPDATE group_users SET status = 1 WHERE groupid = \''.$group_new_id.'\'');
            } else {
                // Добавляю чат или группу в базу, даже если там никто не играл
                $dbh->query('INSERT INTO group_users (groupid, title, type, status, date) VALUE ("' . $group_new_id . '", "' . $group_new_title . '", "' . $group_new_type . '", 1, NOW())');
            }
        }
        if($group_new_status == 'left'){
            $dbh->query('UPDATE group_users SET status = 0 WHERE groupid = \''.$group_new_id.'\'');
            $checkUser = $dbh->query('SELECT * FROM users WHERE role_id = '.$ADMIN)->fetchAll();
            foreach ($checkUser as $item){
                $data = http_build_query([
                    'text' => 'Из '.$group_new_type.' - "'.$group_new_title.'" удален бот!',
                    'chat_id' => $item['userid']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
        }
    }
}

if (isset($emoji)) {
    if($group_type == 'group' || $group_type == 'supergroup'){
        if($emoji == '🎲' OR $emoji == '⚽' OR $emoji == '🎯' OR $emoji == '🎳' OR $emoji == '🏀' OR $emoji == '🎰'){
            if(isset($typeMessage)){

            } else {
                $checkBanUser = $dbh->query('SELECT * FROM user_ban WHERE groupid = \''.$group_id.'\' AND userid = \''.$user_id.'\'')->fetchAll();
                if(is_array($checkBanUser) && count($checkBanUser) > 0) {
                    $getDatetimeUserBan = 0;
                    foreach ($checkBanUser as $row){
                        $getDatetimeUserBan = $row['date_time_tm'];
                    }
                    $currentTime = time();
                    if($currentTime > $getDatetimeUserBan){
                        $dbh->query('DELETE FROM user_ban WHERE groupid = \''.$group_id.'\' AND userid = \''.$user_id.'\'');
//                    $dbh->query('INSERT INTO user_ban_count (user_id, groupid, count) VALUE ("'.$user_id.'", "'.$group_id.'", 0)');
                        $dbh->query('UPDATE user_ban_count SET count = 0 WHERE groupid = \''.$group_id.'\' AND user_id = \''.$user_id.'\''); // Вроде есть запись и нужно лишь обновить (выше закомментил)
                        $data = http_build_query([
                            'text' => 'Вы разбанены в данном чате. Можете дальше играть!',
                            'chat_id' => $group_id,
                            'reply_to_message_id' => $message_id
                        ]);
                        file_get_contents($botAPI . "/sendMessage?{$data}");
                    } else {
                        $remainder = $getDatetimeUserBan - $currentTime;
                        $messageTime = round($remainder / 60, 0);
                        $data = http_build_query([
                            'text' => '🔇 Вы получили (3/3) предупреждения за спам. Бот ограничил вас в игре и не будет учитывать ваши победы в течение '.$messageTime.' мин.',
                            'chat_id' => $group_id,
                            'reply_to_message_id' => $message_id
                        ]);
                        file_get_contents($botAPI . "/sendMessage?{$data}");
                    }
                } else {
                    $date = date("Y-m-d H:i:s");
                    $checkUser = $dbh->query('SELECT * FROM users WHERE userid = "'.$user_id.'"')->fetchAll();
                    if(is_array($checkUser) && count($checkUser) > 0) {
                        //Проверяю и сравниваю first_name. Если различается, обновляю
                        $getBdFirstName = '';
                        foreach ($checkUser as $item){
                            $getBdFirstName = $item['first_name'];
                        }
                        if (strcmp($getBdFirstName, $first_name) !== 0) {
                            $dbh->query('UPDATE users SET first_name = \''.$first_name.'\' WHERE userid = \''.$user_id.'\'');
                        }
                    } else {
                        $dbh->query('INSERT INTO users (userid, first_name, username, role_id, date) VALUE ("' . $user_id . '", "' . $first_name . '", "' . $username . '", '.$USER.', "' . $date . '")');
                    }
                    $checkGroup = $dbh->query('SELECT * FROM group_users WHERE groupid = \''.$group_id.'\'')->fetchAll();
                    if (is_array($checkGroup) && count($checkGroup) > 0) {
                        //Проверяю и сравниваю title. Если различается, обновляю
                        $getBdGroupTitle = '';
                        foreach ($checkGroup as $item){
                            $getBdGroupTitle = $item['title'];
                        }
                        if (strcmp($getBdGroupTitle, $group_title) !== 0) {
                            $dbh->query('UPDATE group_users SET title = \''.$group_title.'\' WHERE groupid = \''.$group_id.'\'');
                        }
                    } else {
                        $dbh->query('INSERT INTO group_users (groupid, title, type, date) VALUE ("' . $group_id . '", "' . $group_title . '", "' . $group_type . '", NOW())');
                    }

                    $checkMessageLastUser = $dbh->query('SELECT * FROM chat_users_group WHERE userid = "'.$user_id.'" AND groupid = "'.$group_id.'" AND id = (SELECT MAX(id) FROM chat_users_group WHERE userid = "'.$user_id.'" AND groupid = "'.$group_id.'")')->fetchAll();
                    if(is_array($checkMessageLastUser) && count($checkMessageLastUser) > 0) {
                        $checkTime = $dbh->query('SELECT * FROM last_message_group WHERE groupid = \''.$group_id.'\' AND userid = \''.$user_id.'\' AND id = (SELECT MAX(id) FROM last_message_group WHERE groupid = \''.$group_id.'\' AND userid = \''.$user_id.'\')')->fetchAll();
                        if(is_array($checkTime) && count($checkTime) > 0) {
                            $checkDateTime = 0;
                            foreach ($checkTime as $row) {
                                $checkDateTime = $row['date_time_tm'] + $countProdGame;
                            }
                            if ($checkDateTime > time()) {
                                $countBan = 0;
                                $getCountBan = $dbh->query('SELECT count as count_ban FROM user_ban_count WHERE groupid = \''.$group_id.'\' AND user_id = \''.$user_id.'\'')->fetchAll();
                                if(is_array($getCountBan) && count($getCountBan) > 0) {
                                    foreach ($getCountBan as $row){
                                        $countBan = $row['count_ban'] + 1;
                                    }
                                } else {
                                    $countBan = 1;
                                    $dbh->query('INSERT INTO user_ban_count (user_id, groupid, count) VALUE ("'.$user_id.'", "'.$group_id.'", '.$countBan.')');
                                }
                                if($countBan > 2){
                                    $dbh->query('INSERT INTO user_ban (userid, groupid, date_time_tm, date)  VALUE ("'.$user_id.'", "'.$group_id.'", '.$banUserTime.', NOW())');
                                    $dbh->query('UPDATE user_ban_count SET count = '.$countBan.' WHERE groupid = \''.$group_id.'\' AND user_id = \''.$user_id.'\'');
                                    $data = http_build_query([
                                        'text' => '🔇 Вы получили ('.$countBan.'/3) предупреждения за спам. Бот ограничил вас в игре и не будет учитывать ваши победы в течение '.$banTimeString,
                                        'chat_id' => $group_id,
                                        'reply_to_message_id' => $message_id
                                    ]);
                                    file_get_contents($botAPI . "/sendMessage?{$data}");
                                } else {
                                    $dbh->query('UPDATE user_ban_count SET count = '.$countBan.' WHERE groupid = \''.$group_id.'\' AND user_id = \''.$user_id.'\'');
                                    $data = http_build_query([
                                        'text' => '🚫 Спам в игре запрещен, такие действия не учитываются. (Предупреждение '.$countBan.'/3 на 24 часа)

❗️ Если вы продолжите спам, бот ограничит вас в игре и не будет учитывать ваши победы.'.PHP_EOL.''.PHP_EOL.'🎮 1 ход в 1 секунду - допустимый формат для игры!',
                                        'chat_id' => $group_id,
                                        'reply_to_message_id' => $message_id
                                    ]);
                                    file_get_contents($botAPI . "/sendMessage?{$data}");
                                }

                                exit;
                            } else {
                                $dbh->query('INSERT INTO last_message_group (userid, groupid, date_time_tm) VALUE ("'.$user_id.'", "'.$group_id.'", '.$date_send_msg.')');
                            }
                        }

                    } else {
                        $dbh->query('INSERT INTO chat_users_group (userid, groupid, type_game_id, date_time_tm, message_id, date) VALUE ("'.$user_id.'", "'.$group_id.'", 0, '.$date_send_msg.', 0, "'.$date.'")');
                        $dbh->query('INSERT INTO last_message_group (userid, groupid, date_time_tm) VALUE ("'.$user_id.'", "'.$group_id.'", '.$date_send_msg.')');
                        $dbh->query('INSERT INTO user_ban_count (user_id, groupid, count) VALUE ("'.$user_id.'", "'.$group_id.'", 0)');
                    }
                    // Кубик
                    if($emoji == '🎲') {
                        if ($emoji_result == 6) {
                            gameStart($emoji, $user_id, $group_id, $GAME_CUBIC , $date_send_msg, $dbh, $botAPI, $message_id);
                        }
                    }

                    // Футбол
                    if($emoji == '⚽') {
                        if ($emoji_result > 2) {
                            gameStart($emoji, $user_id, $group_id, $GAME_FOOTBALL, $date_send_msg, $dbh, $botAPI, $message_id);
                        }
                    }
                    // Дартс
                    if($emoji == '🎯') {
                        if ($emoji_result == 6) {
                            gameStart($emoji, $user_id, $group_id, $GAME_DARTS, $date_send_msg, $dbh, $botAPI, $message_id);
                        }
                    }
                    // Боулинг
                    if($emoji == '🎳') {
                        if ($emoji_result == 6) {
                            gameStart($emoji, $user_id, $group_id, $GAME_BOWLING, $date_send_msg, $dbh, $botAPI, $message_id);
                        }
                    }
                    // Баскетбол
                    if($emoji == '🏀') {
                        if ($emoji_result > 3) {
                            gameStart($emoji, $user_id, $group_id, $GAME_BASKETBALL, $date_send_msg, $dbh, $botAPI, $message_id);
                        }
                    }
                    // Казино
                    if($emoji == '🎰') {
                        /*
                         * 64 - 777 (джекпот)
                         * 43 - лимоны (джекпот)
                         * 22 - вишня (джекпот)
                         * 1 - bar (джекпот)
                         * OR $emoji_result == 16 - ???
                         * */
                        if ($emoji_result == 22 or $emoji_result == 43 or $emoji_result == 1 or $emoji_result == 64) {
                            gameStart($emoji, $user_id, $group_id, $GAME_CASINO, $date_send_msg, $dbh, $botAPI, $message_id);
                        }
                    }
                }
            }
        }
    }
}

function gameStart($emoji, $user_id, $group_id, $game, $date_send_msg, $dbh, $botAPI, $message_id){
    $monday = new \DateTime('Monday this week');
    $mondayFormat =  $monday->format('Y-m-d');
    $currentDate = date('Y-m-d');
    $mondayFormat = $mondayFormat.' 00:00:00';
    $currentDate = $currentDate.' 23:59:00';

    $dbh->query('INSERT INTO chat_users_group (userid, groupid, type_game_id, date_time_tm, message_id, date) VALUE ("'.$user_id.'", "'.$group_id.'", '.$game.', '.$date_send_msg.', '.$message_id.', NOW())');
    $last_id = $dbh->lastInsertId();
    $getCountSucessUser = $dbh->query('SELECT COUNT(type_game_id) as count_game, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.date BETWEEN "'.$mondayFormat.'" AND "'.$currentDate.'" AND type_game_id = '.$game.' AND chat.userid = \''.$user_id.'\' AND chat.groupid = \''.$group_id.'\' GROUP BY users.first_name')->fetchAll();

    $message_id_user = 0;
    $getMessageId = $dbh->query('SELECT message_id FROM chat_users_group as chat WHERE chat.id = '.$last_id)->fetchAll();
    foreach ($getMessageId as $item){
        $message_id_user = $item['message_id'];
    }

    if(is_array($getCountSucessUser) && count($getCountSucessUser) > 0) {
        $textSendArray = array(
            1 => 'тебе выпал макс. число в кубик на этой неделе: ',
            4 => 'ты забил гол на этой неделе: ',
            3 => 'ты попал в мишень на этой неделе: ',
            2 => 'ты выбил страйк на этой неделе: ',
            5 => 'ты попал в корзину на этой неделе: ',
            6 => 'у тебя выпал джекпот на этой неделе: '
        );
        foreach ($getCountSucessUser as $val) {
            foreach ($textSendArray as $key => $value){
                if($game == $key){
                    $message = "$emoji ".$val['first_name'].", ".$value."".$val['count_game']." раз".PHP_EOL;
                }
            }
        }
        $message .= PHP_EOL;
        $textLeader = array(
            1 => 'лидер недели по победам в кубик: ',
            4 => 'лидер недели по забитым голам: ',
            3 => 'лидер недели по попаданиям в мишень: ',
            2 => 'лидер недели по выбитым страйкам: ',
            5 => 'лидер недели по попаданиям в корзину: ',
            6 => 'лидер недели по джекпотам: '
        );

        $getSuperGamer = $dbh->query('SELECT COUNT(type_game_id) as count_game, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.date BETWEEN "'.$mondayFormat.'" AND "'.$currentDate.'" AND type_game_id = '.$game.' AND chat.groupid = "'.$group_id.'" GROUP BY users.first_name ORDER BY count_game DESC LIMIT 1')->fetchAll();
        foreach ($getSuperGamer as $item) {
            foreach ($textLeader as $key => $value){
                if($game == $key){
                    $message .= "🏆 ".$item['first_name'].", ".$value."".$item['count_game']." раз";
                }
            }
        }

//        if($game == 1){
//            $message .= PHP_EOL;
//            $data = http_build_query([
//                'text' => $message,
//                'chat_id' => $group_id,
//                'reply_to_message_id' => $message_id_user
//            ]);
//            file_get_contents($botAPI . "/sendMessage?{$data}");
//        } else {
            $message .= PHP_EOL;
            $message .= PHP_EOL;
            $message .= PHP_EOL;


            $data = http_build_query([
                'text' => $message,
                'chat_id' => $group_id,
                'reply_to_message_id' => $message_id_user
            ]);
            $inline_button1 = array("text"=>"⚽","callback_data" => 'football_'.$user_id);
            $inline_button2 = array("text"=>"🎳","callback_data" => 'bowling_'.$user_id);
            $inline_button3 = array("text"=>"🎯","callback_data" => 'darts_'.$user_id);
            $inline_button4 = array("text"=>"🏀","callback_data" => 'basketball_'.$user_id);
            $inline_button5 = array("text"=>"🎲","callback_data" => 'cubic_'.$user_id);
            $inline_button6 = array("text"=>"🎰","callback_data" => 'casino_'.$user_id);
            $inline_keyboard = [[wordAd()], [$inline_button1, $inline_button2, $inline_button3, $inline_button4, $inline_button5, $inline_button6]];
            $keyboard=array("inline_keyboard"=>$inline_keyboard);
            $replyMarkup = json_encode($keyboard);
            $data = quotemeta($data);
            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup."&parse_mode=MarkdownV2&disable_web_page_preview=true");
            //file_put_contents('file.txt', '$data: '.print_r($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup."&parse_mode=MarkdownV2&disable_web_page_preview=true", 1)."\n", FILE_APPEND);
//            $a = file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup."&parse_mode=MarkdownV2&disable_web_page_preview=true");
//            $data = http_build_query([
//                'text' => $a,
//                'chat_id' => $group_id,
//            ]);
//            file_get_contents($botAPI . "/sendMessage?{$data}");
//            $d = $botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup."&parse_mode=MarkdownV2&disable_web_page_preview=true";
//            $data = http_build_query([
//                'text' => $d,
//                'chat_id' => $group_id,
//            ]);
//            file_get_contents($botAPI . "/sendMessage?{$data}");
//        }
    }
}


function wordAd(){
    $bot = include('typebotarr.php');

    $inline_button[] = array("text"=>"Добавить бота в группу ","url"=> $bot["addBotChat"]);
    $inline_button[] = array("text"=>"КУПИТЬ РЕКЛАМНОЕ МЕСТО 🚀","url"=> 'https://t.me/managerbotstg');
    $inline_button[] = array("text"=>"👉 КЛИК 👀","url"=> 'https://t.me/newsclik');
    $inline_button[] = array("text"=>"Случайный Стикер 😋 Бот","url"=> 'https://t.me/random_stikers_bot');
    $inline_button[] = array("text"=>"ДОБАВИТЬ БОТ В СВОЙ ЧАТ","url"=> 'https://t.me/stickersgame_bot?startgroup=Lichka');
    $inline_button[] = array("text"=>"👉 Игры в Телеграм 🎮 Бот","url"=> 'https://t.me/igry_v_telegram_bot');
    $inline_button[] = array("text"=>"Получить до 20 000 р. под 0%","url"=> 'https://t.me/podbor_zaimov_bot');
    $inline_button[] = array("text"=>"Что подарить? 🎁 Бот","url"=> 'https://t.me/chtomnepodarit_bot');
    $inline_button[] = array("text"=>"Чат Обмена Стикерами 🔥","url"=> 'https://t.me/stikerychat');
    $inline_button[] = array("text"=>"👉 ЕЩЕ БОТЫ 👾","url"=> 'https://t.me/top_bots_telegram');
    $inline_button[] = array("text"=>"👉 ДА или НЕТ? ⚖️ Бот","url"=> 'https://t.me/otvet_da_ili_net_bot');

    $rand_keys = array_rand($inline_button, 1);

    return $inline_button[$rand_keys];
}

# Обрабатываем сообщение
switch ($message) {
    case '/start':

        start($botAPI, $user_id, $urlBots);

        break;

    case '/play':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        play($botAPI, $all_chat_id, $group_type, $urlBots, $user_id);

        break;

    case '/ad':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        ad($botAPI, $all_chat_id);
        break;

    case '/contact':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        contact($botAPI, $all_chat_id);

        break;

    case '/topchats':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        topchats($botAPI, $all_chat_id, $dbh);

        break;

    case '/globaltop':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        globaltop($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO);

        break;

    case '/topgamers':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        topgamers($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO);

        break;


    case '/mywinschat':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        mywinschat($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO, $first_name, $user_id);

        break;

    case '/mywinsglobal':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        mywinsglobal($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO, $first_name, $user_id);

        break;

    case '/top':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        top($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO);

        break;



    case '/start'.$typeBotAll.'':

        start($botAPI, $user_id, $urlBots);

        break;

    case '/play'.$typeBotAll:

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");

        play($botAPI, $all_chat_id, $group_type, $urlBots, $user_id);

        break;

    case '/ad'.$typeBotAll:

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        ad($botAPI, $all_chat_id);
        break;

    case '/contact'.$typeBotAll:

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        contact($botAPI, $all_chat_id);

        break;

    case '/topchats'.$typeBotAll:

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        topchats($botAPI, $all_chat_id, $dbh);

        break;

    case '/globaltop'.$typeBotAll:

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        globaltop($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO);

        break;

    case '/topgamers'.$typeBotAll:

        topgamers($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO);

        break;


    case '/mywinschat'.$typeBotAll:

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        mywinschat($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO, $first_name, $user_id);

        break;


    case '/mywinsglobal'.$typeBotAll:

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        mywinsglobal($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO, $first_name, $user_id);

        break;


    case '/top'.$typeBotAll.'':

        // Deleting message
        $data_del = http_build_query([
            'chat_id' => $all_chat_id,
            'message_id' => $message_id,
        ]);
        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        top($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO);

        break;

        //Admin menu

    case '/statistic':

        $checkAdmin = $dbh->query('SELECT * FROM users WHERE role_id = '.$ADMIN.' AND userid = '.$user_id)->fetchAll();
        if(is_array($checkAdmin) && count($checkAdmin) > 0) {
            $getCountUsers = $dbh->query('SELECT COUNT(id) as user_all FROM users')->fetchAll();
            $countUserNumber = 0;
            foreach ($getCountUsers as $item){
                $countUserNumber = $item['user_all'];
            }
            $getCountGroup = $dbh->query('SELECT COUNT(id) as group_all FROM group_users')->fetchAll();
            $countGroupNumber = 0;
            foreach ($getCountGroup as $item){
                $countGroupNumber = $item['group_all'];
            }
            $getCountGroupActive = $dbh->query('SELECT COUNT(id) as group_all FROM group_users WHERE status = 1')->fetchAll();
            $countGroupNumberActive = 0;
            foreach ($getCountGroupActive as $item){
                $countGroupNumberActive = $item['group_all'];
            }
            $getCountGroupNoActive = $dbh->query('SELECT COUNT(id) as group_all FROM group_users WHERE status = 0')->fetchAll();
            $countGroupNumberNoActive = 0;
            foreach ($getCountGroupNoActive as $item){
                $countGroupNumberNoActive = $item['group_all'];
            }
            $message = '';
            $message .= 'Всего пользователей: '.$countUserNumber.''.PHP_EOL;
            $message .= 'Всего групп: '.$countGroupNumber.''.PHP_EOL;
            $message .= 'Всего активных групп: '.$countGroupNumberActive.''.PHP_EOL;
            $message .= 'Всего не активных групп: '.$countGroupNumberNoActive.''.PHP_EOL;

            $data = http_build_query([
                'text' => $message,
                'chat_id' => $all_chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        } else {
            $data = http_build_query([
                'text' => 'Упс, вы не являетесь администратором.',
                'chat_id' => $all_chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }

        break;

    case '/maillist':

        //$dbh->query('INSERT INTO group_users (groupid, title, type, status, date) VALUE ("' . $group_new_id . '", "' . $group_new_title . '", "' . $group_new_type . '", 1, NOW())');

        $checkAdmin = $dbh->query('SELECT * FROM users WHERE role_id = '.$ADMIN.' AND userid = '.$user_id)->fetchAll();
        if(is_array($checkAdmin) && count($checkAdmin) > 0) {
            $checkTime = $dbh->query('SELECT * FROM maillist')->fetchAll();
                if(is_array($checkTime) && count($checkTime) > 0) {
                    foreach ($checkTime as $row){
                        if($row['date_time_tm'] > time()){
                            $data = http_build_query([
                                'text' => "Рассылку можно делать раз в 24 часа",
                                'chat_id' => $all_chat_id
                            ]);
                            file_get_contents($botAPI . "/sendMessage?{$data}");
                            exit;
                        }
                    }
                } else {
                    $dateTime = time();
                    $dbh->query('INSERT INTO maillist (date_time_tm, date)  VALUE ('.$dateTime .', NOW())');
                }

            $getActiveGroup = $dbh->query('SELECT * FROM group_users WHERE status = 1')->fetchAll();
            foreach ($getActiveGroup as $item){
                $data = http_build_query([
                    'text' => "Нажми на этот emoji, чтобы играть!👇",
                    'chat_id' => $item['groupid']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");

                $data = http_build_query([
                    'emoji' => '⚽',
                    'protect_content' => false,
                    'chat_id' => $item['groupid']
                ]);
                file_get_contents($botAPI . "/sendDice?{$data}");
            }

            $data = http_build_query([
                'text' => 'Всем чатам и группам отправился emoji',
                'chat_id' => $all_chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");

            $dateTime = time() + 60 * 60 * 24;
            $dbh->query('UPDATE maillist SET date_time_tm = '.$dateTime.', date = NOW()');
            $dbh->query('INSERT INTO maillistlog (userid, date)  VALUE ("'.$user_id.'", NOW())');
        } else {
            $data = http_build_query([
                'text' => 'Упс, вы не являетесь администратором.',
                'chat_id' => $all_chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }

        break;


    default:

        break;
}

function contact($botAPI, $all_chat_id){
    $data = http_build_query([
        'text' => 'Наш чат — https://t.me/chatdlyaigry
Админ — @managerbotstg',
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function ad($botAPI, $all_chat_id){
    $data = http_build_query([
        'text' => 'Чтобы купить рекламу в чатах, где есть наш бот, напишите менеджеру:
@managerbotstg',
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function topchats($botAPI, $all_chat_id, $dbh){
    $monday = new \DateTime('Monday this week');
    $mondayFormat =  $monday->format('Y-m-d');
    $currentDate = date('Y-m-d');
    $mondayFormat = $mondayFormat.' 00:00:00';
    $currentDate = $currentDate.' 23:59:00';

    $query = $dbh->query('SELECT COUNT(chat.id) as top, chat.groupid as chat_group, group_users.title FROM chat_users_group as chat INNER JOIN group_users ON chat.groupid = group_users.groupid WHERE chat.date BETWEEN "'.$mondayFormat.'" AND "'.$currentDate.'" GROUP BY chat.groupid, group_users.title ORDER BY top DESC LIMIT 10')->fetchAll();
    $top = 'Топ 10 чатов за неделю:'.PHP_EOL;
    $top .= PHP_EOL;
    if(is_array($query) && count($query) > 0) {
        foreach ($query as $key => $row) {
            $key++;
            $top .= $key.'. '.$row['title'].', побед: '.$row['top'].PHP_EOL;
        }
    }
    $data = http_build_query([
        'text' => $top,
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function globaltop($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO){
    $bestAll = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    $top = '';
    $top .= 'Топ лучших игроков:'.PHP_EOL;
    $top .= PHP_EOL;
    if(is_array($bestAll) && count($bestAll) > 0) {
        foreach ($bestAll as $row) {
            $top .= '🏆 '.$row["first_name"].' - чемпион бота: '.$row["best"].' побед'.PHP_EOL;
        }
    }

    $bestCubic = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_CUBIC.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCubic) && count($bestCubic) > 0) {
        foreach ($bestCubic as $row) {
            $top .= '🎲 '.$row["first_name"].' - лидер по победам в кубик: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBowling = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_BOWLING.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBowling) && count($bestBowling) > 0) {
        foreach ($bestBowling as $row) {
            $top .= '🎳 '.$row["first_name"].' - лидер по выбитым страйкам: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestDarts = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_DARTS.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestDarts) && count($bestDarts) > 0) {
        foreach ($bestDarts as $row) {
            $top .= '🎯 '.$row["first_name"].' - лидер попаданий в мишень: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestFootball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_FOOTBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestFootball) && count($bestFootball) > 0) {
        foreach ($bestFootball as $row) {
            $top .= '⚽️'.$row["first_name"].' - лидер по забитым голам: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBasketball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_BASKETBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBasketball) && count($bestBasketball) > 0) {
        foreach ($bestBasketball as $row) {
            $top .= '🏀 '.$row["first_name"].' - лидер по попаданиям в корзину: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestCasino = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_CASINO.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCasino) && count($bestCasino) > 0) {
        foreach ($bestCasino as $row) {
            $top .= '🎰 '.$row["first_name"].' - лидер по джекпотам: '.$row["best"].' раз'.PHP_EOL;
        }
    }

    $data = http_build_query([
        'text' => $top,
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function topgamers($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO){
    $bestAll = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    $top = '';
    $top .= 'Топ лучших игроков:'.PHP_EOL;
    $top .= PHP_EOL;
    if(is_array($bestAll) && count($bestAll) > 0) {
        foreach ($bestAll as $row) {
            $top .= '🏆 '.$row["first_name"].' - чемпион бота: '.$row["best"].' побед'.PHP_EOL;
        }
    }

    $bestCubic = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_CUBIC.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCubic) && count($bestCubic) > 0) {
        foreach ($bestCubic as $row) {
            $top .= '🎲 '.$row["first_name"].' -  лидер по победам в кубик: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBowling = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_BOWLING.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBowling) && count($bestBowling) > 0) {
        foreach ($bestBowling as $row) {
            $top .= '🎳 '.$row["first_name"].' -  лидер по выбитым страйкам: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestDarts = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_DARTS.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestDarts) && count($bestDarts) > 0) {
        foreach ($bestDarts as $row) {
            $top .= '🎯 '.$row["first_name"].' -  лидер попаданий в мишень: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestFootball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_FOOTBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestFootball) && count($bestFootball) > 0) {
        foreach ($bestFootball as $row) {
            $top .= '⚽️'.$row["first_name"].' -  лидер по забитым голам: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBasketball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_BASKETBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBasketball) && count($bestBasketball) > 0) {
        foreach ($bestBasketball as $row) {
            $top .= '🏀 '.$row["first_name"].' -  лидер по попаданиям в корзину: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestCasino = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE type_game_id = '.$GAME_CASINO.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCasino) && count($bestCasino) > 0) {
        foreach ($bestCasino as $row) {
            $top .= '🎰 '.$row["first_name"].' -   лидер по джекпотам: '.$row["best"].' раз'.PHP_EOL;
        }
    }

    $data = http_build_query([
        'text' => $top,
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function mywinschat($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO, $first_name, $user_id){
    $bestAll = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND chat.userid = \''.$user_id.'\'  AND type_game_id != 0 GROUP BY chat.userid, users.first_name ORDER BY best')->fetchAll();
    $top = '';
    $top .= $first_name. ', твои победы:'.PHP_EOL;
    $top .= PHP_EOL;
    if(is_array($bestAll) && count($bestAll) > 0) {
        foreach ($bestAll as $row) {
            $top .= '🏆 Общие: '.$row["best"].' побед'.PHP_EOL;
        }
    }

    $bestCubic = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_CUBIC.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCubic) && count($bestCubic) > 0) {
        foreach ($bestCubic as $row) {
            $top .= '🎲 Победы в кубик: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBowling = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_BOWLING.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBowling) && count($bestBowling) > 0) {
        foreach ($bestBowling as $row) {
            $top .= '🎳 Выбито страйков: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestDarts = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_DARTS.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestDarts) && count($bestDarts) > 0) {
        foreach ($bestDarts as $row) {
            $top .= '🎯 Попаданий в мишень: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestFootball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_FOOTBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestFootball) && count($bestFootball) > 0) {
        foreach ($bestFootball as $row) {
            $top .= '⚽️ Забито голов: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBasketball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_BASKETBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBasketball) && count($bestBasketball) > 0) {
        foreach ($bestBasketball as $row) {
            $top .= '🏀 Попаданий в корзину: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestCasino = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_CASINO.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCasino) && count($bestCasino) > 0) {
        foreach ($bestCasino as $row) {
            $top .= '🎰 Джекпоты: '.$row["best"].' раз'.PHP_EOL;
        }
    }

    $data = http_build_query([
        'text' => $top,
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function mywinsglobal($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO, $first_name, $user_id){
    $bestAll = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.userid = \''.$user_id.'\'  AND type_game_id != 0 GROUP BY chat.userid, users.first_name ORDER BY best')->fetchAll();
    $top = '';
    $top .= $first_name. ', твои победы:'.PHP_EOL;
    $top .= PHP_EOL;
    if(is_array($bestAll) && count($bestAll) > 0) {
        foreach ($bestAll as $row) {
            $top .= '🏆 Общие: '.$row["best"].' побед'.PHP_EOL;
        }
    }

    $bestCubic = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_CUBIC.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCubic) && count($bestCubic) > 0) {
        foreach ($bestCubic as $row) {
            $top .= '🎲 Победы в кубик: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBowling = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_BOWLING.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBowling) && count($bestBowling) > 0) {
        foreach ($bestBowling as $row) {
            $top .= '🎳 Выбито страйков: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestDarts = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_DARTS.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestDarts) && count($bestDarts) > 0) {
        foreach ($bestDarts as $row) {
            $top .= '🎯 Попаданий в мишень: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestFootball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_FOOTBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestFootball) && count($bestFootball) > 0) {
        foreach ($bestFootball as $row) {
            $top .= '⚽️ Забито голов: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBasketball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_BASKETBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBasketball) && count($bestBasketball) > 0) {
        foreach ($bestBasketball as $row) {
            $top .= '🏀 Попаданий в корзину: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestCasino = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE chat.userid = \''.$user_id.'\' AND type_game_id = '.$GAME_CASINO.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCasino) && count($bestCasino) > 0) {
        foreach ($bestCasino as $row) {
            $top .= '🎰 Джекпоты: '.$row["best"].' раз'.PHP_EOL;
        }
    }

    $data = http_build_query([
        'text' => $top,
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function play($botAPI, $all_chat_id, $group_type, $urlBots, $user_id){
    if($group_type == "private"){
        $data = http_build_query([
            'text' => "Я работаю только в чатах. Добавь меня и я устрою захватывающую игру среди участников! 🚀",
            'chat_id' => $all_chat_id
        ]);
        $inline_button1 = array("text"=>"Добавить бота в группу ","url"=>$urlBots);
        $inline_keyboard = [[$inline_button1]];
        $keyboard=array("inline_keyboard"=>$inline_keyboard);
        $replyMarkup = json_encode($keyboard);
        file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
    }
    if($group_type == "group" || $group_type == "supergroup"){
        $data = http_build_query([
            'chat_id' => $all_chat_id,
            'video' => 'https://game.cryptopushbot.ru/1.mp4',
            'caption' => 'Привет\\, я игровой бот для чатов\\! 👋

В игре есть 6 основных emoji\\: 

🎯 \\- Попадай в мишень
🎳 \\- Выбивай страйки
⚽️ \\- Забивай голы
🎰 \\- Выбивай ДжекПоты
🏀 \\- Попадай в корзину
🎲 \\- Выбивай max число в кубик

Telegram гарантирует честную игру за счёт рандома, а [«Игровой бот»](https://t.me/stickersgame_bot) будет учитывать победы и составлять топ среди участников и чатов\\.

СТАНЬ ТОП 1 ИГРОКОМ ИЛИ ПОБЕЖДАЙ КОМАНДОЙ\\!🥇'
        ]);

        $inline_button1 = array("text"=>"⚽","callback_data" => 'football_'.$user_id);
        $inline_button2 = array("text"=>"🎳","callback_data" => 'bowling_'.$user_id);
        $inline_button3 = array("text"=>"🎯","callback_data" => 'darts_'.$user_id);
        $inline_button4 = array("text"=>"🏀","callback_data" => 'basketball_'.$user_id);
        $inline_button5 = array("text"=>"🎲","callback_data" => 'cubic_'.$user_id);
        $inline_button6 = array("text"=>"🎰","callback_data" => 'casino_'.$user_id);
        $inline_keyboard = [[$inline_button1, $inline_button2, $inline_button3, $inline_button4, $inline_button5, $inline_button6]];
        $keyboard=array('inline_keyboard'=>$inline_keyboard);
        $replyMarkup = json_encode($keyboard);
        file_get_contents($botAPI . "/sendVideo?{$data}&reply_markup=".$replyMarkup."&parse_mode=MarkdownV2");
    }
}

function top($botAPI, $all_chat_id, $dbh, $GAME_CUBIC, $GAME_BOWLING, $GAME_DARTS, $GAME_FOOTBALL, $GAME_BASKETBALL, $GAME_CASINO){
    $bestAll = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND type_game_id != 0  GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    $top = '';
    $top .= 'Топ чата:'.PHP_EOL;
    $top .= PHP_EOL;
    if(is_array($bestAll) && count($bestAll) > 0) {
        foreach ($bestAll as $row) {
            $top .= '🏆 '.$row["first_name"].' - чемпион чата: '.$row["best"].' побед'.PHP_EOL;
        }
    }

    $bestCubic = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND type_game_id = '.$GAME_CUBIC.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCubic) && count($bestCubic) > 0) {
        foreach ($bestCubic as $row) {
            $top .= '🎲 '.$row["first_name"].' -  лидер по победам в кубик: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBowling = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND type_game_id = '.$GAME_BOWLING.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBowling) && count($bestBowling) > 0) {
        foreach ($bestBowling as $row) {
            $top .= '🎳 '.$row["first_name"].' -  лидер по выбитым страйкам: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestDarts = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND type_game_id = '.$GAME_DARTS.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestDarts) && count($bestDarts) > 0) {
        foreach ($bestDarts as $row) {
            $top .= '🎯 '.$row["first_name"].' -  лидер попаданий в мишень: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestFootball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND type_game_id = '.$GAME_FOOTBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestFootball) && count($bestFootball) > 0) {
        foreach ($bestFootball as $row) {
            $top .= '⚽️'.$row["first_name"].' -  лидер по забитым голам: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestBasketball = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND type_game_id = '.$GAME_BASKETBALL.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestBasketball) && count($bestBasketball) > 0) {
        foreach ($bestBasketball as $row) {
            $top .= '🏀 '.$row["first_name"].' -  лидер по попаданиям в корзину: '.$row["best"].' раз'.PHP_EOL;
        }
    }
    $bestCasino = $dbh->query('SELECT chat.userid, COUNT(chat.userid) as best, users.first_name FROM chat_users_group as chat INNER JOIN users ON chat.userid = users.userid WHERE groupid = "'.$all_chat_id.'" AND type_game_id = '.$GAME_CASINO.' GROUP BY chat.userid, users.first_name ORDER BY best DESC LIMIT 1')->fetchAll();
    if(is_array($bestCasino) && count($bestCasino) > 0) {
        foreach ($bestCasino as $row) {
            $top .= '🎰 '.$row["first_name"].' -   лидер по джекпотам: '.$row["best"].' раз'.PHP_EOL;
        }
    }

    $data = http_build_query([
        'text' => $top,
        'chat_id' => $all_chat_id
    ]);
    file_get_contents($botAPI . "/sendMessage?{$data}");
}

function start($botAPI, $user_id, $urlBots){
    $data = http_build_query([
        'text' => "Привет! 👋
Я игровой бот, который работает только в чатах. Добавь меня в чат и я устрою захватывающую игру среди участников! 🚀

/play - играть 
/topchats - топ 10 лучших чатов недели
/globaltop - глобальный топ игроков
/ad - реклама в боте
/contact - контакты
",
        'chat_id' => $user_id
    ]);
    $inline_button1 = array("text"=>"Добавить бота в группу ","url"=> $urlBots);
    $inline_keyboard = [[$inline_button1]];
    $keyboard=array("inline_keyboard"=>$inline_keyboard);
    $replyMarkup = json_encode($keyboard);
    file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
}

if (isset($update['callback_query'])) {
    $groupCallBackId = $update['callback_query']['message']['chat']['id'];
    $pars = explode('_', $update['callback_query']['data']);
    if(isset($pars[0]) && isset($pars[1])) {
        $data = http_build_query([
            'text' => "Нажми на этот emoji, чтобы играть!👇",
            'chat_id' => $groupCallBackId
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
        if ($pars[0] == 'bowling') {
            $data = http_build_query([
                'emoji' => '🎳',
                'protect_content' => false,
                'chat_id' => $groupCallBackId
            ]);
            file_get_contents($botAPI . "/sendDice?{$data}");
        }
        if ($pars[0] == 'football') {
            $data = http_build_query([
                'emoji' => '⚽',
                'protect_content' => false,
                'chat_id' => $groupCallBackId
            ]);
            file_get_contents($botAPI . "/sendDice?{$data}");
        }
        if ($pars[0] == 'darts') {
            $data = http_build_query([
                'emoji' => '🎯',
                'protect_content' => false,
                'chat_id' => $groupCallBackId
            ]);
            file_get_contents($botAPI . "/sendDice?{$data}");
        }
        if ($pars[0] == 'basketball') {
            $data = http_build_query([
                'emoji' => '🏀',
                'protect_content' => false,
                'chat_id' => $groupCallBackId
            ]);
            file_get_contents($botAPI . "/sendDice?{$data}");
        }
        if ($pars[0] == 'cubic') {
            $data = http_build_query([
                'emoji' => '🎲',
                'protect_content' => false,
                'chat_id' => $groupCallBackId
            ]);
            file_get_contents($botAPI . "/sendDice?{$data}");
        }
        if ($pars[0] == 'casino') {
            $data = http_build_query([
                'emoji' => '🎰',
                'protect_content' => false,
                'chat_id' => $groupCallBackId
            ]);
            file_get_contents($botAPI . "/sendDice?{$data}");
        }
    }
}


function sendSms($botAPI, $replyMarkup, $group_id, $method, $data)
{

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $botAPI."/".$method."?".$data."&reply_markup='.$replyMarkup.'&parse_mode=MarkdownV2");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
    $out = curl_exec($curl);
    curl_close($curl);
}