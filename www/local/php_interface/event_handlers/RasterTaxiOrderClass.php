<?
	namespace TaxiOrderRaster;
	use Bitrix\Main\Diag;
	
	//добавить нвого пользователя в нужные групповые чаты
	class RasterTaxiOrderClassClass
	{		
		// создаем обработчик события "OnAfterUserAdd"
		public function RasterTaxiOrderFunction(&$arFields)
		{
			$taskIDNumberTemplateString = array("Заказ такси"); //строка из названия задачи для которой нужно создать подзадачу 
			$taskTag = array("56"); //строка из названия задачи для которой нужно создать подзадачу 
			$taskIDGroup = 37; //ID группы для которой нужно создать подзадачу 
			$nameStrAdditionally = array("(подтверждение факта доставки)"); //для формирования названия подзадачи
			$nameStrAdditionallyExclude = array("Заказ такси/КС"); //для формирования названия подзадачи
			$fix_the_cost = array("Зафиксировать себестоимость:"); //не создавать подзадачу Зафиксировать себестоимость
			
			$storekeeper = 63; //Кладовщик
			
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
			
			if ( $arTask['STATUS'] == 5 && $arTask['GROUP_ID'] == $taskIDGroup && self::search_subproblem_ar($arTask['TAGS'][0], $taskTag) == 1 && self::search_for_occurrence_of_subproblem( $arTask["TITLE"], $nameStrAdditionally) == 0 ) 
			{
							
				if ( self::search_for_occurrence_of_subproblem($arTask["TITLE"], $fix_the_cost) == 0) 
				{	
					//создание подзадачи
					$arTask['TITLE'] = $arTask['TITLE'] . " " . $nameStrAdditionally[0];			
					
					foreach ($arTask['ACCOMPLICES'] as $key => $value) {
						if ($storekeeper != $value) {				
							$arTaskAccomplices[] = $value;
						}
					}
					$arTask['ACCOMPLICES'] = $arTaskAccomplices;			
					unset($arTaskAccomplices);
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
					//	"TAGS" => $tag_str,
					// "DEADLINE" => date("d.m.Y H:i:s",$sevenup),
					"DEADLINE" => $date_dedline, //крайний срок
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
					
					//Добавить соисполнителя
					\CTasks::AddAccomplices($arFields, $arTask['ACCOMPLICES']);	// после этого потом запись в файл не работает
					
				}	
			}
			
		}
		
		//ФУНКЦИИ дополнительные	
		private function search_for_occurrence_of_subproblem ($task_title, $messages) {	
			foreach ($messages as $key => $message)
			{			
				if ( strpos($task_title, $message) !== false ) {
					return 1;	// есть вхождение
				}
				else {
					return 0; //нет вхождения
				}
			}		
		}
		
				private function search_subproblem_ar ($task_title, $messages) { 		
			$sn=count($messages);		 
			//Diag\Debug::writeToFile($sn, $varName = "$sn", $fileName = "logText.log");	
			for ($i = 0; $i < $sn; $i++) {
				//Diag\Debug::writeToFile($i, $varName = "i", $fileName = "logText.log");
				//Diag\Debug::writeToFile($task_title, $varName = "task_title", $fileName = "logText.log");	
				//Diag\Debug::writeToFile($messages[$i], $varName = "messages[$i]", $fileName = "logText.log");				
			//	Diag\Debug::writeToFile($i, $varName = "i", $fileName = "logText.log");				
   				if ( $task_title == $messages[$i] ) {
					return 1;	//есть вхождение
				}								
			}
			
			return 0;  // нет вхождения
		}
	}	
?>	