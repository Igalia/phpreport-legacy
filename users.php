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
	* nueva_password[uid] = Array de passwords cambiadas
	* administrador[uid] = Array de atributos administrador cambiados (a 1 cuando true)
	* cambiar[uid] = Array de botones CAMBIAR. Habrá como mucho un elemento, cuyo uid indicará
 *                el usuario que se quiere cambiar.
	* borrar[uid] = Array de botones BORRAR. Habrá como mucho un elemento, cuyo uid indicará
	*                el usuario que se quiere borrar.
	* nuevo_usuario_login = Login del nuevo usuario a crear.
	* nuevo_usuario_password = Password del nuevo usuario a crear.
	* nuevo_usuario_administrador = Flag administrador del nuevo usuario a crear.
	* crear = Se ha pulsado CREAR
 */

require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

if (!(in_array("informesadm",(array)$session_groups) 
)) {
 header("Location: login.php");
}

if(!empty($edit)&&empty($periods)||(!empty($id)&&empty($del_period)&&empty($change))) {
 $periods=array();
 if (!empty($change))$user=reset(array_keys($change));
 elseif (!empty($edit)) $user=reset(array_keys($edit));
 else $user=$id;

 $die=_("Can't finalize the operation");
   $result=pg_exec($cnx,$query="SELECT * FROM periods"
   ." WHERE uid='$user' ORDER BY init ")
   or die($die);
 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
   $row["init"]=date_sql_to_web($row["init"]);
   if ($row["_end"]!="") $row["_end"]=date_sql_to_web($row["_end"]);
   else $row["_end"]="NULL";
   $periods[]=$row;
 }
 @pg_freeresult($result);
}

if (!empty($change)) {

 do {
   $k=array_keys($change);
   $uid=$k[0];

   if (!empty($new_password[$uid])) {
     $query_password=", password=md5('$new_password[$uid]')";
   } else {
     $query_password="";
   }
   
   $die=_("Can't finalize the operation: ");
   if (!@pg_exec($cnx,$query=
    "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
   ."BEGIN TRANSACTION; ")) {
     $error=_("Can't finalize the operation");
     break;
   }

   do{
     
     // BORRADO DE PERIODOS
     if (!pg_exec($cnx,$query=
     "DELETE FROM periods WHERE uid='$uid'")) {
       $error=_("Can't finalize the operation");
       break;
     }

     if(!(@pg_exec($cnx,$query="UPDATE users SET admin='".
	 (($administrator[$uid]==1)?"t":"f")
	 ."' ".$query_password." WHERE uid='$uid'")
	or die("$die $query"))) {
     	  $error=_("Can't finalize the operation");
     	  break;
    }


$empty=true;

if(($init_date!=""&&$end_date!=""&&$city!="")){
  $empty=false;
  $s=sizeof($periods);
  $periods[$s]["uid"]=$uid;
  $periods[$s]["journey"]=$jour_hours;
  $periods[$s]["init"]=$init_date;
  $periods[$s]["_end"]=$end_date;
  $periods[$s]["city"]=$city;
}

for ($i=0;$i<sizeof($periods);$i++) {
    $fields=array("uid","journey","init","_end","city");
    $row=array();
    foreach ($fields as $field)
    $row[$field]=$periods[$i][$field];
    $row["uid"]=$uid;
    $row["init"]=date_web_to_sql($periods[$i]["init"]);
    if ($periods[$i]["_end"]=="NULL"){
	if(!$empty) {
	  $row["_end"]=date_web_to_sql($periods[$i+1]["init"]);
	  $periods[$i]["_end"]=$periods[$i+1]["init"];
	}
        else $row["_end"]="";
    }
    else $row["_end"]=date_web_to_sql($periods[$i]["_end"]);

    foreach ($fields as $field)
     if (empty($row[$field]) && !is_integer($row[$field])) $row[$field]="NULL";
     else $row[$field]="'$row[$field]'";

    // Y FINALMENTE SE INSERTAN
  if (!empty($periods)) {
    if (!pg_exec($cnx,$query="INSERT INTO periods ("
     .implode(",",$fields).") VALUES (".implode(",",$row).")")) {
     $error=_("Can't finalize the operation");
     break;
    }
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

if (!empty($del_period)) {
$periods=del_elements_shifting($periods, current(array_keys($del_period)));
}

if (!empty($delete)) {
  $k=array_keys($delete);
  $admin=$k[0];
  if ($admin=="t") {
    $error=_("An administrator cannot be erased to itself");
  } else {
    $result=@pg_exec($cnx,$query="DELETE FROM users WHERE uid='$user'")
	or die("$die $query");
    $confirmation=_("The user has been deleted correctly");
    @pg_freeresult($result);

    $result2=pg_exec($cnx,$query="DELETE FROM periods WHERE uid='$user'")
        or die("$die $query");
    @pg_freeresult($result2);
 }
}

if (!empty($new_user_login)&&!empty($new_user_password)||!empty($create)) {
  do{
    if(empty($new_user_login)){
      $error=_("You must specify the user login");
      break;
    }	
    if(empty($new_user_password)){
      $error=_("You must specify the user password");
      break;
    } 		
    $city="coruña";
    $jour_hours="8";
    if (!@pg_exec($cnx,$query=
	"SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
	."BEGIN TRANSACTION; ")) {
      $error=_("Can't finalize the operation");
      break;
    }
    if ((!$result=@pg_exec($cnx,$query="INSERT INTO users (uid,password,admin)"
	." VALUES('$new_user_login',md5('$new_user_password'),'"
	.(($new_user_admin==1)?"t":"f")
	."')"))||(!$result2=pg_exec($cnx,$query="INSERT INTO periods"
	." (uid,journey,init,_end,city) VALUES ('$new_user_login', '$jour_hours'," 
	."CURRENT_DATE, NULL,'$city')"))) {
	$error=_("Can't finalize the operation");
    } else {
	$confirmation=_("The user has been created correctly");
	$combo[]=$new_user_login;
    }
    @pg_exec($cnx,$query="COMMIT TRANSACTION");
    if (!empty($error)) {
      // Depuración
      $error.="<!-- $query -->";
      @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
      break;
    }		
    @pg_freeresult($result);
    @pg_freeresult($result2);
  }while(false);
}


//Carga dos usuarios a visualizar no combo
$result=@pg_exec($cnx,$query="SELECT uid,admin FROM users ORDER BY uid")
or die("$die $query");	
$users=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$users[]=$row;
}
@pg_freeresult($result);

require_once("include/close_db.php");

$flag="edit"; //Para poner el focus en el botón de Edit
$title=_("Users management");
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
<?=_("Users list")?>
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
<td><b><?=_("Users:")?></b></td>

<?
$combo=array();
foreach ($users as $user){ 
  $combo[]=$user["uid"];
}
if (empty($id)||!empty($delete)) {
  $user["uid"]=$combo[0];
  $id=$user["uid"];
  unset($id);
}
else $user["uid"]=$id;
?>

 <td>
<select name="id" onchange="javascript: document.results.submit();">
<?=array_to_option(
    array_values($combo),$id)?>
  </select> 
 </td>
 <td>
   <input type="submit" name="edit[<?=$user["uid"]?>]" value="<?=_("Edit")?>">
 </td>
</tr>
<?

if ($authentication_mode=="sql") {
?>
<tr>
<input type="submit" name="new" value="<?=_("Create new user")?>" style="margin-left: auto; margin-right: auto">
</tr>
<?
}
?>
</table>

</center>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->

<?
if(!empty($new)) {?>
<br><br><br>
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("User creation")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<table border="0" >
<tr>
 <td><?=_("New user login");?></td>
 <td><?=_("New user password");?></td> 
 <td><?=_("Administrator");?></td>
</tr>
<tr>
 <td><input type="text" name="new_user_login"></td>
 <td><input type="password" name="new_user_password"></td>
 <td><input type="checkbox" name="new_user_admin" value="1"></td>
</tr>
</table>
<table border="0" style=" text-align: center;margin-left: auto; margin-right: auto">
<tr>
<td>  <br>
  <input type="submit" name="create" value="<?=_("Create")?>"></td>
</tr>
</table>

<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>

<?
}
if (!empty($edit)||!empty($id)&&(empty($new)&&empty($create))||!empty($del_period)){
?>
<br><br><br>

<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("User edition")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<?
if ($authentication_mode=="sql") {
?>

<table border="0">
 <tr>
   <th>Login</th>
   <th></th>
   <th><?=_("New Password")?></th>
   <th><?=_("Administrator");?></th>
   <td>&nbsp</td>
   <td>&nbsp</td>
 </tr>
 <tr>
   <td style="text-align: center; vertical-align: top;"><?=$user["uid"]?></td>
   <td></td>
   <td><input type="password" name="new_password[<?=$user["uid"]?>]" value=""></td>
<?foreach ($users as $u) {
  if ($user["uid"]==$u["uid"]) $user["admin"]=$u["admin"];
}?>
   <td><input type="checkbox" name="administrator[<?=$user["uid"]?>]" 
		 value="1" <?=($user['admin']=='t'?"checked":"")?>>
   </td>
 </tr>
</table>

<?
}else {?>

<table border="0">
 <tr>
   <th>Login: </th>
   <td style="text-align: center; vertical-align: top;"><?=$user["uid"]?></td>
 </tr>

<?
}?>
<br>

<?
$journey=array(8=>_("Full time"),4=>_("Half time"),6=>_("Practice journey"));
if (!empty($periods)) {?>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Existing periods")?> 
<!-- end title box -->
</font></td></tr>

<?
for ($i=0;$i<sizeof($periods);$i++) {
?>
<tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<tr>
  <td><?=_("Hours");?>:</td>
  <td><select name="<?="periods[$i][journey]"?>"><?=array_to_option(array_values($journey),$periods[$i]["journey"],array_keys($journey))?>
   </select>
</tr>
<tr>
  <td><?=_("Init date");?>:</td>
  <td><input type="text" name="<?="periods[$i][init]"?>" value="<?=$periods[$i]["init"]?>"></td>
</tr>
<tr>
  <td><?=_("End date");?>:</td>
  <td><input type="text" name="<?="periods[$i][_end]"?>" value="<?=$periods[$i]["_end"]?>"></td>
</tr>
<tr>
  <td><?=_("City");?>:</td>
  <td><input type="text" name="<?="periods[$i][city]"?>" value="<?=$periods[$i]["city"]?>"></td>
</tr>

 <tr>
  <td></td><td align="center">

   <input type="submit" name="<?="del_period[$i]"?>" value="<?=_("Delete period")?>">
  </td>
 </tr>

</font></td></tr></table>
<?}
?>

<!-- end text box -->
</td></tr></table></td></tr></table>
<?} /*end if !empty(periods)*/?>   

<br><br>
<table>
 <tr>
  <td>
<?=_("Type of journey");?>:
   <select name="jour_hours"><?=array_to_option(array_values($journey),$journey,array_keys($journey))?>
   </select>
  </td>
 </tr>

<table><br>
 <tr>
  <td>
<?=_("Init date");?>:
  </td>
  <td>
    <input type="text" name="init_date">
  </td> 
 </tr>
 <tr>
  <td>
<?=_("End date");?>:
  </td>
  <td>
    <input type="text" name="end_date">
  </td> 
 </tr>
 <tr>
  <td><br>
<?=_("City");?>
  </td>
  <td><br>
    <input type="text" name="city">
  </td> 
 </tr>

</table>

<br>
<table border="0" style="margin-left: auto; margin-right: auto">
<tr>
  <td>
     <input type="submit" name="change[<?=$user["uid"]?>]" value="<?=_("Change")?>">
  </td>
<? if ($authentication_mode=="sql") {?>
  <td>
   <input type="submit" name="delete[<?=$user["admin"]?>]" value="<?=_("Delete")?>"></td>
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
