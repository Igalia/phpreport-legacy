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

$die=_("Can't finalize the operation");

/* if (multi_in_array($board_group_names,(array)$session_groups)) { */
/*   // If the user in not in a group that belongs to the board members, she can't */
/*   // access the information */
/*   header("Location: login.php"); */
/* } */

/* RETRIEVE INFORMATION FROM DATABASE */

/* User info */
$project=null;
$result=@pg_exec($cnx, $query="SELECT uid, admin, staff FROM users WHERE uid = '".$id."'")
        or die("$die $query");	
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
$user=$row;
@pg_freeresult($result);


/* RETRIEVE DATA FOR PERSON/TASK TABLE */

$result=@pg_exec($cnx,$query="SELECT fest,city FROM holiday ORDER BY fest")
     or die($die);
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $val=date_sql_to_web($row["fest"]);
  $arrayDMA=date_web_to_arrayDMA($val);
  $holidays[$row["city"]][]=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
}
@pg_freeresult($result);

$type=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='type'")
     or die($die);
$type_consult=array();
for ($i=0;$row=@pg_fetch_array($type,$i,PGSQL_ASSOC);$i++) {
  $type_consult[]=$row["code"];
}
@pg_freeresult($type);

/* Retrieve projects which the user has worked in */
$projects=@pg_exec($cnx,$query="SELECT DISTINCT CASE name WHEN '' THEN 'unknown_project' ELSE name END FROM task WHERE uid = '".$id."' ORDER BY name ASC")
     or die($die);
$projects_consult=array();
for ($i=0;$row=@pg_fetch_array($projects,$i,PGSQL_ASSOC);$i++) {
  $projects_consult[]=$row["name"];
}
@pg_freeresult($projects);

$data=@pg_exec($cnx,$query="SELECT type, CASE name WHEN '' THEN 'unknown_project' ELSE name END, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE uid = '".$id."' GROUP BY type, name ORDER BY name ASC")
     or die($die);
$data_consult=array();
for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
  $data_consult[]=$row;
}

@pg_freeresult($data);
$row_index="name";
$row_index_trans=_("Project");
$col_index="type";
$col_index_trans=_("Type");

foreach((array)$data_consult as $row2) {
  $a[$row2[$row_index]][$row2[$col_index]]=$row2["add_hours"];
  $add_hours_row[$row2[$row_index]]+=$row2["add_hours"];
  $add_hours_col[$row2[$col_index]]+=$row2["add_hours"];
  $add_hours+=$row2["add_hours"];
} 

foreach ((array)$projects_consult as $name) {
  $percent_row[$name]+=@($add_hours_row[$name]*100/$add_hours);
  $percent_row["tot"]+=@($add_hours_row[$name]*100/$add_hours);
}
foreach ((array)$type_consult as $type) {
  $percent_col[$type]+=@($add_hours_col[$type]/$add_hours*100);
  $percent_col["tot"]+=@($add_hours_col[$type]/$add_hours*100);
}

require_once("include/close_db.php");

$title=_("User details");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
  <tr>
    <td style="text-align: center; vertical-align: top;">

      <center>

      <table border="0" cellspacing="0" cellpadding="0">
	<tr>
	  <td bgcolor="#000000">
	    <table border="0" cellspacing="1" cellpadding="0" width="100%">
	      <tr>
		<td bgcolor="#000000" class="title_box">
		  <font color="#FFFFFF" class="title_box">
		  <!-- title box -->
		  <?=_("User details")?>
		  <!-- end title box -->
		  </font>
		</td>
	      </tr>
	      <tr>
		<td bgcolor="#FFFFFF" class="text_box">
		  <table border="0" cellspacing="0" cellpadding="10">
		    <tr>
		      <td>
			<table border="0">
			  <tr>
			    <td align="right"><b><?=_("User Id")?>:</b></td>
			    <td align="left"><?=$user["uid"]?></td> 
			  </tr>				
			  <tr>
			    <td align="right"><b><?=_("Admin")?>:</b></td>
			    <td align="left">
			      <?=($user['admin']=='t'?_("Yes"):_("No"))?>
			    </td>
			  </tr>
			  <tr>
			    <td align="right"><b><?=_("Staff")?>:</b></td>
			    <td align="left">
			      <?=($user['staff']=='t'?_("Yes"):_("No"))?>
			    </td>
			  </tr>
			</table>
		      </td>
		    </tr>
		  </table>
		</td>		
	      </tr>
	    </table>
	  </td>
	</tr>
	<tr>
	  <td height="15px"><!-- spacing cell --></td>
	</tr>
      </table>

      
      <!-- PERSON/TASK TABLE -->

      <!-- box -->
      <table border="0" cellspacing="0" cellpadding="0">
	<tr>
	  <td bgcolor="#000000">
	    <table border="0" cellspacing="1" cellpadding="2" width="100%">
	      <!-- title box -->
	      <tr>
		<td class="title_table"><?=$row_index_trans?></td>
		<td colspan="100%"  class="title_table"><?=$col_index_trans?></td>
	      </tr>
	      
	      <tr>
		<td bgcolor="#FFFFFF" class="title_box"></td>
		<?
		foreach ((array)$type_consult as $col) {
		?>
		<td bgcolor="#FFFFFF" class="title_box">
                  <a href="projectdetails.php?id=<?=$col?>"><?=$col?></a>
		</td>
		<?
		}
		?>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Percent")?></td>
	      </tr>
	      <?
	      $odd_even = 0; /* start with odd rows */
	      foreach ((array)$projects_consult as $row) {
	      ?>
	      <tr class="<?=($odd_even==0)?odd:even?>">
		 <td bgcolor="#FFFFFF" class="title_box">
                 <? 
		 if ($row=="unknown_project") { 
		   echo(_("unknown project"));
		 } else {
                 ?>
		   <a href="projectdetails.php?id=<?=$row?>"><?=$row?></a>
		 <?
		 }
		 ?>
		 </td>
		<? 
		foreach ((array)$type_consult as $col) {
		if ($a[$row][$col]==""){
		?>
		<td bgcolor="#FFFFFF" class="text_data">&nbsp;</td>
		<?    
		} else if ($a[$col]==""&&$a[""][$col]!=""){
		?>
		<td bgcolor="#FFFFFF" class="text_data"><?=sprintf("%01.2f",$a[$row][$col])?></td>
		<?
		} else {
		?>
		<td bgcolor="#FFFFFF" class="text_data"><?=sprintf("%01.2f",$a[$row][$col])?></td>
		<?
		}
		}
		?>
		<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_row[$row])?></b></td>
		
		<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row[$row])?></b></td>
		
	      </tr>
	      <?
                $odd_even = ($odd_even+1)%2; /* update odd_even counter */  
	      }
	      ?>
	      <tr>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
		<?
		foreach ((array)$type_consult as $col) {
		?>
		<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_col[$col])?></b></td>
		<?
		}
		?>
		<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours)?></b></td>
		<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row["tot"])?></b></td>
	      </tr>
	      <tr>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Percent")?></td>
		<?
		foreach ((array)$type_consult as $col) {
		?>
		<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_col[$col])?></b></td>
		<?
		}
		?>
		<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_col["tot"])?></b></td>
		<td bgcolor="#FFFFFF" class="text_percent">&nbsp;</td>
	      </tr>
	      
	      
	      <!-- end title box -->
	    </table>
	    
	  </td>
	</tr>
	<tr>
	  <td height="15px"><!-- spacing cell --></td>
	</tr>
      </table>
      <!-- end box -->
      </center>

      <!-- Line points chart -->
      <a href="graphicbuilder.php?id=<?=$id?>&type=linepoints&flag=PERSON&title=User+evolution&width=1600&height=1200">
        <img class="noborder" src="graphicbuilder.php?id=<?=$id?>&type=linepoints&flag=PERSON&title=User+evolution" />
      </a>
      <br>

      <!-- Pie chart -->
      <a href="graphicbuilder.php?id=<?=$id?>&type=pie&flag=PERSON&title=User+dedication&width=1600&height=1200">
        <img class="noborder" src="graphicbuilder.php?id=<?=$id?>&type=pie&flag=PERSON&title=User+dedication" />
      </a>
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
