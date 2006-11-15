<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
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
 * Support functions needed for XML weekly report processing. They use
 * the XSL files to do all needed checks.
 *
 * José Riguera, <jriguera@igalia.com>
 *
 */


// Returns the number of days in a month taking into account leap years

function DaysMonth ($month, $year)
{
    $d_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    if (($month < 1) || ($month > 12)) return 0;
    if ($month != 2) return $d_month[$month - 1];
    return (checkdate($month, 29, $year)) ? 29 : 28;
}



// Returns month number in the calendar

function Month2Number ($name)
{
    $month = array(
 		"january" => 1,
  	"february" => 2,
		"march" => 3,
		"april" => 4,
		"may" => 5,
		"june" => 6,
		"july" => 7,
		"august" => 8,
		"september" => 9,
		"october" => 10,
		"november" => 11,
		"december" => 12
    );

    $name = trim($name);
    $name = strtolower($name);
    if (array_key_exists($name, $month)) return $month[$name];
    else return 0;
}



// Returns the month related to the number passed as argument

function Number2Month ($number)
{
    $month = array(
		1 => "january",
    2 => "february",
		3 => "march",
		4 => "april",
		5 => "may",
		6 => "june",
		7 => "july",
		8 => "august",
		9 => "september",
		10 => "october",
		11 => "november",
		12 => "december"
    );
    return $month[$number];
}


// Checks an XML report name in the format "weeklyreport-<user>-
// <startyear>-<startmonth>-<startday>-<endyear>-<endmonth>-<endday>.xml".
// For that, checks if the report matchs a week and that the user exists.

function ValidName ($users, $file, &$ret)
{
    $ret["file"] = $file;

    if (ereg("^weeklyreport-([a-z_]+)-([0-9]{4})-([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{2})\.xml$", $file, $regs))
    {
      	if (date("w", mktime(0, 0, 0, $regs[3], $regs[4], $regs[2])) != 1)
       	{
      	    $ret["error"] = _("Filename doesn't match a weekly report, there aren't a week starting at the specified date.");
	    return (-3);
	}
	if (date("w", mktime(0, 0, 0, $regs[6], $regs[7], $regs[5])) != 0)
	{
	    $ret["error"] = _("Filename doesn't match a weekly report, there aren't a week ending at the specified date.");
	    return (-2);
	}
        $ret["user"] = $regs[1];
        $ret["Iyearini"] = $regs[2];
        $ret["Imonthini"] = $regs[3];
        $ret["Idayini"] = $regs[4];
        $ret["Iyearend"] = $regs[5];
        $ret["Imonthend"] = $regs[6];
        $ret["Idayend"] = $regs[7];
        if (in_array($regs[1], (array)$users)) return 1;
        else
        {
            $ret["error"] = _("Unknown report owner.");
            return 0;
        }
    }
    else
    {
       	$ret["error"] = _("Incorrect name format.");
       	return (-1);
    }
}



// Checks if XML report info is coherent.

function ValidateXMLReport ($file, $fields, &$msg)
{
    unset($array_rsl);
    $ret = 0;
    $result = exec($cmd="xsltproc --novalid -param Iyearini $fields[Iyearini] ".
    		      "-param Imonthini $fields[Imonthini] -param Idayini $fields[Idayini] ".
                      "-param Iyearend $fields[Iyearend] -param Imonthend $fields[Imonthend] ".
                      "-param Idayend $fields[Idayend] include/xmlcoherent.xsl $file", $array_rsl, $ret);

    $result = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret._(". (More info in the 'xsltproc' manual).");
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
    //xslt_set_encoding($xsltproc, "UTF-8");
    //...
    $ret = ValidName($session_logins, $file, $fields);
    if ($ret != 1)
    {
        $msg = _("Report with wrong name format: ").$file.".";
        return (-2);
    }
    unset($array_rsl);
    $result = exec($cmd="xsltproc --novalid -param Iyearini $fields[Iyearini] ".
    		      "-param Imonthini $fields[Imonthini] -param Idayini $fields[Idayini] ".
                      "-param Iyearend $fields[Iyearend] -param Imonthend $fields[Imonthend] ".
                      "-param Idayend $fields[Idayend] include/xmlcoherent.xsl ".$dir.$file, $array_rsl, $ret);

    $result = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret._(". (More info in the 'xsltproc' manual).");
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


// Insert XML report data into DB, using XSLT and stylesheets

function SaveXML2SQL ($cnx, $session_uid, $session_logins, $dir, $file, &$msg)
{
    $ret = ValidName($session_logins, $file, $fields);
    if ($ret != 1)
    {
        $msg = _("Report with wrong name");
        return (-2);
    }
    unset($array_rsl);
    $result = exec($cmd="xsltproc --novalid -param Iyearini $fields[Iyearini] ".
		      "-param Imonthini $fields[Imonthini] -param Idayini $fields[Idayini] ".
		      "-param Iyearend $fields[Iyearend] -param Imonthend $fields[Imonthend] ".
		      "-param Idayend $fields[Idayend] --stringparam Iuser $fields[user] ".
                      "include/xml2php.xsl ".$dir.$file, $array_rsl, $ret);

    $result = implode("\n", $array_rsl);
    if ($ret != 0)
    {
        $msg = "XSLT Error: ".$ret._(". (More info in the 'xsltproc' manual).");
        return 0;
    }
    else
    {
        unset($error);

        // dangerrrr ;-)

        eval($result);

        if (!empty($error))
        {
            $msg = "DB Error ".$error;
            return (-1);
        }
        return 1;
    }
}


// Get all the reports well named and belonging to the user of a directory

function DirValidReports ($session_uid, $session_grupos, $session_logins, $dir, &$valids)
{
    $valid_reports = array();
    $fields = array();
    $valids = 0;


    $dh = @opendir($dir);
    while ($entry = @readdir($dh))
    {
    	if ((($entry != ".") && ($entry != "..")) &&
        (ValidName($session_logins, $entry, $fields) == 1))
    	{
     	    if (in_array($admin_group_name, (array)$session_grupos))
    	    {
            	$valids++;
            	$valid_reports[] = $entry;
            }
            else
            {
	    	if ($session_uid == $fields["user"])
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



// Create a temporary file

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



// Validate an XML file against its DTD

function ValidateDTD ($file, &$msg)
{
    //$error = array();

    // Regenerate rules.dtd file from rules.dtd.php
    update_dtd();

    if (!(is_file($file) && is_readable($file)))
    {
      	$msg .= _("File access impossible");
      	return(-1);
    }

    /*
    $dom = xmldocfile($file);
    //$dom = @xmldocfile($file, DOMXML_LOAD_VALIDATING, $error);
    //$dom = domxml_open_file($file, DOMXML_LOAD_PARSING, $error);
    if (!is_object($dom))
    {
      	$msg = $msg."The XML document is bad formed.";
      	//print_r($error);
      	return(-2);
    }
    //if (!domxml_doc_validate($dom, $error))

    $dom->validate($erro);
    if (!empty($erro))
    {
       	$msg = $msg."Document doesn't satisfy DTD specifications.";
      	return(-3);
    }
    return 1;
    */

    $ar = array();
    $ret = 0;
/*
echo "file READ";

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
//    	$extract = ereg_replace("^<!DOCTYPE[[:space:]]+weeklyDedication[[:space:]]+SYSTEM[[:space:]]+([[:alpha:]\"\'\./]){4,}>",
//    				"<!DOCTYPE weeklyDedication SYSTEM \"rules.dtd\">", $data);
/*    	$extract = ereg_replace("^<!DOCTYPE weeklyDedication SYSTEM ([[:alpha:]\"\'\./]){4,}>",
    				"<!DOCTYPE weeklyDedication SYSTEM \"rules.dtd\">", $data);
        fwrite($file, $extract);
        fclose($file);
    }
echo "file WRITTEN";
*/
    $result = exec($cmd="cat ".EscapeShellCmd($file)." | rxp -x -s -V" , $ar, $ret);
    if ($ret == 0)
    {
    	$msg .= _("Report validated");
        return 1;
    }
    elseif ($ret == 1)
    {
    	$msg .= _("Bad formed");
        return (-2);
    }
    elseif ($ret == 2)
    {
    	$msg .= ("Doc ! DTD");
        return (-3);
    }
    else
    {
    	$msg .= _("Error using rxp ...");
        return (-4);
    }
}


// Check if the info contained in the XML file is coherent, that is
// check if XML ranges 2 consecutive months, that tasks aren't 
// overlapped, etc... 

function ValidateXMLphp ($doc, $year, $month, $day_ini, $day_end, &$msg)
{
    $sections = $doc->get_elements_by_tagname("dedication");

    if (count($sections) > 2)
    {
       $msg .= _("XML document ranges some months. Is weekly!");
       return(-30);
    }
    foreach($sections as $section)
    {
        $days = array();

        $worked_days = $section->child_nodes();
        foreach ($worked_days as $day)
        {
            if ($day->node_name() != "dailyDedication") continue;
            $hours_day = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);

            $worked_tasks = $day->child_nodes();
            foreach ($worked_tasks as $hours)
            {
                if ($hours->node_name() != "task") continue;

                $hour_init = intval($hours->get_attribute("start"));
                $hora_end = intval($hours->get_attribute("end"));

                if (($hour_end - $hour_init) < 1)
                {
                    $msg .= _("day ").$day->get_attribute("day")._(", task with wrong timetable ");
                    $msg .= _("(end hour previous to start hour)");
		    
                    return(-10);
                }
                $h_ini = intval($hour_init/100);
                if (($h_ini < 0) || ($h_ini > 23))
                {
                    $msg .= _("day ").$day->get_attribute("day").
                    _(", task with wrong start time (hour)");	
                    return(-11);
                }
                $m_ini = intval($hour_init%100);
                if (($m_ini < 0) || ($m_ini > 59))
                {
                    $msg .= _("day ").$day->get_attribute("day").
                    _(", task with wrong start time (minutes)");
                    return(-12);
                }
                $h_end = intval($hour_end/100);
                if (($h_end < 0) || ($h_end > 23))
                {
                    $msg .= _("day ").$day->get_attribute("day").
                    _(", task with wrong end time (hour)");
                    return(-13);
                }
                $m_end = intval($hour_end%100);
                if (($m_end < 0) || ($m_end > 59))
                {
                    $msg .= _("day ").$day->get_attribute("day").
                    _(", task with wrong end time (minutes)");
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
                        $msg .= _("task at day ").$day->get_attribute("day").
                        _("(from ").$hour_init;
                        $msg .= _(" to ").$hour_end._("), overlaps");
                        return(-15);
                    }
                }
            }
            $days[] = intval($day->get_attribute("day"));
        }
        $num_days = count($days);
        if ($num_days > 7)
        {
           $msg .= _("Report ranges more than 7 days");
           return(-20);
        }
        sort($days);
        $minor_day = $days[0];
        $greater_day = $days[$num_days - 1];
        if ($minor_day < 1)
        {
           $msg .= _("Incorrect report first day");
           return(-21);
        }
        $month_report = month_to_number($section->get_attribute("month"));
        if ($day_ini > $minor_day)
        {
           $msg .= _("Incorrect report first day");
           return(-22);
        }
	}
    $num_days_mes = days_of_month($month_report, $year);
    if (($greater_day > $num_days_mes) || ($day_end < $greater_day))
    {
        $msg .= _("Incorrect report last day");
        return(-23);
    }
    if ($month_report != $month)
    {
        $msg .= _("Incorrect report month");
        return(-24);
    }
	$msg .= _("OK, document verified");
   	return 1;
}


?>
