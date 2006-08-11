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


require_once("include/config.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

 $die=_("Can't finalize the operation");
 $code=@pg_exec($cnx,$query="SELECT code FROM label WHERE type='name' ORDER BY code")
  or die($die);
 $code_consult=array();
 $code_consult[]="(empty)";
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

 $users=@pg_exec($cnx,$query="SELECT uid FROM users")
  or die($die);
 $users_consult=array();
   for ($i=0;$row=@pg_fetch_array($users,$i,PGSQL_ASSOC);$i++) {
    $users_consult[]=$row["uid"];
   }
   @pg_freeresult($users);

if (!empty($sheets)) {
 if ($sheets==1) {
   $data=pg_exec($cnx,$query="SELECT type, name, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '2006-01-01' AND _date <= '2006-12-31' ) ) GROUP BY type, name ORDER BY name ASC")
   or die($die);
   $data_consult=array();
     for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
     }
   @pg_freeresult($data);
   $row_index=_("name");
   $col_index=_("type");
   $row_title_var="code_consult";
   $col_title_var="type_consult";
 }
 if ($sheets==2) {
   $data=pg_exec($cnx,$query="SELECT type, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '2006-01-01' AND _date <= '2006-12-31' ) ) GROUP BY type, uid ORDER BY uid ASC")
   or die($die);
   $data_consult=array();
     for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
     }
   @pg_freeresult($data);
   $row_index=_("uid");
   $col_index=_("type");
   $row_title_var="users_consult";
   $col_title_var="type_consult";
 }

 if ($sheets==3) {
   $data=pg_exec($cnx,$query="SELECT name, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '2006-01-01' AND _date <= '2006-12-31' ) ) GROUP BY name, uid ORDER BY uid ASC")
   or die($die);
   $data_consult=array();
     for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
     }
   @pg_freeresult($data);
   $row_index=_("name");
   $col_index=_("uid");
   $row_title_var="code_consult";
   $col_title_var="users_consult";
 }

 foreach($data_consult as $row2) {
  foreach ((array)$row2 as $value) {

    if($row2[$row_index]=="") $row2[$row_index]="(empty)";
    $a[$row2[$row_index]][$row2[$col_index]]=$row2["add_hours"];
  }
  $add_hours_row[$row2[$row_index]]+=$row2["add_hours"];
  $add_hours_col[$row2[$col_index]]+=$row2["add_hours"];
  $add_hours+=$row2["add_hours"];
 } 
 foreach ($$row_title_var as $cod) {
  $percent_row[$cod]+=$add_hours_row[$cod]*100/$add_hours;
  $percent_row["tot"]+=$add_hours_row[$cod]*100/$add_hours;
 }
 foreach ($$col_title_var as $type) {
  $percent_col[$type]+=$add_hours_col[$type]/$add_hours*100;
  $percent_col["tot"]+=$add_hours_col[$type]/$add_hours*100;
 }


if ($sheets==4) {
   $data=pg_exec($cnx,$query="SELECT DISTINCT tblTotal.name, total_hours, proj.invoice, proj.est_hours
 FROM 
(SELECT SUM( _end - init ) / 60.0 AS total_hours, name
 FROM task GROUP BY name
) AS tblTotal
LEFT JOIN
(SELECT id,est_hours,invoice 
FROM projects GROUP BY id,est_hours,invoice
) AS proj ON (proj.id=tblTotal.name),
(SELECT SUM( _end - init ) / 60.0 AS year_hours, name 
FROM task WHERE ( ( _date >= '2006-01-01' AND _date <= '2006-12-31' ) )
 GROUP BY name
) AS tblYear 
WHERE tblTotal.name=tblYear.name  ORDER BY name")
   or die($die);
   $data_consult=array();
     for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
     }
   @pg_freeresult($data);
   $row_index=_("name");
   $row_title_var="code_consult";
   $project_consult=array("total_hours","est_hours","desv1","desv2","invoice","eur_real","eur_pres");
   $col_title_var="project_consult";

 foreach($data_consult as $row2) {
  foreach ((array)$row2 as $value) {
    if($row2[$row_index]=="") $row2[$row_index]="(empty)";
    $a[$row2[$row_index]][$project_consult[0]]=$row2["total_hours"];
    $a[$row2[$row_index]][$project_consult[1]]=$row2["est_hours"];
    if ($row2[$row_index]!="(empty)") {
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
  $add_totals=array($add_total_hours,$add_est_hours,$add_desv1,$add_desv2,$add_invoice,$add_eur_real,$add_eur_pres);

 }
 } 

/*if ($sheets==5) {
   $data=pg_exec($cnx,$query="SELECT uid, SUM( _end - init ) / 60.0 AS total_hours FROM task WHERE ( ( _date >= '2006-01-01' AND _date <= '2006-12-31' ) ) GROUP BY uid ORDER BY uid ASC")
   or die($die);
   $data_consult=array();
     for ($i=0;$row=@pg_fetch_array($data,$i,PGSQL_ASSOC);$i++) {
      $data_consult[]=$row;
     }
   @pg_freeresult($data);
   $row_index=_("uid");
   $row_title_var="users_consult";
   $project_consult=array("total_hours","extra_hours");
   $col_title_var="project_consult";

 foreach($data_consult as $row2) {
  foreach ((array)$row2 as $value) {
    $a[$row2[$row_index]][$project_consult[0]]=$row2["total_hours"];
    $a[$row2[$row_index]][$project_consult[1]]=$row2["extra_hours"];       
  }
  $add_total_hours+=$row2["total_hours"];
  $add_est_hours+=$row2["extra_hours"];
  $add_totals=array($add_total_hours,$add_extra_hours);

 }
} */

} //fin if !empty(sheets)


$querys=array("---",_("Project template"),_("Person template"),_("Person/project"),_("Project evaluation"),_("Breef sheet"));

$title=_("Project evaluation");
require("include/template-pre.php");
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
<?=array_to_option(array_values($querys),$sheets,array_keys($querys))?>
</select>
<br><br><br>

<?if (!empty($sheets)&&$sheets!="---") {?>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="2" width="100%">
<!-- title box -->
<tr>
<td class="title_table"><?=$row_index;?></td>
<td colspan="100%"  class="title_table"><?=$col_index;?></td>
</tr></td>
<td colspan="100%" bgcolor="#000000">
<tr>
<td bgcolor="#FFFFFF" class="title_box"></td>

<?if ($sheets!=4) {
foreach ((array)$$col_title_var as $col) {
?><td bgcolor="#FFFFFF" class="title_box">
      <?=$col?>
      </td>
<?  }?>

<td bgcolor="#FFFFFF" class="title_box"><?=_("Total result");?></td>
<td bgcolor="#FFFFFF" class="title_box"><?=_("Percent");?></td>

<?} else { $titles=array(_("Performed hours"),_("Estimated hours"),_("Desviation %"),_("Desviation abs"),_("Invoice"),_("€/h real"),_("€/h est."));
foreach ($titles as $col) {
?>
<td bgcolor="#FFFFFF" class="title_box">
      <?=$col?>
      </td>
<?
  }
}?>

</tr>
<?
 foreach ($$row_title_var as $row) {
?>
  <tr>
   <td bgcolor="#FFFFFF" class="title_box">
   <?=$row?>
   </td>

<? 
  foreach ($$col_title_var as $col) {
      if ($a[$row][$col]==""){?>
   <td bgcolor="#FFFFFF" class="text_data"></td>
<?    }else if ($a[$col]==""&&$a[""][$col]!=""){?>
   <td bgcolor="#FFFFFF" class="text_data"><?=sprintf("%01.2f",$a[$row][$col]);?></td>
<?    }else {?>
   <td bgcolor="#FFFFFF" class="text_data"><?=sprintf("%01.2f",$a[$row][$col]);?></td>
<?
      }
  }
if ($sheets!=4) {?>
<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_row[$row]);?></b></td>
<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row[$row]);?></b></td>
<?}?>
</tr>
<?
}    
?>
<tr>
<td bgcolor="#FFFFFF" class="title_box"><?=_("Total result");?></td>
<?foreach ((array)$$col_title_var as $col) {
  if ($sheets!=4) {?>
<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_col[$col]);?></b></td>
<?} 
}
if ($sheets!=4) { ?>
<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours);?></b></td>
<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row["tot"]);?></b></td>
<?} else {
     foreach ($add_totals as $total) {?>
<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$total);?></b></td>
<?}}?>
</tr>
<tr>
<?if ($sheets!=4) {?>
<td bgcolor="#FFFFFF" class="title_box"><?=_("Percent");?></td>
<?foreach ((array)$$col_title_var as $col) {
?>
<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_col[$col]);?></b></td>
<?}?>
<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_col["tot"]);?></b></td>
<?}?>
</tr>
<!-- end title box -->
</td></tr></table>
</td></tr></table>
<!-- end box -->
</form>
</center>
<?} //end if (!empty($sheets))
?>

<td style="width: 25ex" valign="top">
<? require("include/show_sections.php") ?>
<br>
<? require("include/show_calendar.php") ?>
</td>
</table>
<?
require("include/template-post.php");
?>
