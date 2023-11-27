<?php

require_once('token.php');
require_once('bd.php');


$MOI_OPISANIE_OBMEN = 1; // Редактируем описание обменника
$SEND_SMS_ROOM = 2; // Создается диалог и пишем друг другу
$FORM_REQUISITES = 3; // Пишет свои реквизиты для рефералки
$CREATE_USER_OBMEN = 4; // Пользователь создает обмен (Совершить обмен)
$CAN_ACTION_OBMEN_EXCHANGER = 5; // Пользователь создает обмен (Совершить обмен)
$SEND_ADMIN_MAIL_LIST = 6; // Рассылка от администратора
$CONFIRM_SEND_SMS_ADMIN = 7; // Подтверждение Рассылка от администратора

# Принимаем запрос
$data = json_decode(file_get_contents('php://input'), TRUE);
file_put_contents('file.txt', '$data: '.print_r($data, 1)."\n", FILE_APPEND); // Посмотреть что пришло от сервера


//https://api.telegram.org/bot*Токен бота*/setwebhook?url=*ссылка на бота*


# Обрабатываем ручной ввод или нажатие на кнопку
$data = $data['callback_query'] ? $data['callback_query'] : $data['message'];

$referal = $data['text'];

# Важные константы
define('TOKEN', $token);

# Записываем сообщение пользователя
$message = $data['text'] ? $data['text'] : $data['data'];


$botToken = $token;
$botAPI = "https://api.telegram.org/bot" . $botToken;
$update = json_decode(file_get_contents('php://input'), TRUE);

$chat_id = $data['chat']['id'];
$first_name = $data['chat']['first_name'];
$username = $data['chat']['username'];

$newConnect =  new PDO('mysql:host='.$host.';charset=utf8;dbname='.$dbName, $bdUser, $bdPassword);

if (isset($update['callback_query'])) {

    if($update['callback_query']['data'] == 'yesConfirmSendSmsAllUser'){
        $dbh = $newConnect;

        $dbh->query('DELETE FROM action_users WHERE chat_id = '.$update['callback_query']['from']['id']);
//        $dbh->query('DELETE FROM number_message WHERE action_id = '.$SEND_ADMIN_MAIL_LIST.' AND manager_id = '.$update['callback_query']['from']['id']);

        $getMessage = $dbh->query('SELECT * FROM send_mail_list WHERE status = 0 AND user_id = '.$update['callback_query']['from']['id'])->fetchAll();
        $textSendAdmin = '';
        $getIdMailList = 0;
        if(is_array($getMessage) && count($getMessage) > 0) {
            foreach ($getMessage as $item){
                $textSendAdmin = $item['text'];
                $getIdMailList = $item['id'];
            }
            $dbh->query('UPDATE send_mail_list SET status = 1 WHERE id = '.$getIdMailList);
        }

        // ToDo WHERE role_id = 3
        $getUsers = $dbh->query('SELECT * FROM users WHERE role_id = 3')->fetchAll();
        if(is_array($getUsers) && count($getUsers) > 0) {
            foreach ($getUsers as $item){
                $data = http_build_query([
                    'text' => $textSendAdmin,
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
        }

        $getMessageId = $dbh->query('SELECT * FROM number_message WHERE action_id = '.$CONFIRM_SEND_SMS_ADMIN.' AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
        if(is_array($getMessageId) && count($getMessageId) > 0) {
            $message_id = 0;
            foreach ($getMessageId as $row){
                $message_id = $row['message_id'];
            }
            // Deleting message
            $data_del = http_build_query([
                'chat_id' => $update['callback_query']['from']['id'],
                'message_id' => $message_id,
            ]);
            file_get_contents($botAPI . "/deleteMessage?{$data_del}");
            foreach ($getMessageId as $row){
                $dbh->query('DELETE FROM number_message WHERE id = '.$row['id']);
            }
        }
        $getMessageId = $dbh->query('SELECT * FROM number_message WHERE action_id = '.$SEND_ADMIN_MAIL_LIST.' AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
        if(is_array($getMessageId) && count($getMessageId) > 0) {
            $message_id = 0;
            foreach ($getMessageId as $row){
                $message_id = $row['message_id'];
            }
            // Deleting message
            $data_del = http_build_query([
                'chat_id' => $update['callback_query']['from']['id'],
                'message_id' => $message_id,
            ]);
            file_get_contents($botAPI . "/deleteMessage?{$data_del}");
            foreach ($getMessageId as $row){
                $dbh->query('DELETE FROM number_message WHERE id = '.$row['id']);
            }
        }

        $dbh = null;
    }

    if($update['callback_query']['data'] == 'noConfirmSendSmsAllUser'){
        $dbh = $newConnect;
        $dbh->query('DELETE FROM action_users WHERE chat_id = '.$update['callback_query']['from']['id']);
        $getMessageId = $dbh->query('SELECT * FROM number_message WHERE action_id = '.$CONFIRM_SEND_SMS_ADMIN.' AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
        if(is_array($getMessageId) && count($getMessageId) > 0) {
            $message_id = 0;
            foreach ($getMessageId as $row){
                $message_id = $row['message_id'];
            }
            // Deleting message
            $data_del = http_build_query([
                'chat_id' => $update['callback_query']['from']['id'],
                'message_id' => $message_id,
            ]);
            file_get_contents($botAPI . "/deleteMessage?{$data_del}");
            foreach ($getMessageId as $row){
                $dbh->query('DELETE FROM number_message WHERE id = '.$row['id']);
            }
        }

        $getMessageCancel = $dbh->query('SELECT * FROM send_mail_list WHERE status = 0 AND user_id = '.$update['callback_query']['from']['id'])->fetchAll();
        $getIdMailList = 0;
        if(is_array($getMessageCancel) && count($getMessageCancel) > 0) {
            foreach ($getMessageCancel as $item){
                $getIdMailList = $item['id'];
            }
            $dbh->query('UPDATE send_mail_list SET status = 2 WHERE id = '.$getIdMailList);
        }

        $dbh = null;
    }

    if($update['callback_query']['data'] == 'closeUserApplication'){
        $dbh = $newConnect;
//        $query = $dbh->query('SELECT * from users_obmen WHERE chat_id = '.$update['callback_query']['from']['id'].' AND status = 1')->fetchAll();
        $query = $dbh->query('SELECT users_obmen.*, users.first_name FROM users_obmen INNER JOIN users ON users_obmen.chat_id = users.chat_id WHERE users_obmen.chat_id = '.$update['callback_query']['from']['id'].' AND users_obmen.status = 1')->fetchAll();
        if(is_array($query) && count($query) > 0) {
            foreach ($query as $row){
                if(!empty($row['manager_id'])){
                    $dbh->query('DELETE FROM action_users WHERE chat_id = '.$row['manager_id']);
                    $dbh->query('DELETE FROM action_users WHERE chat_id = '.$row['chat_id']);
                    $dbh->query('UPDATE users_obmen SET status = 0 WHERE status = 1 AND chat_id = '.$row['chat_id']);
                    $dbh->query('UPDATE chat_room SET status = 0 WHERE status = 1 AND user_id = ' .$row['chat_id'].' AND manager_id = '.$row['manager_id']);
                    $dbh->query('UPDATE users SET free_action = 0 WHERE chat_id = '.$row['manager_id']);
                } else {
                    $dbh->query('DELETE FROM action_users WHERE chat_id = '.$row['chat_id']);
                    $dbh->query('UPDATE users_obmen SET status = 0 WHERE status = 1 AND chat_id = '.$row['chat_id']);
                    $dbh->query('UPDATE chat_room SET status = 0 WHERE status = 1 AND user_id = ' .$row['chat_id']);
                }
            }
            $data = http_build_query([
                'text' => 'Заявка успешна отменена!',
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            $inline_button1 = array("text"=>"Совершить обмен 🔄");
            $inline_button2 = array("text"=>"Мои заявки ⭐️");
            $inline_button3 = array("text"=>"О проекте 📄");
            $inline_button4 = array("text"=>"Отзывы ✍️");
            $inline_button5 = array("text"=>"Пригласить друга 💰");
            $inline_keyboard = [[$inline_button1], [$inline_button3, $inline_button4], [$inline_button5, $inline_button2]];
            $keyboard=array("keyboard"=>$inline_keyboard);
            $replyMarkup = json_encode($keyboard);
            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

            $getManager = $dbh->query('SELECT * FROM users WHERE role_id = 2')->fetchAll();
            foreach ($getManager as $item){
                $data = http_build_query([
                    'text' => '❌ ЗАЯВКА ОТМЕНЕНА КЛИЕНТОМ!

Заявка от пользователя: '.$row["first_name"].'
Текст заявки: ('.$row["text"].')',
                    'chat_id' =>  $item['chat_id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }

        } else {
            $data = http_build_query([
                'text' => 'Нет активных заявок',
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }
        $dbh = null;
    }

    if($update['callback_query']['data'] == 'noCloseUserApplication'){
        $data = http_build_query([
            'text' => 'Заявка не отменена!',
            'chat_id' =>  $update['callback_query']['from']['id']
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
    }

    if($update['callback_query']['data'] == 'edit_info_obmen'){
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from action_users WHERE action_id = 1 AND chat_id = '.$update['callback_query']['from']['id'])->fetchAll();
        if(is_array($query) && count($query) > 0) {
            $data = http_build_query([
                'text' => 'Редактируйте!',
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        } else {
            $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$update['callback_query']['from']['id'].', '.$MOI_OPISANIE_OBMEN.')');
            $data = http_build_query([
                'text' => 'Пришлите новое описание вашего обменника!',
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }
        $dbh = null;
    }

    // closeErrorNoObmen, closeSuccessNoObmen - два бесполезных обработчика

    if($update['callback_query']['data'] == 'closeErrorNoObmen'){
        $data = http_build_query([
            'text' => 'Хорошо, работайте дальше',
            'chat_id' => $update['callback_query']['from']['id']
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
    }

    if($update['callback_query']['data'] == 'closeSuccessNoObmen'){
        $data = http_build_query([
            'text' => 'Хорошо, работайте дальше',
            'chat_id' => $update['callback_query']['from']['id']
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
    }

    if($update['callback_query']['data'] == 'editRequisites'){
        $dbh =$newConnect;
        $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$update['callback_query']['from']['id'].', '.$FORM_REQUISITES.')');
        $data = http_build_query([
            'text' => 'Укажите ваши реквизиты в TRC20',
            'chat_id' => $update['callback_query']['from']['id']
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
        $dbh = null;
    }

    if($update['callback_query']['data'] == 'formRequisites'){
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from users WHERE chat_id = '.$update['callback_query']['from']['id'])->fetchAll();
        if(is_array($query) && count($query) > 0) {
            foreach ($query as $row){
                $data = http_build_query([
                    'text' => $row['requisites'],
                    'chat_id' => $update['callback_query']['from']['id']
                ]);
                $inline_button1 = array("text"=>"Редактировать реквизиты","callback_data"=>"editRequisites");
                $inline_keyboard = [[$inline_button1]];
                $keyboard=array("inline_keyboard"=>$inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
            }
        }
//        $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$update['callback_query']['from']['id'].', '.$FORM_REQUISITES.')');
        $dbh = null;
    }

    $pars = explode('_', $update['callback_query']['data']);
    if(isset($pars[0]) && isset($pars[1])) {
        if($pars[0] == 'closeErrorObmen') {
            $dbh = $newConnect;
            if($pars[1] == 3){
                $ch = 'chat_id';
            } else if($pars[1] == 2){
                $ch = 'manager_id';
            }
            $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND '.$ch.' = ' . $update['callback_query']['from']['id'])->fetchAll();
            if (is_array($query) && count($query) > 0) {
                $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND '.$ch.' = ' . $update['callback_query']['from']['id'])->fetchAll();
                if (is_array($query) && count($query) > 0) {
                    foreach ($query as $row) {
                        $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = ' . $row['manager_id']);
                        $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = ' . $row['chat_id']);
                        $dbh->query('UPDATE users SET free_action = 0 WHERE chat_id = '.$row['manager_id']);
                        if($pars[1] == 3){
                            // Для обычного пользователя
                            $dbh->query('UPDATE chat_room SET status = 0 WHERE status = 1 AND user_id = ' . $update['callback_query']['from']['id']);
                            $data = http_build_query([
                                'text' => 'Заявка №'.$row['id'].' закрыта, теперь у вас нет активных заявок',
                                'chat_id' => $row['chat_id']
                            ]);
                            $inline_button1 = array("text"=>"Совершить обмен 🔄");
                            $inline_button2 = array("text"=>"Мои заявки ⭐️");
                            $inline_button3 = array("text"=>"О проекте 📄");
                            $inline_button4 = array("text"=>"Отзывы ✍️");
                            $inline_button5 = array("text"=>"Пригласить друга 💰");
                            $inline_keyboard = [[$inline_button1], [$inline_button3, $inline_button4], [$inline_button5, $inline_button2]];
                            $keyboard=array("keyboard"=>$inline_keyboard);
                            $replyMarkup = json_encode($keyboard);
                            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
                            // Для обменника (менеджера)
                            $data = http_build_query([
                                'text' => "Пользователь закрыл текущую заявку, теперь у вас нет активных заявок",
                                'chat_id' => $row['manager_id']
                            ]);
                            file_get_contents($botAPI . "/sendMessage?{$data}");
                        }
                        if($pars[1] == 2){
                            $dbh->query('UPDATE chat_room SET status = 0 WHERE status = 1 AND manager_id = ' . $update['callback_query']['from']['id']);
                            $data = http_build_query([
                                'text' => "Обменник закрыл заявку, теперь у вас нет активных заявок",
                                'chat_id' => $row['chat_id']
                            ]);
                            $inline_button1 = array("text"=>"Совершить обмен 🔄");
                            $inline_button2 = array("text"=>"Мои заявки ⭐️");
                            $inline_button3 = array("text"=>"О проекте 📄");
                            $inline_button4 = array("text"=>"Отзывы ✍️");
                            $inline_button5 = array("text"=>"Пригласить друга 💰");
                            $inline_keyboard = [[$inline_button1], [$inline_button3, $inline_button4], [$inline_button5, $inline_button2]];
                            $keyboard=array("keyboard"=>$inline_keyboard);
                            $replyMarkup = json_encode($keyboard);
                            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
                            // Для обменника (менеджера)
                            $data = http_build_query([
                                'text' => "Вы закрыли текущую заявку, теперь у вас нет активных заявок",
                                'chat_id' => $row['manager_id']
                            ]);
                            file_get_contents($botAPI . "/sendMessage?{$data}");
                        }

                    }

                    $dbh->query('UPDATE users_obmen SET status = 0 WHERE status = 1 AND '.$ch.' = ' . $update['callback_query']['from']['id']);
//                    $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = ' . $update['callback_query']['from']['id']);
                    $dbh->query('UPDATE chat_room SET status = 0 WHERE status = 1 AND user_id = ' . $update['callback_query']['from']['id']);
                } else {
                    $data = http_build_query([
                        'text' => "У Вас нет активных заявок",
                        'chat_id' => $update['callback_query']['from']['id']
                    ]);
                    file_get_contents($botAPI . "/sendMessage?{$data}");
                }
            } else {
                $data = http_build_query([
                    'text' => "У Вас нет активных заявок",
                    'chat_id' => $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
            $dbh = null;
        }
    }

    $pars = explode('_', $update['callback_query']['data']);
    if(isset($pars[0]) && isset($pars[1])) {
        if ($pars[0] == 'closeSuccessObmen') {
            if($pars[1] == 3){
                $ch = 'chat_id';
                $textSend = 'Пользователь';
            }
            if($pars[1] == 2){
                $ch = 'manager_id';
                $textSend = 'Обменник';
            }
            $dbh =$newConnect;
            $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND '.$ch.' = '.$update['callback_query']['from']['id'])->fetchAll();
            if(is_array($query) && count($query) > 0) {
                $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND '.$ch.' = '.$update['callback_query']['from']['id'])->fetchAll();
                if(is_array($query) && count($query) > 0) {
                    foreach ($query as $row) {
                        $data = http_build_query([
                            'text' => 'Заявка №'.$row['id'].' успешно закрыта. Спасибо, что выбрали нашего бота!',
                            'chat_id' => $row['chat_id']
                        ]);
                        $inline_button1 = array("text"=>"Совершить обмен 🔄");
                        $inline_button2 = array("text"=>"Мои заявки ⭐️");
                        $inline_button3 = array("text"=>"О проекте 📄");
                        $inline_button4 = array("text"=>"Отзывы ✍️");
                        $inline_button5 = array("text"=>"Пригласить друга 💰");
                        $inline_keyboard = [[$inline_button1], [$inline_button3, $inline_button4], [$inline_button5, $inline_button2]];
                        $keyboard=array("keyboard"=>$inline_keyboard);
                        $replyMarkup = json_encode($keyboard);
                        file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);


                        $data = http_build_query([
                            'text' => 'Заявка №'.$row['id'].' успешно закрыта!',
                            'chat_id' => $row['manager_id']
                        ]);
                        file_get_contents($botAPI . "/sendMessage?{$data}");

                        $getPartner = $dbh->query('SELECT * from users WHERE referal_id IS NOT NULL AND  chat_id = '.$row['chat_id'])->fetchAll();
                        if(is_array($getPartner) && count($getPartner) > 0) {
                            $queryAdmin = $dbh->query('SELECT * from users WHERE role_id = 1')->fetchAll();
                            if(is_array($queryAdmin) && count($queryAdmin) > 0) {
                                foreach ($queryAdmin as $admin) {
                                    foreach ($getPartner as $partner) {
                                        $data = http_build_query([
                                            'text' => 'Сделка №' . $row["id"] . ' прошла успешно
Id клиента: ' . $row['chat_id'] . '

Id обменника: ' . $row['manager_id'] . '

Id партнера: ' .$partner['referal_id'].'
Кошелёк партнера: '.$partner['requisites'].'',
                                            'chat_id' => $admin['chat_id']
                                        ]);
                                        file_get_contents($botAPI . "/sendMessage?{$data}");
                                    }
                                }
                            }
                        } else {
                            $queryAdmin = $dbh->query('SELECT * from users WHERE role_id = 1')->fetchAll();
                            if(is_array($queryAdmin) && count($queryAdmin) > 0) {
                                foreach ($queryAdmin as $admin) {
                                    $data = http_build_query([
                                        'text' => 'Сделка №'.$row["id"].' прошла успешно
Id клиента: '.$row['chat_id'].'

Id обменника: '.$row['manager_id'].'',
                                        'chat_id' => $admin['chat_id']
                                    ]);
                                    file_get_contents($botAPI . "/sendMessage?{$data}");
                                }
                            }
                        }
                    }
                }

                foreach ($query as $row) {
                    $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = '.$row['chat_id']);
                    $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = '.$row['manager_id']);
                    $dbh->query('UPDATE users SET free_action = 0 WHERE chat_id = '.$row['manager_id']);
                }
                $dbh->query('UPDATE users_obmen SET status = 2 WHERE status = 1 AND '.$ch.' = '.$update['callback_query']['from']['id']);
                $dbh->query('UPDATE chat_room SET status = 0 WHERE status = 1 AND '.$ch.' = '.$update['callback_query']['from']['id']);
            } else {
                $data = http_build_query([
                    'text' => "У Вас нет активных заявок",
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
            $dbh = null;
        }
    }

    if($update['callback_query']['data'] == 'yesob'){
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND chat_id = '.$update['callback_query']['from']['id'])->fetchAll();
        if(is_array($query) && count($query) > 0) {
            // Reply with callback_query data
            $data = http_build_query([
                'text' => 'Selected language: '.$update['callback_query']['data'],
                'chat_id' => $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        } else {
            $data = http_build_query([
                'text' => "У Вас нет активных заявок",
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }
        $dbh = null;
    }

    $pars = explode('_', $update['callback_query']['data']);
    if(isset($pars[0]) && isset($pars[1])) {
        if ($pars[0] == 'endObmen') {
            if($pars[1] == 3){
                $ch = 'chat_id';
            }
            if($pars[1] == 2){
                $ch = 'manager_id';
            }
            $dbh =$newConnect;
            $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND '.$ch.' = '.$update['callback_query']['from']['id'])->fetchAll();
            if(is_array($query) && count($query) > 0) {
                // Reply with callback_query data
                $data = http_build_query([
                    'text' => 'Вы точно хотите подтвердить обмен?',
                    'chat_id' => $update['callback_query']['from']['id']
                ]);


                $inline_button1 = array("text"=>"Да","callback_data"=>"closeSuccessObmen_".$pars[1]);
                $inline_button2 = array("text"=>"Нет","callback_data"=>"closeSuccessNoObmen");
                $inline_keyboard = [[$inline_button1, $inline_button2]];
                $keyboard=array("inline_keyboard"=>$inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
            } else {
                $data = http_build_query([
                    'text' => "У Вас нет активных заявок",
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
            $dbh->query('UPDATE users SET free_action = 1 WHERE chat_id = '.$pars[1]);
            $dbh = null;
        }
    }

    if($update['callback_query']['data'] == 'endNoObmen'){
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND chat_id = '.$update['callback_query']['from']['id'])->fetchAll();
        if(is_array($query) && count($query) > 0) {
            $data = http_build_query([
                'text' => 'Вы точно хотите отменить обмен?',
                'chat_id' => $update['callback_query']['from']['id']
            ]);
            foreach ($query as $row) {
                if($update['callback_query']['from']['id'] == $row['chat_id']){
                    $role = 3;
                } else if($update['callback_query']['from']['id'] == $row['manager_id']){
                    $role = 2;
                } else {
                    $role = 3;
                    $data = http_build_query([
                        'text' => "Возникли ошибки. Обратитесь к администратору!",
                        'chat_id' =>  $update['callback_query']['from']['id']
                    ]);
                    file_get_contents($botAPI . "/sendMessage?{$data}");
                }
            }
            $inline_button1 = array("text"=>"Да","callback_data"=>"closeErrorObmen_".$role);
            $inline_button2 = array("text"=>"Нет","callback_data"=>"closeErrorNoObmen");
            $inline_keyboard = [[$inline_button1, $inline_button2]];
            $keyboard=array("inline_keyboard"=>$inline_keyboard);
            $replyMarkup = json_encode($keyboard);
            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
        } else {
            $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
            if(is_array($query) && count($query) > 0) {
                $data = http_build_query([
                    'text' => 'Вы точно хотите отменить обмен?',
                    'chat_id' => $update['callback_query']['from']['id']
                ]);
                foreach ($query as $row) {
                    if($update['callback_query']['from']['id'] == $row['chat_id']){
                        $role = 3;
                    } else if($update['callback_query']['from']['id'] == $row['manager_id']){
                        $role = 2;
                    } else {
                        $role = 3;
                        $data = http_build_query([
                            'text' => "Возникли ошибки. Обратитесь к администратору!",
                            'chat_id' =>  $update['callback_query']['from']['id']
                        ]);
                        file_get_contents($botAPI . "/sendMessage?{$data}");
                    }
                }
                $inline_button1 = array("text"=>"Да","callback_data"=>"closeErrorObmen_".$role);
                $inline_button2 = array("text"=>"Нет","callback_data"=>"closeErrorNoObmen");
                $inline_keyboard = [[$inline_button1, $inline_button2]];
                $keyboard=array("inline_keyboard"=>$inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
            } else {
                $data = http_build_query([
                    'text' => "У Вас нет активных заявок",
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
        }
        $dbh = null;
    }

    $pars = explode('_', $update['callback_query']['data']);
    if(isset($pars[0]) && isset($pars[1])){
        // $pars[1] - это id пользователя
        $date = date("Y-m-d H:i:s");
        if($pars[0] == 'sendObmenSumm'){
            $dbh =$newConnect;
            $query = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND chat_id = '.$pars[1])->fetchAll();
            if(is_array($query) && count($query) > 0) {
                $dbh->query('DELETE FROM action_users WHERE chat_id = '.$update['callback_query']['from']['id']);
                $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$update['callback_query']['from']['id'].', '.$CAN_ACTION_OBMEN_EXCHANGER.')');
                $data = http_build_query([
                    'text' => '✍️ Напишите предложение клиенту и отправьте сообщение:',
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");

                $users_obmen_id = 0;
                foreach ($query as $row){
                    $users_obmen_id = $row['id'];
                }

                $dbh->query('INSERT INTO send_message_exchanger (users_obmen_id, user_id, manager_id, text, status, date)  VALUE ('.$users_obmen_id.', '.$pars[1].', '.$update['callback_query']['from']['id'].', "", 0, "'.$date.'")');
            } else {
                $data = http_build_query([
                    'text' => "У Вас нет активных заявок",
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }

            $dbh = null;
        }
    }
    if(isset($pars[0]) && isset($pars[1])){
        if($pars[0] == 'addObmen'){
            $dbh =$newConnect;

            $query = $dbh->query('SELECT * from chat_room WHERE status = 1 AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
            if(is_array($query) && count($query) > 0) {
                $data = http_build_query([
                    'text' => "Вы уже выбрали обменник! Ожидайте сообщения или напишите сообщение в чат!",
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            } else {
                $checkChatRoom = $dbh->query('SELECT * FROM users WHERE free_action = 1 AND chat_id = '.$pars[1])->fetchAll();
                if(is_array($checkChatRoom) && count($checkChatRoom) > 0) {
                    $data = http_build_query([
                        'text' => "Этот обменник уже занят! Выберите другой!",
                        'chat_id' =>  $update['callback_query']['from']['id']
                    ]);
                    file_get_contents($botAPI . "/sendMessage?{$data}");
                } else {
                    $dbh->query('UPDATE users SET free_action = 1 WHERE chat_id = '.$pars[1]);
                    $checkUser = $dbh->query('SELECT * from users_obmen WHERE status = 1 AND chat_id = '.$update['callback_query']['from']['id'])->fetchAll();
                    if(is_array($checkUser) && count($checkUser) > 0) {
                        $dbh->query('UPDATE users_obmen SET manager_id = '.$pars[1].' WHERE status = 1 AND chat_id = '.$update['callback_query']['from']['id']);
//                        $dbh->query('UPDATE users_obmen SET free_action = 1 WHERE chat_id = '.$pars[1]); // ToDo глянуть на этот запрос

                        $query = $dbh->query('SELECT * from chat_room WHERE status = 1 AND user_id = '.$update['callback_query']['from']['id'].' AND manager_id = '.$pars[1])->fetchAll();
                        if(is_array($query) && count($query) > 0) {
                            $data = http_build_query([
                                'text' => "Вы уже выбрали обменник! Ожидайте сообщения или напишите сообщение в чат!",
                                'chat_id' =>  $update['callback_query']['from']['id']
                            ]);
                            file_get_contents($botAPI . "/sendMessage?{$data}");

                        } else {
                            $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = '.$update['callback_query']['from']['id']);
                            $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = '.$pars[1]);
                            $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$update['callback_query']['from']['id'].', '.$SEND_SMS_ROOM.')');
                            $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$pars[1].', '.$SEND_SMS_ROOM.')');
                            $dbh->query('INSERT INTO chat_room (user_id, manager_id, date, status) VALUE ('.$update['callback_query']['from']['id'].', '.$pars[1].', NOW(), 1)');
                            $last_id = $dbh->lastInsertId();
                            $mes = 'Вам создана комната для общения с обменником.'.PHP_EOL.'Хорошего обмена! 👍';
                            $dbh->query('INSERT INTO chat_room_message (chat_room_id, user_id, message, date) VALUE ('.$last_id.', '.$update['callback_query']['from']['id'].', "'.$mes.'", NOW())');
                            $dbh->query('INSERT INTO chat_room_message (chat_room_id, user_id, message, date) VALUE ('.$last_id.', '.$pars[1].', "'.$mes.'", NOW())');
                            $data = http_build_query([
                                'text' => $mes,
                                'chat_id' =>  $update['callback_query']['from']['id']
                            ]);
                            file_get_contents($botAPI . "/sendMessage?{$data}");

                            $getUser = $dbh->query('SELECT users.chat_id as chat_id_user, users.first_name as first_name_user, users_obmen.id as id
FROM users_obmen INNER JOIN users
ON users_obmen.chat_id = users.chat_id
WHERE users_obmen.manager_id = '.$pars[1].' AND users_obmen.status = 1;')->fetchAll();
                            foreach ($getUser as $item){
                                $data = http_build_query([
                                    'text' => "Вас для обмена выбрал пользователь!
                                    
Заявка №".$item['id']." открыта

Id клиента: ".$item['chat_id_user']."
Имя клиента: ".$item['first_name_user']."

Создана комната для общения с клиентом. Напишите ему и договоритесь о сделке!",
                                    'chat_id' =>  $pars[1]
                                ]);
                                file_get_contents($botAPI . "/sendMessage?{$data}");
                            }


                        }
                    } else {
                        $data = http_build_query([
                            'text' => "У Вас нет активных заявок",
                            'chat_id' =>  $update['callback_query']['from']['id']
                        ]);
                        file_get_contents($botAPI . "/sendMessage?{$data}");
                    }
                }
            }
            $dbh = null;
        }
    }

    // roleAdmin_
    if(isset($pars[0]) && isset($pars[1])) {
        if ($pars[0] == 'roleAdmin') {
            $dbh = $newConnect;
            $dbh->query('UPDATE users SET role_id = 1 WHERE chat_id = '.$pars[1]);
            $data = http_build_query([
                'text' => "Пользователь - ".$pars[1]." назначен админом",
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");

            $data = http_build_query([
                'text' => "Вы теперь администратор, обновите меню - /start",
                'chat_id' =>  $pars[1]
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
            $dbh = null;
        }
        if ($pars[0] == 'deleteAdmin') {
            $dbh = $newConnect;
            $dbh->query('UPDATE users SET role_id = 3 WHERE chat_id = '.$pars[1]);
            $data = http_build_query([
                'text' => "Пользователь - ".$pars[1]." назначен обычным пользователем",
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");

            $data = http_build_query([
                'text' => "Вы теперь не являетесь администратором, обновите меню - /start",
                'chat_id' => $pars[1]
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
            $dbh = null;
        }
        if ($pars[0] == 'roleObmen') {
            $dbh = $newConnect;
            $dbh->query('UPDATE users SET role_id = 2 WHERE chat_id = '.$pars[1]);
            $data = http_build_query([
                'text' => "Пользователь - ".$pars[1]." назначен обменником",
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");

            $data = http_build_query([
                'text' => "Вы теперь являетесь обменником, обновите меню - /start Далее, чтобы получать заявки, напишите описание обменника",
                'chat_id' =>  $pars[1]
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
            $dbh = null;
        }
        if ($pars[0] == 'deleteObmen') {
            $dbh = $newConnect;
            $dbh->query('UPDATE users SET role_id = 3 WHERE chat_id = '.$pars[1]);
            $data = http_build_query([
                'text' => "Пользователь - ".$pars[1]." назначен обычным пользователем",
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");

            $data = http_build_query([
                'text' => "Вы теперь обычный пользователь - /start",
                'chat_id' =>  $pars[1]
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
            $dbh = null;
        }
        if ($pars[0] == 'yesSendMessageExchanger') {
            $dbh = $newConnect;
            // $pars[1] - это user_id
            // $update['callback_query']['message']['message_id'] // получаем id сообщения
            $dbh->query('DELETE FROM action_users WHERE chat_id = '.$update['callback_query']['from']['id']);

            $checkApplication = $dbh->query('SELECT * FROM send_message_exchanger as msg INNER JOIN users_obmen ON msg.users_obmen_id = users_obmen.id WHERE users_obmen.status = 1 AND msg.manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
            if(is_array($checkApplication) && count($checkApplication) > 0) {
                $actionMessageSend = $dbh->query('SELECT * FROM send_message_exchanger as msg INNER JOIN obmen_info ON msg.manager_id = obmen_info.chat_id WHERE status = 0 AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
                if(is_array($actionMessageSend) && count($actionMessageSend) > 0) {
                    $dbh->query('UPDATE send_message_exchanger SET status = 1 WHERE manager_id = '.$update['callback_query']['from']['id']);
                    $sendMessage = '';
                    $user_id = 0;
                    foreach ($actionMessageSend as $row){
                        $sendMessage .= 'Сообщение от обменника: '.$row['text'].PHP_EOL;
                        $sendMessage .= 'Описание обменника: '.$row['info'];
                        $user_id = $row['user_id'];
                    }

                    $data = http_build_query([
                        'text' => $sendMessage,
                        'chat_id' =>  $user_id
                    ]);
                    $inline_button1 = array("text"=>"Выбрать обменник","callback_data"=>"addObmen_".$update['callback_query']['from']['id']);
                    $inline_keyboard = [[$inline_button1]];
                    $keyboard=array("inline_keyboard"=>$inline_keyboard);
                    $replyMarkup = json_encode($keyboard);
                    file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

                    $data = http_build_query([
                        'text' => "Ответ успешно отправлен клиенту!",
                        'chat_id' =>  $update['callback_query']['from']['id']
                    ]);
                    file_get_contents($botAPI . "/sendMessage?{$data}");


                    $getMessageId = $dbh->query('SELECT * FROM number_message WHERE action_id = 5 AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
                    if(is_array($getMessageId) && count($getMessageId) > 0) {
                        $message_id = 0;
                        foreach ($getMessageId as $row){
                            $message_id = $row['message_id'];
                        }
                        // Deleting message
                        $data_del = http_build_query([
                            'chat_id' => $update['callback_query']['from']['id'],
                            'message_id' => $message_id,
                        ]);
                        file_get_contents($botAPI . "/deleteMessage?{$data_del}");
                        foreach ($getMessageId as $row){
                            $dbh->query('DELETE FROM number_message WHERE id = '.$row['id']);
                        }
                    }

                } else {
                    $data = http_build_query([
                        'text' => "Нет активного сообщения чтобы отправить пользователю.",
                        'chat_id' =>  $update['callback_query']['from']['id']
                    ]);
                    file_get_contents($botAPI . "/sendMessage?{$data}");
                }
            } else {
                // Нет активных заявок
                $data = http_build_query([
                    'text' => 'Нет активных заявок',
                    'chat_id' =>  $update['callback_query']['from']['id']
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
            $dbh = null;
        }

        if ($pars[0] == 'noSendMessageExchanger') {
            $data = http_build_query([
                'text' => "✍️ Напишите новое предложение клиенту и отправьте сообщение:",
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }


        /*
         *
         * action = 5 (Отказаться от заявки)
         * Когда обменник пишет сообщение для пользовотеля и отказывается от заявки
         *
         * */
        if ($pars[0] == 'closeSendMessageExchanger') {
            $dbh = $newConnect;
            // $pars[1] - это user_id
            $dbh->query('DELETE FROM action_users WHERE chat_id = '.$update['callback_query']['from']['id']);
            $data = http_build_query([
                'text' => "Вы вышли из редактора \"Отправки сообщения клиенту\" ",
                'chat_id' =>  $update['callback_query']['from']['id']
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");

            $getMessageId = $dbh->query('SELECT * FROM number_message WHERE action_id = 5 AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
            if(is_array($getMessageId) && count($getMessageId) > 0) {
                $message_id = 0;
                foreach ($getMessageId as $row){
                    $message_id = $row['message_id'];
                }
                // Deleting message
                $data_del = http_build_query([
                    'chat_id' => $update['callback_query']['from']['id'],
                    'message_id' => $message_id,
                ]);
                file_get_contents($botAPI . "/deleteMessage?{$data_del}");
                foreach ($getMessageId as $row){
                    $dbh->query('DELETE FROM number_message WHERE id = '.$row['id']);
                }
            }
            $dbh = null;
        }

        if ($pars[0] == 'cancelMailList') {
            // $pars[1] - Это id админа
            $dbh = $newConnect;
            $getMessageId = $dbh->query('SELECT * FROM number_message WHERE action_id = '.$SEND_ADMIN_MAIL_LIST.' AND manager_id = '.$update['callback_query']['from']['id'])->fetchAll();
            if(is_array($getMessageId) && count($getMessageId) > 0) {
                $message_id = 0;
                foreach ($getMessageId as $row){
                    $message_id = $row['message_id'];
                }
                // Deleting message
                $data_del = http_build_query([
                    'chat_id' => $update['callback_query']['from']['id'],
                    'message_id' => $message_id,
                ]);
                file_get_contents($botAPI . "/deleteMessage?{$data_del}");
                foreach ($getMessageId as $row){
                    $dbh->query('DELETE FROM number_message WHERE id = '.$row['id']);
                    $dbh->query('DELETE FROM action_users WHERE action_id = '.$row['action_id']);
                }
            } else {
                $data = http_build_query([
                    'text' => 'Возникли ошибки при отмене рассылки. Обратитесь к администратору',
                    'chat_id' =>  $chat_id
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
            $dbh = null;
        }
        $dbh = null;
    }
}

# Обрабатываем сообщение
switch ($message)
{
    case 'Совершить обмен 🔄':
        $dbh =$newConnect;
        $dbh->query('DELETE FROM action_users WHERE chat_id = ' . $chat_id);
        $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$chat_id.', '.$CREATE_USER_OBMEN.')');
        $queryCheckManager = $dbh->query('SELECT * FROM users WHERE role_id = 2 AND free_action = 0')->fetchAll();
        if(is_array($queryCheckManager) && count($queryCheckManager) > 0) {
            $data = http_build_query([
                'text' => "Напишите в текстовом формате: в какой валюте Вы хотите отдать и в какой валюте получить средства. Если наличными, то в каком городе?

Например: Хочу отдать наличные рублями в Москве — 5 млн. и получить дирхамы в Турции (г. Анкара) 
",
                'chat_id' =>  $chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
            $date = date("Y-m-d H:i:s");

            $dbh =$newConnect;
            $dbh->query('UPDATE users_obmen SET status = 0 WHERE status = 1 AND chat_id = '.$chat_id);
            $dbh->query('INSERT INTO users_obmen (chat_id, text, manager_id, status, date)  VALUE ('.$chat_id.', null, 0, 1, "'.$date.'")');
            $dbh = null;
        } else {
            $data = http_build_query([
                'text' => "Все обменники заняты. Попробуйте позже",
                'chat_id' =>  $chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }
        $dbh = null;
        break;


    case '/start':
        $button = checkUser($chat_id, $first_name, $username);
        $method = 'sendMessage';
        $send_data = $button;
        break;

    case 'Рассылка 🔉':
        // $SEND_ADMIN_MAIL_LIST

        $data = http_build_query([
            'text' => '📰 Введите сообщение для рассылки:',
            'chat_id' =>  $chat_id
        ]);
        $inline_button1 = array("text"=>"Отмена","callback_data"=>"cancelMailList_".$chat_id);
        $inline_keyboard = [[$inline_button1]];
        $keyboard=array("inline_keyboard"=>$inline_keyboard);
        $replyMarkup = json_encode($keyboard);
        $messageServer = file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

        $getJson = json_decode($messageServer);
        $message_id = $getJson->result->message_id;

        $dbh =$newConnect;
        $dbh->query('INSERT INTO action_users(chat_id, action_id) VALUE('.$chat_id.', '.$SEND_ADMIN_MAIL_LIST.')')->fetchAll();
        $dbh->query('INSERT INTO number_message(manager_id, action_id, message_id)  VALUE ('.$chat_id.', '.$SEND_ADMIN_MAIL_LIST.', '.$message_id.')')->fetchAll();

        break;

    case 'Пользователи':
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from users')->fetchAll();
        if(is_array($query) && count($query) > 0) {
            foreach ($query as $row) {
                $date_reg = date("d.m.Y H:i", strtotime($row['date']));

                $data = http_build_query([
                    'text' => 'Пользователь: '.$row['chat_id'].' '.PHP_EOL.' Дата регистрации: '.$date_reg,
                    'chat_id' =>  $chat_id
                ]);
                if($row['role_id'] == 3){
                    $inline_button1 = array("text"=>"Сделать администратором","callback_data"=>"roleAdmin_".$row['chat_id']);
                    $inline_button2 = array("text"=>"Сделать обмеником","callback_data"=>"roleObmen_".$row['chat_id']);
                    $inline_keyboard = [[$inline_button1, $inline_button2]];
                }
                if($row['role_id'] == 1){
                    $inline_button1 = array("text"=>"Убрать админку","callback_data"=>"deleteAdmin_".$row['chat_id']);
                    $inline_keyboard = [[$inline_button1]];
                }
                if($row['role_id'] == 2){
                    $inline_button1 = array("text"=>"Убрать обменник","callback_data"=>"deleteObmen_".$row['chat_id']);
                    $inline_keyboard = [[$inline_button1]];
                }

                $keyboard=array("inline_keyboard"=>$inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

//                $data = http_build_query([
//                    'text' => 'Пользователь: '.$row['chat_id'].' '.PHP_EOL.' Дата регистрации: '.$date_reg,
//                    'chat_id' =>  $chat_id
//                ]);
//                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
        }
        break;

    case 'Мои заявки ⭐️':
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from users_obmen WHERE chat_id = '.$chat_id)->fetchAll();
        if(is_array($query) && count($query) > 0) {
            $send_data = [
                'text'   => 'Все ваши заявки',
            ];
            $status = '';
            foreach ($query as $row) {
                if($row['status'] == 0){
                    $status = 'Закрыта';
                } else if($row['status'] == 1){
                    $status = 'Действующая';
                } else {
                    $status = 'Успешно выполнена (закрыта)';
                }
                $data = http_build_query([
                    'text' => 'Заявка №'.$row['id'].''.PHP_EOL.'Название и описание: '.$row['text'].' '.PHP_EOL.'Статус: '.$status,
                    'chat_id' =>  $chat_id
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
        } else {
            $data = http_build_query([
                'text' => 'У вас нет заявок',
                'chat_id' =>  $chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }

        $dbh = null;

        break;

    case 'Мои заявки 🌟':
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from users_obmen WHERE manager_id = '.$chat_id)->fetchAll();
        if(is_array($query) && count($query) > 0) {
            $send_data = [
                'text'   => 'Все ваши заявки',
            ];
            $status = '';
            foreach ($query as $row) {
                if($row['status'] == 0){
                    $status = 'Не состоялась';
                } else if($row['status'] == 1){
                    $status = 'Действующая';
                } else {
                    $status = 'Успешно выполнена (закрыта)';
                }
                $data = http_build_query([
                    'text' => 'Заявка №'.$row['id'].''.PHP_EOL.'Название и описание: '.$row['text'].' '.PHP_EOL.'Статус: '.$status,
                    'chat_id' =>  $chat_id
                ]);
                file_get_contents($botAPI . "/sendMessage?{$data}");
            }
        } else {
            $data = http_build_query([
                'text' => 'У вас нет заявок',
                'chat_id' =>  $chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
        }

        $dbh = null;

        break;

    case 'О проекте 📄':
        $data = http_build_query([
            'text' => 'Бот для подбора КРИПТООБМЕННИКА от команды RSI Capital

Как это работает?

1. Вы оставляете заявку в боте на обмен криптовалюты. Например: Вы хотите поменять N-сумму наличных средств в определённом городе и получить криптовалюту или наличные в другом.

2. Вашу заявку получат все криптообменники, а именно те, кто может обработать заявку, присылают Вам предложения с выгодным курсом и подходящими условиями.

3. Вы выбираете самый лучший для Вас вариант и проводите сделку с обменником.

Все обменники в нашем боте проходят обязательную проверку, в том числе  AML. 

Разработчик бота: @garant_rsi
Отзывы и предложения: @crypto_exchange_reviews
Если вы хотите стать обменником на нашей платформе, напишите: @Sabirov_Airat',
            'chat_id' =>  $chat_id
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
        break;

    case 'Отзывы ✍️':
        $data = http_build_query([
            'text' => 'Отзывы и предложения по работе бота Вы можете оставить в этом канале: @crypto_exchange_reviews',
            'chat_id' =>  $chat_id
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
        break;

    case 'Освободился. Закрыть заявку':

        $dbh =$newConnect;

        $query = $dbh->query('SELECT * FROM users_obmen WHERE status = 1 AND manager_id = '.$chat_id)->fetchAll();
        if(is_array($query) && count($query) > 0) {
            foreach ($query as $row) {
                $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = '.$row['chat_id']);
                $dbh->query('UPDATE users_obmen SET status = 0 WHERE chat_id = '.$row['chat_id'].' AND manager_id = '.$chat_id);
                // Для обычного пользователя
                $data = http_build_query([
                    'text' => "Обменник закрыл заявку! Теперь вы можете создать новую заявку!",
                    'chat_id' => $row['chat_id']
                ]);
                $inline_button1 = array("text"=>"Совершить обмен 🔄");
                $inline_button2 = array("text"=>"Мои заявки ⭐️");
                $inline_button3 = array("text"=>"О проекте 📄");
                $inline_button4 = array("text"=>"Отзывы ✍️");
                $inline_button5 = array("text"=>"Пригласить друга 💰");
                $inline_keyboard = [[$inline_button1], [$inline_button3, $inline_button4], [$inline_button5, $inline_button2]];
                $keyboard=array("keyboard"=>$inline_keyboard);
                $replyMarkup = json_encode($keyboard);
                file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
            }
        }

        $dbh->query('UPDATE users SET free_action = 0 WHERE chat_id = '.$chat_id);
        $dbh->query('UPDATE chat_room SET status = 0 WHERE manager_id = '.$chat_id);
        $dbh->query('DELETE FROM action_users WHERE action_id = 2 AND chat_id = '.$chat_id);

        $dbh = null;

        $data = http_build_query([
            'text' => 'Все заявки закрыты, вы можете принимать новые заявки от пользователей',
            'chat_id' =>  $chat_id
        ]);
        file_get_contents($botAPI . "/sendMessage?{$data}");
        break;

    case 'Пригласить друга 💰':
        $data = http_build_query([
            'text' => 'Если Ваш друг совершит обмен в боте, то Вы получите партнёрское вознаграждение - 20% от комиссии обменника.

Ваша реферальная ссылка — https://t.me/podbor_obmennika_bot?start=ref_'.$chat_id.'

Укажите ниже Ваши реквизиты кошелька USDT TRC20.',
            'chat_id' =>  $chat_id
        ]);
        $inline_button1 = array("text"=>"Ваши реквизиты","callback_data"=>"formRequisites");
        $inline_keyboard = [[$inline_button1]];
        $keyboard=array("inline_keyboard"=>$inline_keyboard);
        $replyMarkup = json_encode($keyboard);
        file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

        //https://t.me/b2bbroker_bot?start=ref403295577

        break;

    case 'Отменить заявку 🔄':

        $data = http_build_query([
            'text' => 'Вы уверены, что хотите отменить заявку?',
            'chat_id' => $chat_id
        ]);
        $inline_button1 = array("text"=>"Да","callback_data"=>"closeUserApplication");
        $inline_button2 = array("text"=>"Нет","callback_data"=>"noCloseUserApplication");
        $inline_keyboard = [[$inline_button1, $inline_button2]];
        $keyboard=array("inline_keyboard"=>$inline_keyboard);
        $replyMarkup = json_encode($keyboard);
        file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

        break;

    case 'Мое описание  📄':

        $method = 'sendMessage';

        $dbh =$newConnect;

        $query = $dbh->query('SELECT * from obmen_info WHERE chat_id = '.$chat_id)->fetchAll();

        if(is_array($query) && count($query) > 0) {
            foreach($query as $row) {
                $query = $dbh->query('SELECT * from action_users WHERE (action_id = 2 OR action_id = 3) AND chat_id = '.$chat_id)->fetchAll();
                if(is_array($query) && count($query) > 0) {
                    foreach ($query as $action) {
                        if($action['action_id'] == 2){
                            $actionText = 'У вас создана комната (чат) с пользователям';
                        } else {
                            $actionText = 'какое-то действие';
                        }
                        $data = http_build_query([
                            'text' => 'На данный момент запрещено делать данное действие, так как сейчас у Вас активно другое действие - '.$actionText,
                            'chat_id' =>  $chat_id
                        ]);
                        file_get_contents($botAPI . "/sendMessage?{$data}");
                    }
                } else {
                    $data = http_build_query([
                        'text' => 'Ваше действующее описание: 
                        
                        '.''.PHP_EOL.''.$row['info'].'',
                        'chat_id' =>  $chat_id
                    ]);
                    $inline_button1 = array("text"=>"Редактировать описание","callback_data"=>"edit_info_obmen");
                    $inline_keyboard = [[$inline_button1]];
                    $keyboard=array("inline_keyboard"=>$inline_keyboard);
                    $replyMarkup = json_encode($keyboard);
                    file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
                }
            }
            $dbh = null;
        } else {
            $data = http_build_query([
                'text' => '✍️ Напишите описание обменника, которое будет отправляться клиентам: название, преимущества, кол-во сделок.

Например: 
Здравствуйте! Можем провести ваш обмен по выгодному курсу!

О нас: E-obmen — это надёжный обменник представленный в 10 городах, работает с 2020 года, успешно провел более 10000 сделок

Менеджер: Егор',
                'chat_id' =>  $chat_id
            ]);
            file_get_contents($botAPI . "/sendMessage?{$data}");
            $dbh->query('INSERT INTO action_users (chat_id, action_id) VALUE ('.$chat_id.', '.$MOI_OPISANIE_OBMEN.')');
        }
        $dbh = null;
        break;

    default:

        $pars = explode(" ", $referal);
        if($pars[0] == '/start'){
            if(isset($pars[1])){
                $parsRef = explode("_", $pars[1]);
                if(isset($parsRef[1])){
                    $ref= $parsRef[1];
                    $dbh =$newConnect;
                    $dbh->query('UPDATE users SET referal_id = "'.$ref.'" WHERE chat_id = '.$chat_id);
                    $dbh = null;
                }
            }
        }

        $method = 'sendMessage';
        $dbh =$newConnect;




        $query = $dbh->query('SELECT * from users WHERE chat_id ='.$chat_id)->fetchAll();
        if(is_array($query) && count($query) > 0) {
            foreach($query as $row) {
                if($row['role_id'] == 1){
                    $getActionAdmin = $dbh->query('SELECT action_id from action_users WHERE chat_id = '.$chat_id)->fetchAll();
                    if(is_array($getActionAdmin) && count($getActionAdmin) > 0) {
                        foreach ($getActionAdmin as $row) {
                            if ($row['action_id'] == $SEND_ADMIN_MAIL_LIST) {
                                $dbh->query('INSERT INTO send_mail_list (user_id, text, status, date)  VALUE ('.$chat_id.', "'.$message.'", 0, NOW())');
                            }
                        }

                        $data = http_build_query([
                            'text' => '✨ Отлично! Отправляю?',
                            'chat_id' => $chat_id
                        ]);

                        $inline_button1 = array("text"=>"Да","callback_data"=>"yesConfirmSendSmsAllUser");
                        $inline_button2 = array("text"=>"Нет","callback_data"=>"noConfirmSendSmsAllUser");
                        $inline_keyboard = [[$inline_button1, $inline_button2]];
                        $keyboard=array("inline_keyboard"=>$inline_keyboard);
                        $replyMarkup = json_encode($keyboard);
                        $messageServer = file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

                        $getJson = json_decode($messageServer);
                        $message_id = $getJson->result->message_id;
                        $dbh->query('INSERT INTO number_message(manager_id, action_id, message_id)  VALUE ('.$chat_id.', '.$CONFIRM_SEND_SMS_ADMIN.', '.$message_id.')');

                    }
                }
                if($row['role_id'] == 2){
                    $query = $dbh->query('SELECT action_id from action_users WHERE chat_id = '.$chat_id)->fetchAll();
                    if(is_array($query) && count($query) > 0) {
                        foreach($query as $row) {
                            if($row['action_id'] == $MOI_OPISANIE_OBMEN){
                                $queryInfo = $dbh->query('SELECT * from obmen_info WHERE chat_id = '.$chat_id)->fetchAll();
                                if(is_array($queryInfo) && count($queryInfo) > 0) {
                                    $dbh->query('UPDATE obmen_info SET info = "'.$message.'" WHERE chat_id = '.$chat_id);
                                    $send_data = [
                                        'text'   => 'Ваше описание успешно отредактировано и выглядит так: '.PHP_EOL.'
                                        
'.$message,
                                    ];
                                } else {
                                    $dbh->query('INSERT INTO obmen_info (chat_id, info) VALUE ('.$chat_id.', "'.$message.'")');
                                    $send_data = [
                                        'text'   => 'Ваше описание успешно добавлено и выглядит так: '.PHP_EOL.' '.$message,
                                    ];
                                }
                                $dbh->query('DELETE FROM action_users WHERE action_id = 1 AND chat_id = '.$chat_id);
                            } elseif($row['action_id'] == $SEND_SMS_ROOM){
                                $query = $dbh->query('SELECT * from chat_room WHERE status = 1 AND (user_id = '.$chat_id.' OR manager_id = '.$chat_id.')')->fetchAll();
                                if(is_array($query) && count($query) > 0) {
                                    foreach($query as $item) {
                                        if($item['user_id'] == $chat_id){
                                            $dbh->query('INSERT INTO chat_room_message (chat_room_id, user_id, message, date) VALUE ('.$item['id'].', '.$item['user_id'].', "'.$message.'", NOW())');
                                            $data = http_build_query([
                                                'text' => $message,
                                                'chat_id' => $item['manager_id']
                                            ]);

                                            $inline_button1 = array("text"=>"Обмен совершен","callback_data"=>"endObmen_2");
                                            $inline_button2 = array("text"=>"Обмен не совершен","callback_data"=>"endNoObmen");
                                            $inline_keyboard = [[$inline_button1, $inline_button2]];
                                            $keyboard=array("inline_keyboard"=>$inline_keyboard);
                                            $replyMarkup = json_encode($keyboard);
                                            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

                                        } else {
                                            $dbh->query('INSERT INTO chat_room_message (chat_room_id, user_id, message, date) VALUE ('.$item['id'].', '.$item['manager_id'].', "'.$message.'", NOW())');
                                            $data = http_build_query([
                                                'text' => $message,
                                                'chat_id' => $item['user_id']
                                            ]);
                                            $inline_button1 = array("text"=>"Обмен совершен","callback_data"=>"endObmen_3");
                                            $inline_button2 = array("text"=>"Обмен не совершен","callback_data"=>"endNoObmen");
                                            $inline_keyboard = [[$inline_button1, $inline_button2]];
                                            $keyboard=array("inline_keyboard"=>$inline_keyboard);
                                            $replyMarkup = json_encode($keyboard);
                                            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
                                        }
                                    }
                                }
                                $dbh = null;
                                break;
                            } elseif($row['action_id'] == $CAN_ACTION_OBMEN_EXCHANGER){
                                $dbh->query('UPDATE send_message_exchanger SET text = "'.$message.'" WHERE status = 0 AND manager_id = '.$chat_id);


                                $getUser = $dbh->query('SELECT * from send_message_exchanger WHERE status = 0 AND manager_id = '.$chat_id)->fetchAll();
                                $user_id = 0;
                                if(is_array($getUser) && count($getUser) > 0) {
                                    foreach ($getUser as $row){
                                        $user_id = $row['user_id'];
                                    }
                                } else {
                                    $data = http_build_query([
                                        'text' => 'Возникли ошибки с пользователем. Обратитесь к администратору',
                                        'chat_id' =>  $chat_id
                                    ]);
                                    file_get_contents($botAPI . "/sendMessage?{$data}");
                                }

                                $data = http_build_query([
                                    'text' => 'Вы уверены, что хотите отправить сообщение клиенту?',
                                    'chat_id' => $chat_id
                                ]);
                                $inline_button1 = array("text"=>"Да","callback_data"=>"yesSendMessageExchanger_".$user_id);
                                $inline_button2 = array("text"=>"Нет","callback_data"=>"noSendMessageExchanger_".$user_id);
                                $inline_button3 = array("text"=>"Выйти из редактора","callback_data"=>"closeSendMessageExchanger_".$user_id);
                                $inline_keyboard = [[$inline_button1, $inline_button2], [$inline_button3]];
                                $keyboard=array("inline_keyboard"=>$inline_keyboard);
                                $replyMarkup = json_encode($keyboard);
                                $messageServer = file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);

                                $getJson = json_decode($messageServer);
                                $message_id = $getJson->result->message_id;
                                $dbh->query('INSERT INTO number_message(manager_id, action_id, message_id)  VALUE ('.$chat_id.', '.$CAN_ACTION_OBMEN_EXCHANGER.', '.$message_id.')');
                            }

                        }
                    }
                    $dbh = null;
                } else {
                    $dbh =$newConnect;
                    $query = $dbh->query('SELECT action_id from action_users WHERE chat_id = '.$chat_id)->fetchAll();
                    if(is_array($query) && count($query) > 0) {
                        foreach ($query as $row) {
                            if($row['action_id'] == $SEND_SMS_ROOM){
                                $query = $dbh->query('SELECT * from chat_room WHERE status = 1 AND (user_id = '.$chat_id.' OR manager_id = '.$chat_id.')')->fetchAll();
                                if(is_array($query) && count($query) > 0) {
                                    foreach($query as $row) {
                                        if($row['user_id'] == $chat_id){
                                            $dbh->query('INSERT INTO chat_room_message (chat_room_id, user_id, message, date) VALUE ('.$row['id'].', '.$row['user_id'].', "'.$message.'", NOW())');
                                            $data = http_build_query([
                                                'text' => $message,
                                                'chat_id' => $row['manager_id']
                                            ]);
                                            $inline_button1 = array("text"=>"Обмен совершен","callback_data"=>"endObmen_2");
                                            $inline_button2 = array("text"=>"Обмен не совершен","callback_data"=>"endNoObmen");
                                            $inline_keyboard = [[$inline_button1, $inline_button2]];
                                            $keyboard=array("inline_keyboard"=>$inline_keyboard);
                                            $replyMarkup = json_encode($keyboard);
                                            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
                                        } else {
                                            $dbh->query('INSERT INTO chat_room_message (chat_room_id, user_id, message, date) VALUE ('.$row['id'].', '.$row['manager_id'].', "'.$message.'", NOW())');
                                            $data = http_build_query([
                                                'text' => $message,
                                                'chat_id' => $row['user_id']
                                            ]);
                                            $inline_button1 = array("text"=>"Обмен совершен","callback_data"=>"endObmen_3");
                                            $inline_button2 = array("text"=>"Обмен не совершен","callback_data"=>"endNoObmen");
                                            $inline_keyboard = [[$inline_button1, $inline_button2]];
                                            $keyboard=array("answer_callback_query"=>$inline_keyboard);
                                            $replyMarkup = json_encode($keyboard);
                                            file_get_contents($botAPI . "/sendMessage?{$data}&reply_markup=".$replyMarkup);
                                        }
                                    }
                                }
                                $dbh = null;
                                break;
                            } elseif($row['action_id'] == $FORM_REQUISITES){
                                $dbh =$newConnect;
                                $dbh->query('UPDATE users SET requisites = "'.$message.'" WHERE chat_id = '.$chat_id);
                                $dbh->query('DELETE FROM action_users WHERE action_id = 3 AND chat_id = '.$chat_id);
                                $dbh = null;
                                $data = http_build_query([
                                    'text' => 'Реквизиты успешно записаны',
                                    'chat_id' =>  $chat_id
                                ]);
                                file_get_contents($botAPI . "/sendMessage?{$data}");
                            } elseif($row['action_id'] == $CREATE_USER_OBMEN){
                                $dbh->query('DELETE FROM action_users WHERE action_id = 4 AND chat_id = ' . $chat_id);
                                foreach($dbh->query('SELECT * from users_obmen WHERE chat_id = '.$chat_id.' AND status = 1') as $row) {
                                    if(isset($row['id'])){
                                        $dbh->query('UPDATE users_obmen SET text = "'.$message.'" WHERE status = 1 AND chat_id = '.$chat_id);

                                        foreach($dbh->query('SELECT first_name, chat_id FROM users WHERE role_id = 2 AND free_action = 0') as $row) {
                                            $dataObm = [
                                                'text'   => 'Заявка на обмен от пользователя  --'.$chat_id.'--'.PHP_EOL.'Имя пользователя: '.$first_name.' '.PHP_EOL.' 
Сообщение пользователя: '.$message,
                                                'reply_markup' => [
                                                    'inline_keyboard' => [
                                                        [
                                                            [
                                                                "text" => "Могу обменять",
                                                                "callback_data" => 'sendObmenSumm_'.$chat_id
                                                            ],
                                                        ]
                                                    ]
                                                ]
                                            ];
                                            $dataObm['chat_id'] = $row['chat_id'];
                                            $send_data = buttonUserObmen();
                                            sendTelegramMy($dataObm);
                                        }

                                    }
                                    $dbh = null;
                                    break;
                                }
                            }
                        }
                    } else {
//                        $dbh->query('DELETE FROM action_users WHERE action_id = 4 AND chat_id = ' . $chat_id);
//                        foreach($dbh->query('SELECT * from users_obmen WHERE chat_id = '.$chat_id.' AND status = 1') as $row) {
//                            if(isset($row['id'])){
//                                $dbh->query('UPDATE users_obmen SET text = "'.$message.'" WHERE status = 1 AND chat_id = '.$chat_id);
//
//                                foreach($dbh->query('SELECT first_name, chat_id FROM users WHERE role_id = 2 AND free_action = 0') as $row) {
//                                    $dataObm = [
//                                        'text'   => 'Заявка на обмен от пользователя  --'.$chat_id.'--'.PHP_EOL.'Имя пользователя: '.$first_name.' '.PHP_EOL.'
//Сообщение пользователя: '.$message,
//                                        'reply_markup' => [
//                                            'inline_keyboard' => [
//                                                [
//                                                    [
//                                                        "text" => "Могу обменять",
//                                                        "callback_data" => 'sendObmenSumm_'.$chat_id
//                                                    ],
//                                                ]
//                                            ]
//                                        ]
//                                    ];
//                                    $dataObm['chat_id'] = $row['chat_id'];
//                                    $send_data = buttonUserObmen();
//                                    sendTelegramMy($dataObm);
//                                }
//
//                            }
//                            $dbh = null;
//                            break;
//                        }
                    }
                }
            }
        } else {
            $button = checkUser($chat_id, $first_name, $username);
            $method = 'sendMessage';
            $send_data = $button;
            $dbh = null;
            break;
        }
        $dbh = null;
        break;

}

# Добавляем данные пользователя
$send_data['chat_id'] = $data['chat']['id'];

$res = sendTelegram($method, $send_data);


// Мой chat_id - 879771353
function checkUser($chat_id, $first_name, $username){

    $bd = include('bdret.php');

    $newConnect =  new PDO('mysql:host='.$bd['host'].';charset=utf8;dbname='.$bd['dbName'], $bd['bdUser'], $bd['bdPassword']);
    try {
        $dbh =$newConnect;
        $query = $dbh->query('SELECT * from users WHERE chat_id ='.$chat_id)->fetchAll();
        if(is_array($query) && count($query) > 0) {
            foreach($query as $row) {
                if(isset($row['id'])){
                    if($row['role_id'] == 1){
                        $dbh = null;
                        return buttonAdmin();
                    } elseif($row['role_id'] == 2){
                        $dbh = null;
                        return buttonObmen();
                    } else {
                        $query = $dbh->query('SELECT * from users_obmen WHERE chat_id = '.$chat_id.' AND status = 1')->fetchAll();
                        if(is_array($query) && count($query) > 0) {
                            $dbh = null;
                            return buttonUserObmen();
                        } else {
                            $dbh = null;
                            return buttonUser();
                        }
                    }
                }
            }
        } else {
            $date = date("Y-m-d H:i:s");
            $dbh->query('INSERT INTO users (first_name, username, chat_id, role_id, date) VALUE ("'.$first_name.'", "'.$username.'", '.$chat_id.', 3, "'.$date.'")');
            $dbh = null;
            return buttonUser();
        }
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

function buttonAdmin(){
    $send_data = [
        'text'   => 'Администратор',
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Пользователи'],
                    ['text' => 'Рассылка 🔉'],
                ],
            ]
        ]
    ];
    return $send_data;
}

function buttonUser(){
    $send_data = [
        'text'   => 'Добро пожаловать в бот для подбора КРИПТООБМЕННИКА от команды RSI Capital! 

Как работает бот?

1. Вы оставляете заявку в боте на обмен криптовалюты. Например: Вы хотите поменять N-сумму наличных средств в определённом городе и получить криптовалюту или наличные средства в другом.

2. Вашу заявку получат все криптообменники. Именно те, кто может обработать заявку, присылают Вам предложения с выгодным курсом и подходящими условиями.

3. Вы выбираете самый лучший для Вас вариант и проводите сделку с обменником.

Все обменники в нашем боте проходят обязательную проверку, в том числе 	AML. Чтобы начать, выберите необходимый пункт в меню👇
',
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Совершить обмен 🔄']
                ],
                [
                    ['text' => 'О проекте 📄'],
                    ['text' => 'Отзывы ✍️'],
                ],
                [
                    ['text' => 'Пригласить друга 💰'],
                    ['text' => 'Мои заявки ⭐️']
                ]
            ]
        ]
    ];
    return $send_data;
}

function buttonUserObmen(){
    $send_data = [
        'text'   => 'Ваша заявка отправлена всем свободным обменникам в нашем боте. Если они смогут обработать заявку, они пришлют вам сообщение с условиями обмена.

Далее вы сможете выбрать подходящий вам обменник и провести сделку! 👍',
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Отменить заявку 🔄'],
                ],
                [
                    ['text' => 'О проекте 📄'],
                    ['text' => 'Отзывы ✍️'],
                ],
                [
                    ['text' => 'Пригласить друга 💰'],
                    ['text' => 'Мои заявки ⭐️']
                ]
            ]
        ]
    ];
    return $send_data;
}


function buttonObmen(){
    $send_data = [
        'text'   => 'Вы теперь являетесь обменником, обновите меню - /start 

ВАЖНО! Чтобы принимать заявки, заполните описание обменника!',
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Мои заявки 🌟'],
                    ['text' => 'Мое описание  📄'],
                ],
                [
                    ['text' => 'Освободился. Закрыть заявку'],
                ],
            ]
        ]
    ];
    return $send_data;
}

function sendTelegram($method, $data, $headers = [])
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://api.telegram.org/bot' . TOKEN . '/' . $method,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $headers)
    ]);

    $result = curl_exec($curl);
    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}


function sendTelegramMy($data, $headers = [])
{
    $method = 'sendMessage';
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://api.telegram.org/bot' . TOKEN . '/' . $method,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $headers)
    ]);

    $result = curl_exec($curl);
    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}