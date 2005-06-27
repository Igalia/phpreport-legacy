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
 * dia = Día del informe. Formato DD/MM/AAAA
 * minutos_semanales = Valor precalculado con los minutos trabajados esta semana
 *                   Sirve para no tener que hacer continuamente la query. Se
 *                   recalcula si está vacío o si se guarda el informe
 * minutos_diarios = Valor precalculado con los minutos trabajados hoy
 * tarea = array con las diferentes tareas:
 *  array(
 *   0=>array(
 *       "inicio"=>...,
 *       "fin"=>...
 *       ...
 *      ),
 *   1=>array(...),
 *   ...
 *  );
 * borrar_tarea[i] = Se ha pulsado BORRAR TAREA para la tarea i-ésima
 * nueva_tarea = Se ha pulsado NUEVA TAREA
 * cancelar = Se ha pulsado CANCELAR
 * guardar = Se ha pulsado GUARDAR
 * editando = Parámetro hidden de formulario que indica que éste se está editando.
 *            Sirve para evitar recargar los datos cuando se han borrado todas las
 *            tareas (array tareas vacío voluntariamente)
 */

require_once("include/autentificado.php");
require_once("include/conecta_db.php");
require_once("include/prepara_calendario.php");

// SI NO HAY DIA, SE INVENTA

if (empty($dia)) {
 $dia=getdate(time());
 $dia=$dia["mday"]."/".$dia["mon"]."/".$dia["year"];
}


// COMPROBACIÓN DE SI EL INFORME ESTÁ BLOQUEADO

$result=@pg_exec($cnx,$query="SELECT fecha FROM bloqueo"
 ." WHERE uid='$session_uid'")
 or die("No se ha podido completar la operación");
$bloqueado=false;
if ($row=@pg_fetch_row($result))
 $bloqueado=!($row[0]<fecha_web_to_sql($dia));
@pg_freeresult($result);
if ($bloqueado) $parametro_bloqueado=" READONLY ";

// PRECARGA DE LOS VALORES DEL FORMULARIO

if ((empty($editando) && empty($tarea)) || $bloqueado) {
 $tarea=array();
 $result=@pg_exec($cnx,$query="SELECT * FROM tarea"
  ." WHERE uid='$session_uid' AND fecha='"
  .fecha_web_to_sql($dia)."'"
  ." ORDER BY inicio")
 or die("No se ha podido completar la operación");

 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $row["fecha"]=fecha_sql_to_web($row["fecha"]);
  $row["inicio"]=hora_sql_to_web($row["inicio"]);
  $row["fin"]=hora_sql_to_web($row["fin"]);
  $row["texto"]=stripslashes($row["texto"]);
  $tarea[]=$row;
 }
 @pg_freeresult($result);
}

// BORRAR TAREA

if (!empty($borrar_tarea)) {
 $tarea=borra_elementos_desplazando($tarea, current(array_keys($borrar_tarea)));
}

// NUEVA TAREA

if (!empty($nueva_tarea)) {
 $i=count($tarea);
 $tarea[]=array(
  "inicio"=>"",
  "fin"=>"",
  "tipo"=>"",
  "nombre"=>"",
  "fase"=>"",
  "ttipo"=>"",
  "story"=>"",
  "texto"=>""
 );
 if ($i==0) {
  $tarea[$i]["inicio"]=date("H:i",mktime());
 } else if (empty($tarea[$i-1]["fin"])) {
  $tarea[$i-1]["fin"]=date("H:i",mktime());
  $tarea[$i]["inicio"]=$tarea[$i-1]["fin"];
 }
}

// VOLVER AL CALENDARIO

if (!empty($cancelar)) {
 header("Location: ?dia=$dia");
}

// GUARDAR CAMBIOS

if (!empty($guardar)) {
 do {

  // CHEQUEO DE BLOQUEO DE FORMULARIO

  if ($bloqueado) {
   $error="El informe está bloqueado y no se puede modificar";
   break;
  }

  // CHEQUEO DE CAMPOS

  for ($i=0;$i<sizeof($tarea);$i++) {
   foreach (array("inicio","fin") as $campo) {
    if (!preg_match("/[0-9]{1,2}:[0-9]{2,2}/",$tarea[$i][$campo])) {
     $error="Existen errores que deben ser corregidos";
     $error_tarea[$i][$campo]="Debe utilizar el formato HH:MM";
    } else {
     $tarea[$i][$campo]=hora_sql_to_web(hora_web_to_sql($tarea[$i][$campo]));
     if ($tarea[$i][$campo]<"00:00" || $tarea[$i][$campo]>"24:00") {
      $error="Existen errores que deben ser corregidos";
      $error_tarea[$i][$campo]="La hora debe estar entre las 00:00 y las 24:00";
     }
    }
   }
   foreach (array("inicio","fin","tipo","texto") as $campo) {
    if (empty($tarea[$i][$campo])) {
     $error="Existen errores que deben ser corregidos";
     $error_tarea[$i][$campo]="Debe especificar este campo";
    }
   }
  }
  if (!empty($error)) break;

$tamano=sizeof($tarea);
if ($tamano>0) usort ($tarea, cmp_fechas_inicio);

  // REPETIMOS LAS VUELTAS PARA QUE LOS INDICES DE ERRORES SEAN CORRECTOS

  for ($i=0;$i<$tamano;$i++) {
   if ($i>0 && $tarea[$i-1]["fin"]>$tarea[$i]["inicio"]) {
    $error_tarea[$i-1]["fin"]="La tarea se solapa con otra";
    $error_tarea[$i]["inicio"]="La tarea se solapa con otra";
    $error="Existen errores que deben ser corregidos";
   }
   if ($tarea[$i]["inicio"]>=$tarea[$i]["fin"]) {
    $error="Existen errores que deben ser corregidos";
    $error_tarea[$i]["inicio"]="Intervalo de horas no válido";
    $error_tarea[$i]["fin"]="Intervalo de horas no válido";
   }
  }
  if (!empty($error)) break;

  // TRANSACCIÓN DE ALMACENAMIENTO DE INFORME Y TAREAS

  if (!@pg_exec($cnx,$query=
    "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
   ."BEGIN TRANSACTION; ")) {
   $error="No se ha podido completar la operación";
   break;
  }

  do {

   // BORRADO DE TAREAS

   if (!@pg_exec($cnx,$query=
     "DELETE FROM tarea WHERE uid='$session_uid'"
    ." AND fecha='".fecha_web_to_sql($dia)."'")) {
    $error="No se ha podido completar la operación";
    break;
   }

   // GESTION DE BLOQUEOS

  if (!$result=@pg_exec($cnx,$query=
     "SELECT fecha FROM bloqueo WHERE uid='$session_uid'")) {
    $error="No se ha podido completar la operación";
    break;
   }

   if (@pg_numrows($result)==0) {

    // INSERT BLOQUEO
    // ATENCION!! LE HACE FALTA COMILLAS A LA FECHA?
    if (!$result=@pg_exec($cnx,$query=
      "INSERT INTO bloqueo (uid,fecha)"
     ." VALUES ('$session_uid', '1999-12-31')")) {
     $error="No se ha podido completar la operación";
     break;
    }
   }

 // GESTION DEL INFORME

   if (!$result=@pg_exec($cnx,$query=
     "SELECT fecha_modificacion FROM informe WHERE uid='$session_uid'"
    ." AND fecha='".fecha_web_to_sql($dia)."'")) {
    $error="No se ha podido completar la operación";
    break;
   }

   if (@pg_numrows($result)>0) {

    // UPDATE INFORME

    if (!$result=@pg_exec($cnx,$query=
      "UPDATE informe SET fecha_modificacion=now()"
     ." WHERE uid='$session_uid'"
     ." AND fecha='".fecha_web_to_sql($dia)."'")) {
     $error="No se ha podido completar la operación";
     break;
    }

   } else {

    // INSERT INFORME

    if (!$result=@pg_exec($cnx,$query=
      "INSERT INTO informe (uid,fecha,fecha_modificacion)"
     ." VALUES ('$session_uid','"
     .fecha_web_to_sql($dia)."',now())")) {
     $error="No se ha podido completar la operación";
     break;
    }
   }

   // SE LE DA UN FORMATO CORRECTO A LAS TAREAS

   for ($i=0;$i<sizeof($tarea);$i++) {
    $campos=array("uid","fecha","inicio","fin","nombre","tipo","fase","ttipo","story","texto");
    $row=array();
    foreach ($campos as $campo)
     $row[$campo]=$tarea[$i][$campo];
    $row["uid"]=$session_uid;
    $row["fecha"]=fecha_web_to_sql($dia);
    $row["inicio"]=hora_web_to_sql($row["inicio"]);
    $row["fin"]=hora_web_to_sql($row["fin"]);
    foreach ($campos as $campo)
     if (empty($row[$campo]) && !is_integer($row[$campo])
        && $campo!="nombre") $row[$campo]="NULL";
     else $row[$campo]="'$row[$campo]'";

    // Y FINALMENTE SE INSERTAN

    if (!@pg_exec($cnx,$query="INSERT INTO tarea ("
     .implode(",",$campos).") VALUES (".implode(",",$row).")")) {
     $error="No se ha podido completar la operación";
     break;
    }
   }

  // Y SE HACE TODO EL PROCESO

   @pg_exec($cnx,$query="COMMIT TRANSACTION");

  } while(false);

  if (!empty($error)) {

   // Depuración
   $error.="<!-- $query -->";
   @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
   break;
  }

  $confirmacion="Los cambios se han guardado correctamente";

 } while(false);
}

// RECUENTO DE HORAS SEMANALES Y DIARIAS TRABAJADAS

if (empty($minutos_semanales) || !empty($guardar)) {
 $minutos_semanales=minutos_trabajados_esta_semana($cnx,$session_uid,$dia);
 $minutos_diarios=0;
 foreach ((array)$tarea as $t)
  if (!empty($t["inicio"]) && !empty($t["fin"]))
   $minutos_diarios+=(hora_web_to_sql($t["fin"])-hora_web_to_sql($t["inicio"]));
}

require_once("include/cerrar_db.php");

$title="Edición de informes";
require("include/plantilla-pre.php");

if (!empty($error)) msg_fallo($error);
if (!empty($confirmacion)) msg_ok($confirmacion);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<form name="tarea" action="#ultima_tarea" method="post">
<input type="hidden" name="dia" value="<?=$dia?>">
<input type="hidden" name="horas_semanales" value="<?=$horas_semanales?>">
<input type="hidden" name="horas_diarias" value="<?=$horas_diarias?>">

<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="titulo_caja"><font
 color="#FFFFFF" class="titulo_caja">
<!-- título caja -->
Tareas del día <?=$dia?>
<!-- fin título caja -->
</font></td></tr>
<?
for ($i=0;$i<sizeof($tarea);$i++) {
?>
<tr><td bgcolor="#FFFFFF" class="texto_caja">
<?
 if (($i==sizeof($tarea)-1)&&(!empty($nueva_tarea)||!empty($borrar_tarea))) {
?>
<a name="ultima_tarea"></a>
<?
 }
?>
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->
<table border="0" cellpadding="3" cellspacing="1">
<?
 foreach (array(
  "inicio"=>"Hora de inicio",
  "fin"=>"Hora de fin",
  ) as $campo_key=>$campo_value) {
?>
 <tr>
  <td width="200px"><?=$campo_value?></td>
  <td>
   <input type="text" name="<?="tarea[$i][$campo_key]"?>" <?=$parametro_bloqueado?>
    value="<?=$tarea[$i][$campo_key]?>">
  </td>
  <td>
<?
  if (!empty($error_tarea[$i][$campo_key])) {
   echo msg_fallo($error_tarea[$i][$campo_key]);
  }
?>
  </td>
 </tr>
<?
 }

 foreach (array(
  "tipo"=>"Tipo de tarea",
  "nombre"=>"Proyecto",
  "fase"=>"Fase del proyecto",
  "ttipo"=>"Tipo de tarea dentro de la fase"
  ) as $campo_key=>$campo_value) {
   $tabla="tabla_$campo_key";
   for ($j=0;$j<sizeof($tarea);$j++) {
    if (${$tabla}[($tarea[$j][$campo_key])]=="") {
     ${$tabla}[($tarea[$j][$campo_key])]=
      $tarea[$j][$campo_key]." (legacy)";
    }
   }
?>
 <tr>
  <td width="200px"><?=$campo_value?></td>
  <td>
   <select name="<?="tarea[$i][$campo_key]"?>" <?=$parametro_bloqueado?>>
    <?=array_to_option(array_values($$tabla),$tarea[$i][$campo_key],array_keys($$tabla))?>
   </select>
  </td>
  <td>
<?
  if (!empty($error_tarea[$i][$campo_key])) {
   echo msg_fallo($error_tarea[$i][$campo_key]);
  }
?>
  </td>
 </tr>
<?
 }
?>
 <tr>
  <td width="200px">Caso de uso</td>
  <td>
   <input type="text" name="<?="tarea[$i][story]"?>"
    value="<?=$tarea[$i]['story']?>" <?=$parametro_bloqueado?>>
  </td>
  <td>
<?
  if (!empty($error_tarea[$i]['story'])) {
   echo msg_fallo($error_tarea[$i]['story']);
  }
?>
  </td>
 </tr>
 <tr>
  <td colspan="2">
   Descripción:
  </td>
  <td>
<?
if (!empty($error_tarea[$i]["texto"])) {
   echo msg_fallo($error_tarea[$i]["texto"]);
  }
?>
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <textarea rows="3" cols="80" name="<?="tarea[$i][texto]"?>" <?=$parametro_bloqueado?>
    ><?=stripslashes($tarea[$i][texto])?></textarea>
  </td>
 </tr>
<?
if (!$bloqueado) {
?>
 <tr>
  <td colspan="3" align="right">
   <input type="submit" name="<?="borrar_tarea[$i]"?>" value="Borrar tarea">
  </td>
 </tr>
<?
}
?>
</table>
<!-- fin texto caja -->
</table></td></tr>
<?
}
?>
</table></td></tr></table>
<!-- fin caja -->
<br>
<?

if (!$bloqueado) {
?>
<input type="hidden" name="editando" value="1">
<input type="submit" name="nueva_tarea" value="Nueva tarea">
<input type="submit" name="guardar" value="Guardar cambios">
<?
}
?>
<input type="submit" name="cancelar" value="Cancelar">
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/muestra_recuento.php") ?>
<br>
<? require("include/muestra_secciones.php") ?>
<br>
<? require("include/muestra_calendario.php") ?>
</td>
</tr>
</table>
<?
require("include/plantilla-post.php");
?>
