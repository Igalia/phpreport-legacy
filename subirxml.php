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
 * Se suben y descomprimen los ficheros de informes XML del usuario.
 * Crea un directorio temporal (en ruta_absoluta) unico y copia
 * alli los archivos subidos.
 *
 * Parámetros recibidos:
 *
 * filedir: diretorio (dentro de $ruta_absoluta) en donde se encuentran
 * los informes XML que van a ser validados y/o guardados.
 *
 * botontratar: Se ha pulsado el boton para tratar los informes.
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
require_once("include/autentificado.php");

require("include/infxml.php");
require("include/html.php");

//$cmd = "rm -rf $ruta_absoluta";
//`$cmd`;

if (!is_dir($ruta_absoluta)) mkdir($ruta_absoluta, 0777);

$title = "Introducir informes semanales XML";
require("include/plantilla-pre.php");

?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
    <td style="text-align: center; vertical-align: top;">
    <center>

<?

$session_logins = array();
foreach ($session_usuarios as $u=>$v) $session_logins[] = $u;

if (!empty($botonenviar) && ($botonenviar == "Enviar"))
{
    if (empty($filedir))
    {
    	if (strlen($archivo_name) <= 4) $error = "Nombre de archivo no valido.";
    	elseif (!ereg('^informesemanal([a-zA-Z0-9_-]+)\.xml$|([a-zA-Z0-9_-]+)\.tgz$', $archivo_name))
    	{
            $error = "El formato de archivo no es valido, solo se admiten ".
            "informes XML o grupos de informes comprimidos con TGZ.";
    	}
    	elseif ($archivo_size > $limite_fichero)
    	{
            $error = "El archivo seleccionado supera los ".$limite_fichero.
            " bytes, no se acepta por motivos de seguridad.";
    	}
    	else
    	{
       	    // genera un nombre de directorio unico
            do
            {
            	$seed = substr(md5(microtime().posix_getpid()), 0, 8);
            	$filedir = $archivo_name."_".$seed."_dir";
            	$filename = $ruta_absoluta.$filedir;
            }
            while (is_dir($filename));

            mkdir($filename,0777);
            $xmldir = $filedir;
            $filedir .="/";
            $filename = $ruta_absoluta.$filedir.$archivo_name;

            if (file_exists($filename)) @unlink($filename);
            if (!@move_uploaded_file($archivo, $filename))
            {
             	$error = "Error del servidor mientras se trataba el archivo, ".
              	"intentelo más tarde o pongase en contacto con su administrador.";
            }
            else
            {
              	if (ereg('\.tgz$', $archivo_name))
              	{
              	    $ar = array();
                    $retval = 0;
                    $cmd = exec("tar zxvf ".EscapeShellCmd($filename)." -C ".
                    	   EscapeShellCmd($ruta_absoluta.$filedir), $ar, $retval);
		    unset($cmd);
		    unset($ar);
		    if ($retval != 0) $error = "Error descomprimiendo el archivo. Imposible continuar.";
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
	if (!empty($botonnombrar) || ($botonnombrar == "Nombrar"))
	{
    	    if (!(strstr($nuevo_nombre, "/") || strstr($nombre_renombrar, "/")))
    	    {
                if (($nuevo_nombre != "vacio") && (strlen($nuevo_nombre) > 3))
                    @rename($ruta_absoluta.$filedir.$nombre_renombrar, $ruta_absoluta.$filedir.EscapeShellCmd($nuevo_nombre));
    	    }
            unset($botonnombrar);
	}
    	$dh = @opendir($ruta_absoluta.$filedir);
	$ficheros = array();
	while ($entrada = @readdir($dh))
	{
		// Busca sólo los archivos del directorio
		if (($entrada != ".") && ($entrada != "..")) $ficheros[] = $entrada;
	}
	@closedir($dh);
	usort($ficheros, 'strnatcmp');

      	IniTablaGeneral("Informes XML", "Contenido subido al servidor. Edite los ficheros que sean ".
        		"incorrectos (formato de nombre incorrecto, fecha incorrectam etc.)");
?>

    <form name="editor" action="procesarxml.php" enctype="multipart/form-data" method="post">
    <table class="dir_tabla" cellpadding="4" cellspacing="1">
    <tr class="dir_titulo">
        <td align="center" width="70%">Nombre del Informe Semanal</td>
        <td align="center" width="20%">Tama&ntilde;o</td>
        <td align="center" width="10%">Filtro</td>
    </tr>

<?php
	// pone en pantalla la informacion
     	$contador = 0;
     	$contador_validos = 0;
     	$tamano_cum = 0;
     	$campos = array();
        $informes_validos = array();
        $informes_posibles = array();
     	foreach($ficheros as $entrada)
     	{
            if ($contador % 2 == 0) echo "<tr class=\"dir_par\">";
            else echo "<tr class=\"dir_impar\">";
         
            $tamano = filesize($ruta_absoluta.$filedir.$entrada);
            $ret = NombreValido($session_logins, $entrada, $campos);
            if ($ret == 1)
            {
              	if (in_array("informesadm",(array)$session_grupos))
                {
		    LinkEditorXML($xmldir, $entrada);
                    echo "\t<td>".$tamano."</td>";
                    echo "\t<td><font color=\"blue\" face=\"courier\"><b>OK</b></font></td>";
                    $contador_validos++;
                    $informes_validos[] = $entrada;
             	}
             	else
             	{
	            if ($session_uid == $campos["usuario"])
                    {
		    	LinkEditorXML($xmldir, $entrada);
                     	echo "\t<td>".$tamano."</td>";
                      	echo "\t<td><font color=\"blue\" face=\"courier\"><b>OK</b></font></td>";
                      	$contador_validos++;
	                $informes_validos[] = $entrada;
                    }
                    else
                    {
		    	//LinkEditorXML($xmldir, $entrada);
                        echo "\t<td align=\"left\"><font color=\"red\">".$entrada."</font></td>";
                      	echo "\t<td>".$tamano."</td>";
                      	echo "\t<td><font color=\"red\" face=\"courier\"><b>NP</b></font></td>";
                    }
             	}
                $informes_posibles[] = $entrada;
            }
            elseif ($ret == -1)
            {
                echo "\t<td align=\"left\"><font color=\"red\">".$entrada."</font></td>";
              	echo "\t<td>".$tamano."</td>";
              	echo "\t<td><font color=\"red\" face=\"courier\"><b>IN</b></font></td>";
                if (ereg('\.xml$', $entrada)) $informes_posibles[] = $entrada;
            }
            elseif ($ret == 0)
            {
       	    	//LinkEditorXML($xmldir, $entrada);
                echo "\t<td align=\"left\"><font color=\"red\">".$entrada."</font></td>";
               	echo "\t<td>".$tamano."</td>";
               	echo "\t<td><font color=\"red\" face=\"courier\"><b>UD</b></font></td>";
                $informes_posibles[] = $entrada;
            }
            else
            {
       	    	//LinkEditorXML($xmldir, $entrada);
                echo "\t<td><font color=\"red\" face=\"courier\">".$entrada."</font></td>";
              	echo "\t<td>".$tamano."</td>";
              	echo "\t<td><font color=\"red\" face=\"courier\"><b>FI</b></font></td>";
                $informes_posibles[] = $entrada;
            }
            $tamano_cum += $tamano;
            $contador++;
            echo "</tr>";
     	}
     	$contador--;
?>

     </table>

     <table width="100%" border="0" cellpadding="5">
     <tr>
     	<td align="center">
        	<? if ($contador_validos > 0) echo "<input type=\"submit\" name=\"botontratar\" value=\"Tratar\"/>"; ?>
        </td>
        <td align="right">
	       	<table>
            	<tr><td>
			<font color="red"><b>FI</b></font> : Fecha Incorrecta, el informe no se corresponde con una semana. <br>
			<font color="red"><b>IN</b></font> : INcorrecto, el nombre del fichero no se reconoce con el formato. <br>
			<font color="red"><b>UD</b></font> : Usuario Desconocido. No existe el usuario en el LDAP. <br>
			<font color="red"><b>NP</b></font> : No Propietario, vd. no es el propietario del informe semanal. <br>
			<font color="blue"><b>OK</b></font> : Informe listo para ser validado, verificado y tratado. <br>
                </td></tr>
                </table>
        </td>
     </tr>
     </table>

     <input type="hidden" name="botonenviar" value="<? echo $botonenviar; ?>"/>
     <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
     </form>

     <form name="renombrar" action="<? echo "$PHP_SELF" ?>" enctype="multipart/form-data" method="post">
     <table width="100%" border="0" cellpadding="5">
     <tr>
        <td align="left">
       		<select name="nombre_renombrar" size="1">
       			<option value="vacio"> ... </option>
                	<?
     			foreach($informes_posibles as $entrada) echo "<option value=".$entrada.">".$entrada."</option>";
			?>
       		</select>
        </td>
     	<td align="center">
        	<input type="submit" name="botonnombrar" value="Renombrar"/>
        </td>
        <td align="right">
        	<input type="text" name="nuevo_nombre">
        </td>
     </tr>
     </table>

     <input type="hidden" name="botonenviar" value="<? echo $botonenviar; ?>"/>
     <input type="hidden" name="filedir" value="<? echo $xmldir; ?>"/>
     </form>

<?php

     	FinTablaGeneral($contador_validos." de ".$contador." nombres de archivos correctos.");
    }
    else unset($botonenviar);
}

if (empty($botonenviar) || ($botonenviar != "Enviar"))
{
   IniTablaGeneral("Recoger informes en formato XML", "Seleccione un informe ".
   " en XML o un conjunto de informes del mismo usuario comprimidos en formato ".
   " TGZ y pulse enviar cuando este listo.<br>".
   "Recuerde utilizar siempre el fichero ".
   "<a href=\"reglas.dtd.php\"><b>reglas.dtd</b> actualizado</a>.");
?>

	<form name="upload" action="<? echo "$PHP_SELF" ?>" enctype="multipart/form-data" method="post">

	<table border="0" cellpadding="4" align="center">
   	<tr>
		<td align="left" width="30%">
		Fichero local
		</td>
      		<td align="left" width="70%">
      			<input type="file" name="archivo" size="50"/>
      		</td>
   	</tr>
   	<!-- <tr>
		<td align="left" width="30%">
		 Descripcion o informacion complementaria.
		</td>
      		<td align="left" width="70%">
      		<textarea name="descripcion" cols="50" rows="3"><? echo $descripcion; ?></textarea>
        </td>
   	</tr> -->
   	<tr>
    		<td align="left" width="100%" colspan="2">
        	<input type="submit" name="botonenviar" value="Enviar" />
      		</td>
   	</tr>
   	</table>

   </form>
<?

     FinTablaGeneral();
}

if (!empty($error)) TablaError($error);

?>

    </center>

    </td>
    <td style="width: 25ex" valign="top">
	<? require("include/muestra_secciones.php") ?>
    </td>

</tr>
</table>

<?

require("include/plantilla-post.php");

?>
