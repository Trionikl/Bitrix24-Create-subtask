<?
	namespace TasksImprovements;
	use Bitrix\Main\Diag;	
	
	//добавить нвого пользователя в нужные групповые чаты
	class AutomaticSettingSubtaskCompletedClass
	{		
		// создаем обработчик события "OnAfterUserAdd"
		public function AutomaticSettingSubtaskCompleted(&$arFields)
		{
			$task_setter = 1;  //постановщик задачи - не нужно - убрал из проверки
		    $logistician_employee = 264; //id сотрудника логиста Исполнителя 
			$co_executor_step_2 = array(195); //id соисполнителя шага два
			
			$step_id_list=46;  //id списка для записи второго шага   
			$id_properties_task=148; //id свойства задача
			$id_property_second_step=149; //id свойства второго шага
			
			$taskNumber=$arFields; // родительская задача
			$subtask_name_text = 'Зафиксировать себестоимость: ';     //имя подзадачи текст, начало
			$subtask_name_text_ASystem = 'Занести себестоимость доставки в ASystem: ';  //имя подзадачи сформированной логистом текст, начало
			
		    $nameStrAdditionally = array("(подтверждение факта доставки)"); //для формирования названия подзадачи исключить формирование 2 подзадачи у Заказ такси (подтверждение факта доставки)
			
			$taskIDNumberTemplate = array(56,57,58); //тег шаблона задачи для которой нужно создать подзадачу Шаг 1
			
			$taskIDNumberTemplateString = array("58"); //"Заказ ТК/КС: Клиент" строка из названия задачи для которой нужно создать подзадачу 
			
			$task_Taxi_KS_order = array("57"); //Заказ такси/КС - шаблон
			
			$taskIDGroup = array(37); //ID группы для которой нужно создать подзадачу 
			
			$Yakovleva_Executor_Assistant_Logist = 71;    // Ответственный, исполнитель. ID Анна 
			$Lee_Co_executor_Assistant_Logistician = array(31, 195);     // ID Ли Соисполнитель Помошник Логиста - Ирина, Дмитрий
			$auditors = array(23); //массив идентификаторов наблюдателей
			
			$strRequestsMessage = "Коментарий к задаче: ";  //коментарий к предыдущей задаче
			$strRequestsMessage2 = $strRequestsMessage;  //коментарий к предыдущей задаче
			$arrRemoveFROMComments = array("Задача завершена", "Крайний срок изменен на:", "задача возвращена в работу.", "Задача с контролем после завершения.", "вы назначены", "Необходимо указать крайний срок,", "Задача возвращена в работу.");    //убрать из комментариев
			
			$responsible_to_put_Starshinov = 31; // Ирина. Надо в шаблоне "Заказ такси/КС Мск" в дочерней задаче первого уровня ответственной поставить Ирину, а текущего исполнителя в соисполнители.
			
			// ini_set('error_reporting', E_ALL);
			// ini_set('display_errors', 1);
			// ini_set('display_startup_errors', 1);
			
			global $USER;
			
			//$arTask [STATUS] => 5 - задача закрыта
			if (\CModule::IncludeModule("tasks"))
			{
				$rsTask = \CTasks::GetByID($arFields);
				if ($arTask = $rsTask->GetNext())
				{
					//echo $arTask["TITLE"];
				}
			}
			
			$tags_id_template = $arTask['TAGS'][0];
			//ПРОВЕРКА ЯВЛЯЕТСЯ ЛИ СОЗДАВШИЙ УЧАСТНИКОМ ГРУППЫ  ================				
			$arParamsTask = [
			'taskId' => $taskNumber,
			'select' => ['GROUP_ID', 'CREATED_BY', 'TITLE']			
			];
			$arRequestsResult = \CRest::call('tasks.task.get', $arParamsTask);
			
			//Diag\Debug::dumpToFile(array_search($arRequestsResult["result"]["task"]["group"]["id"], $taskIDGroup), $varName = 'Поиск в массиве', $fileName = 'logText.log');
		
			// ПЕРВЫЙ ШАГ
			if( $arTask['STATUS'] == 5 && array_search($arRequestsResult["result"]["task"]["group"]["id"], $taskIDGroup) !== false && self::search_for_occurrence_of_subproblem($arRequestsResult ["result"]["task"]["title"], $nameStrAdditionally) == 0 && self::search_subproblem_ar155614($tags_id_template, $taskIDNumberTemplate) == 1)  
			{			
			
				if (self::search_for_occurrence_of_subproblem($tags_id_template, $taskIDNumberTemplateString) == 0) 
				{
					
					// получить значение метки из списка по имени родительской задачи для текущей задачи			
					$arSelect = Array("ID", "IBLOCK_ID", "NAME");//IBLOCK_ID и ID обязательно должны быть указаны, см. описание arSelectFields выше
					$arFilter = Array("NAME"=>$taskNumber);
					$res = \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>5), $arSelect);
					
					while($ob = $res->GetNextElement()){ 				
						$arFieldsName = $ob->GetFields();
						//$arProps = $ob->GetProperties();					
						//$arFieldsNen[] = $arFields;					
						if ( $arFieldsName ['NAME'] == $taskNumber) {
							//$arFieldsNen[] = $arFields;				
							//$arPropsNen[] = $arProps;
							$second_step_mark = $arFieldsName['NAME'];
						}
						else {
							$second_step_mark=0;
						}
					}
					
					//Diag\Debug::writeToFile($second_step_mark, $varName = "second_step_mark", $fileName = "logText.log");
					
					//убрать дубли подзадач
					if ($second_step_mark != $taskNumber) {					
						unset($second_step_mark);				
						
						//	Diag\Debug::writeToFile("вход в первое условие", $varName = "arFields Первый этап", $fileName = "logText.log");
						//чтобы задачи не двоились - не создавать вторую подзадачу
						// Выбираем все задачи пользователя с ID = $logistician_employee
						if (\CModule::IncludeModule("tasks"))
						{
							$res = \CTasks::GetList(
							Array("TITLE" => "ASC"), 
							Array("RESPONSIBLE_ID" => $logistician_employee, 'PARENT_ID' => $taskNumber, 'STATUS' => 2),
							Array("TITLE")
							);
							
							while ($arTaskLogist = $res->GetNext())		
							{
								//echo "Task name: ".$arTask["TITLE"]. "\n";
								
								$arTaskRes[]=$arTaskLogist["TITLE"];
							}						
						}
						
						//добавить подзадачу, если её нет
						if (is_null($arTaskRes) === true) 			
						{						
							$curTask = $arFields; //id созданной ранее задачи с помощью активити "Задача"
											
							//получить описание задачи 
							if (\CModule::IncludeModule("tasks"))
							{
								$rsTask = \CTasks::GetByID($curTask);
								if ($arTask = $rsTask->GetNext())
								{
									//$arTaskDescription = $arTask['DESCRIPTION'];
									$arTaskDescription = $arTask;
								}					
							}
							
							$task = new \Bitrix\Tasks\Item\Task($curTask); // получение сущности с выбранным id
							$arTaskDescriptionTask = $task->getData();
							
							//Получить комментарии задачи				
							\Bitrix\Main\Loader::IncludeModule('tasks');
							\Bitrix\Main\Loader::IncludeModule('forum');
							
							/**
								* @var int ID задачи
							*/
							$taskId = $arFields;
							
							/**
								* @var int ID форума
							*/
							$forumId = \Bitrix\Tasks\Integration\Forum\Task\Comment::getForumId();					
							
							$messages = \Bitrix\Forum\MessageTable::getList([
							'select' => ['ID','POST_MESSAGE','AUTHOR_ID', 'AUTHOR_NAME'],
							'filter' => [
							'REAL_TOPIC.FORUM_ID' => $forumId,
							'REAL_TOPIC.XML_ID'   => 'TASK_'.$taskId,
							'!=PARAM1' => 'TK',
							],
							'runtime' => [
							new \Bitrix\Main\Entity\ReferenceField(
							'REAL_TOPIC',
							'\Bitrix\Forum\TopicTable',
							array('=this.TOPIC_ID' => 'ref.ID')
							)
							
							],
							]);
							
							
							foreach ($messages as $key => $message)
							{
								
								$str=0;
								//поиск вхождения
								//Diag\Debug::writeToFile($arrRemoveFROMComments , $varName = "arrRemoveFROMComments", $fileName = "logText.log");
								foreach ($arrRemoveFROMComments as $ksn => $value)
								{		
									
									if ( strpos($message['POST_MESSAGE'], $value ) !== false ) {
										//	Diag\Debug::writeToFile($message['POST_MESSAGE'] , $varName = "message['POST_MESSAGE']", $fileName = "logText.log");	
										//	Diag\Debug::writeToFile($value , $varName = "value", $fileName = "logText.log");
										$str=1;
										break 1;
									}							
								}
								
								if ( $str == 0 ) 
								{
									$arRequestsMessage[$key]['ID'] = $message['ID'];
									$arRequestsMessage[$key]['POST_MESSAGE'] = $message['POST_MESSAGE'];
									$arRequestsMessage[$key]['AUTHOR_ID'] = $message['AUTHOR_ID'];
									$arRequestsMessage[$key]['AUTHOR_NAME'] = $message['AUTHOR_NAME'];						
									$strMessage = $strMessage . "<br/>" . "<b>". $message['AUTHOR_NAME'] . "</b><br/>" . $message['POST_MESSAGE'];
								}
							} 
							
							$strRequestsMessage = "<br/>" . $strRequestsMessage . $strMessage;
							
							// Название задачи; Описание; Постановщик ID; Ответственный ID
							// Задача 1; Выполнить задачу 1; 1546; 25
							
							$TASKS = $subtask_name_text . $arTask["TITLE"] . ';' . $arTaskDescription['DESCRIPTION'] . $strRequestsMessage . ';' . $logistician_employee . ';' . $logistician_employee;				
							
							$TASKS = explode(";", $TASKS);	
							//поменять [] на <>
							//$TASKS[1] = str_replace('[','<', $TASKS[1]);
							//$TASKS[1] = str_replace(']','>', $TASKS[1]);
							$tag_str = uniqid('task-', true) . "-2";
							
							$arTask['TITLE'] = $TASKS[0];
							$arTask['DESC'] = $TASKS[1];
							$arTask['CREATED_BY'] = $TASKS[2];
							$arTask['RESPONSIBLE_ID'] = $TASKS[3];	
							
							// Ирина. Надо в шаблоне "Заказ такси/КС Мск" в дочерней задаче первого уровня ответственной поставить Ирину, а текущего исполнителя в соисполнители.
							if (self::search_for_occurrence_of_subproblem($tags_id_template, $task_Taxi_KS_order) == 1) {
								//Diag\Debug::writeToFile($arTask['TITLE'], $varName = 'arTask["TITLE"]', $fileName = "logText.log");
								$co_executor_step_2[]=$arTask['RESPONSIBLE_ID']; //добавляю в соисполнители ответственного			
								$arTask['RESPONSIBLE_ID']= $responsible_to_put_Starshinov; //меняю ответственного			
							}		
							
							//определить крайний срок
							$current_date = date("Y-m-d");
							$date_dedline = date("d.m.Y H:i:s", strtotime($oldDate.'+ 2 days'));
							
							$arFields = Array(
							"TITLE" => $arTask['TITLE'],
							"DESCRIPTION" => $arTask['DESC'],
							"RESPONSIBLE_ID" => $arTask['RESPONSIBLE_ID'],  
							"CREATED_DATE" => date('d.m.Y H:i:s'),
							"CHANGED_DATE" => date('d.m.Y H:i:s'),
							"STATUS_CHANGED_DATE" => date('d.m.Y H:i:s'),
							"START_DATE_PLAN" => date('d.m.Y H:i:s'),
							"VIEWED_DATE" => date('d.m.Y H:i:s'),
							"TAGS" => $tag_str,
							// "DEADLINE" => date("d.m.Y H:i:s",$sevenup),
							"DEADLINE" => $date_dedline, //крайний срок
							//"ALLOW_TIME_TRACKING" => 'Y',
							"CREATED_BY" => $arTask['CREATED_BY'],
							"STATUS" => 2,
							"REAL_STATUS" => 2,
							"ACCOMPLICES" => $co_executor_step_2,
							"GROUP_ID" => $arRequestsResult["result"]["task"]["group"]["id"],
							"DESCRIPTION_IN_BBCODE" => 'Y',
							//	"PRIORITY" => 2,
							"PARENT_ID" => $curTask,
							);
							$obTask = new \CTasks;						
							$ID = $obTask->Add($arFields);
							$success = ($ID>0);							
							
							//Diag\Debug::writeToFile("ХВОСТ", $varName = "ХВОСТ", $fileName = "logText.log");		
							
							//записать тег в инфоблок в список						
							$el = new \CIBlockElement;
							
							$PROP = array();
							$PROP[$id_properties_task] = $taskNumber;  // ИД задачи
							$PROP[$id_property_second_step] = $tag_str;        // уникальная метка второго шага, тег
							
							$arLoadProductArray = Array(
							"MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
							"IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
							"IBLOCK_ID"      => $step_id_list,
							"PROPERTY_VALUES"=> $PROP,
							"NAME"           => $taskNumber,
							"ACTIVE"         => "Y",            // активен
							);
							
							$PRODUCT_ID = $el->Add($arLoadProductArray);
												
							//Добавить соисполнителя на 2 шаг
							\CTasks::AddAccomplices($arFields, $co_executor_step_2);	// после этого потом запись в файл не работает
						}
					}
				}	
			}		
			
			//ВТОРОЙ ШАГ - ПРОВЕРКА ЕСТЬ ЛИ ТЕГ		
		
			if (is_null($arTask['TAGS'][0]) == false && self::search_for_occurrence_of_subproblem($tags_id_template, $taskIDNumberTemplateString) == 0 && self::search_for_occurrence_of_subproblem($arRequestsResult ["result"]["task"]["title"], $nameStrAdditionally) == 0) 
			{		
						
				//ПРОВЕРКА ЯВЛЯЕТСЯ ЛИ СОЗДАВШИЙ УЧАСТНИКОМ ГРУППЫ  ================				
				$arParamsTask = [
				'taskId' => $taskNumber,
				'select' => ['CHANGED_BY', 'GROUP_ID']			
				];
				$arRequestsResult = \CRest::call('tasks.task.get', $arParamsTask);	
							
				//поиск у задачи главной задачи - которая родителем является
				// Выбираем все задачи пользователя с ID = 2
				if (\CModule::IncludeModule("tasks"))
				{
					$res = \CTasks::GetList(
					Array("TITLE" => "ASC"), 
					Array("ID" => $taskNumber)
					);
					
					while ($arTask = $res->GetNext())
					{
						if( !is_null($arTask["PARENT_ID"]) ) {
							$parent_task_id=$arTask["PARENT_ID"];  //ид родительской задачи
						}
					}
				}
				
				//Diag\Debug::writeToFile($parent_task_id, $varName = "parent_task_id", $fileName = "logText.log");
				
				// получить значение метки из списка по имени родительской задачи для текущей задачи			
				$arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_*");//IBLOCK_ID и ID обязательно должны быть указаны, см. описание arSelectFields выше
				$arFilter = Array("NAME"=>$parent_task_id);
				$res = \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>50), $arSelect);
				
				while($ob = $res->GetNextElement()){ 				
					$arFields = $ob->GetFields();
					$arProps = $ob->GetProperties(); 
					
					$arFieldsNen[] = $arFields;	
					
					if ( $arFields ['NAME'] == $parent_task_id) {
						//$arFieldsNen[] = $arFields;				
						//$arPropsNen[] = $arProps;
						$second_step_mark = $arProps[$id_property_second_step]['VALUE'];
					}
				}
						
				//$arTask [STATUS] => 5 - задача закрыта
				if (\CModule::IncludeModule("tasks"))
				{
					$rsTask = \CTasks::GetByID($taskNumber);
					if ($arTask = $rsTask->GetNext())
					{
						//echo $arTask["TITLE"];
					}
				}		
							
				// ========= КОГДА ЛОГИСТ ЕЁ ЗАВЕРШИТ, НЕОБХОДИМО ПОРОДИТЬ ПОДЗАДАЧУ НА ПОМОЩНИКОВ =======
				if( $arTask['STATUS'] == 5 && $arTask['TAGS'][0] == $second_step_mark && $arTask['PARENT_ID'] == $parent_task_id && array_search($arRequestsResult["result"]["task"]["group"]["id"], $taskIDGroup) !== false && is_null($arTask['PARENT_ID']) == false) 
				{
									
					if (self::search_for_occurrence_of_subproblem($tags_id_template, $taskIDNumberTemplateString) == 0)		
					{
									
						//чтобы задачи не двоились - не создавать вторую подзадачу
						// Выбираем все задачи пользователя с ID = $logistician_employee
						if (\CModule::IncludeModule("tasks"))
						{
							$res = \CTasks::GetList(
							Array("TITLE" => "ASC"), 
							Array("RESPONSIBLE_ID" => $Yakovleva_Executor_Assistant_Logist, 'PARENT_ID' => $taskNumber, 'STATUS' => 2),
							Array("TITLE")
							);
							
							while ($arTaskLogist = $res->GetNext())		
							{
								//echo "Task name: ".$arTask["TITLE"]. "\n";
								
								$arTaskRes[]=$arTaskLogist["TITLE"];
							}
							
						}
						
						//поиск существования задачи в родительской подзадаче, у логиста
						if (\CModule::IncludeModule("tasks"))
						{
							$res = \CTasks::GetList(
							Array("TITLE" => "ASC"), 
							Array("RESPONSIBLE_ID" => $logistician_employee, 'PARENT_ID' => $taskNumber, 'STATUS' => 2),
							Array("TITLE")
							);
							
							while ($arTaskLogist = $res->GetNext())		
							{
								//echo "Task name: ".$arTask["TITLE"]. "\n";
								
								$arTaskResLogist[]=$arTaskLogist["TITLE"];
							}
							
						}
						//добавить подзадачу, если её нет
						if (is_null($arTaskRes) === true) {
							//провеить есть ли подздача у логиста		
							if (\CModule::IncludeModule("tasks"))
							{
								$rsTask = \CTasks::GetByID($taskNumber);
								if ($arTaskSub = $rsTask->GetNext())
								{
									//echo $arTask["TITLE"];
								}
							}
							
							if (stripos($arTaskSub["TITLE"], $subtask_name_text_ASystem) === false)
							{
								
								$arTask["TITLE"] = str_replace($subtask_name_text, '', $arTask["TITLE"] ); 
								
								$nameAdddescriptionTaskLvl1 = $arTask["TITLE"];
								
								//Diag\Debug::writeToFile($arTask["TITLE"], $varName = 'arTask["TITLE"] ВТОРОЙ этап', $fileName = "logText.log");
								
								$curTask = $arFields; //id созданной ранее задачи с помощью активити "Задача"							
								
								//получить описание задачи сформированной для логиста
								if (\CModule::IncludeModule("tasks"))
								{
									$rsTask = \CTasks::GetByID($taskNumber);
									if ($arTask = $rsTask->GetNext())
									{
										$arTaskDescription = $arTask['DESCRIPTION'];
									}
								}	
								
								//Получить комментарии задачи				
								\Bitrix\Main\Loader::IncludeModule('tasks');
								\Bitrix\Main\Loader::IncludeModule('forum');
								
								/**
									* @var int ID задачи
								*/
								$taskId = $arFields;
								
								/**
									* @var int ID форума
								*/
								$forumId = \Bitrix\Tasks\Integration\Forum\Task\Comment::getForumId();
								
								
								$messages = \Bitrix\Forum\MessageTable::getList([
								'select' => ['ID','POST_MESSAGE','AUTHOR_ID', 'AUTHOR_NAME'],
								'filter' => [
								'REAL_TOPIC.FORUM_ID' => $forumId,
								'REAL_TOPIC.XML_ID'   => 'TASK_'.$taskNumber,
								'!=PARAM1' => 'TK',
								],
								'runtime' => [
								new \Bitrix\Main\Entity\ReferenceField(
								'REAL_TOPIC',
								'\Bitrix\Forum\TopicTable',
								array('=this.TOPIC_ID' => 'ref.ID')
								)
								
								],
								]);
													
								foreach ($messages as $key => $message)
								{
								
									$str=0;
									//поиск вхождения
									//	Diag\Debug::writeToFile($arrRemoveFROMComments , $varName = "arrRemoveFROMComments", $fileName = "logText.log");
									foreach ($arrRemoveFROMComments as $ksn => $value)
									{		
										
										if ( strpos($message['POST_MESSAGE'], $value ) !== false ) {
											//	Diag\Debug::writeToFile($message['POST_MESSAGE'] , $varName = "message['POST_MESSAGE']", $fileName = "logText.log");	
											//Diag\Debug::writeToFile($value , $varName = "value", $fileName = "logText.log");
											$str=1;
											break 1;
										}							
									}
									
									if ( $str == 0 ) 
									{
										$arRequestsMessage[$key]['ID'] = $message['ID'];
										$arRequestsMessage[$key]['POST_MESSAGE'] = $message['POST_MESSAGE'];
										$arRequestsMessage[$key]['AUTHOR_ID'] = $message['AUTHOR_ID'];
										$arRequestsMessage[$key]['AUTHOR_NAME'] = $message['AUTHOR_NAME'];						
										$strMessage = $strMessage . "<br/>" . "<b>". $message['AUTHOR_NAME'] . "</b><br/>" . $message['POST_MESSAGE'];
									}
								} 
								
								$strRequestsMessage2 = "<br/>" . $strRequestsMessage2 . $strMessage;
								
								//Diag\Debug::writeToFile($strMessage , $varName = "strMessage", $fileName = "logText.log");					
								
								$TASKS = $subtask_name_text_ASystem . $nameAdddescriptionTaskLvl1 . ';' . $arTaskDescription . $strRequestsMessage2 . ';' . $logistician_employee . ';' . $Yakovleva_Executor_Assistant_Logist . ';' . $Lee_Co_executor_Assistant_Logistician;				
								$TASKS = html_entity_decode($TASKS);						
								$TASKS = explode(";", $TASKS);
								
								$arTask['TITLE'] = $TASKS[0];
								$arTask['DESC'] = $TASKS[1];
								$arTask['CREATED_BY'] = $TASKS[2];
								$arTask['RESPONSIBLE_ID'] = $TASKS[3];	
								$arTask['ACCOMPLICES'] = $TASKS[4];	
								
								//определить крайний срок
								$current_date = date("Y-m-d");
								$date_dedline = date("d.m.Y H:i:s", strtotime($oldDate.'+ 2 days'));								
								
								$arFields = Array(
								"TITLE" => $arTask['TITLE'],
								"DESCRIPTION" => $arTask['DESC'],
								"RESPONSIBLE_ID" => $arTask['RESPONSIBLE_ID'],  
								"AUDITORS" => $auditors,
								"CREATED_DATE" => date('d.m.Y H:i:s'),
								"CHANGED_DATE" => date('d.m.Y H:i:s'),
								"STATUS_CHANGED_DATE" => date('d.m.Y H:i:s'),
								"START_DATE_PLAN" => date('d.m.Y H:i:s'),
								"VIEWED_DATE" => date('d.m.Y H:i:s'),
								// "DEADLINE" => date("d.m.Y H:i:s",$sevenup),
								"DEADLINE" => $date_dedline, //крайний срок
								"ALLOW_CHANGE_DEADLINE"  => "Y", //флаг "Разрешить ответственному менять крайний срок";
								//"ALLOW_TIME_TRACKING" => 'Y',
								"CREATED_BY" =>  $logistician_employee,
								"STATUS" => 2,
								"REAL_STATUS" => 2,
								"GROUP_ID" =>$arRequestsResult["result"]["task"]["group"]["id"],
								"ACCOMPLICES" => $Lee_Co_executor_Assistant_Logistician,
								"DESCRIPTION_IN_BBCODE" => "Y",
								//	"PRIORITY" => 2,
								"PARENT_ID" => $taskNumber,
								);
								
								$obTask = new \CTasks;								
								$ID = $obTask->Add($arFields);
								$success = ($ID>0);							
				
								//Добавить соисполнителя
								\CTasks::AddAccomplices($arFields, $Lee_Co_executor_Assistant_Logistician);	

							}
						}			
					}
				}
			}
		}	
		
		
		//ФУНКЦИИ дополнительные	
		private function search_for_occurrence_of_subproblem ($task_title, $messages) { 
			// $task_title -  строка с котрой сравниваем - имя задачи
			// $messages - массив с строками для поиска вхождения		
		
			foreach ($messages as $key => $message)
			{				
				if ( strpos($task_title, $message) !== false ) {
					return 1;	//есть вхождение
				}
				else {
					return 0;  // нет вхождения
				}
			}		
		}
		
		private function search_subproblem_ar155614 ($task_title, $messages) { 
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