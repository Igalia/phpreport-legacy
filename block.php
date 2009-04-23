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

/**
 * HTTP PARAMETERS RECEIVED BY THIS PAGE:
 *
 * day = Day to show calendar for. DD/MM/YYYY format.
 * employee = Employee for whic the blocking is going to be performed.
 */

require_once("include/autenticate.php");
require_once("include/util.php");
require_once("include/prepare_calendar.php");
require_once("include/connect_db.php");

if (!in_array($admin_group_name,(array)$session_groups)) {
 header("Location: login.php");
}

if (!empty($apply)) {
 if ($employee=="all") {
  if (!@pg_exec($cnx,$query="UPDATE block SET _date='".date_web_to_sql($block_day)."'")) {
    $error=_("It has not been possible to update the block date");
  } else {
   $confirmation=_("The block date has been updated correctly");
  }
 } else {
  if (!@pg_exec($cnx,$query="UPDATE block SET "
   ."_date='".date_web_to_sql($block_day)."'"
   ." WHERE uid='$employee'")) {
    $error=_("It has not been possible to update the block date");
  } else {
   $confirmation=_("The block date has been updated correctly");
  }
 }
}

$months=array(
 _("January"),_("February"),_("March"),_("April"),_("May"),_("June"),
 _("July"),_("August"),_("September"),_("October"),_("November"),_("December"));
$days=array(
 _("Monday"),_("Tuesday"),_("Wednesday"),_("Thursday"),_("Friday"),_("Saturday"),_("Sunday"));

$employees=$session_users;
$employees["all"]=_("--- All ---");

if (empty($employee)) $employee="all";

$die=_("Can't finalize the operation");
if($employee!="all") $result=@pg_exec($cnx,$query="SELECT _date FROM block"
    ." WHERE uid = '$employee' ORDER BY _date DESC")
   or die($die);
else $result=@pg_exec($cnx,$query="SELECT _date FROM block"
    ." ORDER BY _date DESC")
   or die($die);
if($row=@pg_fetch_array($result,0,PGSQL_ASSOC)) $block_day=date_sql_to_web($row["_date"]);
@pg_freeresult($result);

if (!empty($day)) {
 $arrayDMA=date_web_to_arrayDMA($day);
 $today=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
} else {
 if (empty($block_day)) {
  $today=getdate(time());
  $today=getdate(mktime(0,0,0,$today["mon"],$today["mday"],$today["year"]));
 } else {
  $arrayDMA=date_web_to_arrayDMA($block_day);
  $today=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
 }
}
$day=date_arrayDMA_to_web(array($today["mday"],$today["mon"],$today["year"]));

$calendar=make_calendar($day);

$day_month_previous=day_month_moved($day, -1);
$day_month_next=day_month_moved($day, 1);
$day_year_previous=day_year_moved($day, -1);
$day_year_next=day_year_moved($day, 1);

require_once("include/close_db.php");
$title=_("Report blocking");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">
<center>
<form name="results" method="post">

   <table border="0">
    <tr>
     <td><b><?=_("Employee to block:")?></b></td>
     <td>
      <select name="employee"
       onchange="javascript:document.results.submit();">
       <?=array_to_option(array_values($employees),$employee,array_keys($employees))?>
      </select>
     </td>
     <td>
      <input type="submit" name="select" value="<?=_("Select")?>">
     </td>
    </tr>
   </table>

<br>

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Selection of blocking limit date")?>
<!-- end title box -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="text_box">
<table border="0" cellspacing="0" cellpadding="0"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->

   <table border="0" cellpadding="0" cellspacing="10" width="100%">
   <tr>
    <td>

     <table border="0" cellpadding="0" cellspacing="0"
      style="text-align: left; margin-left: auto; margin-right: auto;">
      <tr>
       <td bgcolor="#999999" style="text-align: center;">
        <table cellpadding="3" cellspacing="1" style="text-align: center;">
        <?
         $style=array(
          "B"=>"background: #FFE9E9; color: #000000; font-weight: regular; text-align: center",	  
          "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
          "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
          "N"=>"background: #E9FFE9; color: #000000; font-weight: regular; text-align: center",
          "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center",
	  "A"=>"background: #FFE9E9; color: #808080; font-weight: regular; text-align: center"
         );
        ?>
         <tr>

          <td style="background: #FFFFFF; font-weight: bold;
           text-decoration: none"
           colspan="7">
           <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
             <td style="background: #FFFFFF; width: 4ex">
             <a href="?day=<?=$day_month_previous?>&amp;employee=<?=$employee?>"
              style="color: #000000">&lt;</a>
             </td>
             <td style="background: #FFFFFF; width: 4ex">
             <a href="?day=<?=$day_month_next?>&amp;employee=<?=$employee?>"
              style="color: #000000">&gt;</a>
             </td>
             <td align="right">
              <input type="text" name="day" value="<?=$day?>"
               size="10" style="width: 100%; height: 100%"
               onchange="javascript:document.results.submit();">
             </td>
             <td align="right">
              <input type="submit" name="change" value="<?=_("Go to day")?>"
               style="width: 100%; height: 100%">
             </td>
            </tr>
           </table>
          </td>

         </tr>
         <tr>
        <?
         // Day title computing
         foreach ($days as $d) {
        ?>
          <td style="<?=$style["T"]?>">
          <?=$d?>
          </td>
        <?
         }
        ?>
         </tr>
       <?
        foreach ($calendar as $s) {
       ?>
        <tr>
        <?
         foreach ($s as $d) {
          if (!empty($block_day)) {
           if ((strcmp(date_web_to_sql($d[2]),date_web_to_sql($block_day))<0)&&($d[1]!="H")) 
	     {
	       if ($d[1]=="G") $d[1]="A";
	       else $d[1]="B";
	     }
          }
        ?>
          <td style="<?=$style[$d[1]]?>">
           <a href="block.php?day=<?=$d[2]?>&amp;employee=<?=$employee?>" style="<?=$style[$d[1]]?>"
           ><?=$d[0]?></a>
          </td>
        <?
         }
        ?>
         </tr>
       <?
        }
       ?>
        </table>

       </td>
      </tr>
     </table>
    </td>
    <td align="center">
     <b><?=_("Legend")?></b>
     <br><br>
     <table border="0" cellpadding="1" cellspacing="0"><tr><td bgcolor="#999999">
     <table border="0" cellpadding="0" cellspacing="0" style="text-align: center;">
      <tr><td style="<?=$style["B"]?>"><?=_("Blocked day")?></td></tr>
      <tr><td style="<?=$style["H"]?>"><?=_("Selected day")?></td></tr>
      <tr><td style="<?=$style["N"]?>"><?=_("Unblocked day")?></td></tr>
      <tr><td style="<?=$style["G"]?>"><?=_("Day of the previous or next month")?></td></tr>
     </table>
     </td></tr></table>

    </td>
   </tr>
  </table>

<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->

   <table border="0" cellpadding="20" cellspacing="0" style="text-align: center; margin-left: auto; margin-right: auto;">
    <tr>
     <td>
      <input type="submit" name="apply" value="<?=_("Block until selected day")?>">
      <input type="hidden" name="block_day" value="<?=$day?>">
     </td>
    </tr>
   </table>

</form>
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
