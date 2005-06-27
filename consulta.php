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
 * consulta = Consulta seleccionada de entre todas las posibles
 * seleccionar = Se ha pulsado el botón SELECCIONAR CONSULTA
 * editar = Se ha pulsado el botón EDITAR CONSULTA
 * valor_parametro = Array (parametro => valor) de valores introducidos
 *                   por el usuario para los parámetros de una consulta SQL
 * ejecutar = Se ha pulsado el botón EJECUTAR CONSULTA
 * aplicar = Se ha pulsado el botón APLICAR CAMBIOS en la pág. de edición
 * cancelar = Se ha pulsado el botón CANCELAR en la pág. de edición
 * borrar = Se ha pulsado el botón BORRAR CONSULTA
 * borrar_ok = 1 Se ha confirmado el borrado de la consulta
 * dia = Mantiene el día que "nos llevamos" de páginas anteriores
 */

require_once("include/autentificado.php");
require_once("include/conecta_db.php");

require_once("include/prepara_calendario.php");

// Hay ciertas acciones que no están permitidas si el usuario no es
// administrador
if (!in_array("informesadm",(array)$session_grupos) && (
     !empty($editar)
  || !empty($aplicar)
  || !empty($cancelar)
  || !empty($borrar)
 )) {
 header("Location: login.php");
}

if (!empty($aplicar)) {
 if ($consulta=="nueva") {
  do {
   if (!$result=@pg_exec($cnx,
    $query="SELECT NEXTVAL('consulta_id_seq') AS id")) {
    $error="No se ha podido obtener un identificador para la nueva consulta";
    break;
   }

   if (!$row=@pg_fetch_array($result,0,PGSQL_ASSOC)) {
    $error="No se ha podido obtener un identificador para la nueva consulta";
    break;
   }
   $consulta=$row["id"];

   if (!$result=@pg_exec($cnx,"INSERT INTO "
    ."consulta(id,titulo,sql,parametros,descripcion,publica) "
    ."VALUES ('$consulta','$titulo','$sql','$parametros','$descripcion','"
    .checkbox_to_sql($publica)."')")) {
    $error="No se ha podido insertar la nueva consulta";
    break;
   }
   $confirmacion="La consulta se creó correctamente";
  } while(false);
 } else {
  if (!@pg_exec($cnx,$query="UPDATE consulta SET "
   ."titulo='$titulo',sql='$sql',parametros='$parametros',"
   ."descripcion='$descripcion',publica='".checkbox_to_sql($publica)."'"
   ." WHERE id='$consulta'")) {
    $error="No se ha podido actualizar la consulta";
  } else {
   $confirmacion="La consulta se actualizó correctamente";
  }
 }
}

if (!empty($borrar)) {
 if ($borrar_ok==1) {
  do {
   if (!$result=@pg_exec($cnx,
    $query="DELETE FROM consulta WHERE id='$consulta'")) {
    $error="No se ha podido borrar la consulta";
    break;
   }
   unset($consulta);
   $seleccionar="Seleccionar";
   $confirmacion="La consulta se borró correctamente";
  } while(false);
 } else {
  $error="Debe confirmar el borrado en la casilla adjacente al botón Borrar";
 }
}

if (!in_array("informesadm",(array)$session_grupos)) 
 $string_restriccion=" WHERE publica='t'";
else 
 $string_restriccion="";
$result=@pg_exec($cnx,$query="SELECT id,titulo FROM consulta"
 .$string_restriccion." ORDER BY titulo")
or die("No se ha podido completar la operación");

$consultas=array();
for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
 $consultas[$row["id"]]=$row["titulo"];
}
if (in_array("informesadm",(array)$session_grupos)) {
 $consultas["nueva"]="&lt;Nueva consulta&gt;";
}
@pg_freeresult($result);

// Controlamos si el usuario está viendo una consulta que no debe
if (!in_array("informesadm",(array)$session_grupos)
 && !empty($consulta)
 && !in_array($consulta,array_keys($consultas))
) {
 header("Location: login.php");
}

if (!empty($cancelar)) {
 if ($consulta=="nueva") unset($consulta);
 $seleccionar="Seleccionar";
}

if (!empty($consulta)) {
 if ($consulta=="nueva") {
  foreach (array("titulo","descripcion","sql","parametros","publica") as $i) {
   unset($$i);
  }
 } else {
  $result=@pg_exec($cnx,$query="SELECT titulo,descripcion,sql,parametros,publica"
   ." FROM consulta WHERE id='$consulta' ORDER BY titulo")
  or die("No se ha podido completar la operación: $query");

  if ($row=@pg_fetch_array($result,0,PGSQL_ASSOC)) {
   extract($row);
  } else {
   unset($consulta);
  }
  @pg_freeresult($result);

  if (!empty($ejecutar)) {

   foreach (set_to_array($parametros) as $parametro) {
    $parametro=trim($parametro);
    $sql=str_replace($parametro,"'".$valor_parametro[$parametro]."'",$sql);
   }

   do {
    if (!$result=@pg_exec($cnx,$query=$sql)) {
     $error="Error de SQL: <i>".pg_errormessage()
      ."</i><br>Consulta original:<pre>$query</pre>";
     break;
    }

    $result_consulta=array();
    for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
     $result_consulta[]=$row;
    }
    @pg_freeresult($result);
   } while(false);
  }
 }
}

if (!empty($seleccionar) && ($consulta=="nueva")) {
 unset($seleccionar);
 $editar="Editar";
}

if (!empty($seleccionar)) {
 $valor_parametro=array();
 unset($editar);
}

require_once("include/cerrar_db.php");

$title="Extracción de resultados";
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
 <td><b>Consulta:</b></td>
 <td>
  <select name="consulta" onchange="javascript: resultados.submit();">
   <?=array_to_option(
    array_values($consultas),
    $consulta,
    array_keys($consultas))?>
  </select>
 </td>
 <td>
  <input type="submit" name="seleccionar" value="Seleccionar consulta">
  <input type="hidden" name="dia" value="<?=$dia?>">
 </td>
</tr>
</table>
<br>
<?
if (!empty($consulta)) {
?>
<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0" width="50%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="titulo_caja"><font
 color="#FFFFFF" class="titulo_caja">
<!-- título caja -->
Descripción
<!-- fin título caja -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_caja">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->
<?=$descripcion?>
<!-- fin texto caja -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- fin caja -->
<br>
<?
 if (empty($editar)) {
  if (!empty($parametros)) {
?>
<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0" width="50%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="titulo_caja"><font
 color="#FFFFFF" class="titulo_caja">
<!-- título caja -->
Parámetros
<!-- fin título caja -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_caja">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->
<table border="0">
<?
   foreach(set_to_array($parametros) as $parametro) {
    $parametro=trim($parametro);
?>
 <tr>
  <td><?=$parametro?></td>
  <td>
   <input type="text" name="valor_parametro[<?=$parametro?>]"
    value="<?=$valor_parametro[$parametro]?>">
  </td>
 </tr>
<?
   }
?>
 <tr><td colspan="2">
  <br>
  <script language="javascript">
  <!--
  function openpopup() {
   result=window.open('etiquetas.php','_blank','width=400,height=300')"
  }
  -->
  </script>
  Ver equivalencia de códigos
  <a href="#" onclick="javascript:window.open('etiquetas.php','remote',
   'scrollbars=yes,toolbar=no,menubar=no,status=no,width=700,height=400');"
  >en ventana</a> / <a href="etiquetas.php"
  >a pantalla completa</a>
 </td></tr>
</table>
<!-- fin texto caja -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- fin caja -->
<br>
<?
  }

  if (!empty($ejecutar)) {
?>
<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0" width="95%">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="5" width="100%">
<?
   $primera=true;
   foreach ((array)$result_consulta as $fila) {
    if ($primera) {
     $primera=false;
?>
 <tr>
<?
     foreach (array_keys((array)$fila) as $campo) {
?>
  <td bgcolor="#000000" class="titulo_caja">
   <font color="#FFFFFF" class="titulo_caja">
   <?=$campo?>
   </font>
  </td>
<?
     }
?>
 </tr>
<?
    }
?>
 <tr>
<?
    foreach ((array)$fila as $campo) {
     if (empty($campo)) $campo="&nbsp;";
?>
  <td bgcolor="#FFFFFF" class="texto_caja">
   <?=$campo?>
  </td>
<?
    }
?>
 </tr>
<?
   }
?>
</table>
</td></tr></table>
<!-- fin caja -->
<?
  }
?>
<br>
<table border="0">
 <tr>
<?
  if (in_array("informesadm",(array)$session_grupos)) {
?>
  <td width="50%">
   <input type="checkbox" name="borrar_ok" value="1">
   <input type="submit" name="borrar" value="Borrar"
    style="color: #FF0000">
  </td>
<?
  }
?>
  <td width="25%"><input type="submit" name="ejecutar" value="Ejecutar"></td>
<?
  if (in_array("informesadm",(array)$session_grupos)) {
?>
  <td width="25%"><input type="submit" name="editar" value="Editar"></td>
<?
  }
?>
 </tr>
</table>
<?
 } else {
?>
<!-- caja -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="titulo_caja"><font
 color="#FFFFFF" class="titulo_caja">
<!-- título caja -->
Edición de consulta
<!-- fin título caja -->
</font></td></tr><tr><td bgcolor="#FFFFFF" class="texto_caja">
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="texto_caja">
<!-- texto caja -->
<table border="0">
 <tr>
  <td>
   Título:<br>
   <input type="text" name="titulo" value="<?=$titulo?>" size="80">
  </td>
 </tr>
 <tr>
  <td>
   Descripcion:<br>
   <textarea name="descripcion" cols="80" rows="5"><?=$descripcion?></textarea>
  </td>
 </tr>
 <tr>
  <td>
   SQL:<br>
   <textarea name="sql" cols="80" rows="5"><?=$sql?></textarea>
  </td>
 </tr>
 <tr>
  <td>
   Lista de parámetros (separados por comas):<br>
   <input type="text" name="parametros" value="<?=$parametros?>" size="80">
  </td>
 </tr>
 <tr>
  <td>
   <input type="checkbox" name="publica" value="t" <?=sql_to_checkbox($publica)?>>
   Permitir a todos los usuarios usar esta consulta
  </td>
 </tr>
</table>
<!-- fin texto caja -->
</font></td></tr></table></td></tr></table></td></tr></table>
<!-- fin caja -->
<br>
<table border="0">
 <tr>
  <td>
   <input type="submit" name="aplicar" value="Aplicar cambios">
   <input type="hidden" name="editar" value="modo_edicion">
  </td>
  <td><input type="submit" name="cancelar" value="Cancelar"></td>
 </tr>
</table>
<?
 }
}
?>
</form>

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
