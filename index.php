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
const CMD_UNSUBS = 'UNSUBS';
const CMD_UNSUBS_ALL = 'UNSUBS_ALL';
const CMD_YES = 'YES';
const CMD_FEEDBACK = 'FEEDBACK';
const MAX_LENGHT = 73;
//const MAX_LENGHT  = strlen('Список подкатегорий 1-го уровня, нажмите');
const VK_TOKEN = '887f275780153f8d0a42339e542ecb1f1b6a47bce9385aea12ada07d3a459095800074da66b418d5911c9';
//'0f0567f6ffa539268e0b6558d7622d375e6232283542932eadc135443d88109330c37b64bbb8c26bf525a';
 
//Строка для подтверждения адреса сервера из настроек Callback API 
$confirmation_token = 'd18ce045'; 
 
function getBtn($label, $color = COLOR_DEFAULT, $payload = '') {
	/*if(strlen($label)>MAX_LENGHT)
	{
		$start = MAX_LENGHT/2 - 3;
		$end = MAX_LENGHT/2 + 3;
		myLog("Lab bef: $label count:".strlen($label));
		$label = substr($label,0,$start).'.. ..'.substr($label,-(MAX_LENGHT-$end));
		myLog("Lab aft: $label count:".strlen($label));
	}*/
	return [
        'action' => [
            'type' => 'text',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'label' => $label
        ],
        'color' => $color
    ];
}

function getKbd_unsub($start, $end, $keys){
	$buttons = [];
	for($i=$start;$i<$end;++$i) {
		$key = $keys[$i];
		array_push($buttons,[getBtn($key, COLOR_DEFAULT,[CMD_UNSUBS=>'_'.$i])]);

	}
	myLog("buttons: ".json_encode($buttons,JSON_UNESCAPED_UNICODE));
	return $buttons;
}

function getKbd($start, $end, $keys, $prev = null){
	$buttons = [];

	for($i=$start;$i<$end;++$i) {
		$key = $keys[$i];
		/*Если установлен*/
		if(!is_null($prev))
		{
			if(is_array($prev))
			{
				$k = array_keys($prev);
				array_push($buttons,[getBtn($key, COLOR_DEFAULT,[$k[0]=>[$prev[$k[0]]=>$key]])]);//getBtn2
			}
			else
			{
				array_push($buttons,[getBtn($key, COLOR_DEFAULT,[$prev=>$key])]);//
			}
		}
		else
		{
			array_push($buttons,[getBtn($key, COLOR_DEFAULT,$key)]);
		}
		//array_push($buttons_temp,getBtn($key, COLOR_DEFAULT,[$prev=>$key]));//getBtn2

	}
	return $buttons;
}

function get_Butt_level($lvl,$keys = null,$payload = null,$CMD_NEXT = false)
{	
	$key = array_keys($payload);
	$buttons = [];
	$b_main = getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN);
	switch($lvl)
	{
		case 0:
			array_push($buttons,[getBtn('Подписаться на категории', COLOR_DEFAULT,CMD_CAT)]);
			array_push($buttons,[getBtn('Мои подписки', COLOR_DEFAULT,CMD_MY)]);
			array_push($buttons,[getBtn('Отписаться', COLOR_DEFAULT,CMD_UNSUBS)]);
			break;
		case 1:
			if($CMD_NEXT)
			{
				$buttons = getKbd(10,count($keys),$keys);
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK),$b_main]);
			}
			else
			{
				$buttons = getKbd(0,9,$keys);
				array_push($buttons,[$b_main,getBtn('Далее-->', COLOR_POSITIVE,CMD_NEXT)]);
			}
			break;
		case 2:
			/*Если меньше 9, то выводим все + 2 кнопки(подписатся на всё и назад/в главное меню)*/
			if(count($keys)<9)
			{
				if($payload=='Прочее')
				{
					$msg = "Список подкатегорий в $payload.\nНажмите чтобы подписаться.\n";
				}
				$buttons = getKbd(0,count($keys),$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK),$b_main]);
			}
			else
			{
				$buttons = getKbd(0,7,$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
				array_push($buttons,[$b_main,getBtn('На след стр. -->', COLOR_POSITIVE,[$payload=>CMD_NEXT])]);
				array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,CMD_BACK)]);
			}
			break;
		case 3:
			if(count($keys)<9)
			{
				$buttons = getKbd(0,count($keys),$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$payload[$key[0]]=>'SUBS_ALL']])]);//[$payload=>'SA']   [$k[0]=>[$prev[$k[0]]=>$key]]
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,$key[0]),$b_main]);
			}
			else
			{
				$buttons = getKbd(0,7,$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$payload[$key[0]]=>'SUBS_ALL']])]);
				array_push($buttons,[$b_main,getBtn('На след стр. -->', COLOR_POSITIVE,[$key[0]=>[$payload[$key[0]]=>CMD_NEXT]])]);//[$k[0]=>[$prev[$k[0]]=>$key]]
				array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,$key[0])]);
			}
			break;

	}
	return $kbd = [
				'one_time' => false,
				'buttons' => $buttons
			];
}

function add_to_file($str, $userId)
{
	$data = read_file($userId);
	$length = count($data[$userId]);
	for($i=0;$i<$length;++$i)
	{
		$user_data = $data[$userId][$i];
		myLog("us_d: $user_data str: $str strpos:".strpos($user_data,$str));
		/*Если наша категория является подкатегорией, то есть родительская входит в неё в начало*/
		if(strpos($str,$user_data) === 0)
		{
			return 'Вы уже пописаны на эту катогрею или на родительскую категорию';
		}
		/*Если наша категория является родительской, то мы убираем всех её детей и добавляем её*/
		if(strpos($user_data,$str) === 0)
		{
			unset($data[$userId][$i]);	// Удалить подписку
			myLog("data: ".json_encode($data,JSON_UNESCAPED_UNICODE));
		}
	}
	
	$data[$userId][]="$str";	// Добавить подписку
	$data[$userId] = array_values($data[$userId]); //переиндексируем с 0 до конца
	myLog("data: ".json_encode($data,JSON_UNESCAPED_UNICODE));
	file_put_contents(__DIR__ . '/data.json',json_encode($data,JSON_UNESCAPED_UNICODE));  // Перекодировать в формат и записать в файл.
	unset($data);
	return $msg = "Вы успешно подписались на $str";//.$payload[$key[0]][$keys[0]];
}

function read_file()
{
	$file = file_get_contents(__DIR__ . '/data.json');  // Открыть файл data.json
	myLog("file: $file");
	$data = json_decode($file,TRUE);        // Декодировать в массив 						
	unset($file);                               // Очистить переменную $file		   
	return $data;
}

function delete_from_file($idx, $userId)
{
	$data = read_file($userId);		   
	$msg = "Вы успешно отписались от  ".$data[$userId][$idx];
	unset($data[$userId][$idx]);	// Удалить подписку
	$data[$userId] = array_values($data[$userId]);
	file_put_contents(__DIR__ . '/data.json',json_encode($data,JSON_UNESCAPED_UNICODE));  // Перекодировать в формат и записать в файл.
	unset($data);
	return $msg;
}

function myLog($str) {
    file_put_contents("php://stdout", "$str\n");
}

/*--Парсим xls с категориями--*/
$xls = PHPExcel_IOFactory::load(__DIR__ . '/categories.xlsx');

// Первый лист
$xls->setActiveSheetIndex(0);
$sheet = $xls->getActiveSheet();
$def_mas = $sheet->toArray();

$json = file_get_contents('php://input');
myLog("JSON".$json);
/*--*/
$data = json_decode($json, true);
$type = $data['type'] ?? '';
$vk = new VKApiClient('5.80', VKLanguage::RUSSIAN);
$catigories = [];

/*--Создаём ассоц. массив--*/
$array = array();
for($i=1;$i<count($def_mas);++$i) {
	$value = $def_mas[$i];
	$array[$value[0]][$value[1]][] = $value[2];
}

$keys_1 = array_keys($array); /*Кнопки 1-го уровня*/
$keys_2 = array_unique(array_column($def_mas, 1),SORT_REGULAR);
$keys_3 = array_column($def_mas, 2);

$buttons = [];
$kbd = [];
switch ($type) {
	case 'message_new':
		$message = $data['object'] ?? [];
		$userId = $message['from_id'] ?? 0; //user_id
		$body = $message['body'] ?? '';
		$payload = $message['payload'] ?? '';
		$text = $message['text'] ?? '';
		
		/*$user_info = $vk->users()->get(VK_TOKEN,['user_ids'=>$userId,
												'fields'=>'status']);*/
		/*myLog("Name: ".$user_info[0]['first_name'].
				"\nLasName: ".$user_info[0]['last_name'].
				"\nStatus: ".$user_info[0]['status']);*/
		myLog("MSG: ".$body." PAYLOAD string:".$payload);
		if ($payload) {
			$payload = json_decode($payload, true);
		}
		myLog("MSG: ".$body." PAYLOAD:".$payload);
		$msg = "Список подкатегорий 1-го уровня, нажмитете для перехода во 2 уровень";
	
		switch($payload){
			case 'stop':
				$msg = null;
				break;
			case(''):
			case CMD_MAIN:
				$msg = "Нажмите любую кнопку";			
				$kbd = get_Butt_level(0);
				break;
			case CMD_CAT:
				
				$kbd = get_Butt_level(1,$keys_1);
				break;
			case CMD_MY:
				$msg = "Список моих подписок:\n";
				$file = file_get_contents(__DIR__ . '/data.json');  // Открыть файл data.json
				myLog("file: $file");
				$data = json_decode($file,TRUE);   // Декодировать в массив 						
				unset($file);                      // Очистить переменную $file		   
				$my_subs = $data[$userId];
				foreach($my_subs as $item)
				{
					$msg = $msg."-$item\n";
				}
				unset($data);
				if($msg=="Список моих подписок:\n")
				{
					$msg = 'Нет активных подписок';
					
				}
				
				$kbd = get_Butt_level(0);
				$payload = CMD_MAIN;
				break;
			case CMD_BACK:
				
				$kbd = get_Butt_level(1,$keys_1);
				$payload = CMD_MAIN;
				break;
			case CMD_NEXT:
				$kbd = get_Butt_level(1,$keys_1,null,true);
				break;
			case CMD_UNSUBS:
				$msg = 'Нажмите, чтобы отписаться';
				$data = read_file();
				$user_data = $data[$userId];
				myLog("userdata: ".json_encode($user_data,JSON_UNESCAPED_UNICODE));
				if(is_null($user_data))
				{
					$msg = 'Нет активных подписок';
					$kbd = get_Butt_level(0);
				}
				else
				{
					if(count($user_data)<9)
					{
						myLog("&&");
						$buttons = getKbd_unsub(0,count($user_data),$user_data);
						array_push($buttons,[getBtn('Отписаться от всего', COLOR_NEGATIVE,CMD_UNSUBS_ALL)]);
						array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
					}
					else
					{
						$buttons = getKbd_unsub(0,8,$user_data);//count($keys_2)
						array_push($buttons,[getBtn('Отписаться от всего', COLOR_NEGATIVE,CMD_UNSUBS_ALL)]);
						array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('На след стр. -->', COLOR_POSITIVE,[CMD_UNSUBS=>1])]);//[$k[0]=>[$prev[$k[0]]=>$key]]
					}
					myLog("buttons: ".json_encode($buttons,JSON_UNESCAPED_UNICODE));
					$kbd = [
						'one_time' => false,
						'buttons' => $buttons
					];
				}
				break;
			case CMD_UNSUBS_ALL:
				$msg = 'Вы точно хотите от всего отписаться?';
				array_push($buttons,[getBtn('Да', COLOR_POSITIVE,CMD_YES)]);
				array_push($buttons,[getBtn('Нет',COLOR_NEGATIVE,CMD_UNSUBS)]);
				$kbd = [
					'one_time' => false,
					'buttons' => $buttons
				];
				break;
			case CMD_YES:
				$data = read_file($userId);		   
				$data[$userId] = [];
				file_put_contents(__DIR__ . '/data.json',json_encode($data,JSON_UNESCAPED_UNICODE));  // Перекодировать в формат и записать в файл.
				unset($data);
				$msg = 'Все подписки отменены';
				$kbd = get_Butt_level(0);
				break;
			case CMD_FEEDBACK:
				$msg = 'Опиши и отправь мне проблему с которой ты столкнулся';
				$buttons = [];
				array_push($buttons,[getBtn('<-- Назад', COLOR_NEGATIVE,CMD_MAIN)]);
				$kbd = [
					'one_time' => false,
					'buttons' => $buttons
				];
				$payload = 'stop';
				break;
			default:
				if(is_array($payload)){
					$key = array_keys($payload);
					if(is_array($payload[$key[0]]))
					{
						/*4-й уровень - подписка оформляется*/
						myLog("MSG: ".$body." PAYLOAD_val1:".json_encode($payload[$key[0]],JSON_UNESCAPED_UNICODE));
						$keys = array_keys($payload[$key[0]]);
						
						if($payload[$key[0]][$keys[0]]==CMD_NEXT)
						{
							$msg = "Список подкатегорий в $key[0].$keys[0].\nНажмите для чтобы подписаться.\n";
							$keys_3 = $array[$key[0]][$keys[0]];
							$buttons = getKbd(7,count($keys_3),$keys_3,$payload);//count($keys_2)
							array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
							array_push($buttons,[getBtn('<-- На пред. стр.', COLOR_NEGATIVE,[$key[0]=>$keys[0]]),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
							array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,CMD_BACK)]);
							myLog("TEST ".json_encode($kbd, JSON_UNESCAPED_UNICODE));
							$kbd = [
								'one_time' => false,
								'buttons' => $buttons
							];
						}
						/*C 3 уровня пришла комманда SUBS_ALL*/
						elseif($payload[$key[0]][$keys[0]]== 'SUBS_ALL')
						{
							/*------------DK--------------*/
							$str = "$key[0].$keys[0]";
							$msg = add_to_file($str, $userId);
							/*-------------DK------------*/
							$keys_3 = $array[$key[0]][$keys[0]];
							/*Если меньше 9, то выводим все + 2 кнопки(подписатся на всё и назад/в главное меню)*/
							if(count($keys_3)<9)
							{
								$buttons = getKbd(0,count($keys_3),$keys_3,[$key[0]=>$keys[0]]);
								array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$keys[0]=>'SUBS_ALL']])]);//[$payload=>'SA']   [$k[0]=>[$prev[$k[0]]=>$key]]
								array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,$key[0]),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
							}
							else
							{
								$buttons = getKbd(0,7,$keys_3,$payload);//count($keys_2)
								array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$keys[0]=>'SUBS_ALL']])]);
								array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('На след стр. -->', COLOR_POSITIVE,[$key[0]=>[$keys[0]=>CMD_NEXT]])]);//[$k[0]=>[$prev[$k[0]]=>$key]]
								array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,$key[0])]);
							}
							myLog("Keys3: ".json_encode($keys_3,JSON_UNESCAPED_UNICODE));
							//buttons = getKbd_2(0,count($keys_2),$keys_2,$payload);//count($keys_2)
							//array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,[$payload=>CMD_BACK])]);
							//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
							$kbd = [
								'one_time' => false,
								'buttons' => $buttons
							];
							/*------------DK--------------*/
						}
						else{
							
							$str = "$key[0].$keys[0].".$payload[$key[0]][$keys[0]];
							$msg = add_to_file($str, $userId);
							/*Отправляем клавиатуру*/
							$keys_3 = $array[$key[0]][$keys[0]];
							/*Если меньше 9, то выводим все + 2 кнопки(подписатся на всё и назад/в главное меню)*/
							if(count($keys_3)<9)
							{
								$buttons = getKbd(0,count($keys_3),$keys_3,[$key[0]=>$keys[0]]);
								array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$keys[0]=>'SUBS_ALL']])]);//[$payload=>'SA']   [$k[0]=>[$prev[$k[0]]=>$key]]
								array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,$key[0]),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
							}
							else
							{
								$buttons = getKbd(0,7,$keys_3,$payload);//count($keys_2)
								array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$keys[0]=>'SUBS_ALL']])]);
								array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('На след стр. -->', COLOR_POSITIVE,[$key[0]=>[$keys[0]=>CMD_NEXT]])]);//[$k[0]=>[$prev[$k[0]]=>$key]]
								array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,$key[0])]);
							}
							myLog("Keys3: ".json_encode($keys_3,JSON_UNESCAPED_UNICODE));
							//buttons = getKbd_2(0,count($keys_2),$keys_2,$payload);//count($keys_2)
							//array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,[$payload=>CMD_BACK])]);
							//array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
							$kbd = [
								'one_time' => false,
								'buttons' => $buttons
							];
						}
						
					}
					else
					{	
						/*Пришло сообщение от 2 уровня*/
						myLog("MSG: ".$body." PAYLOAD_val:".$payload[$key[0]]);
						if($payload[$key[0]]=== CMD_NEXT)
						{
							$msg = "Список подкатегорий в $key[0].\nНажмите для открытия подкатегорий.\n";
							myLog("???");
							$keys_2 = array_keys($array[$key[0]]);
							$buttons = getKbd(7,count($keys_2),$keys_2,$key[0]);//count($keys_2)
							array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0]=>'SUBS_ALL'])]);
							array_push($buttons,[getBtn('<-- На пред. стр.', COLOR_NEGATIVE,$key[0]),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
							array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,CMD_BACK)]);
							myLog("TEST ".json_encode($kbd, JSON_UNESCAPED_UNICODE));
							$kbd = [
								'one_time' => false,
								'buttons' => $buttons
							];
						}
						/*C 2-го уровня пришла ком. SUBS ALL*/
						elseif($payload[$key[0]]=== 'SUBS_ALL')
						{
							$str = $key[0];
							$msg = add_to_file($str, $userId);
							
							$keys_2 = array_keys($array[$key[0]]);
							myLog("Keys2: ".json_encode($keys_2,JSON_UNESCAPED_UNICODE));

							$kbd = get_Butt_level(2,$keys_2,$key[0]);
							myLog("CHECK THIS OUT: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
						}
						/*прочее*/
						elseif($payload[$key[0]]==='Прочее')
						{
							$str = "$key[0].".$payload[$key[0]];
							$msg = add_to_file($str, $userId);
							$keys_2 = array_keys($array[$key[0]]);
							myLog("Keys2: ".json_encode($keys_2,JSON_UNESCAPED_UNICODE));

							$kbd = get_Butt_level(2,$keys_2,$key[0]);
							myLog("CHECK THIS OUT: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
						}
						/*след страница отписок*/
						elseif($key[0]===CMD_UNSUBS)
						{
							$s = substr($payload[$key[0]],0,1);
							myLog("s: ".$S);
							if($s==='_')
							{
								$s = substr($payload[$key[0]],1);
								$msg = delete_from_file($s,$userId);
								$payload = CMD_MAIN;
							}
							else
							{
								$data = read_file();
								$user_data = $data[$userId];
								$idx = $payload[$key[0]];
								
								$b_main = getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN);
								$b_next = getBtn('На след. стр. -->', COLOR_POSITIVE,[CMD_UNSUBS=>$idx+1]);
								$b_prev = getBtn('<-- На пред. стр.', COLOR_NEGATIVE,[CMD_UNSUBS=>$idx-1]);
								if(8*($idx+1)<count($user_data))
								{
									$buttons = getKbd_unsub(8*$idx,8*($idx+1),$user_data);
									array_push($buttons,[getBtn('Отписаться от всего', COLOR_NEGATIVE,'UNSUBS_ALL')]);
									if($idx > 0)
									{
										array_push($buttons,[$b_prev,$b_main,$b_next]);
									}
									else
									{
										array_push($buttons,[$b_main,$b_next]);
									}
									
								}
								else
								{
									$buttons = getKbd_unsub(8*$idx,count($user_data),$user_data);
									array_push($buttons,[getBtn('Отписаться от всего', COLOR_NEGATIVE,'UNSUBS_ALL')]);
									array_push($buttons,[$b_prev,$b_main]);
								}
								$kbd = [
									'one_time' => false,
									'buttons' => $buttons
								];
							}
						}
						/*3-й уровень кнопок:*/
						else{
							$msg = "Список подкатегорий в $key[0].".$payload[$key[0]].".\nНажмите для чтобы подписаться.\n";
							$keys_3 = $array[$key[0]][$payload[$key[0]]];
							myLog("Keys3: ".json_encode($keys_3,JSON_UNESCAPED_UNICODE));

							$kbd = get_Butt_level(3,$keys_3,$payload);
							myLog("CHECK THIS OUT: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
						}
					}
				}
				else/* Второй уровень*/
				{
					$msg = "Список подкатегорий в $payload.\nНажмите для открытия подкатегорий.\n";
					$keys_2 = array_keys($array[$payload]);
					
					myLog("Keys2: ".json_encode($keys_2,JSON_UNESCAPED_UNICODE));

					$kbd = get_Butt_level(2,$keys_2,$payload);
					myLog("CHECK THIS OUT: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
				}
		}
		try {
			if ($msg !== null) {
				myLog("kbd: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
				$response = $vk->messages()->send(VK_TOKEN, [
					'peer_id' => $userId,
					'message' => $msg,
					'keyboard' => json_encode($kbd, JSON_UNESCAPED_UNICODE),
					'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)
				]);
				myLog("response: ".json_encode($response,JSON_UNESCAPED_UNICODE));
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