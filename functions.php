<?php

function getBtn($label, $color = COLOR_DEFAULT, $payload = '') {
	$MAX_LENGHT = mb_strlen('Список подкатегорий 1-го уровня, нажмите','UTF-8');
	myLog("MAX: $MAX_LENGHT");
	if(mb_strlen($label)>$MAX_LENGHT)
	{
		$start = $MAX_LENGHT/2 - 8;
		$end = $MAX_LENGHT/2 -3;// + strlen($label)%2; //если нечетное прибавляем 1, иначе 0
		$first_part = mb_substr ($label,0,$start,"utf-8");
		$sec_part = mb_substr ($label,-($MAX_LENGHT-$end),null,"utf-8");
		$one_byty_symb = substr_count($sec_part, '.')+substr_count($sec_part, '(')+substr_count($sec_part, ')');
		myLog("Lab bef: $label count:".mb_strlen($label,'UTF-8'));
		$label = $first_part.".. ..".$sec_part;
		myLog("Lab aft: $label count:".mb_strlen($label,'UTF-8'));
	}
	return [
        'action' => [
            'type' => 'text',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'label' => $label
        ],
        'color' => $color
    ];
}

function get_Buttons_unsub($start, $end, $keys) {
	$buttons = [];
	for($i=$start;$i<$end;++$i) {
		$key = $keys[$i];
		array_push($buttons,[getBtn($key, COLOR_DEFAULT,[CMD_UNSUBS=>'_'.$i])]);

	}
	myLog("buttons: ".json_encode($buttons,JSON_UNESCAPED_UNICODE));
	return $buttons;
}

function get_Buttons($start, $end, $keys, $prev = null) {
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

function get_Kbd_level($lvl,$keys = null,$payload = null,$CMD_NEXT = false) {	
	$key = array_keys($payload);
	$buttons = [];
	$b_main = getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN);
	switch($lvl)
	{
		case 0:
			array_push($buttons,[getBtn('Подписаться на категории', COLOR_DEFAULT,CMD_CAT)]);
			array_push($buttons,[getBtn('Мои подписки', COLOR_DEFAULT,CMD_MY)]);
			array_push($buttons,[getBtn('Отписаться', COLOR_DEFAULT,CMD_UNSUBS)]);
			array_push($buttons,[getBtn('Обратная связь', COLOR_DEFAULT,CMD_FEEDBACK)]);
			break;
		case 1:
			if($CMD_NEXT)
			{
				$buttons = get_Buttons(10,count($keys),$keys);
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_CAT),$b_main]);
			}
			else
			{
				$buttons = get_Buttons(0,9,$keys);
				array_push($buttons,[$b_main,getBtn('Далее-->', COLOR_POSITIVE,CMD_NEXT)]);
			}
			break;
		case 2:
			/*Если меньше 9, то выводим все + 2 кнопки(подписатся на всё и назад/в главное меню)*/
			if(count($keys)<9)
			{
				$buttons = get_Buttons(0,count($keys),$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_CAT),$b_main]);
			}
			elseif($CMD_NEXT)
			{
				$buttons = get_Buttons(7,count($keys),$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
				array_push($buttons,[getBtn('<-- На пред. стр.', COLOR_NEGATIVE,$payload),$b_main]);
				array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,CMD_CAT)]);
			}
			else
			{
				$buttons = get_Buttons(0,7,$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
				array_push($buttons,[$b_main,getBtn('На след стр. -->', COLOR_POSITIVE,[$payload=>CMD_NEXT])]);
				array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,CMD_CAT)]);
			}
			break;
		case 3:
			if(count($keys)<9)
			{
				$buttons = get_Buttons(0,count($keys),$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$payload[$key[0]]=>'SUBS_ALL']])]);//[$payload=>'SA']   [$k[0]=>[$prev[$k[0]]=>$key]]
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,$key[0]),$b_main]);
			}
			elseif($CMD_NEXT)
			{
				$buttons = get_Buttons(7,count($keys),$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$payload[$key[0]]=>'SUBS_ALL']])]);
				array_push($buttons,[getBtn('<-- На пред. стр.', COLOR_NEGATIVE,$payload),$b_main]);
				array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,$key[0])]);
			}
			else
			{
				$buttons = get_Buttons(0,7,$keys,$payload);
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

function get_Kbd_unsub($userId) {
	$data = read_file();
	$user_data = $data[$userId];
	myLog("userdata: ".json_encode($user_data,JSON_UNESCAPED_UNICODE));
	if($user_data == [])
	{
		$msg = 'Нет активных подписок';
		$kbd = null;
	}
	else
	{
		if(count($user_data)<9)
		{
			$buttons = get_Buttons_unsub(0,count($user_data),$user_data);
			array_push($buttons,[getBtn('Отписаться от всего', COLOR_NEGATIVE,CMD_UNSUBS_ALL)]);
			array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
		}
		else
		{
			$buttons = get_Buttons_unsub(0,8,$user_data);
			array_push($buttons,[getBtn('Отписаться от всего', COLOR_NEGATIVE,CMD_UNSUBS_ALL)]);
			array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('На след стр. -->', COLOR_POSITIVE,[CMD_UNSUBS=>1])]);//[$k[0]=>[$prev[$k[0]]=>$key]]
		}
		myLog("buttons: ".json_encode($buttons,JSON_UNESCAPED_UNICODE));
		$kbd = [
			'one_time' => false,
			'buttons' => $buttons
		];
	}
	return $kbd;
}

function add_to_file($str, $userId) {
	$data = read_file();
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
	return $msg = "Вы успешно подписались на $str";//.$payload[$key[0]][$keys[0]];
}

function read_file() {
	$file = file_get_contents(__DIR__ . '/data.json');  // Открыть файл data.json
	myLog("file: $file");
	$data = json_decode($file,TRUE);        // Декодировать в массив 								   
	return $data;
}

function delete_from_file($idx, $userId) {
	$data = read_file();		   
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

function sendMsg($vk,$userId,$msg,$kbd = null) {
	try {
		if ($msg !== null) {
			myLog("kbd: ".json_encode($kbd,JSON_UNESCAPED_UNICODE));
			if($kbd !== null)
			{
				$response = $vk->messages()->send(VK_TOKEN, [
					'peer_id' => $userId,
					'message' => $msg,
					'keyboard' => json_encode($kbd, JSON_UNESCAPED_UNICODE)
				]);
			}
			else
			{
				$response = $vk->messages()->send(VK_TOKEN, [
					'peer_id' => $userId,
					'message' => $msg
				]);
			}
		}
	} catch (\Exception $e) {
		myLog( $e->getCode().' '.$e->getMessage() );
	}
}

function getAdmins($vk,$group_id) {
	try {
		$response = $vk->groups()->getMembers(VK_TOKEN, [
			'filter' => 'managers',
			'group_id' => $group_id
			]);
	} catch (\Exception $e) {
		myLog( $e->getCode().' '.$e->getMessage() );
	}
	return $response;
}

?>
