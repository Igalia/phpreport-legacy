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

  $init_sql=date_web_to_sql($init);
  $end_sql=date_web_to_sql($end);


  /* Retrieve the data about projects from the DB */

  $data=@pg_exec($cnx,$query="SELECT DISTINCT tblTotal.name, total_hours, proj.invoice, proj.est_hours, proj.activation, proj.area, init, _end
    FROM 
    (SELECT SUM( _end - init ) / 60.0 AS total_hours,  name
    FROM task 
    WHERE (task.type='pex' OR task.type='pin')
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
    $projects_data[$row["name"]]["total_invoice"]=$row["invoice"];
    $projects_data[$row["name"]]["total_hours"]  =$row["total_hours"];
    $projects_data[$row["name"]]["est_hours"]    =$row["est_hours"];
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

  $data=@pg_exec($cnx,$query="SELECT SUM( task._end - task.init ) / 60.0 AS add_hours, task.name as name, periods.hour_cost as hour_cost, periods.area as worker_area
     FROM task JOIN periods ON task.uid=periods.uid AND task._date>=periods.init AND task._date<=periods._end
     WHERE task._date>='$init_sql' AND task._date<='$end_sql' AND (task.type='pex' OR task.type='pin')
     GROUP BY task.name, periods.hour_cost, periods.area")
    or die(_("failed processing query ").$query);
    
  for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {

    if($row["name"]=="") {
      //a task without a project name is charged to '(unassigned)' project and area
      $row["name"]=_("(unassigned)");
      $project_area =_("(unassigned)");
      $project_price=0;
      $projects_data[$row["name"]] ["area"]=$project_area; // this line creates an element for '(unassigned)'
                                                           // in the array of projects if it doesn't exist
    } else {
      $project_area =$projects_data[$row["name"]]["area"];
      $project_price=$projects_data[$row["name"]]["price_per_hour"];
    }

    if($project_area==_("(unassigned)") && $row["name"]!=_("(unassigned)")) {
      //it's a project without an assigned area
      //we have to split it into the areas of its workers
      if ($row["worker_area"]=='')
        $project_area =_("(unassigned)");
      else
        $project_area=$row["worker_area"];

      $projects_data[$row["name"]] ["subareas"] [$project_area] ["invoice"]  +=$row["add_hours"]*$project_price;
      $projects_data[$row["name"]] ["subareas"] [$project_area] ["expenses"] +=$row["add_hours"]*$row["hour_cost"];
      $projects_data[$row["name"]] ["subareas"] [$project_area] ["hours_in_period"] +=$row["add_hours"];
    }
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
  $init_ts=date_sql_to_ts($init_sql);
  $end_ts =date_sql_to_ts($end_sql);

  foreach($projects_data as $pname => $pdata) {
    if($pdata["hours_in_period"]>0){
      $proj_init_ts=date_sql_to_ts($pdata["init"]);
      $proj_end_ts =date_sql_to_ts($pdata["end"]);
    
      $est_turnover=0;
      if($proj_init_ts>=$init_ts && $proj_end_ts<=$end_ts) {
        //the project begins and ends in this time period
        $est_turnover=$pdata["total_invoice"];
        
      } else if ($proj_init_ts<$init_ts && $proj_end_ts<=$end_ts) {
        //the project began before the time period and ends before the time period end
        //(or it has already ended)
        $hours=($pdata["est_hours"] > $pdata["total_hours"])? ($pdata["est_hours"] - $pdata["total_hours"]):0;
        $est_turnover=$pdata["invoice"] + $hours * $pdata["price_per_hour"];
        
      } else if ($proj_end_ts>$end_ts) {
        //the project estimated end is after the selected time period
        $a=max($proj_init_ts,$init_ts);
        $b=min($end_ts,$day_ts);
        $a=date_ts_to_sql($a);
        $b=date_ts_to_sql($b);

        $daily_dedication=$pdata["hours_in_period"]/num_work_days($a,$b);
        $hours=$pdata["est_hours"]-$pdata["total_hours"];
        $days_end_hours=$hours/$daily_dedication; //work days until the estimated hours end
   
        if($hours > 0 && $days_end_hours>num_work_days($b,$end_sql)) {
          //we estimate that the project ends after the end of the time period
          $hours_end_period=$daily_dedication*num_work_days($a,$end_sql); //estimated work hours in this period
          $est_turnover=$hours_end_period*$pdata["price_per_hour"];

        }
        else {
          //the hours for this project will be finished before the end of the time period, or they have already finished.
          if($proj_init_ts>=$init_ts) {
            //the project began in this time period
            $est_turnover=$pdata["total_invoice"];

          } else if ($proj_init_ts<$init_ts) {
            //the project began before the time period
            if ($hours<0) $hours=0;
            $est_turnover=$pdata["invoice"] + $hours * $pdata["price_per_hour"];
          }
        }
      }
      if(isset($pdata["subareas"])) {
        //in this project there were multiple areas participating
        //we split the estimation proportionaly to the hours worked per each area
        foreach($pdata["subareas"] as $subarea => $subdata) {
          $est_turnover_proportional = $est_turnover * ($subdata["hours_in_period"]/$pdata["hours_in_period"]);
          $projects_data [$pname] ["subareas"] [$subarea] ["est_turnover"] = $est_turnover_proportional;
          $areas_data[$subarea]["est_turnover"]+= $est_turnover_proportional;
        }
      }
      else {
        $projects_data     [$pname]["est_turnover"] = $est_turnover;
        $areas_data[$pdata["area"]]["est_turnover"]+= $est_turnover;
      }
    }
  }


  /* Retrieve turnover goals from the HTML form and calculate % */

  if(isset($turnover_goals)) {
    foreach($turnover_goals as $index => $value) {
      $area=$area_names[$index];
      $potential_turnover_daily=$areas_data[$area]["invoice"]/num_work_days($init_sql,$day_sql);
      $areas_data[$area]["est_potential_turnover"]=$potential_turnover_daily*num_work_days($init_sql,$end_sql);
      if($value!=0) {
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
        </tr>
        <?
        $odd_even = 0; /* start with odd rows */
        foreach($projects_data as $project_name => $data) {
          if(isset($data["subareas"])) {
            foreach($data["subareas"] as $subarea => $subdata) {
            ?>
              <tr class="<?=($odd_even==0)?odd:even?>">
                <td class="title_box"><?=$project_name?></td>
                <td class="text_data"><?=$subarea?></td>
                <td class="text_data"><?=sprintf("%01.2f",$subdata["hours_in_period"])?></td>
                <td class="text_data"><?=sprintf("%01.2f",$subdata["expenses"])?></td>
                <td class="text_data"><?=sprintf("%01.2f",$subdata["invoice"])?></td>
                <td class="text_data"><?=sprintf("%01.2f",$subdata["est_turnover"])?></td>
              </tr>
              <?
              $odd_even = ($odd_even+1)%2; /* update odd_even counter */
            }
          }
          else if($data["hours_in_period"]!=0) {
          ?>
            <tr class="<?=($odd_even==0)?odd:even?>">
              <td class="title_box"><?=$project_name?></td>
              <td class="text_data"><?=$data["area"]?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["hours_in_period"])?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["expenses"])?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["invoice"])?></td>
              <td class="text_data"><?=sprintf("%01.2f",$data["est_turnover"])?></td>
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
