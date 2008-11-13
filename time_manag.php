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

require_once("include/config.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

define('max_holidays', 23);  // Maximun amount of holidays. Weekends not included.

/* This code was based on previous source code cal_manag.php */

/* preparing data for event combobox */
$result=@pg_exec($cnx,$query="SELECT DISTINCT code, description from label where type='evtype'")
  or $error=_("Can't finalize this operation: ". $query);  
$event_types= array();
$event_types["NO_EVENT"]="---";
	
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $event_types[$row["code"]]=$row["description"]; //$event_types array  is indexed by code
 }
@pg_freeresult($result);
 	
$showCombo= $event_types;
$showCombo["NO_EVENT"]="None";
$showCombo["All"]="All events";

/* preparing data to load suitable calendar for appropriate city */

//When an user is just registered, her _end attribute is null	
$result=@pg_exec($cnx,$query="SELECT city from periods where uid='$session_uid'
  				AND init<='".date_web_to_sql($day)."' AND
   			(_end >='".date_web_to_sql($day)."' OR  
   			_end is NULL)")
  or $error=_("Can't finalize this operation: ".$query);
   
while ($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
  $city=$row["city"];
 }
@pg_freeresult($result);

if($city==NULL)
  $warning=_("Warning: No city associated to user '".$session_uid. "' on ".$day);
 else
   $place=$city;

/* preparing data for hours combobox */ 
$hours_account= array(0, 1, 2, 3, 4, 5, 6, 7, 8);

/* Selecting fest according to $place */
$result=@pg_exec($cnx,$query="SELECT fest,city FROM holiday WHERE city='$place' ORDER BY fest")
  or $error=_("Can't finalize this operation: ".$query);

for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $row=date_SQL_to_web($row["fest"]);
  $arrayDMA=date_web_to_arrayDMA($row);
  $holidays[]=getdate(mktime(0,0,0,$arrayDMA[1],$arrayDMA[0],$arrayDMA[2]));
 }
@pg_freeresult($result);
  
/* Selecting busy_hours for this user */ 
$result=@pg_exec($cnx, $query="SELECT date, hours, ev_type 
		FROM busy_hours WHERE uid='$session_uid'")
  or $error=_("Can't finalize this operation: ". $query); 
$busy_hours= array();
while($row=@pg_fetch_array($result,NULL, PGSQL_ASSOC)){
  $busy_hours[$row["date"]]=$row; // indexed by date since only one event is supposed to be booked per day
 }
@pg_freeresult($result);

/* Isolate dates (in YYYY-MM-DD format) from days with DD/MM/YYYY busy_hours
 * In this way, we have an array $busy_hours indexed by date in YYYY-MM-DD format with data corresponding to each event and another
 * array $busy_hours_YMD with only the dates indexed as usual by indexes i €[0,1,2,...] to make easier searches on events. */
$busy_hours_YMD= array_keys($busy_hours);	
 

/* "Delete events in range" is pressed. 
 * All events included in interval pointed by From and To fields are deleted from both form and database.
 * No matter the kind of event to be deleted.
 */ 
if(!empty($delete_range)){
  if($range_visible=='yes'){ 
    if(validate_from_to($from, $to)){
      $dates= create_consecutive_dates($from, $to);
      foreach($dates as $dt){
        $dt_web= date_arrayDMA_to_web($dt);
        $sql_date=date_web_to_sql($dt_web);
        if(in_array($sql_date,$busy_hours_YMD)){					
          $dates_to_delete[]= $sql_date;
        }			
      }//end foreach
    }
    else{
      $error=_("Interval [$from, $to] is not valid");	
      $range_visible='yes';
    }		
    if(empty($error)){
      if(!empty($dates_to_delete)){	 
        if (!@pg_exec($cnx,$query=
                      "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
                      ."BEGIN TRANSACTION; ")) {
          $error=_("Can't finalize the operation");
        }
        foreach($dates_to_delete as $dt){
          if (!@pg_exec($cnx,$query= "DELETE FROM busy_hours WHERE date='$dt' AND uid='$session_uid'")) {
            $error=_("Can't finalize the operation");
            break;
          }
        }
        if (!empty($error)) {
          $error.="<!-- $query -->";
          @pg_exec($cnx,$query="ROLLBACK TRANSACTION");   			
        }
        else{		
          @pg_exec($cnx,$query="COMMIT TRANSACTION");
          $confirmation=_("Events have been deleted correctly in interval [$from, $to]");
        }
        // Update busy_hours array and YMD
        foreach($dates_to_delete as $dt){
          $index=-1;
          $index=IndexOf($dt,$busy_hours_YMD);
          if(is_int($index) && ($index!=-1)) {//If it was found
            $busy_hours= del_elements_shifting($busy_hours, $index);
            $busy_hours_YMD= del_elements_shifting($busy_hours_YMD, $index);
          }
        }
      } //end empty($dates_to_delete)
    } //end empty($error)
  }
  else $error=_("This should not happen"); //It should be visible, since it is displayed all together
 }  	

/* Delete pressed
 * If "Delete" pressed, current selected date ($day) is deleted from both form and database.
 * If range is visible, range is ignored and only $day's date will be affected.
 */	
if(!empty($delete)){
  /* if day selected has any event */
  $day_sql=date_web_to_sql($day);
  if(in_array($day_sql,$busy_hours_YMD)){
    if(!pg_exec($cnx,$query="DELETE FROM busy_hours WHERE uid='$session_uid' AND date='$day_sql'"))
      $error=_("Can't finalize this operation: ".$query);
    // Update busy_hours array and YMD
    $index=-1;
    $index=IndexOf($day_sql,$busy_hours_YMD);
    if(is_int($index) && ($index!=-1)) {//If it was found
      $busy_hours= del_elements_shifting($busy_hours,  $index);
      $busy_hours_YMD= del_elements_shifting($busy_hours_YMD, $index);
    }
  }
 }  

/* Save changes pressed */
if(!empty($save_changes)){
  /* Handle "From: To:" fields when planning events with "Select Range" button */
  if((array_search($events, $event_types)!="NO_EVENT")&& (validate_hours($hours))){
    if($range_visible=='yes'){ //if From, To are active
      if(validate_from_to($from, $to)){				
        $range_dates = create_consecutive_dates($from, $to);				
      }
      else {
        $error=_("Interval [$from, $to] is not valid");
        $range_visible='yes';
      }
    }	
    else{ // From and To are not active=> only one day is selected
      $range_dates[]=date_web_to_arrayDMA($day);
    }
    $week_dates= remove_weekends($range_dates);				
    $event_dates= remove_fest($week_dates, $holidays);
    if(empty($error)){
      if(!empty($event_dates)){		
        foreach($event_dates as $e){
          $event_dts[]=date_arrayDMA_to_web($e);
        }
        $event_dates=$event_dts;
      }
      else $error=_("There is no date where to apply changes. Remember weekends and festive days are not allowed.");
    }
    if($events=="Holidays"){				 			
      $available_days= available_holidays($cnx,$session_uid);
      if($available_days<0)
        $error=_("Planned holidays have already reached the maximum");
      else if(count($event_dates)>$available_days){
        $error=_("Only ".max_holidays. " days are allowed. Your selection [$from, $to] has reached the maximum. You only have "
                 .$available_days." days available");
      }
    }
    $hours=round($hours*100)/100; //two decimals
    if(empty($error)){
      if (!@pg_exec($cnx,$query=
                    "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
                    ."BEGIN TRANSACTION; ")) {
        $error=_("Can't finalize the operation");
      }
      foreach($event_dates as $e){
        if(in_array(date_web_to_sql($e),$busy_hours_YMD)){
          /* Delete previous event on selected date if already in */
          if (!@pg_exec($cnx,$query="DELETE FROM busy_hours WHERE uid='$session_uid' AND 
		  						date='".date_web_to_sql($e)."'")) {
            $error=_("Can't finalize the operation: ".$query);
          }
        }
        if(!pg_exec($cnx, $query="INSERT INTO busy_hours(uid,date,hours,ev_type)VALUES('$session_uid', '".date_web_to_sql($e)."',$hours, '"
                    .array_search($events, $event_types)."')"))
          $error=_("Can't finalize the operation: ".$query);  
        if (!empty($error)) {
          $error.="<!-- $query -->";
          @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
          break;
        }
      }
      @pg_exec($cnx,$query="COMMIT TRANSACTION");
  					
      /* Update arrays busy_hours and YMD */	
      foreach($event_dates as $e){					
        $day_sql=date_web_to_sql($e);
        /* if already in, it is overwritten */
        $busy_hours[$day_sql]=array("date" => $day_sql,
                                    "hours" => $hours,
                                    "ev_type" => array_search($events, $event_types));
        if(!in_array($day_sql,$busy_hours_YMD))
          $busy_hours_YMD[]= $day_sql;
      } 
    }//if empty(error)
  }
  else{
    $error=$error._(" Select appropriate event and amount of hours");
  }
 } // end save changes

$days=array(_("Monday"),_("Tuesday"),_("Wednesday"),_("Thursday"),_("Friday"),_("Saturday"),_("Sunday"));

$day_month_previous=day_month_moved($day, -1);
$day_month_next=day_month_moved($day, 1);
$day_year_previous=day_year_moved($day, -1);
$day_year_next=day_year_moved($day, 1);

/* Create calendar with fest days */
if (empty($day)) $calendar=make_calendar2($holidays);
 else $calendar=make_calendar2($holidays,$day);

/* USEFUL FUNCTIONS */

/* 
 * param: $cn: db connection
 * param: $s_uid: user id
 * available_holidays($cn, $s_uid) returns the amount of days a user has planned as holidays so far.
 */
function available_holidays($cn, $s_uid){
  if(!$result=@pg_exec($cn, $query="SELECT COUNT(date) AS planned FROM busy_hours WHERE uid='$s_uid' 
		AND ev_type='vac'"))
    $error=_("Can't finalize operation". $query);
  $row=@pg_fetch_array($result);		
  $planned=$row["planned"];	
  @pg_freeresult($result);
  $a= max_holidays-$planned;
  return $a;
}
/*
 * param: $f. date in web format
 * param: $t. date in web format
 * validate_from_to($f, $t) validates if dates $f, $t are in a valid web format and if at least interval [$f, $t] spans one day.
 * If $f==$t, this function returns true
 */
function validate_from_to($f, $t){
  if(!validate_date_web($f))
    return false;
  if(!validate_date_web($t))
    return false;
  $elapsed_days= elapsed_days($f, $t);
  if($elapsed_days<0) 
    return false;
  return true;			
}


function display($range_visible){
  if($range_visible=="yes"){
    echo "display:block";
  }
  else {
    echo "display:none";
  }
}
/* 
 * param: $f date in web format
 * param: $f date in web format
 * create_consecutive_dates($f, $t) returns an array of consecutive dates  between dates $f and $t in a closed interval [$f, $t] 
 * (both dates are included)
 */
function create_consecutive_dates($f, $t){
  $fDMA=date_web_to_arrayDMA($f);		
  $tDMA=date_web_to_arrayDMA($t);
  $ts_f = mktime(0,0,0,$fDMA[1],$fDMA[0],$fDMA[2]);
  $days_in_month=date('t',$ts_f);
  $dates=array();
  $i=$fDMA[0];
  $current_month=$fDMA[1];
  $current_year=$fDMA[2];
  $across_month=false;
  $across_year=false;
  $elapsed_days= elapsed_days($f, $t);
  while($elapsed_days>=0){
    $date=$fDMA;							
    if($i>$days_in_month){ //if change of month reached
      $i=1;
      $current_month++;
      $across_month=true;
      if($current_month==13){
        $current_month=1;
        $current_year++;
        $across_year=true;
      }
      $days_in_month=date('t',mktime(0,0,0,$current_month,1,$current_year));
    }
    $date[0]=$i;
    if($across_month)
      $date[1]=$current_month;
    if($across_year)
      $date[2]=$current_year;	
    $dates[]=$date;
    $i++;		
    $elapsed_days--;
  }//end while
  return $dates;
}
/* 
 * param: $dates.an array of dates in DMA format
 * remove_weekends($dates) returns an array of dates in DMA format without weekends
 */
function remove_weekends($dates){
  if(!empty($dates)){
    foreach($dates as $dt){
      $ts= mktime(0,0,0,$dt[1], $dt[0], $dt[2]);
      $day_of_week=date('N',$ts);
      if(($day_of_week!=6)&&($day_of_week!=7))
        $new_dates[]=$dt;
    }
  }
  return $new_dates;
}
/*
 * param: $dates.an array of dates in DMA format
 * param: $holis.an array of holidays.
 * remove_fest($dates, $holis) returns an array of dates in DMA format without fest
 */
function remove_fest($dates, $holis){
  if(!empty($dates)){
    foreach ($dates as $dt){
      $ts= mktime(0,0,0,$dt[1], $dt[0], $dt[2]);
      if(!in_array(getdate($ts), $holis))
        $new_dates[]=$dt;
    }
  }
  return $new_dates;
}
/*
 * param: $hours
 * validate_hours($hours) validates $hours is a numeric value belonging to (0,8].
 */
function validate_hours($hours){
  global $error;
  if(!empty($hours)){
    if(is_numeric($hours)){
      if(($hours>0) && ($hours<=8)){
        return true;
      }
    }
  }
  $error=_("Amount of hours should belong to (0,8] interval. Real values are also accepted.");
  return false;  
}
require_once("include/close_db.php");

$title=_("Time planning");
require("include/template-pre.php");
if (!empty($error)) msg_fail($error);
if(!empty($warning)) msg_warning($warning);
if(!empty($confirmation)) msg_ok($confirmation);
?>
<Table border="0" cellpadding="0" cellspacing="0" width="100%"
  style="text-align: center; margin-left: auto; margin-right: auto;">
  <tr>
    <td style="text-align: center; vertical-align: top;">
      <center>
      <form name="results" method="post" action="time_manag.php">
      <input type="hidden" name="day" value="<?=$day?>">
      <input type="hidden" name="place" value="<?=$place?>">
      <input type="hidden" id="range_visible" name="range_visible"
      <?if($range_visible=='yes'){?> value="yes"<?} else{?> value="no"<?}?>><!-- it notifies if fields from-to are visible -->
      <br><br>
      <?if (!empty($place) && $place!="---" || !empty($holidays)) {?>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td bgcolor="#000000"> <!-- table in black -->
            <table border="0" cellspacing="1" cellpadding="0" width="100%">
              <tr>
                <td bgcolor="#000000" class="title_box">
                  <font color="#FFFFFF" class="title_box">
                  <?=_("Personal time planning");?>
                  </font>
                </td>
              </tr>
              <tr>
                <td bgcolor="#FFFFFF" class="text_box">
                  <table border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td>
                        <font color="#000000" class="text_box">
                        <table border="0" cellpadding="0" cellspacing="10" width="100%">
                          <tr>
                            <td>
                              <table border="0" cellpadding="0" cellspacing="0"
                                style="text-align: left; margin-left: auto; margin-right: auto;"> 
                                <tr>  
                                  <td bgcolor="#999999" style="text-align: center;">
                                    <table cellpadding="3" cellspacing="1" style="text-align: center">
                                      <? $style=array(
                                      "B"=>"background: #FFE9E9; color: #000000; font-weight: regular; text-align: center",	  
                                      "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
                                      "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: right",
                                      "N"=>"background: #E9FFE9; color: #000000; font-weight: regular; text-align: right", 	  
                                      "A"=>"background: #FFE9E9; color: #808080; font-weight: regular; text-align: right" 
                                      );?>
                                      <tr>
                                        <td style="background: #FFFFFF; font-weight: bold;text-decoration: none"colspan="7">
                                          <table border="0" cellpadding="0" cellspacing="0" width="100%"> 
                                            <tr>
                                              <td style="background: #FFFFFF; width: 4ex">
                                                <a href="?day=<?=$day_month_previous?>&amp;show_events=<?=$show_events?>&amp;place=<?="$place"?>"
                                                style="color: #000000">&lt;</a>
                                              </td>
                                              <td style="background: #FFFFFF; width: 4ex">
                                                <a href="?day=<?=$day_month_next?>&amp;show_events=<?=$show_events?>&amp;place=<?="$place"?>"
                                                style="color: #000000">&gt;</a>
                                              </td>
                                              <td align="right">
                                                <input type="text" name="day" value="<?=$day?>"
                                                size="10" style="width: 100%; height: 100%">
                                              </td>
                                              <td align="right">
                                                <input type="submit" name="change" value="<?=_("Go to day")?>"              
                                                style="width: 100%; height: 100%">
                                              </td>
                                            </tr>
                                          </table> 
                                        </td>
                                      </tr>
                                      <tr>
                                        <td td bgcolor="#000000" class="title_box" colspan="7"> 
                                          <font color="#FFFFFF" class="title_box"> 
                                          <? $dayDMA= date_web_to_arrayDMA($day);
                                          $current_month=date('F',mktime(0,0,0,$dayDMA[1], $dayDMA[0], $dayDMA[2]));
                                          $current_year=date('Y',mktime(0,0,0,$dayDMA[1], $dayDMA[0], $dayDMA[2]));?>
                                          <? echo ($current_month."  ".$current_year);?> 
                                          <br>	
                                        </td>
                                      </tr>
                                      <tr>
                                        <?// Day titles computation
                                        foreach ($days as $d) {
                                        ?>
                                        <td style="<?=$style["T"]?>">
                                          <?=$d?>
                                        </td>
                                        <?}
                                        ?>
                                      </tr>
                                      <?foreach ($calendar as $s) { //$s=week
                                      ?>
                                      <tr>
                                        <?foreach ($s as $d) {//$d= dayborder
                                        if(date_web_to_sql($d[2])==date_web_to_sql($day)){
                                        /* Mark date selected on current Calendar creating a new table inside a cell */
                                        ?>
                                        <td style="background:#CB2EDF"> 
                                          <table border="0"
                                            style="<?=$style[$d[1]]?>"
                                            cellpadding="2"
                                            cellspacing="0" width="100%"
                                            align="right" valign="bottom">
                                            <tr>
                                              <td style="<?=$style[$d[1]]?>"  valign="bottom" cellpadding="0" width="100%" > 
                                                <br> 
                                                <?}
                                                else { ?>
                                              <td style="<?=$style[$d[1]]?> " valign="bottom" width="10%">
                                                <?}
                                                if (($d[1]=="N")||($d[1]=="G")){?>	
                                                <?/* Mark those days with busy_hours */
                                                if(in_array(date_web_to_sql($d[2]),$busy_hours_YMD)){?>
                                                <img src="./images/VioPoint.gif" title="ticked day" alt="ticked day" align="left" valign="top">
                                                <br>
                                                <?}?>
                                                <a href="time_manag.php?day=<?=$d[2]?>&amp;show_events=<?=$show_events?>&amp;place=<?="$place"?>" style="<?=$style[$d[1]]?>">
                                                <?}?>
                                                <?=$d[0]?>
                                              </td>
                                              <?if(date_web_to_sql($d[2])==date_web_to_sql($day)){ //close html code for frame of day selected?>
                                            </tr>
                                          </table>
                                        </td>
                                        <?}?>
                                        <?} //end foreach($s as $d)
                                        ?>
                                      </tr>
                                      <? } //end foreach($calendar as $s)
                                      ?>
                                    </table>
                                  </td>
                                </tr>
                              </table> 
                            </td>
                            <!-- legend -->
                            <td align="center">
                              <b><?=_(strtoupper($place)." CALENDAR");?></b>
                              <br><br>
                              <table border="0" cellpadding="1" cellspacing="0"><tr><td bgcolor="#999999">
                                <table border="0" cellpadding="0" cellspacing="0" style="text-align: center;">
                                  <tr><td style="<?=$style["A"]?>"><?=_("Non workable day")?></td></tr>
                                  <tr><td style="<?=$style["N"]?>"><?=_("Workable day")?></td></tr>
                                  <tr><td style="<?=$style["G"]?>"><?=_("Day of the previous or next month")?></td></tr> 
                                  <tr><td style="<?=$style["N"]?>" > 
                                    <img src="./images/VioPoint.gif" title="ticked day" alt="ticked day" align="left" valign="top"><?=_("Planned event")?></td></tr>         
                                  </table>
                                </td></tr>
                              </table> 
                            </td> <!-- end legend -->
                          </tr>
                        </table>
                        <!-- Planned events table -->
                        <table align="center" border="1" cellpadding="0" cellspacing="4"
                          style="text-align: left; align:left;" rules=none frame=box >
                          <tr>  		
                            <td  style="font-weight:bold; text-align:center;" colspan="2">Planned events on <?=_($day)?> : </td>
                          </tr>
                          <tr>
                            <td> <?=_("Event type: ")?> </td>
                            <td>
                              <select name="events" onchange="checkHolidays(this)">
                          <? if(!empty($change)){// if day has been changed by means of "Go to day" button
                              if(in_array(date_web_to_sql($day),$busy_hours_YMD)){ //current date contained in busy_hours
                                $selected_event=$busy_hours[date_web_to_sql($day)]["ev_type"];				
                                $selected_event=$event_types[$selected_event];
                                $selected_hours=$busy_hours[date_web_to_sql($day)]["hours"];
                             }
                             else{
                               $selected_event=$event_types["NO_EVENT"];
                               $selected_hours="0";
                             }
                           }
                           else{ 
                             if(in_array(date_web_to_sql($day),$busy_hours_YMD)){ //current date contained in busy_hours
                               if(empty($events)){ 
                                 $selected_event=$busy_hours[date_web_to_sql($day)]["ev_type"];				
                                 //$selected contains code of event
                                 $selected_event=$event_types[$selected_event];
                                 //$selected contains description of event
                                 $selected_hours=$busy_hours[date_web_to_sql($day)]["hours"];
                               }
                               else{ // To allow user to make changes in event in an already planned event
                                 $selected_event=$events;
                                 $selected_hours=$hours;
                               }
                           }
                             else{ // if nothing was planned on $day
                               if(empty($events)){ //if combo has not been changed
                                 $selected_event=$event_types["NO_EVENT"];
                                 $selected_hours="0";
                               }
                               else{ 
                                 $selected_event=$events;
                                 $selected_hours=$hours;
                               }
                             }
                          }?>
<?=array_to_option(array_values($event_types), $selected_event, array_values($event_types));?>
</select> </td>
</tr>
<tr>
  <td><?echo("Hours: ")?></td>    
  <td><input style="text-align:right" type="text" name="hours" id="hours" size=3 value=<?=$selected_hours?>> </td>
</tr>
</table> <!-- end Planned events table -->
<br> <br>

<!-- Buttons -->
<div  align="center">
  <input   type="button" id="select_range" name="select_range"
  <?if($range_visible=="yes"){?> value="<?=_("Hide range")?>" <?}
  else{?> value="<?=_("Select range")?>"<?}?> onClick="toggle()">
  <input   type="submit" name="save_changes" value="<?=_("Save changes")?>">
  <input   type="submit" name="delete"  value="<?=_("Delete event")?>">
</div>                                                        
<br>
<!-- Range division-->
<div id='rangediv' style="<?=display($range_visible);?>" align="center">
  <table align="center" border="1" cellpadding="0" cellspacing="4"
    style="text-align: left; align:left;" rules=none frame=box>
    <tr> <td style="font-weight:bold"> From: </td> <td> <input type="text"name="from" 
      <?if(!empty($from)){?>value="<?=$from?>" <?} else{?>value="<?=$day?>"<?}?> > </td> </tr>
      <tr> <td style="font-weight:bold"> To :  </td> <td> <input type="text" name="to" 
        <?if(!empty($to)){?>value="<?=$to?>"> <?} else{?> value="<?=_("DD/MM/YYYY")?>"<?}?> </td> </tr>
      </table>
      <br>
      <input type="submit" name="delete_range" value="<?=_("Delete events\nin range")?>" >                                                         
    </div>

    <!--  Show events table -->
    <br>                                                        
    <table align="center" border="1" cellspacing="2" style="border-style:solid; border-collapse:collapse; border-color:#878eaf">
      <tr> <td  colspan="1" style="font-weight:bold" > 
        <?=_("Show events: ")?> </td> 
        <td colspan="2"> 
          <select name="show_events" onchange="javascript:document.forms[0].submit();" >
            <font color="#FFFFFF">
            <? if(empty($show_events)){
            $selected_event=$showCombo["NO_EVENT"];
            }
            else $selected_event=$show_events;?>
            <?=array_to_option(array_values($showCombo), $selected_event, array_values($showCombo));?>
          </select> </td>
        </tr> 
        <? if(!empty($show_events))
        if($show_events!="None"){?>
        <tr> 
          <td style="text-align: center;  font-weight: bold"> Date  </td> 
          <td style="text-align: center;  font-weight: bold"> Event </td> 
          <td style="text-align: center;  font-weight: bold"> Hours </td> 
        </tr>
        <?foreach($busy_hours as $f){
        if($show_events=="All events"){ // Actually, if it is different from "All events"
        ?>
        <tr> 
          <td><a href="time_manag.php?day=<?=(date_sql_to_web($f["date"]))?>&amp;show_events=<?=$show_events?>&amp;place=<?="$place"?>" style="A"><?=(date_sql_to_web($f["date"]))?></a></td>
          <td><?=($event_types[$f["ev_type"]])?> </td>    	
          <td style="text-align: center"> <?=($f["hours"])?> </td>
        </tr>
        <?}
        else if($event_types[$f["ev_type"]]==$show_events){ //Filter events selected ?> 
        <tr>
          <td><a href="time_manag.php?day=<?=(date_sql_to_web($f["date"]))?>&amp;show_events=<?=$show_events?>&amp;place=<?="$place"?>" style="A"><?=(date_sql_to_web($f["date"]))?></a></td>
          <td style="text-align: left"><?=($event_types[$f["ev_type"]])?> </td>
          <td style="text-align: center"> <?=($f["hours"])?> 
          </td>
        </tr>
        <?}
        ?>
        <?}//end foreach
        }//end none
        ?>
      </table>    
      <br>
      </font></td></tr></table></td></tr></table></td></tr></table>
      <?}
      ?>
      </form>
      </center>
    </td>
    <td style="width: 25ex" valign="top">
      <? require("include/show_sections.php") ?>
      <br>
      <? require("include/show_calendar.php") ?>
    </td>
  </table>
<?
  require("include/template-post.php");
?>
      
<script language="javascript">
  function toggle(){
  r = document.getElementById('rangediv').getAttribute('style');
  if (r == 'display: none;') { //there is a blank between ":" and "none" and there is ";" at the end!!
    document.getElementById('rangediv').setAttribute('style', 'display:block');
    document.getElementById('range_visible').value='yes';
    document.getElementById('select_range').value='Hide range';
  } else {
    document.getElementById('rangediv').setAttribute('style', 'display:none');	
    document.getElementById('range_visible').value='no';
    document.getElementById('select_range').value='Select range';
  }
}
function checkHolidays(obj){
  if(obj.value=='Holidays'){
    document.getElementById('hours').value=8;
  }
}
</script>
