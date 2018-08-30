<?php

function connect_db() {
	$url=parse_url(getenv("CLEARDB_DATABASE_URL"));

    $server = $url["host"];
    $username = $url["user"];
    $password = $url["pass"];
    $db = substr($url["path"],1);
	myLog("Sever: $server User: $username Pas: $password db: $db ");

    $link = mysqli_connect($server, $username, $password,$db);
	if (!$link) {
		myLog( "Ошибка: Невозможно установить соединение с MySQL." . PHP_EOL);
		myLog( "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL);
		myLog( "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL);
		exit;
	}

	myLog( "Соединение с MySQL установлено!" . PHP_EOL);
	myLog( "Информация о сервере: " . mysqli_get_host_info($link) . PHP_EOL);
	
	if ($link->connect_errno) {
		myLog("Не удалось подключиться к MySQL: (" . $link->connect_errno . ") " . $link->connect_error);
	}
	$result = mysqli_query($link, "SHOW tables");
	$res = mysqli_fetch_all($result);
	myLog("res: ".json_encode($res,JSON_UNESCAPED_UNICODE));
	mysqli_free_result($result);
	return $link;
    //mysqli_select_db($db);
}

function add_sub($mysqli,$userId,$str) {
	$query = mysqli_query($mysqli,"INSERT INTO user_subs (userid,category) VALUES('$userId','$str')"); 
	if(mysqli_error($mysqli)){
		myLog("error: ".mysqli_error($mysqli));
	}
	if (!mysqli_commit($mysqli)) {
		print("Не удалось зафиксировать транзакцию\n");
	}
	return $query;
}

function drop_table($mysqli,$table) {
	$result = mysqli_query($mysqli,"DROP TABLE $table"); 
	myLog("error".mysqli_error($mysqli));
	return $result;
}
function add_to_db($mysqli,$userId,$str) {
	$data = read_db($mysqli,$userId);
	$length = count($data);
	myLog("data_db: ".json_encode($data,JSON_UNESCAPED_UNICODE));
	for($i=0;$i<$length;++$i)
	{
		$user_data = $data[$i];
		myLog("us_db: $user_data str: $str strpos:".strpos($user_data,$str));
		/*Если наша категория является подкатегорией, то есть родительская входит в неё в начало*/
		if(strpos($str,$user_data) === 0)
		{
			return 'Вы уже пописаны на эту категорию или на родительскую категорию';
		}
		/*Если наша категория является родительской, то мы убираем всех её детей и добавляем её*/
		if(strpos($user_data,$str) === 0)
		{
			$r = delete_from_db($mysqli, $userId, $user_data);	// Удалить подписку
			myLog("rrrr: ".json_encode($r,JSON_UNESCAPED_UNICODE));
		}
	}
	add_sub($mysqli,$userId,$str);
	return $msg = "Вы успешно подписались на $str";//.$payload[$key[0]][$keys[0]];
}

function read_db($mysqli,$userId=null) {
	$query;
	$result = [];
	if(is_null($userId))
	{
		$query = mysqli_query($mysqli,"SELECT * FROM user_subs");
		$res = mysqli_fetch_all($query);
		myLog("res_test: ".json_encode($res,JSON_UNESCAPED_UNICODE));
		foreach( $res as $key  ){
			$result[$key[0]][] = $key[1];
		}
	}
	else 
	{
		$query = mysqli_query($mysqli,"SELECT category FROM user_subs WHERE userid = '$userId'");//"SELECT * FROM 'user_subs'");
		$res = mysqli_fetch_all($query);
		myLog("res_test: ".json_encode($res,JSON_UNESCAPED_UNICODE));
		foreach( $res as $key  ){
			$result[] = $key[0];
		}		
	}
	mysqli_free_result($query);
	return $result;
}

function delete_from_db($mysqli, $userId = null, $str = null) {
	$result;
	if(is_null($userId))
	{
		$msg = "Таблица очищена!";
		$result = mysqli_query($mysqli,"DELETE FROM user_subs");
	}
	else 
	{
		if(!is_null($str))
		{
			$msg = "Вы успешно отписались от  $str";
			$result = mysqli_query($mysqli,"DELETE FROM user_subs WHERE userid = '$userId' AND category='$str'");//"SELECT * FROM 'user_subs'"); 
		}
		else
		{
			$msg = "Все подписки отменены";
			$result = mysqli_query($mysqli,"DELETE FROM user_subs WHERE userid = '$userId'");//"SELECT * FROM 'user_subs'"); 
		}
	
	}
	if (!mysqli_commit($mysqli)) {
		print("Не удалось зафиксировать транзакцию\n");
	}
	myLog("del? $result");
	return $msg;
}

function read_XLS($path) {
	/*--Парсим xls с категориями--*/
	$xls = PHPExcel_IOFactory::load($path);

	// Первый лист
	$xls->setActiveSheetIndex(0);
	$sheet = $xls->getActiveSheet();
	return $sheet->toArray();
}

function getBtn($label, $color = COLOR_DEFAULT, $payload = '') {
	$MAX_LENGHT = mb_strlen('Список подкатегорий 1-го уровня, нажмите','UTF-8');
	myLog("MAX: $MAX_LENGHT");
	if(mb_strlen($label)>$MAX_LENGHT)
	{
		$start = $MAX_LENGHT/2 - 8;
		$end = $MAX_LENGHT/2 -3;// + strlen($label)%2; //если нечетное прибавляем 1, иначе 0
		$first_part = mb_substr ($label,0,$start,"utf-8");
		$sec_part = mb_substr ($label,-($MAX_LENGHT-$end),null,"utf-8");
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

function get_Buttons_unsub($start, $end, $keys, $CMD = CMD_UNSUBS) {
	$buttons = [];
	/*for($i=$start;$i<$end;++$i) {
		$key = $keys[$i];
		array_push($buttons,[getBtn($key, COLOR_DEFAULT,[$CMD=>'_'.$i])]);

	}*/
	foreach($keys as $key){
		array_push($buttons,[getBtn($key, COLOR_DEFAULT,[$CMD=>'_'.$key])]);
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
	//$key = array_keys($payload);
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
			$key = array_keys($payload);
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

function get_Kbd_unsub($mysqli,$userId) {
	$user_data = read_db($mysqli,$userId);
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

function get_Kbd_feedback($userId) {
	$user_data = read_admin_data()[$userId];
	myLog("userdata: ".json_encode($user_data,JSON_UNESCAPED_UNICODE));
	if($user_data == [])
	{
		$kbd = null;
	}
	else
	{
		if(count($user_data)<10)
		{
			$buttons = get_Buttons_unsub(0,count($user_data),$user_data,CMD_FEEDBACK);
			array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
		}
		else
		{
			$buttons = get_Buttons_unsub(0,9,$user_data,CMD_FEEDBACK);
			array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('На след стр. -->', COLOR_POSITIVE,[CMD_FEEDBACK=>1])]);//[$k[0]=>[$prev[$k[0]]=>$key]]
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
			return 'Вы уже пописаны на эту категорию или на родительскую категорию';
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

function sendMsg($vk,$userId,$msg,$kbd = null,$forward_message = null) {
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
			elseif($forward_message !== null)
			{
				$response = $vk->messages()->send(VK_TOKEN, [
					'peer_id' => $userId,
					'message' => $msg,
					'forward_messages' => $forward_message
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

function is_admin($vk,$group_id,$userId) {
	/*$response = $vk->groups()->getById(VK_TOKEN, [
				'group_id' => $group_id,
				'fields' => 'is_admin'
			]);
	myLog("response:". json_encode($response,JSON_UNESCAPED_UNICODE));*/
	$admins = getAdmins($vk,$group_id)["items"];
	foreach($admins as $admin)
	{
		if($admin["id"]==$userId) return true;
	}
	//myLog("test: $test");
	return false;//$response["is_admin"];
}

function read_admin_data() {
	$file = file_get_contents(__DIR__ . '/admin_data.json');  // Открыть файл data.json
	myLog("file_admin: $file");
	$data = json_decode($file,TRUE);        // Декодировать в массив 								   
	return $data;
}

function add_to_admin_file($str, $userId,$adminId) {
	$data = read_admin_data();
	$data[$adminId][]=[$userId=>"$str"];	// Добавить обращение
	myLog("data: ".json_encode($data,JSON_UNESCAPED_UNICODE));
	file_put_contents(__DIR__ . '/admin_data.json',json_encode($data,JSON_UNESCAPED_UNICODE));  // Перекодировать в формат и записать в файл.
}

function userInfo($vk,$userId) {
	$user_info = $vk->users()->get(VK_TOKEN,['user_ids'=>$userId]);
	myLog("user_info: ".json_encode($user_info,JSON_UNESCAPED_UNICODE));
	return $user_info[0]['first_name']." ".$user_info[0]['last_name'];
}

function compare($a,$b) {
	myLog("\nleft: $a \nright: $b");
	if(strpos($a,$b) === 0) return 0;
	else substr_compare($a,$b,0);
}

function intersect($keys,$subs) {
	$array = [];
	foreach($keys as $key) {
		foreach($subs as $sub) {
			if(strpos($key,$sub) === 0) {
				array_push($array,$key);
			}	
		}
	}
	return $array;
}

function send_subs($vk,$user,$subs,$keys,$update) {
	myLog("user: $user subs: ".json_encode($subs,JSON_UNESCAPED_UNICODE));
	myLog("user: $user keys: ".json_encode($keys,JSON_UNESCAPED_UNICODE));
	/*Ищу вхождение моих подписок в массиве новой информации*/
	$intersec = intersect($keys,$subs);//array_uintersect($keys,$subs,"compare");
	foreach($intersec as $sub)
	{	
		myLog("sub: $sub ");
		myLog("update_arr: ".json_encode($update[$sub],JSON_UNESCAPED_UNICODE));
		foreach($update[$sub] as $num=>$status)
		{
			$msg = "Информация о торгах:\nНомер: $num\nКатегория: $sub\nСтатус: $status";
			sendMsg($vk,$user,$msg);
		}
	}
	myLog("intersec: ".json_encode($intersec,JSON_UNESCAPED_UNICODE));
}

function send_user_subs($vk,$userId){
	$path = __DIR__ . '/test.xlsx';
	$cat_array = read_XLS($path);
		
	/*--Создаём ассоц. массив--*/
	$upd_array = array();
	for($i=1;$i<count($cat_array);++$i) {
		$value = $cat_array[$i];
		$upd_array[$value[6]][$value[0]] = $value[5]; //в категории создаём массивы асоц номер-статус
	}

	$keys = array_keys($upd_array);
	$data = read_file();
	send_subs($vk,$userId,$data[$userId],$keys,$upd_array);
}
?>
