<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
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
 * HTTP PARAMETERS RECEIVED BY THIS PAGE:
 *
 * day = Day to show calendar for. DD/MM/YYYY format.
 * activation[type][code] = Label [type][code] is enabled
 * deleted[type][code] = DELETE has been clicked for label [type][code] 
 * type = Type of the label to be added
 * code = Code of the label to be added
 * description = Code of the label to be added
 * apply = APPLY ACTIVATION has been clicked
 * add = ADD has been clicked
 */

require_once("include/autenticate.php");
require_once("include/util.php");
require_once("include/prepare_calendar.php");
require_once("include/connect_db.php");

if (!in_array($admin_group_name,(array)$session_groups)) {
 header("Location: login.php");
}

if (!empty($deleted)) {
 $type_deleted=array_pop(array_keys($deleted));
 $code_deleted=array_pop(array_keys($deleted[$type_deleted]));
 if (!$result=@pg_exec($cnx,$query=
   "DELETE FROM label"
  ." WHERE type='$type_deleted' AND code='$code_deleted'")) {
  $error=_("Can't finalize the operation");
 } else {
  $confirmation=_("The label has been deleted correctly");
 }
}

if (!empty($add)) {
 if (!$result=@pg_exec($cnx,$query=
   "INSERT INTO label(type,code,description)"
  ." VALUES ('$type','$code','$description')")) {
  $error=_("Can't finalize the operation");
 } else {
  $confirmation=_("The label has been inserted correctly");
  unset($type); unset($code); unset($description);
 }
}

if (!empty($apply)) {

 // LIST ALL THE VALUES AND GET THOSE THAT HAVE CHANGED (e)

 $e=array();
 $die=_("Can't finalize the operation");
 $result=@pg_exec($cnx,$query="SELECT type,code,activation FROM label")
 or die($die);
 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  if ((checkbox_to_sql($activation[$row["type"]][$row["code"]]))
   !=($row["activation"])) {
   $e[]=array($row["type"],$row["code"]);
  }
 }
 @pg_freeresult($result);

 // UPDATE CHANGES

 $r=true;
 foreach ($e as $k) {
  if ($k[0]!="name"){
    $result=@pg_exec($cnx,$query=
     "UPDATE label SET activation='".checkbox_to_sql($activation[$k[0]][$k[1]])."'"
    ." WHERE type='${k[0]}' AND code='${k[1]}'");
    $r&=(!(!$result));
    @pg_freeresult($result);
  }
 }

 if ($r) {
  $confirmation=_("The activations have been applied correctly");
 } else {
  $error=_("Can't finalize the operation");
 }
}

$label=array(array(_('Type'),_('Code'),_('Description'),_('Activation'),_('Deleted')));
$die=_("Can't finalize the operation");
$result=@pg_exec($cnx,$query="SELECT * FROM label"
 ." ORDER BY type,code")
or die($die);

for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
 $row["description"]=stripslashes($row["description"]);
 if ($row["type"]=="name") $show="DISABLED";
 else $show="";
 $row["activation"]="<input type=\"checkbox\" name=\"activation["
  .$row["type"]."][".$row["code"]."]\" "
  .sql_to_checkbox($row["activation"])." $show >";
 $delete=_("Delete");
 if ($row["type"]!="name")
   $row["deleted"]="<input type=\"submit\" name=\"deleted["
    .$row["type"]."][".$row["code"]."]\" value=\"$delete\">";
 else $row["deleted"]="";
 $label[]=$row;
}
@pg_freeresult($result);
$temp1=_("Apply activations");
$temp2=_("Add label");
$label[]=array(
 'type'=>"<select name=\"type\" size=\"1\">"
  .array_to_option(array('type','phase','ttype'),$type)."</select>",
 'code'=>"<input type=\"text\" name=\"code\" value=\"".stripslashes($code)."\">",
 'description'=>"<input type=\"text\" name=\"description\" value=\"".stripslashes($description)."\">",
 'activation'=>"<input type=\"submit\" name=\"apply\" value=\"$temp1\">",
 'send'=>"<input type=\"submit\" name=\"add\" value=\"$temp2\">"
);

require_once("include/close_db.php");

$title=_("Label administration");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<form name=labels method="post">

<br>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0" xwidth="95%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="5" width="100%">
<?
$first=true;
foreach ((array)$label as $row) {
 if ($first) {
  $first=false;
  $class="title_box";
 } else {
  $class="text_box";
 }
?>
 <tr>
<?
 foreach ((array)$row as $field) {
?>
  <td bgcolor="#000000" class="<?=$class?>">
   <font color="#FFFFFF" class="<?=$class?>">
   <?=$field?>
   </font>
  </td>
<?
 }
?>
 </tr>
<?
}
?>
</table>
</td></tr></table>
<!-- end box -->

</form>
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/show_sections.php") ?>
<br>
<? require("include/show_calendar.php") ?>
</td>
</tr>
</table>

<?
require("include/template-post.php");
?>
