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


// SHEETS:
// 1 - Project / task type
// 2 - Person / task type
// 3 - Person / project
// 4 - Project evaluation (global)
// 5 - Hourly personal brief
// 6 - Hourly personal brief (explained)
// 7 - Project evaluation (period)

// Show activation (only for project evaluation sheet):
// 1 - All projects
// 2 - Only active projects
// 3 - Only non active projects

// Useful arrays:
// $holidays[city]
// $type_consult[]
// $users_consult[]
// $extra_consult[]

require_once("include/config.php");
require_once("include/util.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

$die=_("Can't finalize the operation");

/* if ($flag=="PROJECTS" && !multi_in_array($board_group_names,(array)$session_groups)) { */
/*   // If the user in not in a group that belongs to the board members, she can't */
/*   // access the PROJECTS information */
/*   header("Location: login.php"); */
/* } */

// In sheet 5, init date will be the same as end date
if ($sheets==5) {
  if(empty($end)){
  //set default end date to today's date
    $end=date('d\/m\/Y');
} 
  $init=$end;
}

$result=@pg_exec($cnx,$query="SELECT fest,city FROM holiday ORDER BY fest")
   or die($die);
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $val=date_sql_to_web($row["fest"]);
  $arrayDMA=date_web_to_arrayDMA($val);
  $holidays[$row["city"]][]=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
}
@pg_freeresult($result);

/* Specific data for project evaluation sheets */
if ($sheets==4 || $sheets==7) {

  /* Area filter */
  if (isset($area)) {
    switch($area) {
    case 'all_areas': /* All projects */
      $areaCond=" true ";
      break;
    default:
      /* some filter specified */
      $areaCond=" area = '".$area."' ";
    }
  } else {
    $areaCond=" true ";
    $area='all_areas';
  }

  /* Activation filter */
  if (isset($showActivation)) {
    switch($showActivation) {
    case 1: /* All projects */
      $activationCond=" true ";
      break;
    case 2: /* Only active projects */
      $activationCond=" activation='t' ";
      break;
    case 3: /* Only non active projects */
      $activationCond=" activation='f' ";
      break;
    default:
      $showActivation=1;
      $activationCond=" true ";
    }
  } else {
    $showActivation=1;
    $activationCond=" true ";
  }

  /* Project type filter */
  if (isset($ptype)) {
    switch($ptype) {
    case 'all': /* All projects */
      $ptypeCond=" true ";
      break;
    case 'unassigned': /* All projects */
      $ptypeCond=" type IS NULL ";
      break;
    default:
      /* some filter specified */
      $ptypeCond=" type = '".$ptype."' ";
    }
  } else {
    $ptypeCond=" true ";
    $ptype='all';
  }

  /* Init some useful boolean values */
  $showAllAreas = (isset($area) && ($area=='all_areas'));
  $showAllProjects = (isset($showActivation) && ($showActivation==1));
  $showNonActiveProjects = (isset($showActivation) && ($showActivation!=2));

  /* Minimum invoice filter */
  if (isset($minInvoice) && is_numeric($minInvoice)) {
    $minInvoiceCond=" invoice >= $minInvoice ";
  } else {
    $minInvoiceCond=" true ";
  }

  /* Maximum invoice filter */
  if (isset($maxInvoice) && is_numeric($maxInvoice)) {
    $maxInvoiceCond=" invoice <= $maxInvoice ";
  } else {
    $maxInvoiceCond=" true ";
  }

  if ($sheets==4) { /* Project evaluation (global) */

    /* Retrieve codes for projects */
    $code=@pg_exec($cnx,$query="SELECT id FROM projects WHERE "
		   .$areaCond." AND ".$activationCond." AND ".$ptypeCond." AND "
		   .$minInvoiceCond." AND ".$maxInvoiceCond
		   ." ORDER BY id")
      or die($die);
    $code_consult=array();
    if ($showAllAreas && $showAllProjects) {
      $code_consult[]=_("(empty)");
    }
    for ($i=0;$row=@pg_fetch_array($code,$i,PGSQL_ASSOC);$i++) {
      $code_consult[]=$row["id"];
    }
    @pg_freeresult($code);
  } else { /* Project evaluation (period) */
    
    /* Retrieve codes for projects in the specified period */
    if (!empty($init) && !empty($end)) {
      $code=@pg_exec($cnx,$query="SELECT distinct p.id FROM projects p JOIN task t ON p.id = t.name WHERE "
		     ."t._date >= '".date_web_to_sql($init)."' AND t._date < '".date_web_to_sql($end)
		     ."' AND ".$areaCond." AND ".$activationCond." AND ".$ptypeCond
		     ." AND ".$minInvoiceCond." AND ".$maxInvoiceCond
		     ." ORDER BY p.id") 
	or die($die);
    } else {
      $code=@pg_exec($cnx,$query="SELECT id FROM projects WHERE "
		     .$areaCond." AND ".$activationCond." AND ".$ptypeCond
		     ." AND ".$minInvoiceCond." AND ".$maxInvoiceCond
		     ." ORDER BY id")
	or die($die);
    }
    
    $code_consult=array();
    for ($i=0;$row=@pg_fetch_array($code,$i,PGSQL_ASSOC);$i++) {
      $code_consult[]=$row["id"];
    }
    @pg_freeresult($code);
  }

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
  $areasList=array();
  $areasList["all_areas"]=_("All areas");
  foreach ($project_areas as $project_area){ 
    $areasList[$project_area["code"]]=$project_area["description"];
  }
  $ptypesList=array();
  $ptypesList["all"]= _("All");
  $ptypesList["unassigned"]= _("Unassigned");
  foreach ($project_types as $type){ 
    $ptypesList[$type["code"]]=$type["description"];
  }
} else {

  /* Retrieve codes for projects with tasks in this interval */
  if (!empty($init) && !empty($end)) {
    $code=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='name' AND code IN "
		   ."(SELECT DISTINCT name FROM task WHERE _date >= '".date_web_to_sql($init)."' "
		   ."AND _date < '".date_web_to_sql($end)."') ORDER BY code") 
      or die($die);
  } else {
    $code=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='name' ORDER BY code")
      or die($die);
  }
  
  $code_consult=array();
  if ($showAllAreas && $showAllProjects) {
    $code_consult[]=_("(empty)");
  }
  for ($i=0;$row=@pg_fetch_array($code,$i,PGSQL_ASSOC);$i++) {
    $code_consult[]=$row["code"];
  }
  @pg_freeresult($code);
}

$type=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='type'")
  or die($die);
$type_consult=array();
for ($i=0;$row=@pg_fetch_array($type,$i,PGSQL_ASSOC);$i++) {
  $type_consult[]=$row["code"];
}
@pg_freeresult($type);

if ($sheets==5 || $sheets==6) $staff_condition="WHERE staff='t'";
else $staff_condition="";

$users=@pg_exec($cnx,$query="SELECT uid FROM users ".$staff_condition." ORDER by uid")
  or die($die);
$users_consult=array();
for ($i=0;$row=@pg_fetch_array($users,$i,PGSQL_ASSOC);$i++) {
  $users_consult[]=$row["uid"];
}
@pg_freeresult($users);

if ($init!=""||$end!="") {
  if(!validate_date_web($init)||!validate_date_web($end)) {
    $error=_("Incorrect date format, should be DD/MM/YYYY");
  }
}
if (($sheets!=0||!empty($view))&&$sheets!=4&&$init==""&&$end=="") $error=_("Dates can't be void");

if (!empty($sheets)&&$sheets!="0"&&empty($error)) {
  $init=date_web_to_sql($init);
  $end=date_web_to_sql($end);
  if ($sheets==1) {
    $data=@pg_exec($cnx,$query="SELECT type, name, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '$init' AND _date <= '$end' ) ) GROUP BY type, name ORDER BY name ASC")
    or die($die);
    $data_consult=array();
    for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
    }
    @pg_freeresult($data);
    $row_index="name";
    $row_index_trans=_("Name");
    $col_index="type";
    $col_index_trans=_("Type");
    $row_title_var="code_consult";
    $col_title_var="type_consult";
  }
  if ($sheets==2) {
    $data=@pg_exec($cnx,$query="SELECT type, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '$init' AND _date <= '$end' ) ) GROUP BY type, uid ORDER BY uid ASC")
    or die($die);
    $data_consult=array();
    for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
    }
    @pg_freeresult($data);
    $row_index="uid";
    $row_index_trans=_("User");
    $col_index="type";
    $col_index_trans=_("Type");
    $row_title_var="users_consult";
    $col_title_var="type_consult";
  }

  if ($sheets==3) {
    $data=@pg_exec($cnx,$query="SELECT name, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '$init' AND _date <= '$end' ) ) GROUP BY name, uid ORDER BY uid ASC")
    or die($die);
    $data_consult=array();
    for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
    }
    @pg_freeresult($data);
    $row_index="name";
    $row_index_trans=_("Name");
    $col_index="uid";
    $col_index_trans=_("User");
    $row_title_var="code_consult";
    $col_title_var="users_consult";
  }

  foreach((array)$data_consult as $row2) {
    if($row2[$row_index]=="") {
      $row2[$row_index]=_("(empty)");
    }
    $a[$row2[$row_index]][$row2[$col_index]]=$row2["add_hours"];
    
    $add_hours_row[$row2[$row_index]]+=$row2["add_hours"];
    $add_hours_col[$row2[$col_index]]+=$row2["add_hours"];
    $add_hours+=$row2["add_hours"];
  } 
  foreach ((array)$$row_title_var as $cod) {
    if($add_hours!=0) {
      $percent_row[$cod]+=@($add_hours_row[$cod]*100/$add_hours);
      $percent_row["tot"]+=@($add_hours_row[$cod]*100/$add_hours);
    }
  }
  foreach ((array)$$col_title_var as $type) {
    if ($add_hours!=0) {
      $percent_col[$type]+=@($add_hours_col[$type]/$add_hours*100);
      $percent_col["tot"]+=@($add_hours_col[$type]/$add_hours*100);
    }
  }

  if ($sheets==4) {

    /* DATA FOR GLOBAL PROJECTS EVALUATION */

    /* retrieve data for each row */
    $data=@pg_exec($cnx,$query="SELECT DISTINCT tblTotal.name, total_hours, proj.invoice, proj.est_hours, proj.activation, total_staff_cost
      FROM 
      (SELECT SUM( task._end - task.init ) / 60.0 AS total_hours, SUM( (task._end - task.init) / 60.0 * hour_cost) AS total_staff_cost, name
      FROM task 
      LEFT JOIN periods ON (
        (task.uid=periods.uid) and 
        (periods.init is null or periods.init<=task._date) and 
        (periods._end is null or periods._end>=task._date) )      
      GROUP BY name
      ) AS tblTotal
      LEFT JOIN
      (SELECT * FROM projects
      ) AS proj ON (proj.id=tblTotal.name)
      WHERE $areaCond AND $activationCond AND $ptypeCond AND $minInvoiceCond AND $maxInvoiceCond
      ORDER BY name")
      or die($die);

    $data_consult=array();
    for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
    }
    @pg_freeresult($data);
    $row_index="name";
    $row_index_trans=_("Name");
    $row_title_var="code_consult";

    if ($showAllProjects) {
      $project_consult=array("total_hours","est_hours","desv1","desv2","invoice","eur_real","eur_pres","total_staff_cost","total_profit","hour_profit","activation");
    } else {
      $project_consult=array("total_hours","est_hours","desv1","desv2","invoice","eur_real","eur_pres","total_staff_cost","total_profit","hour_profit");
    }

    $col_title_var="project_consult";

    foreach($data_consult as $row2) {
      if($row2[$row_index]=="") {
	$row2[$row_index]=_("(empty)");
      }
      $a[$row2[$row_index]]["total_hours"]=$row2["total_hours"];
      $a[$row2[$row_index]]["est_hours"]=($row2["est_hours"]!=0)?$row2["est_hours"]:"";
      if ($row2[$row_index]!=_("(empty)")) {
	$a[$row2[$row_index]]["desv1"]=
	  ($row2["est_hours"]!=0)?@(($row2["total_hours"]-$row2["est_hours"])/$row2["est_hours"]*100):"";
	$a[$row2[$row_index]]["desv2"]=($row2["est_hours"]!=0)?$row2["total_hours"]-$row2["est_hours"]:"";
  $a[$row2[$row_index]]["invoice"]=($row2["invoice"]!=0)?$row2["invoice"]:"";
	$a[$row2[$row_index]]["eur_real"]=($row2["invoice"]!=0)?@($row2["invoice"]/$row2["total_hours"]):"";
	$a[$row2[$row_index]]["eur_pres"]=($row2["invoice"]!=0)?@($row2["invoice"]/$row2["est_hours"]):"";	
  $a[$row2[$row_index]]["total_staff_cost"]=@($row2["total_staff_cost"]);  
  $a[$row2[$row_index]]["total_profit"]=($row2["invoice"]!=0)?@($row2["invoice"]-$row2["total_staff_cost"]):""; 
  $a[$row2[$row_index]]["hour_profit"]=($row2["invoice"]!=0)?@(($row2["invoice"]-$row2["total_staff_cost"])/$row2["total_hours"]):""; 
  
	if ($showAllProjects) {
	  $a[$row2[$row_index]]["activation"]=$row2["activation"];
	}
      }

      $add_total_hours+=$a[$row2[$row_index]]["total_hours"];
      $add_est_hours+=$a[$row2[$row_index]]["est_hours"];
      $add_desv1+=$a[$row2[$row_index]]["desv1"];
      $add_desv2+=$a[$row2[$row_index]]["desv2"];
      $add_invoice+=$a[$row2[$row_index]]["invoice"];
      $add_eur_real+=$a[$row2[$row_index]]["eur_real"];
      $add_eur_pres+=$a[$row2[$row_index]]["eur_pres"];
      $add_total_staff_cost+=$a[$row2[$row_index]]["total_staff_cost"];
      $add_total_profit+=$a[$row2[$row_index]]["total_profit"];
      $add_hour_profit+=$a[$row2[$row_index]]["hour_profit"];
      $add_totals=array($add_total_hours,$add_est_hours,$add_desv1,$add_desv2,
			$add_invoice,$add_eur_real,$add_eur_pres,$add_total_staff_cost,$add_total_profit,$add_hour_profit);
    }
  } else if ($sheets==7) {
    /* DATA FOR PROJECT EVALUATION WITHIN A PERIOD OF TIME */

    if ($showNonActiveProjects) { 

      /* retrieve total worked hours only for the specified interval */
      $period_worked_hours=0.0;  
      $data=@pg_exec($cnx,$query="SELECT SUM( t._end - t.init ) / 60.0 AS period_worked_hours "
		     ."FROM task  t JOIN projects p ON t.name = p.id WHERE "
		     ."p.activation = 'f' AND p.invoice > 0 AND p.est_hours > 0 AND t.name <> '' "
		     ." AND ".$areaCond." AND ".$activationCond." AND ".$ptypeCond
		     ." AND ".$minInvoiceCond." AND ".$maxInvoiceCond
		     ." AND t._date >= '".$init."' AND t._date < '".$end."'") 
	or die($die);
      $row=@pg_fetch_array($data,0,PGSQL_ASSOC);
      $period_worked_hours=$row["period_worked_hours"];
      @pg_freeresult($data);
    }

    /* retrieve data for each row */
    $data=@pg_exec($cnx,$query="SELECT DISTINCT tblPeriod.name, hours AS total_hours, tblPeriod.period_hours,
          proj.invoice, proj.est_hours, proj.activation, total_staff_cost, period_staff_cost
       FROM 
       (SELECT SUM( task._end - task.init ) / 60.0 AS hours, SUM( (task._end - task.init) / 60.0 * hour_cost) AS total_staff_cost, name
       FROM task
       LEFT JOIN periods ON (
         (task.uid=periods.uid) and 
         (periods.init is null or periods.init<=task._date) and 
         (periods._end is null or periods._end>=task._date) )              
       GROUP BY name
       ) AS tblTotal
       LEFT JOIN
       (SELECT id,est_hours,invoice, activation
       FROM projects
       ) AS proj ON (proj.id=tblTotal.name),
       (SELECT t.name, SUM( t._end - t.init ) / 60.0 AS period_hours, SUM( (t._end - t.init) / 60.0 * hour_cost) AS period_staff_cost
       FROM task t 
       LEFT JOIN periods ON (
         (t.uid=periods.uid) and 
         (periods.init is null or periods.init<=t._date) and 
         (periods._end is null or periods._end>=t._date) )              
       JOIN projects p ON t.name = p.id 
		   WHERE t.name <> '' AND 
		   t._date >= '$init' AND t._date < '$end'
                   AND  $areaCond AND $activationCond AND $ptypeCond AND $minInvoiceCond AND $maxInvoiceCond
                   GROUP BY t.name
       ) AS tblPeriod
       WHERE tblTotal.name=tblPeriod.name  ORDER BY name")
      or die($die);

    $data_consult=array();
    for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
    }
    @pg_freeresult($data);

    $row_index="name";
    $row_index_trans=_("Name");
    $row_title_var="code_consult";

    if ($showAllProjects) {
      $project_consult=array("total_hours","total_staff_cost","total_profit","hour_profit","period_hours","est_hours","invoice","eur_real","eur_pres", "percent", "eur_real_pond", "eur_real_est", "period_staff_cost", "activation");
    } else if ($showNonActiveProjects) { 
      $project_consult=array("total_hours","total_staff_cost","total_profit","hour_profit","period_hours","est_hours","invoice","eur_real","eur_pres", "percent", "eur_real_pond", "eur_real_est", "period_staff_cost");
    } else {
      $project_consult=array("total_hours","total_staff_cost","total_profit","hour_profit","period_hours","est_hours","invoice","eur_real","eur_pres", "period_staff_cost");
    }

    $col_title_var="project_consult";      

    foreach($data_consult as $row2) {
      if($row2[$row_index]=="") {
	$row2[$row_index]=_("(empty)");
      }
      $a[$row2[$row_index]]["total_hours"]=$row2["total_hours"];
      $a[$row2[$row_index]]["total_staff_cost"]=$row2["total_staff_cost"];
      $a[$row2[$row_index]]["period_hours"]=$row2["period_hours"];
      $a[$row2[$row_index]]["est_hours"]=($row2["est_hours"]!=0)?$row2["est_hours"]:"";
      $a[$row2[$row_index]]["invoice"]=($row2["invoice"]!=0)?$row2["invoice"]:"";
      $a[$row2[$row_index]]["total_profit"]=($row2["invoice"]!=0)?@($row2["invoice"]-$row2["total_staff_cost"]):""; 
      $a[$row2[$row_index]]["hour_profit"]=($row2["invoice"]!=0)?@(($row2["invoice"]-$row2["total_staff_cost"])/$row2["total_hours"]):""; 
      if ($row2[$row_index]!=_("(empty)")) {
	$a[$row2[$row_index]]["eur_real"]=($row2["invoice"]!=0)?@($row2["invoice"]/$row2["total_hours"]):"";
	$a[$row2[$row_index]]["eur_pres"]=($row2["invoice"]!=0)?@($row2["invoice"]/$row2["est_hours"]):"";

	/* Calculate aditional information about finished projects */
	if ($showNonActiveProjects && ($row2["activation"] == 'f') && ($row2["invoice"] > 0) 
	    && ($row2["est_hours"] > 0) && ($period_worked_hours != 0)) {
	  $pond_value = $a[$row2[$row_index]]["period_hours"] / $period_worked_hours;
	  $a[$row2[$row_index]]["percent"]=$pond_value * 100.0;
	  $a[$row2[$row_index]]["eur_real_pond"]=($row2["invoice"]!=0)?$pond_value * $a[$row2[$row_index]]["eur_real"]:"";
    $a[$row2[$row_index]]["eur_real_est"]=($row2["invoice"]!=0)?$pond_value * $a[$row2[$row_index]]["eur_pres"]:"";
    $a[$row2[$row_index]]["period_staff_cost"]=$row2["period_staff_cost"];
	}
  if ($showAllProjects) {
    $a[$row2[$row_index]]["period_staff_cost"]=$row2["period_staff_cost"];
    $a[$row2[$row_index]]["activation"]=$row2["activation"];
	}
      }

      $add_total_hours+=$a[$row2[$row_index]]["total_hours"];
      $add_total_profit+=$a[$row2[$row_index]]["total_profit"];
      $add_hour_profit+=$a[$row2[$row_index]]["hour_profit"];
      $add_period_staff_cost+=$a[$row2[$row_index]]["period_staff_cost"];
      $add_period_hours+=$a[$row2[$row_index]]["period_hours"];
      $add_est_hours+=$a[$row2[$row_index]]["est_hours"];
      $add_invoice+=$a[$row2[$row_index]]["invoice"];
      $add_eur_real+=$a[$row2[$row_index]]["eur_real"];
      $add_eur_pres+=$a[$row2[$row_index]]["eur_pres"];
      $add_total_staff_cost+=$a[$row2[$row_index]]["total_staff_cost"];
      
      if ($showNonActiveProjects) {
	$add_percent+=$a[$row2[$row_index]]["percent"];
	$add_eur_real_pond+=$a[$row2[$row_index]]["eur_real_pond"];
	$add_eur_real_est+=$a[$row2[$row_index]]["eur_real_est"];
      }

      if ($showNonActiveProjects) {
	$add_totals=array($add_total_hours,$add_total_staff_cost,$add_total_profit,$add_hour_profit,$add_period_hours,$add_est_hours,$add_invoice,$add_eur_real,
			  $add_eur_pres, $add_percent,$add_eur_real_pond,$add_eur_real_est,$add_period_staff_cost);
      } else {
	$add_totals=array($add_total_hours,$add_total_staff_cost,$add_total_profit,$add_hour_profit,$add_period_hours,$add_est_hours,$add_invoice,$add_eur_real,$add_eur_pres,$add_period_staff_cost);
      }
    }

  } 

  if ($sheets==5 || $sheets==6) {
    
    $worked_hours_consult=net_extra_hours($cnx,$init,$end);

    // Select most recent overriden extra hours before 
    // the init date for each user
    //Remember $init=$end in this case
    $extra_hours=@pg_exec($cnx,$query="SELECT e.uid,e.hours,e.date
      FROM extra_hours AS e JOIN (
        SELECT uid,MAX(date) AS date FROM extra_hours
        WHERE date<'$init'
        GROUP BY uid) AS m ON (e.uid=m.uid AND e.date=m.date)
      WHERE e.uid IN (SELECT uid FROM users WHERE staff='t')")
      or die($die);
    $extra_hours_consult=array();
    for ($i=0;$row=@pg_fetch_array($extra_hours,$i,PGSQL_ASSOC);$i++) {
      $extra_hours_consult[$row["uid"]]=$row;
    }
    @pg_freeresult($extra_hours);
    
    // Let's compute the accumulated extra hours before the period we're computing now
    foreach ($users_consult as $k) {
      // $k is the uid
      
      // If there are forced extra hours defined before init, take them into account
      // and only compute hours after the defined ones, but before the init of the
      // period we're computing
      if (!empty($extra_hours_consult[$k])) {
        $previous_init=date_web_to_sql(day_day_moved(date_sql_to_web($extra_hours_consult[$k]["date"]),1));
        $previous_hours=$extra_hours_consult[$k]["hours"];
      } else {
        $previous_init="1900-01-01";
        $previous_hours=0;
      }      

      $h=net_extra_hours($cnx,$previous_init,
        date_web_to_sql(day_yesterday(date_sql_to_web($init))),$k);
      if (!empty($h[$k]["extra_hours"])) $previous_hours+=$h[$k]["extra_hours"];
      
      // Put them all
      $worked_hours_consult[$k]["total_extra_hours"]=$worked_hours_consult[$k]["extra_hours"]
        +$previous_hours;

    }

    $row_index="uid";
    $row_index_trans=_("User");
    $row_title_var="users_consult";
    if ($sheets==5) {
      $project_consult=array("total_extra_hours");
    } else {
      $project_consult=array("total_hours","workable_hours","extra_hours","total_extra_hours");
    }
    $col_title_var="project_consult";

    $a=$worked_hours_consult;
    foreach($worked_hours_consult as $row2) {
      if ($row2["total_extra_hours"]>0) $add_positive_total_extra_hours+=$row2["total_extra_hours"];
      $add_total_hours+=$row2["total_hours"];
      $add_workable_hours+=$row2["workable_hours"];
      $add_extra_hours+=$row2["extra_hours"];
      $add_total_extra_hours+=$row2["total_extra_hours"];
    }
    foreach($worked_hours_consult as $k=>$row2) {
      // Use @ (error quieting) to prevent division by zero. Result will be zero anyway...
      $percent_row[$k]=@(100*$row2["total_extra_hours"]/$add_positive_total_extra_hours);
    }
    
    if ($sheets==5) {
      $add_totals=array($add_total_extra_hours);
    } else {
      $add_totals=array($add_total_hours,$add_workable_hours,$add_extra_hours,
        $add_total_extra_hours);
    }
  }

} //end if !empty(sheets)

if (empty($error)&&$init!=""&&$end!="") {
  $init=date_sql_to_web($init);
  $end=date_sql_to_web($end);
}

$querys1=array(
  "PERSONS"=>array(
    "---",
    2=>_("Person / task type"),
    3=>_("Person / project"),
    5=>_("Hourly personal brief"),
    6=>_("Hourly personal brief (explained)")),
  "PROJECTS"=>array(
    "---",
    1=>_("Project / task type"),
    3=>_("Person / project"),
    4=>_("Project evaluation (global)"),
    7=>_("Project evaluation (period)")));

if ($flag=="PERSONS") {
  $title=_("Person evaluation");
  $detailsURL="userdetails.php";
} else {
  $title=_("Project evaluation");
  $detailsURL="projectdetails.php";
}

require("include/template-pre.php");
if (!empty($error)) msg_fail($error);

?>
<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">
<center>
<form name="results" method="post">
  <input type="hidden" name="day" value="<?=$day?>">
<b><?=_("Sheets")?>:</b>
<select name="sheets" onchange="javascript: document.results.submit();">
<?=array_to_option(array_values($querys1[$flag]),$sheets,array_keys($querys1[$flag]))?>
</select>
<br><br><br>

<table>
<?
if ($sheets==4 || $sheets==7) {
  $activationOptions=array();
  $activationOptions[1]=_("All projects");
  $activationOptions[2]=_("Only active projects");
  $activationOptions[3]=_("Only non active projects");
?>
 <tr>
  <td align="right"><b><?=_("Area")?>:</b></td>
  <td>
   <select name="area" onchange="javascript: document.results.submit();">
     <?=array_to_option(array_values($areasList), $area, array_keys($areasList))?>
   </select> 
  </td> 
 </tr>
 <tr>
  <td align="right"><b><?=_("Activation")?></b></td>
  <td>
    <select name="showActivation" onchange="javascript: document.results.submit();">
      <?=array_to_option(array_values($activationOptions),$showActivation,array_keys($activationOptions))?>
    </select>
  </td>
 <tr>
  <td align="right"><b><?=_("Project type")?></b></td>
  <td>
    <select name="ptype" onchange="javascript: document.results.submit();">
      <?=array_to_option(array_values($ptypesList),$ptype,array_keys($ptypesList))?>
    </select>
  </td>
 </tr>
 <tr>
  <td>
<?=_("Minimun invoice");?>:
  </td>
  <td>
    <input type="text" name="minInvoice" value="<?=$minInvoice?>">
  </td> 
 </tr>
 <tr>
  <td>
<?=_("Maximun invoice");?>:
  </td>
  <td>
    <input type="text" name="maxInvoice" value="<?=$maxInvoice?>">
  </td> 
 </tr>
<?
}

if ($sheets!=4) {
  if ($sheets!=5) {
?>
 <tr>
  <td>
<?=_("Start date");?>:
  </td>
  <td>
    <input type="text" name="init" value="<?=$init?>">
  </td> 
 </tr>
<?
  }
?>
 <tr>
  <td>
<?=_("End date");?>:
  </td>
  <td>
    <input type="text" name="end" value="<?=$end?>">
  </td> 
 </tr>
<?
}
?>
</table>

<input type="submit" name="view" value="<?=_("View")?>">


<br><br><br>

<?

if (empty($error)&&$sheets!=0) {

  if (!empty($sheets)&& $init!="" && $end!=""||!empty($view)) {
?>
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
    if ($sheets!=4 && $sheets!=7) {
      foreach ((array)$$col_title_var as $col) {	
?>
  <td bgcolor="#FFFFFF" class="title_box">
<?  
    if ($sheets==3) { 
?>
    <a href="userdetails.php?id=<?=$col?>"><?=$col?></a>
<?    
    } else {
?>
      <?=$col?>
<?
    }
?>
  </td>
<?
      }
      if ($sheets!=5 && $sheets!=6) {
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
<?
      }
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=_("Percent")?></td>
<?
    } else if ($sheets==7) {
      if ($showAllProjects) {
      $titles=array(_("Total worked hours"),_("Total staff cost"),_("Total profit"),_("Total profit per hour"),_("Worked hours within period"),_("Estimated hours"),
          _("Invoice"),_("EUR/h real"),_("EUR/h est."), 
		      _("%"), _("EUR/h real pond"), _("EUR/h estim pond"), _("Total staff cost within period"), _("Activation"));
      } else if ($showNonActiveProjects) {
      $titles=array(_("Total worked hours"),_("Total staff cost"),_("Total profit"),_("Total profit per hour"),_("Worked hours within period"),_("Estimated hours"),
          _("Invoice"),_("EUR/h real"),_("EUR/h est."), 
		      _("%"), _("EUR/h real pond"), _("EUR/h estim pond"), _("Total staff cost within period"));
      } else {
      $titles=array(_("Total worked hours"), _("Total staff cost"),_("Total profit"),_("Total profit per hour"),_("Worked hours within period"),_("Estimated hours"), 
          _("Invoice"),_("EUR/h real"),_("EUR/h est."), _("Total staff cost within period"));
      }
      foreach ((array)$titles as $col) {
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=$col?></td>
<?
     }
    } else if ($sheets==4) {
      if ($showAllProjects) {
  $titles=array( _("Worked hours"),_("Estimated hours"),_("Desviation %"),
          _("Desviation abs"),_("Invoice"),_("EUR/h real"),_("EUR/h est."),_("Total staff cost"),_("Total profit"),_("Total profit per hour"),_("Activation"));
      } else {
  $titles=array( _("Worked hours"),_("Estimated hours"),_("Desviation %"),
          _("Desviation abs"),_("Invoice"),_("EUR/h real"),_("EUR/h est."),_("Total staff cost"),_("Total profit"),_("Total profit per hour"));
      }
      foreach ((array)$titles as $col) {
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=$col?></td>
<?
      }
    } else {
      if ($showAllProjects) {
	$titles=array( _("Worked hours"),_("Estimated hours"),_("Desviation %"),
		      _("Desviation abs"),_("Invoice"),_("EUR/h real"),_("EUR/h est."),_("Total profit"),_("Total profit per hour"),_("Activation"));
      } else {
	$titles=array( _("Worked hours"),_("Estimated hours"),_("Desviation %"),
		      _("Desviation abs"),_("Invoice"),_("EUR/h real"),_("EUR/h est."),_("Total profit"),_("Total profit per hour"));
      }
      foreach ((array)$titles as $col) {
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=$col?></td>
<?
      }
    }
?>
</tr>
<?
  $odd_even = 0; /* start with odd rows */
  foreach ((array)$$row_title_var as $row) {
?>
<tr class="<?=($odd_even==0)?odd:even?>">
  <td bgcolor="#FFFFFF" class="title_box">
<?  
    if ($sheets==3) { 
?>
    <a href="projectdetails.php?id=<?=$row?>"><?=$row?></a>
<?    
    } else {
?>
    <a href="<?=$detailsURL?>?id=<?=$row?>"><?=$row?></a>
<?
    }
?>
  </td>
<? 
    foreach ((array)$$col_title_var as $col) {
      if ($a[$row][$col]==""){
?>
  <td bgcolor="#FFFFFF" class="text_data">&nbsp;</td>
<?    
      } else if ($a[$col]==""&&$a[""][$col]!=""){
?>
  <td bgcolor="#FFFFFF" class="text_data">
<?
     if ($col=="activation") {
       echo(($a[$row][$col]=='t')?_("Yes"):_("No"));
     } else {
       if (($sheets == 4 || $sheets==7) && ($a[$row][$col] >= 0)) { 
?>    
    <span class="pos_value">
<?       } else if ($sheets == 4 || $sheets==7) { ?>    
    <span class="neg_value">
<?       } else { ?>    
    <span>    
<?       } ?>
      <?=sprintf("%01.2f",$a[$row][$col])?>
    </span>
<?  }  ?>
  </td>
<?
      } else {
?>
  <td bgcolor="#FFFFFF" class="text_data">
<?
     if ($col=="activation") {
       echo(($a[$row][$col]=='t')?_("Yes"):_("No"));
     } else {
       if (($sheets == 4 || $sheets==7) && ($a[$row][$col] >= 0)) { 
?>
    <span class="pos_value">
<?       } else if ($sheets == 4 || $sheets==7) { ?>    
    <span class="neg_value">
<?       } else { ?>    
    <span>    
<?       } ?>
      <?=sprintf("%01.2f",$a[$row][$col])?>
    </span>
<?  }  ?>
  </td>
<?
      }
    }

    if ($sheets!=4 && $sheets!=7) {
      if ($sheets!=5 && $sheets!=6) {
?>
  <td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_row[$row])?></b></td>
<?
      }
?>
  <td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row[$row])?></b></td>
<?
    }
?>
</tr>
<?
    $odd_even = ($odd_even+1)%2; /* update odd_even counter */  
  }
?>
<tr>
  <td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
<?
  foreach ((array)$$col_title_var as $col) {
    if ($sheets!=4 && $sheets!=7 && $sheets!=5 && $sheets!=6) {
?>
  <td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_col[$col])?></b></td>
<?
    } 
  }
  
  if ($sheets!=4 && $sheets!=7 && $sheets!=5 && $sheets!=6) {
?>
  <td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours)?></b></td>
  <td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row["tot"])?></b></td>
<?
  } else {
    foreach ((array)$add_totals as $total) {
?>
  <td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$total)?></b></td>
<?
    }
    if ($sheets!=4 && $sheets!=7 || $showAllProjects) {
?>
  <td bgcolor="#FFFFFF" class="text_data">&nbsp;</td>
<?
    }
  }
?>
</tr>
<?
  if ($sheets!=4 && $sheets!=5 && $sheets!=6 && $sheets!=7) {
?>
<tr>
  <td bgcolor="#FFFFFF" class="title_box"><?=_("Percent")?></td>
<?
    foreach ((array)$$col_title_var as $col) {
?>
  <td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_col[$col])?></b></td>
<?
    }
?>
  <td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_col["tot"])?></b></td>
  <td bgcolor="#FFFFFF" class="text_percent">&nbsp;</td>
</tr>
<?
  }
?>

<!-- end title box -->
</table>

</td></tr></table>
<!-- end box -->
<?
}
?>

</form>
</center>
<?
}//end if (!empty($sheets))
?>
</td>

<td style="width: 25ex" valign="top">
<? require("include/show_sections.php") ?>
<br>
<? require("include/show_calendar.php") ?>
</td>
</table>
<?
require("include/template-post.php");
?>
