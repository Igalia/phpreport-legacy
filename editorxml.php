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

/** editorxml.php
 *
 * Esta pag. permite editar un fichero XML en un "textarea". Permite guardar y
 * validar los cambios realizados. Es conveniente abrirlo en una ventana diferente
 * para no entorpecer ...
 *
 * Parámetros recibidos:
 *
 * fichero: fichero en XML que va a ser abierto y editado.
 * ruta: directorio temporal en donde esta el fichero
 *
 * botonvalidar: Se ha pulsado el boton validar informes.
 * botonguardar: Se ha pulsado el boton guardar el fichero XML.
 *
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */

require_once("include/autentificado.php");

if (empty($fichero) || empty($ruta)) header("Location: subirxml.php");

require("include/infxml.php");
require("include/html.php");

$title = "Editor de informes XML";
require("include/plantilla-pre.php");

?>

<center>

<?

$ruta_fichero = $ruta_absoluta.$ruta;
$fichero_valido = false;

unset($session_logins);
foreach ($session_usuarios as $u=>$v) $session_logins[] = $u;

if (strstr($ruta, "/") || strstr($fichero, "/"))
{
    $ruta_valida = false;
    $error = "Ruta no valida ... XP";
}
else $ruta_valida = true;

if ((!empty($ruta_fichero)) && (!empty($fichero)) && $ruta_valida)
{
    $mostrar = false;
    $info = array();
    $dirfichero = $ruta_fichero."/".$fichero;
    $escribir = true;
    $mesg = "OK";

    if (!is_dir($ruta_fichero)) $error = "No se encuentra ruta al fichero";
    elseif (!NombreValido($session_logins, $fichero, $info))
    {
     	if (!empty($info["error"])) $error = $info["error"];
      	else $error = "Fichero con nombre no valido";
    }
    elseif (!is_file($dirfichero)) $error = "Fichero no reconocido";
    elseif (!is_readable($dirfichero)) $error = "No se puede abrir el fichero";
    else
    {
     	$escribir = is_writeable($dirfichero);
      	$mostrar = true;

       	if ((!empty($botoncerrar)) && ($botoncerrar == "Cerrar"))
       	{
    		// Cerrar
      	}
     	elseif ((!empty($botonguardar)) && ($botonguardar == "Guardar"))
      	{
     	    if (($fp = @fopen($dirfichero, "w")) && $escribir)
            {
	     	$bytes = @fwrite($fp, stripslashes($informe));
	      	@fclose($fp);
	      	$mesg = $mesg.", ".$bytes." bytes escritos correctamente.";
	    }
	    else $mesg = "Imposible guardar los cambios realizados";
   	}
   	elseif ((!empty ($botonvalidar)) && ($botonvalidar == "Validar"))
   	{
            $mesg = "Validando informe ".$fichero." ... ";
            $tmpfname = FicheroTemp($ruta_absoluta, "tmp_", "_".$fichero);
            //$tmpfname = tempnam($ruta_absoluta, $fichero."_");
            $fp = @fopen($tmpfname, "w");
            @fwrite($fp, stripslashes($informe));
   	    @fclose($fp);
            //chmod($tmpfname, 0755);
            $rt = ValidarDTD($tmpfname, $mesg);
	    if ($rt == 1)
	    {
            	$ret = ValidarInformeXML($tmpfname, $info, $msg);
                if ($ret == 1) $mesg = "<font color=\"#00FF00\"><b>Informe válido y coherente, listo para ser guardado</b></font>";
        	elseif ($ret == -1)
                {
                	$error = $msg;
                        $mesg = "<font color=\"#FF0000\"><b>Error ... </b></font>";
                }
                else $mesg = $msg;
   	    }
	    elseif ($rt == -4)
            {
            	$error = $mesg;
                $mesg = "<font color=\"#FF0000\"><b>Error ...</b></font>";
            }
            @unlink($tmpfname);
	}
    }
    unset ($botonguardar);
    unset ($botonvalidar);
}


if (mostrar)
{

    IniTablaGeneral("Editor de informes en formato XML");

?>
	<form name="editor" action="<? echo "$PHP_SELF"; ?>" enctype="multipart/form-data" method="post">
   	<table border="0" width="80%" cellpadding="2">
      	<tr>
		<td align="left" colspan="2">
			<font face="Verdana" color="#000000"><? echo "$fichero"; ?>
			</font>
		</td>
		<? if (!$escribir) { ?>
		<td align="right" colspan="1">
			<font color="#990000">ReadOnly</font>
		</td>
		<? } else { ?>
		<td align="right" colspan="1">
			<font color="#339900">ReadWrite</font>
		</td>
		<? } ?>
      	</tr>
      	<tr>
      		<td align="center" colspan="4">
         		<textarea <? if (!$escribir) echo "readonly"; ?> name="informe" cols="70" rows="18"><?
			if (empty ($informe)) @readfile($dirfichero); else echo stripslashes($informe); ?>
			</textarea>
         	</td>
      	</tr>
      	<tr>
        	<td align="left" colspan="1">
          		<input type="submit" name="botonvalidar" value="Validar" />
			<? if ($escribir) { ?>
          		<input type="submit" name="botonguardar" value="Guardar" />
			<? } ?>
         	</td>
         	<td align="right" colspan="2">
                	<input type="button" name="botoncerrar" value="Cerrar" onclick="window.close()"/>
         	</td>
      	</tr>
	</table>

    	<input type="hidden" name="fichero" value="<? echo $fichero; ?>"/>
    	<input type="hidden" name="ruta" value="<? echo $ruta; ?>"/>

	</form>
 
<?php

    FinTablaGeneral($mesg);
}

if (!empty($error)) TablaError($error);

?>

</center>

<?

require("include/plantilla-post.php");

?>

