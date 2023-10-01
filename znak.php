<?php

//ToDo Перенести в общую функцию вывод профиля

require_once('token.php');
require_once('bd.php');
require_once('typebot.php');

// Role
$ADMIN = 1;
$MODER = 2;
$USER = 3;

// Action
$VIEW_ANKET = 1; // Смотреть анкеты
$VIEW_PROFILE = 2; // Смотреть profile
$VIEW_ANKET_LIKE_NOTI = 3; // Смотреть анкеты по уведомлению

//Action for table likes_user
$ACTION_LIKE = 1; // Поставиди лайк
$ACTION_DISLIKE = 2; // Поставиди дизлайк


$banUserTime = time() + 60 * 5; // Бан на 5 минут;
$banTimeString = '5 минут'; // Текст для бана
$countProdGame = 1; // Время, через которое можно отправлять сообщение

# Принимаем запрос
$data = json_decode(file_get_contents('php://input'), TRUE);
file_put_contents('file.txt', '$data: '.print_r($data, 1)."\n", FILE_APPEND); // Посмотреть что пришло от сервера


//https://api.telegram.org/bot*Токен бота*/setwebhook?url=*ссылка на бота*

$typeMessage = $data['message'];

# Обрабатываем ручной ввод или нажатие на кнопку
$data = $data['callback_query'] ? $data['callback_query'] : $data['message'];

$referal = $data['text'];

//$maskText = '[^A-Za-zА-Яа-я0-9 !@#$%^&*()№;%]';

# Важные константы
define('TOKEN', $token);

# Записываем сообщение пользователя
$message = $data['text'] ? $data['text'] : $data['data'];

$files = $data['photo'];


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

$globalArrayInfo = array();

// Если пишем сообщение с самого бота, то выдается id пользователя, если в группе, то id группы
$all_chat_id = $data['chat']['id'];

$dbh =  new PDO('mysql:host='.$host.';charset=utf8;dbname='.$dbName, $bdUser, $bdPassword);
$dbh->query('SET NAMES utf8mb4');

# Обрабатываем сообщение

$text = '';

/*
 *
 * Блок по обработке сообщений
 *
 * */
if(isset($message) && !empty($message)){
    if ($message == '/start'){
        $checkUser = $dbh->query('SELECT * FROM users WHERE userid = \''.$user_id.'\'')->fetchAll();
        if(is_array($checkUser) && count($checkUser) > 0) {
            $checkName = $dbh->query('SELECT * FROM employee WHERE userid = \''.$user_id.'\' AND (name IS NULL OR name = "")')->fetchAll();
            if(is_array($checkName) && count($checkName) > 0) {
                $data = http_build_query([
                    'text' => "Пройти регистрацию",
                    'chat_id' => $user_id
                ]);
                $inline_button1 = array("text"=>"Пройти регистрацию ","url" => 'https://t.me/znaktest116_bot/znak?userid='.$user_id);
                $inline_keyboard = [[$inline_button1]];
                $keyboard=array("inline_keyboard"=>$inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
            } else {
                $data = http_build_query([
                    'text' => 'Вы успешно прошли регистрацию! Можете смотреть анкеты.',
                    'chat_id' => $user_id
                ]);
                $inline_button1 = array("text"=>"Смотреть анкеты","callback_data" => 'viewAnket_1');
                $inline_keyboard = [[$inline_button1]];
                $keyboard=array("inline_keyboard"=>$inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
            }
        } else {
            $date = date('Y-m-d H:i:s');
            $dbh->query('INSERT INTO users (userid, role_id, date) VALUE ("' . $user_id . '", '.$USER.', "' . $date . '")');
            $dbh->query('INSERT INTO employee (userid, first_name, username,  create_at, update_at) VALUE ("' . $user_id . '",  \''.$first_name.'\', \''.$username.'\', "'.$date.'", "'.$date.'")');
            $data = http_build_query([
                'text' => "Пройти регистрацию",
                'chat_id' => $user_id
            ]);
            $inline_button1 = array("text"=>"Пройти регистрацию ","url" => 'https://t.me/znaktest116_bot/znak?userid='.$user_id);
            $inline_keyboard = [[$inline_button1]];
            $keyboard=array("inline_keyboard"=>$inline_keyboard);
            $replyMarkup = json_encode($keyboard);
            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
        }
    }

    if ($message == '/lol'){
        $send_data = [
            'chat_id' => $user_id,
            'text'   => '🔍🔍🔍',
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => '❤'],
                        ['text' => '❌'],
                        ['text' => '🏠'],
                    ],
                ]
            ]
        ];
        sendTelegram($send_data, 'sendMessage', $botAPI, $user_id);
    }
    if($message == '❤'){
        $checkAction = $dbh->query('SELECT * FROM action_users WHERE userid = \''.$user_id.'\'')->fetchAll();
        if(is_array($checkAction) && count($checkAction) > 0) {
            $action = 0;
            foreach ($checkAction as $act){
                $action = $act['action_id'];
            }
            if($action == 1){
                $getLikeForUser = $dbh->query('SELECT * FROM likes_user WHERE myid = \''.$user_id.'\' AND id = (SELECT MAX(id) FROM likes_user WHERE myid = \''.$user_id.'\')')->fetchAll();
                $employee_id = 0;
                $like_userid = 0;
                if(is_array($getLikeForUser) && count($getLikeForUser) > 0) {

                    foreach ($getLikeForUser as $item) {
                        $employee_id = $item['employee_id'];
                        $like_userid = $item['like_userid'];
                        $dbh->query('UPDATE likes_user SET action = '.$ACTION_LIKE.' WHERE myid = '.$item['myid'].' AND like_userid = '.$item['like_userid']);
                    }

                    $data = http_build_query([
                        'chat_id' => $like_userid,
                        'text' => 'Кому-то понравилась твоя анкета. Хочешь взглянуть?'
                    ]);

                    $inline_button1 = array("text"=>"👀","callback_data" => 'viewAnketForUser_'.$user_id);
                    $inline_button2 = array("text"=>"❌","callback_data" => 'noViewAnketForUser_'.$user_id);
                    $inline_keyboard = [[$inline_button1, $inline_button2]];
                    $keyboard=array('inline_keyboard'=> $inline_keyboard);
                    $replyMarkup = json_encode($keyboard);
                    file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

                    // Показываем следующую анкету
                    viewAnket($botAPI, $user_id, $dbh, $VIEW_ANKET);

                }
            }
        }
    }

    if($message == '❌'){
        $checkAction = $dbh->query('SELECT * FROM action_users WHERE userid = \''.$user_id.'\'')->fetchAll();
        if(is_array($checkAction) && count($checkAction) > 0) {
            $action = 0;
            foreach ($checkAction as $act){
                $action = $act['action_id'];
            }
            if($action == 1){
                $getLikeForUser = $dbh->query('SELECT * FROM likes_user WHERE myid = \''.$user_id.'\' AND id = (SELECT MAX(id) FROM likes_user WHERE myid = \''.$user_id.'\')')->fetchAll();
                $employee_id = 0;
                $likeUserid = 0;
                if(is_array($getLikeForUser) && count($getLikeForUser) > 0) {
                    foreach ($getLikeForUser as $item) {
                        $employee_id = $item['employee_id'];
                        $like_userid = $item['like_userid'];
                        $dbh->query('UPDATE likes_user SET action = '.$ACTION_DISLIKE.' WHERE myid = '.$item['myid'].' AND like_userid = '.$item['like_userid']);
                    }
                    // Показываем следующую анкету
                    viewAnket($botAPI, $user_id, $dbh, $VIEW_ANKET);
                }
            }
        }
    }

    if($message == '👍'){
        $getTemplateLike = $dbh->query('SELECT * FROM template_like WHERE myid = '.$user_id)->fetchAll();
        if (is_array($getTemplateLike) && count($getTemplateLike) > 0) {
            $myId = 0;
            $likeUserid = 0;
            foreach ($getTemplateLike as $item) {
                $myId = $item['myid'];
                $likeUserid = $item['like_userid'];
            }
            $dbh->query('UPDATE likes_user SET action = '.$ACTION_LIKE.' WHERE myid = '.$myId.' AND like_userid = '.$likeUserid);

            $getProfileLike = $dbh->query('SELECT * FROM employee WHERE userid = '.$likeUserid)->fetchAll();
            if (is_array($getProfileLike) && count($getProfileLike) > 0) {
                $username = '';
                foreach ($getProfileLike as $item) {
                    $username = $item['username'];
                }
                $data = http_build_query([
                    'chat_id' => $myId,
                    'text' => 'Есть взаимная симпатия! Начинай общаться 👉 @'.$username
                ]);

                $inline_button1 = array("text"=>"Пожаловаться","callback_data" => 'complaint_'.$user_id);
                $inline_keyboard = [[$inline_button1]];
                $keyboard=array('inline_keyboard'=> $inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
                $dbh->query('DELETE FROM template_like WHERE myid = '.$user_id);
            }

        }
        $dbh->query('DELETE FROM template_like WHERE myid = '.$user_id);
    }

    if($message == '👎'){

        $getTemplateLike = $dbh->query('SELECT * FROM template_like WHERE myid = '.$user_id)->fetchAll();
        if (is_array($getTemplateLike) && count($getTemplateLike) > 0) {
            $myId = 0;
            $likeUserid = 0;
            foreach ($getTemplateLike as $item) {
                $myId = $item['myid'];
                $likeUserid = $item['like_userid'];
            }
            $dbh->query('UPDATE likes_user SET action = '.$ACTION_DISLIKE.' WHERE myid = '.$myId.' AND like_userid = '.$likeUserid);
        }
        
        $data = http_build_query([
            'chat_id' => $myId,
            'text' => 'Продолжить смотреть анкеты?'
        ]);

        $inline_button1 = array("text"=>"Да","callback_data" => 'viewAnket_1');
        $inline_button2 = array("text"=>"Меню","callback_data" => 'mainMenu_1');
        $inline_keyboard = [[$inline_button1, $inline_button2]];
        $keyboard=array('inline_keyboard'=> $inline_keyboard);
        $replyMarkup = json_encode($keyboard);
        file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
        $dbh->query('DELETE FROM template_like WHERE myid = '.$user_id);
        $dbh->query('DELETE FROM action_users WHERE userid = '.$user_id);
    }

    if($message == 'В главное меню'){
        $dbh->query('DELETE FROM action_users WHERE userid = '.$user_id);
        $send_data = [
            'chat_id' => $user_id,
            'text'   => 'Главное меню',
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => 'Смотреть анкеты'],
                        ['text' => 'Мой профиль']
                    ],
                ]
            ]
        ];
        sendTelegram($send_data, 'sendMessage', $botAPI, $user_id);
    }

    if($message == 'Смотреть анкеты'){
        $dbh->query('INSERT INTO action_users (userid, action_id) VALUE ("' . $user_id . '", '.$VIEW_ANKET.')');
        viewAnket($botAPI, $user_id, $dbh, $VIEW_ANKET);
    }
    if($message == 'Мой профиль'){
        $dbh->query('DELETE FROM action_users WHERE userid = '.$user_id);
        myProfile($botAPI, $user_id, $dbh);
    }
}

/*
 *
 * Конец блока по обработке сообщений
 *
 *
 * */


//$data = http_build_query([
//    'text' => $send_msg,
//    'chat_id' => $user_id
//]);
//file_get_contents($botAPI . "/sendMessage?{$data}");

function getAge( $birthday ){
    $timeZone = new DateTimeZone ( 'Europe/Moscow' ); // временная зона
    $datetime1 = new DateTime ( $birthday, $timeZone ); // д.р.
    $datetime2 = new DateTime (); // текущая дата
    $interval = $datetime1->diff ( $datetime2 ); // собственно вычисление
    return $interval->format ( '%y' ); // вывод на экран
}


function deleteMessage($botAPI, $dbh, $user_id, $message_id_c){
    $dbh->query('INSERT INTO last_message_group (userid, message) VALUE ("'.$user_id.'", "'.$message_id_c.'")');
    $checkMessage = $dbh->query('SELECT * FROM last_message_group WHERE userid = \''.$user_id.'\' AND id = (SELECT MAX(id) FROM last_message_group WHERE userid = \''.$user_id.'\')')->fetchAll();
    if(is_array($checkMessage) && count($checkMessage) > 0) {
        foreach ($checkMessage as $row){
            $data_del = http_build_query([
                'chat_id' => $user_id,
                'message_id' => $row['message'],
            ]);
            file_get_contents($botAPI . "/deleteMessage?{$data_del}");
        }
    }
}

if (isset($update['callback_query'])) {
    $date = date("Y-m-d H:i:s");
    $groupCallBackId = $update['callback_query']['message']['chat']['id'];
    $message_id_c = $update['callback_query']['message']['message_id'];
    $pars = explode('_', $update['callback_query']['data']);

    if(isset($pars[0]) && isset($pars[1])) {
        if ($pars[0] == 'polSogl') {
            deleteMessage($botAPI, $dbh, $user_id, $message_id_c);
            $data = http_build_query([
                'chat_id' => $user_id,
                'photo' => 'https://znak.cryptopushbot.ru/files/next.jpeg',
                'caption' => 'Помни, что в интернете люди могут выдавать себя за других.

Нажимая "Продолжить", ты принимаешь пользовательское соглашение, политику конфиденциальности и что тебе есть 18 лет.'
            ]);

            $inline_button1 = array("text"=>"✅ Продолжить","callback_data" => 'viewAnket_1');
            $inline_keyboard = [[$inline_button1]];
            $keyboard=array('inline_keyboard'=> $inline_keyboard);
            $replyMarkup = json_encode($keyboard);
            file_get_contents($botAPI . "/sendPhoto?{$data}&reply_markup=".$replyMarkup);
        }

        // Главное меню
        if($pars[0] == 'mainMenu'){
            $send_data = [
                'chat_id' => $user_id,
                'text'   => 'Главное меню',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Смотреть анкеты'],
                            ['text' => 'Мой профиль']
                        ],
                    ]
                ]
            ];
            sendTelegram($send_data, 'sendMessage', $botAPI, $user_id);
        }

        // Просмотр своего профиля
        if($pars[0] == 'mainProfileView'){

        }

        // Просмотрех всех анкет viewAllAnket
        if($pars[0] == 'viewAnket'){
            viewAnket($botAPI, $user_id, $dbh, $VIEW_ANKET);
        }

        // Просмотр анкеты по уведомлеиню (после того, как другой человек лайкнул)
        if($pars[0] == 'viewAnketForUser'){
            $dbh->query('DELETE FROM action_users WHERE userid = '.$user_id);
            $dbh->query('INSERT INTO action_users (userid, action_id) VALUE ("' . $user_id . '", '.$VIEW_ANKET_LIKE_NOTI.')');


            $checkLike = $dbh->query('SELECT * FROM template_like WHERE myid = "'.$user_id.'" AND like_userid = '.$pars[1])->fetchAll();
            if (is_array($checkLike) && count($checkLike) > 0) {

            } else {
                $dbh->query('INSERT INTO template_like (myid, like_userid) VALUE ("' . $user_id . '", '.$pars[1].')');
            }

            viewOneAnket($botAPI, $user_id, $pars[1], $dbh);
        }

        if($pars[0] == 'complaint'){
            $data = http_build_query([
                'text' => 'Пожаловались на пользователя - '.$pars[1],
                'chat_id' => 879771353
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
            $send_data = [
                'chat_id' => $user_id,
                'text'   => 'Главное меню',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Смотреть анкеты'],
                            ['text' => 'Мой профиль']
                        ],
                    ]
                ]
            ];
            sendTelegram($send_data, 'sendMessage', $botAPI, $user_id);
        }

    }
}

function viewAnket($botAPI, $user_id, $dbh, $VIEW_ANKET){
    $dbh->query('DELETE FROM action_users WHERE userid = '.$user_id);
    $dbh->query('INSERT INTO action_users (userid, action_id) VALUE ("' . $user_id . '", '.$VIEW_ANKET.')');

    $getMyInfo= $dbh->query('SELECT * FROM employee WHERE userid = '.$user_id)->fetchAll();
    $cell = 0;
    foreach ($getMyInfo as $myInfo){
        $cell = $myInfo['sex_znak'];
    }

    $getLikes = $dbh->query('SELECT * FROM likes_user WHERE myid = '.$user_id.' AND (action = 1 OR action = 2)')->fetchAll();
    $iskl_userid = '';
    if(is_array($getLikes) && count($getLikes) > 0) {
        foreach ($getLikes as $lk){
            if($lk['myid'] == $user_id){
                $iskl_userid .= ' AND userid != '.$lk['like_userid'];
            }
        }
    }

//            $data = http_build_query([
//                'text' => $iskl_userid,
//                'chat_id' => $user_id
//            ]);
//            file_get_contents($botAPI . "/sendMessage?{$data}");
//
//            $data = http_build_query([
//                'text' => 'SELECT * FROM employee as em WHERE userid != '.$user_id.' AND sex = '.$cell.' AND em.id = (SELECT MAX(emm.id) FROM employee as emm LEFT JOIN likes_user as lk ON emm.id = lk.employee_id WHERE sex = '.$cell.' AND userid != '.$user_id.')',
//                'chat_id' => $user_id
//            ]);
//            file_get_contents($botAPI . "/sendMessage?{$data}");

    $arrayPhoto = [];
    if(!empty($iskl_userid)){
        $getEmployee= $dbh->query('SELECT * FROM employee as em WHERE userid != '.$user_id.' AND sex = '.$cell.' AND em.id = (SELECT MAX(emm.id) FROM employee as emm LEFT JOIN likes_user as lk ON emm.id = lk.employee_id WHERE sex = '.$cell.' AND userid != '.$user_id.' '.$iskl_userid.')')->fetchAll();
    } else {
        $getEmployee= $dbh->query('SELECT * FROM employee as em WHERE userid != '.$user_id.' AND sex = '.$cell.' AND em.id = (SELECT MAX(emm.id) FROM employee as emm LEFT JOIN likes_user as lk ON emm.id = lk.employee_id WHERE sex = '.$cell.' AND userid != '.$user_id.')')->fetchAll();
    }
    if(is_array($getEmployee) && count($getEmployee) > 0) {
        $can_user_id = 0;
        $employee_name = '';
        $employee_id = 0;
        $age = '';
        $city = '';
        $myInfo = '';
        foreach ($getEmployee as $value){
            $can_user_id = $value['userid'];
            $employee_id = $value['id'];
            $employee_name = $value['name'];
            $age = getAge($value['date_of_birth']);
            $city = $value['city'];
            $myInfo = $value['my_info'];
        }
        $checkUserPhoto = $dbh->query('SELECT * FROM photo WHERE userid = "'.$can_user_id.'" AND status = 1')->fetchAll();
        if (is_array($checkUserPhoto) && count($checkUserPhoto) > 0) {
            $getGoals = $dbh->query('SELECT interest.name FROM interest_user INNER JOIN interest ON interest_user.interest_id = interest.id WHERE userid = "' . $can_user_id . '"')->fetchAll();
            if (is_array($getGoals) && count($getGoals) > 0) {
                $goalAll = [];
                foreach ($getGoals as $goal){
                    $goalAll[] = $goal['name'];
                }
                $goalAll = implode(', ', $goalAll);
            }

            $number = 0;
            foreach ($checkUserPhoto as $item){
                $number++;
                if(count($checkUserPhoto) == $number) {
                    $arrayPhoto[] = [
                        'type' => 'photo',
                        'media' => 'https://znaksite.cryptopushbot.ru/assets/photo/'.$can_user_id.'/'.$item['file'],
                        'caption' => $employee_name.', '.$age.', 📍'.$city.',  '.$myInfo.'

'.$goalAll
                    ];
                } else {
                    $arrayPhoto[] = [
                        'type' => 'photo',
                        'media' => 'https://znaksite.cryptopushbot.ru/assets/photo/'.$can_user_id.'/'.$item['file']
                    ];
                }

            }
            $postContent = [
                'chat_id' => $user_id,
                'media' => json_encode($arrayPhoto),
            ];

            $send_data = [
                'chat_id' => $user_id,
                'text'   => '🔍🔍🔍',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => '❤'],
                            ['text' => '❌'],
                            ['text' => '🏠'],
                        ],
                    ]
                ]
            ];

            $checkLike = $dbh->query('SELECT * FROM likes_user WHERE myid = "'.$user_id.'" AND like_userid = '.$can_user_id)->fetchAll();
            if (is_array($checkLike) && count($checkLike) > 0) {

            } else {
                $dbh->query('INSERT INTO likes_user (myid, like_userid, employee_id, action, date) VALUE ("' . $user_id . '", "'.$can_user_id.'", '.$employee_id.', 0, "' . date('Y-m-d H:i:s') . '")');
            }

            sendTelegram($send_data, 'sendMessage', $botAPI, $user_id);

            sendTelegram($postContent, 'sendMediaGroup', $botAPI, $user_id);

        }
    } else {
        $dbh->query('DELETE FROM action_users WHERE userid = '.$user_id);
        $send_data = [
            'chat_id' => $user_id,
            'text'   => 'End profi. В главное меню?',
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => 'В главное меню']
                    ],
                ]
            ]
        ];
        sendTelegram($send_data, 'sendMessage', $botAPI, $user_id);
    }
    $dbh = null;
}


function viewOneAnket($botAPI, $user_id, $parsId, $dbh){

    $getEmployee= $dbh->query('SELECT * FROM employee WHERE userid = '.$parsId)->fetchAll();
    if(is_array($getEmployee) && count($getEmployee) > 0) {
        $can_user_id = 0;
        $employee_name = '';
        $employee_id = 0;
        $age = '';
        $city = '';
        $myInfo = '';
        foreach ($getEmployee as $value){
            $can_user_id = $value['userid'];
            $employee_id = $value['id'];
            $employee_name = $value['name'];
            $age = getAge($value['date_of_birth']);
            $city = $value['city'];
            $myInfo = $value['my_info'];
        }
        $checkUserPhoto = $dbh->query('SELECT * FROM photo WHERE userid = "'.$can_user_id.'" AND status = 1')->fetchAll();
        if (is_array($checkUserPhoto) && count($checkUserPhoto) > 0) {
            $getGoals = $dbh->query('SELECT interest.name FROM interest_user INNER JOIN interest ON interest_user.interest_id = interest.id WHERE userid = "' . $can_user_id . '"')->fetchAll();
            if (is_array($getGoals) && count($getGoals) > 0) {
                $goalAll = [];
                foreach ($getGoals as $goal){
                    $goalAll[] = $goal['name'];
                }
                $goalAll = implode(', ', $goalAll);
            }

            $number = 0;
            foreach ($checkUserPhoto as $item){
                $number++;
                if(count($checkUserPhoto) == $number) {
                    $arrayPhoto[] = [
                        'type' => 'photo',
                        'media' => 'https://znaksite.cryptopushbot.ru/assets/photo/'.$can_user_id.'/'.$item['file'],
                        'caption' => $employee_name.', '.$age.', 📍'.$city.',  '.$myInfo.'

'.$goalAll
                    ];
                } else {
                    $arrayPhoto[] = [
                        'type' => 'photo',
                        'media' => 'https://znaksite.cryptopushbot.ru/assets/photo/'.$can_user_id.'/'.$item['file']
                    ];
                }

            }
            $postContent = [
                'chat_id' => $user_id,
                'media' => json_encode($arrayPhoto),
            ];

            $send_data = [
                'chat_id' => $user_id,
                'text'   => '❤❤❤',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => '👍'],
                            ['text' => '👎'],
                            ['text' => '🏠'],
                        ],
                    ]
                ]
            ];

            $checkUserPhoto = $dbh->query('SELECT * FROM likes_user WHERE myid = "'.$user_id.'" AND like_userid = '.$can_user_id)->fetchAll();
            if (is_array($checkUserPhoto) && count($checkUserPhoto) > 0) {

            } else {
                $dbh->query('INSERT INTO likes_user (myid, like_userid, employee_id, action, date) VALUE ("' . $user_id . '", "'.$can_user_id.'", '.$employee_id.', 0, "' . date('Y-m-d H:i:s') . '")');
            }

            sendTelegram($send_data, 'sendMessage', $botAPI, $user_id);

            sendTelegram($postContent, 'sendMediaGroup', $botAPI, $user_id);

        }
    }
    $dbh = null;
}

function myProfile($botAPI, $user_id, $dbh){
    $getEmployee= $dbh->query('SELECT * FROM employee WHERE userid = '.$user_id)->fetchAll();
    if(is_array($getEmployee) && count($getEmployee) > 0) {
        $can_user_id = 0;
        $employee_name = '';
        $employee_id = 0;
        $age = '';
        $city = '';
        $myInfo = '';
        foreach ($getEmployee as $value){
            $can_user_id = $value['userid'];
            $employee_id = $value['id'];
            $employee_name = $value['name'];
            $age = getAge($value['date_of_birth']);
            $city = $value['city'];
            $myInfo = $value['my_info'];
        }
        $checkUserPhoto = $dbh->query('SELECT * FROM photo WHERE userid = "'.$can_user_id.'" AND status = 1')->fetchAll();
        if (is_array($checkUserPhoto) && count($checkUserPhoto) > 0) {
            $getGoals = $dbh->query('SELECT interest.name FROM interest_user INNER JOIN interest ON interest_user.interest_id = interest.id WHERE userid = "' . $can_user_id . '"')->fetchAll();
            if (is_array($getGoals) && count($getGoals) > 0) {
                $goalAll = [];
                foreach ($getGoals as $goal){
                    $goalAll[] = $goal['name'];
                }
                $goalAll = implode(', ', $goalAll);
            }

            $number = 0;
            foreach ($checkUserPhoto as $item){
                $number++;
                if(count($checkUserPhoto) == $number) {
                    $arrayPhoto[] = [
                        'type' => 'photo',
                        'media' => 'https://znaksite.cryptopushbot.ru/assets/photo/'.$can_user_id.'/'.$item['file'],
                        'caption' => $employee_name.', '.$age.', 📍'.$city.',  '.$myInfo.'

'.$goalAll
                    ];
                } else {
                    $arrayPhoto[] = [
                        'type' => 'photo',
                        'media' => 'https://znaksite.cryptopushbot.ru/assets/photo/'.$can_user_id.'/'.$item['file']
                    ];
                }

            }
            $postContent = [
                'chat_id' => $user_id,
                'media' => json_encode($arrayPhoto),
            ];
            sendTelegram($postContent, 'sendMediaGroup', $botAPI, $user_id);

        }
    }
    $dbh = null;
}


function sendTelegram($data, $method = null, $botAPI, $user_id)
{

    if(empty($method)){
        $method = 'sendMessage';
    }
    $url = 'https://api.telegram.org/bot' . TOKEN . '/' . $method;


    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
//    if(sizeof($headers) > 0){
//        curl_setopt($curl, CURLOPT_HTTPHEADER, [$headers]);
//    } else {
//        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
//    }
    $result = curl_exec($curl);
    curl_close($curl);

    $getArray = json_decode($result, 1);

    if($getArray['ok'] == 0){
        $data = http_build_query([
            'text' => 'Возники ошибки! Обритетсь к админстратору',
            'chat_id' => $user_id
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");

        $data = http_build_query([
            'text' => 'У пользователя '.$user_id.' возникили ошибки. Текст ошибки - '.$getArray['description'],
            'chat_id' => 879771353
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
    }

    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}
