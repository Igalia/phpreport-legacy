<?
require_once("autenticate.php");

$actual_date=getdate(time());
$arrayDMA=array($actual_date["mday"],$actual_date["mon"],$actual_date["year"]);
$actual_date=date_arrayDMA_to_web($arrayDMA);
$actual_date=date_web_to_sql($actual_date);

$pre_vars=array("uid_worker"=> $session_uid,"_date__end"=> $actual_date);
?>
