<?php
	
	//Автоматическая постановка подзадачи после завершения родительской. 
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->addEventHandlerCompatible(
	'tasks',
	'OnTaskUpdate',
	[
	'TasksImprovements\\AutomaticSettingSubtaskCompletedClass',
	'AutomaticSettingSubtaskCompleted'
	],
	$_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/event_handlers/AutomaticSettingSubtaskCompletedClass.php",
	100
	);	
	
	//Шаблон: "Заказ такси"
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->addEventHandlerCompatible(
	'tasks',
	'OnTaskUpdate',
	[
	'TaxiOrderRaster\\RasterTaxiOrderClassClass',
	'RasterTaxiOrderFunction'
	],
	$_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/event_handlers/RasterTaxiOrderClass.php",
	100
	);	
	
	// Шаблон: Забрать_Поставщик_Что забираем (с названием информации на макетах)
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->addEventHandlerCompatible(
	'tasks',
	'OnTaskUpdate',
	[
	'PickSupplierWhatPick\\PickSupplierWhatPickClass',
	'PickSupplierWhatPickFunction'
	],
	$_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/event_handlers/PickSupplierWhatPickClass.php",
	100
);