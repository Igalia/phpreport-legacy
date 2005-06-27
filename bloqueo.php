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
 * igalio = Igalio sobre el cual se realizara el bloqueo.
 *
 */

require_once("include/autentificado.php");
require_once("include/util.php");
require_once("include/prepara_calendario.php");
require_once("include/conecta_db.php");

if (!in_array("informesadm",(array)$session_grupos)) {
 header("Location: login.php");
}

if (!empty($aplicar)) {
 if ($igalio=="todos") {
  if (!@pg_exec($cnx,$query="UPDATE bloqueo SET fecha='".fecha_web_to_sql($dia_bloquear)."'")) {
    $error="No se ha podido actualizar la fecha de bloqueo";
  } else {
   $confirmacion="La fecha de bloqueo se actualizó correctamente";
  }
 } else {
  if (!@pg_exec($cnx,$query="UPDATE bloqueo SET "
   ."fecha='".fecha_web_to_sql($dia_bloquear)."'"
   ." WHERE uid='$igalio'")) {
    $error="No se ha podido actualizar la fecha de bloqueo";
  } else {
   $confirmacion="La fecha de bloqueo se actualizó correctamente";
  }
 }
}

$meses=array(
 "Enero","Febrero","Marzo","Abril","Mayo","Junio",
 "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$dias=array(
 "Lunes","Martes","Miércoles","Jueves","Viernes","Sábado","Domingo");

$igalios=$session_usuarios;
$igalios["todos"]="--- Todos ---";

if (empty($igalio)) $igalio="todos";

if($igalio!="todos") $result=@pg_exec($cnx,$query="SELECT fecha FROM bloqueo"
    ." WHERE uid = '$igalio' ORDER BY fecha DESC")
   or die("No se ha podido completar la operación");
else $result=@pg_exec($cnx,$query="SELECT fecha FROM bloqueo"
    ." ORDER BY fecha DESC")
   or die("No se ha podido completar la operación");
if($row=@pg_fetch_array($result,0,PGSQL_ASSOC)) $dia_bloqueo=fecha_sql_to_web($row["fecha"]);
@pg_freeresult($result);

if (!empty($dia)) {
 $arrayDMA=fecha_web_to_arrayDMA($dia);
 $hoy=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
} else {
 if (empty($dia_bloqueo)) {
  $hoy=getdate(time());
  $hoy=getdate(mktime(0,0,0,$hoy["mon"],$hoy["mday"],$hoy["year"]));
 } else {
  $arrayDMA=fecha_web_to_arrayDMA($dia_bloqueo);
  $hoy=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
 }
}
$dia=fecha_arrayDMA_to_web(array($hoy["mday"],$hoy["mon"],$hoy["year"]));

$calendario=crea_calendario($dia);

$dia_mes_anterior=dia_mes_movido($dia, -1);
$dia_mes_siguiente=dia_mes_movido($dia, 1);
$dia_anho_anterior=dia_anho_movido($dia, -1);
$dia_anho_siguiente=dia_anho_movido($dia, 1);


require_once("include/cerrar_db.php");

$title="Bloqueo de informes";
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);
if (!empty($confirmacion)) msg_ok($confirmacion);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<form name="resultados" method="post">

   <table border="0">
    <tr>
     <td><b>Empleado a bloquear:</b></td>
     <td>
      <select name="igalio"
       onchange="javascript:document.resultados.submit();">
       <?=array_to_option(
        array_values($igalios),
        $igalio,
        array_keys($igalios))?>
      </select>
     </td>
     <td>
      <input type="submit" name="seleccionar" value="Seleccionar">
     </td>
    </tr>
   </table>

<br>

<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="titulo_caja"><font
 color="#FFFFFF" class="titulo_caja">
<!-- título caja -->
Selección de fecha límite de bloqueo
<!-- fin título caja -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_caja">
<table border="0" cellspacing="0" cellpadding="0"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->

   <table border="0" cellpadding="0" cellspacing="10" width="100%">
   <tr>
    <td>

     <table border="0" cellpadding="0" cellspacing="0"
      style="text-align: left; margin-left: auto; margin-right: auto;">
      <tr>
       <td bgcolor="#999999" style="text-align: center;">
        <table cellpadding="3" cellspacing="1" style="text-align: center;">
        <?
         $estilo=array(
          "B"=>"background: #FFE9E9; color: #000000; font-weight: regular; text-align: center",
          "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
          "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
          "N"=>"background: #E9FFE9; color: #000000; font-weight: regular; text-align: center",
          "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center"
         );
        ?>
         <tr>

          <td style="background: #FFFFFF; font-weight: bold;
           text-decoration: none"
           colspan="7">
           <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
             <td style="background: #FFFFFF; width: 4ex">
             <a href="?dia=<?=$dia_mes_anterior?>&amp;igalio=<?=$igalio?>"
              style="color: #000000">&lt;</a>
             </td>
             <td style="background: #FFFFFF; width: 4ex">
             <a href="?dia=<?=$dia_mes_siguiente?>&amp;igalio=<?=$igalio?>"
              style="color: #000000">&gt;</a>
             </td>
             <td align="right">
              <input type="text" name="dia" value="<?=$dia?>"
               size="10" style="width: 100%; height: 100%"
               onchange="javascript:document.resultados.submit();">
             </td>
             <td align="right">
              <input type="submit" name="cambiar" value="Ir a día"
               style="width: 100%; height: 100%">
             </td>
            </tr>
           </table>
          </td>

         </tr>
         <tr>
        <?
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
          if (!empty($dia_bloqueo)) {
           if ((strcmp(fecha_web_to_sql($d[2]),fecha_web_to_sql($dia_bloqueo))<0)&&($d[1]!="H")) $d[1]="B";
          }
        ?>
          <td style="<?=$estilo[$d[1]]?>">
           <a href="bloqueo.php?dia=<?=$d[2]?>&amp;igalio=<?=$igalio?>" style="<?=$estilo[$d[1]]?>"
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
     <b>Leyenda</b>
     <br><br>
     <table border="0" cellpadding="1" cellspacing="0"><tr><td bgcolor="#999999">
     <table border="0" cellpadding="0" cellspacing="0" style="text-align: center;">
      <tr><td style="<?=$estilo["B"]?>">Día bloqueado</td></tr>
      <tr><td style="<?=$estilo["H"]?>">Día seleccionado</td></tr>
      <tr><td style="<?=$estilo["N"]?>">Día no bloqueado</td></tr>
      <tr><td style="<?=$estilo["G"]?>">Día del mes anterior o posterior</td></tr>
     </table>
     </td></tr></table>

    </td>
   </tr>
  </table>

<!-- fin texto caja -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- fin caja -->

   <table border="0" cellpadding="20" cellspacing="0" style="text-align: center; margin-left: auto; margin-right: auto;">
    <tr>
     <td>
      <input type="submit" name="aplicar" value="Bloquear hasta dia seleccionado">
      <input type="hidden" name="dia_bloquear" value="<?=$dia?>">
     </td>
    </tr>
   </table>

</form>
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/muestra_secciones.php") ?>
<br>
<? require("include/muestra_calendario.php") ?>
</td>
</tr>
</table>

<?
require("include/plantilla-post.php");
?>
