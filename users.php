<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
//  Mario Sánchez Prada <msanchez@igalia.com>
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
 * day = Report day. DD/MM/YYYY format.
 * new_password[uid] = Array of changed passwords
 * administrator[uid] = Array of changed administrator attributes (to 1 when true)
 * staff[uid] = Array of changed belongs to staff attributes (to 1 when true)
 * change[uid] = Array of CHANGE buttons. There'll be at most one element, whose uid
 *               will tell the user to be changed.
 * delete[uid] = Array of DELETE buttons. There'll be at most one element, whose uid
 *               will tell the user to be deleted.
 * new_user_login = Login of the new user to be created.
 * new_user_password = Password of the new user to be created.
 * new_user_admin = Administrator flag of the new user to be created.
 * new_user_staff = User belongs to staff flag of the new user to be created. 
 * create = CREATE has been pressed.
 */

require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

if (!(in_array($admin_group_name,(array)$session_groups) 
)) {
 header("Location: login.php");
}
 
if (!empty($change)) $user=reset(array_keys($change));
elseif (!empty($edit)) $user=reset(array_keys($edit));
else $user=$id;


/* Retrieve list of cities */
   $result=pg_exec($cnx, $query="SELECT DISTINCT CITY FROM periods")
	or die("$die $query");
$cities = array();

//pg_fetch_array returns a row in an array form
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$cities[]=$row["city"];
}
 @pg_freeresult($result);

if(!empty($edit)&&empty($periods)||(!empty($id)&&empty($del_period)&&empty($change))) {
 $periods=array();
 
 $die=_("Can't finalize the operation");
   $result=pg_exec($cnx,$query="SELECT * FROM periods"
   ." WHERE uid='$user' ORDER BY init ")
   or die($die);
 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
   if ($row["init"]!="") $row["init"]=date_sql_to_web($row["init"]);
   else $row["init"]="NULL";
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
     
     // PERIOD DELETE
     if (!pg_exec($cnx,$query=
     "DELETE FROM periods WHERE uid='$uid'")) {
       $error=_("Can't finalize the operation");
       break;
     }
     if(!(@pg_exec($cnx,$query="UPDATE users SET admin='".
	 (($administrator[$uid]==1)?"t":"f")
   ."', staff='".
   (($staff[$uid]==1)?"t":"f")
   ."' ".$query_password." WHERE uid='$uid'")
	or die("$die $query"))) {
     	  $error=_("Can't finalize the operation");
     	  break;
    }
$empty=true;

//Setting last information (not related with only one period)
if(($init_date!=""&&$end_date!=""&&$city!="")){
  $empty=false;
  $s=sizeof($periods);
  $periods[$s]["uid"]=$uid;
  $periods[$s]["journey"]=$jour_hours;
  $periods[$s]["init"]=$init_date;
  $periods[$s]["_end"]=$end_date;
  $periods[$s]["city"]=$city;
  $periods[$s]["hour_cost"]=$hour_cost;
}
for ($i=0;$i<sizeof($periods);$i++) {
    $fields=array("uid","journey","init","_end","city","hour_cost");
    $row=array();
    foreach ($fields as $field)
    $row[$field]=$periods[$i][$field];
    $row["uid"]=$uid;
    if ($periods[$i]["init"]=="NULL") {
      $row["init"]="";
    } else  {
      $row["init"]=date_web_to_sql($periods[$i]["init"]);  
    }
    
    if ($periods[$i]["_end"]=="NULL"){
      if(!$empty && $i+1<sizeof($periods)) {
        $row["_end"]=date_web_to_sql($periods[$i+1]["init"]);
        $periods[$i]["_end"]=$periods[$i+1]["init"];
      } else $row["_end"]="";
    } else $row["_end"]=date_web_to_sql($periods[$i]["_end"]);

    foreach ($fields as $field)
     if (empty($row[$field]) && !is_integer($row[$field])) $row[$field]="NULL";
     else $row[$field]="'$row[$field]'";

    // AND FINALLY THEY ARE INSERTED
  if (!empty($periods)) {

    if (!@pg_exec($cnx,$query="INSERT INTO periods ("
     .implode(",",$fields).") VALUES (".implode(",",$row).")")) {
     $error=_("Can't finalize the operation").$query;
     break;
    }
  }
} // end for periods

  // AND ALL THE PROCESS IS DONE

   @pg_exec($cnx,$query="COMMIT TRANSACTION");
  } while(false);

  if (!empty($error)) {

   // Debugging
   $error.="<!-- $query -->";
   @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
   break;
  }

  $confirmation=_("The changes have been saved correctly");
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

if (!empty($add_project)) {
  $result=@pg_exec($cnx,$query="INSERT INTO project_user (uid, name) VALUES ('$user','$added_project')")
    or die("$die $query");
  $confirmation=_("The user has been properly assigned to a project");
  @pg_freeresult($result);
}

if (!empty($remove_project)) {
  $remove_project_keys=array_keys($remove_project);
  $result=@pg_exec($cnx,$query="DELETE FROM project_user WHERE uid='$user' AND name='".$remove_project_keys[0]."'")
    or die("$die $query");
  $confirmation=_("The user has been properly remove from a project");
  @pg_freeresult($result);
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
    $city="corunha";
    $jour_hours="8";
    $new_user_login=trim($new_user_login);
    if (!@pg_exec($cnx,$query=
	"SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
	."BEGIN TRANSACTION; ")) {
      $error=_("Can't finalize the operation");
      break;
    }
    if ((!$result=@pg_exec($cnx,$query="INSERT INTO users (uid,password,admin,staff)"
	." VALUES('$new_user_login',md5('$new_user_password'),'"
	.(($new_user_admin==1)?"t":"f")
  ."','"
  .(($new_user_staff==1)?"t":"f")
  ."')"))||(!$result2=pg_exec($cnx,$query="INSERT INTO periods"
	." (uid,journey,init,_end,city,hour_cost) VALUES ('$new_user_login', '$jour_hours'," 
	."CURRENT_DATE, NULL,'$city', NULL)"))) {
	$error=_("Can't finalize the operation");
    } else {
	$confirmation=_("The user has been created correctly. Remember to update her contract periods!");
	$combo[]=$new_user_login;
    }
    @pg_exec($cnx,$query="COMMIT TRANSACTION");
    if (!empty($error)) {
      // Debugging
      $error.="<!-- $query -->";
      @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
      break;
    }		
    @pg_freeresult($result);
    @pg_freeresult($result2);
  }while(false);
}

// If we are in LDAP mode, new users being in the ldap and not
// in the user table, are created

if ($authentication_mode=="ldap") {
  // Load the users to do some tests
  $result=@pg_exec($cnx,$query="SELECT uid FROM users")
    or die("$die");  
  $users=array();
  while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
    $users[$row["uid"]]=$row["uid"];
  }
  
  @pg_freeresult($result);
  $newu=array();
  foreach (array_keys($session_users) as $u) {
    if (empty($users[$u])) {        
      @pg_exec($cnx,$query2="INSERT INTO users(uid,password,admin,staff) VALUES ('".$u."',NULL,'f','f')");
      $newu[]=$u;
    }
  }
  if (!empty($newu)) {
    $confirmation=_("New users have appeared in the LDAP and have been created in PhpReport. Remember to set up their contract properly!").": ".implode(", ",$newu);
  }
  @pg_freeresult($result);
}

// Load the users to be shown in the combo
$result=@pg_exec($cnx,$query="SELECT uid,admin,staff FROM users ORDER BY uid")
or die("$die $query");	
$users=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$users[]=$row;
}
@pg_freeresult($result);

// Load the available projects to be shown in the combo
$result=@pg_exec($cnx,$query="SELECT id, description FROM projects"
		 ." WHERE activation='t' AND id NOT IN (SELECT name FROM project_user WHERE uid='$user') "
		 ."ORDER BY description")
     or die($die."$query");

$available_projects=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $available_projects[$row['id']]=$row['description'];
}
@pg_freeresult($result);


// Load the projects assigned to the user to be shown in a list
$result=@pg_exec($cnx,$query="SELECT id, p.description, lc.description AS customer, "
		 ."la.description AS area, p.activation FROM projects p "
		 ."JOIN project_user pu ON p.id=pu.name LEFT JOIN label lc "
		 ."ON p.customer=lc.code LEFT JOIN label la ON p.area=la.code "
		 ."WHERE pu.uid='$user' ORDER BY description")
     or die($die."$query");

$assigned_projects=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $assigned_projects[$row['id']]=$row;
}
@pg_freeresult($result);

require_once("include/close_db.php");

$flag="edit"; // To put focus at the Edit button
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
} else $user["uid"]=$id;
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
 <td><?=_("Staff member");?></td>
</tr>
<tr>
 <td><input type="text" name="new_user_login"></td>
 <td><input type="password" name="new_user_password"></td>
 <td><input type="checkbox" name="new_user_admin" value="1"></td>
 <td><input type="checkbox" name="new_user_staff" value="1"></td>
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
   <th><?=_("Staff member");?></th>
 </tr>
 <tr>
   <td style="text-align: center; vertical-align: top;"><?=$user["uid"]?></td>
   <td></td>
   <td><input type="password" name="new_password[<?=$user["uid"]?>]" value=""></td>
<?
  foreach ($users as $u) {
    if ($user["uid"]==$u["uid"]) {
      $user["admin"]=$u["admin"];
      $user["staff"]=$u["staff"];
    }
  }
?>
   <td><input type="checkbox" name="administrator[<?=$user["uid"]?>]" 
		 value="1" <?=($user['admin']=='t'?"checked":"")?>>
   </td>
   <td><input type="checkbox" name="staff[<?=$user["uid"]?>]" 
      value="1" <?=($user['staff']=='t'?"checked":"")?>>
   </td>
 </tr>

<?
?>
</table>

<?
}else {

  foreach ($users as $u) {
    if ($user["uid"]==$u["uid"]) {
      $user["staff"]=$u["staff"];
    }
  }

?>
<table border="0">
 <tr>
   <th>Login</th>
   <th></th>
   <th><?=_("Staff member");?></th>
 </tr>
 <tr>
   <td style="text-align: center; vertical-align: top;"><?=$user["uid"]?></td>
   <td></td>
   <td><input type="checkbox" name="staff[<?=$user["uid"]?>]" 
      value="1" <?=($user['staff']=='t'?"checked":"")?>>
   </td>
 </tr>
<?
}
?>
<br>

<?
$journey=array(
  8=>_("Full time"),
  4=>_("Half time"),
  6=>_("Practice journey"));

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
  <td><?=_("City")?>:</td>
  <td>
    <select name="<?="periods[$i][city]"?>">
	<?=array_to_option(array_values($cities), $periods[$i]["city"], array_values($cities));?>
    </select> 
  </td> 
</tr>
<tr>
  <td><?=_("Cost per hour");?>:</td>
  <td><input type="text" name="<?="periods[$i][hour_cost]"?>" value="<?=$periods[$i]["hour_cost"]?>"></td>
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
  <td><?=_("City")?>:</td>
  <td>
    <select name="city">
	 <?=array_to_option(array_values($cities),NULL, array_values($cities))?>
    </select> 
  </td> 
</tr>
 <tr>
  <td><br>
<?=_("Cost per hour");?>
  </td>
  <td><br>
    <input type="text" name="hour_cost">
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

<br><br><br>

<table border="0" cellspacing="0" cellpadding="0"">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Project assignation")?> 
<!-- end title box -->
</font></td></tr>

<tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="5"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
 <tr>
   <td align="center" colspan="6">
   <select name="added_project"><?=array_to_option(array_values($available_projects),1,array_keys($available_projects))?></select>
   <input type="submit" name="<?="add_project"?>" value="<?=_("Add user to project")?>"></td>
 </tr>
 <tr>
   <td colspan="6"><hr width="75%"></td>
 </tr>
 <?
   if (count($assigned_projects)==0) {
 ?>
 <tr>
   <td colspan="6" align="center"><b><?=_("There are no assigned projects for this user")?></b></td>
 </tr>
 <?
   } else {
  ?>
 <tr>
    <td align="center"><b><?=_("Id")?></b></td>
    <td align="center"><b><?=_("Name")?></b></td>
    <td align="center"><b><?=_("Customer")?></b></td>
    <td align="center"><b><?=_("Area")?></b></td>
    <td align="center"><b><?=_("Activation")?></b></td>
    <td></td>
 </tr>
 <?
     foreach($assigned_projects as $projectId => $project) {
  ?>
 <tr>
   <td align="center"><?=$project["id"]?></td>
   <td align="center"><?=$project["description"]?></td>
   <td align="center"><?=$project["customer"]?></td>
   <td align="center"><?=$project["area"]?></td>
   <td align="center"><?=($project["activation"]=='t')?_("Yes"):_("No")?></td>
   <td align="center"><input type="submit" name="<?="remove_project[$projectId]"?>" value="<?=_("Remove from project")?>"></td>
 </tr>
 <?
     }
   }
  ?>
</font></td></tr></table>
<br>
<!-- end text box -->
</td></tr></table></td></tr></table>

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

