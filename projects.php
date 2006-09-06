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

require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

if (!(in_array("informesadm",(array)$session_groups) 
)) {
 header("Location: login.php");
}



if(!empty($edit)&&empty($project)||!empty($id)&&empty($new)&&empty($create)) {
 $project=array();
 if (empty($id)) $id=reset(array_keys($edit));
 $die=_("Can't finalize the operation");
   $result=pg_exec($cnx,$query="SELECT * FROM projects"
   ." WHERE id='$id' ")
   or die($die);
 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {   
   if ($row["init"]!="") $row["init"]=date_sql_to_web($row["init"]);
   else $row["init"]="";
   if ($row["_end"]!="") $row["_end"]=date_sql_to_web($row["_end"]);
   else $row["_end"]="";
   $project=$row;
 }
 @pg_freeresult($result);
}

if (!empty($change)) {
  $k=array_keys($change);
  $id=$k[0];

  $project["id"]=$id;
  $project["description"]=$description;
  $project["activation"]=checkbox_to_sql($activation);
  $project["est_hours"]=$est_hours;
  $project["invoice"]=$invoice;
  $project["init"]=$init;
  $project["_end"]=$end;   
 
  $init=date_web_to_quoted_sql($init);
  $end=date_web_to_quoted_sql($end);  

  if (!pg_exec($cnx,$query="UPDATE projects SET "
    ." description='$description',activation='".(($activation==1)?"t":"f")."',"
    ." est_hours='$est_hours',invoice='$invoice',"
    ."init=".$init.", _end=".$end
    ." WHERE id='$id'")||
      !pg_exec($cnx,$query="UPDATE label SET "
    ." description='$description' ,activation='".(($activation==1)?"t":"f")."'"
    ."WHERE code='$id'")) {
    $error=$die;
  } else {
    $confirmation=_("The project has been updated correctly");
  }  
}

if (!empty($new)) {
  $project=array();
  $creating=true; //Para q no cambie el boton create por change
}
if (!empty($create)) {
  $creating=true;
  $id=$name;

  $project["id"]=$id;
  $project["description"]=$description;
  $project["activation"]=checkbox_to_sql($activation);
  $project["est_hours"]=$est_hours;
  $project["invoice"]=$invoice;
  $project["init"]=$init;
  $project["_end"]=$end; 
  $init=date_web_to_quoted_sql($init);
  $end=date_web_to_quoted_sql($end);
  $activation=checkbox_to_sql($activation);

  if (!pg_exec($cnx,$query="INSERT INTO projects"
    ." (id,description,activation,est_hours,invoice,init,_end) "
    ."VALUES ('$id', '$description','$activation',"
    ."'$est_hours','$invoice', $init, $end)")
    ||!pg_exec($cnx,$query="INSERT INTO " 
    ."label (type,code,description,activation) "
    ."VALUES ('name','$id','$description', '$activation')")) {
    $error=$die;
  } else {
    $creating=false;
    $confirmation=_("The project has been created correctly");
  }
}

if (!empty($delete)) {
  $k=array_keys($delete);
  $id=$k[0];

  $result=pg_fetch_array(pg_exec($cnx,$query="SELECT * FROM task WHERE name='$id'"));
  if (!empty($result)) $error=_("The project hasn't been deleted: associate tasks");
  else{
    $result=@pg_exec($cnx,$query="DELETE FROM projects WHERE id='$id'")
	or die("$die $query");
    $confirmation=_("The project has been deleted correctly");
    @pg_freeresult($result);

    $result2=pg_exec($cnx,$query="DELETE FROM label WHERE code='$id'")
        or die("$die $query");
    @pg_freeresult($result2);
  }
}

$result=@pg_exec($cnx,$query="SELECT id FROM projects ORDER BY id")
or die("$die $query");	
$users=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$projects[]=$row;
}
@pg_freeresult($result);


require_once("include/close_db.php");

$flag="edit"; //Para poner el focus en el botón de Edit
$title=_("Projects management");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Projects list")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="5"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<center>
<form name="results" method="post">     
<input type="hidden" name="day" value="<?=$day?>">
<table border="0" cellspacing="0" cellpadding="10">
<tr>
<td><b><?=_("Projects")?>:</b></td>

<?
$combo=array();
foreach ($projects as $proj){ 
  $combo[]=$proj["id"];
}
?>
 <td>
<select name="id" onchange="javascript: document.results.submit();">
<?=array_to_option(
    array_values($combo),$id)?>
  </select> 
 </td>
 <td>
   <input type="submit" name="edit[<?=$proj["id"]?>]" value="<?=_("Edit")?>">
 </td>
</tr>
<tr>
<input type="submit" name="new" value="<?=_("Create new project")?>" style="margin-left: auto; margin-right: auto">
</tr>
</table>

</center>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->

<?
if (!empty($edit)||!empty($id)&&empty($delete)||$creating){
?>
<br><br><br>

<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Project edition")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->

<table border="0">
 <tr>
   <td><b><?=_("Name")?>:</b></td>
<?if ($creating) {?>
   <td><input type="input" name="name" value="<?=$project["id"]?>"></td> 
<?} else {?>
   <td><?=$project["id"]?></td> 
<?}?>
 </tr>

 <tr>
   <td><b><?=_("Description: ")?></b></td>
   <td><input type="input" name="description" value="<?=$project["description"]?>"></td> 
 </tr>

 <tr>
   <td><b><?=_("Activation")?>:</b></td>
<?if ($creating) {?>
   <td><input type="checkbox" name="activation" value="1" checked></td> 
<?} else {?>
   <td><input type="checkbox" name="activation" value="1" 
        <?=($project['activation']=='t'?"checked":"")?>></td> 
<?}?>
 </tr>
</table>

<table><br>

 <tr>
  <td><br>
<b><?=_("Estimated hours");?>:</b>
  </td>
  <td><br>
    <input type="text" name="est_hours" value="<?=$project["est_hours"]?>">
  </td> 
 </tr>
 <tr>
  <td>
<b><?=_("Invoice");?>:</b>
  </td>
  <td>
    <input type="text" name="invoice" value="<?=$project["invoice"]?>">
  </td> 
 </tr>
 <tr>
  <td>
<?=_("Init date");?>:
  </td>
  <td>
    <input type="text" name="init" value="<?=$project["init"]?>">
  </td> 
 </tr>
 <tr>
  <td>
<?=_("End date");?>:
  </td>
  <td>
    <input type="text" name="end" value="<?=$project["_end"]?>">
  </td> 
 </tr>
</table>

<br>
<table border="0" style="margin-left: auto; margin-right: auto">
<tr>
<?if (!$creating) { ?>
  <td>
     <input type="submit" name="change[<?=$project["id"]?>]" value="<?=_("Change")?>">
  </td>
<?}
else {?>
  <td>
     <input type="submit" name="create" value="<?=_("Create")?>">
  </td>
<?}?>
<? if ($authentication_mode=="sql") {  ?>
  <td>
    <input type="submit" name="delete[<?=$project["id"]?>]" value="<?=_("Delete")?>"></td>
<?}?>
  </tr>
</table>



<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>

<?
}
?>

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
