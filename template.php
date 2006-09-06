<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
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
  <td align="right" class="title"><?=_("Introducción de informes")?></td>
 </tr>
</table>
<? msg_fail_("No se pudo completar la operación"); ?>
<? msg_ok_("Los cambios fueron realizados correctamente"); ?>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Título de la caja")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<?=_("Texto de la caja:<br>
Igalia es una empresa de Ingeniería en Informática dedicada a la investigación y desarrollo de soluciones en el campo de las Tecnologías de la Información y las Comunicaciones, y está especializada en la consultoría de tecnologías basadas en Software Libre - GNU/Linux y sus aplicaciones asociadas."?>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->

</body>
</html>
