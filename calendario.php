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

$meses=array(
 "Enero","Febrero","Marzo","Abril","Mayo","Junio",
 "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$dias=array(
 "L","M","M","J","V","S","D");

if (!empty($dia)) {
 $arrayDMA=fecha_web_to_arrayDMA($dia);
 $hoy=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
} else {
 $hoy=getdate(time());
 $hoy=getdate(mktime(0,0,0,$hoy["mon"],$hoy["mday"],$hoy["year"]));
}
$dia=fecha_arrayDMA_to_web(array($hoy["mday"],$hoy["mon"],$hoy["year"]));

$calendario=crea_calendario($dia);

$dia_mes_anterior=dia_mes_movido($dia, -1);
$dia_mes_siguiente=dia_mes_movido($dia, 1);
$dia_anho_anterior=dia_anho_movido($dia, -1);
$dia_anho_siguiente=dia_anho_movido($dia, 1);

$title="Calendario de ".$meses[$hoy["mon"]-1]
 ." de ".$hoy["year"];
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);
?>
<center>
<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
 <tr>
  <td style="text-align: center; vertical-align: top;">
   <table border="0" cellpadding="20" cellspacing="0" style="text-align: center; margin-left: auto; margin-right: auto;">
    <tr>
     <td align="center" style="font-weight: bold">
      <a href="?dia=<?=$dia_anho_anterior?>"
       >&lt;&lt; Año anterior</a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?dia=<?=$dia_mes_anterior?>"
      >&lt; Mes anterior</a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?">Hoy</a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?dia=<?=$dia_mes_siguiente?>"
       >Mes siguiente &gt;</a>
     </td>
     <td align="center" style="font-weight: bold">
      <a href="?dia=<?=$dia_anho_siguiente?>"
       >Año siguiente &gt;&gt;</a>
     </td>
    </tr>
   </table>
   <table border="0" cellpadding="0" cellspaging="0" style="text-align: left; margin-left: auto; margin-right: auto;">
    <tr>
     <td bgcolor="#000000" style="text-align: center;">
      <table cellpadding="3" cellspacing="1" style="text-align: center;">
       <tr>
        <?
         $estilo=array(
          "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
          "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
          "N"=>"background: #E0E0F0; color: #000000; font-weight: regular; text-align: center",
          "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center"
         );

         // Cálculo de los títulos de los días
         foreach ($dias as $d) {
        ?>
        <td style="<?=$estilo["T"]?>">
        <?=$d?>
        </td>
        <?
         }
        ?>
       </tr>
       <?
        foreach ($calendario as $s) {
       ?>
      <tr>
        <?
         foreach ($s as $d) {
        ?>
        <td style="<?=$estilo[$d[1]]?>">
         <a href="informe.php?dia=<?=$d[2]?>" style="<?=$estilo[$d[1]]?>"
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
  // El usuario actual es del grupo de administradores

  // if (in_array("informesadm", $grupos)) {
  if (true) {
 ?>
  <td style="width: 25ex" valign="top">

   <!-- caja -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   <table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
   <td bgcolor="#000000" class="titulo_minicaja"><font
    color="#FFFFFF" class="titulo_minicaja">
   <!-- título caja -->
   Administración
   <!-- fin título caja -->
   </font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_minicaja">
   <table border="0" cellspacing="0" cellpadding="10"><tr><td
    align="left">
   <font
    color="#000000" class="texto_minicaja">
   <!-- texto caja -->
   <a href="bloqueo.php"
    style="font-weight: bold;">- Bloqueo de Informes</a>
   <br>
   <a href="consulta.php"
    style="font-weight: bold;">- Consultas</a>

   <!-- fin texto caja -->
   </font></td></tr></table></td></tr></table></td></tr></table>
   <!-- fin caja -->

   <br>

   <!-- caja -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   <table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
   <!-- título caja -->
   <td bgcolor="#000000" class="titulo_minicaja"
    ><a href="?dia=<?=$dia_mes_anterior?>"
    ><font color="#FFFFFF" class="titulo_minicaja">
   &nbsp;&lt;&nbsp;
   </font></a></td>
   <td bgcolor="#000000" class="titulo_minicaja" width="100%"
    ><font color="#FFFFFF" class="titulo_minicaja">
   Calendario
   </font></td>
   <td bgcolor="#000000" class="titulo_minicaja"
    ><a href="?dia=<?=$dia_mes_siguiente?>"
    ><font color="#FFFFFF" class="titulo_minicaja">
   &nbsp;&gt;&nbsp;
   </font></a></td>
   </tr><tr><td bgcolor="#FFFFFF" class="texto_minicaja" colspan="3">
   <!-- fin título caja -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td
    align="center">
   <!-- texto caja -->
   <table border="0" cellpadding="0" cellspacing="0" width="100%">
   <form name="mini_calendario" method="get">
   <tr><td style="background: #C0C0D0; color: #000000; text-align: center">
   <input type="text" name="dia" value="<?=$dia?>" size="10"
    style="border: none; background: #C0C0D0; color: #000000"
    onchange="javascript: document.mini_calendario.submit();">
   </td><td style="background: #C0C0D0; color: #000000; text-align: right">
   <input type="submit" name="cambiar" value="Cambiar">
   </td></tr>
   </form>
   </table>
   <table cellpadding="3" cellspacing="0" style="text-align: center;"
    width="100%">
    <tr>
     <?
      $estilo=array(
       "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
       "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
       "N"=>"background: #E0E0F0; color: #000000; font-weight: regular; text-align: center",
       "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center"
      );

      // Cálculo de los títulos de los días
      foreach ($dias as $d) {
     ?>
     <td style="<?=$estilo["T"]?>">
     <?=$d?>
     </td>
     <?
      }
     ?>
    </tr>
    <?
     foreach ($calendario as $s) {
    ?>
   <tr>
     <?
      foreach ($s as $d) {
     ?>
     <td style="<?=$estilo[$d[1]]?>">
      <a href="?dia=<?=$d[2]?>" style="<?=$estilo[$d[1]]?>"
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
   <!-- fin texto caja -->
   </td></tr></table></td></tr></table></td></tr></table>
   <!-- fin caja -->

  </td>
 <? } ?>
 </tr>
</table>
</center>
<?
require("include/plantilla-post.php");
?>
