<?php
require_once './vendor/autoload.php';
require_once __DIR__ . '/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php';
require_once __DIR__ .'/functions.php';

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

const MAX_LENGHT = 40;
const VK_TOKEN = '887f275780153f8d0a42339e542ecb1f1b6a47bce9385aea12ada07d3a459095800074da66b418d5911c9';
 
//Строка для подтверждения адреса сервера из настроек Callback API 
$confirmation_token = 'd18ce045'; 
$group_id = 169930012;

/*--Парсим xls с категориями--*/
$def_mas = read_XLS(__DIR__ . '/categories.xlsx') ;

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
$buttons = [];
$kbd = null;

switch ($type) {
	case 'message_new':
		$message = $data['object'] ?? [];
		$userId = $message['from_id'] ?? 0; //user_id
		$body = $message['body'] ?? '';
		$payload = $message['payload'] ?? '';
		$text = $message['text'] ?? '';
		
		$link = connect_db();
		$db = read_db($link);
		foreach($db as $user=>$subs)
		{
			myLog("user: $user, subs: $subs");							
		}
		myLog("db: ".json_encode($db,JSON_UNESCAPED_UNICODE));
				
		myLog("MSG: ".$body." PAYLOAD string:".$payload);
		if ($payload) {
			$payload = json_decode($payload, true);
		}
		myLog("MSG: ".$body." PAYLOAD:".$payload);
		$msg = "Список подкатегорий 1-го уровня, нажмитете для перехода во 2 уровень";
		
		switch($payload){
			case(''):
				//$kbd = null;
				/*получаем историю последних 2 сообщений*/
				$history = $vk->messages()->getHistory(VK_TOKEN, [
						'user_id' => $userId,
						'count' => 2
						//'group_id' => json_encode($kbd, JSON_UNESCAPED_UNICODE)
					]);
				$first_item = $history["items"][0];
				$sec_item = $history["items"][1];
				$text = $first_item["text"];
				if($sec_item["text"]==='Опиши и отправь мне проблему с которой ты столкнулся')
				{
					$admins = getAdmins($vk,$group_id);
					$r = rand(0,$admins["count"]-1);
					$support = $admins["items"][$r];
					$msg = "Новая заявка!Помогите пользователю!";
					//add_to_admin_file($text,$userId,$support["id"]);
					sendMsg($vk,$support["id"],$msg,null,$first_item["id"]);
					myLog("admins: ".json_encode($admins,JSON_UNESCAPED_UNICODE));
					$msg = "Отлично, теперь жди ответа, с тобой обязательно свяжутся";
					sendMsg($vk,$userId,$msg);
					
					$resp = $vk->messages()->markAsImportantConversation(VK_TOKEN, [
							'peer_id' => $userId,
							'important' => 1
						]);
					myLog("resp: ".json_encode($resp,JSON_UNESCAPED_UNICODE));
					//send_to_all_admins($vk,$admins,$msg);
				}
				$msg=null;
				break;
			case CMD_MAIN:
				$msg = "Нажмите любую кнопку";			
				$kbd = get_Kbd_level(0);
				break;
			case CMD_CAT:
				$kbd = get_Kbd_level(1,$keys_1);
				break;
			case CMD_MY:
				$my_subs = read_db($link,$userId);
				myLog("mysubs".$my_subs.json_encode($my_subs,JSON_UNESCAPED_UNICODE));
				$msg = "Список моих подписок:\n";						
				
				if($my_subs == [])
				{
					$msg = 'Нет активных подписок';
				}
				else
				{
					foreach($my_subs as $item)
					{
						$msg = $msg."-$item\n";
					}
				}
				$kbd = null;
				break;
			case CMD_NEXT:
				$kbd = get_Kbd_level(1,$keys_1,null,true);
				break;
			case CMD_UNSUBS:
				$kbd = get_Kbd_unsub($link,$userId);
				if(is_null($kbd))
				{
					$msg = 'Нет активных подписок';
				}
				else
				{
					$msg = 'Нажмите, чтобы отписаться';
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
				$msg = delete_from_db($link, $userId);
				$kbd = get_Kbd_level(0);
				break;
			case CMD_FEEDBACK:
				if(is_admin($vk,$group_id,$userId))
				{
					$msg = 'Проверьте важные сообщения сообщества!';//$kbd = get_Kbd_feedback($userId);
				}
				else
				{
					$msg = 'Опиши и отправь мне проблему с которой ты столкнулся';
					array_push($buttons,[getBtn('<-- Назад', COLOR_NEGATIVE,CMD_MAIN)]);
					$kbd = [
						'one_time' => false,
						'buttons' => $buttons
					];
				}
				/*if(is_null($kbd))
				{
					$msg = 'Нет активных заявок';
				}*/
				break;
			default:
				if(is_array($payload)){ /*Пришло сообщение от 2 или 3 уровня уровня*/
					$key = array_keys($payload);
					
					if(is_array($payload[$key[0]])) { /*Пришло сообщение от 3 уровня*/
						/*4-й уровень - подписка оформляется*/
						myLog("PAYLOAD_val_1:".json_encode($payload[$key[0]],JSON_UNESCAPED_UNICODE));
						$keys = array_keys($payload[$key[0]]);
						
						if($payload[$key[0]][$keys[0]]==CMD_NEXT)
						{
							$msg = "Список подкатегорий в $key[0].$keys[0].\nНажмите для чтобы подписаться.\n";
							$keys_3 = $array[$key[0]][$keys[0]];
							$kbd = get_Kbd_level(3,$keys_3,[$key[0]=>$keys[0]],true);
						}
						/*C 3 уровня пришла комманда SUBS_ALL*/
						elseif($payload[$key[0]][$keys[0]]== 'SUBS_ALL')
						{
							$str = "$key[0].$keys[0]";
							$msg = add_to_db($link,$userId,$str);
						}
						else{
							
							$str = "$key[0].$keys[0].".$payload[$key[0]][$keys[0]];
							$msg = add_to_db($link,$userId,$str);
						}
						
					} else { /*Пришло сообщение от 2 уровня*/
						myLog("PAYLOAD_val:".$payload[$key[0]]);
						if($payload[$key[0]]=== CMD_NEXT)
						{
							$msg = "Список подкатегорий в $key[0].\nНажмите для открытия подкатегорий.\n";
							$keys_2 = array_keys($array[$key[0]]);
							$kbd = get_Kbd_level(2,$keys_2,$key[0],true);
							myLog("TEST ".json_encode($kbd, JSON_UNESCAPED_UNICODE));
						}
						elseif($payload[$key[0]]=== 'SUBS_ALL')
						{
							$str = $key[0];
							$msg = add_to_db($link,$userId,$str);
						}
						elseif($key[0]===CMD_UNSUBS)
						{
							$s = substr($payload[$key[0]],0,1);
							myLog("s: ".$s);
							/*Удаляем конкретный элемент*/
							if($s==='_')
							{
								$s = substr($payload[$key[0]],1);
								myLog("s1: ".$s);
								$msg = delete_from_db($link, $userId, $s);
								$kbd = get_Kbd_unsub($link,$userId);
								if(is_null($kbd))
								{
									sendMsg($vk,$userId,$msg);
									$msg = "Нажмите любую кнопку";			
									$kbd = get_Kbd_level(0);
								}
							}
							/*Переход по страницами*/
							else
							{
								$user_data = read_db($link,$userId);
								$idx = $payload[$key[0]];
								$kbd = get_Kbd_unsub($link,$userId,$idx);
							}
						}
						/*3-й уровень кнопок:*/
						else{
							$msg = "Список подкатегорий в $key[0].".$payload[$key[0]].".\nНажмите для чтобы подписаться.\n";
							$keys_3 = $array[$key[0]][$payload[$key[0]]];
							/*Если нет третьего уровня то подписка осуществляется*/
							if($keys_3 == [null])//$payload=='Прочее'
							{
								$str = "$key[0].".$payload[$key[0]];
								$msg = add_to_db($link,$userId,$str);
							}
							else
							{
								$kbd = get_Kbd_level(3,$keys_3,$payload);
							}
							myLog("Keys3: ".json_encode($keys_3,JSON_UNESCAPED_UNICODE));
							myLog("CHECK THIS OUT: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
						}
					}
				}
				/* Второй уровень*/
				else
				{
					$msg = "Список подкатегорий в $payload.\nНажмите для открытия подкатегорий.\n";
					$keys_2 = array_keys($array[$payload]);
					/*Если нет 3 уровня, то подписка на 2 осуществляется*/
					if($array[$payload][$keys_2[0]] == [null])//$payload=='Прочее'
					{
						$msg = "Список подкатегорий в $payload.\nНажмите чтобы подписаться.\n";
					}
					
					$kbd = get_Kbd_level(2,$keys_2,$payload);
					
					myLog("Keys2: ".json_encode($keys_2,JSON_UNESCAPED_UNICODE));
					myLog("CHECK THIS OUT: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
				}
		}
		mysqli_close($link);
		sendMsg($vk,$userId,$msg,$kbd);
		echo  "OK";
		break;
	case 'confirmation': 
		//...отправляем строку для подтверждения 
		echo $confirmation_token; 
		break; 
}