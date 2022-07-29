<?
	namespace PickSupplierWhatPick;
	use Bitrix\Main\Diag;	
	
	//Шаблон: Забрать_Поставщик_Что забираем (с названием информации на макетах)
	class PickSupplierWhatPickClass
	{
		public function PickSupplierWhatPickFunction(&$arFields)
		{
			$taskIDNumberTemplateString = array("Забрать_"); //строка из названия задачи для которой нужно создать подзадачу 
			
			$taskTags = array("51"); //строка из названия задачи для которой нужно создать подзадачу 
			
			$taskIDGroup = 62; //ID группы для которой нужно создать подзадачу 
			$nameStrAdditionally = '(доставка на наш склад)';     //имя подзадачи текст, начало
			
			$step_id_list = 47;   //инфоблок в списках для записи задачи - чтобы подзадачи не двоились
			
			$arCo_executor = array(63, 65);     //добавляем в соисполнители
			
			$taskId = $arFields; // текущая задача
			$userId = 1;    //пользователь от имени которого будут создаваться все задачи
			
			//проверка на вход в создание задачи
			if (\CModule::IncludeModule("tasks"))
			{
				$rsTask = \CTasks::GetByID($arFields);
				if ($arTask = $rsTask->GetNext())
				{
					//echo $arTask["TITLE"];
				}
			}			
			
			$TaskName = $arTask['TITLE'];
			$arTask['TITLE'] = $arTask['TITLE'] . " " . $nameStrAdditionally;
			
			if ($arTask["STATUS"] == 5 && $arTask['GROUP_ID'] == $taskIDGroup && self::search_subproblem_ar($arTask["TAGS"][0], $taskTags) == 1 && self::search_for_occurrence_of_subproblem($arTask["TITLE"], $nameStrAdditionally) == 0 && is_null( $arTask["PARENT_ID"] ) === true) 
			{
				
				// получить имя (id)  из списка по id  задачи для текущей задачи			
				$arSelect = Array("ID", "IBLOCK_ID", "NAME");//IBLOCK_ID и ID обязательно должны быть указаны, см. описание arSelectFields выше
				$arFilter = Array("NAME"=>$taskId);
				$res = \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>50), $arSelect);
				
				while($ob = $res->GetNextElement()){ 				
					$arFields = $ob->GetFields();					
					$arFieldsNen[] = $arFields;						
					if ( $arFields ['NAME'] == $taskId) {
						$second_step_mark = $arFields['NAME'];
					}
				}
				
				if ($arTask["ID"] != $second_step_mark) //убрать повторонеие второй подзадачи при закрытии основной задачи
				{					
					//создание подзадачи
					$arTask['TITLE'] = $TaskName;
					$arTask['TITLE'] = $arTask['TITLE'] . " " . $nameStrAdditionally;											
					$arTask['ACCOMPLICES'] = array_merge($arTask['ACCOMPLICES'], $arCo_executor);		
					
					$curTask = $arTask['ID'];
					
					//определить крайний срок
					$current_date = date("Y-m-d");
					$date_dedline = date("d.m.Y H:i:s", strtotime($oldDate.'+ 2 days'));
					
					$arFields = Array(
					"TITLE" => $arTask['TITLE'],
					"DESCRIPTION" => $arTask['DESCRIPTION'],
					"RESPONSIBLE_ID" => $arTask['RESPONSIBLE_ID'],  
					"CREATED_DATE" => date('d.m.Y H:i:s'),
					"CHANGED_DATE" => date('d.m.Y H:i:s'),
					"STATUS_CHANGED_DATE" => date('d.m.Y H:i:s'),
					"START_DATE_PLAN" => date('d.m.Y H:i:s'),
					"VIEWED_DATE" => date('d.m.Y H:i:s'),
					//	"DEADLINE" => date( 'd.m.Y H:i:s', strtotime("+5 hours") ),
					"DEADLINE" => $date_dedline, //крайний срок
					//	"TAGS" => $tag_str,
					// "DEADLINE" => date("d.m.Y H:i:s",$sevenup),
					//"ALLOW_TIME_TRACKING" => 'Y',
					"CREATED_BY" => $arTask['CREATED_BY'],
					"STATUS" => 2,
					"REAL_STATUS" => 2,
					"ACCOMPLICES" => $arTask['ACCOMPLICES'],
					"GROUP_ID" => $arTask['GROUP_ID'],
					"DESCRIPTION_IN_BBCODE" => 'Y',
					//	"PRIORITY" => 2,
					"PARENT_ID" => $curTask,
					);
					$obTask = new \CTasks;						
					$ID = $obTask->Add($arFields);
					$success = ($ID>0);	
					
					//записать тег в инфоблок в список						
					$el = new \CIBlockElement;
					
					$PROP = array();
					$PROP[$id_properties_task] = $taskId;  // ИД задачи
					//$PROP[$id_property_second_step] = $tag_str;        // уникальная метка второго шага, тег
					
					$arLoadProductArray = Array(
					"MODIFIED_BY"    => $userId, // элемент изменен текущим пользователем
					"IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
					"IBLOCK_ID"      => $step_id_list,				
					"NAME"           => $taskId,
					"ACTIVE"         => "Y",            // активен
					);
					
					$PRODUCT_ID = $el->Add($arLoadProductArray);
					
					//Добавить соисполнителя
					\CTasks::AddAccomplices($arFields, $arTask['ACCOMPLICES']);	// после этого потом запись в файл не работает
				}
			}
		}
		
		//ФУНКЦИИ дополнительные	
		private function search_for_occurrence_of_subproblem ($task_title, $messages) {	
			foreach ($messages as $key => $message)
			{			
				if ( strpos($task_title, $message) !== false ) { // если вхождение найдено то 1
					return 1;	
				}
				else {
					return 0;   //если вхождение не найдено то 0
				}
			}		
		}
		
		//шаг 2 вывод нормального текста в описании
		private function replaceBBCode($text_post) {
			
			$str_search = array(
			"<br />",
			"<b>\\1</b>",
			"<i>\\1</i>",
			"<span style='text-decoration:underline'>\\1</span>",
			"<code class='code'>\\1</code>",
			"<table width = '95%'><tr><td>Цитата</td></tr><tr><td class='quote'>\\1</td></tr></table>",
			"<a href='\\1'>\\2</a>",
			"<a href='\\1'>\\1</a>",
			"<img src='\\1' alt = 'Изображение' />",
			"<span style='font-size:\\1%'>\\2</span>",
			"<span style='color:\\1'>\\2</span>",
			"<ul>\\1</ul>",
			"<ol>\\1</ol>",
			"<li>\\1</li>"
			);
			
			$str_replace= array(
			"#\\\n#is",
			"#\[b\](.+?)\[\/b\]#is",
			"#\[i\](.+?)\[\/i\]#is",
			"#\[u\](.+?)\[\/u\]#is",
			"#\[code\](.+?)\[\/code\]#is",
			"#\[quote\](.+?)\[\/quote\]#is",
			"#\[url=(.+?)\](.+?)\[\/url\]#is",
			"#\[url\](.+?)\[\/url\]#is",
			"#\[img\](.+?)\[\/img\]#is",
			"#\[size=(.+?)\](.+?)\[\/size\]#is",
			"#\[color=(.+?)\](.+?)\[\/color\]#is",
			"#\[list\](.+?)\[\/list\]#is",
			"#\[listn](.+?)\[\/listn\]#is",
			"#\[\*\](.+?)\[\/\*\]#"
			);
			
			return preg_replace($str_search, $str_replace, $text_post);
		}
		
		private function search_subproblem_ar ($task_title, $messages) {				
			// $task_title -  строка с котрой сравниваем - имя задачи
			// $messages - массив с строками для поиска вхождения	
			$sn=count($messages);		 
			// Diag\Debug::writeToFile($sn, $varName = "$sn", $fileName = "logText.log");	
			for ($i = 0; $i < $sn; $i++) {			
   				if ( $task_title == $messages[$i] ) {
					return 1;	//есть вхождение
				}								
			}
			
			return 0;  // нет вхождения
		}
	}
	
?>	