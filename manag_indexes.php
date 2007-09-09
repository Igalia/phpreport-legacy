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

require_once("include/config.php");
require_once("include/util.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

$die=_("Can't finalize the operation");

if (empty($min_percent_hour_dev)) $min_percent_hour_dev=25;
if (empty($max_percent_hour_dev)) $max_percent_hour_dev=50;
if (empty($min_profit)) $min_profit=10;
if (empty($max_profit)) $max_profit=30;

$init_end_condition=" p.init IS NOT NULL ";
if (!empty($init)) $init_end_condition.=" AND p.init>='".date_web_to_sql($init)."' ";
$init_end_condition.=" AND ";
$init_end_condition.=" p._end IS NOT NULL ";
if (!empty($end)) $init_end_condition.=" AND p._end<='".date_web_to_sql($end)."' ";

// PERCENT HOUR DEVIATION AGAINST ESTIMATION (ALL HOURS)

$data_hour_dev_all=array();
$result=@pg_exec($cnx,$query=
  "
  SELECT 
    tot.id,
    (tot.tot_wk_hours-tot.est_hours)/tot.est_hours*100 AS tot_dev
  FROM
    (SELECT p.id, p.est_hours, SUM( t._end - t.init ) / 60.0 AS tot_wk_hours
    FROM projects p
    JOIN task t ON (t.name=p.id)
    WHERE ".$init_end_condition."
    GROUP BY p.id, p.est_hours) AS tot
  WHERE
    tot.est_hours>0    
  ORDER BY tot_dev    
  "
  ) or die($die);
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $data_hour_dev_all[$row["id"]]=$row["tot_dev"];
}  
@pg_freeresult($result);

// PERCENT HOUR DEVIATION AGAINST ESTIMATION (ONLY PEX HOURS)

$data_hour_dev_pex=array();
$result=@pg_exec($cnx,$query=
  "
  SELECT 
    pex.id,
    (pex.pex_wk_hours-pex.est_hours)/pex.est_hours*100 AS pex_dev
  FROM
    (SELECT p.id, p.est_hours, SUM( t._end - t.init ) / 60.0 AS pex_wk_hours
    FROM projects p
    JOIN task t ON (t.name=p.id)
    WHERE t.type='pex' AND ".$init_end_condition."
    GROUP BY p.id, p.est_hours) AS pex  
  WHERE pex.est_hours>0
  ORDER BY pex_dev  
  "
  ) or die($die);
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $data_hour_dev_pex[$row["id"]]=$row["pex_dev"];
}  
@pg_freeresult($result);

// EUR/H PROFIT DEVIATION AGAINST ESTIMATION (ALL HOURS)

$data_prof_dev_all=array();
$result=@pg_exec($cnx,$query=
  "
  SELECT 
    tot.id,
    (tot.invoice - tot.tot_cost) / tot_hours as tot_hour_profit
  FROM
    (select p.id, p.invoice,
      SUM( t._end - t.init ) / 60.0 AS tot_hours,
      SUM( ( t._end - t.init ) / 60.0 * hour_cost) AS tot_cost
    FROM projects p
      JOIN task t ON (t.name=p.id)
      LEFT JOIN periods ON (
        (t.uid=periods.uid) AND
        (periods.init IS NULL OR periods.init<=t._date) AND
        (periods._end IS NULL OR periods._end>=t._date) )
    WHERE ".$init_end_condition."
    GROUP BY p.id, p.invoice) AS tot  
  WHERE tot.invoice>0
  ORDER BY tot_hour_profit  
  "
  ) or die($die);
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $data_prof_dev_all[$row["id"]]=$row["tot_hour_profit"];
}  
@pg_freeresult($result);

// EUR/H PROFIT DEVIATION AGAINST ESTIMATION (ONLY PEX HOURS)

$data_prof_dev_pex=array();
$result=@pg_exec($cnx,$query=
  "
  SELECT 
    tot.id,
    (tot.invoice - pex.pex_cost) / pex_hours as pex_hour_profit
  FROM
    (select p.id, p.invoice,
      SUM( t._end - t.init ) / 60.0 AS tot_hours,
      SUM( ( t._end - t.init ) / 60.0 * hour_cost) AS tot_cost
    FROM projects p
      JOIN task t ON (t.name=p.id)
      LEFT JOIN periods ON (
        (t.uid=periods.uid) AND
        (periods.init IS NULL OR periods.init<=t._date) AND
        (periods._end IS NULL OR periods._end>=t._date) )
    WHERE ".$init_end_condition."
    GROUP BY p.id, p.invoice) AS tot,
  
    (SELECT p.id, SUM ( t._end - t.init ) / 60.0 AS pex_hours, 
      SUM( ( t._end - t.init ) / 60.0 * hour_cost) AS pex_cost
    FROM projects p
      JOIN task t ON (t.name=p.id)
      LEFT JOIN periods ON (
        (t.uid=periods.uid) AND
        (periods.init IS NULL OR periods.init<=t._date) AND
        (periods._end IS NULL OR periods._end>=t._date) )  
    WHERE t.type='pex' AND ".$init_end_condition."
    GROUP BY p.id) AS pex
  
  WHERE
    tot.id=pex.id
    AND tot.invoice>0
  ORDER BY pex_hour_profit
  "
  ) or die($die);
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $data_prof_dev_pex[$row["id"]]=$row["pex_hour_profit"];
}  
@pg_freeresult($result);

$title=_("Management indexes");

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
  <table>
    <tr>
      <td><?=_("Start date")?>:</td>
      <td>
        <input type="text" name="init" value="<?=$init?>">
      </td> 
    </tr>
    <tr>
      <td><?=_("End date")?>:</td>
     <td><input type="text" name="end" value="<?=$end?>"></td> 
    </tr>
    <tr>
      <td><?=_("Minimum percent for hour deviation")?>:</td>
      <td>
        <input type="text" name="min_percent_hour_dev" value="<?=$min_percent_hour_dev?>">
      </td> 
    </tr>
    <tr>
      <td><?=_("Maximum percent for hour deviation")?>:</td>
      <td>
        <input type="text" name="max_percent_hour_dev" value="<?=$max_percent_hour_dev?>">
      </td> 
    </tr>
    <tr>
      <td><?=_("Minimum EUR/h for profit analisys")?>:</td>
      <td>
        <input type="text" name="min_profit" value="<?=$min_profit?>">
      </td> 
    </tr>
    <tr>
      <td><?=_("Maximum EUR/h for profit analisys")?>:</td>
      <td>
        <input type="text" name="max_profit" value="<?=$max_profit?>">
      </td> 
    </tr>
  </table>
  <input type="submit" name="view" value="<?=_("View")?>">
</form> 

<br/>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="2" width="100%">
<!-- title box -->
<tr>
  <td colspan="4" class="title_table"><?=_("Percent hour deviation against estimation")?></td>
</tr>
<tr>
  <td colspan="2" class="title_table"><?=_("All")?></td>
  <td colspan="2" class="title_table"><?=_("PEX only")?></td>
</tr>
<tr>
  <td class="title_table"><?=_("Project")?></td>
  <td class="title_table"><?=_("Percent")?></td>
  <td class="title_table"><?=_("Project")?></td>
  <td class="title_table"><?=_("Percent")?></td>
</tr>
<?
  $odd_even = 0; /* start with odd rows */
  $k_h_all=array_keys($data_hour_dev_all);
  $k_h_pex=array_keys($data_hour_dev_pex);
  $dev_h_all_less=0;
  $dev_h_all_more=0;
  $dev_h_all_count=0;
  $dev_h_pex_less=0;
  $dev_h_pex_more=0;
  $dev_h_pex_count=0;
  for($i=0;$i<max(count($data_hour_dev_all),count($data_hour_dev_all));$i++) {
    $project_h_all=$k_h_all[$i];
    $project_h_pex=$k_h_pex[$i];
        
    $dev_h_all=$data_hour_dev_all[$project_h_all];
    $dev_h_pex=$data_hour_dev_pex[$project_h_pex];

    if ($dev_h_all<$min_percent_hour_dev) {
      $dev_h_all_color="#009900";
      $dev_h_all_less++;
    } else if ($dev_h_all>$max_percent_hour_dev) {
      $dev_h_all_color="#990000";
      $dev_h_all_more++;
    } else $dev_h_all_color="#000000";
    $dev_h_all_count++;  
        
    if ($dev_h_pex<$min_percent_hour_dev) {
      $dev_h_pex_color="#009900";
      $dev_h_pex_less++;
    } else if ($dev_h_pex>$max_percent_hour_dev) {
      $dev_h_pex_color="#990000";
      $dev_h_pex_more++;
    } else $dev_h_pex_color="#000000";
    $dev_h_pex_count++;
        
    if ($dev_h_all=="") $dev_h_all="&nbsp;";
    else $dev_h_all=sprintf("%01.2f",$dev_h_all);
    if ($dev_h_pex=="") $dev_h_pex="&nbsp;";
    else $dev_h_pex=sprintf("%01.2f",$dev_h_pex);

?>
<tr class="<?=($odd_even==0)?odd:even?>">
  <td bgcolor="#FFFFFF" class="title_box">
    <a href="projectdetails.php?id=<?=$project_h_all?>"><?=$project_h_all?></a>
  </td>
  <td bgcolor="#FFFFFF" class="text_data" style="color: <?=$dev_h_all_color?>">
    <?=$dev_h_all?>
  </td>
  <td bgcolor="#FFFFFF" class="title_box">
    <a href="projectdetails.php?id=<?=$project_h_pex?>"><?=$project_h_pex?></a>
  </td>
  <td bgcolor="#FFFFFF" class="text_data" style="color: <?=$dev_h_pex_color?>">
    <?=$dev_h_pex?>
  </td>
</tr>
<?
    $odd_even = ($odd_even+1)%2; /* update odd_even counter */  
  }
?>

<!-- end title box -->
</table>

</td></tr></table>
<!-- end box -->

<br/>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="2" width="100%">
<!-- title box -->
<tr>
  <td colspan="4" class="title_table"><?=_("Profit deviation against estimation")?></td>
</tr>
<tr>
  <td colspan="2" class="title_table"><?=_("All")?></td>
  <td colspan="2" class="title_table"><?=_("PEX only")?></td>
</tr>
<tr>
  <td class="title_table"><?=_("Project")?></td>
  <td class="title_table"><?=_("EUR/h")?></td>
  <td class="title_table"><?=_("Project")?></td>
  <td class="title_table"><?=_("EUR/h")?></td>
</tr>
<?
  $odd_even = 0; /* start with odd rows */
  $k_p_all=array_keys($data_prof_dev_all);
  $k_p_pex=array_keys($data_prof_dev_pex);
  $dev_p_all_less=0;
  $dev_p_all_more=0;
  $dev_p_all_count=0;
  $dev_p_pex_less=0;
  $dev_p_pex_more=0;
  $dev_p_pex_count=0;
  for($i=0;$i<max(count($data_prof_dev_all),count($data_prof_dev_pex));$i++) {
    $project_p_all=$k_p_all[$i];
    $project_p_pex=$k_p_pex[$i];
        
    $dev_p_all=$data_prof_dev_all[$project_p_all];
    $dev_p_pex=$data_prof_dev_pex[$project_p_pex];

    if ($dev_p_all<$min_profit) {
      $dev_p_all_color="#990000";
      $dev_p_all_less++;
    } else if ($dev_p_all>$max_profit) {
      $dev_p_all_color="#009900";
      $dev_p_all_more++;
    } else $dev_p_all_color="#000000";
    $dev_p_all_count++;
    
    if ($dev_p_pex<$min_profit) {
      $dev_p_pex_color="#990000";
      $dev_p_pex_less++;
    } else if ($dev_p_pex>$max_profit) {
      $dev_p_pex_color="#009900";
      $dev_p_pex_more++;
    } else $dev_p_pex_color="#000000";
    $dev_p_pex_count++;  
        
    if ($dev_p_all=="") $dev_p_all="&nbsp;";
    else $dev_p_all=sprintf("%01.2f",$dev_p_all);
    if ($dev_p_pex=="") $dev_p_pex="&nbsp;";
    else $dev_p_pex=sprintf("%01.2f",$dev_p_pex);

?>
<tr class="<?=($odd_even==0)?odd:even?>">
  <td bgcolor="#FFFFFF" class="title_box">
    <a href="projectdetails.php?id=<?=$project_p_all?>"><?=$project_p_all?></a>
  </td>
  <td bgcolor="#FFFFFF" class="text_data" style="color: <?=$dev_p_all_color?>">
    <?=$dev_p_all?>
  </td>
  <td bgcolor="#FFFFFF" class="title_box">
    <a href="projectdetails.php?id=<?=$project_p_pex?>"><?=$project_p_pex?></a>
  </td>
  <td bgcolor="#FFFFFF" class="text_data" style="color: <?=$dev_p_pex_color?>">
    <?=$dev_p_pex?>
  </td>
</tr>
<?
    $odd_even = ($odd_even+1)%2; /* update odd_even counter */  
  }
?>

<!-- end title box -->
</table>

</td></tr></table>
<!-- end box -->

<br/>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Management index analisys")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->

<?=sprintf(_('%01.2f percent of the projects have less than %01.2f percent of all hour deviation.'),
  $dev_h_all_less/$dev_h_all_count*100,$min_percent_hour_dev)?><br/>

<?=sprintf(_('%01.2f percent of the projects have less than %01.2f percent of PEX hour deviation.'),
  $dev_h_pex_less/$dev_h_pex_count*100,$min_percent_hour_dev)?><br/>

<?=sprintf(_('%01.2f percent of the projects have more than %01.2f percent of all hour deviation.'),
  $dev_h_all_more/$dev_h_all_count*100,$max_percent_hour_dev)?><br/>

<?=sprintf(_('%01.2f percent of the projects have more than %01.2f percent of PEX hour deviation.'),
  $dev_h_pex_more/$dev_h_pex_count*100,$max_percent_hour_dev)?><br/>

<br/>
  
<?=sprintf(_('%01.2f percent of the projects have less than %01.2f EUR/h of profit.'),
  $dev_p_all_less/$dev_p_all_count*100,$min_profit)?><br/>

<?=sprintf(_('%01.2f percent of the projects have less than %01.2f EUR/h of profit considering PEX hour only.'),
  $dev_p_pex_less/$dev_p_pex_count*100,$min_profit)?><br/>

<?=sprintf(_('%01.2f percent of the projects have more than %01.2f EUR/h of profit.'),
  $dev_p_all_more/$dev_p_all_count*100,$max_profit)?><br/>

<?=sprintf(_('%01.2f percent of the projects have more than %01.2f EUR/h of profit considering PEX hour only.'),
  $dev_p_pex_more/$dev_p_pex_count*100,$max_profit)?><br/>

<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->

</center>
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
