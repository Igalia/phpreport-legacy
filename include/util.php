<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2009 Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Andrés Maneiro Boga <amaneiro@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  Jacobo Aragunde Pérez <jaragunde@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
//  María Fernández Rodríguez <maria.fdez.rguez@gmail.com>
//  Mario Sánchez Prada <msanchez@igalia.com>
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

// USEFUL FUNCTION LIBRARY

// NOTE: Uncoment the ones you need. At the end of the project,
// unneeded ones will be deleted

require_once("include/config.php");

function msg_ok($msg) {
 echo("<p class=\"msg_ok\"><b>$msg</b></p>");
}

function msg_fail($msg) {
 echo("<p class=\"msg_fail\"><b>$msg</b></p>");
}
function msg_warning($msg){
 echo("<p class=\"msg_warning\"><b>$msg</b></p>");
}

// Get the greatest key of a monodimensional array
/*
function max_key($array) {
 $max=-1;
 foreach (array_keys($array) as $i)
  if ($i>$max) $max=$i;
 return $max;
}
*/

// Transform a MySQL SET type (value string delimited by commas)
// into a PHP ARRAY, containing each of the elements of the set.
function set_to_array($set) {
 if (empty($set)) return array();
 else return explode(",",$set);
}

function indexOf($needle, $haystack) {                // conversion of JavaScripts most awesome
        for ($i=0;$i<count($haystack);$i++) {         // indexOf function.  Searches an array for
                if ($haystack[$i] == $needle) {       // a value and returns the index of the *first*
                        return $i;                    // occurance
                }
        }
        return -1;
}
/*
function checked($checkbox) {
 if ($checkbox==1) return "CHECKED";
 else return "";
}
*/

// Deletes i-th element of a monodimensional array.
// i is numeric. All the k elements, being k>i, will be shifted
// to the k-1 position for the purpose of avoid "holes" in the
// i numbering of the array.
// Example: A[1] A[2] A[3] A[4] --> A[1] A[3] A[4] ¡¡A[2] hole mustn't remain!!
// Correct: A[1] A[2] A[3] A[4] --> A[1] A[2] A[3] (A[2] y A[3] are the old
//           A[3] and A[4].
function del_elements_shifting($array, $i) {
 array_splice($array,$i,1);
 return(array_values($array));
}

// Tests if a date is valid DD/MM/YYYY or D/M/YYYY
function validate_date_web($web_date) {
 /* check max length for each field (regexp sometimes doesn't work well) */
 $arrayDMA = date_web_to_arrayDMA($web_date);
 if ((strlen($arrayDMA[0]) > 2) || (strlen($arrayDMA[1]) > 2) || (strlen($arrayDMA[2]) > 4)) {
   return false;
 }
 /* if valid length, check regular expression */
 if(preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4,4}/",$web_date)){
   if(($arrayDMA[1]<=12)&&($arrayDMA[1]>=1) && ($arrayDMA[0]>=1) &&($arrayDMA[0]<=31))
     return TRUE;
   else
     return FALSE;
 }
 else
   return FALSE;
}

// Tests if a time is valid HH:MM or H:MM
function validate_time_web($web_time) {
 if (preg_match("/[0-9]{1,2}:[0-9]{2,2}/",$web_time))
   return TRUE;
 else
   return preg_match("/[0-9]{4,4}/",$web_time);
}

// Convert a date from "spanish" format (DD/MM/YYYY) to a (DD,MM,YYYY) array
function date_web_to_arrayDMA($web_date) {
 return explode("/",$web_date);
}

// Convert from a (DD,MM,YYYY) array to a "spanish" (DD/MM/YYYY) date
function date_arrayDMA_to_web($date_arrayDMA) {
 return implode("/",$date_arrayDMA);
}

// Convert from "spanish" (DD/MM/YYYY) date to a "PostgreSQL" (YYYY-MM-DD) date
function date_web_to_sql($web_date) {
 $dateDMA=explode("/",$web_date);
 if (strlen($dateDMA[0])==1) $dateDMA[0]="0".$dateDMA[0];
 if (strlen($dateDMA[1])==1) $dateDMA[1]="0".$dateDMA[1];
 return($dateDMA[2]."-".$dateDMA[1]."-".$dateDMA[0]);
}

// Convert from "PostgreSQL" (YYYY-MM-DD) date to "spanish" (DD/MM/YYYY)
function date_sql_to_web($date_sql) {
 $dateAMD=explode("-",$date_sql);
 return($dateAMD[2]."/".$dateAMD[1]."/".$dateAMD[0]);
}

// Convert from "PostgreSQL" (YYYY-MM-DD) date to timestamp
function date_sql_to_ts($date_sql) {
  $date_array = explode("-",$date_sql);
  return mktime(0, 0, 0, intval($date_array[1]), intval($date_array[2]), intval($date_array[0]));
}

// Convert from timestamp to "PostgreSQL" (YYYY-MM-DD) date
function date_ts_to_sql($date_ts) {
  $date=getdate($date_ts);
  return $date["year"]."-".$date["mon"]."-".$date["mday"];
}

// Returns NULL if there is any null date
function date_web_to_quoted_sql($web_date) {
  if($web_date=="") return "NULL";
  else {
   $dateDMA=explode("/",$web_date);
   if (strlen($dateDMA[0])==1) $dateDMA[0]="0".$dateDMA[0];
   if (strlen($dateDMA[1])==1) $dateDMA[1]="0".$dateDMA[1];
   return("'".$dateDMA[2]."-".$dateDMA[1]."-".$dateDMA[0]."'");
  }
}

// Compare init date from two arrays
function cmp_init_dates ($a, $b) {
    if ($a["init"] == $b["init"]) return 0;
    return ($a["init"] < $b["init"]) ? -1 : 1;
}

// Compare dates in web format
function cmp_web_dates ($date_a, $date_b) {
  $arrayDMA_a = date_web_to_arrayDMA($date_a);
  $arrayDMA_b = date_web_to_arrayDMA($date_b);
  $timestamp_a = mktime(0, 0, 0, $arrayDMA_a[1], $arrayDMA_a[0], $arrayDMA_a[2]);
  $timestamp_b = mktime(0, 0, 0, $arrayDMA_b[1], $arrayDMA_b[0], $arrayDMA_b[2]);
  if ($timestamp_a == $timestamp_b) return 0;
  return ($timestamp_a < $timestamp_b)? -1 : 1;
}

// Convert "spanish" time (HH:MM) to "PostgreSQL" (total minutes)
function hour_web_to_sql($web_hour) {
 if(strstr($web_hour,":")) {
   $hourHM=explode(":",$web_hour);
   return($hourHM[0]*60+$hourHM[1]);
 }
 else
   return(substr($web_hour,0,-2)*60+substr($web_hour,2));
}

// Convert "PostgreSQL" time (total minutes) to "spanish" (HH:MM)
function hour_sql_to_web($hour_sql) {
 $hourHM=array(floor($hour_sql/60),$hour_sql%60);
 return(sprintf("%02d:%02d",$hourHM[0],$hourHM[1]));
}

// Convert a "MySQL" datetime (YYYY-MM-DD HH:MM:SS) to "spanish" (DD/MM/YYYY HH:MM)
/*
function datetime_sql_to_web($date_sql) {
 $dateDT=explode(" ",$date_sql);
 return(date_sql_to_web($dateDH[0])." "
  .hour_sql_to_web($dateDH[1]));
}
*/

// Convert a number to currency format
/*
function money_to_web($currency) {
 return(sprintf("%01.2f",$currency));
}
*/

// Generate OPTION fields for forms, checking the specified field as SELECTED 
function array_to_option($options, $selected, $values="") {
 $result="";
 $i=0;
 foreach((array)$options as $option) {
  if (!empty($values)) {
   $value="VALUE=\"$values[$i]\"";
   if ($values[$i]==$selected) $txt_selected="SELECTED";
   else $txt_selected="";
  } else {
   $value="";
   if ($option==$selected) $txt_selected="SELECTED";
   else $txt_selected="";
  }
  $result=$result."<OPTION $txt_selected $value>".$option."</OPTION>";
  $i++;
 }
 return $result;
}

// Join the content of two arrays element by element
// Ex: [a b c] [x y z] --> [ax by cz]
/*
function array_join($a1, $a2) {
 $result=$a1;
 foreach (array_keys((array)$a2) as $k)
  $result[$k]=$a1[$k].$a2[$k];
 return $result;
}
*/

// Convert array values to a boolean array indexed by those values.
// Ex: [a b c] = [0->a, 1->b, 2->c] --> [a->TRUE, b->TRUE, c->TRUE]
// By this way, doing array_search($element,$array) will no more bee needed,
// because now we can use this easier comparation: ($array[$element]==true)
/*
function array_to_boolean_array($array) {
 $result=array();
 foreach (array_values($array) as $i)
  $result[$i]=true;
 return $result;
}
*/

// Extracts a portion of an array
// Ex: sub_array([a->w, b->x, c->y, d->z], [a, c]) = [a->w, c->y]
/*
function sub_array($array, $claves) {
 $result=array();
 foreach (array_values($claves) as $i)
  $result[$i]=$array[$i];
 return $result;
}
*/

// Returns passed date moved as many months forward or backward as indicated 
// by the parameters. Date must be in DD/MM/YYYY format.
function day_month_moved($day, $shift) {
 $arrayDMA=date_web_to_arrayDMA($day);
 $tmp=getdate(mktime(0,0,0,$arrayDMA[1]+$shift,$arrayDMA[0],$arrayDMA[2]));
 return(date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

// Returns passed date moved as many years forward or backward as indicated 
// by the parameters. Date must be in DD/MM/YYYY format.
function day_year_moved($day, $shift) {
 $arrayDMA=date_web_to_arrayDMA($day);
 $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]+$shift));
 return(date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

// Returns passed date moved as many days forward or backward as indicated 
// by the parameters. Date must be in DD/MM/YYYY format.
function day_day_moved($day, $shift){
  $arrayDMA=date_web_to_arrayDMA($day);
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0]+$shift,$arrayDMA[2]));
  return(date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

// Given a date, it returns the date corresponding to previous day
// (useful for "copy previous day report" action)
function day_yesterday($day){
  return day_day_moved($day,-1);
}

// Create an array with next month calendar, for a given date.
// If date is void, the current day is used.
function make_calendar($day) {
 if (!empty($day)) {
  $arrayDMA=date_web_to_arrayDMA($day);
  $today=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
 } else {
  $today=getdate(time());
  $today=getdate(mktime(0,0,0,$today["mon"],$today["mday"],$today["year"]));
 }

 $day=date_arrayDMA_to_web(array($today["mday"],$today["mon"],$today["year"]));
 $arrayDMA=date_web_to_arrayDMA($day);

 $calendar=array();

 // Computing of previous month grey days

 $dt=getdate(mktime(0,0,0,$today["mon"],1,$today["year"]));
 $d=getdate(mktime(0,0,0,$today["mon"],
  1-((($dt["wday"]+6)%7)+1),$today["year"]));
 $s=1; // Semana
 for ($i=1;$i<((($dt["wday"]+6)%7)+1);$i++) {
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1]-1,$d["mday"]+$i,$arrayDMA[2]));
  $calendar[$s][$i]=array($d["mday"]+$i,"G",
   date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"]))
  );
 }

 // $calendar2=$calendar2;
 // Computing of black days (of this month)
 for ($k=0;;$k++,$i++) {
  $t=mktime(0,0,0,$today["mon"],$k+1,$today["year"]);
  $dt=getdate($t);
  if (($dt["mon"])==($today["mon"])) {
   if ($dt==$today) $letter_colour="H";
   else $letter_colour="N";
   $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$dt["mday"],$arrayDMA[2]));
   $calendar[$s][$i]=array($dt["mday"],$letter_colour,
    date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
   if ($i==7) {
    $i=0;
    $s++;
   }
  } else break;
 }
 // Computing of next month grey days
 for ($j=1;($i>1)&&($i<=7);$i++) {
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1]+1,$j,$arrayDMA[2]));
  $calendar[$s][$i]=array($j,"G",
    date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
  $j++;
 }
 return($calendar);
}


// Needed for eval_html
function eval_buffer($string) {
    ob_start();
    eval("$string[2];");
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
}
 
// Needed for eval_html
function eval_print_buffer($string) {
    ob_start();
    eval("print $string[2];");
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
}
 
// Evals a PHP and returns the result as a string
function eval_html($string) {
    $string = preg_replace_callback("/(<\?=)(.*?)\?>/si",
                                    "eval_print_buffer",$string);
    return preg_replace_callback("/(<\?php|<\?)(.*?)\?>/si",
                                  "eval_buffer",$string);
}

// Update rules.dtd with config.php configuration
function update_dtd() {

   global $table_type, $table_name, $table_customer, $table_ttype;
   ob_start();
   require("rules.dtd.php");
   $dtd=ob_get_contents();
   ob_end_clean();
   $f=fopen("rules.dtd","w");
   fwrite($f,$dtd);
   fclose($f);
}
 
// Convert a checkbox value to a boolean variable for PostgreSQL
function checkbox_to_sql($i) {
 if (!empty($i)) return "t";
 else return "f";
}

// Convert a boolean PostgreSQL variable (t/f) to a "CHECKED" string
function sql_to_checkbox($i) {
 if ($i=="t") return " CHECKED ";
 else return "";
}

// Compute the minutes that a worker has worked during current week
function worked_minutes_this_week($cnx,$uid,$day) {
 $hours="---";
 $result=@pg_exec($cnx,$query="
SELECT uid, SUM(_end-init) - 60*COALESCE((
  SELECT SUM(hours)
  FROM compensation
  WHERE uid=task.uid AND uid='$uid'
   AND init>=(timestamp '$day' -
    ((int2(date_part('dow',timestamp '$day')+7-1) % 7)||' days')::interval)::date
   AND _end<=(timestamp '$day'-
    ((int2(date_part('dow',timestamp '$day')+7-1) % 7)-6||' days')::interval)::date
 ),0) AS add_hours
FROM task
WHERE _date>=(timestamp '$day' -
 ((int2(date_part('dow',timestamp '$day')+7-1) % 7)||' days')::interval)::date
 AND _date<= (timestamp '$day' -
 ((int2(date_part('dow',timestamp '$day')+7-1) % 7)-6||' days')::interval)::date
 AND uid='$uid'
GROUP BY uid");
 if ($row=@pg_fetch_row($result)) $minutes=$row[1];
 @pg_freeresult($result);
 return $minutes;
}

// Compute the minutes that a worker has worked during current week
// between init_date and _end_date
function worked_minutes_this_week_during_project($cnx,$uid,$day,$init_date,$_end_date,$project_name) {
 $hours="---";
 $result=@pg_exec($cnx,$query="
SELECT uid, SUM(_end-init) - 60*COALESCE((
  SELECT SUM(hours)
  FROM compensation
  WHERE uid=task.uid AND uid='$uid'
   AND init>=(timestamp '$day' -
    ((int2(date_part('dow',timestamp '$day')+7-1) % 7)||' days')::interval)::date
 AND init>='$init_date'
 AND _end<='$_end_date'
   AND _end<=(timestamp '$day'-
    ((int2(date_part('dow',timestamp '$day')+7-1) % 7)-6||' days')::interval)::date
 ),0) AS add_hours
FROM task
WHERE _date>=(timestamp '$day' -
 ((int2(date_part('dow',timestamp '$day')+7-1) % 7)||' days')::interval)::date
 AND _date>='$init_date'
 AND _date<= (timestamp '$day' -
 ((int2(date_part('dow',timestamp '$day')+7-1) % 7)-6||' days')::interval)::date
 AND _date<='$_end_date' 
 AND name='$project_name'
 AND uid='$uid'

GROUP BY uid");
 if ($row=@pg_fetch_row($result)) $minutes=$row[1];
 @pg_freeresult($result);
 return $minutes;
}


// Compute the minutes that a worker has worked during current week

function worked_minutes_this_month($cnx,$uid,$day) {
 
 $time_stamp= strtotime($day);
 $days_in_month= date('t',$time_stamp);
 $year=date('Y',$time_stamp);
 $day=date('d',$time_stamp);
 $month=date('m',$time_stamp);
 $init_month= "01/".$month."/".$year;
 $end_month= $days_in_month."/".$month."/".$year;
 $init_month=date_web_to_sql($init_month);
 $end_month= date_web_to_sql($end_month);
 
 $result=@pg_exec($cnx,$query="
 SELECT uid, SUM(_end-init) - 60*COALESCE((
  SELECT SUM(hours)
  FROM compensation
  WHERE uid=task.uid AND uid='$uid'
   AND init>='$init_month' 
   AND _end<='$end_month'
 ),0) AS add_hours
FROM task
WHERE _date>='$init_month'
 AND _date<= '$end_month'
 AND uid='$uid'
GROUP BY uid");


 if ($row=@pg_fetch_row($result)) $minutes=$row[1];
 @pg_freeresult($result);
 
 return $minutes;
}
function make_calendar2($holidays,$day) {
$empty=false;

if (!empty($day)) {
  $arrayDMA=date_web_to_arrayDMA($day);
  $day=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
 } else {
  $empty=true;
  $day=getdate(time());
  $day=getdate(mktime(0,0,0,$day["mon"],$day["mday"],$day["year"]));
 }

 $calendar=array();

 // Computing of previous month grey days
 $dt=getdate(mktime(0,0,0,$day["mon"],1,$day["year"]));
 $d=getdate(mktime(0,0,0,$day["mon"],
  1-((($dt["wday"]+6)%7)+1),$day["year"]));
 $s=1; // Semana
 for ($i=1;$i<((($dt["wday"]+6)%7)+1);$i++) {
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1]-1,$d["mday"]+$i,$arrayDMA[2]));
  $calendar[$s][$i]=array($d["mday"]+$i,"G",
   date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"]))
  );
 }

 // Computing of black days (of this month)
 for ($k=0;;$k++,$i++) {
  $t=mktime(0,0,0,$day["mon"],$k+1,$day["year"]);
  $dt=getdate($t);
  if (($dt["mon"])==($day["mon"])) {  

   if ($dt==$day && !$empty)$letter_colour="A";
   else $letter_colour="N";
   
   if($dt["weekday"]=="Saturday"||$dt["weekday"]=="Sunday") $letter_colour="A"; else $letter_colour="N";
   $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$dt["mday"],$arrayDMA[2]));
if (!empty ($holidays)) {
foreach ($holidays as $d) {
if($tmp==$d&&!$empty)$letter_colour="A";
     else $letter_colour="N";
   if ($tmp!=$d){
     if($dt["weekday"]=="Saturday"||$dt["weekday"]=="Sunday") $letter_colour="A";
     $calendar[$s][$i]=array($dt["mday"],$letter_colour,
      date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
   }
   else {
    $calendar[$s][$i]=array($dt["mday"],"A",
    date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
    break;
   }
}
}

else {
  if($day["mday"]==$tmp["mday"]&&empty($holidays)){ 
   $calendar[$s][$i]=array($dt["mday"],$letter_colour,
      date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));}
  else { $calendar[$s][$i]=array($dt["mday"],$letter_colour,
      date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));}
  
}

   if ($i==7) {
    $i=0;
    $s++;
   }
  } else break;
 } 
 // Computing of next month grey days
 for ($j=1;($i>1)&&($i<=7);$i++) {
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1]+1,$j,$arrayDMA[2]));
  $calendar[$s][$i]=array($j,"G",
    date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
  $j++;
 }
 return($calendar);
}

// Compute the days passed since a reference date called "epoch"
// NOTE: For convenience reasons, the epoch ISN'T THE SAME as the standard
//       unix epoch.
function days_from_epoch($date) {
  // We use gmmktime() to be immune to daily saving time issues
  $val=date_sql_to_web($date);
  $arrayDMA=date_web_to_arrayDMA($val);
  $tm_date=gmmktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]);

  // 1970-01-05 is a better epoch day, because it's Monday and can be
  // used to compute the week day doing modulus
  $arrayDMA=array("5","1","1970");
  $tm_epoch=gmmktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]);

  return ($tm_date-$tm_epoch)/(60*60*24);
}

function num_weekend_days($init,$end) {
  // Let's call "days" to the number of days since "epoch" for a
  // Particular date.
  // So, floor(days/7)*2 is the number of *complete* weeks happened
  // if (days%7==5) a Saturday has also happened
  // if (days%7==6) a Saturday and a Sunday have also happened

  // Computing the number of weekend days happened since epoch for one date2 (both included)
  // and then, the number of weekend days happened since epoch for another date1 (both)
  // and substracting both, gives the number of weekend days happened from
  // date1 to date2 (both)

  // NOTE: -1 because we also include the start date
  // (init<=x<=end, INSTEAD OF init<x<=end)
  $days_epoch_to_init=days_from_epoch($init)-1;
  $days_epoch_to_end=days_from_epoch($end);

  $weekend_days_epoch_to_init=floor($days_epoch_to_init/7)*2;
  if ($days_epoch_to_init%7==5) $weekend_days_epoch_to_init+=1;
  else if ($days_epoch_to_init%7==6) $weekend_days_epoch_to_init+=2;

  $weekend_days_epoch_to_end=floor($days_epoch_to_end/7)*2;
  if ($days_epoch_to_end%7==5) $weekend_days_epoch_to_end+=1;
  else if ($days_epoch_to_end%7==6) $weekend_days_epoch_to_end+=2;

  $result = $weekend_days_epoch_to_end - $weekend_days_epoch_to_init;

  return $result;
}

function num_work_days($init,$end) {
  // Taking on account the things said in function num_weekend_days,
  // this is a function that works in a similar way to get the number
  // of *work* days (that means excluding weekends) between two dates.

  // NOTE: -1 because we also include the start date
  // (init<=x<=end, INSTEAD OF init<x<=end)
  $days_epoch_to_init=days_from_epoch($init)-1;
  $days_epoch_to_end=days_from_epoch($end);

  $weekend_days_epoch_to_init=floor($days_epoch_to_init/7)*2;
  if ($days_epoch_to_init%7==5) $weekend_days_epoch_to_init+=1;
  else if ($days_epoch_to_init%7==6) $weekend_days_epoch_to_init+=2;

  $weekend_days_epoch_to_end=floor($days_epoch_to_end/7)*2;
  if ($days_epoch_to_end%7==5) $weekend_days_epoch_to_end+=1;
  else if ($days_epoch_to_end%7==6) $weekend_days_epoch_to_end+=2;

  $result = $days_epoch_to_end - $days_epoch_to_init -
            ($weekend_days_epoch_to_end - $weekend_days_epoch_to_init);

  return $result;
}

function net_extra_hours($cnx,$init,$end,$uid=null) {
  if (empty($uid)) $uid_condition=" true";
  else $uid_condition=" uid='$uid'";
  $worked_hours=@pg_exec($cnx,$query="SELECT uid, SUM( _end - init ) / 60.0 AS total_hours FROM task 
        WHERE ((_date >= '$init' AND _date <= '$end' AND ".$uid_condition.")) 
        GROUP BY uid ORDER BY uid ASC")
  or die($die);

  $worked_hours_consult=array();
  for ($i=0;$row=@pg_fetch_array($worked_hours,$i,PGSQL_ASSOC);$i++) {
    $worked_hours_consult[$row["uid"]]["total_hours"]=$row["total_hours"];
  }
  @pg_freeresult($worked_hours);

  // Get hired intervals, but they can start before the init date or end after end date, so they
  // have to be clipped.
  $hired_intervals=@pg_exec($cnx,$query="SELECT uid,journey,init,_end,city FROM periods 
        WHERE (_end >= '$init' OR _end IS NULL) AND (init <= '$end' OR init IS NULL)
        AND ".$uid_condition."
        ORDER BY uid ASC, init ASC")
  or die($die);
  for ($i=0;$row=@pg_fetch_array($hired_intervals,$i,PGSQL_ASSOC);$i++) {

    // Clipping
    if ($row["init"]===NULL || $row["init"]<$init) $row["init"]=$init;
    if ($row["_end"]===NULL || $row["_end"]>$end) $row["_end"]=$end;

    $days=0;
    $hours=0;
    
    // Count workable days
    //  +1 because we also count the init day
    $days+=days_from_epoch($row["_end"])-days_from_epoch($row["init"])+1;
    
    // Discount the weekend days
    $days-=num_weekend_days($row["init"],$row["_end"]);
    
    // Discount the holidays for that city on that interval
    $query="SELECT count(fest) as days FROM holiday 
        WHERE city='".$row["city"]."'";
    if (!($row["init"]===NULL)) $query.=" AND fest>='".$row["init"]."'";
    if (!($row["_end"]===NULL)) $query.=" AND fest<='".$row["_end"]."'";
    $r=@pg_exec($cnx,$query) or die($die);
    $days-=@pg_fetch_result($r,"days");
    @pg_freeresult($r);
    
    // Discount the compensated (already paid) hours
    // The compensation blocks aren't clipped by the current interval. Only the
    // compensation blocks that full lay inside the interval are taken into account.
    // Have it in mind when adding new compensations!!
    $query="SELECT sum(hours) as hours FROM compensation
        WHERE uid='".$row["uid"]."'";
    if (!($row["init"]===NULL)) $query.=" AND init>='".$row["init"]."'";
    if (!($row["_end"]===NULL)) $query.=" AND _end<='".$row["_end"]."'";
    $r=@pg_exec($cnx,$query)
    or die($die);
    $ch=@pg_fetch_result($r,"hours");
    // A compensation changes money adding more working time to an employee
    if (!empty($ch)) $hours+=$ch;
    @pg_freeresult($r);

    // Put it all and compute net extra hours
    $worked_hours_consult[$row["uid"]]["workable_hours"]+=($days*$row["journey"]+$hours);

  }
  @pg_freeresult($hired_intervals);

  foreach(array_keys($worked_hours_consult) as $k)
    $worked_hours_consult[$k]["extra_hours"]=
      $worked_hours_consult[$k]["total_hours"]
      -$worked_hours_consult[$k]["workable_hours"];

  return $worked_hours_consult;
}

// Like in_array, but checks if at least one of the needles is in the array
function multi_in_array($needles,$haystack,$strict=false) {
    $found=false;
    foreach ($needles as $needle) {
       $found|=in_array($needle,$haystack,$strict);
       if ($found) break;
    }
    return $found;
}
/* 
 * param: $f date in web format
 * param: $f date in web format
 * elapsed_days($f, $t) computes the amount of days elapsed between two dates in a closed interval [$f, $t]
 * (both dates are included)
 * It returns the amount of days elapsed from $f to $t
 */
function elapsed_days($f, $t){
  if(empty($f) || empty($t))
    return NULL;
  
  $fDMA=date_web_to_arrayDMA($f);		
  $tDMA=date_web_to_arrayDMA($t);

  $ts_f = mktime(0,0,0,$fDMA[1],$fDMA[0],$fDMA[2]);
  $ts_t = mktime(0,0,0,$tDMA[1],$tDMA[0],$tDMA[2]);
  $seconds = $ts_t-$ts_f;
  $elapsed_days = floor($seconds / (60 * 60 * 24)); 
  return $elapsed_days;
}


// Returns the number of hours devoted to a project
function devoted_hours($cnx, $project_id) {
  $query="SELECT SUM( _end - init ) / 60.0 AS add_hours FROM task WHERE name = '".$id."'";
  $data=@pg_exec($cnx,$query) or die($die);
  $data_consult=@pg_fetch_result($data,"add_hours");
  @pg_freeresult($data);
  
  return $data_result;
}
?>
