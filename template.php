<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andr�s G�mez Garc�a <agomez@igalia.com>
//  Enrique Oca�a Gonz�lez <eocanha@igalia.com>
//  Jos� Riguera L�pez <jriguera@igalia.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.


require("include/util.php");
?>
<html>
<head>
<link rel=StyleSheet href="css/styles.css" type="text/css">
<title>
PhpReport
</title>
</head>
<body bgcolor="#FFFFFF">
<table border="0" cellspacing="0" cellpadding="0" width="100%">
 <tr>
  <td align="left"><img src="images/title.gif"></td>
  <td align="right" class="title"><?=_("Introducci�n de informes")?></td>
 </tr>
</table>
<? msg_fail_("No se pudo completar la operaci�n"); ?>
<? msg_ok_("Los cambios fueron realizados correctamente"); ?>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("T�tulo de la caja")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<?=_("Texto de la caja:<br>
Igalia es una empresa de Ingenier�a en Inform�tica dedicada a la investigaci�n y desarrollo de soluciones en el campo de las Tecnolog�as de la Informaci�n y las Comunicaciones, y est� especializada en la consultor�a de tecnolog�as basadas en Software Libre - GNU/Linux y sus aplicaciones asociadas."?>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->

</body>
</html>
