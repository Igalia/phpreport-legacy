<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2009 Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Andrés Maneiro Boga <amaneiro@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  Jacobo Aragunde Pérez <jaragunde@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
//  María Fernández Rodríguez <maria.fdez.rguez@gmail.com>
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

/* if (!multi_in_array($board_group_names,(array)$session_groups)) { */
/*    // If the user in not in a group that belongs to the board members, she can't */
/*    // access the project information */
/*    header("Location: login.php"); */
/* } */

$die=_("Can't finalize the operation");

/* RETRIEVE INFORMATION FROM DATABASE */

/* Project info and estimation of cost */
$project=null;
$result=@pg_exec($cnx, $query="SELECT id, description, type, activation, init, _end, invoice, est_hours "
		              ."FROM projects WHERE id = "."'$id'")
        or die("$die $query");	
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
$project=$row;
@pg_freeresult($result);

/* Initial date: date of the first task for this project */
$project_init="";
$result=@pg_exec($cnx, $query="SELECT MIN(_date) AS init_date FROM task WHERE name = '".$id."'")
        or die("$die $query");
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
$project_init=$row["init_date"];
@pg_freeresult($result);

/* End date: date of the last task for this project */
$project_end="";
$result=@pg_exec($cnx, $query="SELECT MAX(_date) AS end_date FROM task WHERE name = '".$id."'")
        or die("$die $query");
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
$project_end=$row["end_date"];
@pg_freeresult($result);

/* Spent hours (all) */
$all_hours=0;
$result=@pg_exec($cnx, $query="SELECT COALESCE(SUM(_end - init)/60.0, 0) AS all_hours from task WHERE name = "."'$id'")
        or die("$die $query");	
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
$all_hours=$row["all_hours"];
@pg_freeresult($result);

/* Spent hours (pex) */
$pex_hours=0;
$result=@pg_exec($cnx, $query="SELECT COALESCE(SUM(_end - init)/60.0, 0) AS pex_hours from task WHERE name = "."'$id' AND type = 'pex'")
        or die("$die $query");	
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
$pex_hours=$row["pex_hours"];
@pg_freeresult($result);

/* Spent hours (pin) */
$pin_hours=0;
$result=@pg_exec($cnx, $query="SELECT COALESCE(SUM(_end - init)/60.0, 0) AS pin_hours from task WHERE name = "."'$id' AND type = 'pin'")
        or die("$die $query");	
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
$pin_hours=$row["pin_hours"];
@pg_freeresult($result);

/* CALCULATE NEEDED VALUES */

/* Init and end date (web format) */
$project_init=date_sql_to_web($project_init);
$project_end=date_sql_to_web($project_end);

/* Absolute values */
$pex_pin_hours = $pex_hours + $pin_hours;
$est_cost_hour = ($project["est_hours"] == 0)?0:$project["invoice"]/$project["est_hours"];
$actual_cost_hour_all = ($all_hours == 0)?0:$project["invoice"]/$all_hours;
$actual_cost_hour_pex = ($pex_hours == 0)?0:$project["invoice"]/$pex_hours;
$actual_cost_hour_pex_pin = ($pex_pin_hours == 0)?0:$project["invoice"]/$pex_pin_hours;

/* Desviations */
$desv_hours_all = $all_hours - $project["est_hours"];
$desv_hours_pex = $pex_hours - $project["est_hours"];
$desv_hours_pex_pin = $pex_pin_hours - $project["est_hours"];
$desv_cost_hour_all = $actual_cost_hour_all - $est_cost_hour;
$desv_cost_hour_pex = $actual_cost_hour_pex - $est_cost_hour;
$desv_cost_hour_pex_pin = $actual_cost_hour_pex_pin - $est_cost_hour;


/* RETRIEVE DATA FOR PERSON/TASK TABLE */

/* validate dates (DD/MM/YYYY) */
if(($init!="" && !validate_date_web($init)) || ($end!="" && !validate_date_web($end))) {
  $error=_("Incorrect date format, should be DD/MM/YYYY");
}

/* Set default dates if they were not previously set */
if ($init=="" || !validate_date_web($init)) {
  $init = $project_init;
}
if ($end=="" || !validate_date_web($end)) {
  $end = $project_end;
}


/* validate valid range (from past to future) */
if(cmp_web_dates($init, $end) > 0) {
  $error=_("End date must be greater than start date");
}

/* redundant check, since dates are ALWAYS set before reaching this point */
if ($init==""&&$end=="") {
  $error=_("Dates can't be void");
}

/* retrieve project types */
$result=@pg_exec($cnx,$query="SELECT code, description FROM label WHERE type='ptype'")
        or die($die);
$project_types=array("" => _("Unassigned"));
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $project_types[$row["code"]]=$row["description"];
}
@pg_freeresult($result);

/* define limits to retrieve data for the specified interval */
$lowest_date = date_web_to_sql($init);
$uppest_date = date_web_to_sql($end);    

/* Retrieve users who work/worked in this project during the specified interval */
$users=@pg_exec($cnx,$query="SELECT DISTINCT uid FROM task WHERE ( _date >= '"
		.$lowest_date."'::date AND _date <= '".$uppest_date."'::date ) AND name = '"
		.$id."' ORDER BY uid ASC") or die($die);
$users_consult=array();
for ($i=0;$row=@pg_fetch_array($users,$i,PGSQL_ASSOC);$i++) {
  $users_consult[]=$row["uid"];
}
@pg_freeresult($users);

if(empty($type_of_table))
  $type_of_table="type";

$work_consult=array();
if($type_of_table=="type")
{
  $type_of_table_trans=_("Type");

  /* Retrieve project dedication during the specified interval */
  /* user/type table */
  $data=@pg_exec($cnx,$query="SELECT type, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( _date >= '"
	       .$lowest_date."'::date AND _date <= '".$uppest_date."'::date ) AND name = '"
	       .$id."' GROUP BY type, uid ORDER BY uid ASC") or die($die);
  $data_consult=array();
  for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
    $data_consult[]=$row;
  }
  @pg_freeresult($data);

  /* retrieve work types */
  $type=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='type'")
        or die($die);
  for ($i=0;$row=@pg_fetch_array($type,$i,PGSQL_ASSOC);$i++) {
    $work_consult[]=$row["code"];
  }
  @pg_freeresult($type);

}
if($type_of_table=="story")
{
  $type_of_table_trans=_("Story");

  /* Retrieve project dedication during the specified interval */
  /* user/story table */
  $data=@pg_exec($cnx,$query="SELECT story, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( _date >= '"
	       .$lowest_date."'::date AND _date <= '".$uppest_date."'::date ) AND name = '"
	       .$id."' GROUP BY story, uid ORDER BY uid ASC") or die($die);
  $data_consult=array();
  for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
    $data_consult[]=$row;
  }
  @pg_freeresult($data);
  
  /* retrieve distinct stories in specified interval */
  $story=@pg_exec($cnx,$query="SELECT DISTINCT story FROM task WHERE ( _date >= '"
		.$lowest_date."'::date AND _date <= '".$uppest_date."'::date ) AND name = '"
		.$id."' ORDER BY story ASC")
        or die($die);
  for ($i=0;$row=@pg_fetch_array($story,$i,PGSQL_ASSOC);$i++) {
    if($row["story"]!="")
      $work_consult[]=$row["story"];
    else
      $work_consult[]=_("(unassigned)");
  }
  @pg_freeresult($story);
}

if(!empty($invert_cols_and_rows)&&($invert_cols_and_rows=="yes")) {
  $row_field=$work_consult;
  $column_field=$users_consult;
  $row_index=$type_of_table;
  $col_index="uid";
  $row_index_trans=$type_of_table_trans;
  $col_index_trans=_("User");
} else {
  $row_field=$users_consult;
  $column_field=$work_consult;
  $row_index="uid";
  $col_index=$type_of_table;
  $row_index_trans=_("User");
  $col_index_trans=$type_of_table_trans;
}

foreach((array)$data_consult as $row2) {
  if($row2["uid"]=="")   $row2["uid"]=_("(empty)");
  if($row2["story"]=="") $row2["story"]=_("(unassigned)");

  $a[$row2[$row_index]][$row2[$col_index]]=$row2["add_hours"];
  $add_hours_row[$row2[$row_index]]+=$row2["add_hours"];
  $add_hours_col[$row2[$col_index]]+=$row2["add_hours"];
  $add_hours+=$row2["add_hours"];
} 
foreach ((array)$row_field as $row) {
  $percent_row[$row]+=@($add_hours_row[$row]*100/$add_hours);
  $percent_row["tot"]+=@($add_hours_row[$row]*100/$add_hours);
}
foreach ((array)$column_field as $col) {
  $percent_col[$col]+=@($add_hours_col[$col]/$add_hours*100);
  $percent_col["tot"]+=@($add_hours_col[$col]/$add_hours*100);
}

/* SET NEEDED DATA FOR DRAWING GRAPHS */
$chart_types = array("area" => _("area"),  "bars" => _("bars"),  "linepoints" => _("linepoints"), "lines" => _("lines"),  
		     "pie" => _("pie"),  "points" => _("points"), "squared" => _("squared"), "thinbarline" => _("thinbarline"));

/* retrieve GET param => _("thinbarline"));

/* retrieve GET param, or set it to "bars" (default) if still not set, */
/* or if the selected type is not available from previous defined list      */
$ctype = "";
if (isset($chart_type) && in_array($chart_type, array_keys($chart_types))) {
  $ctype = $chart_type;
} else {
  $ctype = "bars"; /* default */
}


require_once("include/close_db.php");

$title=_("Project details");
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
		  <?=_("Project details")?>
		  <!-- end title box -->
		  </font>
		</td>
	      </tr>
	      <tr>
		<td bgcolor="#FFFFFF" class="text_box">
		  <table border="0" cellspacing="0" cellpadding="10">
		    <tr>
		      <td>
			<table class="innertable" cellspacing="1" cellpadding="2">
			  <tr>
			    <td colspan="2" class="title_table">
			      <?=_("Data")?>
			    </td>
			  </tr>
			  <tr>
			    <td class="text_data">
			      <table border="0">
				<tr>
				  <td align="right"><b><?=_("Name")?>:</b></td>
				  <td align="left"><?=$project["id"]?></td> 
				</tr>				
				<tr>
				  <td align="right"><b><?=_("Description: ")?></b></td>
				  <td align="left"><?=$project["description"]?></td> 
				</tr>				
				<tr>
				  <td align="right"><b><?=_("Project type: ")?></b></td>
				  <td align="left"><?=$project_types[$project["type"]]?></td> 
				</tr>
				<tr>
				  <td align="right"><b><?=_("Activation")?>:</b></td>
				  <td align="left">
				    <?=($project["activation"]=="t")?_("Yes"):_("No")?>
				  </td>
				</tr>
			      </table>
			    </td>
			    <td class="text_data">
			      <table border="0">
				<tr>
				  <td align="right">
				    <b><?=_("Invoice");?>:</b>			      
				  </td>
				  <td align="left">
				    <?=sprintf("%01.2f", $project["invoice"])?>
				  </td> 
				</tr>
				<tr>
				  <td align="right">
				    <b><?=_("Estimated cost per hour");?>:</b>
				  </td>
				  <td align="left">
				    <?=sprintf("%01.2f", $est_cost_hour)?>
				  </td> 
				</tr>
				<tr>
				  <td align="right">
				    <b><?=_("Estimated hours");?>:</b>
				  </td>
				  <td align="left">
				    <?=sprintf("%01.2f", $project["est_hours"])?>
				  </td> 
				</tr>
			      </table>
			    </td>
			  </tr>
			</table>
		      </td>
		    </tr>

		    <tr>
		      <td class="text_box">
			<table class="innertable" cellspacing="1" cellpadding="2">
			  <tr>
			    <td colspan="1" class="title_table">
			      <?=_("Type")?>
			    </td>
			    <td colspan="3" class="title_table">
			      <?=_("Hours")?>
			    </td>
			  </tr>
			  <tr>
			    <td rowspan="2" class="title_box">
			      &nbsp;
			    </td>
			    <td rowspan="2" class="title_box">
			      <?=_("Worked hours")?>
			    </td>
			    <td colspan="2" class="title_box">
			      <?=_("Desviation")?>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("Total")?>
			    </td>
			    <td class="title_box">
			      <?=_("Percent")?>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("all")?>
			    </td>
			    <td class="text_data">
			      <?=sprintf("%01.2f", $all_hours)?>
			    </td>
			    <td class="text_result">
			      <b><?=sprintf("%01.2f", $desv_hours_all)?></b>
			    </td>
			    <td class="text_percent">
			      <b><?=sprintf("%01.2f", ($project["est_hours"]==0)?0:100*$desv_hours_all/$project["est_hours"])?></b>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("pex")?>
			    </td>
			    <td class="text_data">
			      <?=sprintf("%01.2f", $pex_hours)?>
			    </td>
			    <td class="text_result">
			      <b><?=sprintf("%01.2f", $desv_hours_pex)?></b>
			    </td>
			    <td class="text_percent">
			      <b><?=sprintf("%01.2f", ($project["est_hours"]==0)?0:100*$desv_hours_pex/$project["est_hours"])?></b>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("pex+pin")?>
			    </td>
			    <td class="text_data">
			      <?=sprintf("%01.2f", $pex_pin_hours)?>
			    </td>
			    <td class="text_result">
			      <b><?=sprintf("%01.2f", $desv_hours_pex_pin)?></b>
			    </td>
			    <td class="text_percent">
			      <b><?=sprintf("%01.2f", ($project["est_hours"]==0)?0:100*$desv_hours_pex_pin/$project["est_hours"])?></b>
			    </td>
			  </tr>
			</table>
		      </td>
		    </tr>

		    <tr>
		      <td class="text_box">
			<table class="innertable" cellspacing="1" cellpadding="2">
			  <tr>
			    <td colspan="1" class="title_table">
			      <?=_("Type")?>
			    </td>
			    <td colspan="3" class="title_table">
			      <?=_("Invoicing (cost per hour)")?>
			    </td>
			  </tr>
			  <tr>
			    <td rowspan="2" class="title_box">
			      &nbsp;
			    </td>
			    <td rowspan="2" class="title_box">
			      <?=_("Actual/current invocing")?>
			    </td>
			    <td colspan="2" class="title_box">
			      <?=_("Desviation")?>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("Total")?>
			    </td>
			    <td class="title_box">
			      <?=_("Percent")?>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("all")?>
			    </td>
			    <td class="text_data">
			      <?=sprintf("%01.2f", $actual_cost_hour_all)?>
			    </td>
			    <td class="text_result">
			      <b><?=sprintf("%01.2f", $desv_cost_hour_all)?></b>
			    </td>
			    <td class="text_percent">
			      <b><?=sprintf("%01.2f", ($est_cost_hour==0)?0:100*$desv_cost_hour_all/$est_cost_hour)?></b>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("pex")?>
			    </td>
			    <td class="text_data">
			      <?=sprintf("%01.2f", $actual_cost_hour_pex)?>
			    </td>
			    <td class="text_result">
			      <b><?=sprintf("%01.2f", $desv_cost_hour_pex)?></b>
			    </td>
			    <td class="text_percent">
			      <b><?=sprintf("%01.2f", ($est_cost_hour==0)?0:100*$desv_cost_hour_pex/$est_cost_hour)?></b>
			    </td>
			  </tr>
			  <tr>
			    <td class="title_box">
			      <?=_("pex+pin")?>
			    </td>
			    <td class="text_data">
			      <?=sprintf("%01.2f", $actual_cost_hour_pex_pin)?>
			    </td>
			    <td class="text_result">
			      <b><?=sprintf("%01.2f", $desv_cost_hour_pex_pin)?></b>
			    </td>
			    <td class="text_percent">
			      <b><?=sprintf("%01.2f", ($est_cost_hour==0)?0:100*$desv_cost_hour_pex_pin/$est_cost_hour)?></b>
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
	  <td height="35px"><!-- spacing cell --></td>
	</tr>
      </table>

      <!-- DATE SELECTION FORM -->

      <table border="0" cellspacing="0" cellpadding="0">
	<tr>
	  <td bgcolor="#000000">
	    <table border="0" cellspacing="1" cellpadding="0" width="100%">
	      <tr>
		<td bgcolor="#000000" class="title_box">
		  <font color="#FFFFFF" class="title_box">
		  <!-- title box -->
		  <?=_("Period to check")?>
		  <!-- end title box -->
		  </font>
		</td>
	      </tr>
	      <tr>
		<td bgcolor="#FFFFFF" class="text_box">
		  <form name="results" method="post" onreset="this.init.value='<?=$project_init?>'; this.end.value='<?=$project_end?>'; return false;">
		  <table>
		    <tr>
		      <td>
			<?=_("Start date");?>:
		      </td>
		      <td>
			<input type="text" name="init" value="<?=$init?>">
		      </td> 
		    </tr>
		    <tr>
		      <td>
			<?=_("End date");?>:
		      </td>
		      <td>
			<input type="text" name="end" value="<?=$end?>">
		      </td> 
		    </tr>
		    <tr>
		      <td align="center" colspan="2">
			<input type="reset" name="reset" value="<?=_("Set defaults")?>">
			<input type="submit" name="view" value="<?=_("View")?>">
		      </td> 
		    </tr>
		  </table>
		  <!-- set data from the other form to send it when this one is submitted -->
		  <input type="hidden" name="type_of_table" value="<?=$type_of_table?>">
		  <input type="hidden" name="invert_cols_and_rows" value="<?=$invert_cols_and_rows?>">
		  <input type="hidden" name="chart_type" value="<?=$ctype?>">
		  </form>		  
		</td>
	      </tr>
	    </table>
	  </td>
	</tr>
	<tr>
	  <td height="15px"><!-- spacing cell --></td>
	</tr>
      </table>
      

      <!-- PERSON/TASK-STORY TABLE -->

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
	      
	      <!-- column names -->
	      <tr>
		<td bgcolor="#FFFFFF" class="title_box"></td>
		<?
		foreach ((array)$column_field as $col) {
		?>
		<td bgcolor="#FFFFFF" class="title_box">
		  <?=$col?>
		</td>
		<?
		}
		?>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Percent")?></td>
	      </tr>

	      <?
              /* loop to print each row */
	      $odd_even = 0; /* start with odd rows */
              foreach ((array)$row_field as $row) {
	      ?>
	      <tr class="<?=($odd_even==0)?odd:even?>">
                <!-- row name -->
		<td bgcolor="#FFFFFF" class="title_box">
              	  <!--<a href="userdetails.php?id=<?=$row?>">-->
                    <?=$row?>
              	  <!--</a>-->
		</td>
		<? 
            	/* loop to print the colums for this row */
		foreach ((array)$column_field as $col) {
              	/* empty field */
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

              <!-- total result row -->
	      <tr>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
		<?
		foreach ((array)$column_field as $col) {
		?>
		<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_col[$col])?></b></td>
		<?
		}
		?>
		<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours)?></b></td>
		<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row["tot"])?></b></td>
	      </tr>
              <!-- percent row -->
	      <tr>
		<td bgcolor="#FFFFFF" class="title_box"><?=_("Percent")?></td>
		<?
		foreach ((array)$column_field as $col) {
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
      </table>
      <!-- end box -->

      <!-- TABLE SELECTION FORM -->

      <table border="0" cellspacing="0" cellpadding="0">
	<tr>
	  <td height="5px"><!-- spacing cell --></td>
	</tr>
	<tr>
	  <td>
		<form name="table_type" method="post">
		  <!-- set data from the other forms to send it when this one is submitted -->
		  <input type="hidden" name="chart_type" value="<?=$ctype?>">
		  <input type="hidden" name="init" value="<?=$init?>">
		  <input type="hidden" name="end" value="<?=$end?>">
		  <input type="hidden" name="invert_cols_and_rows" value="<?=$invert_cols_and_rows?>">
		  <?php
		  if($type_of_table=="type")
		  {
		  ?>
			<input type="hidden" name="type_of_table" value="story">
			<input type="submit" name="view" value="<?=_("Change to user/story table")?>">
		  <?php
		  }
		  if($type_of_table=="story")
		  {
		  ?>
			<input type="hidden" name="type_of_table" value="type">
			<input type="submit" name="view" value="<?=_("Change to user/task type table")?>">
		  <?php
		  }
		  ?>
		</form>
	  </td>
	</tr>
      </table>

      <!-- INVERT ROWS/COLUMNS FORM -->

      <table border="0" cellspacing="0" cellpadding="0">
	<tr>
	  <td height="5px"><!-- spacing cell --></td>
	</tr>
	<tr>
	  <td>
		<form name="invert_table" method="post">
		  <!-- set data from the other forms to send it when this one is submitted -->
		  <input type="hidden" name="type_of_table" value="<?=$type_of_table?>">
		  <input type="hidden" name="chart_type" value="<?=$ctype?>">
		  <input type="hidden" name="init" value="<?=$init?>">
		  <input type="hidden" name="end" value="<?=$end?>">
		  <?php
      		  if(!empty($invert_cols_and_rows)&&($invert_cols_and_rows=="yes")) {
		  ?>
			<input type="hidden" name="invert_cols_and_rows" value="no">
		  <?php
		  } else {
		  ?>
			<input type="hidden" name="invert_cols_and_rows" value="yes">
	  	  <?php
		  }
		  ?>
		  <input type="submit" name="invert" value="<?=_("Invert rows/columns")?>">
		</form>
	  </td>
	</tr>

	<tr>
	  <td height="35px"><!-- spacing cell --></td>
	</tr>
      </table>

      <!-- GRAPH TYPE SELECTION FORM -->

      <table border="0" cellspacing="0" cellpadding="0">
	<tr>
	  <td bgcolor="#000000">
	    <table border="0" cellspacing="1" cellpadding="0" width="100%">
	      <tr>
		<td bgcolor="#000000" class="title_box">
		  <font color="#FFFFFF" class="title_box">
		  <!-- title box -->
		  <?=_("Select chart type")?>
		  <!-- end title box -->
		  </font>		 
		</td>
	      </tr>
	      <tr>
		<td bgcolor="#FFFFFF" class="text_box" >
		  <form name="chart" method="post">
		  <table>
		    <tr>
		      <td width="10px"></td>
		      <td>
			<select name="chart_type" onchange="this.form.submit()">
			  <?
                          foreach ($chart_types as $chart_type_id => $chart_type_text) {
                          ?>
                            <option value="<?=$chart_type_id?>" <?=($ctype==$chart_type_id)?"selected=\"selected\"":""?>>			      
			      <?=_($chart_type_text)?>
			    </option>
			  <?
			  }
			  ?>
			</select>
			<!-- set data from the other form to send it when this one is submitted -->
			<input type="hidden" name="type_of_table" value="<?=$type_of_table?>">
			<input type="hidden" name="invert_cols_and_rows" value="<?=$invert_cols_and_rows?>">
			<input type="hidden" name="init" value="<?=$init?>">
			<input type="hidden" name="end" value="<?=$end?>">
		      </td>
		      <td width="1px"></td>
		    </tr>
		  </table>
		  </form>		  
		</td>
	      </tr>
	    </table>
	  </td>
	</tr>
	<tr>
	  <td height="15px"><!-- spacing cell --></td>
	</tr>
      </table>

      <!-- GRAPH DRAWINGS -->

      <table>
	<tr>
	  <td>
	    <!-- Line points chart -->
            <?
              /* Build an URL encoded title */
              $g_title=urlencode("Project evolution");
            ?>
	    <a href="graphicbuilder.php?id=<?=$id?>&type=<?=$ctype?>&flag=PROJECT&title=<?=$g_title?>&width=1600&height=1200&startdate=<?=$init?>&enddate=<?=$end?>">
	    <img class="noborder" src="graphicbuilder.php?id=<?=$id?>&type=<?=$ctype?>&flag=PROJECT&title=<?=$g_title?>&width=800&height=600&startdate=<?=$init?>&enddate=<?=$end?>" />
	    </a>
	  </td>
	</tr>
	<tr><td height="20px"><!-- spacing cell --></td></tr>
      </table>
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
