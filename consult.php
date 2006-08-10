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
 * consulta = Consulta seleccionada de entre todas las posibles
 * seleccionar = Se ha pulsado el botón SELECCIONAR CONSULTA
 * editar = Se ha pulsado el botón EDITAR CONSULTA
 * parameter_value = Array (parametro => valor) de valores introducidos
 *                   por el usuario para los parámetros de una consulta SQL
 * ejecutar = Se ha pulsado el botón EJECUTAR CONSULTA
 * aplicar = Se ha pulsado el botón APLICAR CAMBIOS en la pág. de edición
 * cancelar = Se ha pulsado el botón CANCELAR en la pág. de edición
 * borrar = Se ha pulsado el botón BORRAR CONSULTA
 * borrar_ok = 1 Se ha confirmado el borrado de la consulta
 * dia = Mantiene el día que "nos llevamos" de páginas anteriores
 */

require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/predefined_vars.php");
require_once("include/prepare_calendar.php");
/*
echo("<pre>");
print_r($pre_vars);
echo("</pre>");
*/
      
// Hay ciertas acciones que no están permitidas si el usuario no es
// administrador
if (!in_array("informesadm",(array)$session_groups) && (
     !empty($edit)
  || !empty($apply)
  || !empty($cancel)
  || !empty($delete)
 )) {
 header("Location: login.php");
}

if (!empty($apply)) {
 if ($consult=="new") {
  do {
   if (!$result=@pg_exec($cnx,
    $query="SELECT NEXTVAL('consult_id_seq') AS id")) {
    $error=_("It has not been possible to obtain an identifier for the new query");
    break;
   }

   if (!$row=@pg_fetch_array($result,0,PGSQL_ASSOC)) {
    $error=_("It has not been possible to obtain an identifier for the new query");
    break;
   }
   $consult=$row["id"];

   if (!$result=@pg_exec($cnx,"INSERT INTO "
    ."consult(id,title,sql,parameters,description,public) "
    ."VALUES ('$consult','$title','$sql','$parameters','$description','"
    .checkbox_to_sql($public)."')")) {
    $error=_("It has not been possible to insert the new query");
    break;
   }
   $confirmation=_("The query was created correctly");
  } while(false);
 } else {
  if (!@pg_exec($cnx,$query="UPDATE consult SET "
   ."title='$title',sql='$sql',parameters='$parameters',"
   ."description='$description',public='".checkbox_to_sql($public)."'"
   ." WHERE id='$consult'")) {
    $error=_("It has not been possible to update the query");
  } else {
   $confirmation=_("The query was updated correctly");
  }
 }
}

if (!empty($delete)) {
 if ($delete_ok==1) {
  do {
   if (!$result=pg_exec($cnx,
    $query="DELETE FROM consult WHERE id='$consult'")) {
    $error=_("It has not been possible to delete the query");
    break;
   }
   unset($consult);
   $select=_("Select");
   $confirmation=_("The query was deleted correctly");
  } while(false);
 } else {
  $error=_("You must confirm the erasure in the adjacent square to the button Delete");
 }
}

if (!in_array("informesadm",(array)$session_groups)) 
 $string_restriction=" WHERE public='t'";
else 
 $string_restriction="";
$die=_("Can't finalize the operation");
$result=@pg_exec($cnx,$query="SELECT id,title FROM consult"
 .$string_restriction." ORDER BY title")
or die($die);

$consults=array();
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
 $consults[$row["id"]]=$row["title"];
}
$temp=_("New query");
if (in_array("informesadm",(array)$session_groups)) {
 $consults["new"]="&lt;$temp&gt;";
}
@pg_freeresult($result);

// Controlamos si el usuario está viendo una consulta que no debe
if (!in_array("informesadm",(array)$session_groups)
 && !empty($consult)
 && !in_array($consult,array_keys($consults))
) {
 header("Location: login.php");
}

if (!empty($cancel)) {
 if ($consult=="new") unset($consult);
 $select=_("Select");
}
$i=0;
if (!empty($consult)) {
 if ($consult=="new") {
  foreach (array("title","description","sql","parameters","public") as $i) {
   unset($$i);
  }
 } else {
  $result=@pg_exec($cnx,$query="SELECT title,description,sql,parameters,public"
   ." FROM consult WHERE id='$consult' ORDER BY title")
  or die("$die $query");
  if ($row=@pg_fetch_array($result,0,PGSQL_ASSOC)) {
   extract($row);
  } else {
   unset($consult);
  }

//Si no se realiza ninguna otra acción y los campos no están vacíos significa que hemos pulsado enter
if(empty($cancel)&&empty($execute)&&empty($delete)&&empty($apply)&&empty($edit)&&!empty($parameters)) $execute="Execute";

  @pg_freeresult($result);
  if (!empty($execute)) {
    foreach (set_to_array($parameters) as $parameter) {
    $parameter=trim($parameter);
      foreach ($pre_vars as $var_key=>$var_value) {
        $parameter_value[$var_key]=$var_value;
        if ($var_key==$parameter){
	  if (in_array("informesadm",(array)$session_groups))   
	    $show_vars.="$var_key = $var_value ; ";            
        } 
      }
    $i++;
    //if (empty($parameter_value[$parameter])) $empty[$i]="Este campo no puede ser vacío";
    $sql=str_replace($parameter,"'".$parameter_value[$parameter]."'",$sql);
    }
   do {
    if (!$result=@pg_exec($cnx,$query=$sql)) {
     $error=_("SQL error:")."<i>".pg_errormessage()
      ."</i><br>"._("Original query").":<pre>$query</pre>";
     break;
    }

   $result_consult=array();
   for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
    $result_consult[]=$row;
   }
   @pg_freeresult($result);
  } while(false);
  }
}
}
if (!empty($select) && ($consult=="new")) {
 unset($select);
 $edit=_("Edit");
}

if (!empty($select)) {
 $parameter_value=array();
 unset($edit);
}
require_once("include/close_db.php");
$title=_("Results extraction");
$flag="exec";
require("include/template-pre.php");

?>
<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">
<center>
<form name="results" method="post">
<table border="0">
<tr>
 <td><b><?=_("Query:")?></b></td>
 <td>
  <select name="consult" onchange="javascript: results.submit();">
   <?=array_to_option(
    array_values($consults),
    $consult,
    array_keys($consults))?>
  </select>
 </td>
 <td>
  <input type="submit" name="select" value="<?=_("Query select")?>">
  <input type="hidden" name="day" value="<?=$day?>">
 </td>
</tr>
</table>
<br>
<?
if (!empty($consult)) {
?>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0" width="50%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Description")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<?=$description?>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->
<br>
<?
foreach (set_to_array($parameters) as $parameter) {
    $parameter=trim($parameter);
      foreach ($pre_vars as $var_key=>$var_value) {
       //$parameter_value[$var_key]=$var_value;
      if ($var_key==$parameter){
	if (in_array("informesadm",(array)$session_groups)) {    
	  $show_vars.="$var_key = $var_value ; ";     
        }
      } 
      }
}

 if (empty($edit)) {
  if (!empty($parameters)) {
?>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0" width="50%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Parameters")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<table border="0">
<?
$i=0;
foreach(set_to_array($parameters) as $parameter) {
  $parameter=trim($parameter);
  foreach ($pre_vars as $var_key=>$var_value) {
    if ($var_key==$parameter) {
	$disabled=true;    
    }
  }
  $i++;
  if(!$disabled) {
?>
   <tr>
    <td><?=$parameter?></td>
    <td> 
     <input type="text" name="parameter_value[<?=$parameter?>]"
      value="<?=$parameter_value[$parameter]?>">
    </td>
   </tr>
<?/*   <tr><td></td>
    <td><?if (!empty($empty[$i])) msg_fail($empty[$i]);?>
    </td>
   </tr>

echo("<pre>");
//print_r($parameter_value);
echo("</pre>");*/
  }
  $disabled=false;  
}

?>
 <tr><td colspan="2">
 
  <script language="javascript">
  <!--
  function openpopup() {
   result=window.open('labels.php','_blank','width=400,height=300')"
  }
  -->
  </script>
  <br>
  <?=_("See equivalence of codes")?>
  <a href="#" onclick="javascript:window.open('labels.php','remote',
   'scrollbars=yes,toolbar=no,menubar=no,status=no,width=700,height=400');"
  ><?=_("in window")?></a> / <a href="labels.php"
  ><?=_("in full screen")?></a>
 </td></tr>
</table>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->
<br>
<?
  } 
?>

<table border="0">
 <tr>
<?
  if (in_array("informesadm",(array)$session_groups)) {
?>
  <td width="50%">
   <input type="checkbox" name="delete_ok" value="1">
   <input type="submit" name="delete" value="<?=_("Delete")?>"
    style="color: #FF0000">
  </td>
<?
  }
?>
  <td width="25%"><input type="submit" name="execute" value="<?=_("Execute")?>"></td>
<?
  if (in_array("informesadm",(array)$session_groups)) {
?>
  <td width="25%"><input type="submit" name="edit" value="<?=_("Edit")?>"></td>
<?
  }
?>
 </tr>
</table>
<br>
<?
  if (!empty($execute)) {
?>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0" width="95%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="5" width="100%">
<?
   $first=true;
   foreach ((array)$result_consult as $row) {
    if ($first) {
     $first=false;
?>
 <tr>
<?
     foreach (array_keys((array)$row) as $field) {
?>
  <td bgcolor="#000000" class="title_box">
   <font color="#FFFFFF" class="title_box">
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
 <tr>
<?
    foreach ((array)$row as $field) {
     if (empty($field)) $field="&nbsp;";
?>
  <td bgcolor="#FFFFFF" class="text_box">
   <?=$field?>
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
<?
  }
?>

<?
 } else {
?>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Query edition")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<table border="0">
 <tr>
  <td>
   <?=_("Title")?><br>
   <input type="text" name="title" value="<?=$title?>" size="80">
  </td>
 </tr>
 <tr>
  <td>
   <?=_("Description")?>:<br>
   <textarea name="description" cols="80" rows="5"><?=$description?></textarea>
  </td>
 </tr>
 <tr>
  <td>
   SQL:<br>
   <textarea name="sql" cols="80" rows="5"><?=$sql?></textarea>
  </td>
 </tr>
 <tr>
  <td>
   <?=_("Parameters list (separated by comas):")?><br>
   <input type="text" name="parameters" value="<?=$parameters?>" size="80">
  </td>
 </tr>
 <tr>
<?
  if (in_array("informesadm",(array)$session_groups)) {
?>
  <td>
  <?=_("Predefined variables:");?><br>
  <input type="text" name="vars" value="<?=$show_vars?>" size="80">
  </td>
<?
  }
?>
 </tr>
 <tr>
  <td>
   <input type="checkbox" name="public" value="t" <?=sql_to_checkbox($public)?>>
   <?=_("Allow to all users to use this query")?>
  </td>
 </tr>
</table>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->
<br>
<table border="0">
 <tr>
  <td>
   <input type="submit" name="apply" value="<?=_("Apply changes")?>">
   <input type="hidden" name="edit" value="<?=_("edition_mode")?>">
  </td>
  <td><input type="submit" name="cancel" value="<?=_("Cancel")?>"></td>
 </tr>
</table>
<?
 }
}
?>
</form>

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
