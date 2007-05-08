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


if(!empty($edit)&&empty($customer)||!empty($id)&&empty($new)&&empty($create)) {
 $customer=array();
 if (empty($id)) $id=reset(array_keys($edit));
 $die=_("Can't finalize the operation");
   $result=pg_exec($cnx,$query="SELECT c.id, c.name AS name, c.url AS url, "
		   ."l1.code AS typeid, l2.code AS sectorid "
		   ."FROM label l1 RIGHT JOIN customer c ON l1.code = c.type "
		   ."LEFT JOIN label l2 ON c.sector = l2.code WHERE c.id='$id'")
   or die($die);

 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
   $customer=$row;
 }
 @pg_freeresult($result);
}

if (!empty($change)) {
  $k=array_keys($change);
  $id=$k[0];

  $customer["name"]=$name;
  $customer["url"]=$url;
  $customer["typeid"]=$type;
  $customer["sectorid"]=$sector;

  if (!pg_exec($cnx,$query="UPDATE customer SET "
    ." name='$name', url='$url',type='$type',sector='$sector'"
    ." WHERE id='$id'")||
      !pg_exec($cnx,$query="UPDATE label SET "
    ." description='$name' WHERE code='$id'")) {
    $error=$die;
  } else {
    $confirmation=_("The customer has been updated correctly");
  }  
}

if (!empty($new)) {
  $customer=array();
  $creating=true; //Para q no cambie el boton create por change
}
if (!empty($create)) {
  $creating=true;
  $customer["id"]=$id;
  $customer["name"]=$name;
  $customer["url"]=$url;
  $customer["type"]=$type;
  $customer["sector"]=$sector;

  if (!pg_exec($cnx,$query="INSERT INTO customer"
    ." (id, name, url, type, sector) "
    ."VALUES ('$id', '$name', '$url','$type', '$sector')")
    ||!pg_exec($cnx,$query="INSERT INTO " 
    ."label (type,code,description,activation) "
    ."VALUES ('customer','$id','$name', 't')")) {
    $error=$die;
  } else {
    $creating=false;
    $confirmation=_("The customer has been created correctly");
  }
}

if (!empty($delete)) {
  $k=array_keys($delete);
  $id=$k[0];

  $result=pg_fetch_array(pg_exec($cnx,$query="SELECT * FROM projects WHERE customer='$id'"));
  if (!empty($result)) $error=_("The customer hasn't been deleted: it has related projects");
  else{
    $result=@pg_exec($cnx,$query="DELETE FROM customer WHERE id='$id'")
	or die("$die $query");
    $confirmation=_("The customer has been deleted correctly");
    @pg_freeresult($result);

    $result2=pg_exec($cnx,$query="DELETE FROM label WHERE code='$id'")
        or die("$die $query");
    @pg_freeresult($result2);
  }
}

/* Retrieve list of customers and their associated info */
$result=pg_exec($cnx,$query="SELECT id, name FROM customer ORDER BY id")    
     or die("$die $query");
$customers=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$customers[]=$row;
}
@pg_freeresult($result);


/* Retrieve list of customer types */
$result=@pg_exec($cnx,$query="SELECT code, description FROM label WHERE type='ctype' AND activation='t' ORDER BY code")
     or die("$die $query");

$customer_types=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$customer_types[]=$row;
}
@pg_freeresult($result);


/* Retrieve list of customer sectors */
$result=@pg_exec($cnx,$query="SELECT code, description FROM label WHERE type = 'csector' AND activation='t' ORDER BY code")
     or die("$die $query");

$customer_sectors=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
	$customer_sectors[]=$row;
}
@pg_freeresult($result);


// Load the projects assigned to the customer to be shown in a list
$result=@pg_exec($cnx,$query="SELECT p.id, p.description, p.activation, p.invoice, p.est_hours, l.description AS area "
		 ."FROM projects p JOIN label l ON p.area=l.code WHERE customer = '".$customer['id']."'")
     or die($die."$query");

$assigned_projects=array();
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $assigned_projects[$row['id']]=$row;
}
@pg_freeresult($result);

require_once("include/close_db.php");


/* Build needed view lists */
$customersList=array();
foreach ($customers as $cust) { 
  $customersList[$cust["id"]]=$cust["name"];
}

$typesList=array();
foreach ($customer_types as $type){ 
  $typesList[$type["code"]]=$type["description"];
}

$sectorList=array();
foreach ($customer_sectors as $sector){ 
  $sectorsList[$sector["code"]]=$sector["description"];
}


$flag="edit"; //To put focus at Edit button
$title=_("Customer management");
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
	      <?=_("Customer list")?>
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
		      <td><b><?=_("Customers")?>:</b></td>
		      <td>
			<select name="id" onchange="javascript: document.results.submit();">
			  <?=array_to_option(array_values($customersList), $id, array_keys($customersList))?>
			</select> 
		      </td>
		      <td>
			<input type="submit" name="edit[<?=$customer["id"]?>]" value="<?=_("Edit")?>">
		      </td>
		    </tr>
		    <tr>
		      <input type="submit" name="new" value="<?=_("Create new customer")?>" style="margin-left: auto; margin-right: auto">
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
			  <?=_("Customer edition")?>
			  <!-- end title box -->
			  </font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
			    <table border="0" cellspacing="0" cellpadding="10"><tr><td>
			      <font
			      color="#000000" class="text_box">
			      <!-- text box -->

			      <table border="0">
				<tr>
				  <td><b><?=_("Id")?>:</b></td>
				  <?if ($creating) {?>
				  <td><input type="input" name="id" value="<?=$customer["id"]?>"></td> 
				  <?} else {?>
				  <td><?=$customer["id"]?></td> 
				  <?}?>
				</tr>

				<tr>
				  <td><b><?=_("Name: ")?></b></td>
				  <td><input type="input" name="name" value="<?=$customer["name"]?>"></td> 
				</tr>

				<tr>
				  <td><b><?=_("URL: ")?></b></td>
				  <td><input type="input" name="url" value="<?=$customer["url"]?>"></td> 
				</tr>

				<tr>
				  <td><b><?=_("Type: ")?></b></td>
				  <td>
				    <select name="type">
				      <?=array_to_option(array_values($typesList), $customer["typeid"], array_keys($typesList))?>
				    </select> 
				  </td> 
				</tr>

				<tr>
				  <td><b><?=_("Sector: ")?></b></td>
				  <td>
				    <select name="sector">
				      <?=array_to_option(array_values($sectorsList), $customer["sectorid"], array_keys($sectorsList))?>
				    </select> 
				  </td> 
				</tr>

			      </table>

			      <br>
			      <table border="0" style="margin-left: auto; margin-right: auto">
				<tr>
				  <?if (!$creating) { ?>
				  <td>
				    <input type="submit" name="change[<?=$customer["id"]?>]" value="<?=_("Change")?>">
				  </td>
				  <?}
				  else {?>
				  <td>
				    <input type="submit" name="create" value="<?=_("Create")?>">
				  </td>
				  <?}?>
				  <? if ($authentication_mode=="sql") {  ?>
				  <td>
				    <input type="submit" name="delete[<?=$customer["id"]?>]" value="<?=_("Delete")?>"></td>
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
			  <?=_("Projects assigned")?> 
			  <!-- end title box -->
			  </font></td></tr>

			  <tr><td bgcolor="#FFFFFF" class="text_box">
			    <table border="0" cellspacing="0" cellpadding="5"><tr><td>
			      <font
			      color="#000000" class="text_box">
			      <!-- text box -->
			      <?
			      if (count($assigned_projects)==0) {
			      ?>
			      <tr>
				<td colspan="6" align="center"><b><?=_("There are no assigned projects for this customer")?></b></td>
			      </tr>
			      <?
			      } else {
			      ?>
			      <tr>
				<td align="center"><b><?=_("Id")?></b></td>
				<td align="center"><b><?=_("Name")?></b></td>
				<td align="center"><b><?=_("Invoice")?></b></td>
				<td align="center"><b><?=_("Estimated hours")?></b></td>
				<td align="center"><b><?=_("Area")?></b></td>
				<td align="center"><b><?=_("Activation")?></b></td>
			      </tr>
			      <?
			      foreach($assigned_projects as $projectId => $project) {
			      ?>
			      <tr>
				<td align="center"><?=$project["id"]?></td>
				<td align="center"><?=$project["description"]?></td>
				<td align="center"><?=$project["invoice"]?></td>
				<td align="center"><?=$project["est_hours"]?></td>
				<td align="center"><?=$project["area"]?></td>
				<td align="center"><?=($project["activation"]=='t')?_("Yes"):_("No")?></td>
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
