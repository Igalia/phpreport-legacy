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
 * dia = Día del informe. Formato DD/MM/AAAA
 * minutos_semanales = Valor precalculado con los minutos trabajados esta semana
 *                   Sirve para no tener que hacer continuamente la query. Se
 *                   recalcula si está vacío o si se guarda el informe
 * minutos_diarios = Valor precalculado con los minutos trabajados hoy
 * tarea = array con las diferentes tareas:
 *  array(
 *   0=>array(
 *       "init"=>...,
 *       "fin"=>...
 *       ...
 *      ),
 *   1=>array(...),
 *   ...
 *  );
 * borrar_tarea[i] = Se ha pulsado BORRAR TAREA para la tarea i-ésima
 * nueva_tarea = Se ha pulsado NUEVA TAREA
 * cancelar = Se ha pulsado CANCELAR
 * guardar = Se ha pulsado GUARDAR
 * editando = Parámetro hidden de formulario que indica que éste se está editando.
 *            Sirve para evitar recargar los datos cuando se han borrado todas las
 *            tareas (array tareas vacío voluntariamente)
 */
require_once("include/config.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

// SI NO HAY DIA, SE GENERA

if (empty($day)) {
 $day=getdate(time());
 $day=$day["mday"]."/".$day["mon"]."/".$day["year"];
}

// COMPROBACIÓN DE SI EL INFORME ESTÁ BLOQUEADO
$die=_("Can't finalize the operation");
$result=@pg_exec($cnx,$query="SELECT _date FROM block"
 ." WHERE uid='$session_uid'")
 or die($die);
$blocked=false;
if ($row=@pg_fetch_row($result))
 $blocked=!($row[0]<date_web_to_sql($day));
@pg_freeresult($result);
if ($blocked) $param_blocked=" READONLY ";

// PRECARGA DE LOS VALORES DEL FORMULARIO
// SE COMPRUEBA SI SE HA PULSADO ALGÚN BOTÓN COPIAR INFORME Y EN ESTE CASO
// SE CARGAN LOS VALORES DEL DÍA CORRESPONDIENTE

if ((empty($editing) && empty($task)) || $blocked || !empty($change) || !empty($change2)) {
 $task=array();
 $die=_("Can't finalize the operation");
 if (!empty($copy2) || !empty($copy) && empty($change) && empty($change2)) {
   $yesterday=day_yesterday($day);
   $temp=(!empty($copy)) ? $copy_day : $yesterday;
   $result=@pg_exec($cnx,$query="SELECT * FROM task"
   ." WHERE uid='$session_uid' AND _date='"
   .date_web_to_sql($temp)."'"
   ." ORDER BY init")
   or die($die);
 }
 else {
  $result=@pg_exec($cnx,$query="SELECT * FROM task"
  ." WHERE uid='$session_uid' AND _date='"
  .date_web_to_sql($day)."'"
  ." ORDER BY init")
  or die($die);
 }

 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $row["_date"]=date_sql_to_web($row["_date"]);
  $row["init"]=hour_sql_to_web($row["init"]);
  $row["_end"]=hour_sql_to_web($row["_end"]);
  $row["text"]=stripslashes($row["text"]);
  $task[]=$row;
 }
 @pg_freeresult($result);
}

// BORRAR TAREA

if (!empty($delete_task)) {
 $task=del_elements_shifting($task, current(array_keys($delete_task)));
}

// NUEVA TAREA
if (!empty($new_task)) {
 $i=count($task);
 $task[]=array(
  "init"=>date("H:i",mktime()),
  "_end"=>"",
  "type"=>"",
  "name"=>"",
  "phase"=>"",
  "ttype"=>"",
  "story"=>"",
  "text"=>""
 );
 if ($i==0) {	
  $task[$i]["init"]=date("H:i",mktime());
 } else if (empty($task[$i-1]["_end"])) {
  $task[$i-1]["_end"]=date("H:i",mktime());
  $task[$i]["init"]=$task[$i-1]["_end"];
 }
}

// VOLVER AL CALENDARIO

if (!empty($cancel)) {
 header("Location: ?day=$day");
}

//Actualiza el campo end de tarea con la hora actual
if (!empty($currenthour)){
$t=current(array_keys($currenthour));
$task[$t]["_end"]=date("H:i",mktime());
}

// GUARDAR CAMBIOS

if (!empty($save)) {
 do {
  // CHEQUEO DE BLOQUEO DE FORMULARIO

  if ($blocked) {
   $error=_("The report is blocked and it can't be modified");
   break;
  }

  // CHEQUEO DE CAMPOS

  for ($i=0;$i<sizeof($task);$i++) {
   foreach (array("init","_end") as $field) {
    if (!preg_match("/[0-9]{1,2}:[0-9]{2,2}/",$task[$i][$field])) {
     $error=_("Errors exist that must be corrected");
     $error_task[$i][$field]=_("You must use the format HH:MM");
    } else {     
     $task[$i][$field]=hour_sql_to_web(hour_web_to_sql($task[$i][$field]));
     if ($task[$i][$field]<"00:00" || $task[$i][$field]>"24:00") {
      $error=_("Errors exist that must be corrected");
      $error_task[$i][$field]=_("The hour must be between 00:00 and 24:00");
     }
    }
   }
   foreach (array("init","_end","type","text") as $field) {
    if (empty($task[$i][$field])) {
     $error=_("Errors exist that must be corrected");
     $error_task[$i][$field]=_("You must specify this field");
    }
   }
  }
  if (!empty($error)) break;

$size=sizeof($task);
if ($size>0) usort ($task, cmp_init_dates);

  // REPETIMOS LAS VUELTAS PARA QUE LOS INDICES DE ERRORES SEAN CORRECTOS

  for ($i=0;$i<$size;$i++) {
   if ($i>0 && $task[$i-1]["_end"]>$task[$i]["init"]) {
    $error_task[$i-1]["_end"]=_("The task overlaps with another one");
    $error_task[$i]["init"]=_("The task overlaps with another one");
    $error=_("Errors exist that must be corrected");
   }
   if ($task[$i]["init"]>=$task[$i]["_end"]) {
    $error=_("Errors exist that must be corrected");
    $error_task[$i]["init"]=_("Nonvalid interval of hours");
    $error_task[$i]["_end"]=_("Nonvalid interval of hours");
   }
  }
  if (!empty($error)) break;

  // TRANSACCIÓN DE ALMACENAMIENTO DE INFORME Y TAREAS

  if (!@pg_exec($cnx,$query=
    "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
   ."BEGIN TRANSACTION; ")) {
   $error=_("Can't finalize the operation");
   break;
  }

  do {

   // BORRADO DE TAREAS

   if (!@pg_exec($cnx,$query=
     "DELETE FROM task WHERE uid='$session_uid'"
    ." AND _date='".date_web_to_sql($day)."'")) {
    $error=_("Can't finalize the operation");
    break;
   }

   // GESTION DE BLOQUEOS

  if (!$result=@pg_exec($cnx,$query=
     "SELECT _date FROM block WHERE uid='$session_uid'")) {
    $error=_("Can't finalize the operation");
    break;
   }

   if (@pg_numrows($result)==0) {

    // INSERT BLOQUEO
    // ATENCION!! LE HACE FALTA COMILLAS A LA FECHA?
    if (!$result=@pg_exec($cnx,$query=
      "INSERT INTO block (uid,_date)"
     ." VALUES ('$session_uid', '1999-12-31')")) {
     $error=_("Can't finalize the operation");
     break;
    }
   }

 // GESTION DEL INFORME

   if (!$result=@pg_exec($cnx,$query=
     "SELECT modification_date FROM report WHERE uid='$session_uid'"
    ." AND _date='".date_web_to_sql($day)."'")) {
    $error=_("Can't finalize the operation");
    break;
   }

   if (@pg_numrows($result)>0) {

    // UPDATE INFORME

    if (!$result=@pg_exec($cnx,$query=
      "UPDATE report SET modification_date=now()"
     ." WHERE uid='$session_uid'"
     ." AND _date='".date_web_to_sql($day)."'")) {
     $error=_("Can't finalize the operation");
     break;
    }

   } else {

    // INSERT INFORME

    if (!$result=@pg_exec($cnx,$query=
      "INSERT INTO report (uid,_date,modification_date)"
     ." VALUES ('$session_uid','"
     .date_web_to_sql($day)."',now())")) {
     $error=_("Can't finalize the operation");
     break;
    }
   }

   // SE LE DA UN FORMATO CORRECTO A LAS TAREAS

   for ($i=0;$i<sizeof($task);$i++) {
    $fields=array("uid","_date","init","_end","name","type","phase","ttype","story","text");
    $row=array();
    foreach ($fields as $field)
    $row[$field]=$task[$i][$field];
    $row["uid"]=$session_uid;
    $row["_date"]=date_web_to_sql($day);
    $row["init"]=hour_web_to_sql($row["init"]);
    $row["_end"]=hour_web_to_sql($row["_end"]);
    foreach ($fields as $field)
     if (empty($row[$field]) && !is_integer($row[$field])
        && $field!="name") $row[$field]="NULL";
     else $row[$field]="'$row[$field]'";

    // Y FINALMENTE SE INSERTAN

    if (!@pg_exec($cnx,$query="INSERT INTO task ("
     .implode(",",$fields).") VALUES (".implode(",",$row).")")) {
     $error=_("Can't finalize the operation");
     break;
    }
   }

  // Y SE HACE TODO EL PROCESO

   @pg_exec($cnx,$query="COMMIT TRANSACTION");
  } while(false);

  if (!empty($error)) {

   // Depuración
   $error.="<!-- $query -->";
   @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
   break;
  }

  $confirmation=_("The changes have save correctly");

 } while(false);
}

// RECUENTO DE HORAS SEMANALES Y DIARIAS TRABAJADAS

if (empty($weekly_minutes) || !empty($save)) {
 $weekly_minutes=worked_minutes_this_week($cnx,$session_uid,date_web_to_sql($day));
 $daily_minutes=0;
 foreach ((array)$task as $t)
  if (!empty($t["init"]) && !empty($t["_end"]))
   $daily_minutes+=(hour_web_to_sql($t["_end"])-hour_web_to_sql($t["init"]));
}
require_once("include/close_db.php");

$title=_("Report edition");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<form name="task" action="#last_task" method="post">
<input type="hidden" name="day" value="<?=$day?>">
<input type="hidden" name="hoxe" value="<?=$hoxe?>">
<input type="hidden" name="weekly_hours" value="<?=$weekly_hours?>">
<input type="hidden" name="daily_hours" value="<?=$daily_hours?>">

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Tasks of the day")?> <?=$day?>
<!-- end title box -->
</font></td></tr>
<?
for ($i=0;$i<sizeof($task);$i++) {
?>
<tr><td bgcolor="#FFFFFF" class="text_box">
<?
 if (($i==sizeof($task)-1)&&(!empty($new_task)||!empty($delete_task))) {
?>
<a name="last_task"></a>
<?
 }
?>
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<table border="0" cellpadding="3" cellspacing="1">
<?
 foreach (array(
  "init"=>_("Init hour"),
  "_end"=>_("End hour"),
  ) as $field_key=>$field_value) {
?>
 <tr>
  <td width="200px"><?=$field_value?></td>
  <td>
   <input type="text" name="<?="task[$i][$field_key]"?>" <?=$param_blocked?>
    value="<?=$task[$i][$field_key]?>">
  </td>
 
  <td>
<?
  if (!empty($error_task[$i][$field_key])) {
   echo msg_fail($error_task[$i][$field_key]);
  }
?>
  </td></tr>
 <?
 }
?>
<tr>
  <td width="200px"></td>
  <td> <input type="submit" name="<?="currenthour[$i]"?>" value="<?=_("Current hour")?>"> 
 </td>
 </tr>
<?
 foreach (array(
  "type"=>_("Task type"),
  "name"=>_("Project"),
  "phase"=>_("Project phase"),
  "ttype"=>_("Task type into the phase")
  ) as $field_key=>$field_value) {
   $tabla="table_$field_key";
   for ($j=0;$j<sizeof($task);$j++) {
    if (${$tabla}[($task[$j][$field_key])]=="") {
     ${$tabla}[($task[$j][$field_key])]=
      $task[$j][$field_key]." (legacy)";
    }
   }
?>
 <tr>
  <td width="200px"><?=$field_value?></td>
  <td>
   <select name="<?="task[$i][$field_key]"?>" <?=$param_blocked?>>
    <?=array_to_option(array_values($$tabla),$task[$i][$field_key],array_keys($$tabla))?>
   </select>
  </td>
  <td>
<?
  if (!empty($error_task[$i][$field_key])) {
   echo msg_fail($error_task[$i][$field_key]);
  }
?>
  </td>
 </tr>
<?
 }
?>
 <tr>
  <td width="200px"><?=_("Use case")?></td>
  <td>
   <input type="text" name="<?="task[$i][story]"?>"
    value="<?=$task[$i]['story']?>" <?=$param_blocked?>>
  </td>
  <td>
<?
  if (!empty($error_task[$i]['story'])) {
   echo msg_fail($error_task[$i]['story']);
  }
?>
  </td>
 </tr>
 <tr>
  <td colspan="2">
   <?=_("Description:")?>
  </td>
  <td>
<?
if (!empty($error_task[$i]["text"])) {
   echo msg_fail($error_task[$i]["text"]);
  }
?>
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <textarea rows="3" cols="80" name="<?="task[$i][text]"?>" <?=$param_blocked?>
    ><?=stripslashes($task[$i][text])?></textarea>
  </td>
 </tr>
<?
if (!$blocked) {
?>
 <tr>
  <td colspan="3" align="right">
   <input type="submit" name="<?="delete_task[$i]"?>" value="<?=_("Delete task")?>">
  </td>
 </tr>
<?
}
?>
</table>
<!-- end text box -->
</table></td></tr>
<?
}
?>
</table></td></tr></table>
<!-- end box -->
<br>
<?

if (!$blocked) {
?>
<input type="hidden" name="editing" value="1">
<input type="submit" name="new_task" value="<?=_("New task")?>">
<input type="submit" name="save" value="<?=_("Save changes")?>">
<?
}
?>
<input type="submit" name="cancel" value=<?=_("Cancel")?>>
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/show_count.php") ?>
<br>
<? require("include/show_sections.php") ?>
<br>
<? require("include/show_calendar.php") ?>

</td>
</tr>
</table>
<?
require("include/template-post.php");
?>
