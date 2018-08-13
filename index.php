<?php
require_once './vendor/autoload.php';

use VK\Client\Enums\VKLanguage;
use VK\Client\VKApiClient;

const COLOR_NEGATIVE = 'negative';
const COLOR_POSITIVE = 'positive';
const COLOR_DEFAULT = 'default';
const COLOR_PRIMARY = 'primary';

const CMD_ID = 'ID';
const CMD_NEXT = 'NEXT';
const CMD_TYPING = 'TYPING';

const VK_TOKEN = '0f0567f6ffa539268e0b6558d7622d375e6232283542932eadc135443d88109330c37b64bbb8c26bf525a';
//Строка для подтверждения адреса сервера из настроек Callback API 
$confirmation_token = 'd18ce045'; 

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

$json = file_get_contents('php://input');
//myLog($json);
$data = json_decode($json, true);
$type = $data['type'] ?? '';
$vk = new VKApiClient('5.78', VKLanguage::RUSSIAN);
switch ($type) {
	case 'message_new':
		$message = $data['object'] ?? [];
		$userId = $message['user_id'] ?? 0;
		$body = $message['body'] ?? '';
		$payload = $message['payload'] ?? '';
		echo payload;
		if ($payload) {
			$payload = json_decode($payload, true);
		}
		myLog("MSG: ".$body." PAYLOAD:".$payload);
		$kbd = [
			'one_time' => false,
			'buttons' => [
				[getBtn("Покажи мой ID", COLOR_DEFAULT, CMD_ID)],
				[getBtn("Далее", COLOR_PRIMARY, CMD_NEXT)],
			]
		];
		$msg = "Привет я бот!";
		switch(payload){
			case CMD_ID:
				$msg = "Ваш id ".$userId;
				break;
			case CMD_NEXT: 
				$kbd = [
					'one_time' => false,
					'buttons' => [
						[getBtn("Пошли тайпинг", COLOR_POSITIVE, CMD_TYPING)],
						[getBtn("Назад", COLOR_NEGATIVE)],
					]
				];
				break;
			case CMD_TYPING:
				try {
					$res = $vk->messages()->setActivity(VK_TOKEN, [
						'peer_id' => $userId,
						'type' => 'typing'
					]);
					$msg = null;
				} catch (\Exception $e) {
					myLog( $e->getCode().' '.$e->getMessage() );
				}
				break;
		}
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
		echo  "OK";
		break;
	case 'confirmation': 
		//...отправляем строку для подтверждения 
		echo $confirmation_token; 
		break; 
}