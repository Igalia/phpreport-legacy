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


/** subirxml.php
 *
 * Se suben y descomprimen los ficheros de $reports XML del usuario.
 * Crea un directorio temporal (en ruta_absoluta) unico y copia
 * alli los archivos subidos.
 *
 * Parámetros recibidos:
 *
 * filedir: diretorio (dentro de $absolute_path) en donde se encuentran
 * los $reports XML que van a ser validados y/o guardados.
 *
 * treat_button: Se ha pulsado el boton para tratar los $reports.
 * botonguardar: Se ha pulsado el boton para subir el informe.
 * botonnombrar: Se ha pulsado el boton para renombrar un archivo
 *	nuevo_nombre: nombre nuevo del fichero.
 *      nombre_renombrar: fichero a renombrar.
 *
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */

require_once("include/config.php");
require_once("include/autenticate.php");

require("include/infxml.php");
require("include/html.php");

//$cmd = "rm -rf $absolute_path";
//`$cmd`;
echo($absolute_path);
if (!is_dir($absolute_path)) mkdir($absolute_path, 0777);

$title = _("Introducir $reports semanales XML");
require("include/template-pre.php");

?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
    <td style="text-align: center; vertical-align: top;">
    <center>

<?

$session_logins = array();
foreach ($session_users as $u=>$v) $session_logins[] = $u;

if (!empty($send_button) && ($send_button == "Enviar"))
{
    if (empty($filedir))
    {
    	if (strlen($file_name) <= 4) $error = _("Nombre de archivo no valido.");
    	elseif (!ereg('^$reportsemanal([a-zA-Z0-9_-]+)\.xml$|([a-zA-Z0-9_-]+)\.tgz$', $file_name))
    	{
            $error = _("El formato de archivo no es valido, solo se admiten $reports XML o grupos de $reports comprimidos con TGZ.");
    	}
    	elseif ($file_size > $file_limit)
    	{
            $error = _("El archivo seleccionado supera los ").$file_limit.
            _(" bytes, no se acepta por motivos de seguridad.");
    	}
    	else
    	{
       	    // genera un nombre de directorio unico
            do
            {
            	$seed = substr(md5(microtime().posix_getpid()), 0, 8);
            	$filedir = $file_name."_".$seed."_dir";
            	$filename = $absolute_path.$filedir;
            }
            while (is_dir($filename));

            mkdir($filename,0777);
            $xmldir = $filedir;
            $filedir .="/";
            $filename = $absolute_path.$filedir.$file_name;

            if (file_exists($filename)) @unlink($filename);
            if (!@move_uploaded_file($file, $filename))
            {
             	$error = _("Error del servidor mientras se trataba el archivo, intentelo más tarde o pongase en contacto con su administrador.");
            }
            else
            {
              	if (ereg('\.tgz$', $file_name))
              	{
              	    $ar = array();
                    $retval = 0;
                    $cmd = exec("tar zxvf ".EscapeShellCmd($filename)." -C ".
                    	   EscapeShellCmd($absolute_path.$filedir), $ar, $retval);
		    unset($cmd);
		    unset($ar);
		    if ($retval != 0) $error = _("Error descomprimiendo el archivo. Imposible continuar.");
		    unlink($filename);
            	}
	    }
	}
    }
    else
    {
        $xmldir = $filedir;
        $filedir .="/";
    }

    if (empty($error))
    {
	if (!empty($name_button) || ($name_button == _("Nombrar")))
	{
    	    if (!(strstr($new_name, "/") || strstr($name_rename, "/")))
    	    {
                if (($new_name != _("vacio")) && (strlen($new_name) > 3))
                    @rename($absolute_path.$filedir.$name_rename, $absolute_path.$filedir.EscapeShellCmd($new_name));
    	    }
            unset($name_button);
	}
    	$dh = @opendir($absolute_path.$filedir);
	$files = array();
	while ($entry = @readdir($dh))
	{
		// Busca sólo los archivos del directorio
		if (($entry != ".") && ($entry != "..")) $files[] = $entry;
	}
	@closedir($dh);
	usort($files, 'strnatcmp');

      	IniGeneralTable(_("$reports XML"), _("Contenido subido al servidor. Edite los ficheros que sean incorrectos (formato de nombre incorrecto, fecha incorrectam etc.)"));
?>

    <form name="editor" action="processxml.php" enctype="multipart/form-data" method="post">
    <table class="dir_table" cellpadding="4" cellspacing="1">
    <tr class="dir_title">
        <td align="center" width="70%"><?=_("Nombre del Informe Semanal")?></td>
        <td align="center" width="20%"><?=_("Tama&ntilde")?>;o</td>
        <td align="center" width="10%"><?=_("Filtro")?></td>
    </tr>

<?php
	// pone en pantalla la informacion
     	$count = 0;
     	$count_valids = 0;
     	$size_cum = 0;
     	$fields = array();
        $reports_valids = array();
        $reports_possibles = array();
     	foreach($files as $entry)
     	{
            if ($count % 2 == 0) echo "<tr class=\"dir_even\">";
            else echo "<tr class=\"dir_odd\">";
         
            $size = filesize($absolute_path.$filedir.$entry);
            $ret = ValidName($session_logins, $entry, $fields);
            if ($ret == 1)
            {
              	if (in_array("$reportsadm",(array)$session_groups))
                {
		    LinkEditorXML($xmldir, $entry);
                    echo "\t<td>".$size."</td>";
                    echo "\t<td><font color=\"blue\" face=\"courier\"><b>OK</b></font></td>";
                    $count_valids++;
                    $reports_valids[] = $entry;
             	}
             	else
             	{
	            if ($session_uid == $fields[_("usuario")])
                    {
		    	LinkEditorXML($xmldir, $entry);
                     	echo "\t<td>".$size."</td>";
                      	echo "\t<td><font color=\"blue\" face=\"courier\"><b>OK</b></font></td>";
                      	$count_valids++;
	                $reports_valids[] = $entry;
                    }
                    else
                    {
		    	//LinkEditorXML($xmldir, $entry);
                        echo "\t<td align=\"left\"><font color=\"red\">".$entry."</font></td>";
                      	echo "\t<td>".$size."</td>";
                      	echo "\t<td><font color=\"red\" face=\"courier\"><b>NP</b></font></td>";
                    }
             	}
                $reports_possibles[] = $entry;
            }
            elseif ($ret == -1)
            {
                echo "\t<td align=\"left\"><font color=\"red\">".$entry."</font></td>";
              	echo "\t<td>".$size."</td>";
              	echo "\t<td><font color=\"red\" face=\"courier\"><b>IN</b></font></td>";
                if (ereg('\.xml$', $entry)) $reports_possibles[] = $entry;
            }
            elseif ($ret == 0)
            {
       	    	//LinkEditorXML($xmldir, $entry);
                echo "\t<td align=\"left\"><font color=\"red\">".$entry."</font></td>";
               	echo "\t<td>".$size."</td>";
               	echo "\t<td><font color=\"red\" face=\"courier\"><b>UD</b></font></td>";
                $reports_possibles[] = $entry;
            }
            else
            {
       	    	//LinkEditorXML($xmldir, $entry);
                echo "\t<td><font color=\"red\" face=\"courier\">".$entry."</font></td>";
              	echo "\t<td>".$size."</td>";
              	echo "\t<td><font color=\"red\" face=\"courier\"><b>FI</b></font></td>";
                $reports_possibles[] = $entry;
            }
            $size_cum += $size;
            $count++;
            echo "</tr>";
     	}
     	$count--;
?>

     </table>

     <table width="100%" border="0" cellpadding="5">
     <tr>
     	<td align="center">
        	<? if ($count_valids > 0) echo "<input type=\"submit\" name=\"treat_button\" value=\"Tratar\"/>"; ?>
        </td>
        <td align="right">
	       	<table>
            	<tr><td>
			<font color="red"><b>FI</b></font> : <?=_("Fecha Incorrecta, el informe no se corresponde con una semana.")?> <br>
			<font color="red"><b>IN</b></font> : <?=_("INcorrecto, el nombre del fichero no se reconoce con el formato.")?> <br>
			<font color="red"><b>UD</b></font> : <?=_("Usuario Desconocido. No existe el usuario en el LDAP.")?> <br>
			<font color="red"><b>NP</b></font> : <?=_("No Propietario, vd. no es el propietario del informe semanal.")?> <br>
			<font color="blue"><b>OK</b></font> : <?=_("Informe listo para ser validado, verificado y tratado.")?> <br>
                </td></tr>
                </table>
        </td>
     </tr>
     </table>

     <input type="hidden" name="send_button" value="<? echo $send_button; ?>"/>
     <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
     </form>

     <form name="rename" action="<? echo "$PHP_SELF" ?>" enctype="multipart/form-data" method="post">
     <table width="100%" border="0" cellpadding="5">
     <tr>
        <td align="left">
       		<select name="name_rename" size="1">
       			<option value="empty"> ... </option>
                	<?
     			foreach($reports_possibles as $entry) echo "<option value=".$entry.">".$entry."</option>";
			?>
       		</select>
        </td>
     	<td align="center">
        	<input type="submit" name="name_button" value=<?_("Renombrar")?>/>
        </td>
        <td align="right">
        	<input type="text" name="new_name">
        </td>
     </tr>
     </table>

     <input type="hidden" name="send_button" value="<? echo $send_button; ?>"/>
     <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
     </form>

<?php

     	EndGeneralTable($count_valids._(" de ").$count._(" nombres de archivos correctos."));
    }
    else unset($send_button);
}

if (empty($send_button) || ($send_button != _("Enviar")))
{
   IniGeneralTable(_("Recoger $reports en formato XML"), _("Seleccione un informe en XML o un conjunto de $reports del mismo usuario comprimidos en formato TGZ y pulse enviar cuando este listo.<br>Recuerde utilizar siempre el fichero <a href=\"rules.dtd.php\"><b>rules.dtd</b> actualizado</a>."));
?>

	<form name="upload" action="<? echo "$PHP_SELF" ?>" enctype="multipart/form-data" method="post">

	<table border="0" cellpadding="4" align="center">
   	<tr>
		<td align="left" width="30%">
		<?=_("Fichero local")?>
		</td>
      		<td align="left" width="70%">
      			<input type="file" name="file" size="50"/>
      		</td>
   	</tr>
   	<!-- <tr>
		<td align="left" width="30%">
		 <?=_("Descripcion o informacion complementaria.")?>
		</td>
      		<td align="left" width="70%">
      		<textarea name="description" cols="50" rows="3"><? echo $description; ?></textarea>
        </td>
   	</tr> -->
   	<tr>
    		<td align="left" width="100%" colspan="2">
        	<input type="submit" name="send_button" value=<?=_("Enviar")?> />
      		</td>
   	</tr>
   	</table>

   </form>
<?

     EndGeneralTable();
}

if (!empty($error)) ErrorTable($error);

?>

    </center>

    </td>
    <td style="width: 25ex" valign="top">
	<? require("include/show_sections.php") ?>
    </td>

</tr>
</table>

<?

require("include/template-post.php");

?>
