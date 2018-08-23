<?php

function get_Butt_level($lvl,$keys,$payload)
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
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
			}
			else
			{
				$buttons = getKbd(0,9,$keys);
				array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('Далее-->', COLOR_POSITIVE,CMD_NEXT)]);
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
				$buttons = getKbd(0,count($keys),$keys,$payload);//count($keys_2)
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,CMD_BACK),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
			}
			else
			{
				$buttons = getKbd(0,7,$keys,$payload);//count($keys_2)
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$payload=>'SUBS_ALL'])]);
				array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('На след стр. -->', COLOR_POSITIVE,[$payload=>CMD_NEXT])]);
				array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,CMD_BACK)]);
			}
			break;
		case 3:
			if(count($keys)<9)
			{
				$buttons = getKbd(0,count($keys),$keys,$payload);
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$payload[$key[0]]=>'SUBS_ALL']])]);//[$payload=>'SA']   [$k[0]=>[$prev[$k[0]]=>$key]]
				array_push($buttons,[getBtn('<--Назад', COLOR_NEGATIVE,$key[0]),getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN)]);
			}
			else
			{
				$buttons = getKbd(0,7,$keys,$payload);//count($keys_2)
				array_push($buttons,[getBtn('Подписаться на всё', COLOR_PRIMARY,[$key[0] => [$payload[$key[0]]=>'SUBS_ALL']])]);
				array_push($buttons,[getBtn('В главное меню', COLOR_NEGATIVE,CMD_MAIN),getBtn('На след стр. -->', COLOR_POSITIVE,[$key[0]=>[$payload[$key[0]]=>CMD_NEXT]])]);//[$k[0]=>[$prev[$k[0]]=>$key]]
				array_push($buttons,[getBtn('Назад', COLOR_NEGATIVE,$key[0])]);
			}
			break;
		case 4:
			$key_2 = array_keys($payload[$key[0]]);
			break;

	}
	return $kbd = [
				'one_time' => false,
				'buttons' => $buttons
			];
}