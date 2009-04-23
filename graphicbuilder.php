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
require_once("/usr/share/phplot/phplot.php");

/* if ($flag=="PROJECT" && !multi_in_array($board_group_names,(array)$session_groups)) { */
/*    // If the user in not in a group that belongs to the board members, she can't */
/*    // access the PROJECTS information  */
/*    header("Location: login.php"); */
/* } */

$die=_("Can't finalize the operation");

/* set content-type at HTTP header */
//header('Content-type: image/png');

/* Retrieve GET params */
$g_id = isset($id)?$id:"";
/* available types: bars, lines, linepoints, area, points, pie, thinbarline, squared */
$g_type = isset($type)?$type:"bars"; /* default type "bars" */
$g_flag = isset($flag)?$flag:"PROJECT"; /* default flag PROJECT */
$g_width = isset($width)?$width:800; /* default width 800px */
$g_height = isset($height)?$height:600; /* default height 600px */
$g_title = isset($title)?urldecode($title):$g_type." chart"; /* default title <type> chart*/
$g_startdate = isset($startdate)?$startdate:"NULL";
$g_enddate = isset($enddate)?$enddate:"NULL";

/* abort if no id is specified */
if ($g_id=="") return; 


/* RETRIEVE DATA TO DRAW THE GRAPH */

require_once("include/connect_db.php");

/* Check valid flag */
if (($g_flag == "PROJECT") || ($g_flag == "PERSON")) {

  /* Get start and end dates for queries */

  if (validate_date_web($g_startdate) && validate_date_web($g_enddate)) {
    if (cmp_web_dates($g_startdate, $g_enddate) > 0) {
      /* if start and end dates are valid, and start */
      /* date is greater than end date, swap them.   */
      $tmp = $g_startdate;
      $g_startdate = $g_enddate;
      $g_enddate = $tmp;
    }
  }

  /* get start date */
  $start_date = "";
  if (validate_date_web($g_startdate)) {
    $start_date = $g_startdate;
  } else {
    /* retrieve the first date of a task for this project/person */
    $first_date = "";
    $data = array();
    if ($g_flag == "PROJECT") {
      $data=@pg_exec($cnx, $query="SELECT MIN(_date) AS first_date FROM task WHERE name = '".$g_id."'")
	or die($die);
    } else if ($g_flag == "PERSON") {
      $data=@pg_exec($cnx, $query="SELECT MIN(_date) AS first_date FROM task WHERE uid = '".$g_id."'")
	or die($die);
    }
    $row=@pg_fetch_array($data,0,PGSQL_ASSOC);
    $first_date=$row["first_date"];
    @pg_freeresult($result);
    
    if (($first_date != "") && validate_date_web(date_sql_to_web($first_date))) {
      $first_date=date_sql_to_web($first_date);
    } else {
      return; /* error: first_date not found */
    }
    
    /* start date: the first day of the month when the first task was done */
    $first_date_arrayDMA=date_web_to_arrayDMA($first_date);
    $start_date = date_arrayDMA_to_web(array($first_date_arrayDMA[0], $first_date_arrayDMA[1], $first_date_arrayDMA[2]));
  }

  /* get end date */
  $end_date = "";
  if (validate_date_web($g_enddate)) {
    $end_date = $g_enddate;
  } else { /* if not set, take the first day of the next month starting at current day */   
    $today=getdate(time());
    $this_month=$today["mday"]."/".$today["mon"]."/".$today["year"];
    $end_date=day_month_moved($this_month, 1);
  }


  /* loop retrieving monthly data, since start_date to end_date */
  $exists_data = false;
  $continue = true;
  $monthly_hours = array();  
  $end_date_arrayDMA = date_web_to_arrayDMA($end_date);
  for ($current_date = $start_date; $continue ; $current_date=day_month_moved($current_date, 1)) {

    /* build label for this month */
    $current_date_arrayDMA = date_web_to_arrayDMA($current_date);
    if (strlen($current_date_arrayDMA[1])==1) $current_date_arrayDMA[1] = "0".$current_date_arrayDMA[1];
    if (strlen($current_date_arrayDMA[2])==1) $current_date_arrayDMA[2] = "0".$current_date_arrayDMA[2];
    $current_label = $current_date_arrayDMA[1]."/".substr($current_date_arrayDMA[2],2,2);    

    /* define lowest and highest limits, handling special cases */
    $next_month_date = day_month_moved($current_date, 1);
    $next_month_arrayDMA = date_web_to_arrayDMA($next_month_date);

    /* default values (neither first nor last month) */
    $lowest_date = date_arrayDMA_to_web(array(1, $current_date_arrayDMA[1], $current_date_arrayDMA[2]));
    $highest_date = date_arrayDMA_to_web(array(1, $next_month_arrayDMA[1], $next_month_arrayDMA[2]));

    /* first month: override lowest date */
    if ($current_date == $start_date) { 
      $lowest_date = $start_date;
    }

    /* last month: override highest date */
    if (($current_date_arrayDMA[1] == $end_date_arrayDMA[1]) 
	&& ($current_date_arrayDMA[2] == $end_date_arrayDMA[2])) { 
      $continue = false;
      $highest_date = $end_date;
    }

    /* convert dates to SQL */
    $lowest_date = date_web_to_sql($lowest_date);
    $highest_date = date_web_to_sql($highest_date);
    
    /* retrieve information for currente interval from DB */
    //	print_r("NOT LAST\n".cmp_web_dates(day_month_moved($current_date, 1), $end_date)."  ".$current_date);
    $data = false;
    if ($g_flag == "PROJECT") {
      $data=@pg_exec($cnx, $query="SELECT uid AS data_id, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '"
		     .$lowest_date."'::date AND _date < '".$highest_date."'::date ) AND name = '"
		     .$g_id."' ) GROUP BY uid ORDER BY uid ASC")  or die($die);
    } else if ($g_flag = "PERSON") {
      $data=@pg_exec($cnx, $query="SELECT CASE name WHEN '' THEN '"._("unknown project")
		     ."' ELSE name END AS data_id, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '"
		     .$lowest_date."'::date AND _date < '".$highest_date."'::date ) AND uid = '"
		     .$g_id."' ) GROUP BY name ORDER BY name ASC")  or die($die);
    }
    
    /* retrieve data for current month (if available) */
    if ($data) {
      if (pg_num_rows($data) > 0) {
	$exists_data = true;
	for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
	  $monthly_hours[$current_label][$row["data_id"]] = $row["add_hours"];
	}
      }
      @pg_freeresult($data);
    }
  }

  $data_consult = array();
  if ($g_flag == "PROJECT") {
    /* Retrieve projects which the user has worked in during the specified interval */
    $projects=@pg_exec($cnx,$query="SELECT DISTINCT uid FROM task WHERE name = '".$g_id."' AND _date BETWEEN '"
		       .date_web_to_sql($start_date)."' AND '".date_web_to_sql($end_date)."' ORDER BY uid ASC");
    for ($i=0;$row=@pg_fetch_array($projects,$i,PGSQL_ASSOC);$i++) {
      if ($row["uid"] != "") {
	$data_consult[]=$row["uid"];
      }
    }
    @pg_freeresult($projects);
  } else if ($g_flag == "PERSON") {
    /* Retrieve users who work/worked in this project during the specified interval */
    $users=@pg_exec($cnx,$query="SELECT DISTINCT CASE name WHEN '' THEN '"
		    ._("unknown project")."' ELSE name END FROM task WHERE uid = '"
		    .$g_id."' AND _date BETWEEN '".date_web_to_sql($start_date)."' AND '"
		    .date_web_to_sql($end_date)."' ORDER BY name ASC")
      or die($die);
    for ($i=0;$row=@pg_fetch_array($users,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row["name"];
    }
    @pg_freeresult($users);
  }
  
  /* BUILD THE GRAPH (if data available) */
  if ($exists_data) {
    /* Build graph data legend */
    $graph_legend=array();
    $unknown_legend = "";
    foreach ((array)$data_consult as $data_id) {
      /* Add uid to graph legend */
      $graph_legend[]=$data_id;
    }
    
    /* Build graph data array */
    $graph_data=array();
    foreach ($monthly_hours as $mh_rindex => $mh_row) {
      
      /* Fill info for each row */
      $current_info=array();
      $current_info=array($mh_rindex);
      
      foreach ((array)$data_consult as $id) {
	/* If we have no information about this uid, zero hours are shown */
	if (!isset($mh_row[$id])) {
	  $current_info[]=0.0;
	} else {
	  $current_info[]=$mh_row[$id];
	}
      }
      /* Add monthly data */
      $graph_data[]=$current_info;
    }
    
    $graph = new PHPlot($g_width, $g_height);
    $graph->SetDataValues($graph_data);
    $graph->SetFileFormat("png");
    $graph->SetDataType("text-data");
    $graph->SetPlotType($g_type);
    $graph->SetTitle($g_title);
    $graph->SetLegend($graph_legend);
    $graph->DrawGraph();
  }
}

require_once("include/close_db.php");
?>

