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

function msg_fallo($msg) {
 echo("<p class=\"msg_fallo\"><b>$msg</b></p>");
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
function borra_elementos_desplazando($array, $i) {
 array_splice($array,$i,1);
 return(array_values($array));
}

// Convierte una fecha de formato "español" (DD/MM/AAAA) a un
// array(DD,MM,AAAA)
function fecha_web_to_arrayDMA($fecha_web) {
 return explode("/",$fecha_web);
}

// Convierte un array(DD,MM,AAAA) en una fecha de
// formato "español" (DD/MM/AAAA)
function fecha_arrayDMA_to_web($fecha_arrayDMA) {
 return implode("/",$fecha_arrayDMA);
}

// Convierte una fecha de formato "español" (DD/MM/AAAA) a formato "PostgreSQL" (AAAA-MM-DD)
function fecha_web_to_sql($fecha_web) {
 $fechaDMA=explode("/",$fecha_web);
 if (strlen($fechaDMA[0])==1) $fechaDMA[0]="0".$fechaDMA[0];
 if (strlen($fechaDMA[1])==1) $fechaDMA[1]="0".$fechaDMA[1];
 return($fechaDMA[2]."-".$fechaDMA[1]."-".$fechaDMA[0]);
}

// Convierte una fecha de formato "PostreSQL" (AAAA-MM-DD) a formato "español" (DD/MM/AAAA)
function fecha_sql_to_web($fecha_sql) {
 $fechaAMD=explode("-",$fecha_sql);
 return($fechaAMD[2]."/".$fechaAMD[1]."/".$fechaAMD[0]);
}

// Compara las dos fechas de inicio de dos arrays
function cmp_fechas_inicio ($a, $b) {
    if ($a["inicio"] == $b["inicio"]) return 0;
    return ($a["inicio"] < $b["inicio"]) ? -1 : 1;
}

// Convierte una hora de formato "español" (HH:MM) a formato "PostgreSQL" (minutos totales)
function hora_web_to_sql($hora_web) {
 $horaHM=explode(":",$hora_web);
 return($horaHM[0]*60+$horaHM[1]);
}

// Convierte una fecha de formato "PostgreSQL" (minutos totales) a formato "español" (HH:MM)
function hora_sql_to_web($hora_sql) {
 $horaHM=array(floor($hora_sql/60),$hora_sql%60);
 return(sprintf("%02d:%02d",$horaHM[0],$horaHM[1]));
}

// Convierte una fechahora de formato "MySQL" (AAAA-MM-DD HH:MM:SS) a
// formato "español" (DD/MM/AAAA HH:MM)
/*
function fechahora_sql_to_web($fecha_sql) {
 $fechaFH=explode(" ",$fecha_sql);
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
function array_to_option($opciones, $selected, $valores="") {
 $result="";
 $i=0;
 foreach((array)$opciones as $opcion) {
  if (!empty($valores)) {
   $valor="VALUE=\"$valores[$i]\"";
   if ($valores[$i]==$selected) $txt_selected="SELECTED";
   else $txt_selected="";
  } else {
   $valor="";
   if ($opcion==$selected) $txt_selected="SELECTED";
   else $txt_selected="";
  }
  $result=$result."<OPTION $txt_selected $valor>".$opcion."</OPTION>";
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
function dia_mes_movido($dia, $movimiento) {
 $arrayDMA=fecha_web_to_arrayDMA($dia);
 $tmp=getdate(mktime(0,0,0,$arrayDMA[1]+$movimiento,$arrayDMA[0],$arrayDMA[2]));
 return(fecha_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

// Devuelve la fecha que se le pasa con tantos años hacia adelante o atras como se le indique
// en los parametros. La fecha debe estar en formato DD/MM/AAAA
function dia_anho_movido($dia, $movimiento) {
 $arrayDMA=fecha_web_to_arrayDMA($dia);
 $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]+$movimiento));
 return(fecha_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
}

// Crea un array con el calendario del mes de una fecha dada.
// Si la fecha esta vacia se coge el dia actual.
function crea_calendario($dia) {
 if (!empty($dia)) {
  $arrayDMA=fecha_web_to_arrayDMA($dia);
  $hoy=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
 } else {
  $hoy=getdate(time());
  $hoy=getdate(mktime(0,0,0,$hoy["mon"],$hoy["mday"],$hoy["year"]));
 }

 $dia=fecha_arrayDMA_to_web(array($hoy["mday"],$hoy["mon"],$hoy["year"]));
 $arrayDMA=fecha_web_to_arrayDMA($dia);

 $calendario=array();

 // Cálculo de los días en gris del mes anterior

 $dt=getdate(mktime(0,0,0,$hoy["mon"],1,$hoy["year"]));
 $d=getdate(mktime(0,0,0,$hoy["mon"],
  1-((($dt["wday"]+6)%7)+1),$hoy["year"]));
 $s=1; // Semana
 for ($i=1;$i<((($dt["wday"]+6)%7)+1);$i++) {
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1]-1,$d["mday"]+$i,$arrayDMA[2]));
  $calendario[$s][$i]=array($d["mday"]+$i,"G",
   fecha_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"]))
  );
 }

 // Cálculo de los días en negro (de este mes)
 for ($k=0;;$k++,$i++) {
  $t=mktime(0,0,0,$hoy["mon"],$k+1,$hoy["year"]);
  $dt=getdate($t);
  if (($dt["mon"])==($hoy["mon"])) {
   if ($dt==$hoy) $letra_color="H";
   else $letra_color="N";
   $tmp=getdate(mktime(0,0,0,$arrayDMA[1],$dt["mday"],$arrayDMA[2]));
   $calendario[$s][$i]=array($dt["mday"],$letra_color,
    fecha_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
   if ($i==7) {
    $i=0;
    $s++;
   }
  } else break;
 }

 // Cálculo de los días en gris del mes siguiente
 for ($j=1;($i>1)&&($i<=7);$i++) {
  $tmp=getdate(mktime(0,0,0,$arrayDMA[1]+1,$j,$arrayDMA[2]));
  $calendario[$s][$i]=array($j,"G",
    fecha_arrayDMA_to_web(array($tmp["mday"],$tmp["mon"],$tmp["year"])));
  $j++;
 }
 return($calendario);
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
   require("reglas.dtd.php");
   $dtd=ob_get_contents();
   ob_end_clean();
   $f=fopen("reglas.dtd","w");
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
function minutos_trabajados_esta_semana($cnx,$uid,$dia) {
 $horas="---";
 $result=@pg_exec($cnx,$query="
SELECT uid, SUM(fin-inicio) - 60*COALESCE((
  SELECT SUM(horas)
  FROM compensacion
  WHERE uid=tarea.uid AND uid='$uid'
   AND inicio>=(timestamp '$dia' -
    ((int2(date_part('dow',timestamp '$dia')+7-1) % 7)||' days')::interval)::date
   AND fin<=(timestamp '$dia'-
    ((int2(date_part('dow',timestamp '$dia')+7-1) % 7)-6||' days')::interval)::date
 ),0) AS suma_horas
FROM tarea
WHERE fecha>=(timestamp '$dia' -
 ((int2(date_part('dow',timestamp '$dia')+7-1) % 7)||' days')::interval)::date
 AND fecha<= (timestamp '$dia' -
 ((int2(date_part('dow',timestamp '$dia')+7-1) % 7)-6||' days')::interval)::date
 AND uid='$uid'
GROUP BY uid");
 if ($row=@pg_fetch_row($result)) $minutos=$row[1];
 @pg_freeresult($result);
 return $minutos;
}

?>
