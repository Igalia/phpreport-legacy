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

require_once("include/autenticate.php");

$title=_("Equivalence of codes");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<?
foreach (array(
 "table_type"=>_("Task type"),
 "table_name"=>_("Project"),
 "table_phase"=>_("Project phase"),
 "table_ttype"=>_("Task type into the phase")
 ) as $confirmation_name=>$confirmation_explain) {
 $table=&$$confirmation_name;

?>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="5" width="100%">
 <tr>
  <td bgcolor="#000000" class="title_box">
   <font color="#FFFFFF" class="title_box">
   <?=_("Code")?>
   </font>
  </td>
  <td bgcolor="#000000" class="title_box">
   <font color="#FFFFFF" class="title_box">
   <?=$confirmation_explain?>
   </font>
  </td>
 </tr>
<?
 foreach ((array)$table as $id=>$value) {
  if (empty($id)) continue;
?>
 <tr>
  <td bgcolor="#FFFFFF" class="text_box">
   <?=$id?>
  </td>
  <td bgcolor="#FFFFFF" class="text_box">
   <?=$value?>
  </td>
 </tr>
<?
 }
?>
</table>
</td></tr></table>
<!-- end box -->

<br>
<br>
<?
}
?>
</tr>
</table>

<?
require("include/template-post.php");
?>


