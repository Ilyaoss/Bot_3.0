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
const CMD_CAT = 'CAT';

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
		$photo_cat = [
			'type' => 'photo',
			'photo' =>[
				'photo_2560'=> @'https://s.fishki.net/upload/users/2017/04/05/414721/8419b6ac67d83d3dea58db13a67b2763.jpg'
			]
		];
		switch($payload){
			case CMD_ID:
				$msg = "Ваш id ".$userId;
				break;
			case CMD_NEXT: 
				$kbd = [
					'one_time' => false,
					'buttons' => [
						[getBtn("Пошли котика", COLOR_POSITIVE, CMD_CAT)],
						[getBtn("Назад", COLOR_NEGATIVE)],
					]
				];
				break;
			case CMD_CAT:
				try {
					/*$request_param = [
						'access_token' => VK_TOKEN,
						'v' => '5.78'
					];*/
					$url = $vk->photos()->getMessagesUploadServer(userId);
					$result = json_decode($url,true);
					
					/*$curl = curl_init();
					$file = 'https://s.fishki.net/upload/users/2017/04/05/414721/8419b6ac67d83d3dea58db13a67b2763.jpg';
					$file = curl_file_create($file, mime_content_type($file), pathinfo($file)['basename']);
					curl_setopt($curl, CURLOPT_URL,$result['response']['upload_url']);
					curl_setopt($curl, CURLOPT_POST,true);
					curl_setopt($curl, CURLOPT_HTTPHEADER,['Content-Type: multipart/form-data;charset=utf-8']);
					curl_setopt($curl, CURLOPT_POSTFIELDS,['file'=>$file]);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
					curl_setopt($curl, CURLOPT_TIMEOUT,10);
					curl_setopt($curl, CURLOPT_FOLLOWINGLOCATION,true);*/
					
					$response_image = json_decode($vk->photos()->saveMessagesPhoto(VK_TOKEN, array( 
							'server' => $result['server'], 
							'photo' => $result['photo'], 
							'hash' => $result['hash'], 
							)),true);
					
					/*$request_params = [
						'server' => $response_image['server'],
						'photo' =>$response_image['photo'],
						'hash' =>$response_image['hash'],
						'access_token' => VK_TOKEN,
						'v' => '5.78'
					];
					$url = $vk->photos()->saveMessagesPhoto(request_params);
					$res_img = json_decode(curl_exec($curl),true);*/
					
					$res = $vk->messages()->send(VK_TOKEN, [
						'peer_id' => $userId,
						'attachment' => 'photo'.$response_image['response'][0]['owner_id'].'_'.$response_image['response'][0]['id']
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
		/*if ($payload === CMD_ID) {
			$msg = "Ваш id ".$userId;
		}
		if ($payload === CMD_NEXT) {
			$kbd = [
				'one_time' => false,
				'buttons' => [
					[getBtn("Пошли тайпинг", COLOR_POSITIVE, CMD_TYPING)],
					[getBtn("Назад", COLOR_NEGATIVE)],
				]
			];
		}
		if ($payload === CMD_TYPING) {
			try {
				$res = $vk->messages()->setActivity(VK_TOKEN, [
					'peer_id' => $userId,
					'type' => 'typing'
				]);
				$msg = null;
			} catch (\Exception $e) {
				myLog( $e->getCode().' '.$e->getMessage() );
			}
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
			
		}*/
		echo  "OK";
		break;
	case 'confirmation': 
		//...отправляем строку для подтверждения 
		echo $confirmation_token; 
		break; 
}