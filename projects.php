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

require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

if (!(in_array($admin_group_name,(array)$session_groups) 
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
  $project["customer"]=$customer;   
  $project["area"]=$area;   
  $project["activation"]=checkbox_to_sql($activation);
  $project["est_hours"]=$est_hours;
  $project["invoice"]=$invoice;
  $project["init"]=$init;
  $project["_end"]=$end;   
  $project["type"]=$type;
 
  $init=date_web_to_quoted_sql($init);
  $end=date_web_to_quoted_sql($end);  

  if (!pg_exec($cnx,$query="UPDATE projects SET "
    ." description='$description',activation='".(($activation==1)?"t":"f")."',"
    ." customer='$customer',area='$area',est_hours='$est_hours',invoice='$invoice',"
    ."init=".$init.", _end=".$end.", type=".(($type != 'unknown_type')?("'$type'"):"NULL")
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
  $project["customer"]=$customer;   
  $project["area"]=$area;   
  $project["activation"]=checkbox_to_sql($activation);
  $project["est_hours"]=$est_hours;
  $project["invoice"]=$invoice;
  $project["init"]=$init;
  $project["_end"]=$end; 
  $project["type"]=$type; 
  $init=date_web_to_quoted_sql($init);
  $end=date_web_to_quoted_sql($end);
  $activation=checkbox_to_sql($activation);

  if (!pg_exec($cnx,$query="INSERT INTO projects"
    ." (id,description,customer,area,activation,est_hours,invoice,init,_end,type) "
    ."VALUES ('$id', '$description','$customer','$area','$activation',"
    ."'$est_hours','$invoice', $init, $end, '$type')")
    ||!pg_exec($cnx,$query="INSERT INTO " 
    ."label (type,code,description,activation) "
    ."VALUES ('name','$id','$description','$activation')")) {
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
  if (!empty($result)) $error=_("The project hasn't been deleted: it has related tasks");
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

if (!empty($add_users)) {
  foreach($added_users as $added_user) {
    $result=@pg_exec($cnx,$query="INSERT INTO project_user (uid, name) VALUES ('$added_user', '".$project['id']."')")
      or die("$die $query");
  }
  $confirmation=_("Users have been properly assigned to a project");
  @pg_freeresult($result);
}

if (!empty($remove_users)) {
  foreach($removed_users as $removed_user) {
  $result=@pg_exec($cnx,$query="DELETE FROM project_user WHERE uid='$removed_user' AND name='".$project['id']."'")
    or die("$die $query");
  }
  $confirmation=_("Users have been properly removed from a project");
  @pg_freeresult($result);
}

$result=@pg_exec($cnx,$query="SELECT id FROM projects ORDER BY id")
or die("$die $query");	
$users=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$projects[]=$row;
}
@pg_freeresult($result);

/* Retrieve list of customers and their associated info */
$result=pg_exec($cnx,$query="SELECT id, name FROM customer ORDER BY id")    
     or die("$die $query");
$customers=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$customers[]=$row;
}
@pg_freeresult($result);

/* Retrieve list of project areas */
$result=@pg_exec($cnx,$query="SELECT code, description FROM label WHERE type='parea' AND activation='t' ORDER BY code")
     or die("$die $query");

$project_areas=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$project_areas[]=$row;
}
@pg_freeresult($result);

/* Retrieve list of project types */
$result=@pg_exec($cnx,$query="SELECT code, description FROM label WHERE type='ptype' AND activation='t' ORDER BY code")
     or die("$die $query");

$project_types=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$project_types[]=$row;
}
@pg_freeresult($result);

/* Build needed view lists */
$customersList=array();
foreach ($customers as $cust) { 
  $customersList[$cust["id"]]=$cust["name"];
}

$areasList=array();
foreach ($project_areas as $area){ 
  $areasList[$area["code"]]=$area["description"];
}

$ptypesList=array();
$ptypesList[unknown_type]="(Type not set)";
foreach ($project_types as $ptype){ 
  $ptypesList[$ptype["code"]]=$ptype["description"];
}

// Load the available users to be shown in the combo
// We check information against periods table because we want only 
// active users and not inactive ones
$result=@pg_exec($cnx,$query="SELECT DISTINCT uid FROM periods "
		 ." WHERE uid NOT IN (SELECT uid FROM project_user WHERE name='".$project['id']."') "
		 ."ORDER BY uid")
     or die($die."$query");

$available_users=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $available_users[]=$row['uid'];
}
@pg_freeresult($result);


// Load the projects assigned to the user to be shown in a list
$result=@pg_exec($cnx,$query="SELECT u.uid FROM users u "
		 ."JOIN project_user pu ON u.uid=pu.uid "
		 ."WHERE pu.name='".$project['id']."' ORDER BY uid")
     or die($die."$query");

$assigned_users=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $assigned_users[]=$row["uid"];
 }
@pg_freeresult($result);

require_once("include/close_db.php");

$flag="edit"; //To put focus at Edit button
$title=_("Project management");
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
	      <?=_("Project list")?>
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
				  <td><b><?=_("Description")?>:</b></td>
				  <td><input type="input" name="description" value="<?=$project["description"]?>"></td> 
				</tr>

				<tr>
				  <td><b><?=_("Customer")?>:</b></td>
				  <td>
				    <select name="customer">
				      <?=array_to_option(array_values($customersList), $project["customer"], array_keys($customersList))?>
				    </select> 
				  </td> 
				</tr>

				<tr>
				  <td><b><?=_("Area")?>:</b></td>
				  <td>
				    <select name="area">
				      <?=array_to_option(array_values($areasList), $project["area"], array_keys($areasList))?>
				    </select> 
				  </td> 
				</tr>

				<tr>
				  <td><b><?=_("Project type")?>:</b></td>
				  <td>
				    <select name="type">
				      <?=array_to_option(array_values($ptypesList), $project["type"], array_keys($ptypesList))?>
				    </select> 
				  </td> 
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
				      <?=_("Start date");?>:
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

                 		  <?if (!$creating) {?>
				  <br><br><br>

				  <table border="0" cellspacing="0" cellpadding="0"">
				    <tr><td bgcolor="#000000">
				      <table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
					<td bgcolor="#000000" class="title_box"><font
					  color="#FFFFFF" class="title_box">
					  <!-- title box -->
					  <?=_("Users assignation")?> 
					  <!-- end title box -->
					  </font></td></tr>

					  <tr><td bgcolor="#FFFFFF" class="text_box">
					    <table border="0" cellspacing="0" cellpadding="5"><tr><td>
					      <font
					      color="#000000" class="text_box">
					      <!-- text box -->
					      <tr>
						<td align="center">
						  <table>
						    <tr>
						      <td rowspan="3" align="center">
							
							<b><?=_("Available users")?>:</b><br><br>
							<select multiple="multiple" size="10" name="added_users[]">
							  <?=array_to_option(array_values($available_users),1,array_values($available_users))?>
							</select>
						      </td>
						      <td></td> 
						      <td rowspan="3" align="center">
							<b><?=_("Assigned users")?>:</b><br><br>
							<select multiple="multiple" size="10" name="removed_users[]">
							  <?=array_to_option(array_values($assigned_users),1,array_values($assigned_users))?>
							</select>
						      </td>	 
						    </tr>
						    <tr><td>
						      <br>
						      <input type="submit" name="<?="add_users"?>" value=">>">
						      <br><br>
						      <input type="submit" name="<?="remove_users"?>" value="<<">
						    </td></tr>
						    <tr><td></td></tr>
						  </table>
						</td>
					      </tr>

					      </font></td></tr></table>
					      <br>
					      <!-- end text box -->
					    </td></tr></table></td></tr></table>

					    <?
					    }
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
