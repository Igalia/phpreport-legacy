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
  <td align="left"><img src="images/titulo.gif"></td>
  <td align="right" class="titulo">Introducci�n de informes</td>
 </tr>
</table>
<? msg_fallo("No se pudo completar la operaci�n"); ?>
<? msg_ok("Los cambios fueron realizados correctamente"); ?>

<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="titulo_caja"><font
 color="#FFFFFF" class="titulo_caja">
<!-- t�tulo caja -->
T�tulo de la caja
<!-- fin t�tulo caja -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_caja">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->
Texto de la caja:<br>
Igalia es una empresa de Ingenier�a en Inform�tica dedicada a la investigaci�n y desarrollo de soluciones en el campo de las Tecnolog�as de la Informaci�n y las Comunicaciones, y est� especializada en la consultor�a de tecnolog�as basadas en Software Libre - GNU/Linux y sus aplicaciones asociadas.
<!-- fin texto caja -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- fin caja -->

</body>
</html>
