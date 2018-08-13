<?php 

require_once './vendor/autoload.php';

use VK\Client\Enums\VKLanguage;
use VK\Client\VKApiClient;

if (!isset($_REQUEST)) { 
return; 
} 

//Строка для подтверждения адреса сервера из настроек Callback API 
$confirmation_token = 'd18ce045'; 

//Ключ доступа сообщества 
const VK_TOKEN = '0f0567f6ffa539268e0b6558d7622d375e6232283542932eadc135443d88109330c37b64bbb8c26bf525a'; 

const VERSION = 5.80;

const COLOR_NEGATIVE = 'negative';
const COLOR_POSITIVE = 'positive';
const COLOR_DEFAULT = 'default';
const COLOR_PRIMARY = 'primary';

const CMD_ID = 'ID';
const CMD_NEXT = 'NEXT';
const CMD_QUEST = 'QUEST';

function getBtn($label, $color, $payload = '') {
    return [
        'action' => [
            'type' => 'text',
            "payload" => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'label' => $label
        ],
        'color' => $color
    ];
}

function myLog($str) {
    file_put_contents("php://stdout", "$str\n");
}

//Получаем и декодируем уведомление 
$data = json_decode(file_get_contents('php://input')); 
$vk = new VKApiClient('5.5', VKLanguage::RUSSIAN);

//Проверяем, что находится в поле "type" 
switch ($data->type) { 

//Если это уведомление для подтверждения адреса... 
case 'confirmation': 
	//...отправляем строку для подтверждения 
	echo $confirmation_token; 
	break; 

//Если это уведомление о новом сообщении... 
case 'message_new': 
	//...получаем id его автора 
	$user_id = $data->object->user_id; 
	//затем с помощью users.get получаем данные об авторе 
	$user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={".VK_TOKEN."}&v=5.5")); 

	//и извлекаем из ответа его имя 
	$user_name = $user_info->response[0]->first_name; 

	$payload = $data->object->payload;
	
	if ($payload) {
        $payload = json_decode($payload, true);
    }
	
	$kbd = [
		one_time => false,
		'buttons' => [
				[getBtn("Покажи мой ID", COLOR_DEFAULT, CMD_ID)],
				[getBtn("Далее", COLOR_PRIMARY, CMD_NEXT)],
			]
	];
	
	$msg = "Привет я бот!";
	
	//нестрогое == не забудь!
	switch(payload){
		case CMD_ID:
			$msg = "Ваш id ".$userId;
			break;
		case CMD_NEXT:
			$kbd = [
				'one_time' => false,
				'buttons' => [
					[getBtn("Как дела?", COLOR_POSITIVE, CMD_QUEST)],
					[getBtn("Назад", COLOR_NEGATIVE)],
				]
			];
			break;
		case CMD_QUEST:
			$msg = "Отлично, спасибо! А у тебя как?";
			break;
		
	}
		
		
	//С помощью messages.send отправляем ответное сообщение 
	/*$request_params = array( 
	'message' => "Hello, {$user_name}!", 
	'user_id' => $user_id, 
	'access_token' => VK_TOKEN, 
	'v' => VERSION 
	); */

	
	try {
        if ($msg !== null) {
            $response = $vk->messages()->send(VK_TOKEN, [
                'peer_id' => $userId,
                'message' => $msg,
                'keyboard' => json_encode($kbd, JSON_UNESCAPED_UNICODE)
            ]);
        }
    } catch (\Exception $e) {
        myLog( $e->getCode().' '.$e->getMessage() );
    }

	/*$get_params = http_build_query($request_params); 

	file_get_contents('https://api.vk.com/method/messages.send?'. $get_params); */

	//Возвращаем "ok" серверу Callback API 

	echo('ok'); 

	break; 

} 
?> 