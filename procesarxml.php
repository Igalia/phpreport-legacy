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


/** procesarxml.php
 *
 * Aqui se validan los ficheros de informes XML que hay en un directorio,
 * es decir, se comprueba si los XML están bien formados, son validos y
 * si son coherentes. Para la realizacion de estas acciones se usan las
 * funciones de apoyo definidas en "infxml.php" y los ficheros "xsl". Una
 * vez que se ha validado los informes, se introducen en la BD.
 *
 * Parámetros recibidos:
 *
 * filedir: diretorio (dentro de $ruta_absoluta) en donde se encuentran
 * los informes XML que van a ser validados y/o guardados.
 *
 * botonvalidar: Se ha pulsado el boton validar informes.
 * botonguardar: Se ha pulsado el boton guardar informes en la BD.
 *
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */

require_once("include/config.php");
require_once("include/autentificado.php");

if (empty($filedir)) header("Location: subirxml.php");
if (strstr($filedir, "/")) header("Location: subirxml.php");

require("include/infxml.php");
require("include/html.php");

$title = "Validar informes semanales XML";
require("include/plantilla-pre.php");

require_once("include/prepara_calendario.php");

$xmldir = $filedir;
$filedir = $filedir."/";

?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
    <td style="text-align: center; vertical-align: top;">
    <center>
<?

unset($error);
unset($session_logins);
foreach ($session_usuarios as $u=>$v) $session_logins[] = $u;
$contador_v = 0;
$contador_g = 0;


IniTablaGeneral("Informes XML", "Procesar los informes XML. Puede editar los informes y ".
		"revalidarlos tantas veces como sea necesario para finalmente guardarlos en la BD.");

if (is_dir($ruta_absoluta.$filedir))
{
    $informes_validos = DirInformesValidos($session_uid, $session_grupos, $session_logins, $ruta_absoluta.$filedir, $contador_validos);
}
else $contador_validos = 0;

if ($contador_validos > 0)
{

?>
    <form name="editor" action="<? echo "$PHP_SELF"; ?>" enctype="multipart/form-data" method="post">

    <table class="dir_tabla" cellpadding="4" cellspacing="1">
    <tr class="dir_titulo">
        <td align="center" width="40%">Informe Semanal</td>
        <td align="center" width="20%">Válido</td>
        <td align="center" width="20%">Coherente</td>
        <td align="center" width="20%">Guardado</td>
    </tr>
<?php

    $contador = 0;
    $error_sql = false;

    //if (!empty($botonvalidar))
    require_once("include/conecta_db.php");

    foreach($informes_validos as $entrada)
    {
      	if ($contador % 2 == 0) echo "<tr class=\"dir_par\">";
     	else echo "<tr class=\"dir_impar\">";

	LinkEditorXML($xmldir, $entrada);
        unset($msg);

    	if (ValidarDTD($ruta_absoluta.$filedir.$entrada, $msg) == 1)
        {
            echo "<td><font class=\"f_correcto\">OK</font></td>";
	    $ret = ValidarXML($entrada, $ruta_absoluta.$filedir, $session_logins, $msg);

            if ($ret == 1)
            {
                echo "<td><font class=\"f_correcto\">OK</font></td>";
    		$contador_v++;

                if (!empty($botonguardar))
                {
                    if (!$error_sql)
                    {
                    	unset($mesg);
                    	$retg = GuardarXML2SQL($cnx, $session_uid, $session_logins, $ruta_absoluta.$filedir, $entrada, $mesg);
                    	if ($retg == 1)
                        {
                            echo "<td><font class=\"f_correcto\">OK</font></td>";
    			    $contador_g++;
                        }
                    	elseif ($retg == 0)
                    	{
                     	    echo "<td><font class=\"f_incorrecto\">XSLT Error</font></td>";
                            $error = $mesg;
		    	}
                    	else
                    	{
                     	    echo "<td><font class=\"f_incorrecto\">BD SQL Error</font></td>";
                            $error_sql = true;
                            $error = $mesg;
                    	}
                    }
                    else echo "<td><font class=\"f_incorrecto\">BD SQL Error</font></td>";
                }
            }
            elseif ($ret == 0)
            {
            	echo "<td><font class=\"f_incorrecto\">".$msg."</font></td>";
	        echo "<td><font class=\"f_incorrecto\">---</font></td>";
            }
            else
            {
                $error = $msg;
            	echo "<td><font class=\"f_incorrecto\">XSLT Error</font></td>";
	        echo "<td><font class=\"f_incorrecto\">---</font></td>";
            }
  	}
	else
        {
            echo "<td><font class=\"f_incorrecto\">".$msg."</font></td>";
            echo "<td><font class=\"f_incorrecto\">---</font></td>";
            echo "<td><font class=\"f_incorrecto\">---</font></td>";
        }
        echo "</tr>";
        $contador++;
    }

?>
    </table>

    <? if ($contador_v != $contador_g) { ?>

    <table width="100%" border="0" cellpadding="6">
    <tr>
     	<td align="center" width="30%">
	     	<input type="submit" name="botonvalidar" value="Revalidar"/>
      	</td>
        <td align="left">
                Comprueba si los informes están bien formados, son válidos conforme al DTD y
                si son coherentes (si se solapan tareas, días incorrectos, ...).
        </td>
    </tr>
    <tr>
      	<td align="center" width="30%">
    	  	<?
                if ($contador_validos > 0)
                	echo "<input type=\"submit\" name=\"botonguardar\" value=\"Guardar\"/>";
                ?>
        </td>
        <td align="left">
                Introduce los datos de los informes XML que son correctos (válidos y coherentes)
                en la BD.
        </td>
    </tr>
    </table>

    <? } ?>

    <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
    </form>

<?php

}
else $error = "Imposible encontrar informes o todos los enviados son incorrectos.";

require_once("include/cerrar_db.php");

if (!empty($botonguardar))
{
    if ($contador_v == $contador_g)
    {
    	$msg = "<font color=\"#00FF00\">Todos los informes válidos han sido guardados correctamente. </font>";
    }
    else $msg = "<font color=\"#FF0000\">OJO, sólo ".$contador_g." informes de ".$contador_v." válidos han sido guardados. </font>";
    FinTablaGeneral($msg);

    unset($botonguardar);
}
else
{
    //require_once("include/cerrar_db.php");

    if ($contador_v == $contador_validos)
    {
    	$msg = "<font color=\"#00FF00\">Todos los informes son válidos y coherentes. Están listos para guardar.</font>";
    }
    else $msg = "<font color=\"#FF0000\">OJO, solo ".$contador_v." informes de ".$contador_validos." son válidos y coherentes. Edite los que considere oportuno.</font>";
    FinTablaGeneral($msg);
}
//else FinTablaGeneral();


if (!empty($error)) TablaError($error);

?>
	</center>

    </td>
    <td style="width: 25ex" valign="top">
	<? require("include/muestra_secciones.php"); ?>
	<br>
	<? if ($contador_v == $contador_g) require("include/muestra_calendario.php"); ?>
    </td>
</tr>
</table>

<?

require("include/plantilla-post.php");

?>

