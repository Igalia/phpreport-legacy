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


// LIBRERÍA DE FUNCIONES ÚTILES

// NOTA: Descomenta las que necesites. Al final del proyecto se
// borrarán las sobrantes.
require_once("include/config.php");

function msg_ok($msg) {
 echo("<p class=\"msg_ok\"><b>$msg</b></p>");
}

function msg_fail($msg) {
 echo("<p class=\"msg_fail\"><b>$msg</b></p>");
}

// Obtiene la mayor de las claves de un array monodimensional
/*
function max_key($array) {
 $max=-1;
 foreach (array_keys($array) as $i)
  if ($i>$max) $max=$i;
 return $max;
}
*/

// Transforma un tipo SET de MySQL (cadena de valores separados por comas)
// en un ARRAY de PHP que contiene cada uno de los elementos presentes en
// el conjunto.
function set_to_array($set) {
 if (empty($set)) return array();
 else return explode(",",$set);
}

/*
function chequeado($checkbox) {
 if ($checkbox==1) return "CHECKED";
 else return "";
}
*/

// Borra el elemento i de un array unidimensional.
// i es numérico. Todos los elementos k, siendo
// k>i serán desplazados a la posición k-1 con el fin de no dejar "huecos"
// en la numeración i del array.
// Ej: A[1] A[2] A[3] A[4] --> A[1] A[3] A[4] ¡¡No debe quedar el hueco A[2]!!
// Correcto: A[1] A[2] A[3] A[4] --> A[1] A[2] A[3] (A[2] y A[3] son los antiguos
//           A[3] y A[4].
function del_elements_shifting($array, $i) {
 array_splice($array,$i,1);
 return(array_values($array));
}

// Tests if a date is valid DD/MM/AAAA or D/M/AAAA
function validate_date_web($web_date) {
 return preg_match("/[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4,4}/",$web_date);
}

// Convierte una fecha de formato "español" (DD/MM/AAAA) a un
// array(DD,MM,AAAA)
function date_web_to_arrayDMA($web_date) {
 return explode("/",$web_date);
}

// Convierte un array(DD,MM,AAAA) en una fecha de
// formato "español" (DD/MM/AAAA)
function date_arrayDMA_to_web($date_arrayDMA) {
 return implode("/",$date_arrayDMA);
}

// Convierte una fecha de formato "español" (DD/MM/AAAA) a formato "PostgreSQL" (AAAA-MM-DD)
function date_web_to_sql($web_date) {
 $dateDMA=explode("/",$web_date);
 if (strlen($dateDMA[0])==1) $dateDMA[0]="0".$dateDMA[0];
 if (strlen($dateDMA[1])==1) $dateDMA[1]="0".$dateDMA[1];
 return($dateDMA[2]."-".$dateDMA[1]."-".$dateDMA[0]);
}

// Convierte una fecha de formato "PostreSQL" (AAAA-MM-DD) a formato "español" (DD/MM/AAAA)
function date_sql_to_web($date_sql) {
 $dateAMD=explode("-",$date_sql);
 return($dateAMD[2]."/".$dateAMD[1]."/".$dateAMD[0]);
}

// Devuelve NULL si alguna fecha es nula
function date_web_to_quoted_sql($web_date) {
  if($web_date=="") return "NULL";
  else {
   $dateDMA=explode("/",$web_date);
   if (strlen($dateDMA[0])==1) $dateDMA[0]="0".$dateDMA[0];
   if (strlen($dateDMA[1])==1) $dateDMA[1]="0".$dateDMA[1];
   return("'".$dateDMA[2]."-".$dateDMA[1]."-".$dateDMA[0]."'");
  }
}

// Compara las dos fechas de inicio de dos arrays
function cmp_init_dates ($a, $b) {
    if ($a["init"] == $b["init"]) return 0;
    return ($a["init"] < $b["init"]) ? -1 : 1;
}

// Convierte una hora de formato "español" (HH:MM) a formato "PostgreSQL" (minutos totales)
function hour_web_to_sql($web_hour) {
 $hourHM=explode(":",$web_hour);
 return($hourHM[0]*60+$hourHM[1]);
}

// Convierte una fecha de formato "PostgreSQL" (minutos totales) a formato "español" (HH:MM)
function hour_sql_to_web($hour_sql) {
 $hourHM=array(floor($hour_sql/60),$hour_sql%60);
 return(sprintf("%02d:%02d",$hourHM[0],$hourHM[1]));
}

// Convierte una fechahora de formato "MySQL" (AAAA-MM-DD HH:MM:SS) a
// formato "español" (DD/MM/AAAA HH:MM)
/*
function fechahora_sql_to_web($date_sql) {
 $fechaFH=explode(" ",$date_sql);
 return(fecha_sql_to_web($fechaFH[0])." "
  .hora_sql_to_web($fechaFH[1]));
}
*/

// Convierte un número a formato monetario. Ej: 123456.45
/*
function money_to_web($currency) {
 return(sprintf("%01.2f",$currency));
}
*/

// Genera los campos OPTION para formularios, marcando SELECTED el campo especificado
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

// Une el contenido de dos arrays elemento a elemento
// Ej: [a b c] [x y z] --> [ax by cz]
/*
function array_join($a1, $a2) {
 $result=$a1;
 foreach (array_keys((array)$a2) as $k)
  $result[$k]=$a1[$k].$a2[$k];
 return $result;
}
*/

// Convierte los values de un array en un array de booleans indexado
// por esos values.
// Ej: [a b c] = [0->a, 1->b, 2->c] --> [a->TRUE, b->TRUE, c->TRUE]
// De este modo ya no es necesario hacer array_search($elemento,$array),
// ya que podemos usar esta comparación más cómoda: ¿($array[$elemento]==true)?
/*
function array_to_boolean_array($array) {
 $result=array();
 foreach (array_values($array) as $i)
  $result[$i]=true;
 return $result;
}
*/

// Extrae una porción de un array
// Ej: sub_array([a->w, b->x, c->y, d->z], [a, c]) = [a->w, c->y]
/*
function sub_array($array, $claves) {
 $result=array();
 foreach (array_values($claves) as $i)
  $result[$i]=$array[$i];
 return $result;
}
*/

// Devuelve la fecha que se le pasa con tantos meses hacia adelante o atras como se le indique
// en los parametros. La fecha debe estar en formato DD/MM/AAAA
function day_month_moved($day, $shift) {
 $arrayDMA=date_web_to_arrayDMA($day);
 $tmp=getdate(mktime(0,0,0,$arrayDMA[1]+$shift,$arrayDMA[0],$arrayDMA[2]));
 return(date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

// Devuelve la fecha que se le pasa con tantos años hacia adelante o atras como se le indique
// en los parametros. La fecha debe estar en formato DD/MM/AAAA
function day_year_moved($day, $shift) {
 $arrayDMA=date_web_to_arrayDMA($day);
 $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]+$shift));
 return(date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

// Devuelve la fecha que se le pasa con tantos días hacia adelante o atras como se le indique
// en los parametros. La fecha debe estar en formato DD/MM/AAAA
function day_day_moved($day, $shif){
  $arrayDMA=date_web_to_arrayDMA($day);
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0]+$shift,$arrayDMA[2]));
  return(date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

//Dada una fecha, devuelve la fecha correspondiente al día anterior
//(útil para la acción copiar informe del día anterior)
function day_yesterday($day){
  return day_day_moved($day,-1);
}

// Crea un array con el calendario del mes de una fecha dada.
// Si la fecha esta vacia se coge el dia actual.
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

 // Cálculo de los días en gris del mes anterior

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
 // Cálculo de los días en negro (de este mes)
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
 // Cálculo de los días en gris del mes siguiente
 for ($j=1;($i>1)&&($i<=7);$i++) {
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1]+1,$j,$arrayDMA[2]));
  $calendar[$s][$i]=array($j,"G",
    date_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
  $j++;
 }
 return($calendar);
}


// Necesario para eval_html
function eval_buffer($string) {
    ob_start();
    eval("$string[2];");
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
}
 
// Necesario para eval_html
function eval_print_buffer($string) {
    ob_start();
    eval("print $string[2];");
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
}
 
// Evalúa un PHP y devuelve el resultado como un string
function eval_html($string) {
    $string = preg_replace_callback("/(<\?=)(.*?)\?>/si",
                                    "eval_print_buffer",$string);
    return preg_replace_callback("/(<\?php|<\?)(.*?)\?>/si",
                                  "eval_buffer",$string);
}

// Actualiza el fichero reglas.dtd con la configuración de config.php
function update_dtd() {

   global $tabla_tipo, $tabla_nombre, $tabla_fase, $tabla_ttipo;

   ob_start();
   require("rules.dtd.php");
   $dtd=ob_get_contents();
   ob_end_clean();
   $f=fopen("rules.dtd","w");
   fwrite($f,$dtd);
   fclose($f);
}
 
// Convierte un valor de un checkbox a una variable booleana para PostgreSQL
function checkbox_to_sql($i) {
 if (!empty($i)) return "t";
 else return "f";
}

// Convierte una variable booleana de PostgreSQL (t/f) en una cadena "CHECKED"
function sql_to_checkbox($i) {
 if ($i=="t") return " CHECKED ";
 else return "";
}

// Calcula los minutos que un trabajador lleva trabajados la semana actual
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

function workable_days ($init, $end, $holidays, $periods){
foreach ($periods as $period) {
  $i=date_web_to_arrayDMA($period["init"]);
  $i=date(mktime(0,0,0,$i[1],$i[0],$i[2]));
  $init=$i;
  if ($period["_end"]=="NULL"){
   $e=getdate(time());
   $e=date(mktime(0,0,0,$e["mon"],$e["mday"],$e["year"]));
  }
  else {
    $e=date_web_to_arrayDMA($period["_end"]);
    $e=date(mktime(0,0,0,$e[1],$e[0],$e[2]));
  }
  $end=$e;
  while ($init<=$e&&$e<=$end){
    $init=getdate($init);
    $search=array_search($init,$holidays);
    if ($search===false) {
      if($init["weekday"] != 'Sunday' && $init["weekday"] != 'Saturday')
            $hours[$init["year"]][$init["mon"]]+=$period["journey"];
    }
    $init=getdate(mktime(0,0,0,$init["mon"],$init["mday"]+1,$init["year"]));
    $init=date(mktime(0,0,0,$init["mon"],$init["mday"],$init["year"]));
  }
} //end foreach ($periods...
return $hours;
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

 // Cálculo de los días en gris del mes anterior

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

 // Cálculo de los días en negro (de este mes)
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
 // Cálculo de los días en gris del mes siguiente
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
        WHERE _end >= '$init' AND init <= '$end' AND ".$uid_condition."
        ORDER BY uid ASC, init ASC")
  or die($die);
  for ($i=0;$row=@pg_fetch_array($hired_intervals,$i,PGSQL_ASSOC);$i++) {
    // Clipping
    if ($row["init"]===NULL || cmp_init_dates($row["init"],$init)<0) $row["init"]=$init;
    if ($row["_end"]===NULL || cmp_init_dates($row["_end"],$end)>0) $row["_end"]=$end;
    
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
    $hours-=@pg_fetch_result($r,"hours");
    @pg_freeresult($r);

    // Put it all and compute net extra hours
    $worked_hours_consult[$row["uid"]]["workable_hours"]+=($days*$row["journey"]+$hours);        
  }
  @pg_freeresult($hired_intervals);

  foreach(array_keys($worked_hours_consult) as $k)
    $worked_hours_consult[$k]["period_extra_hours"]=
      $worked_hours_consult[$k]["total_hours"]
      -$worked_hours_consult[$k]["workable_hours"];

  return $worked_hours_consult;
}

?>
