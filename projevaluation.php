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


// SHEETS:
// 1 - Project / task type
// 2 - Person / task type
// 3 - Person / project
// 4 - Project evaluation
// 5 - Hourly personal brief

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

$result=@pg_exec($cnx,$query="SELECT fest,city FROM holiday ORDER BY fest")
   or die($die);
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $val=date_sql_to_web($row["fest"]);
  $arrayDMA=date_web_to_arrayDMA($val);
  $holidays[$row["city"]][]=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
}
@pg_freeresult($result);

$code=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='name' ORDER BY code")
  or die($die);
$code_consult=array();
$code_consult[]=_("(empty)");
for ($i=0;$row=@pg_fetch_array($code,$i,PGSQL_ASSOC);$i++) {
  $code_consult[]=$row["code"];
}
@pg_freeresult($code);

$type=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='type'")
  or die($die);
$type_consult=array();
for ($i=0;$row=@pg_fetch_array($type,$i,PGSQL_ASSOC);$i++) {
  $type_consult[]=$row["code"];
}
@pg_freeresult($type);

$users=@pg_exec($cnx,$query="SELECT uid FROM users ORDER by uid")
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
if (($sheets!=0||!empty($view))&&$init==""&&$end=="") $error=_("Dates can't be void");

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
    foreach ((array)$row2 as $value) {
      if($row2[$row_index]=="") $row2[$row_index]=_("(empty)");
      $a[$row2[$row_index]][$row2[$col_index]]=$row2["add_hours"];
    }
    $add_hours_row[$row2[$row_index]]+=$row2["add_hours"];
    $add_hours_col[$row2[$col_index]]+=$row2["add_hours"];
    $add_hours+=$row2["add_hours"];
  } 
  foreach ((array)$$row_title_var as $cod) {
    if($add_hours!=0) {
      $percent_row[$cod]+=$add_hours_row[$cod]*100/$add_hours;
      $percent_row["tot"]+=$add_hours_row[$cod]*100/$add_hours;
    }
  }
  foreach ((array)$$col_title_var as $type) {
    if ($add_hours!=0) {
      $percent_col[$type]+=$add_hours_col[$type]/$add_hours*100;
      $percent_col["tot"]+=$add_hours_col[$type]/$add_hours*100;
    }
  }

  if ($sheets==4) {
    $data=@pg_exec($cnx,$query="SELECT DISTINCT tblTotal.name, total_hours, proj.invoice, proj.est_hours
      FROM 
      (SELECT SUM( _end - init ) / 60.0 AS total_hours, name
      FROM task GROUP BY name
      ) AS tblTotal
      LEFT JOIN
      (SELECT id,est_hours,invoice 
      FROM projects GROUP BY id,est_hours,invoice
      ) AS proj ON (proj.id=tblTotal.name),
      (SELECT SUM( _end - init ) / 60.0 AS year_hours, name 
      FROM task WHERE ( ( _date >= '$init' AND _date <= '$end' ) )
      GROUP BY name
      ) AS tblYear 
      WHERE tblTotal.name=tblYear.name  ORDER BY name")
    or die($die);
    $data_consult=array();
    for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
    }
    @pg_freeresult($data);
    $row_index="name";
    $row_index_trans=_("Name");
    $row_title_var="code_consult";
    $project_consult=array("total_hours","est_hours","desv1","desv2","invoice","eur_real","eur_pres");
    $col_title_var="project_consult";

    foreach($data_consult as $row2) {
      foreach ((array)$row2 as $value) {
        if($row2[$row_index]=="") $row2[$row_index]=_("(empty)");
        $a[$row2[$row_index]][$project_consult[0]]=$row2["total_hours"];
        $a[$row2[$row_index]][$project_consult[1]]=$row2["est_hours"];
        if ($row2[$row_index]!=_("(empty)")) {
          $a[$row2[$row_index]][$project_consult[2]]=(1-($row2["est_hours"]/$row2["total_hours"]))*100;
          $a[$row2[$row_index]][$project_consult[3]]=$row2["total_hours"]-$row2["est_hours"];
          $a[$row2[$row_index]][$project_consult[5]]=$row2["invoice"]/$row2["total_hours"];
          $a[$row2[$row_index]][$project_consult[6]]=$row2["invoice"]/$row2["est_hours"];
        }
        $a[$row2[$row_index]][$project_consult[4]]=$row2["invoice"];
      }
      $add_total_hours+=$row2["total_hours"];
      $add_est_hours+=$row2["est_hours"];
      $add_desv1+=$a[$row2[$row_index]][$project_consult[2]];
      $add_desv2+=$a[$row2[$row_index]][$project_consult[3]];
      $add_invoice+=$row2["invoice"];
      $add_eur_real+=$a[$row2[$row_index]][$project_consult[5]];
      $add_eur_pres+=$a[$row2[$row_index]][$project_consult[6]];
      $add_totals=array($add_total_hours,$add_est_hours,$add_desv1,$add_desv2,
      $add_invoice,$add_eur_real,$add_eur_pres);
    }
  } 

  if ($sheets==5) {

    $worked_hours_consult=net_extra_hours($cnx,$init,$end);

    // Select most recent overriden extra hours before 
    // the init date for each user
    $extra_hours=@pg_exec($cnx,$query="SELECT e.uid,e.hours,e.date
      FROM extra_hours AS e JOIN (
        SELECT uid,MAX(date) AS date FROM extra_hours
        WHERE date<='$init'
        GROUP BY uid) AS m ON (e.uid=m.uid AND e.date=m.date)")
      or die($die);
    $extra_hours_consult=array();
    for ($i=0;$row=@pg_fetch_array($extra_hours,$i,PGSQL_ASSOC);$i++) {
      $extra_hours_consult[$row["uid"]]=$row;
    }
    @pg_freeresult($extra_hours);

    // Let's compute the accumulated extra hours before the period we're computing now
    foreach (array_keys($worked_hours_consult) as $k) {
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
      if (!empty($h[$k]["period_extra_hours"])) $previous_hours+=$h[$k]["period_extra_hours"];
      
      // Put them all
      $worked_hours_consult[$k]["total_extra_hours"]=$worked_hours_consult[$k]["period_extra_hours"]+$previous_hours;
    }

    $row_index="uid";
    $row_index_trans=_("User");
    $row_title_var="users_consult";
    $project_consult=array("total_hours","period_extra_hours","total_extra_hours");
    $col_title_var="project_consult";

    $a=$worked_hours_consult;
    
    foreach($worked_hours_consult as $row2) {
      if ($row2["total_extra_hours"]>0) $add_positive_total_extra_hours+=$row2["total_extra_hours"];
      $add_total_hours+=$row2["total_hours"];
      $add_period_extra_hours+=$row2["period_extra_hours"];
      $add_total_extra_hours+=$row2["total_extra_hours"];
    }
    foreach($worked_hours_consult as $k=>$row2) {
      // Use @ (error quieting) to prevent division by zero. Result will be zero anyway...
      $percent_row[$k]=@(100*$row2["total_extra_hours"]/$add_positive_total_extra_hours);
    }
    
    $add_totals=array($add_total_hours,$add_period_extra_hours,$add_total_extra_hours);
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
    5=>_("Hourly personal brief")),
  "PROJECTS"=>array(
    "---",
    1=>_("Project / task type"),
    3=>_("Person / project"),
    4=>_("Project evaluation")));

if ($flag=="PERSONS") $title=_("Person evaluation");
else $title=_("Project evaluation");


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
    if ($sheets!=4) {
      foreach ((array)$$col_title_var as $col) {
?>
  <td bgcolor="#FFFFFF" class="title_box">
        <?=$col?>
  </td>
<?
      }
      if ($sheets!=5) {
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
<?
      }
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=_("Percent")?></td>
<?
    } else {
      $titles=array(
        _("Worked hours"),_("Estimated hours"),_("Deviation %"),
        _("Desviation abs"),_("Invoice"),_("EUR/h real"),_("EUR/h est."));
      foreach ((array)$titles as $col) {
?>
  <td bgcolor="#FFFFFF" class="title_box"><?=$col?></td>
<?
    }
  }
?>
</tr>
<?
  foreach ((array)$$row_title_var as $row) {
?>
<tr>
  <td bgcolor="#FFFFFF" class="title_box"><?=$row?></td>
<? 
    foreach ((array)$$col_title_var as $col) {
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

    if ($sheets!=4) {
      if ($sheets!=5) {
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
  }
?>
<tr>
  <td bgcolor="#FFFFFF" class="title_box"><?=_("Total result")?></td>
<?
  foreach ((array)$$col_title_var as $col) {
    if ($sheets!=4&&$sheets!=5) {
?>
  <td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_col[$col])?></b></td>
<?
    } 
  }
  
  if ($sheets!=4&&$sheets!=5) {
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
    if ($sheets!=4) {
?>
  <td bgcolor="#FFFFFF" class="text_data">&nbsp;</td>
<?
    }
  }
?>
</tr>
<?
  if ($sheets!=4&&$sheets!=5) {
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
</form>
</center>
<?
} 
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
