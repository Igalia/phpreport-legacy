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

/**
 * PARÁMETROS HTTP QUE RECIBE ESTA PÁGINA:
 *
 * Ninguno
 *
 */

require_once("include/autentificado.php");

$title="Equivalencia de códigos";
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);
if (!empty($confirmacion)) msg_ok($confirmacion);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<?
foreach (array(
 "tabla_tipo"=>"Tipo de tarea",
 "tabla_nombre"=>"Proyecto",
 "tabla_fase"=>"Fase del proyecto",
 "tabla_ttipo"=>"Tipo de tarea dentro de la fase"
 ) as $nombre_tabla=>$explicacion_tabla) {
 $tabla=&$$nombre_tabla;

?>
<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="5" width="100%">
 <tr>
  <td bgcolor="#000000" class="titulo_caja">
   <font color="#FFFFFF" class="titulo_caja">
   Código
   </font>
  </td>
  <td bgcolor="#000000" class="titulo_caja">
   <font color="#FFFFFF" class="titulo_caja">
   <?=$explicacion_tabla?>
   </font>
  </td>
 </tr>
<?
 foreach ((array)$tabla as $id=>$valor) {
  if (empty($id)) continue;
?>
 <tr>
  <td bgcolor="#FFFFFF" class="texto_caja">
   <?=$id?>
  </td>
  <td bgcolor="#FFFFFF" class="texto_caja">
   <?=$valor?>
  </td>
 </tr>
<?
 }
?>
</table>
</td></tr></table>
<!-- fin caja -->

<br>
<br>
<?
}
?>
</tr>
</table>

<?
require("include/plantilla-post.php");
?>


