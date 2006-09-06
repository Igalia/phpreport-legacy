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
require_once("include/util.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");
require_once("include/extra_hours.php");

$die=_("Can't finalize the operation");

$result=@pg_exec($cnx,$query="SELECT fest,city FROM holiday ORDER BY fest")
   or die($die);

for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
$val=date_SQL_to_web($row["fest"]);
$arrayDMA=date_web_to_arrayDMA($val);
$holidays[$row["city"]][]=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
 }
@pg_freeresult($result);

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

 $users=@pg_exec($cnx,$query="SELECT uid FROM users ORDER by uid")
  or die($die);
 $users_consult=array();
   for ($i=0;$row=@pg_fetch_array($users,$i,PGSQL_ASSOC);$i++) {
    $users_consult[]=$row["uid"];
   }
   @pg_freeresult($users);

 $extra_hours=@pg_exec($cnx,$query="SELECT hours FROM extra_hours")
  or die($die);
 $extra_consult=array();
   for ($i=0;$row=@pg_fetch_array($extra_hours,$i,PGSQL_ASSOC);$i++) {
    $extra_consult[]=$row["uid"];
   }
   @pg_freeresult($extra_hours);


    if ($init!=""||$end!="") {
     if(!preg_match("/[0-9]{1,2}\/[0-9]{2,2}\/[0-9]{4,4}/",$init)||!preg_match("/[0-9]{1,2}\/[0-9]{2,2}\/[0-9]{4,4}/",$end)) {
     $error=_("Formato de fecha incorrecto");
     }
}
 if (($sheets!=0||!empty($view))&&$init==""&&$end=="") $error="Las fechas no pueden ser vacias";

if (!empty($sheets)&&$sheets!="0"&&empty($error)) {
$init=date_web_to_SQL($init);
$end=date_web_to_SQL($end);
 if ($sheets==1) {
   $data=@pg_exec($cnx,$query="SELECT type, name, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '$init' AND _date <= '$end' ) ) GROUP BY type, name ORDER BY name ASC")
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
   $data=@pg_exec($cnx,$query="SELECT type, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '$init' AND _date <= '$end' ) ) GROUP BY type, uid ORDER BY uid ASC")
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
   $data=@pg_exec($cnx,$query="SELECT name, uid, SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE ( ( _date >= '$init' AND _date <= '$end' ) ) GROUP BY name, uid ORDER BY uid ASC")
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
  if($add_hours!=0) {
    $percent_row[$cod]+=$add_hours_row[$cod]*100/$add_hours;
    $percent_row["tot"]+=$add_hours_row[$cod]*100/$add_hours;
  }
 }
 foreach ($$col_title_var as $type) {
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

if ($sheets==5) {
   $data=@pg_exec($cnx,$query="SELECT uid, SUM( _end - init ) / 60.0 AS total_hours FROM task 
	WHERE ((_date >= '2006-01-01' AND _date <= '2006-12-31')) 
	GROUP BY uid ORDER BY uid ASC")
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
     }
     $add_total_hours+=$row2["total_hours"];    
   }

   foreach ($users_consult as $user) {
     $count[$user]=0;
     $die=_("Can't finalize the operation");
     $result=pg_exec($cnx,$query="SELECT * FROM periods WHERE uid='$user' ORDER BY init ")
       or die($die);
     for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
       $row["init"]=date_sql_to_web($row["init"]);
       if ($row["_end"]!="") $row["_end"]=date_sql_to_web($row["_end"]);
       else $row["_end"]="NULL";
       $periods[$user][]=$row;
     }
     @pg_freeresult($result);

     echo("<pre>");
     $workable_hours[$user]=workable_days($init,$end,$holidays,$periods[$user]);
     print_r($workable_hours);

     foreach ($workable_hours[$user] as $year){
       foreach ($year as $hours){
         $count[$user]+=$hours;
       }
       $add_extra_hours+=$row2["extra_hours"];
     }
     //print_r($count);
     $extra[$user]=$xtra_hours[$user]-$count[$user]+ $a[$user]["total_hours"];
     //print_r($extra[$user]);
     $a[$user]["extra_hours"]=$extra[$user];
     if ($extra[$user]>=0) $add_extra_hours+=$extra[$user];
     $add_totals=array($add_total_hours,$add_extra_hours);
     //print_r($add_extra_hours); 
   }//end foreach ($users_consult...
   foreach ($users_consult as $user) { 
     if($add_extra_hours!=0) {
       $percent_row[$user]+=$extra[$user]*100/$add_extra_hours;
       $percent_row["tot"]+=$extra[$user]*100/$add_extra_hours;
     }

     if (!empty($extra_count)) {
       $proba[$user]=$xtra_hours[$user]+$extra[$user];
     }
   }
   echo("</pre>");

} 
} //end if !empty(sheets)

if (empty($error)&&$init!=""&&$end!="") {
$init=date_SQL_to_web($init);
$end=date_SQL_to_web($end);
}

$querys1=array("PERSONS"=>array("---",2=>_("Person template"),3=>_("Person/project"),5=>_("Breef sheet")),
	      "PROJECTS"=>array("---",1=>_("Project template"),3=>_("Person/project"),4=>_("Project evaluation")));

$title=_("Project evaluation");
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
<?if ($sheets==5){?><input type="submit" name="extra_count" value="<?=_("Recuento horas")?>"><?}?>
<br><br><br>

<table>
 <tr>
  <td>
<?=_("Init date DD/MM/YYYY");?>:
  </td>
  <td>
    <input type="text" name="init" value="<?=$init?>">
  </td> 
 </tr>
 <tr>
  <td>
<?=_("End date DD/MM/YYYY");?>:
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

if (!empty($sheets)&& $init!="" && $end!=""||!empty($view)) {?>
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

<?if ($sheets!=5) {?><td bgcolor="#FFFFFF" class="title_box"><?=_("Total result");?></td><?}?>
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
<?if ($sheets!=5) {?><td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_row[$row]);?></b></td><?}?>
<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row[$row]);?></b></td>
<?}?>
</tr>
<?
}    
?>
<tr>
<td bgcolor="#FFFFFF" class="title_box"><?=_("Total result");?></td>
<?foreach ((array)$$col_title_var as $col) {
  if ($sheets!=4&&$sheets!=5) {?>
<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours_col[$col]);?></b></td>
<?} 
}
if ($sheets!=4&&$sheets!=5) { ?>
<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$add_hours);?></b></td>
<td bgcolor="#FFFFFF" class="text_percent"><b><?=sprintf("%01.2f",$percent_row["tot"]);?></b></td>
<?} else {
     foreach ($add_totals as $total) {?>
<td bgcolor="#FFFFFF" class="text_result"><b><?=sprintf("%01.2f",$total);?></b></td>
<?}}?>
</tr>
<tr>
<?if ($sheets!=4&&$sheets!=5) {?>
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
<?} }//end if (!empty($sheets))
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
