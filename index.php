<?php
require_once './vendor/autoload.php';
//require_once './Excel/reader.php';
require_once __DIR__ . '/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php';

use VK\Client\Enums\VKLanguage;
use VK\Client\VKApiClient;

const COLOR_NEGATIVE = 'negative';
const COLOR_POSITIVE = 'positive';
const COLOR_DEFAULT = 'default';
const COLOR_PRIMARY = 'primary';

const CMD_ID = 'ID';
const CMD_NEXT = 'NEXT';
const CMD_CAT = 'CAT';

const VK_TOKEN = '887f275780153f8d0a42339e542ecb1f1b6a47bce9385aea12ada07d3a459095800074da66b418d5911c9';
//'0f0567f6ffa539268e0b6558d7622d375e6232283542932eadc135443d88109330c37b64bbb8c26bf525a';
//Строка для подтверждения адреса сервера из настроек Callback API 
$confirmation_token = 'd18ce045'; 

function getBtn($label, $color, $payload = '') {
    return [
        'action' => [
            'type' => 'text',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
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
$vk = new VKApiClient('5.80', VKLanguage::RUSSIAN);
/*$data = new Spreadsheet_Excel_Reader();
$data->setOutputEncoding('CP1251');
$data->read('Test.xls');
$mes = $data->sheets[0]['cells'][1][2];*/
// Файл xlsx
$xls = PHPExcel_IOFactory::load(__DIR__ . '/Test.xls');

// Первый лист
$xls->setActiveSheetIndex(0);
$sheet = $xls->getActiveSheet();
$mes = "test";
foreach ($sheet->toArray() as $row) {
   myLog($row[0]);
   myLog($row[1]);
}
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
				[getBtn("Покажи моё имя", COLOR_DEFAULT, CMD_ID)],
				[getBtn("Покажи мою фамилию", COLOR_DEFAULT, CMD_ID)],
				[getBtn("Покажи мой статус", COLOR_DEFAULT, CMD_ID)],
				[getBtn("Далее", COLOR_PRIMARY, CMD_NEXT)],
			]
		];
		$msg = "Привет я бот!";
		/*$photo_cat = [
			'type' => 'photo',
			'photo' =>[
				'photo_2560'=> @'https://s.fishki.net/upload/users/2017/04/05/414721/8419b6ac67d83d3dea58db13a67b2763.jpg'
			]
		];*/
		switch($payload){
			case CMD_ID:

				$msg = "Ваш id ".$mes;//$userId;
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
					$url = $vk->photos()->getMessagesUploadServer(VK_TOKEN,['peer_id'=>$userId]); //peer_id не понятно?
					myLog("typeof".gettype($url));
					//$file = [files={'photo': open('test.jpg', 'rb')};
					$img = __DIR__ . '/test.jpg';
					
					$result;// = json_decode($url,true);
					myLog("server: ".gettype($url["upload_url"])." ".$url['upload_url']. 
							'photo: '.gettype($url["album_id"])." ".$url['album_id']. 
							'hash: '.gettype($url["group_id"])." ".$url['group_id'].
							'count: '.count($url));
							
					//myLog("ver: ". curl_version()[version]);
					$myCurl = curl_init();
					//$file = 'https://s.fishki.net/upload/users/2017/04/05/414721/8419b6ac67d83d3dea58db13a67b2763.jpg';
					//$file = curl_file_create($file, mime_content_type($file), pathinfo($file)['basename']);
					$f=curl_file_create('./test.jpg','image/jpeg','test');
					myLog($f->getFilename());
					$data_file = ['photo'=> $f];
					curl_setopt_array($myCurl, array(
						CURLOPT_URL => $url['upload_url'],
						CURLOPT_HTTPHEADER=>['Content-Type: multipart/form-data'],
						CURLOPT_SSL_VERIFYPEER=> false,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => $data_file
					));
					$response = curl_exec($myCurl);
					curl_close($myCurl);

					myLog("Ответ на Ваш запрос: ".$response);
					
					/*$curl = curl_init();
					$file = 'https://s.fishki.net/upload/users/2017/04/05/414721/8419b6ac67d83d3dea58db13a67b2763.jpg';
					$file = curl_file_create($file,'image/jpeg','test');
					curl_setopt($curl, CURLOPT_URL,$url['upload_url']);
					curl_setopt($curl, CURLOPT_POST,true);
					curl_setopt($curl, CURLOPT_HTTPHEADER,['Content-Type: multipart/form-data;charset=utf-8']);
					curl_setopt($curl, CURLOPT_POSTFIELDS,['file'=>$file]);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
					curl_setopt($curl, CURLOPT_TIMEOUT,10);
					$response = curl_exec($curl);
					
					myLog("Ответ на Ваш запрос: ".$response);*/
					
					$res_img = json_decode($response,true);
					//myLog("Ответ на Ваш запрос: ".$res_img["photo"][0]);
					$uploadResult = $vk->photos()->saveMessagesPhoto(VK_TOKEN,['server'=>$res_img["server"],
																  'photo'=>$res_img["photo"],
																  'hash'=>$res_img["hash"]
																		]);

					/*vkapi.messages.send(user_id=target_id,
										message="randomTextMessage",
										attachment=uploadResult[0]["id"])*/
					/*$response_image = json_decode($vk->photos()->saveMessagesPhoto(VK_TOKEN, array( 
							'server' => $result['server'], 
							'photo' => $result['photo'], 
							'hash' => $result['hash'], 
							)),true);
					myLog("response_image: ".$response_image);*/
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
						'attachment' => 'photo'.$uploadResult[0]['owner_id'].'_'.$uploadResult[0]['id']
					]);
					$msg = null;
					break;
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