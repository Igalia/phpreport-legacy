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


/**
 * PARÁMETROS HTTP QUE RECIBE ESTA PÁGINA:
 *
 * dia = Día del que mostrar el calendario. Formato DD/MM/AAAA
 */

require_once("include/autentificado.php");
require_once("include/util.php");

$meses_minical=array(
 "Enero","Febrero","Marzo","Abril","Mayo","Junio",
 "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$dias_minical=array(
 "L","M","M","J","V","S","D");

if (!empty($dia)) {
 $arrayDMA=fecha_web_to_arrayDMA($dia);
 $hoy=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
} else {
 $hoy=getdate(time());
 $hoy=getdate(mktime(0,0,0,$hoy["mon"],$hoy["mday"],$hoy["year"]));
}
$arrayDMA=array($hoy["mday"],$hoy["mon"],$hoy["year"]);
$dia=fecha_arrayDMA_to_web($arrayDMA);

$calendario=crea_calendario($dia);

$dia_mes_anterior=dia_mes_movido($dia, -1);
$dia_mes_siguiente=dia_mes_movido($dia, 1);
$dia_anho_anterior=dia_anho_movido($dia, -1);
$dia_anho_siguiente=dia_anho_movido($dia, 1);
?>
