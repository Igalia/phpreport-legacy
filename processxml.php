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
 * Aqui se validan los ficheros de reports XML que hay en un directorio,
 * es decir, se comprueba si los XML están bien formados, son valids y
 * si son coherentes. Para la realizacion de estas acciones se usan las
 * funciones de apoyo definidas en "infxml.php" y los ficheros "xsl". Una
 * vez que se ha validado los reports, se introducen en la BD.
 *
 * Parámetros recibidos:
 *
 * filedir: diretorio (dentro de $absolute_path) en donde se encuentran
 * los reports XML que van a ser validados y/o guardados.
 *
 * botonvalidar: Se ha pulsado el boton validar reports.
 * botonguardar: Se ha pulsado el boton guardar reports en la BD.
 *
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */

require_once("include/config.php");
require_once("include/autenticate.php");

if (empty($filedir)) header("Location: uploadxml.php");
if (strstr($filedir, "/")) header("Location: uploadxml.php");

require("include/infxml.php");
require("include/html.php");

$title = _("Validar reports semanales XML");
require("include/template-pre.php");

require_once("include/prepare_calendar.php");

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
foreach ($session_users as $u=>$v) $session_logins[] = $u;
$count_v = 0;
$count_g = 0;


IniGeneralTable(_("reports XML"), _("Procesar los reports XML. Puede editar los reports y revalidarlos tantas veces como sea necesario para finalmente guardarlos en la BD."));

if (is_dir($absolute_path.$filedir))
{
    $valid_reports = DirValidReports($session_uid, $session_groups, $session_logins, $absolute_path.$filedir, $count_valids);
}
else $count_valids = 0;

if ($count_valids > 0)
{

?>
    <form name="editor" action="<? echo "$PHP_SELF"; ?>" enctype="multipart/form-data" method="post">

    <table class="dir_tabla" cellpadding="4" cellspacing="1">
    <tr class="dir_titulo">
        <td align="center" width="40%"><?_("Informe Semanal")?></td>
        <td align="center" width="20%"><?_("Válido")?></td>
        <td align="center" width="20%"><?_("Coherente")?></td>
        <td align="center" width="20%"><?_("Guardado")?></td>
    </tr>
<?php

    $count = 0;
    $error_sql = false;

    //if (!empty($botonvalidar))
    require_once("include/connect_db.php");

    foreach($valid_reports as $entry)
    {
      	if ($count % 2 == 0) echo "<tr class=\"dir_even\">";
     	else echo "<tr class=\"dir_odd\">";

	LinkEditorXML($xmldir, $entry);
        unset($msg);

    	if (ValidateDTD($absolute_path.$filedir.$entry, $msg) == 1)
        {
            echo "<td><font class=\"f_correct\">OK</font></td>";
	    $ret = ValidarXML($entry, $absolute_path.$filedir, $session_logins, $msg);

            if ($ret == 1)
            {
                echo "<td><font class=\"f_correct\">OK</font></td>";
    		$count_v++;

                if (!empty($save_button))
                {
                    if (!$error_sql)
                    {
                    	unset($mesg);
                    	$retg = SaveXML2SQL($cnx, $session_uid, $session_logins, $absolute_path.$filedir, $entry, $mesg);
                    	if ($retg == 1)
                        {
                            echo "<td><font class=\"f_correct\">OK</font></td>";
    			    $count_g++;
                        }
                    	elseif ($retg == 0)
                    	{
                     	    echo "<td><font class=\"f_incorrect\">XSLT Error</font></td>";
                            $error = $mesg;
		    	}
                    	else
                    	{
                     	    echo "<td><font class=\"f_incorrect\">BD SQL Error</font></td>";
                            $error_sql = true;
                            $error = $mesg;
                    	}
                    }
                    else echo "<td><font class=\"f_incorrect\">BD SQL Error</font></td>";
                }
            }
            elseif ($ret == 0)
            {
            	echo "<td><font class=\"f_incorrect\">".$msg."</font></td>";
	        echo "<td><font class=\"f_incorrect\">---</font></td>";
            }
            else
            {
                $error = $msg;
            	echo "<td><font class=\"f_incorrect\">XSLT Error</font></td>";
	        echo "<td><font class=\"f_incorrecto\">---</font></td>";
            }
  	}
	else
        {
            echo "<td><font class=\"f_incorrect\">".$msg."</font></td>";
            echo "<td><font class=\"f_incorrect\">---</font></td>";
            echo "<td><font class=\"f_incorrect\">---</font></td>";
        }
        echo "</tr>";
        $count++;
    }

?>
    </table>

    <? if ($count_v != $count_g) { ?>

    <table width="100%" border="0" cellpadding="6">
    <tr>
     	<td align="center" width="30%">
	     	<input type="submit" name="validate_button" value="Revalidate"/>
      	</td>
        <td align="left">
                <?_("Comprueba si los reports están bien formados, son válidos conforme al DTD y
                si son coherentes (si se solapan tareas, días incorrectos, ...).")?>
        </td>
    </tr>
    <tr>
      	<td align="center" width="30%">
    	  	<?
		$temp=_("Guardar");
                if ($count_valids > 0)
                	echo "<input type=\"submit\" name=\"save_button\" value=\"$temp\"/>";
                ?>
        </td>
        <td align="left">
                <?_("Introduce los datos de los reports XML que son correctos (válidos y coherentes) en la BD.")?>
        </td>
    </tr>
    </table>

    <? } ?>

    <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
    </form>

<?php

}
else $error = _("Imposible encontrar reports o todos los enviados son incorrectos.");

require_once("include/close_db.php");

if (!empty($save_button))
{
    if ($count_v == $count_g)
    {
	$temp=_("Todos los reports válidos han sido guardados correctamente.");
    	$msg = "<font color=\"#00FF00\"> $temp </font>";
    }
    else $msg = "<font color=\"#FF0000\">OJO, sólo ".$count_g." reports de ".$count_v." válidos han sido guardados. </font>";
    EndGeneralTable($msg);

    unset($save_button);
}
else
{
    //require_once("include/cerrar_db.php");

    if ($count_v == $count_valids)
    {
    	$msg = "<font color=\"#00FF00\">Todos los reports son válidos y coherentes. Están listos para guardar.</font>";
    }
    else $msg = "<font color=\"#FF0000\">OJO, solo ".$count_v." reports de ".$count_valids." son válidos y coherentes. Edite los que considere oportuno.</font>";
    EndGeneralTable($msg);
}
//else FinTablaGeneral();


if (!empty($error)) ErrorTable($error);

?>
	</center>

    </td>
    <td style="width: 25ex" valign="top">
	<? require("include/show_sections.php"); ?>
	<br>
	<? if ($count_v == $count_g) require("include/show_calendar.php"); ?>
    </td>
</tr>
</table>

<?

require("include/template-post.php");

?>

