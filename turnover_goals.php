<?php
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
//  Mario Sánchez Prada <msanchez@igalia.com>
//  Jacobo Aragunde Pérez <jaragunde@igalia.com>
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


/* Check if dates are empty */
$empty_dates=true;
if($init=="" || $end=="") {
  $empty_dates=true;
}
else {
  $empty_dates=false;
}


/* validate dates (DD/MM/YYYY) */

if(!$empty_dates && (!validate_date_web($init) || !validate_date_web($end))) {
  $error=_("Incorrect date format, should be DD/MM/YYYY");
}
else if(isset($view) && $empty_dates) {
  $error=_("Dates can't be void");
}


/* Retrieve area names */

$type=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='parea' ORDER BY code")
      or die($die);
$areas_data=array();
for ($i=0;$row=@pg_fetch_array($type,$i,PGSQL_ASSOC);$i++) {
  $areas_data[$row["code"]]["hours"]  =0;
  $areas_data[$row["code"]]["invoice"]=0;
}
@pg_freeresult($type);
  

if(!$empty_dates&&!isset($error)) {

  /* Convert dates to SQL format */

  $sql_init=date_web_to_sql($init);
  $sql_end=date_web_to_sql($end);


  /* Retrieve the data about projects from the DB */

  $data=@pg_exec($cnx,$query="SELECT DISTINCT tblTotal.name, total_hours, proj.invoice, proj.est_hours, proj.activation, proj.area, init, _end
    FROM 
    (SELECT SUM( _end - init ) / 60.0 AS total_hours,  name
    FROM task 
    GROUP BY name
    ) AS tblTotal
    LEFT JOIN projects AS proj ON (proj.id=tblTotal.name)     
    ORDER BY name")
    or die(_("failed processing query ").$query);
  $projects_data=array();

  for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
    //initialize some values
    $projects_data[$row["name"]]["invoice"]=0;
    $projects_data[$row["name"]]["expenses"]=0;
    $projects_data[$row["name"]]["hours_in_period"]=0;

    //save the project data
    $projects_data[$row["name"]]["name"]=$row["name"];
    $projects_data[$row["name"]]["init"]=$row["init"];
    $projects_data[$row["name"]]["end"] =$row["_end"];
    if($row["area"]!="")
      $projects_data[$row["name"]]["area"]=$row["area"];
    else
      $projects_data[$row["name"]]["area"]=_("(unassigned)");

    //calculate price per hour
    if ($row["activation"]=="t") 
      //in active projects, we use the max between real and estimated hours
      $projects_data[$row["name"]]["price_per_hour"]=$row["invoice"]/max($row["total_hours"],$row["est_hours"]);
    else
      //in inactive projects, we use the real hours
      $projects_data[$row["name"]]["price_per_hour"]=$row["invoice"]/$row["total_hours"];
  }
  @pg_freeresult($data);


  /* Retrieve the data about tasks from the DB */

  $data=@pg_exec($cnx,$query="SELECT SUM( task._end - task.init ) / 60.0 AS add_hours, task.name as name, periods.hour_cost as hour_cost
     FROM task JOIN periods ON task.uid=periods.uid AND task._date>=periods.init AND task._date<=periods._end
     WHERE task._date>='$sql_init' AND task._date<='$sql_end' AND (task.type='pex' OR task.type='pin')
     GROUP BY task.name, periods.hour_cost")
    or die(_("failed processing query ").$query);
    
  for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {

    if($row["name"]=="") {
      $row["name"]=_("(unassigned)");
      $project_area=_("(unassigned)");
      $projects_data[$row["name"]] ["area"]=$project_area;
    } else 
      $project_area =$projects_data[$row["name"]]["area"];
    $project_price=$projects_data[$row["name"]]["price_per_hour"];
  
    $areas_data[$project_area]   ["hours"]    +=$row["add_hours"];
    $areas_data[$project_area]   ["invoice"]  +=$row["add_hours"]*$project_price;
    $projects_data[$row["name"]] ["invoice"]  +=$row["add_hours"]*$project_price;
    $projects_data[$row["name"]] ["expenses"] +=$row["add_hours"]*$row["hour_cost"];
    $projects_data[$row["name"]] ["hours_in_period"] +=$row["add_hours"];
  }
  @pg_freeresult($data);


  /* Calculate the estimated turnover at the end of the period */
  
  $day=getdate(time());
  $day=$day["mday"]."/".$day["mon"]."/".$day["year"];
  $day_sql=date_web_to_sql($day);
  $day_ts =date_sql_to_ts($day_sql);
  
  $init_ts=date_sql_to_ts($sql_init);
  $end_ts =date_sql_to_ts($sql_end);
  foreach($projects_data as $pname => $data) {
    if($data["hours_in_period"]>0){
      $proj_init_ts=date_sql_to_ts($data["init"]);
      $proj_end_ts =date_sql_to_ts($data["end"]);
      
      $a=max($proj_init_ts,$init_ts);
      $b=min($proj_end_ts,$end_ts,$day_ts);
      $c=min($proj_end_ts,$end_ts,$day_ts);
      $d=min($day_ts,$proj_end_ts);
      
      $a=date_ts_to_sql($a);
      $b=date_ts_to_sql($b);
      $c=date_ts_to_sql($c);
      $d=date_ts_to_sql($d);
      
      $worked_days=num_work_days($a,$b);
      $remaining_work_days=num_work_days($day_sql,$c);

      if($worked_days>0 && $day_ts<$end_ts) {
        $invoice_per_day=$data["invoice"]/$worked_days;
        if ($remaining_work_days>0)
          $est_turnover=$remaining_work_days*$invoice_per_day+$data["invoice"];
        else
          $est_turnover=$data["invoice"];
        $est_potential_turnover=num_work_days($d,$sql_end)*$invoice_per_day+$data["invoice"];
      } else {
        $est_turnover=$data["invoice"];
        $est_potential_turnover=$data["invoice"];
      }
      $projects_data[$pname]["est_turnover"]=$est_turnover;
      $projects_data[$pname]["est_potential_turnover"]=$est_potential_turnover;
      $areas_data[$data["area"]]["est_turnover"]+=$est_turnover;
      $areas_data[$data["area"]]["est_potential_turnover"]+=$est_potential_turnover;
    }
  }


  /* Retrieve turnover goals from the HTML form and calculate % */

  if(isset($turnover_goals)) {
    foreach($turnover_goals as $index => $value) {
      if($value!=0) {
        $area=$area_names[$index];
        $areas_data[$area]["turnover_goal"]=$value;
        $areas_data[$area]["turnover_percentage"]=$areas_data[$area]["invoice"]*100.00/$value;
        $areas_data[$area]["est_turnover_percentage"]=$areas_data[$area]["est_turnover"]*100/$value;
        $areas_data[$area]["est_potential_turnover_percentage"]=$areas_data[$area]["est_potential_turnover"]*100/$value;
      }
    }
  }
}

require_once("include/close_db.php");


/*-----------------------------------------------------------------*/

$title=_("Turnover goals");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation); 

?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
       style="text-align: center; margin-left: auto; margin-right: auto;">
  <tr>
    <td style="text-align: center; vertical-align: top;">

     <center>

      <!-- DATE SELECTION FORM -->

      <table>
        <form name="results" method="post">
	        <tr>
		        <td colspan="2">
		          <b><?=_("Period to check")?></b>
            </td>
	        </tr>
	        <tr>
            <td align="right"><?=_("Start date");?>:</td>
            <td><input type="text" name="init" value="<?=$init?>"></td> 
          </tr>
          <tr>
            <td align="right"><?=_("End date");?>:</td>
            <td><input type="text" name="end" value="<?=$end?>"></td> 
          </tr>
	        <tr>
		        <td colspan="2">
		          <b><?=_("Turnover goals per department")?></b>
            </td>
	        </tr>
	        <?
	        foreach($areas_data as $area_name => $data) {
	          if($area_name!=_("(unassigned)")) {?>
	            <tr>
                <td align="right"><?=$area_name?>:</td>
                <td><input type="text" name="turnover_goals[]" value="<?=$data["turnover_goal"]?>"/> 
                <input type="hidden" name="area_names[]" value="<?=$area_name?>"/></td>
              </tr>
            <?
            }
          }
          ?>
          <tr>
            <td align="center" colspan="2">
              <input type="submit" name="view" value="<?=_("View")?>">
            </td> 
          </tr>
        </form>
      </table>
      <br/><br/><br/>

      <?if(!$empty_dates&&!isset($error)) {?>
      
      <!-- AREAS TABLE -->

      <table border="0" cellspacing="1" cellpadding="2" bgcolor="#000000">
        <tr>
          <td class="title_table"><?=_("Area")?></td>
          <td class="title_table" colspan="100"></td>
        </tr>
        <tr>
          <td class="title_box"></td>
          <td class="title_box"><?=_("Hours")?></td>
          <td class="title_box"><?=_("Turnover")?></td>
          <td class="title_box"><?=_("Turnover goal")?></td>
          <td class="title_box"><?=_("Turnover goal %")?></td>
          <td class="title_box"><?=_("Estimated turnover")?></td>
          <td class="title_box"><?=_("Estimated turnover %")?></td>
          <td class="title_box"><?=_("Potential turnover")?></td>
          <td class="title_box"><?=_("Potential turnover %")?></td>
        </tr>
        <?
        $odd_even = 0; /* start with odd rows */
        foreach($areas_data as $area_name => $data) {
        ?>
          <tr class="<?=($odd_even==0)?odd:even?>">
            <td class="title_box"><?=$area_name?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["hours"])?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["invoice"])?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["turnover_goal"])?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["turnover_percentage"])?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["est_turnover"])?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["est_turnover_percentage"])?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["est_potential_turnover"])?></td>
            <td class="text_data"><?=sprintf("%01.2f",$data["est_potential_turnover_percentage"])?></td>
          </tr>
          <?
          $odd_even = ($odd_even+1)%2; /* update odd_even counter */  
        }?>
      </table>
      <br/><br/><br/>

      <!-- PROJECTS TABLE -->

      <table border="0" cellspacing="1" cellpadding="2" bgcolor="#000000">
        <tr>
          <td class="title_table"><?=_("Project")?></td>
          <td class="title_table" colspan="100"></td>
        </tr>
        <tr>
          <td class="title_box"></td>
          <td class="title_box"><?=_("Area")?></td>
          <td class="title_box"><?=_("Hours")?></td>
          <td class="title_box"><?=_("Expenses")?></td>
          <td class="title_box"><?=_("Turnover")?></td>
          <td class="title_box"><?=_("Estimated turnover")?></td>
          <td class="title_box"><?=_("Potential turnover")?></td>
        </tr>
        <?
        $odd_even = 0; /* start with odd rows */
        foreach($projects_data as $project_name => $data) {
          if($data["hours_in_period"]!=0) {
          ?>
            <tr class="<?=($odd_even==0)?odd:even?>">
              <td class="title_box"><?=$project_name?></td>
              <td class="text_data"><?=$data["area"]?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["hours_in_period"])?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["expenses"])?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["invoice"])?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["est_turnover"])?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["est_potential_turnover"])?></td>
            </tr>
            <?
            $odd_even = ($odd_even+1)%2; /* update odd_even counter */  
            }
        }?>
      </table>
      <?}?>
     </center>
    </td>
    

    <!-- SIDEBAR -->
    <td style="width: 25ex" valign="top">
      <? require("include/show_sections.php") ?>
      <br/>
      <? require("include/show_calendar.php") ?>
    </td>
  </tr>
</table>

<?
require("include/template-post.php");
?>
