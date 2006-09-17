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

if (!in_array($admin_group_name,(array)$session_groups)) {
 header("Location: login.php");
}
if (!empty($ok)) $editing=true;
if (!empty($new_place)) 
$place=$new_place;

if (empty ($ok)&&empty($copy)&&empty($change)) {
  if ($insert=="true"&&!empty($place)) {
    $day=date_web_to_SQL($day);
  
    if (!$result=pg_exec($cnx,$query=
        "INSERT INTO holiday (fest,city)"
      ." VALUES ('$day','$place')")) {
      $error=_("Can't finalize the operation");
    }
  $day=date_SQL_to_web($day);
  }
  if ($insert=="false") {
    $day=date_web_to_SQL($day);
    if (!@pg_exec($cnx,$query=
        "DELETE FROM holiday WHERE fest='$day' AND city='$place'")) {
      $error=_("Can't finalize the operation");
    }
    $day=date_SQL_to_web($day);
  }
}
$die=_("Can't finalize the operation");

if (!empty($place)&&!empty($copy)){

  $arrayDMA=date_web_to_arrayDMA($day);
  $arrayDMA[0]="01";
  $arrayDMA[1]="01";
  $init1=date_web_to_SQL(date_arrayDMA_to_web($arrayDMA));
  $arrayDMA[2]=$arrayDMA[2]-1;
  $init=date_web_to_SQL(date_arrayDMA_to_web($arrayDMA));
  $arrayDMA[0]="31";
  $arrayDMA[1]="12";
  $arrayDMA[2]=$arrayDMA[2]+1;
  $end1=date_web_to_SQL(date_arrayDMA_to_web($arrayDMA));
  $arrayDMA[2]=$arrayDMA[2]-1;
  $end=date_web_to_SQL(date_arrayDMA_to_web($arrayDMA));
  
  if (!@pg_exec($cnx,$query=
    "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
    ."BEGIN TRANSACTION; ")) {
    $error=_("Can't finalize the operation");
    break;
  }
  
  if (!pg_exec($cnx,$query=
    "DELETE FROM holiday WHERE city='$place' AND (fest>='$init1' AND fest<='$end1')")) {
    $error=_("Can't finalize the operation");
  }
  
  $result=pg_exec($cnx,$query="SELECT fest,city FROM holiday WHERE city='$place' 
    AND (fest>='$init' AND fest<='$end')  ORDER BY fest")
    or die($die);
  
  for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
    $row=date_SQL_to_web($row["fest"]);
    $arrayDMA=date_web_to_arrayDMA($row);
    $fest=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]+1));
    if ($fest["weekday"]!="Saturday"&&$fest["weekday"]!="Sunday") {
      $holidays[]=$fest;
      $arrayDMA[2]=$arrayDMA[2]+1;
      $temp=date_web_to_SQL(date_arrayDMA_to_web($arrayDMA));
      if (!$ss=pg_exec($cnx,$query=
        "INSERT INTO holiday (fest,city)"
      ." VALUES ('$temp','$place')")) {
        $error=_("Can't finalize the operation");
      }
    }
  }
  
  @pg_exec($cnx,$query="COMMIT TRANSACTION");
  
  if (!empty($error)) {
    // Debugging
    $error.="<!-- $query -->";
    @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
    break;
  }
  @pg_freeresult($result);
} else {
  $result=@pg_exec($cnx,$query="SELECT fest,city FROM holiday WHERE city='$place' ORDER BY fest")
    or die($die);

  for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
    $row=date_SQL_to_web($row["fest"]);
    $arrayDMA=date_web_to_arrayDMA($row);
    $holidays[]=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
  }
  @pg_freeresult($result);
}

$city[]="---";
$result=@pg_exec($cnx,$query="SELECT DISTINCT city FROM holiday ORDER BY city")
   or die($die);

for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $city[]=$row["city"];
}
@pg_freeresult($result);

$months=array(
 _("January"),_("February"),_("March"),_("April"),_("May"),_("June"),
 _("July"),_("August"),_("September"),_("October"),_("November"),_("December"));
$days=array(
 _("Monday"),_("Tuesday"),_("Wednesday"),_("Thursday"),_("Friday"),_("Saturday"),_("Sunday"));

$day_month_previous=day_month_moved($day, -1);
$day_month_next=day_month_moved($day, 1);
$day_year_previous=day_year_moved($day, -1);
$day_year_next=day_year_moved($day, 1);

if (empty($day)) $calendar=make_calendar2($holidays);
else $calendar=make_calendar2($holidays,$day);

require_once("include/close_db.php");

$title=_("Calendar management");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">
<center>
<form name="results" method="post" action="cal_manag.php">
<input type="hidden" name="day" value="<?=$day?>">
<input type="hidden" name="place" value="<?=$place?>">
<input type="submit" name="default_submit" value="" style="display: none;">
   <table border="0">
    <tr>
     <td><b><?=_("City:")?></b></td>
     <td>

      <select name="place"
       onchange="javascript:document.results.submit();">
<?
if (empty($holidays)&&!empty($ok)||$editing||!empty($change)) $city[]=$place;?>
       <?=array_to_option(array_values($city),$place)?>
      </select>
     </td>
     <td>
      
      <input type="submit" name="new" value="<?=_("New city")?>">
     </td>
<?if (!empty($new)) {?> 
  <td><input type="text" name="new_place" value=""></td> 
  <td><input type="submit" name="ok" value="<?=_("OK")?>"></td>
<?}?>
    </tr>
   </table>
<br><br>


<?
if (!empty($place)&&$place!="---"||!empty($holidays)||!empty($ok)||!empty($new_place)||!empty($change)) {?>
<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Selection of non workable days for ");?>
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
             <a href="?day=<?=$day_month_previous?>&amp;place=<?="$place"?>&amp;editing=<?="$editing"?>"
              style="color: #000000">&lt;</a>
             </td>
             <td style="background: #FFFFFF; width: 4ex">
             <a href="?day=<?=$day_month_next?>&amp;place=<?="$place"?>&amp;editing=<?="$editing"?>"
              style="color: #000000">&gt;</a>
             </td>
             <td align="right">
	       <input type="text" name="day" value="<?=$day?>"
               onChange="javascript:f=document.getElementById('form');f.action.value='cal_manag.php?change=1';f.submit();"
               size="10" style="width: 100%; height: 100%">
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
         // Day titles computation
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
        ?>
          <td style="<?=$style[$d[1]]?>">
          <? if ($d[1]=="N"){?> 
		 <a href="cal_manag.php?day=<?=$d[2]?>&amp;insert=<?="true"?>&amp;place=<?="$place"?>" style="<?=$style[$d[1]]?>">

	  <?}
	     if ($d[1]=="A") {?>
		 <a href="cal_manag.php?day=<?=$d[2]?>&amp;insert=<?="false"?>&amp;place=<?="$place"?>" style="<?=$style[$d[1]]?>">
		 
	  <?}?>
           <?=$d[0]?></a>
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
      <tr><td style="<?=$style["B"]?>"><?=_("Non workable day")?></td></tr>
      <tr><td style="<?=$style["N"]?>"><?=_("Workable day")?></td></tr>
      <tr><td style="<?=$style["G"]?>"><?=_("Day of the previous or next month")?></td></tr>      
     </table>
     </td></tr></table>
<br>
<input type="submit" name="copy" value="<?=_("Copy from previous year")?>">
    </td>
   </tr>
  </table>
  <?if (!empty($holidays)) {?>
  <table>
   <tr>
    <td> <?=_("Non workable days");?>:</td></tr>
    <?$arrayDMA=date_web_to_arrayDMA($day);
      foreach ($holidays as $fest) {?>
      <?if($fest["year"]==$arrayDMA[2]) {
	$temp=date_arrayDMA_to_web(array($fest["mday"],$fest["mon"],$fest["year"]));?>
        <tr><td></td><td><a href="cal_manag.php?day=<?=$temp?>&amp;place=<?="$place"?>" style="A"><?=$temp?></a></td></tr>
      <?}?>
<?}?>
  </table>
  <?}?>
<!-- end text box -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- end box -->
<?}?>
</form>
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
