<?
/**
 * VARIABLES ALREADY DEFINED USED BY THIS PAGE:
 *
 * title = Page title
 *
 */
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel=StyleSheet href="css/styles.css" type="text/css">
<title><?=$title?></title>
</head>
<? if($flag=="exec") {?>
<body onload="document.forms[0].execute.focus();" bgcolor="#FFFFFF"> 
<?} elseif($flag=="edit") {?>
<body onload="document.forms[0].elements[2].focus();" bgcolor="#FFFFFF"> 
<?}else {?>
<body onload="document.forms[0].elements[0].focus();" bgcolor="#FFFFFF">
<?}?>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
 <tr>
  <td align="left"><img src="images/title_1_4.gif" alt="Igalia PhpReport"></td>
  <td align="right" class="title"><font color="#42458C"><?=$title?></font></td>
 </tr>
</table>
<br>
<br>
