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
 * semanales en XML. Emplean los files XSL para realizar las comprobaciones
 * necesarias
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */


// Devuelve el numero de dias de un mes teniendo en cuenta
// los años bisiestos

function DaysMonth ($month, $year)
{
    $d_mes = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    if (($month < 1) || ($month > 12)) return 0;
    if ($month != 2) return $d_mes[$month - 1];
    return (checkdate($month, 29, $year)) ? 29 : 28;
}



// Devuelve el numero de mes en el calendario

function Month2Number ($name)
{
    $month = array(
      		_("enero") => 1,
	  	_("febrero") => 2,
		_("marzo") => 3,
		_("abril") => 4,
		_("mayo") => 5,
		_("junio") => 6,
		_("julio") => 7,
		_("agosto") => 8,
		_("septiembre") => 9,
		_("octubre") => 10,
		_("noviembre") => 11,
		_("diciembre") => 12
    );

    $name = trim($name);
    $name = strtolower($name);
    if (array_key_exists($name, $month)) return $month[$name];
    else return 0;
}



// Devuelve el mes asociado al numero que recibe como argumento

function Number2Month ($number)
{
    $month = array(
		1 => _("enero"),
	  	2 => _("febrero"),
		3 => _("marzo"),
		4 => _("abril"),
		5 => _("mayo"),
		6 => _("junio"),
		7 => _("julio"),
		8 => _("agosto"),
		9 => _("septiembre"),
		10 => _("octubre"),
		11 => _("noviembre"),
		12 => _("diciembre")
    );
    return $month[$number];
}



// Determina si un nombre de un informe XML en formato "informesemanal-<usuario>-
// -<anoini>-<mesini>-<diaini>-<anofin>-<mesfin>-<diafin>.xml. Para ello comprueba
// que el informe abarca una semana y que el usuario existe

function ValidName ($users, $file, &$ret)
{
    $ret["file"] = $file;

    if (ereg("^informesemanal-([a-z_]+)-([0-9]{4})-([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{2})\.xml$", $file, $regs))
    {
      	if (date("w", mktime(0, 0, 0, $regs[3], $regs[4], $regs[2])) != 1)
       	{
      	    $ret["error"] = _("El nombre de file no se corresponde a un informe semanal, no existe ninguna semana que comienze en la fecha indicada.");
	    return (-3);
	}
	if (date("w", mktime(0, 0, 0, $regs[6], $regs[7], $regs[5])) != 0)
	{
	    $ret["error"] = _("El nombre de file no se corresponde a un informe semanal, no existe ninguna semana que finalize en la fecha indicada.");
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
            $ret["error"] = _("Propietario del informe no reconocido.");
            return 0;
        }
    }
    else
    {
       	$ret["error"] = _("Nombre con formato incorrecto.");
       	return (-1);
    }
}



// Comprueba si la informacion de un informe XML es coherente.

function ValidateXMLReport ($file, $fields, &$msg)
{
    unset($array_rsl);
    $ret = 0;
    $result = exec("xsltproc --novalid -param Ianoini $fields[Ianoini] ".
    		      "-param Imesini $fields[Imesini] -param Idiaini $fields[Idiaini] ".
                      "-param Ianofin $fields[Ianofin] -param Imesfin $fields[Imesfin] ".
                      "-param Idiafin $fields[Idiafin] include/xmlcoherente.xsl $file", $array_rsl, $ret);

    $result = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret._(". (Mas informacion en el manual de 'xsltproc').");
        return (-1);
    }
    else
    {
      	if ($result != 'OK')
        {
            $msg = "Error ".$result;
            return 0;
        }
        else
        {
            $msg = "OK";
            return 1;
        }
    }
}



function ValidateXML ($file, $dir, $session_logins, &$msg)
{

    //$xsltproc = xslt_create();
    //xslt_set_encoding($xsltproc, "ISO-8859-1");
    //...
    $ret = ValidName($session_logins, $file, $fields);
    if ($ret != 1)
    {
        $msg = _("Informe con formato de nombre erroneo: ").$file.".";
        return (-2);
    }
    unset($array_rsl);
    $result = exec("xsltproc --novalid -param Ianoini $fields[Ianoini] ".
    		      "-param Imesini $fields[Imesini] -param Idiaini $fields[Idiaini] ".
                      "-param Ianofin $fields[Ianofin] -param Imesfin $fields[Imesfin] ".
                      "-param Idiafin $fields[Idiafin] include/xmlcoherente.xsl ".$dir.$file, $array_rsl, $ret);

    $result = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret._(". (Mas informacion en el manual de 'xsltproc').");
        return (-1);
    }
    else
    {
      	if ($result != 'OK')
        {
            $msg = "Error ".$result;
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

function SaveXML2SQL ($cnx, $session_uid, $session_logins, $dir, $file, &$msg)
{
    $ret = ValidName($session_logins, $file, $fields);
    if ($ret != 1)
    {
        $msg = _("Informe con nombre erroneo");
        return (-2);
    }
    unset($array_rsl);
    $result = exec("xsltproc --novalid -param Ianoini $fields[Ianoini] ".
		      "-param Imesini $fields[Imesini] -param Idiaini $fields[Idiaini] ".
		      "-param Ianofin $fields[Ianofin] -param Imesfin $fields[Imesfin] ".
		      "-param Idiafin $fields[Idiafin] --stringparam Iusuario $fields[usuario] ".
                      "include/xml2php.xsl ".$dir.$file, $array_rsl, $ret);

    $result = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret._(". (Mas informacion en el manual de 'xsltproc').");
        return 0;
    }
    else
    {
        unset($error);

        // peligrorrrr ;-)

        eval($result);

        if (!empty($error))
        {
            $msg = "BD Error ".$error;
            return (-1);
        }
        return 1;
    }
}



// Obtiene todos los informes bien nombrados y del propio usuario de un directorio

function DirValidReports ($session_uid, $session_grupos, $session_logins, $dir, &$valids)
{
    $valid_reports = array();
    $fields = array();
    $valids = 0;


    $dh = @opendir($dir);
    while ($entry = @readdir($dh))
    {
    	if ((($entry != ".") && ($entry != "..")) &&
        (NombreValido($session_logins, $entry, $fields) == 1))
    	{
     	    if (in_array("informesadm", (array)$session_grupos))
    	    {
            	$valids++;
            	$valid_reports[] = $entry;
            }
            else
            {
	    	if ($session_uid == $fields["usuario"])
            	{
             	    $valids++;
	            $valid_reports[] = $entry;
            	}
            }
    	}
    }
    @closedir($dh);
    @usort($valid_reports, 'strnatcmp');

    return $valid_reports;
}



// Crea un file temporal

function fileTemp ($dir, $prefix, $postfix)
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



// Valida un file XML contra su DTD

function ValidateDTD ($file, &$msg)
{
    //$error = array();

    // Regeneramos el file reglas.dtd a partir de reglas.dtd.php
    update_dtd();

    if (!(is_file($file) && is_readable($file)))
    {
      	$msg .= _("Imposible acceder al file");
      	return(-1);
    }

    /*
    $dom = xmldocfile($file);
    //$dom = @xmldocfile($file, DOMXML_LOAD_VALIDATING, $error);
    //$dom = domxml_open_file($file, DOMXML_LOAD_PARSING, $error);
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
echo "file LEIDO";

    unset($data);
    $file = @fopen(EscapeShellCmd($file), "r+");
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
echo "file ESCRITO";
*/
    $result = exec("cat ".EscapeShellCmd($file)." | rxp -x -s -V" , $ar, $ret);
    if ($ret == 0)
    {
    	$msg .= _("Informe validado");
        return 1;
    }
    elseif ($ret == 1)
    {
    	$msg .= _("Mal formado");
        return (-2);
    }
    elseif ($ret == 2)
    {
    	$msg .= ("Doc ! DTD");
        return (-3);
    }
    else
    {
    	$msg .= _("Error con rxp ...");
        return (-4);
    }
}



// Comprueba si la informacion que contiene un file XML es coherente, es
// decir, comprueba que el XML abarca 2 meses consecutivos, que no solapen
// tareas, etc

function ValidateXMLphp ($doc, $year, $month, $day_ini, $day_end, &$msg)
{
    $sections = $doc->get_elements_by_tagname("dedicacion");

    if (count($sections) > 2)
    {
       $msg .= _("El documento XML abarca varios meses. ¡Es semanal!");
       return(-30);
    }
    foreach($sections as $section)
    {
        $days = array();

        $worked_days = $section->child_nodes();
        foreach ($worked_days as $day)
        {
            if ($day->node_name() != "dedicacionDiaria") continue;
            $hours_day = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);

            $worked_tasks = $day->child_nodes();
            foreach ($worked_tasks as $hours)
            {
                if ($hours->node_name() != "tarea") continue;

                $hour_init = intval($hours->get_attribute("inicio"));
                $hora_end = intval($hours->get_attribute("fin"));

                if (($hour_end - $hour_init) < 1)
                {
                    $msg .= _("dia ").$day->get_attribute("dia")._(", tarea con horario erroneo ");
                    $msg .= _("(hora fin anterior a hora de inicio)");
		    
                    return(-10);
                }
                $h_ini = intval($hour_init/100);
                if (($h_ini < 0) || ($h_ini > 23))
                {
                    $msg .= _("dia ").$day->get_attribute("dia").
                    _(", tarea con horario inicial erroneo");	
                    return(-11);
                }
                $m_ini = intval($hour_init%100);
                if (($m_ini < 0) || ($m_ini > 59))
                {
                    $msg .= _("dia ").$day->get_attribute("dia").
                    _(", tarea con horario inicial (minutos) erroneo");
                    return(-12);
                }
                $h_end = intval($hour_end/100);
                if (($h_end < 0) || ($h_end > 23))
                {
                    $msg .= _("dia ").$day->get_attribute("dia").
                    _(", tarea con horario final erroneo");
                    return(-13);
                }
                $m_end = intval($hour_end%100);
                if (($m_end < 0) || ($m_end > 59))
                {
                    $msg .= _("dia ").$day->get_attribute("dia").
                    _(", tarea con horario final (minutos) erroneo");
                    return(-14);
                }
                for ($i = $h_ini; $i <= $h_end; $i++)
                {
                    if ($i == $h_ini)
                    {
                        if ($h_ini == $h_end) $hours_day[$i] += $m_end - $m_ini;
                        else $hours_day[$i] += 60 - $m_ini;
                    }
                    elseif ($i == $h_end) $hours_day[$i] += $m_end;
                    else $hours_day[$i] += 60;

                    if ($hours_day[$i] > 60)
                    {
                        $msg .= _("la tarea del dia ").$day->get_attribute("dia").
                        _("(de ").$hour_init;
                        $msg .= _(" a ").$hour_end._("), se solapa");
                        return(-15);
                    }
                }
            }
            $days[] = intval($day->get_attribute("dia"));
        }
        $num_days = count($days);
        if ($num_days > 7)
        {
           $msg .= _("El informe abarca mas de 7 dias");
           return(-20);
        }
        sort($days);
        $minor_day = $days[0];
        $greater_day = $days[$num_days - 1];
        if ($minor_day < 1)
        {
           $msg .= _("Dia de inicio del informe incorrecto");
           return(-21);
        }
        $month_report = month_to_number($section->get_attribute("mes"));
        if ($day_ini > $minor_day)
        {
           $msg .= _("Primer dia del informe incorrecto");
           return(-22);
        }
	}
    $num_days_mes = days_of_month($month_report, $year);
    if (($greater_day > $num_days_mes) || ($day_end < $greater_day))
    {
        $msg .= _("Ultimo dia del informe incorrecto");
        return(-23);
    }
    if ($month_report != $month)
    {
        $msg .= _("El mes del informe es incorrecto");
        return(-24);
    }
	$msg .= _("OK, documento verificado");
   	return 1;
}


?>

