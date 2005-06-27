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

/** infxml.php
 *
 * Funciones de apoyo necesarias para el tratamiento de los informes
 * semanales en XML. Emplean los ficheros XSL para realizar las comprobaciones
 * necesarias
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */


// Devuelve el numero de dias de un mes teniendo en cuenta
// los años bisiestos

function DiasMes ($month, $year)
{
    $d_mes = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    if (($month < 1) || ($month > 12)) return 0;
    if ($month != 2) return $d_mes[$month - 1];
    return (checkdate($month, 29, $year)) ? 29 : 28;
}



// Devuelve el numero de mes en el calendario

function Mes2Numero ($month)
{
    $mes = array(
      		"enero" => 1,
	  	"febrero" => 2,
		"marzo" => 3,
		"abril" => 4,
		"mayo" => 5,
		"junio" => 6,
		"julio" => 7,
		"agosto" => 8,
		"septiembre" => 9,
		"octubre" => 10,
		"noviembre" => 11,
		"diciembre" => 12
    );

    $month = trim($month);
    $month = strtolower($month);
    if (array_key_exists($month, $mes)) return $mes[$month];
    else return 0;
}



// Devuelve el mes asociado al numero que recibe como argumento

function Numero2Mes ($month)
{
    $mes = array(
		1 => "enero",
	  	2 => "febrero",
		3 => "marzo",
		4 => "abril",
		5 => "mayo",
		6 => "junio",
		7 => "julio",
		8 => "agosto",
		9 => "septiembre",
		10 => "octubre",
		11 => "noviembre",
		12 => "diciembre"
    );
    return $mes[$month];
}



// Determina si un nombre de un informe XML en formato "informesemanal-<usuario>-
// -<anoini>-<mesini>-<diaini>-<anofin>-<mesfin>-<diafin>.xml. Para ello comprueba
// que el informe abarca una semana y que el usuario existe

function NombreValido ($users, $fichero, &$ret)
{
    $ret["fichero"] = $fichero;

    if (ereg("^informesemanal-([a-z_]+)-([0-9]{4})-([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{2})\.xml$", $fichero, $regs))
    {
      	if (date("w", mktime(0, 0, 0, $regs[3], $regs[4], $regs[2])) != 1)
       	{
      	    $ret["error"] = "El nombre de fichero no se corresponde a un ".
	    "informe semanal, no existe ninguna semana que comienze en la ".
	    "fecha indicada.";
	    return (-3);
	}
	if (date("w", mktime(0, 0, 0, $regs[6], $regs[7], $regs[5])) != 0)
	{
	    $ret["error"] = "El nombre de fichero no se corresponde a un ".
	    "informe semanal, no existe ninguna semana que finalize en la ".
	    "fecha indicada.";
	    return (-2);
	}
        $ret["usuario"] = $regs[1];
        $ret["Ianoini"] = $regs[2];
        $ret["Imesini"] = $regs[3];
        $ret["Idiaini"] = $regs[4];
        $ret["Ianofin"] = $regs[5];
        $ret["Imesfin"] = $regs[6];
        $ret["Idiafin"] = $regs[7];
        if (in_array($regs[1], (array)$users)) return 1;
        else
        {
            $ret["error"] = "Propietario del informe no reconocido.";
            return 0;
        }
    }
    else
    {
       	$ret["error"] = "Nombre con formato incorrecto.";
       	return (-1);
    }
}



// Comprueba si la informacion de un informe XML es coherente.

function ValidarInformeXML ($fichero, $campos, &$mesg)
{
    unset($array_rsl);
    $ret = 0;
    $resultado = exec("xsltproc --novalid -param Ianoini $campos[Ianoini] ".
    		      "-param Imesini $campos[Imesini] -param Idiaini $campos[Idiaini] ".
                      "-param Ianofin $campos[Ianofin] -param Imesfin $campos[Imesfin] ".
                      "-param Idiafin $campos[Idiafin] include/xmlcoherente.xsl $fichero", $array_rsl, $ret);

    $resultado = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $mesg = "XSLT Error: ".$ret.". (Mas informacion en el manual de 'xsltproc').";
        return (-1);
    }
    else
    {
      	if ($resultado != 'OK')
        {
            $mesg = "Error ".$resultado;
            return 0;
        }
        else
        {
            $mesg = "OK";
            return 1;
        }
    }
}



function ValidarXML ($fichero, $dir, $session_logins, &$msg)
{

    //$xsltproc = xslt_create();
    //xslt_set_encoding($xsltproc, "ISO-8859-1");
    //...
    $ret = NombreValido($session_logins, $fichero, $campos);
    if ($ret != 1)
    {
        $msg = "Informe con formato de nombre erroneo: ".$fichero.".";
        return (-2);
    }
    unset($array_rsl);
    $resultado = exec("xsltproc --novalid -param Ianoini $campos[Ianoini] ".
    		      "-param Imesini $campos[Imesini] -param Idiaini $campos[Idiaini] ".
                      "-param Ianofin $campos[Ianofin] -param Imesfin $campos[Imesfin] ".
                      "-param Idiafin $campos[Idiafin] include/xmlcoherente.xsl ".$dir.$fichero, $array_rsl, $ret);

    $resultado = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret.". (Mas informacion en el manual de 'xsltproc').";
        return (-1);
    }
    else
    {
      	if ($resultado != 'OK')
        {
            $msg = "Error ".$resultado;
            return 0;
        }
        else
        {
            $msg = "OK";
            return 1;
        }
    }
}



// Inserta los datos de un informe XML en la BD, mediante XSLT y hojas de estilo

function GuardarXML2SQL ($cnx, $session_uid, $session_logins, $dir, $fichero, &$msg)
{
    $ret = NombreValido($session_logins, $fichero, $campos);
    if ($ret != 1)
    {
        $msg = "Informe con nombre erroneo";
        return (-2);
    }
    unset($array_rsl);
    $resultado = exec("xsltproc --novalid -param Ianoini $campos[Ianoini] ".
		      "-param Imesini $campos[Imesini] -param Idiaini $campos[Idiaini] ".
		      "-param Ianofin $campos[Ianofin] -param Imesfin $campos[Imesfin] ".
		      "-param Idiafin $campos[Idiafin] --stringparam Iusuario $campos[usuario] ".
                      "include/xml2php.xsl ".$dir.$fichero, $array_rsl, $ret);

    $resultado = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret.". (Mas informacion en el manual de 'xsltproc').";
        return 0;
    }
    else
    {
        unset($error);

        // peligrorrrr ;-)

        eval($resultado);

        if (!empty($error))
        {
            $msg = "BD Error ".$error;
            return (-1);
        }
        return 1;
    }
}



// Obtiene todos los informes bien nombrados y del propio usuario de un directorio

function DirInformesValidos ($session_uid, $session_grupos, $session_logins, $dir, &$validos)
{
    $informes_validos = array();
    $campos = array();
    $validos = 0;


    $dh = @opendir($dir);
    while ($entrada = @readdir($dh))
    {
    	if ((($entrada != ".") && ($entrada != "..")) &&
        (NombreValido($session_logins, $entrada, $campos) == 1))
    	{
     	    if (in_array("informesadm", (array)$session_grupos))
    	    {
            	$validos++;
            	$informes_validos[] = $entrada;
            }
            else
            {
	    	if ($session_uid == $campos["usuario"])
            	{
             	    $validos++;
	            $informes_validos[] = $entrada;
            	}
            }
    	}
    }
    @closedir($dh);
    @usort($informes_validos, 'strnatcmp');

    return $informes_validos;
}



// Crea un fichero temporal

function FicheroTemp ($dir, $prefix, $postfix)
{
   if ($dir[strlen($dir) - 1] == '/') $trailing_slash = "";
   else $trailing_slash = "/";

   if (!is_dir(realpath($dir)) || filetype(realpath($dir)) != "dir") return false;
   if (!is_writable($dir)) return false;

   do {
   	$seed = substr(md5(microtime().posix_getpid()), 0, 8);
       	$filename = $dir . $trailing_slash . $prefix . $seed . $postfix;
   }
   while (file_exists($filename));

   $fp = fopen($filename, "w");
   fclose($fp);
   return $filename;
}



// Valida un fichero XML contra su DTD

function ValidarDTD ($fichero, &$msg)
{
    //$error = array();

    // Regeneramos el fichero reglas.dtd a partir de reglas.dtd.php
    update_dtd();

    if (!(is_file($fichero) && is_readable($fichero)))
    {
      	$msg .= "Imposible acceder al fichero";
      	return(-1);
    }

    /*
    $dom = xmldocfile($fichero);
    //$dom = @xmldocfile($fichero, DOMXML_LOAD_VALIDATING, $error);
    //$dom = domxml_open_file($fichero, DOMXML_LOAD_PARSING, $error);
    if (!is_object($dom))
    {
      	$msg = $msg."El documento xml está mal formado.";
      	//print_r($error);
      	return(-2);
    }
    //if (!domxml_doc_validate($dom, $error))

    $dom->validate($erro);
    if (!empty($erro))
    {
       	$msg = $msg."El documento XML no cumple las especificaciones del DTD.";
      	return(-3);
    }
    return 1;
    */

    $ar = array();
    $ret = 0;
/*
echo "FICHERO LEIDO";

    unset($data);
    $file = @fopen(EscapeShellCmd($fichero), "r+");
    if ($file)
    {
        $control = 0;
     	while (!feof($file))
        {
        	$data .= fread($file, 1024);
                $control++;
                if ($control > 10000) break;
        }
        fseek($file, 0); */
//    	$extract = ereg_replace("^<!DOCTYPE[[:space:]]+dedicacionSemanal[[:space:]]+SYSTEM[[:space:]]+([[:alpha:]\"\'\./]){4,}>",
//    				"<!DOCTYPE dedicacionSemanal SYSTEM \"reglas.dtd\">", $data);
/*    	$extract = ereg_replace("^<!DOCTYPE dedicacionSemanal SYSTEM ([[:alpha:]\"\'\./]){4,}>",
    				"<!DOCTYPE dedicacionSemanal SYSTEM \"reglas.dtd\">", $data);
        fwrite($file, $extract);
        fclose($file);
    }
echo "FICHERO ESCRITO";
*/
    $resultado = exec("cat ".EscapeShellCmd($fichero)." | rxp -x -s -V" , $ar, $ret);
    if ($ret == 0)
    {
    	$msg .= "Informe validado";
        return 1;
    }
    elseif ($ret == 1)
    {
    	$msg .= "Mal formado";
        return (-2);
    }
    elseif ($ret == 2)
    {
    	$msg .= "Doc ! DTD";
        return (-3);
    }
    else
    {
    	$msg .= "Error con rxp ...";
        return (-4);
    }
}



// Comprueba si la informacion que contiene un fichero XML es coherente, es
// decir, comprueba que el XML abarca 2 meses consecutivos, que no solapen
// tareas, etc

function ValidarXMLphp ($doc, $ano, $mes, $dia_ini, $dia_fin, &$mesg)
{
    $secciones = $doc->get_elements_by_tagname("dedicacion");

    if (count($secciones) > 2)
    {
       $mesg .= "El documento XML abarca varios meses. ¡Es semanal!";
       return(-30);
    }
    foreach($secciones as $seccion)
    {
        $dias = array();

        $dias_trabajados = $seccion->child_nodes();
        foreach ($dias_trabajados as $dia)
        {
            if ($dia->node_name() != "dedicacionDiaria") continue;
            $horas_dia = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);

            $tareas_trabajadas = $dia->child_nodes();
            foreach ($tareas_trabajadas as $horas)
            {
                if ($horas->node_name() != "tarea") continue;

                $hora_inicio = intval($horas->get_attribute("inicio"));
                $hora_fin = intval($horas->get_attribute("fin"));

                if (($hora_fin - $hora_inicio) < 1)
                {
                    $mesg .= "dia ".$dia->get_attribute("dia").
                    ", tarea con horario erroneo ";
                    $mesg .= "(hora fin anterior a hora de inicio)";
                    return(-10);
                }
                $h_ini = intval($hora_inicio/100);
                if (($h_ini < 0) || ($h_ini > 23))
                {
                    $mesg .= "dia ".$dia->get_attribute("dia").
                    ", tarea con horario inicial erroneo";
                    return(-11);
                }
                $m_ini = intval($hora_inicio%100);
                if (($m_ini < 0) || ($m_ini > 59))
                {
                    $mesg .= "dia ".$dia->get_attribute("dia").
                    ", tarea con horario inicial (minutos) erroneo";
                    return(-12);
                }
                $h_fin = intval($hora_fin/100);
                if (($h_fin < 0) || ($h_fin > 23))
                {
                    $mesg .= "dia ".$dia->get_attribute("dia").
                    ", tarea con horario final erroneo";
                    return(-13);
                }
                $m_fin = intval($hora_fin%100);
                if (($m_fin < 0) || ($m_fin > 59))
                {
                    $mesg .= "dia ".$dia->get_attribute("dia").
                    ", tarea con horario final (minutos) erroneo";
                    return(-14);
                }
                for ($i = $h_ini; $i <= $h_fin; $i++)
                {
                    if ($i == $h_ini)
                    {
                        if ($h_ini == $h_fin) $horas_dia[$i] += $m_fin - $m_ini;
                        else $horas_dia[$i] += 60 - $m_ini;
                    }
                    elseif ($i == $h_fin) $horas_dia[$i] += $m_fin;
                    else $horas_dia[$i] += 60;

                    if ($horas_dia[$i] > 60)
                    {
                        $mesg .= "la tarea del dia ".$dia->get_attribute("dia").
                        "(de ".$hora_inicio;
                        $mesg .= " a ".$hora_fin."), se solapa";
                        return(-15);
                    }
                }
            }
            $dias[] = intval($dia->get_attribute("dia"));
        }
        $num_dias = count($dias);
        if ($num_dias > 7)
        {
           $mesg .= "El informe abarca mas de 7 dias";
           return(-20);
        }
        sort($dias);
        $menor_dia = $dias[0];
        $mayor_dia = $dias[$num_dias - 1];
        if ($menor_dia < 1)
        {
           $mesg .= "Dia de inicio del informe incorrecto";
           return(-21);
        }
        $mes_informe = mes_a_numero($seccion->get_attribute("mes"));
        if ($dia_ini > $menor_dia)
        {
           $mesg .= "Primer dia del informe incorrecto";
           return(-22);
        }
	}
    $num_dias_mes = dias_del_mes($mes_informe, $ano);
    if (($mayor_dia > $num_dias_mes) || ($dia_fin < $mayor_dia))
    {
        $mesg .= "Ultimo dia del informe incorrecto";
        return(-23);
    }
    if ($mes_informe != $mes)
    {
        $mesg .= "El mes del informe es incorrecto";
        return(-24);
    }
	$mesg .= "OK, documento verificado";
   	return 1;
}


?>

