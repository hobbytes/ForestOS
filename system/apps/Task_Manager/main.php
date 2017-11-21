<?
/*--------Получаем App Name и App ID--------*/
$appname=$_GET['appname'];
$appid=$_GET['appid'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:100%; width:100%; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*--------Подключаем библиотеки--------*/
require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
/*--------Запускаем сессию--------*/
session_start();
/*--------Проверяем безопасность--------*/
$security	=	new security;
$security->appprepare();
/*
Инициализируем переменные
$click - переменная используется для определения действия (клик или прикосновение)
$folder - переменная хранит место запуска программы
*/
$click=$_GET['mobile'];
$folder=$_GET['destination'];
/*--------Логика--------*/
?>
</div>
<script>
/*--------Логика JS--------*/
</script>
<?
unset($appid);//Очищаем переменную $appid
?>
