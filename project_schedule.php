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


require_once("include/config.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");


define('max_week_hours', 60);  // Max amount of hours a user can choose to work.
define('default_working_week', 35); // Default amount of hours to work a week
define('periods_separator',"=");
define('users_separator',"@");
define('data_periods_separator',"^");
define('user_periods_separator',"%");
define('year_week_separator', "-");
define('week_user_separator',"_");

$title=_("Project Scheduling");   
require("include/template-pre.php");

/* SET SCREEN VALUE */


if((!empty($generate)) && ($screen=="INIT") && (empty($schedule_type))){ //If none of radio buttons is selected in INIT screen, stay in "INIT"
  $screen="INIT";
  $warning="Select one schedule type";
 }
 else if(!empty($generate)){
   $old_screen=$screen;// it is necessary in screen transition from EDITION to GENERATED, since button Generate can be used from INIT screen to GENERATED 
   // and from EDITION to GENERATED
   $screen="GENERATED";
 }
if(!empty($edit))
  $screen="EDITION";
if(!empty($reset)){
  $screen="INIT";
  unset($edited_cells);
  $schedule_type=$project_type;
 }
if(!empty($save))
  $screen="GENERATED";
if(!empty($week_break_down))
  $screen="BUSY_HOURS";
if($project=="---")
  $screen="SELECT";
if(!empty($go_back))
  $screen="EDITION";
//print("screen". $screen);

/* If project already chosen*/
if(!empty($project)){
  if($project=="---"){
    unset($project);
    unset($screen);
  }
  else{
    /* Select init and _end dates from project (always) */
    $result=@pg_exec($cnx, $query="SELECT init,_end, est_hours, moved_hours FROM projects WHERE id='$project'")
      or exit("Cant't finalize this operation. ".$query);

    while($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)){
      $project_info=$row;
    }
    @pg_freeresult($result);

    $project_info["total_hours"]=$project_info["est_hours"]+$project_info["moved_hours"];

    $init_proj=date_sql_to_web($project_info["init"]);
    $init_year=get_ISO_year($init_proj);
    $end_proj= date_sql_to_web($project_info["_end"]);
    $end_year= get_ISO_year($end_proj);
    $current_year=$init_year;

    /* Select users related to the project */
    $result= @pg_exec($cnx, $query="SELECT uid FROM project_user WHERE name='$project'")
      or exit("Can't finalize this operation. ".$query);
    $users_project= array();
    while($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)){
      array_push($users_project, $row["uid"]);
    }
    @pg_freeresult($result);
    
    if(($old_project!=$project)){ //if a different project has been selected in combo project, load users from db     
      $screen="INIT";
      /* Select schedule data related to the project (if any) and create periods */        
      $result= @pg_exec($cnx, $query="SELECT uid, to_char(init_week,'DD/MM/YYYY') as init_week, to_char(end_week,'DD/MM/YYYY') as end_week, weekly_load
                        FROM project_schedule WHERE pid='$project' ORDER BY uid, init_week")
        or $error=_("Can't finalize this operation. ".$query);
      
      $users=array(); //tmp array    
      while($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)){ //create periods data structure:
        if(!in_array($row["uid"],$users)){
          array_push($users,$row["uid"]);
          $periods[$row["uid"]]=array();
        }
        $p=array("init"=>$row["init_week"],
                 "_end"=>$row["end_week"],
                 "weekly_ded"=>$row["weekly_load"]);
        array_push($periods[$row["uid"]],$p);
      }
      @pg_freeresult($result);
      unset($users);
      unset($users_weeks);
      unset($from);
      unset($to);
      unset($ded_per_week);
      unset($edited_cells);
      unset($schedule_type);
      
      /* Find out if this project has already been scheduled */
      $result=@pg_exec($cnx, $query="SELECT sched_type from projects where id='$project'")
        or $error=_("Can't finalize this operation. ".$query);
      while($row=@pg_fetch_array($result,NULL, PGSQL_ASSOC)){
          $project_type=$row["sched_type"];
      }
      @pg_freeresult($result);
      if(!empty($project_type)){
        $screen="GENERATED"; // GO directly to Generated screen, omit INIT screen.
        $jump=TRUE;
      }
    }
    else { /* read values from old data */
      $periods=unpack_periods($data_periods);
      $weeks=explode(",",$_POST['all_weeks']);//in case weeks are less than project duration  
      if(empty($weeks))
        unset($weeks);
    }   
      

        
    if($screen=="INIT"){ //provisional weeks to show in "Project users" table
      $weeks=create_consecutive_weeks($init_proj, $end_proj);
    }
    if($screen=="BUSY_HOURS"){  
      //find out init and end of this concrete week
      $this_week=$init_proj; //web format
      $end_week=create_end_week($this_week);

      $link_week=get_week_from_link($week_break_down);
      $link_user=get_user_from_link($week_break_down);

      for($i=0;$i<$link_week;$i++){
        $this_week=day_day_moved($end_week,1);
        $end_week=create_end_week($this_week);
      }
       
      $end_week=create_end_week($this_week);
      
     
      $weekly_load=get_weekly_load($this_week,$periods[$link_user]);
      if($weekly_load==-1){         
        $week=create_year_week($this_week);
        $warning=_("Weekly load could not be determined for user ".$link_user." during week ".$week. ". A weekly load of 35 hours was set. Relative hours may
 be wrong.");
        msg_warning($warning);
      }
      else{
        //get a break down of busy hours
        $busy_hours_break_down=busy_hours_break_down($cnx,$link_user,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);
      }
    }
   
    /******************** BUTTONS ACTIONS ********************/

    if(!empty($add_period)){
      $uid=current(array_keys($add_period));
      $count_periods= count($periods[$uid]);
      $last_period=$periods[$uid][$count_periods-1];
      $last_end=$last_period["_end"];   
      $combo=create_combo_values($weeks);
      $index=indexOf($last_end,$weeks);
      if($index!=-1){
        if($index==(count($weeks)-1))
          $next_week=$weeks[$index];
        else
          $next_week=$weeks[$index+1];
      }else//this should not happen
        $next_week=$weeks[0];
      $new_p=array("init"=>$next_week,
                   "_end"=>$weeks[count($weeks)-1],
                   "weekly_ded"=>$last_period["weekly_ded"]); 
      array_push($periods[$uid],$new_p);
      $one_more=TRUE;//flag to control "Project Users" table style
    }

    if(!empty($delete_period)){
      $uid=current(array_keys($delete_period));       
      foreach($_POST as $name){   //looking for index of period to be deleted:
        if(is_array($name)){         
          $element=array_pop($name);
          if(is_array($element)){
            $deleted_period=array_search("Delete period",$element); 
            //BE CAREFUL because deleted_period can be 0, treated as FALSE,this is not happening right now because index =0 can't be deleted.
          }
        }
      }

      // create periods for this user
      $array=$periods[$uid];
      unset($periods[$uid]);
      $new_periods=array();
      for($i=0; $i<count($array);$i++){
        if($i!=$deleted_period){
          array_push($new_periods,$array[$i]);
        }
      }
      $periods[$uid]=$new_periods;

      //Reset values to prevent show_periods() from keekping old values:
      unset($from[$uid]);
      unset($to[$uid]);
      unset($ded_per_week[$uid]);
    }
      
    if(!empty($users_project)){
      $journeys=get_journeys($cnx,$users_project, $project_info["init"], $project_info["_end"]);
    }//end no user
    else $warning=_("Warning: There is not any user allocated to project '".$project."'");
      
    // RESET BUTTON: 
    if(!empty($reset)){
      $periods=unpack_periods($data_periods);
      foreach($users_project as $uid){
        $periods[$uid]=sort_periods($periods[$uid]);  
      }
      $weeks=create_consecutive_weeks($init_proj, $end_proj);
      unset($project_type);
      unset($schedule_type);
    }
    // DELETE BUTTON (special case) 
    // Delete button is not a submit button, so it is controlled by means of hidden input $confirmation activated when 
    // delete is pressed and confirmation is done
    if(($confirmation=="ok") && empty($warning) && empty($error)){
      $confirmation="nop";
      $affected_rows=0;
      if (!@pg_exec($cnx,$query="SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
                    ."BEGIN TRANSACTION; ")) {
        $error=_("Can't finalize the operation: ".$query);
      }
      if(empty($error)){	
        $result=@pg_exec($cnx,$query="DELETE FROM project_schedule WHERE pid='$project'")
          or $error=_("Can't finalize the operation".$query);
        
        $affected_rows+=pg_affected_rows($result);

        $result=@pg_exec($cnx,$query= "DELETE FROM edited_project_schedule WHERE pid='$project'")
         or $error=_("Can't finalize the operation".$query);

        $affected_rows+=pg_affected_rows($result);
        // reset schedule type in projects table
        if (!@pg_exec($cnx,$query=
                      "UPDATE projects SET sched_type='' where id='$project'")) {
          $error=_("Can't finalize the operation".$query);
        }
        if (!empty($error)) {
          @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
        }
        else{
          @pg_exec($cnx,$query="COMMIT TRANSACTION");
          if($affected_rows>0)
            $ok=_("Schedule data has been deleted.");			
        }
      }
      $screen="INIT";
      //reset values to show in INIT screen
      $weeks=create_consecutive_weeks($init_proj, $end_proj);      
      unset($project_type);
      unset($edited_cells);
    }
    if(!empty($error))
      msg_fail($error);
    

    // GENERATE BUTTON + generation for the first time(special case)

    if(($jump) //  a previous schedule was already in db [skip INIT screen]
       || (!empty($schedule_type) && !empty($generate))){ //generate for the first time from INIT screen,
   
      $edited_weeks=get_edited_weeks($cnx,$project);//Get weeks that have been manually edited in this project from database:
        if($edited_weeks==-1)
          $error=_("Manual edited weeks could not be found.");      

        if(!empty($generate)&& !empty($from)){ //if generate button was pressed and screen was INIT, validate periods
          validate_periods();
          if(empty($error)){
            $periods=update_periods($periods);
            if(!empty($users_project)){
              foreach($users_project as $uid){         
                $message=check_conflicts($cnx,$uid, $project, $periods[$uid]);
                if(!empty($message)){
                  msg_warning($message);
                }
              }
            }
          } // empty error form validate_periods
          else{
            $screen="INIT"; //Stay in this screen if periods are not valid
            $periods=unpack_periods($data_periods); //get periods from init screen, no from data_periods string
            
          }
        }
            if(empty($project_type)&& !empty($schedule_type))
              $project_type=$schedule_type;
            
            if($project_type=="init_est"){
              check_feasibility($cnx,$periods,$project_info["total_hours"]);
              if(empty($error)){
              $weeks=array();
              $this_week=$init_proj; //web format
              $acc_hours=0;
              $i=0;
             
              while(($acc_hours<$project_info["total_hours"]) && empty($error) && empty($warning)){

                $end_week=create_end_week($this_week);         
                if(!empty($users_project)){
                  foreach($users_project as $uid){
                    $weekly_load= get_weekly_load($this_week,$periods[$uid]); 
                    if($weekly_load!=-1){                   
                      $busy=busy_hours_this_week($cnx,$uid,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);
                      if(!empty($error)){
                        break(2);
                      }
                      if(!empty($warning)){
                        break(2);
                      }
                      $availability[$uid][$i]=$busy;
                    }
                    // previously edited weeks are set now
                    if((!empty($edited_weeks[$uid])) && (contains_date($this_week,$edited_weeks[$uid]))){                      
                      $hrs=get_edited_hours($cnx,$uid,$project,$this_week);
                      if($hrs==-1){                        
                        $error=_("Manual edited hours from user ".$uid." during week ".$this_week." was not found");
                      }
                      else{
                        $users_weeks[$uid][$i]=$hrs;
                        $edited=explode(",",$edited_cells);
                        array_push($edited,"users_weeks[".$uid."][".$i."]");
                        $edited_cells=implode(",",$edited);
                      }
                    }
                    else if($weekly_load!=-1)
                      $users_weeks[$uid][$i]=$weekly_load-$busy;  
                    if($users_weeks[$uid][$i]<0)
                      $users_weeks[$uid][$i]=0;
                    $acc_hours=$acc_hours+$users_weeks[$uid][$i];          
                  }//foreach
                }
                array_push($weeks,$this_week);
                $this_week= day_day_moved($end_week,1);
                $i++;
              }//end while 

              if($acc_hours!=$project_info["total_hours"]){
                if(!empty($users_project)){
                  $acc_last_week=0;
                  foreach($users_project as $uid){
                    $acc_last_week+=$users_weeks[$uid][count($weeks)-1];
                  }
                }            
                $acc_hours=$acc_hours-$acc_last_week;
                if(empty($error) && empty($warning)) // in order to avoid infinite loops because excess is distributed among users equally 
                  // assuming no error so far
                  prune($acc_hours,$i-2,$project_info["total_hours"]);
              }
              } //error:not feasible
            } //end if init_est
            else if($project_type=="init_end"){
            $weeks=create_consecutive_weeks($init_proj, $end_proj);
            $this_week=$init_proj;
            if(!empty($users_project)){
              foreach($users_project as $uid){
                for($i=0; $i<count($weeks);$i++){
                  $end_week=create_end_week($this_week);
                  $weekly_load= get_weekly_load($weeks[$i], $periods[$uid]);
                  if($weekly_load!=-1){
                    $busy=busy_hours_this_week($cnx,$uid,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);
                    if(!empty($error)){                    
                      break;                      
                    }
                    if(!empty($warning))
                      break;
                    $availability[$uid][$i]=$busy;
                  }
                  if(!empty($edited_weeks[$uid]) &&(contains_date($weeks[$i], $edited_weeks[$uid]))){
                    $hrs=get_edited_hours($cnx,$uid,$project,$weeks[$i]);
                    if($hrs==-1)
                      $error=_("Manual edited hours from user ".$uid." during week ".create_year_week($weeks[$i])." was not found");
                    else{
                      $users_weeks[$uid][$i]=$hrs;
                      $edited=explode(",",$edited_cells);
                      array_push($edited,"users_weeks[".$uid."][".$i."]");
                      $edited_cells=implode(",",$edited);
                    }
                  }
                  else if($weekly_load!=-1)
                    $users_weeks[$uid][$i]=$weekly_load-$busy;  
                  if($users_weeks[$uid][$i]<0)
                    $users_weeks[$uid][$i]=0;
                  $this_week=day_day_moved($end_week,1);
                }
              }
            }
          } //end init_end
       
      
    } //end generate for the first time

      else if(!empty($generate) && $old_screen=="EDITION"){ // GENERATE BUTTON WAS PRESSED    

        /** Checkbox logic to reset edited values to effective values **/
        if(!empty($reset_hours)){ //If any checkbox was active     
          $users=array_keys($reset_hours); //$reset is an associative array indexed by username
          for($i=0; $i<count($users);$i++){
            $user=$users[$i];
            $indexes=array_keys($reset_hours[$user]);
            for($j=0;$j<count($indexes);$j++){
            $index=$indexes[$j];
            $weekly_load= get_weekly_load($weeks[$index],$periods[$user]);
            if($weekly_load==-1){  
              unset($users_weeks[$user][$index]);
            }
            else{
              $this_week=$init_proj;   //find out init and end of current week
              $end_week=create_end_week($this_week); //init date of project does not have to be on Monday, so it is kept that way                       
              for($k=0; $k<$index; $k++){
                $this_week=day_day_moved($end_week,1); 
                $end_week=day_day_moved($this_week,6);
              }              
              //set effective hours
              $busy=busy_hours_this_week($cnx,$user,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);              
              $users_weeks[$user][$index]=$weekly_load-$busy;
            }
            // remove these cells from edited cells
            $edited=explode(",",$edited_cells);
            $found=indexOf("users_weeks[".$user."][".$index."]",$edited);
            if($found!=-1){              
              $edited=del_elements_shifting($edited,$found);
              $edited_cells=implode(",",$edited);
            }
          }
        }            
      }/** End checkbox logic **/

        if($project_type=="init_end"){ //repeat generation method
          if(!empty($users_project)){
            foreach($users_project as $uid){
              $this_week=$init_proj;
              for($i=0; $i<count($weeks);$i++){	
                $end_week=create_end_week($this_week);
                $weekly_load=get_weekly_load($weeks[$i],$periods[$uid]);
                if($weekly_load!=-1){
                  $busy=busy_hours_this_week($cnx,$uid,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);
                  $availability[$uid][$i]=$busy;                  
                  $this_week=day_day_moved($end_week, 1);
                }
              }
            }
          }
        }
        else if($project_type=="init_est"){
          check_feasibility($cnx,$periods,$project_info["total_hours"]);

          if(empty($error)){
          $acc=0;            //calculating hours invested in a schedule
            for($i=0; $i<count($weeks); $i++){
              if(!empty($users_project)){
                foreach($users_project as $uid){
                  $acc+=$users_weeks[$uid][$i];
                }
              }
            }
            //compute hours accumulated in last week
              if(!empty($users_project)){
                $acc_last_week=0;
                foreach($users_project as $uid){
                  $acc_last_week+=$users_weeks[$uid][count($weeks)-1];
                }
              }
            if($acc>$project_info["total_hours"]){  //more weeks than needed: keep values until goal(est_hours) is met
              $_acc=0;
              for($i=0;$i<count($weeks);$i++){
                if(!empty($users_project)){
                  foreach ($users_project as $uid){
                    $_acc=$_acc+$users_weeks[$uid][$i];
                  }
                  if($_acc>$project_info["total_hours"]){
                    $index=$i; //index of first week which exceeds est_hours
                    break;
                  }
                }
              }
              //delete unnecessary weeks
              if(!empty($users_project)){
                foreach($users_project as $uid){
                  array_splice($users_weeks[$uid],$index+1);
                }
              }
              array_splice($weeks, $index+1);
              
              $_acc=$_acc-$acc_last_week; 
              if(empty($error)&& empty($warning))// in order to avoid infinite loops because excess is distributed among users assuming no error has occurred
                prune($_acc,count($weeks)-2,$project_info["total_hours"]);
            }
            else if($acc<$project_info["total_hours"]){ //less weeks than needed: add more weeks
              $acc-=$acc_last_week;
              reschedule($cnx,$init_proj,$acc,$project_info["total_hours"]);
              
            }
          }// error feasible
        } //end if project type == init-est
      }
 
    if(empty($generate) && empty($save)){ //if generate and save were not pressed, availability array is created anyway: 
      if(($screen!="INIT")){//it is needed in $screen=GENERATED to keep checkboxes logic
        if(!empty($users_project)){  	
          foreach($users_project as $uid){  
            $availability["$uid"]=find_availability($cnx,$uid,$init_proj,$weeks);
          }
        }
      }
    }
  
    /* Get practical or real work */
    $users_weeks_prac=array();
    if(!empty($users_project)){
      foreach($users_project as $uid){
        $hours_weeks_prac=array();
        $init_current_week=$project_info["init"];//sql format 
        for($i=0;$i<count($weeks);$i++){	  
          $minutes= worked_minutes_this_week_during_project($cnx,$uid,$init_current_week,$project_info["init"],$project_info["_end"],$project);
          $hours_2dec= round(($minutes/60)*100)/100;//get number of hours with two decimals
          array_push($hours_weeks_prac,$hours_2dec);   
          $init_current_week=day_day_moved(date_sql_to_web($init_current_week),7);
          $init_current_week=date_web_to_sql($init_current_week);
        }
        $users_weeks_prac[$uid]=$hours_weeks_prac;	  
      }
    }
  }//end else project!="---"
 }//end if $projects empty: 1st time this page is loaded.

 // SAVE BUTTON  		
    if(!empty($save) && empty($error) && empty($warning)){
      //first of all, validate text fields
      foreach($users_project as $uid){
        for($i=0;$i<count($weeks);$i++){
          if((!empty($users_weeks[$uid][$i]) && validate_text_fields($users_weeks[$uid][$i])==-1)){
            $error=_("Invalid values found. Save process is not possible.");
            break(2);
          }
        }
      }
      if(empty($error)){
        if (!@pg_exec($cnx,$query="SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
                      ."BEGIN TRANSACTION; ")) {
          $error=_("Can't finalize the operation: ".$query);
        }
        /* if schedule already done for this project delete everything previous*/
          if (!@pg_exec($cnx,$query=
                        "DELETE FROM project_schedule WHERE pid='$project'")) {
            $error=_("Can't finalize the operation".$query);
          }
          if (!@pg_exec($cnx,$query=
                        "DELETE FROM edited_project_schedule WHERE pid='$project'")) {
            $error=_("Can't finalize the operation".$query);
          }
          // reset schedule type in projects table
          if (!@pg_exec($cnx,$query=
                        "UPDATE projects SET sched_type='' where id='$project'")) {
            $error=_("Can't finalize the operation".$query);
          }
          if (!empty($error)) {
            @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
          }
          else{	
            if(!empty($users_project)){
              foreach($users_project as $uid){
                if(!empty($periods[$uid])){
                  foreach($periods[$uid] as $p){
                    if(!pg_exec($cnx, $query="INSERT INTO project_schedule(uid, pid, init_week, end_week,weekly_load)
		   VALUES('$uid','$project',to_timestamp('".$p["init"]."','DD/MM/YYYY'),to_timestamp('".$p["_end"]."','DD/MM/YYYY'),'".$p["weekly_ded"]."')"))
                      $error=_("Can't finalize the operation: ".$query);  			
                    if (!empty($error)) {
                      @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
                      break(2);
                    }     
                  }
                }
                 
                for($i=0;$i<count($weeks);$i++){	
                  $edited=explode(",",$edited_cells);
                  $edited=array_unique($edited);                    
                  if(in_array("users_weeks[".$uid."][".$i."]",$edited)){
                    $hrs=$users_weeks[$uid][$i];
                    if(!empty($hrs)||($hrs==0)){
                      if(!pg_exec($cnx, $query="INSERT INTO edited_project_schedule(uid, pid, year_week, edited_hours)
		   VALUES('$uid','$project',to_timestamp('$weeks[$i]','DD/MM/YYYY'),$hrs)"))
                        $error=_("Can't finalize the operation: ".$query);  			
                      if (!empty($error)) {
                        @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
                      break(2);
                      }
                    }
                  }
                }
              }
            }
            if(!empty($error)){            
              @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
            } 
            else{
              if(!pg_exec($cnx,$query="UPDATE projects set sched_type='$project_type' where id='$project'"))
                $error=_("Can't finalize the operation: ".$query);
              if(!empty($error)){
                @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
              }
              else{
                @pg_exec($cnx,$query="COMMIT TRANSACTION");		
                $ok=_("Changes have been saved correctly");			
              }
            }
          }//else
        }// valid values
      if(!empty($error))
        msg_fail($error);
    }// end save 
   
/* Select projects from projects table */
/* Maybe only active projects would be enough, but if we want to see one planning already done? */
$result= @pg_exec($cnx,$query="SELECT id FROM projects ORDER BY id")
  or exit("Can't finalize this operation. ".$query);
$projects=array();
$projects[]="---";
while($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)){
  $projects[]=$row["id"];
 }
@pg_freeresult($result);

/*Create an array to show in combo dedication hours*/
$dedication_hours=array();
for($i=1; $i<=max_week_hours; $i++){
  array_push($dedication_hours,$i);
 }

/*********** USEFUL FUNCTIONS **********/

/* param: $date: date in a string with the format DD/MM/YYYY
 * get_ISO_year returns ISO-8601 year number in 4-digit representation.
 * In general is the same value of the year, except that if the ISO week number belongs to the previous
 * or next year, that year is used instead.
 */
function get_ISO_year($date) {
    $date_DMA= date_web_to_arrayDMA($date);
    $ts= mktime(0,0,0,$date_DMA[1],$date_DMA[0],$date_DMA[2]);

  if(version_compare(PHP_VERSION, '5.1', '<')) {
    $year=$date_DMA[2];
    $week=date('W', $ts);
    if($date_DMA[1]==12&&$week==1) $year+=1;
    if($date_DMA[1]==1&&$week>=52) $year-=1;
  } else {
    $year=date('o', $ts);
  }
  return $year;
}
/* param: $week_user: variable which contains both week and user concatenated by means of week_user_separator
 * get_user_from_link returns user from $week_user variable and NULL otherwise 
 */
function get_user_from_link($week_user){
  if(empty($week_user))
    return NULL;
  $week_user=explode(week_user_separator,$week_user);
  return $week_user[1];
}
/* param: $week_user: variable which contains both week and user concatenated by means of week_user_separator
 * get_week_from_link returns week from $week_user variable and NULL otherwise 
 */
function get_week_from_link($week_user){
  if(empty($week_user))
    return NULL;
  $week_user=explode(week_user_separator,$week_user);
  return $week_user[0];
}
/* param: $cnx: database connection
 * param: $uid: user id
 * param: $project: project id
 * param: $periods: data structure which contains $uid's  periods
 * check_conflicts queries database to find out which periods which overlap somehow any $uid period 
 * involve more than default_working_week hours/week.
 * It creates an array to display a list of period conflicts per $uid period.
 */
function check_conflicts($cnx,$uid,$project,$periods){ 
  if(empty($uid) || empty($project) || empty($periods)|| empty($cnx))
    return NULL;
  foreach($periods as $p){
    $result= @pg_exec($cnx,$query="SELECT to_char(init_week,'DD/MM/YYYY') as init_week, to_char(end_week,'DD/MM/YYYY') as end_week, pid, weekly_load  FROM project_schedule 
 where (uid='$uid') and (pid!='$project')
 and (to_char(init_week,'DD/MM/YYYY')!='".$p["init"]."')
 and (to_char(end_week, 'DD/MM/YYYY')!='".$p["_end"]."')
 and (init_week, (end_week + interval '7 days')) overlaps (to_timestamp('".$p["init"]."','DD/MM/YYYY'), (to_timestamp('".$p["_end"]."','DD/MM/YYYY')+ interval '7 days'))")
      or $error=_("Can't finalize this operation. ".$query);
    
    while($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)){
      $pi=array("init"=>$row["init_week"],
                "_end"=>$row["end_week"],
                "pid"=>$row["pid"],
                "weekly_load"=>$row["weekly_load"]);  
  
      if(($pi["weekly_load"]+$p["weekly_ded"]) >default_working_week){
       $conflicts.= "<li>Period [".create_year_week($pi["init"]).", ".create_year_week($pi["_end"])
         ."] in project ".$pi["pid"]." with ".$pi["weekly_load"]." hours/week.</li>".$conflicts;
      }
    }
    @pg_freeresult($result);
    if(!empty($conflicts))
      $conflicts= "Period [".create_year_week($p["init"]).", ".create_year_week($p["_end"])."] has the following conflicts for user ".$uid.": <ul>".$conflicts."</ul>";
  }
  return $conflicts;
}
/* param: $cnx: database connection
 * param: $uid: user id
 * param: $project: project id
 * param: $current_week: web date that matches the Monday of the corresponding week 
 *        or the beginning of the project (if project does not start on Monday)
 * get_edited_hours queries database to find out which dates have been manually edited for $project and $uid 
 * it returns edited hours for the $current_week or -1 or if any error has occurred 
 */
function get_edited_hours($cnx,$uid,$project,$current_week){

  $result= @pg_exec($cnx, $query="SELECT edited_hours FROM edited_project_schedule WHERE pid='$project' AND uid='$uid' 
  AND year_week=to_timestamp('$current_week','DD/MM/YYYY')")
    or $error=_("Can't finalize this operation. ".$query);
 
  while($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)){
    return $row["edited_hours"];
  }
  if(!empty($error))
    return -1;
}
/* param: $web_date. Date in web foramt DD/MM/YYYY or D/M/YYYY
 * param: $dates_array: Array of web dates in DD/MM/YYYY or D/M/YYYY format
 * contains_date returns a boolean value according to if $dates_array contains $web_date
 * this function was created to ensure that the same date in DD/MM/YYYY or in D/M/YYYY format could 
 * be compared and found correctly.
 */
function contains_date($web_date,$dates_array){
  if(empty($web_date) || empty($dates_array))
    return FALSE;
  foreach($dates_array as $d){   
    if(cmp_web_dates($d, $web_date)==0)
      return TRUE;
  }
  return FALSE;
}
/* param: $cnx. database connection
 * param: $project.$project id.
 * get_edited_weeks queries db to get those dates which had been edited for project $project.
 * it returns an array of edited week dates (starting on Mondays unless first week in case 
 * beginning of project is not on Monday) or -1 if any error occurs.
 */
function get_edited_weeks($cnx,$project){
  if(empty($cnx) || empty($project))
     return -1;
  
  // select edited values
  $result= @pg_exec($cnx, $query="SELECT uid, to_char(year_week, 'DD/MM/YYYY') as year_week, edited_hours FROM edited_project_schedule WHERE pid='$project'
                  ORDER BY uid, year_week")
    or $error=_("Can't finalize this operation. ".$query);

  $edited_weeks=array();
  $users=array();//tmp array
  $i=0; //to control if some year_week was edited
  while($row=@pg_fetch_array($result,NULL,PGSQL_ASSOC)){
    $i++;
    if(!in_array($row["uid"],$users)){
      array_push($users,$row["uid"]);
      $edited_weeks[$row["uid"]]=array();
    }
    array_push($edited_weeks[$row["uid"]],$row["year_week"]);
  }
  unset($users);
  @pg_freeresult($result);

  if(empty($error) && ($i>=0))
    return $edited_weeks;
  else
    return -1;
}

/* param: $web_date
 * create_year_week creates a string in yyyy_ww format (year_week) from a date in web format 
 */
function create_year_week($web_date){
  if(!empty($web_date)){    
    $dma_init=date_web_to_arrayDMA($web_date);
    $current_year=get_ISO_year($web_date);
    $w=date('W',mktime(0,0,0,$dma_init[1], $dma_init[0], $dma_init[2]));	
    $current_year_week= $current_year.year_week_separator.$w;
    return $current_year_week;
  }
}
/*
 * param: $cnx.database connection
 * param: $init_proj. Date of the beginning of the project in web format.
 * param: $acc_hours. Amount of hours accumulated in the previous schedule
 * param: $est_hours. Amount of hours estimated for a given project.
 * reschedule keeps old values of a init-est schedule and add more weeks to the schedule taking into account busy-hours
 * 
 */

function reschedule($cnx,$init_proj,$acc_hours,$est_hours){
  global $weeks, $users_project, $users_weeks,$error, $warning, $availability, $periods;
  $this_week= $weeks[count($weeks)-1];

  //$this_week starts now  on next Monday after last week of an already displayed schedule
  $i=count($weeks)-1;//last week
  array_pop($weeks); // eliminate last week (it'll be inserted once again during loop)

  while(($acc_hours<$est_hours) && (empty($error)) && (empty($warning))){
    $end_week=create_end_week($this_week);
    if(!empty($users_project)){
      foreach($users_project as $uid){        
        $weekly_load=get_weekly_load($this_week,$periods[$uid]);
      
        if($weekly_load!=-1){

          $busy=busy_hours_this_week($cnx,$uid,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);
            $availability[$uid][$i]=$busy;
            if(!empty($error)) 
              return;            
            if(!empty($warning))
              return;
            $users_weeks[$uid][$i]=$weekly_load-$busy;

        }
        if($users_weeks[$uid][$i]<0)
          $users_weeks[$uid][$i]=0;
        $acc_hours=$acc_hours+$users_weeks[$uid][$i];     
      }
    } 
    array_push($weeks, $this_week);
    $this_week= day_day_moved($end_week,1);
    $i++;
  }//end while

  if($acc_hours!=$est_hours){
    if(!empty($users_project)){
      $acc_last_week=0;
      foreach($users_project as $uid){
        $acc_last_week+=$users_weeks[$uid][count($weeks)-1];
      }
    }
    $acc_hours=$acc_hours-$acc_last_week;
    if(empty($error) && empty($warning))// in order to avoid infinite loops
      prune($acc_hours,$i-2,$est_hours);
  }
}

/*
 * param: $acc. Amount of hours accumulated in a schedule, so far
 * param: $i.Index of the last week which does NOT exceed the estimated hours amount
 * param: $est_hours. Amount of hours a project was estimated
 * prune resets the last week to zero and distributes as equal as possible the excess of hours
 * among users.
 */
function prune($acc,$i,$est_hours){
  global $users_weeks, $users_project, $periods,$weeks, $error;

  if(!empty($users_project)){
    foreach($users_project as $uid){
      $users_weeks[$uid][$i+1]=0;
    }
  }

  while($acc<$est_hours){
    if(!empty($users_project)){
      foreach($users_project as $uid){
        $weekly_load=get_weekly_load($weeks[$i+1],$periods[$uid]);
        if($weekly_load==-1) // Distribute only among users who have active periods.
          continue;
        $users_weeks[$uid][$i+1]++;
        $acc++;
        if($acc>$est_hours){
          $users_weeks[$uid][$i+1]--;
          $acc--;
          $excess=$est_hours-$acc;
          $users_weeks[$uid][$i+1]+=$excess;
          $acc++; // to stop while
          break;
        }
        $users_weeks[$uid][$i+1]=round($users_weeks[$uid][$i+1]*100)/100;//2 decimals
      }
    }
  }

}

/* param: $init_week. Date in web format.
 * create_end_week adds the suitable amount of days 
 * to reach the end of the week (Sunday) depending on the week day (Monday, Tuesday...)
 */
 function create_end_week($init_week){
  $init_dma=date_web_to_arrayDMA($init_week);
  $ts=mktime(0,0,0,$init_dma[1],$init_dma[0],$init_dma[2]);
  $week_day=date('w',$ts);
  if($week_day==0) $week_day=7; //'w' in date returns 0 for Sunday
  $days2move=abs($week_day-7);
  return day_day_moved($init_week, $days2move);
}
/*
 * param: $yyyy_ww.string year_week in yyyy[year_week_separator]ww format
 * get_week returns the string corresponding to the week
 */
function get_week($yyyy_ww){
  return substr($yyyy_ww,5,2);
}
/*
 * param: $yyyy_ww.string year_week in yyyy[year_week_separator]ww format
 * get_year returns the string corresponding to the year
 */
function get_year($yyyy_ww){
  return substr($yyyy_ww,0,4);
}
/*
 * param: $init_date.Date in web format where dates generation starts
 * param: $end_date.Date in web format where dates generation ends
 * create_consecutive_weeks creates an array of dates in web format with the begining of weeks: 
 * weeks start on Monday but the first one, which beginning is established by project beginning
 */
function create_consecutive_weeks($init_date, $end_date){
  if(empty($init_date)|| empty($end_date))
    return NULL;

  $week_dates=array();
  array_push($week_dates, $init_date);//insert date of project beginning 
  $current_date=create_end_week($init_date); //$current_date is the date of the end of the week
  $current_date=day_day_moved($current_date,1);//beginning of next_week
  while(cmp_web_dates($current_date,$end_date)!=1){
    array_push($week_dates,$current_date);
    $current_date=day_day_moved($current_date,7);
  }
  return $week_dates;
}
/*
 * param: $week_dates. Array of dates in web_format
 * create_combo_values creates an array of strings in year_weak format from the web dates
 */
function create_combo_values($week_dates){
 
  if(empty($week_dates))
    return NULL;

  $combo_values=array();  
  for($i=0;$i<count($week_dates);$i++){
    $value=create_year_week($week_dates[$i]);
    array_push($combo_values,$value);
  }
  return $combo_values;
}
/*
 * param: $web_date: Date in web format
 * get_year_from_web_date returns the corresponding year from the web date
 */
function get_year_from_web_date($web_date){
  if(empty($web_date))
    return NULL;
  return get_ISO_year($web_date);
}
/*
 * param: $web_date: Date in web format
 * get_week_day_from_web_date returns the corresponding day of the week from the web date
 */
function get_week_day_from_web_date($web_date){
  if(empty($web_date))
    return NULL;
  $dma=date_web_to_arrayDMA($web_date);
  $ts=mktime(0,0,0,$dma[1], $dma[0], $dma[2]);
  $week_day=date('N',$ts);
  return $week_day;
}
/*
 * param: $web_date: Date in web format
 * get_week_from_web_date returns corresponding week number from the web date
 */
function get_week_from_web_date($web_date){
  if(empty($web_date))
    return NULL;
  $dma=date_web_to_arrayDMA($web_date);
  $ts=mktime(0,0,0,$dma[1], $dma[0], $dma[2]);
  $week=date('W',$ts);
  return $week;
}
/*
 * param: $cn: db connection
 * param: $users: array of users id
 * param: $init:date in sql format
 * param: $end: date in sql format
 * get_journeys queries the db to get which journey users have from $init til $end (both in sql format)
 * BE CAREFUL: In case several periods overlap with project duration, it only retrieves the first of all
 * It returns an array indexed by user id of integers: journeys
 */
function get_journeys($cn,$users,$init, $end){
  if(!empty($users)){
    foreach($users as $uid){  
      if(!$result=pg_exec($cn,$query=" SELECT journey FROM periods WHERE init=
 (SELECT MIN(INIT) FROM periods WHERE uid='$uid' AND 
    ((init,_end) overlaps (DATE '$init', DATE '$end')))")){
        $error=_("Can't finalize this operation" .$query); 	
      }
      if(empty($error)){
        while($row=pg_fetch_array($result, NULL, PGSQL_ASSOC)){
          $journey=$row["journey"];
          $journeys[$uid]=$journey;
        }
        @pg_freeresult($result);
      }
      else{
        msg_fail($error);
        break;
      }
    }//foreach
  }
  return $journeys;
}

/*
 * param: $cn: db connection
 + param: $u. user id
 * param: $init_week: date in sql format
 * param: $end_week.date in sql format
 * param: $weekly_load: amount of hours a user is willing to work per week on a given project
 * busy_hours_this_week queries db to get busy hours a user $u has during a given week $init_week til $end_week 
 * busy hours are broken down as follows:
 * amount of festive days * 8 hours
 * amount of busy_hours during a given week
 */
function busy_hours_this_week($cn,$u, $init_week, $end_week,$weekly_load){
  global $error, $warning, $project;

  $daily_ded=$weekly_load/5;
  
  /* Look for contract periods of this user which overlap duration of specified period[init_week, end_week] */  

  if(!$result=pg_exec($cn,$query=" SELECT city, journey, init, _end FROM periods WHERE init=
 (SELECT MIN(INIT) FROM periods WHERE uid='$u' AND 
    ((init,_end) overlaps (DATE '$init_week)', DATE '$end_week')))")){
    $error=_("Can't finalize this operation" .$query); 	
  }  
  if(empty($error)){    
    while($row=pg_fetch_array($result, NULL, PGSQL_ASSOC)){
      $city=$row["city"];				
      $_end=$row["_end"];//$_end can be null because of periods insertion system
      if($_end==null)
	$_end=$end_week;						
      $init=$row["init"];					
      $journey=$row["journey"];
     
      if(empty($journey)||($journey=="")){
        $journey=8; //let's suppose it is full-time job
        $journey_warning=true;
      }
    }
    @pg_freeresult($result);
  
    /* Find out fest from the city the user is assigned to during week:[init_week, $end_week] */
    if(($init=="")&&($_end==""))
      $warning=_("Warning: There is no contract period for user ".$u." during : [".date_sql_to_web($init_week)." , ".date_sql_to_web($end_week)."]");
     if(empty($warning)){
    $result=@pg_exec($cn, $query="SELECT fest FROM holiday WHERE fest>='$init_week' AND fest<='$end_week' AND
           city='$city'")
      or $error=_("Can't finalize this operation: ". $query);
    if(empty($error)){
      $fest=array();
      while($row=pg_fetch_array($result, NULL,PGSQL_ASSOC)){
        array_push($fest,$row["fest"]);
      }
      @pg_freeresult($result);				
      /* Find busy hours in this week*/				
      $result=@pg_exec($cn, $query="SELECT date, hours FROM busy_hours WHERE date>='$init_week' AND date<='$end_week' AND uid='$u'")
        or $error=_("Can't finalize this operation: ".$query);
      
      if(empty($error)){
        $dates=array();
        $hours=array();
        while($row=pg_fetch_array($result, NULL, PGSQL_ASSOC)){
          array_push($dates,$row["date"]);
          array_push($hours,$row["hours"]);
        }
        @pg_freeresult($result);  
        $fest_count=count($fest);     		
        $events_hours=0;
        if(!empty($dates)){
          foreach($dates as $f){		
            $index=indexOf($f, $dates);		
            if(($index!=-1)){
              $events_hours+=$hours[$index];		    
            }
          }
        }
        if($journey==0)
          $journey=8;
        $busy_hours=$fest_count*$daily_ded +($events_hours*$daily_ded)/$journey; 
        if($journey_warning)
          $warning="No journey associated to user ".$u.". Full-time job is assumed. Check journey in section \"User management\".";
      }
    }
     }
  }
  if(!empty($error)){
    $busy_hours=0;
  }
  return $busy_hours;
}
/*
 * param: $cn: db connection
 * param: $init_week: date in sql format
 * param: $end_week.date in sql format
 * param: $weekly_load: amount of hours a user is willing to work per week on a given project
 * busy_hours_break_down queries db to get busy hours a user $u has during a given week $init_week til $end_week 
 * busy hours are broken down as follows:
 * amount of festive days * 8 hours
 * amount of busy_hours during a given week
 * it creates an array of dates, description, absolute hours dedicated to a given event and the relative hours that a project is gonna suffer as a result.
 */
function busy_hours_break_down($cn,$u, $init_week, $end_week,$weekly_load){
  global $error, $warning, $project;

  $rows=array();
  $rows[0]=array("Date", "Description", "Abs.Hours", "Rel. Hours");
  $daily_ded=$weekly_load/5;
  
  if(!$result=pg_exec($cn,$query=" SELECT city, journey, init, _end FROM periods WHERE init=
 (SELECT MIN(INIT) FROM periods WHERE uid='$u' AND 
    ((init,_end) overlaps (DATE '$init_week)', DATE '$end_week')))")){
    $error=_("Can't finalize this operation" .$query); 	
  }
  if(empty($error)){    
    while($row=pg_fetch_array($result, NULL, PGSQL_ASSOC)){
      $city=$row["city"];				
      $_end=$row["_end"];//$_end can be null because of periods insertion system
      if($_end==null)
	$_end=$end_week;						
      $init=$row["init"];
      $journey=$row["journey"];
      if(empty($journey)){
        $journey=8; //let's suppose it is full-time job
        $journey_warning=true;
      }
    }
    @pg_freeresult($result);
  
    /* Find out fest from the city the user is assigned to during week:[init_week, $end_week] */
    if(($init=="")&&($_end==""))
      $warning=_("Warning: There is no contract period for user ".$u." during : [".date_sql_to_web($init_week)." , ".date_sql_to_web($end_week)."]");
     if(empty($warning)){
    $result=@pg_exec($cn, $query="SELECT fest FROM holiday WHERE fest>='$init_week' AND fest<='$end_week' AND
           city='$city'")
      or $error=_("Can't finalize this operation: ". $query);
    if(empty($error)){
      while($row=pg_fetch_array($result, NULL,PGSQL_ASSOC)){
        $rows[count($rows)]=array(date_sql_to_web($row["fest"]),"festive",8,$daily_ded);
      }
      @pg_freeresult($result);				
      /* Find free hours for the total period of this user during period contract*/				
      $result=@pg_exec($cn, $query="SELECT date, hours, ev_type FROM busy_hours WHERE date>='$init_week' AND date<='$end_week' AND uid='$u'")
        or $error=_("Can't finalize this operation: ".$query);
      
      if(empty($error)){       
        while($row=pg_fetch_array($result, NULL, PGSQL_ASSOC)){
          $rows[count($rows)]= array(date_sql_to_web($row["date"]),$row["ev_type"], $row["hours"], (($row["hours"]*$daily_ded)/$journey));
        }
        @pg_freeresult($result);  
      } 
    }
     }
  }
  if(!empty($error)){
    $busy_hours=0;
  }
  if($journey_warning)
    $warning="No journey associated to user ".$u.". Full-time job is assumed. Check journey in section \"User management\".";
  return $rows;
}

/*
 * param: $content.
 * validate_text_fields checks that content inside cells is numeric and belonging to [0, max_week_hours]
 */
function validate_text_fields($content){

  if(is_numeric($content)){
    if(($content>=0) && ($content<=max_week_hours))
      return $content;
    else
      return -1;
  }
  else 
    return -1;
}
/*
 * param: $cn: db connection
 * param: $u: user id
 * param: $init_p: date in web format corresponding to the beginning of the project
 * find_availability calls busy_hours_this_week and fills in an array with availability for $u during $weeks duration
 */
function find_availability($cn,$u, $init_p,$weeks){
  global $periods, $error,$warning;
  
  $this_week=$init_p; //web format             
  $end_week=create_end_week($this_week);
  $availability=array();
  for($i=0; $i<count($weeks); $i++){   
    $week=create_year_week($this_week);    // find out weekly_load
    $weekly_load=get_weekly_load($this_week,$periods[$u]);
    if($weekly_load!=-1){
      $availability[$i]=busy_hours_this_week($cn,$u,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);
      if(!empty($error)) 
        break;
    }
    $this_week=day_day_moved($end_week,1);
    $end_week=create_end_week($this_week);

  }
  return $availability;
}
/*
 * param: $year.A year in 4 digits format.
 * param: $weeks.array of dates in web format
 * count_weeks_year computes the number of weeks that belong to a given year
 */
function count_weeks_year($year,$weeks){
  $count=0;
 
  for($i=0; $i<count($weeks); $i++){
    $yyyyi=get_year_from_web_date($weeks[$i]);
    if($yyyyi==NULL)
      return 0;
    if($yyyyi==$year)
      $count++;   
  }
  return $count;
}
/*
 * param: $users_project. Array of users id
 * param: $init_proj: date in web format
 * param: $end_proj: date in web format
 * init_periods creates an array of periods with init=beginning of project, 
 * end=  end of project and weekly_ded=default_working_week
 */
function init_periods($users_project, $init_proj, $end_proj){
  global $periods;

  if(empty($users_project) || empty($init_proj) || empty($end_proj)){
    unset($periods);
    return $periods;
  }  
  if(!empty($users_project)){
    foreach($users_project as $uid){
      $periods[$uid]=array();
      $period=array("init"=>$init_proj,
                    "_end"=>$end_proj,
                    "weekly_ded"=>default_working_week);      
      array_push($periods[$uid],$period);      
    }    
  }
  return $periods;
}
/*
 * param: $periods.Array of periods indexed by users id
 * param: $user: User id
 * show_periods creates html code to show periods in a table
 */
function show_periods($periods, $user){
;
  global $dedication_hours, $ded_per_week,$weeks,$from, $to, $error;
  if(!empty($periods[$user])){
    $i=0;
    foreach($periods[$user] as $p){

      //weekly_ded field
      echo("<td align=\"center\"><b><select name=\"ded_per_week[$user][$i]\">");
      if(empty($ded_per_week[$user][$i]))
        $selected_value=$p["weekly_ded"];
      else
        $selected_value=$ded_per_week[$user][$i];
      echo array_to_option($dedication_hours,$selected_value, NULL);
      echo("</select></b></td>");
      
      //from field
 
      echo("<td><b><select name=\"from[$user][$i]\">");
      if(empty($from[$user][$i]))
        $selected_value=create_year_week($p["init"]);  
      else
        $selected_value=$from[$user][$i]; 
   
      $combo=create_combo_values($weeks);
      if($combo==NULL)
        $error=_("Week dates could not be computed properly.");
      echo array_to_option($combo,$selected_value,NULL);
      echo("</select></b></td>");

      //to field
    
    
      echo("<td ><b><select name=\"to[$user][$i]\">");
      if(empty($to[$user][$i]))
        $selected_value=create_year_week($p["_end"]);
      else
        $selected_value=$to[$user][$i];      
      if($combo==NULL)
        $error=_("Week dates could not be computed properly.");
      echo array_to_option($combo,$selected_value,NULL);
      echo("</select></b></td>");
        
      if($i==0) //Only one "Add period" button per user
        echo ("<td><b><input type=\"submit\" name=\"add_period[$user]\" value=\"Add period\"></b></td>");
      else{
        echo("<td></td>");
        if($i>0) //"Delete period" button in all periods but the first one
          echo ("<td><b><input type=\"submit\" name=\"delete_period[$user][$i]\" value=\"Delete period\"></b></td>");
        else
          echo("<td><b></b></td>");
      }
      if((count($periods[$user])-1)!=$i)
        echo "</tr>";
      $i++;
      if(count($periods[$user])>$i)
        echo("<td></td> <td> </td>");
    }
  }
}
/*
 * param:$year_week string in year_week format (yyyy_ww)
 * param:$init_proj.web date of the beginning of the project
 * year_week_to_web_date creates a date in web format from yyyy_ww format matching Mondays of week ww,
 * unless ww belongs to the same week as $init_proj,where $init_proj is returned.
 */
function year_week_to_web_date($year_week, $init_proj){

  if(empty($year_week)|| empty($init_proj))
    return NULL;

  $tmp_year_week=create_year_week($init_proj);
  if($tmp_year_week==$year_week)
    return $init_proj;
  if(cmp_year_weeks($year_week,$tmp_year_week)==-1)
    return NULL; 

  $end_week= create_end_week($init_proj);
  $next_week= day_day_moved($end_week,1);//$next_week points to Monday of next week
  
  while(create_year_week($next_week)!=$year_week){
    $next_week=day_day_moved($next_week,7);
  }
  return $next_week;
}
/*
 * param: $periods,array indexed by users id which contains periods with init date, end date and weekly_load
 * update_periods fills in $periods data structure with visible data from table Project Users
 */
function update_periods($periods){
  global $from, $to, $ded_per_week, $users_project, $init_proj;
  if(!empty($users_project)){
    foreach($users_project as $uid){
      unset($periods[$uid]);
      $periods[$uid]=array();
      for($i=0;$i<count($from[$uid]);$i++){        
        $p=array("init"=>year_week_to_web_date($from[$uid][$i],$init_proj),
                 "_end"=>year_week_to_web_date($to[$uid][$i],$init_proj),
                 "weekly_ded"=>$ded_per_week[$uid][$i]);
        array_push($periods[$uid],$p);
      }      
    }
    return $periods;
  }
  return NULL;
}
/*
 * param: $users_project.array of users id
 * param: $periods, array indexed by users id which contains periods with init date, end date and weekly_load
 * pack_periods creates a string with all data from $periods data structure separated by several separators
 */
function pack_periods($users_project, $periods){
  global $screen, $from;  
  if(!empty($from))
    $periods=update_periods($periods);    
  
  if(!empty($users_project)&&!empty($periods)){
    if(!empty($periods)){
      $users_data=array();
      foreach($users_project as $uid){  
        $separated_periods=array();
        if(!empty($periods[$uid])){
          foreach ($periods[$uid] as $p){
            array_push($separated_periods, implode(data_periods_separator,$p));//data periods separated
          }       
          $separated_periods=implode(periods_separator,$separated_periods);//periods are separated
          $user_periods=array();
          array_push($user_periods, $uid);
          array_push($user_periods,$separated_periods);
          $user_data=implode(user_periods_separator,$user_periods);
          array_push($users_data, $user_data);
        }
      }
      return  implode(users_separator,$users_data);
    }
    else return NULL;
  }
  else return NULL;
}
/*
 * param: $string which contains periods data separated by several separators
 * unpack_periods creates an array indexed by users id with, init, end and weekly_load from 
 * a string contained in a hidden input value if screen!=INIT, 
 * from $from, $to and $ded_per_week otherwise.
 */
function unpack_periods($string){
  global  $from, $to, $ded_per_week, $users_project, $screen,$init_proj;


  if(!empty($from)){ //if data is available, ignore string 
    if(!empty($users_project)){
      foreach($users_project as $uid){
        $periods[$uid]=array();
        for($i=0;$i<count($from[$uid]);$i++){
          $p=array("init"=>year_week_to_web_date($from[$uid][$i],$init_proj),
            "_end"=>year_week_to_web_date($to[$uid][$i],$init_proj),
          "weekly_ded"=>$ded_per_week[$uid][$i]);        
          array_push($periods[$uid],$p);
        }
      }
    }
    
    return $periods;
  }
  else{  
    if(!empty($string)){
      $users=explode(users_separator,$string);// $users contains [uid p1 p2...pn]
      $array_periods=array();
      for($i=0; $i<count($users); $i++){
        $user_periods=explode(user_periods_separator,$users[$i]);// $users_periods contains [uid
        $uid= $user_periods[0];
        $periods_list=$user_periods[1];
        $periods=explode(periods_separator,$periods_list);        
        $array_periods[$uid]=array();        
        for($j=0;$j<count($periods); $j++){
          $data_period=explode(data_periods_separator,$periods[$j]);
          $p=array("init"=>$data_period[0],
                   "_end"=>$data_period[1],
                   "weekly_ded"=>$data_period[2]);         
          array_push($array_periods[$uid],$p);
        }      
      }
      return $array_periods;
    }
    else
      return NULL;
  }
}
/*
 * It validates periods: $to>=$from
 * Consecutive periods can't overlap.
 */
function validate_periods(){
  global $from, $to, $ded_per_week, $users_project, $error, $periods, $init_proj,$schedule_type,$cnx, $project_info;
  
  //create a structure with from, to values
  if(!empty($users_project)){
    foreach($users_project as $uid){
      $pers[$uid]=array();
     
      for($i=0;$i<count($from[$uid]);$i++){
             $p=array("init"=>$from[$uid][$i],
                 "_end"=>$to[$uid][$i],
                 "weekly_ded"=>$ded_per_week[$uid][$i]);
        
        array_push($pers[$uid], $p);
      }
    }
  }
  if(!empty($users_project)){
    foreach($users_project as $uid){
      $pers[$uid]=sort_periods($pers[$uid]);
    }
  }
  if(!empty($users_project)){
    foreach($users_project as $uid){
      for($i=0;$i<count($pers[$uid]);$i++){
        //valid periods?
        $result=cmp_year_weeks($pers[$uid][$i]["init"],$pers[$uid][$i]["_end"]);
        if(($result==2) || ($result==1)){
          $error=_("[".$pers[$uid][$i]["init"].", ".$pers[$uid][$i]["_end"]."] is not a valid interval");
          break(2);
        }
      }
    }
    //end periods validation 
    //overlapping validation
    foreach($users_project as $uid){
      for($i=0;$i<count($pers[$uid]);$i++){
        //valid periods?
        if($i>0){
          $result=cmp_year_weeks($pers[$uid][$i]["init"],$pers[$uid][$i-1]["_end"]);
          if($result!=1){
            $error=_("Consecutive periods can't overlap: [".$pers[$uid][$i-1]["init"].", ".$pers[$uid][$i-1]["_end"]."]&cap;[".
                     $pers[$uid][$i]["init"].", ".$pers[$uid][$i]["_end"]."]");

            break(2);
          }
        }
      }
    }
    //end overlapping validation
  }
  if($schedule_type=="init_est"){
  // Find out if periods and weekly load can reach estimated hours 
    if(!empty($users_project)){
      $acc=0;
      foreach($users_project as $uid){
        for($i=0;$i<count($pers[$uid]);$i++){
          $init_date=year_week_to_web_date($pers[$uid][$i]["init"],$init_proj);
          $end_date=year_week_to_web_date($pers[$uid][$i]["_end"],$init_proj);
          $days=elapsed_days($init_date,$end_date);
          $days+=7; // because end of period refers to the beginning of the week
          $elapsed_weeks=ceil($days/7); // ceil in case project init does not match a Monday
          $acc+= $elapsed_weeks * $pers[$uid][$i]["weekly_ded"];
          $period_weeks=create_consecutive_weeks($init_date,$end_date);
          $busy=find_availability($cnx,$uid,$init_proj,$period_weeks);
          $acc-=array_sum($busy);
        }
      }
    }
    if($acc<$project_info["total_hours"])
      $error=_("Periods duration and weekly load are not enough to reach estimated hours for this project: Only $acc hours scheduled.");
  }

}
/*
 * param: $cnx.db connection
 * param: $periods.data structure with users periods init, end and weekly load
 * param: $est_hours. amount of hours a project is estimated
 * check_feasibility computes if periods init,end and weekly load are enough to reach estimated hours.
 * in order to get this result, it computes for all users:
 *   > the amount of weeks a user is assigned during a period ($elapsed_weeks)
 *   > the amount of hours a user is working during a period ($elapsed_weeks*$weekly_load);
 *   > the amount of hours a user can't dedicate to a project because of several events ($busy)
 *   > the amount of hours which have been manually edited.
 *  theoretical result comes from \sum($elapsed_weeks*$weekly_load)) along all periods  (to be continued on next line)
 *  -$busy(along project duration) + ($edited_hours-weekly_load_during_edited_weeks + $busy_during_edited_weeks)
 * after all these calculations, this function establishes an error in case schedule is not enough.
 */
function check_feasibility($cnx,$periods,$est_hours){
  global $users_project,$users_weeks,$init_proj,$error,$edited_cells,$weeks;

  if(!empty($users_project)){
    $acc=0;
    foreach($users_project as $uid){
      for($i=0;$i<count($periods[$uid]);$i++){         
        $init_date=$periods[$uid][$i]["init"];
        $end_date=$periods[$uid][$i]["_end"];
        $days=elapsed_days($init_date, $end_date)+7;
        $elapsed_weeks=ceil($days/7);
        $acc+=$elapsed_weeks* $periods[$uid][$i]["weekly_ded"];

        $indexes=get_indexes_edited_weeks($edited_cells,$uid);
        if(!empty($indexes)){
          for($j=0;$j<count($indexes);$j++){
            $weekly_load=get_weekly_load($weeks[$indexes[$j]],$periods[$uid]);
            if($weekly_load!=-1){ // it could happen a week outside period bounds has been edited manually, so subtract weekly_load only if it was already planned
              $acc-=$weekly_load;
              //In case of manual edition, busy hours are not taken into account.
              //Busy hours are subtracted in next block anyway,so they are added here to counteract this effect.
              $this_week=$weeks[$indexes[$j]];
              $end_week=create_end_week($this_week);
              $busy_hours=busy_hours_this_week($cnx,$uid,date_web_to_sql($this_week), date_web_to_sql($end_week),$weekly_load);
              $acc+=$busy_hours;
            }
            $acc+=$users_weeks[$uid][$indexes[$j]];
          }
        }

        $period_weeks=create_consecutive_weeks($init_date,$end_date);
        $busy=find_availability($cnx,$uid,$init_proj,$period_weeks);
        $acc-=array_sum($busy);

      }
    }
    if($acc<$est_hours)
      $error=_("It seems this schedule is not feasible: Only $acc hours are scheduled. Check users periods duration and work load");
  }

}
/*
 * param: $edited_cells. string which contains the manually edited
 * weeks concatenated by "," param: $uid: user id
 * get_indexes_edited_weeks parses the string $edited_cells to get the indexes of edited_weeks
 */
function get_indexes_edited_weeks($edited_cells,$uid){
if(version_compare(PHP_VERSION, '5.0', '<')) {
  //version for PHP4
  $edited=explode(',',$edited_cells);
  if(!empty($edited)){
    $indexes=array();
    foreach($edited as $e){
      if(!(strpos($e,"users_weeks[".$uid."][")===false)) {
        $index=str_replace("users_weeks[".$uid."][","",$e);
        $length= strpos($index,"]");
        $index=substr($index,0,$length);
        array_push($indexes,$index);
      }
    }
    return $indexes;
  }
  return NULL;
} else {
  //version for PHP5
  $edited=explode(',',$edited_cells);
  if(!empty($edited)){
    $indexes=array();
    foreach($edited as $e){
      $index=str_replace("users_weeks[".$uid."][","",$e,$success);
      if($success>0){// $index looks like this: 1], 2], 3],...        
        $length= strpos($index,"]");
        $index=substr($index,0,$length);
        array_push($indexes,$index);
      }
    }
    return $indexes;
  }
  return NULL;
}
}
/*
 * param: $periods_list. array of periods belonging to a same user
 * sort_periods sorts periods ordered by init_date
 */
function sort_periods($periods_list){
  if(!empty($periods_list)){
    $init_weeks=array();
    foreach($periods_list as $p){
      array_push($init_weeks,create_year_week($p["init"]));
    }
    array_multisort($init_weeks,$periods_list);
    return $periods_list;
  }
  return NULL;
}
/*
 * param: $yyyy_ww_a.
 * param: $yyyy_ww_b.
 * cmp_year_weeks returns -1 if $yyyy_ww_a <$yyyy_ww_b,
 * returns 1 if $yyyy_ww_a>$yyyy_ww_b or
 * returns 0 if $yyyy_ww_a==$yyyy_wwb
 */

function cmp_year_weeks($yyyy_ww_a, $yyyy_ww_b){
  if(!empty($yyyy_ww_a)&&!empty($yyyy_ww_b)){
    // 
    $ya=get_year($yyyy_ww_a);
    $yb=get_year($yyyy_ww_b); 
    if($ya<$yb)
      return -1;
    else if($ya>$yb)
      return 1;
    else{
      $wa=get_week($yyyy_ww_a);
      $wb=get_week($yyyy_ww_b);
      if($wa<$wb)
        return -1;
      else if($wa>$wb)
        return 1;
      else return 0;
    }
  }   
  else
    return 2;
}
/*
 * param: $web_date. date in web format
 * param: $periods_list is an array of periods corresponding to a same user for a given project
 * get_weekly_load finds out in which period is contained $web_date and retrieves weekly_load if match 
 * takes place and -1 otherwise.
 */
function get_weekly_load($web_date, $periods_list){
  if(!empty($web_date) && !empty($periods_list)){
    foreach($periods_list as $p){
      $result=cmp_web_dates($web_date,$p["init"]);
      if(($result==0)||($result==1)){
        $result=cmp_web_dates($web_date,$p["_end"]);
        if(($result==0) ||($result==-1)){
          return $p["weekly_ded"];
        }
      }       
    }
  }
  return -1;
}

require_once("include/close_db.php");

if($screen=="GENERATED"){
  if(!empty($users_project)){
    foreach($users_project as $uid){
      for($i=0;$i<count($weeks);$i++){
        if(!empty($users_weeks[$uid][$i]) && validate_text_fields($users_weeks[$uid][$i])==-1)
          $warning=_("Invalid values found");
      }
    }
  }
 }
if(!empty($error)){
  msg_fail($error);
 }
if(!empty($ok)) msg_ok($ok);
if (!empty($warning)){
  msg_warning($warning);
 }
?>
<Table border="0" cellpadding="0" cellspacing="0" width="100%"style="text-align: center;
  margin-left: auto; margin-right: auto;">
  <tr>
    <td style="text-align: center; vertical-align: top;">
      <center>
      <form  id="form" method="post" action="project_schedule.php">
      <table border="0">
        <tr>
          <td><b><?=_("Project:")?></b></td>
          <td>
            <select name="project" onchange="javascript:document.forms[0].submit(); ">
              <?=array_to_option(array_values($projects),$project)?>
            </select>
          </td>
        </tr>
      </table>
      <br>
      <br>
      <? if(!empty($project)){?>
      <!--  ____________               Project details table    ______________ -->

      <table border="1" style="border-style:solid; border-collapse:collapse;  cellspacing="0" cellpadding="0">
        <tr>
          <td bgcolor="#000000" class="title_table">
            <font color="#FFFFFF" class="title_table">
            <?=_("Project details")?>
            </font>
          </td>
        </tr>
        <tr>          
          <td>
            <table border="1" style="border-style:solid; border-collapse:collapse" cellspacing="0" cellpadding="4">
              <tr>
                <td class="title_box"><b><?=_("Name")?></b></td>
                <td class="title_box"><b><?=_("Init date")?></b></td>
                <td class="title_box"><b><?=_("End date")?></b></td>
                <td class="title_box"><b><?=_("Est. Hours")?></b></td>
                <td class="title_box"><b><?=_("Moved Hours")?></b></td>
                <td class="title_box"><b><?=_("Schedule type")?></b></td>
              </tr>
              <tr>
                <td align="center"><?=$project?></td> 
                <td align="center"><?=date_sql_to_web($project_info["init"])?></td> 
                <td align="center"><?=date_sql_to_web($project_info["_end"])?></td> 
                <td align="center"><?=$project_info["est_hours"]?></td> 
                <td align="center"><?=$project_info["moved_hours"]?></td> 
                <td align="center"><?=$project_type?></td> 
              </tr>
            </table>
          </td>
        </tr>
      </table>
     <!--  ________________________________ End Project details table  ________________________________ -->
      <br> <br>
      <?if($screen=="INIT"){
      $span=7;
      ?>                                                                                                        
      <!--  ________________________________Diplay Project users ________________________________ -->
      <table border="1" style="border-style:solid; border-collapse:collapse;  cellspacing="0" cellpadding="4">
        <tr>
          <td bgcolor="#000000" class="title_table" colspan="<?="$span"?>">
            <font color="#FFFFFF" class="title_table">
            <?=_("Project users")?>
            </font>
          </td>
        </tr>
        <tr>
          <td class="title_box"><b><?=_("User")?></b></td>
          <td class="title_box"><b><?=_("Journey")?></b></td>
          <td class="title_box"><b><?=_("Weekly load")?></b></td>
          <td class="title_box"><b><?=_("From week:")?></b></td>
          <td class="title_box"><b><?=_("To week:")?></b></td>
          <td  class="title_box"></td> <!-- Add period cell -->
          <? if(empty($periods))
          $periods=init_periods($users_project, $init_proj, $end_proj);

          if($one_more){?>
          <td  class="title_box"></td> <!-- Delete period cell -->
          <? $one_more=FALSE;
          }/* End checking one more cell */
          ?>
        </tr>      
        <?if(!empty($users_project)){
        foreach($users_project as $uid){?>
        <tr>
          <td style="text-align:left"><?=$uid?></td> 
          <td style="text-align:center"><?=$journeys[$uid]?></td> 
          <?show_periods($periods,$uid);?>
        </tr>
        <?} //end foreach
        }//end empty
        ?>                    
      </tr>
    </table>        
    <!--  ________________________________ End Display Project users ________________________________ -->
    <?} //if screen="INIT"
    ?>    
    <?}//end no EMPTY PROJECT
    ?>
    
    <?if(($screen=="GENERATED")||($screen=="EDITION")||($screen=="BUSY_HOURS")){?>
    <!--  ________________________________Display schedule table ________________________________ -->
    <br>
    <br>
    <table border="1" style="border-style:solid; border-collapse:collapse;
      empty-cells:show" cellspacing="0" cellpadding="2" >	
      <tr>
        <!--  ________________________________Display Project title ________________________________ -->
        <td class="title_table"></td><?
          if(empty($users_weeks)||empty($weeks)||!empty($error) || !empty($warning)){?>                          
        <td colspan="<?=count($weeks)?>" class="title_table"><?=$project?> </td> <?}
        else{
        $current_year=$init_year;
        $end_year=get_year_from_web_date($weeks[count($weeks)-1]);

        $percent=count_weeks_year($current_year,$weeks);
        
        while($percent>0){ ?>          
          <td colspan="<? if($current_year==$end_year){$percent++;}
          // to have title_table style over "Total" cell
          echo $percent."%"?>"  class="title_table">
          <?=$project ." ".$current_year?>
        </td>
        <? $current_year++;
        $percent=count_weeks_year($current_year,$weeks);
        }//end while
        }//end else
        ?>
      </tr>
      <!-- _________________________________Display Weeks title____________________________________-->
      <tr>
        <td bgcolor="#FFFFFF" class="title_box" nowrap> <?=_("User")?></td>
        <? for($i=0; $i<count($weeks);$i++)
        {?>
        <td bgcolor="#FFFFFF" class="title_box"><?=_("w").get_week_from_web_date($weeks[$i])?></td>
        <?}
        if(!empty($users_weeks)&& empty($error) && empty($warning)){?>
        <td bgcolor="#FFFFFF" class="title_box"><?=_("Total")?></td>
        <?}?>
      </tr>
      <!--  _________________________________Display Users Data _________________________________ -->
      <?
      if(!empty($users_project)){
      foreach($users_project as $uid){
      ?>
      <!-- _________________________________Theoretical Weekly hours row_________________________  -->
      <tr>
        <td bgcolor="#FFFFFF" class="title_box" nowrap><?echo "th. ".$uid?></td>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td bgcolor="#FFFFFF" class="text_data" style="text-align:center">
          <table >
            <tr>
              <td>
                <?$edited=explode(",",$edited_cells);
                if(in_array("users_weeks[".$uid."][".$w."]",$edited)==TRUE){
                $style="green_value";
                $edited_cell=TRUE;
                }             
                if(!empty($users_weeks[$uid][$w]) &&(validate_text_fields($users_weeks[$uid][$w])==-1)){
                $style="red_value";
                $edited_cell=TRUE;
                }
                if(empty($style)){
                $style="auto_value";
                $edited_cell=FALSE;
                }
                ?>
                <input class="<?=$style?>"  type="text" name="<?="users_weeks[$uid][$w]"?>" id="<?="users_weeks[$uid][$w]"?>"
                value="<?echo $users_weeks[$uid][$w];?>" size=6 onChange="onChange(this)";
                <?if($screen!="EDITION"){ echo('readonly');}?>
              </td>
              </tr>
              <?if(($edited_cell)&&($screen=="EDITION"))
              $display="block";
              else
              $display="none";?>
              <tr valign="bottom" align="right">
                <td>   
                  <input style="display:<?=$display?>;"  type="checkbox" id="<?="reset_hours[$uid][$w]"?>" name="<?="reset_hours[$uid][$w]"?>"  
                  value="reset_hours" >
                </td>
                <?
                unset($style);
                unset($edited_cell);
                ?>
              </tr>
          </table>
        </td>
        <?} //end for weeks
        ?>

        <!-- _____________________________ Total theoretical hours per user _______________________________ -->

        <?if(!empty($users_weeks) && empty($error)&& empty($warning)){?>
        <td  bgcolor="#FFFFFF" class="text_data"style="text-align:center; font-weight:bold">
          <?$total_th_user=0;
          for($w=0;$w<count($weeks);$w++){
          $total_th_user=$total_th_user+ $users_weeks[$uid][$w];
          } 
          echo $total_th_user;
          ?>
        </td>
        <?} 
        ?>
      </tr>
      <!-- _________________________________ Busy hours row________________ -->

      <?if($screen=="EDITION"){?>
      <tr>
        <td bgcolor="#FFFFFF" class="title_box" nowrap><?=_("non avail. hrs")?></td>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td  bgcolor="#FFFFFF" style="text-align:center">
          <a href="javascript:get_week_user('<?="$w"?>','<?="$uid"?>');" name="<?="availability[$uid][$w]"?>" id="<?="availability[$uid][$w]"?>"><?=$availability[$uid][$w]?></a>
        </td>
        <?} //for $w
        ?>
      </tr>
      <? }//if EDITION
      ?>
      <!-- _________________________________ End Busy hours row____________ -->

      <?} // end foreach user
      }//end empty ?>
      
      <!-- ___Theoretical Weekly hours row___-->
      
      <?if(($screen=="GENERATED") && (empty($error)) && empty($warning) && !empty($users_project)) {?>
      <tr>
        <td class="title_box" nowrap> <?=_("th. week")?> </td>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td style="text-align:center" class="text_data" >
          <?$th_week=0;
          foreach($users_project as $uid){
            $th_week=$th_week+ $users_weeks[$uid][$w];
          }
          echo $th_week;
          ?>
        </td>
        <?}//for $w
        ?>
      </tr>
     
      <!-- ____End Theoretical Weekly hours row___-->
      
      <!-- ____Theory acc___-->
      <tr class="even">
        <td bgcolor="#FFFFFF" class="title_box" nowrap> <?=_("th. acc")?> </td>
        <?$th_acc=0;?>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td style="text-align:center" class="text_data" >
          <?$th_week=0;
          foreach($users_project as $uid){
          $th_week=$th_week+ $users_weeks[$uid][$w];
          }
          $th_acc=$th_acc+$th_week;
          echo $th_acc;
          ?>
        </td>
        <? }//for $w
        ?>
      </tr>
   
      <!-- ___End Theory acc__-->
      
     
      <!-- _____Real Week hours per user __ -->
      <!-- _____________Display Weeks title_________ -->
      <tr>
        <td  class="title_box"> </td>
        <? for($i=0;$i<count($weeks);$i++){?>
        <td  class="title_box">
          <?=_("w").get_week_from_web_date($weeks[$i])?>
        </td>
        <? } //end for weeks
        ?>
      </tr>
      <?foreach($users_project as $uid){?>
      <tr class="even">
        <td  class="title_box" nowrap><?echo $uid?></td>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td  style="text-align:center" class="text_data" >
          <?=$users_weeks_prac[$uid][$w]?>
        </td>
        <?}//for weeks
        ?>
      </tr>
      <?}//end foreach user
      ?>                            
      <!--__ End Real Week hours per user __ -->
      
      <!-- ___Real Week all users ___-->
      <tr>
        <td  class="title_box" nowrap> <?=_("real week")?> </td>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td  style="text-align:center" class="text_data" >
          <?$real_week=0;
          foreach($users_project as $uid){
          $real_week=$real_week+ $users_weeks_prac[$uid][$w];
          }
          echo $real_week;
          ?>
        </td>
        <? }//for $w
        ?>
      </tr>
      <!-- _____End Real Week all users ___ -->
                            
      <!--______ Week diff ____ -->
      <tr>
        <td class="title_box" nowrap> <?=_("week diff.")?> </td>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td bgcolor="#FFFFFF" style="text-align:center" class="text_data" >
          <?
          $real_week=0;
          $th_week=0;
          foreach($users_project as $uid){
          $real_week=$real_week+$users_weeks_prac[$uid][$w];
          $th_week=$th_week+ $users_weeks[$uid][$w];
          }
          echo $real_week-$th_week;
          ?>
        </td>
        <? }//for $w
        ?>
      </tr>
      <!-- End Week diff -->
      <!-- Real acc -->
      <tr>
        <td  class="title_box" nowrap> <?=_("real acc.")?> </td>
        <? $real_acc=0;?>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td   class="title_table" >
          <?
          $real_week=0;
          foreach($users_project as $uid){
          $real_week=$real_week+ $users_weeks_prac[$uid][$w];
          }
          $real_acc=$real_acc+$real_week;
          echo $real_acc;
          ?>
        </td>
        <? }//for $w
        ?>
      </tr>
      <!--_End Real acc _ -->

      <!-- _acc diff _ -->
      <tr>
        <td bgcolor="#FFFFFF" class="title_box" nowrap> <?=_("acc. diff.")?> </td>
        <? $real_acc=0;
        $th_acc=0;
        ?>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td bgcolor="#FFFFFF" style="text-align:center" class="text_data" >
          <?
          $real_week=0;
          $th_week=0;
          foreach($users_project as $uid){
          $real_week=$real_week+ $users_weeks_prac[$uid][$w];
          $th_week=$th_week+ $users_weeks[$uid][$w];
          }
          $real_acc=$real_acc+$real_week;
          $th_acc=$th_acc+$th_week;
          echo $real_acc-$th_acc;
          ?>
        </td>
        <? }//for $w
        ?>
      </tr>
      <!-- _End  acc diff  -->
                            
      <!--_  acc diff %_ -->
      <!-- acc_diff_percent= acc_diff/(estimated_hours-real_acc)-->
      <tr>
        <td bgcolor="#FFFFFF" class="title_box" nowrap> <?=_("acc. diff. %")?> </td>
        <? $real_acc=0;
        $th_acc=0;
        ?>
        <?for($w=0; $w<count($weeks); $w++){?>
        <td bgcolor="#FFFFFF" style="text-align:center" class="text_data" >
          <?
          $real_week=0;
          $th_week=0;
          foreach($users_project as $uid){
          $real_week=$real_week+ $users_weeks_prac[$uid][$w];
          $th_week=$th_week+ $users_weeks[$uid][$w];
          }
          $real_acc=$real_acc+$real_week;
          $th_acc=$th_acc+$th_week;
          echo number_format((($real_acc-$th_acc)/$project_info["total_hours"])*100,2);
          echo '%';
          ?>
        </td>
        <? }//for $w
        ?>
      </tr>
      <!-- _End  acc diff % -->
      
      <!--  acc diff per user  -->
      <? foreach($users_project as $uid){?>
      <tr>
        <td bgcolor="#FFFFFF" class="title_box" nowrap> <?=_("acc. diff. ". $uid)?> </td>
        <? $acc_diff=0;?>
        <?for($w=0; $w<count($weeks); $w++){
        ?>
        <td style="text-align:center" class="text_data" >
          <?
          $diff_week= $users_weeks_prac[$uid][$w]-$users_weeks[$uid][$w];
          $acc_diff=$acc_diff+$diff_week;
          echo $acc_diff;
          ?>
        </td>
        <? }//for $w
        ?>
      </tr>
      <? }//for users
      ?>
      <!-- _End  acc diff per user % -->
      <? }//if GENERATED
      ?>
    </tr>
  </table>
  <br>  <br>
  <?}//if $screen==GENERATED before Project Table
  ?>                

  <?if($screen=="INIT") {?>
  <br><br>
  <input  style="text-align:left" type="radio" name="schedule_type" value="init_est" 
  <?if($schedule_type=="init_est") echo"checked";?>>
  <?=_("Generate schedule according to (Init.date, Est. hours)")?> <br>
  <input  style="text-align:left" type="radio" name="schedule_type" value="init_end" 
  <?if($schedule_type=="init_end") echo"checked";?>> 
  <?=_("Generate schedule according to (Init.date, End date)  ")?>
  <br> <br> <br>
  <?}//if screen=INIT
  ?> 
  <?if(($screen=="INIT")||($screen=="EDITION")){?>
  <input type="submit" name="generate" value="<?=_("Generate")?>" >	
  <?} //if button generate
  ?>
  <?if(($screen=="GENERATED")){?>
  <input type="submit" name="edit" value="<?=_("Edit")?>">
  <input type="submit" name="reset" value="<?=_("Reset")?>">
  <?if(empty($error)&& empty($warning)){?>
  <input type="submit" name="save" value="<?=_("Save")?>">
  <?}
  ?>                                            
        <input type="button" name="delete" value="<?=_("Delete")?>"  onClick="confirmation_win();">
  <?} //end screen generated
  ?>
  <input type="hidden" name="old_project" value="<?=$project?>">
  <input type="hidden" id="all_weeks" name="all_weeks" <?if(!empty($weeks)){?> value="<?=implode(",",$weeks);?>"> <?}?>   
  <? //this is a small trick to avoid implode(",", $users_project) crashing
  if(count($users_project)==0)
  $users_project[0]="";
  ?>
  <!-- HIDDEN VALUES -->
 
  <input type="hidden" name="project_type" value="<?=$project_type?>">
  <input type="hidden" name="screen" value="<?=$screen?>">
  <input type="hidden" name="week_break_down">
  <input type="hidden" name="edited_cells" id="edited_cells" value="<?=$edited_cells?>">
  <input type="hidden" name="confirmation" id="confirmation" value="nop">

<?if(!empty($screen) //first of all
&& ($screen!="INIT") // periods already in form
&& ($screen!="SELECT")){ // only combo visible 
$data_p=pack_periods($users_project, $periods);
echo " <input type=\"hidden\" id=\"data_periods\" name=\"data_periods\" value=\"$data_p\" >";
}?>

<?if($screen=="BUSY_HOURS"){ ?>

<!-- Busy hours break down table_-->

<table border="1" style="border-style:solid; border-collapse:collapse;
  empty-cells:show" cellspacing="2" cellpadding="2" >	

  <td bgcolor="#000000" class="title_table" colspan="4">
    <font color="#FFFFFF" class="title_table">
    <?$user=get_user_from_link($week_break_down);
    $index=get_week_from_link($week_break_down);                                                                                           
    echo($user ."'s time planning: Week ".get_week_from_web_date($weeks[$index]));?>
    </font>
  </td>
  <?for($i=0;$i<count($busy_hours_break_down);$i++){?>
  <tr>
    <? for($j=0;$j<4;$j++){
    if($i==0){?>
    <td  class="title_box">  <?=$busy_hours_break_down[$i][$j]?> </td>
    <?}
    else{?>
    <td style="text-align:center"> <?=$busy_hours_break_down[$i][$j]?> </td>
    <?} //end else
    } //end for
    ?>
  </tr>
  <?}//end for 2
  ?>
  <tr>
    <td class="title_box"><?="w".get_week_from_web_date($weeks[$index])?></td>
    <td class="title_box"> <?=_("Total")?> </td>  
    <td class="title_box"> 
      <?$acc=0;
      for($i=0;$i<count($busy_hours_break_down);$i++){
      $acc+=$busy_hours_break_down[$i][2];
      }
      echo $acc;?>
    </td>                                          
    <td  class="title_box"> 
      <?$acc=0;
      for($i=0;$i<count($busy_hours_break_down);$i++){
      $acc+=$busy_hours_break_down[$i][3];
      }
      echo $acc;?>
    </td>                                         
  </table>

  <br>
  <input type="submit" name="go_back" value="<?=_("Go back")?>">

  <!--_ End Busy hours break down -->
  <?}//end screen = busy_hours
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

   <script language="JavaScript" type="text/javascript">

  function get_week_user (a,b)
{
  document.forms[0].week_break_down.value=a+"_"+b;
  document.forms[0].submit();

}

function onChange(obj) {

  document.getElementById(obj.name).style.color = 'green'; 
  document.getElementById(obj.name).style.fontStyle='italic';
  document.getElementById(obj.name).style.fontWeight='bold';
  var edited= document.getElementById('edited_cells').value;
  if(edited.indexOf(obj.name)==-1)
    document.getElementById('edited_cells').value= document.getElementById('edited_cells').value+","+ obj.name;
  //  alert(document.getElementById('edited_cells').value);
  //   alert(obj.name);

  var users_weeks= obj.name;
  users_weeks=users_weeks.substring(users_weeks.indexOf('['),users_weeks.length);
  document.getElementById('reset_hours'+users_weeks).setAttribute('style', 'display:block');
  // alert(users_weeks);
}

function confirmation_win() {
  var answer = confirm("Are you sure you want to delete this schedule?")
    if (answer){
      document.getElementById('confirmation').value='ok';
      document.getElementById('form').submit();
    }
    else{
      document.getElementById('confirmation').value='nop';
      
    }
}
</script> 
