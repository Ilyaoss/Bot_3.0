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
const CMD_BACK = 'BACK';
const CMD_MAIN = 'MAIN';
const CMD_MY = 'MY_SUBS';
const CMD_SUBS = 'SUBS';

const VK_TOKEN = '887f275780153f8d0a42339e542ecb1f1b6a47bce9385aea12ada07d3a459095800074da66b418d5911c9';
//'0f0567f6ffa539268e0b6558d7622d375e6232283542932eadc135443d88109330c37b64bbb8c26bf525a';

//Строка для подтверждения адреса сервера из настроек Callback API 
$confirmation_token = 'd18ce045'; 
$cur_lvl = 0;
$cur_mas = []; 
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

function getBtn2($label, $color = COLOR_DEFAULT, $payload = '', $prev) {
    return [
        'action' => [
            'type' => 'text',
            'payload' => json_encode([$prev=>$payload], JSON_UNESCAPED_UNICODE),
            'label' => $label
        ],
        'color' => $color
    ];
}

/*Цикл вывода в 2 ряда*/
/*for($i=0;$i<9;++$i) {
	$key = $keys_1[$i];
	array_push($buttons1,getBtn($key, COLOR_DEFAULT,$key));
	if($i%2>0) {
		array_push($buttons,$buttons1);
		$buttons1 = [];
	}
}*/
function getKbd($start, $end, $keys){
	$buttons = [];
	for($i=$start;$i<$end;++$i) {
		$key = $keys[$i];
		array_push($buttons,[getBtn($key, COLOR_DEFAULT,$key)]);
	}
	return $buttons;
}
function getKbd_2($start, $end, $keys, $prev){
	$buttons = [];
	$buttons_temp = [];
	for($i=$start;$i<$end;++$i) {
		$key = $keys[$i];
		array_push($buttons_temp,getBtn2($key, COLOR_DEFAULT,$key,$prev));
		if($i%2>0) {
			array_push($buttons,$buttons_temp);
			$buttons_temp = [];
		}
	}
	return $buttons;
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
for($i=1;$i<count($def_mas);++$i) {
	$value = $def_mas[$i];
	$array[$value[0]][$value[1]][] = $value[2];
}

$keys_1 = array_keys($array); /*Кнопки 1-го уровня*/
/*foreach($keys_1 as $key){
	array_push($buttons,[getBtn($key, COLOR_DEFAULT)]);
}*/

/*Цикл вывода в 2 ряда*/
/*for($i=0;$i<9;++$i) {
	$key = $keys_1[$i];
	array_push($buttons1,getBtn($key, COLOR_DEFAULT,$key));
	if($i%2>0) {
		array_push($buttons,$buttons1);
		$buttons1 = [];
	}
}*/

for($i=0;$i<count($keys_1);++$i) {
	$key = $keys_1[$i];
	myLog("Key $key count :".count(array_keys($array[$key])));
}

$buttons = getKbd(0,9,$keys_1);
array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('Далее-->', COLOR_POSITIVE,CMD_NEXT)]);

$keys_2 = array_unique(array_column($def_mas, 1),SORT_REGULAR);
$keys_3 = array_column($def_mas, 2);

myLog("Keyboard1: ".json_encode($buttons,JSON_UNESCAPED_UNICODE));
myLog("Keys: ".json_encode($keys_1[0],JSON_UNESCAPED_UNICODE));
//myLog("Array: ".json_encode($array),JSON_UNESCAPED_UNICODE);
myLog("Test: $array[0]\n count: ".count($array)."\n c1 ".$array['Комм и маркетинг']['Медиа'][0]);
myLog("Ключ 1: $keys_1[2] Ключ 2: $keys_2[6] Ключ 3: $keys_3[3]");
//myLog("Ключ 1: ".count($keys_1)."Ключ 2: ".count($keys_2)."Ключ 3: ".count($keys_3));
myLog("Array: $array[2][0][0]");
myLog("\nКомм и маркетинг.Производство ATL рекламы.Наружная и indoor реклама:\n".$array[$keys_1[2]][$keys_2[6]][3]);


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
		myLog("MSG: ".$body." PAYLOAD:".$payload);
		if ($payload) {
			$payload = json_decode($payload, true);
		}
		myLog("MSG: ".$body." PAYLOAD:".$payload);
		$kbd = [
			'one_time' => false,
			'buttons' => $buttons//,$buttons2]
		];
		$msg = "Список подкатегорий 1-го уровня, нажми для перехода во 2 уровень";

		switch($payload){
			case CMD_MAIN:
				$cur_lvl = 0;
				$buttons = [];
				array_push($buttons,[getBtn('Подписаться на категории', COLOR_DEFAULT,CMD_CAT)]);
				array_push($buttons,[getBtn('Мои подписки', COLOR_DEFAULT,CMD_MY)]);
				$kbd = [
					'one_time' => false,
					'buttons' => $buttons//,$buttons2]
				];
				break;
			case CMD_CAT:
				$cur_lvl = 1;
				myLog("CUR_LVL: $cur_lvl");
				$buttons = getKbd(0,9,$keys_1);
				array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('Далее-->', COLOR_POSITIVE,CMD_NEXT)]);
				//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
				$kbd = [
					'one_time' => false,
					'buttons' => $buttons
				];
				break;
			case CMD_MY:
				
				break;
			case CMD_BACK:
				$buttons = getKbd(0,9,$keys_1);
				array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('Далее-->', COLOR_POSITIVE,CMD_NEXT)]);
				//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
				$kbd = [
					'one_time' => false,
					'buttons' => $buttons
				];
				break;
			case CMD_NEXT:
				$buttons = getKbd(10,count($keys_1),$keys_1);
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
				//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
				
				$kbd = [
					'one_time' => false,
					'buttons' => $buttons
				];
				break;
			/*case CMD_CAT:
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
				break;*/
			default:
				myLog("CUR_LVL: $cur_lvl");
				/*if($cur_lvl == 1)/*Перешли с 1-го уровня
				{
					$cur_lvl = 2;
					myLog("CUR_LVL: $cur_lvl");
					$cur_mas = $array[$payload];
					$keys_2 = array_keys($array[$payload]);
					myLog("Keys2: ".json_encode($keys_2,JSON_UNESCAPED_UNICODE));
					$buttons = getKbd_2(0,count($keys_2),$keys_2,$payload);//count($keys_2)
					array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK)]);
					//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
					$kbd = [
						'one_time' => false,
						'buttons' => $buttons
					];
				}
				if($cur_lvl == 2)/*Перешли с 2-го уровня
				{
					$cur_lvl = 3;
					myLog("CUR_LVL: $cur_lvl");
					$keys_3 = $cur_mas[$payload];
					myLog("Keys3: ".json_encode($keys_3,JSON_UNESCAPED_UNICODE));
					$buttons = getKbd(0,count($keys_3),$keys_3);//count($keys_2)
					array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK)]);
					//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
					$kbd = [
						'one_time' => false,
						'buttons' => $buttons
					];
				}*/
				if(is_array($payload)){
					myLog("MSG: ".$body." PAYLOAD:".$payload[0]);
				}
				else
				{
					$keys_2 = array_keys($array[$payload]);
					myLog("Keys2: ".json_encode($keys_2,JSON_UNESCAPED_UNICODE));
					$buttons = getKbd_2(0,count($keys_2),$keys_2,$payload);//count($keys_2)
					array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK)]);
					//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
					$kbd = [
						'one_time' => false,
						'buttons' => $buttons
					];
				}
				/*foreach($keys_1 as $key)
				{
					if($key == $payload)
					{
						
						break;
					}
				}*/
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