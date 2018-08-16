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
const CMD_NAME = 'NAME';
const CMD_FAM = 'FAM';
const CMD_STAT = 'STAT';

const VK_TOKEN = '887f275780153f8d0a42339e542ecb1f1b6a47bce9385aea12ada07d3a459095800074da66b418d5911c9';
//'0f0567f6ffa539268e0b6558d7622d375e6232283542932eadc135443d88109330c37b64bbb8c26bf525a';

//Строка для подтверждения адреса сервера из настроек Callback API 
$confirmation_token = 'd18ce045'; 

function getBtn($label, $color = COLOR_DEFAULT, $payload = '') {
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

$xls = PHPExcel_IOFactory::load(__DIR__ . '/categories.xlsx');

// Первый лист
$xls->setActiveSheetIndex(0);
$sheet = $xls->getActiveSheet();

$json = file_get_contents('php://input');
myLog($json);
$data = json_decode($json, true);
$type = $data['type'] ?? '';
$vk = new VKApiClient('5.80', VKLanguage::RUSSIAN);
$catigories = [];
$def_mas = $sheet->toArray();
myLog("Выводит?".$def_mas[0][1]);
/*$level_12 = array_col( $def_mas, 1,0); /*асоц массив где ключи 1-й уровень, а значения 2-й*/
/*$level_23 = array_col( $def_mas, 2,1); /*асоц массив где ключи 2-й уровень, а значения 3-й*/
/*$keys_12 = array_keys($level_12); 
$keys_23 = array_keys($level_23);
$res = $level_12;
for($i=1;$i<count($level_12);$i++)
{
	myLog("\nВыводит!: ".$level_12[$keys_12[$i]]);
	/*for ($j=1;$j<count($level_12[$i]);$j++)
	{
		array_push($res[$i][$j], $level_23[$j]);
	}
}*/
$array = array();
foreach ($def_mas as $value) {
	//$array[$value[0]]=> $value[1];
	$array[$value[0]][$value[1]] = $value[3];
}
myLog("Test: $array[0]\n count: ".count($array)."\n c1 ".count($array['Комм и маркетинг']));

myLog("\nКомм и маркетинг.Производство ATL рекламы.Наружная и indoor реклама:\n $array[3][7][4]");


switch ($type) {
	case 'message_new':
		$message = $data['object'] ?? [];
		$userId = $message['from_id'] ?? 0; //user_id
		$body = $message['body'] ?? '';
		$payload = $message['payload'] ?? '';
		
		/*$user_info = $vk->users()->get(VK_TOKEN,['user_ids'=>$userId,
												'fields'=>'status']);*/
		/*myLog("Name: ".$user_info[0]['first_name'].
				"\nLasName: ".$user_info[0]['last_name'].
				"\nStatus: ".$user_info[0]['status']);*/
		
		if ($payload) {
			$payload = json_decode($payload, true);
		}
		myLog("MSG: ".$body." PAYLOAD:".$payload);
		$kbd = [
			'one_time' => false,
			'buttons' => [
				[getBtn("Покажи мой ID", COLOR_DEFAULT, CMD_ID)],
				[getBtn("Покажи моё имя", COLOR_DEFAULT, CMD_NAME)],
				[getBtn("Покажи мою фамилию", COLOR_DEFAULT, CMD_FAM)],
				[getBtn("Покажи мой статус", COLOR_DEFAULT, CMD_STAT)],
				[getBtn("Далее", COLOR_PRIMARY, CMD_NEXT)],
			]
		];
		$msg = "Привет я бот!";

		switch($payload){
			case CMD_ID:
				$msg = "Ваш id: ".$userId;
				break;
			case CMD_NAME:
				$msg = "Ваше имя : ".$user_info[0]['first_name'];
				break;
			case CMD_FAM:
				$msg = "Ваша фамилия: ".$user_info[0]['last_name'];
				break;
			case CMD_STAT:
				$msg = "Ваш статус: ".$user_info[0]['status'];
				break;
			case CMD_NEXT: 
				$kbd = [
					'one_time' => false,
					'buttons' => [
						[getBtn("Пришли котика", COLOR_POSITIVE, CMD_CAT)],
						[getBtn("Назад", COLOR_NEGATIVE)],
					]
				];
				$msg = null;
				break;
			case CMD_CAT:
				try {

					$url = $vk->photos()->getMessagesUploadServer(VK_TOKEN,['peer_id'=>$userId]); //peer_id не понятно?
					myLog("typeof".gettype($url));

					myLog("server: ".gettype($url["upload_url"])." ".$url['upload_url']. 
							'photo: '.gettype($url["album_id"])." ".$url['album_id']. 
							'hash: '.gettype($url["group_id"])." ".$url['group_id'].
							'count: '.count($url));
							
					$myCurl = curl_init();
					$f=curl_file_create(dirname(__FILE__)."/test.jpg",'image/jpeg','test_name.jpg');
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

					$res_img = json_decode($response,true);

					$uploadResult = $vk->photos()->saveMessagesPhoto(VK_TOKEN,['server'=>$res_img["server"],
																  'photo'=>$res_img["photo"],
																  'hash'=>$res_img["hash"]
																		]);
					myLog("own_id ".$uploadResult[0]['owner_id']);
					myLog("user_id ".$userId);
					myLog('photo'.$uploadResult[0]['owner_id'].'_'.$uploadResult[0]['id']);
					$res = $vk->messages()->send(VK_TOKEN, ['peer_id' => $userId,
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
		echo  "OK";
		break;
	case 'confirmation': 
		//...отправляем строку для подтверждения 
		echo $confirmation_token; 
		break; 
}