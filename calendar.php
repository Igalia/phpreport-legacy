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
 */

require_once("include/autenticate.php");
require_once("include/util.php");

$months=array(
 _("January"),_("February"),_("March"),_("April"),_("May"),_("June"),
 _("July"),_("August"),_("September"),_("October"),_("November"),_("December"));
$days=array(
 _("M"),_("T"),_("W"),_("Th"),_("F"),_("St"),_("S"));

if (!empty($day)) {
 $arrayDMA=date_web_to_arrayDMA($day);
 $today=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
} else {
 $today=getdate(time());
 $today=getdate(mktime(0,0,0,$today["mon"],$today["mday"],$today["year"]));
}
$day=date_arrayDMA_to_web(array($today["mday"],$today["mon"],$today["year"]));

$calendar=make_calendar($day);

$day_month_previous=day_month_moved($day, -1);
$day_month_next=day_month_moved($day, 1);
$day_year_previous=day_year_moved($day, -1);
$day_year_next=day_year_moved($day, 1);

$title=_("calendar for ").$months[$today["mon"]-1]
 ._(" of ").$today["year"];
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
?>
<center>
<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
 <tr>
  <td style="text-align: center; vertical-align: top;">
   <table border="0" cellpadding="20" cellspacing="0" style="text-align: center; margin-left: auto; margin-right: auto;">
    <tr>
     <td align="center" style="font-weight: bold">
      <a href="?day=<?=$day_year_previous?>"
       >&lt;&lt; <?=_("Previous year")?></a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?day=<?=$day_month_previous?>"
      >&lt; <?=_("Previous month")?></a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?"><?=_("Today")?></a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?day=<?=$day_month_next?>"
       ><?=_("Next month")?> &gt;</a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?day=<?=$day_year_next?>"
       ><?=_("Next year")?> &gt;&gt;</a>
     </td>
    </tr>
   </table>
   <table border="0" cellpadding="0" cellspaging="0" style="text-align: left; margin-left: auto; margin-right: auto;">
    <tr>
     <td bgcolor="#000000" style="text-align: center;">
      <table cellpadding="3" cellspacing="1" style="text-align: center;">
       <tr>
        <?
         $style=array(
          "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
          "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
          "N"=>"background: #E0E0F0; color: #000000; font-weight: regular; text-align: center",
          "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center"
         );

         // Day title computation
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
         <a href="report.php?day=<?=$d[2]?>" style="<?=$style[$d[1]]?>"
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
 <?
  // The current user belongs to administrator group

  if (in_array($admin_group_name, $groups)) {
 ?>
  <td style="width: 25ex" valign="top">

   <!-- box -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   <table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
   <td bgcolor="#000000" class="title_minibox"><font
    color="#FFFFFF" class="title_minibox">
   <!-- title box -->
   <?=_("Administration")?>
   <!-- end title box -->
   </font></td></tr><tr><td bgcolor="#FFFFFF" class="text_minibox">
   <table border="0" cellspacing="0" cellpadding="10"><tr><td
    align="left">
   <font
    color="#000000" class="text_minibox">
   <!-- text box -->
   <a href="block.php"
    style="font-weight: bold;">- <?=_("Report blocking")?></a>
   <br>
   <a href="consult.php"
    style="font-weight: bold;">- <?=_("Querys")?></a>

   <!-- end text box -->
   </font></td></tr></table></td></tr></table></td></tr></table>
   <!-- end box -->

   <br>

   <!-- box -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   <table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
   <!-- title box -->
   <td bgcolor="#000000" class="title_minibox"
    ><a href="?day=<?=$day_month_previous?>"
    ><font color="#FFFFFF" class="title_minibox">
   &nbsp;&lt;&nbsp;
   </font></a></td>
   <td bgcolor="#000000" class="title_minibox" width="100%"
    ><font color="#FFFFFF" class="title_minibox">
   <?=_("Calendar")?>
   </font></td>
   <td bgcolor="#000000" class="title_minibox"
    ><a href="?day=<?=$day_month_next?>"
    ><font color="#FFFFFF" class="title_minibox">
   &nbsp;&gt;&nbsp;
   </font></a></td>
   </tr><tr><td bgcolor="#FFFFFF" class="text_minibox" colspan="3">
   <!-- end title box -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td
    align="center">
   <!-- text box -->
   <table border="0" cellpadding="0" cellspacing="0" width="100%">
   <form name="mini_calendar" method="get">
   <tr><td style="background: #C0C0D0; color: #000000; text-align: center">
   <input type="text" name="day" value="<?=$day?>" size="10"
    style="border: none; background: #C0C0D0; color: #000000"
    onchange="javascript: document.mini_calendar.submit();">
   </td><td style="background: #C0C0D0; color: #000000; text-align: right">
   <input type="submit" name="change" value="<?=_("Change")?>">
   </td></tr>
   </form>
   </table>
   <table cellpadding="3" cellspacing="0" style="text-align: center;"
    width="100%">
    <tr>
     <?
      $style=array(
       "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
       "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
       "N"=>"background: #E0E0F0; color: #000000; font-weight: regular; text-align: center",
       "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center"
      );

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
     ?>
     <td style="<?=$style[$d[1]]?>">
      <a href="?day=<?=$d[2]?>" style="<?=$style[$d[1]]?>"
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
   <!-- end text box -->
   </td></tr></table></td></tr></table></td></tr></table>
   <!-- end box -->

  </td>
 <? } ?>
 </tr>
</table>
</center>
<?
require("include/template-post.php");
?>
