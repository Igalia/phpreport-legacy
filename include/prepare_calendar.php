<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andr�s G�mez Garc�a <agomez@igalia.com>
//  Enrique Oca�a Gonz�lez <eocanha@igalia.com>
//  Jos� Riguera L�pez <jriguera@igalia.com>
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
 * PAR�METROS HTTP QUE RECIBE ESTA P�GINA:
 *
 * dia = D�a del que mostrar el calendario. Formato DD/MM/AAAA
 */

require_once("include/autenticate.php");
require_once("include/util.php");

$months_minical=array(
 _("January"),_("February"),_("March"),_("April"),_("May"),_("June"),
 _("July"),_("August"),_("September"),_("October"),_("November"),_("December"));
$days_minical=array(
 _("M"),_("T"),_("W"),_("Th"),_("F"),_("St"),_("S"));

 $hoxe=getdate(time());
 $hoxe=getdate(mktime(0,0,0,$hoxe["mon"],$hoxe["mday"],$hoxe["year"]));

if (!empty($day)) {
 $arrayDMA=date_web_to_arrayDMA($day);
 $today=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
} else {
 $today=getdate(time());
 $today=getdate(mktime(0,0,0,$today["mon"],$today["mday"],$today["year"]));
}

if($change==_("Go to day")){
  $arrayDMA=array($today["mday"],$today["mon"],$today["year"]);
  $day=date_arrayDMA_to_web($arrayDMA);
  $calendar=make_calendar($day);
}
else if($change2==_("Go to today")){
  $arrayDMA=array($hoxe["mday"],$hoxe["mon"],$hoxe["year"]);
  $hoxe=date_arrayDMA_to_web($arrayDMA);
  $day=$hoxe;
  $today=$hoxe;
  $calendar=make_calendar($day);
}
else{
  $arrayDMA=array($today["mday"],$today["mon"],$today["year"]);
  $day=date_arrayDMA_to_web($arrayDMA);
  $calendar=make_calendar($day);
}

$day_month_previous=day_month_moved($day, -1);
$day_month_next=day_month_moved($day, 1);
$day_year_previous=day_year_moved($day, -1);
$day_year_next=day_year_moved($day, 1);
?>
