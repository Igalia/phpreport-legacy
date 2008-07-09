<?
// PhpReport - A task reporting application
//
// Copyright (C) 2003-2005
//  Igalia, S.L. <info@igalia.com>
//  Andrés Gómez García <agomez@igalia.com>
//  Enrique Ocaña González <eocanha@igalia.com>
//  José Riguera López <jriguera@igalia.com>
//  Jesús Pérez Díaz <jperez@igalia.com>
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


/**
 * PARAMETERS RECEIVED BY THIS PAGE:
 *
 * day = Report day. DD/MM/YYYY format.
 * weekly_minutes = Precomputed value with the minutes worked this week.
 *                  It's useful in order to avoid making the query for every reload.
 *                  It's recomputed if it's empty or if the report is stored.
 * daily_minutes = Precomputed value with the minutes worked today
 * task = Array with the different tasks:
 *  array(
 *   0=>array(
 *       "init"=>...,
 *       "fin"=>...
 *       ...
 *      ),
 *   1=>array(...),
 *   ...
 *  );
 * delete_task[i] = TASK DELETE button has been pressed for the i-th task
 * new_task = NEW TASK button has been pressed
 * cancel = CANCEL button has been pressed
 * save = SAVE button has been pressed
 * editing = Hidden form parameter indicating that it's being edited. It's used
 *           to avoid data reloading when all the tasks have been deleted (task
 *           array empty on purpose)
 */
require_once("include/config.php");
require_once("include/autenticate.php");
require_once("include/connect_db.php");
require_once("include/prepare_calendar.php");

// IF THERE ISN'T DAY, LET'S GENERATE IT

if (empty($day)) {
 $day=getdate(time());
 $day=$day["mday"]."/".$day["mon"]."/".$day["year"];
}

// TEST IF THE REPORT IS LOCKED

$die=_("Can't finalize the operation");
$result=@pg_exec($cnx,$query="SELECT _date FROM block"
 ." WHERE uid='$session_uid'")
 or die($die);
$blocked=false;
if ($row=@pg_fetch_row($result))
 $blocked=!($row[0]<date_web_to_sql($day));
@pg_freeresult($result);
if ($blocked) $param_blocked=" READONLY ";

// PRECHARGE OF THE FORM VALUES
// PRESSING OF REPORT COPY BUTTON IS CHECKED, AND IN THAT CASE,
// CORRESPONDING DAY VALUES ARE LOADED

if ((empty($editing) && empty($task)) || $blocked || !empty($change) || !empty($change2)) {
 $task=array();
 $die=_("Can't finalize the operation");
 if (!empty($copy2) || !empty($copy) && empty($change) && empty($change2)) {
   $yesterday=day_yesterday($day);
   $temp=(!empty($copy)) ? $copy_day : $yesterday;
   $result=@pg_exec($cnx,$query="SELECT * FROM task "
		    ." WHERE uid='$session_uid' AND _date='"
		    .date_web_to_sql($temp)."'"
		    ." ORDER BY init")
   or die($die);
 }
 else {
  $result=@pg_exec($cnx,$query="SELECT * FROM task "
		   ." WHERE uid='$session_uid' AND _date='"
		   .date_web_to_sql($day)."'"
		   ." ORDER BY init")
    or die($die);
 }

 for ($i=0;$row=@pg_fetch_array($result,$i,PGSQL_ASSOC);$i++) {
  $row["_date"]=date_sql_to_web($row["_date"]);
  $row["init"]=hour_sql_to_web($row["init"]);
  $row["_end"]=hour_sql_to_web($row["_end"]);
  $row["telework"]=($row["telework"]=='t')?"true":"false";
  $row["text"]=stripslashes($row["text"]);
  $task[]=$row;
 }
 @pg_freeresult($result);
}

// DELETE TASK

if (!empty($delete_task)) {
 $task=del_elements_shifting($task, current(array_keys($delete_task)));
}

// NEW TASK
if (!empty($new_task)) {
 $i=count($task);

 /* Select data from last inserted task */

 $result=@pg_exec($cnx,$query="SELECT * FROM task WHERE uid='$session_uid' "
		  ."AND init=(SELECT MAX(init) FROM task WHERE uid='$session_uid' "
                  ."           AND _date=(SELECT MAX(_date) AS maxdate FROM task WHERE uid='$session_uid' "
                  ."                      AND _date<='".date_web_to_sql($day)."')) "
		  ."AND _date=(SELECT MAX(_date) FROM task WHERE uid='$session_uid' "
                  ."           AND _date<='".date_web_to_sql($day)."')")
   or die($die);

 $last_task=array();
 if($row=@pg_fetch_array($result,0,PGSQL_ASSOC)) {
   $last_task["type"]=$row["type"];
   $last_task["customer"]=$row["customer"];
   $last_task["name"]=$row["name"];
   $last_task["ttype"]=$row["ttype"];
   $last_task["story"]=$row["story"];
 }
 @pg_freeresult($result);

 $task[]=array(
  "init"=>date("H:i",mktime()),
  "_end"=>"",
  "type"=>isset($last_task["type"])?$last_task["type"]:"",
  "customer"=>isset($last_task["customer"])?$last_task["customer"]:"",
  "name"=>isset($last_task["name"])?$last_task["name"]:"",
  "ttype"=>isset($last_task["ttype"])?$last_task["ttype"]:"",
  "story"=>isset($last_task["story"])?$last_task["story"]:"",
  "telework"=>"",
  "text"=>""
 );

 if ($i==0) {	// if no task
  $task[$i]["init"]=date("H:i",mktime());
 } else if (empty($task[$i-1]["_end"])) {
  $task[$i-1]["_end"]=date("H:i",mktime());
  $task[$i]["init"]=$task[$i-1]["_end"];
 }
}
//CLONE TASK

if(!empty($clone_task)){
 /* $clone_task is an array of only one element with index i =  ith task to be cloned */
 /* array_keys($clone_task)[0] returns an array whose first element is the index of the ith task to be cloned */
 
  /* Select data from selected task */
  $cloned_task= array_keys($clone_task);  
  $cloned_task_index= $cloned_task[0];  
  $task[]=array(
  "init"=>date("H:i",mktime()),
  "_end"=>"",
  "type"=>$task[$cloned_task_index]["type"],
  "customer"=>$task[$cloned_task_index]["customer"],
  "name"=>$task[$cloned_task_index]["name"],
  "ttype"=>$task[$cloned_task_index]["ttype"],
  "story"=>$task[$cloned_task_index]["story"],
  "telework"=>$task[$cloned_task_index]["telework"],
  "text"=>$task[$cloned_task_index]["text"],
 );
 
}
// RETURN TO CALENDAR

if (!empty($cancel)) {
 header("Location: ?day=$day");
}

// Updates task end field with the current hour
if (!empty($currenthour)){
$t=current(array_keys($currenthour));
$task[$t]["_end"]=date("H:i",mktime());
}

// SAVE CHANGES

if (!empty($save)) {
 do {
  // REPORT LOCK CHECKING

  if ($blocked) {
   $error=_("The report is locked and it can't be modified");
   break;
  }

  // FIELD CHECKING

  for ($i=0;$i<sizeof($task);$i++) {
   foreach (array("init","_end") as $field) {
    if (!validate_time_web($task[$i][$field])) {
     $error=_("Errors exist that must be corrected");
     $error_task[$i][$field]=_("You must use the format HH:MM");
    } else {     
     $task[$i][$field]=hour_sql_to_web(hour_web_to_sql($task[$i][$field]));
     if ($task[$i][$field]<"00:00" || $task[$i][$field]>"24:00") {
      $error=_("Errors exist that must be corrected");
      $error_task[$i][$field]=_("The hour must be between 00:00 and 24:00");
     }
    }
   }
   foreach (array("init","_end","type","text") as $field) {
    if (empty($task[$i][$field])) {
     $error=_("Errors exist that must be corrected");
     $error_task[$i][$field]=_("You must specify this field");
    }
   }
  }
  if (!empty($error)) break;

$size=sizeof($task);
if ($size>0) usort ($task, cmp_init_dates);

  // REPEAT THE LOOPS TO MAKE ERROR INDEXES TO BE CORRECT

  for ($i=0;$i<$size;$i++) {
   if ($i>0 && $task[$i-1]["_end"]>$task[$i]["init"]) {
    $error_task[$i-1]["_end"]=_("The task overlaps with another one");
    $error_task[$i]["init"]=_("The task overlaps with another one");
    $error=_("Errors exist that must be corrected");
   }
   if ($task[$i]["init"]>=$task[$i]["_end"]) {
    $error=_("Errors exist that must be corrected");
    $error_task[$i]["init"]=_("Invalid interval of hours");
    $error_task[$i]["_end"]=_("Invalid interval of hours");
   }
  }
  if (!empty($error)) break;

  // REPORT AND TASKS SAVING TRANSACTION

  if (!@pg_exec($cnx,$query=
    "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; "
   ."BEGIN TRANSACTION; ")) {
   $error=_("Can't finalize the operation");
   break;
  }

  do {

   // TASK DELETING

   if (!@pg_exec($cnx,$query=
     "DELETE FROM task WHERE uid='$session_uid'"
    ." AND _date='".date_web_to_sql($day)."'")) {
    $error=_("Can't finalize the operation");
    break;
   }

  // LOCK MANAGEMENT

  if (!$result=@pg_exec($cnx,$query=
     "SELECT _date FROM block WHERE uid='$session_uid'")) {
    $error=_("Can't finalize the operation");
    break;
  }

  if (@pg_numrows($result)==0) {

    // LOCK INSERT
    if (!$result=@pg_exec($cnx,$query=
      "INSERT INTO block (uid,_date)"
     ." VALUES ('$session_uid', '1999-12-31')")) {
     $error=_("Can't finalize the operation");
     break;
    }
   }

   // REPORT MANAGEMENT

   if (!$result=@pg_exec($cnx,$query=
     "SELECT modification_date FROM report WHERE uid='$session_uid'"
    ." AND _date='".date_web_to_sql($day)."'")) {
    $error=_("Can't finalize the operation");
    break;
   }

   if (@pg_numrows($result)>0) {

    // REPORT UPDATE

    if (!$result=@pg_exec($cnx,$query=
      "UPDATE report SET modification_date=now()"
     ." WHERE uid='$session_uid'"
     ." AND _date='".date_web_to_sql($day)."'")) {
     $error=_("Can't finalize the operation");
     break;
    }

   } else {

    // REPORT INSERT

    if (!$result=@pg_exec($cnx,$query=
      "INSERT INTO report (uid,_date,modification_date)"
     ." VALUES ('$session_uid','"
     .date_web_to_sql($day)."',now())")) {
     $error=_("Can't finalize the operation");
     break;
    }
   }

   // TASKS ARE FORMATTED PROPERLY

   for ($i=0;$i<sizeof($task);$i++) {
    $fields=array("uid","_date","init","_end","name","type","customer","ttype","story","telework","text");
    $row=array();
    foreach ($fields as $field) {
      $row[$field]=trim($task[$i][$field]);
    }
    $row["uid"]=$session_uid;
    $row["_date"]=date_web_to_sql($day);
    $row["init"]=hour_web_to_sql($row["init"]);
    $row["_end"]=hour_web_to_sql($row["_end"]);
    foreach ($fields as $field)
     if (empty($row[$field]) && !is_integer($row[$field])
        && $field!="name") $row[$field]="NULL";
     else $row[$field]="'$row[$field]'";

    // AND FINALLY ARE INSERTED

    if (!@pg_exec($cnx,$query="INSERT INTO task ("
     .implode(",",$fields).") VALUES (".implode(",",$row).")")) {
     $error=_("Can't finalize the operation");
     break;
    }
   }

  // AND ALL THE PROCESS IS DONE

   @pg_exec($cnx,$query="COMMIT TRANSACTION");
  } while(false);

  if (!empty($error)) {

   // Debugging
   $error.="<!-- $query -->";
   @pg_exec($cnx,$query="ROLLBACK TRANSACTION");
   break;
  }

  $confirmation=_("The changes have been saved correctly");

 } while(false);
}

// WEEKLY AND DAILY WORKED HOURS COMPUTATION

if (empty($weekly_minutes) || !empty($save)) {
 $weekly_minutes=worked_minutes_this_week($cnx,$session_uid,date_web_to_sql($day));
 $daily_minutes=0;
 foreach ((array)$task as $t)
  if (!empty($t["init"]) && !empty($t["_end"]))
   $daily_minutes+=(hour_web_to_sql($t["_end"])-hour_web_to_sql($t["init"]));
}

$title=_("Report edition");
require("include/template-pre.php");

if (!empty($error)) msg_fail($error);
if (!empty($confirmation)) msg_ok($confirmation);

// RETRIEVE DATA FROM DB FOR FILLING UP WITH COMBO VALUES

/* Retrieve staff condition for the user */
$result=@pg_exec($cnx,$query="SELECT staff FROM users WHERE uid='$session_uid'");
$row=@pg_fetch_array($result,0,PGSQL_ASSOC);
@pg_freeresult($result);

/* Check if user is a trainee */
$isTrainee = ($row["staff"]!='t');

if (!$isTrainee) {
  /* Staff member */
  $result=@pg_exec($cnx,$query="SELECT type,code,description,no_customer FROM label"
		   ." WHERE activation='t' AND type='type' AND code<>'prac' ORDER BY description")
    or die($die."$query");
} else {
  /* Non staff member */
  $result=@pg_exec($cnx,$query="SELECT code, description,no_customer FROM label "
		   ."WHERE activation='t' AND type='type' AND code = 'prac'")
    or die($die."$query");
}
$table_type=array(""=>"---");
for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
  $table_type[$row['code']]=$row['description'];
}
@pg_freeresult($result);


/* Retrieve user customers combo values */
$result=@pg_exec($cnx,$query="SELECT type,code,description FROM label"
		 ." WHERE activation='t' AND type='customer' AND code IN "
		 ." (SELECT DISTINCT customer FROM project_user pu JOIN projects p ON pu.name=p.id WHERE uid='$session_uid') "
		 ."ORDER BY description")
     or die($die."$query");
     
     $table_userCustomers=array(""=>"---");
     for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
       $table_userCustomers[$row['code']]=$row['description'];
     }
@pg_freeresult($result);

/* Retrieve all customers combo values */
$result=@pg_exec($cnx,$query="SELECT type,code,description FROM label "
		 ."WHERE activation='t' AND type='customer' "
		 ."ORDER BY description")
     or die($die."$query");
     
     $table_allCustomers=array(""=>"---");
     for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
       $table_allCustomers[$row['code']]=$row['description'];
     }
@pg_freeresult($result);

/* Retrieve all projects combo values */
$result=@pg_exec($cnx,$query="SELECT type,code,description FROM label "
		 ."WHERE activation='t' AND type='name' "
		 ."ORDER BY description")
     or die($die."$query");
     
     $table_allProjects=array(""=>"---");
     for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
       $table_allProjects[$row['code']]=$row['description'];
     }
@pg_freeresult($result);


/* Retrieve all projects for this user combo values */
$result=@pg_exec($cnx,$query="SELECT l.code FROM label l "
		 ."JOIN project_user pu ON l.code=pu.name "
		 ."WHERE l.activation='t' AND l.type='name' "
		 ."AND pu.uid = '$session_uid' ORDER BY code")
     or die($die."$query");
     
     $table_userProjects=array();
     for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
       $table_userProjects[]=$row['code'];
     }
@pg_freeresult($result);


/* Retrieve task type for  project combo values */
$result=@pg_exec($cnx,$query="SELECT code, description FROM label "
		 ."WHERE activation='t' AND type='ttype' "
		 ."ORDER BY description")
     or die($die."$query");
     
     $table_ttype=array(""=>"---");
     for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
       $table_ttype[$row['code']]=$row['description'];
     }
@pg_freeresult($result);


/* Retrieve a list of task types which don't need a */
/* customer or a project (therefore ignore them)    */
$result=@pg_exec($cnx,$query="SELECT code "
		 ."FROM label WHERE type = 'type' "
		 ."AND no_customer = 't' ORDER BY code")
     or die($die."$query");
     for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
       $noCustomer_types[]=$row['code'];
     }
@pg_freeresult($result);

/* Javascript generation for types which don't need customer to be set */
?>
<script language="Javascript">
var oldCustomerIndex = new Array();
var oldProjectIndex = new Array();

var noCustomer_types = new Array (
<?
$noCustomerTypes_array = array();
foreach ($noCustomer_types as $i => $value) {
  $noCustomerTypes_array[] = "'".$value."'";
} 
echo(implode(", ", $noCustomerTypes_array));
?>
);

function checkNoCustomerType(type) {
  for (i=0; i < noCustomer_types.length; i++) {
    if (noCustomer_types[i] == type)
      return true;
  }
  return false;
}

function setCombosStatus(type, task_number) {
  var customerCombo = document.getElementById('customer_combo_' + task_number);
  var projectCombo = document.getElementById('name_combo_' + task_number);
  var boolValue = checkNoCustomerType(type);

  /* If disabled status for combos has not changed and we're disabling combos, save old status */
  if (boolValue && 
      (customerCombo.disabled != boolValue
       || projectCombo.disabled != boolValue)) {

    oldCustomerIndex[task_number] = customerCombo.selectedIndex;
    customerCombo.selectedIndex = 0;

    oldProjectIndex[task_number] = projectCombo.selectedIndex;
    projectCombo.selectedIndex = 0;
  } else if (!boolValue) {
    /* If disabled status for combos has not changed and we're enabling combos, reload old status */
    if (oldCustomerIndex[task_number]) {
      customerCombo.selectedIndex = oldCustomerIndex[task_number];
    }
    if (oldProjectIndex[task_number]) {
      projectCombo.selectedIndex = oldProjectIndex[task_number];
    }
  } /* else do nothing */

  customerCombo.disabled = boolValue;
  projectCombo.disabled = boolValue;
}
</script>


<table border="0" cellpadding="0" cellspacing="0" width="100%"
 style="text-align: center; margin-left: auto; margin-right: auto;">
<tr>
<td style="text-align: center; vertical-align: top;">

<center>
<form name="task" action="#last_task" method="post">
<input type="hidden" name="day" value="<?=$day?>">
<input type="hidden" name="hoxe" value="<?=$hoxe?>">
<input type="hidden" name="weekly_hours" value="<?=$weekly_hours?>">
<input type="hidden" name="daily_hours" value="<?=$daily_hours?>">

<!-- box -->
<table border="0" cellspacing="0" cellpadding="0">
<tr><td bgcolor="#000000">
<table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
<td bgcolor="#000000" class="title_box"><font
 color="#FFFFFF" class="title_box">
<!-- title box -->
<?=_("Tasks of the day")?> <?=$day?>
<!-- end title box -->
</font></td></tr>
<?

// ITERATE RENDERING REPORT TABLES

for ($i=0;$i<sizeof($task);$i++) {

  /* Set showingAllData value */
  $showingAllDataValue=FALSE;
  if (!empty($task[$i]['showingAllData'])) {
    if (!empty($task[$i]['showAllData'])) {
      $showingAllDataValue=TRUE;
    } else if (empty($task[$i]['showExactData'])) {
      $showingAllDataValue=($task[$i]['showingAllData']=="t");
    }
  } else if (empty($task[$i]['showingExactData'])) {

    /* Check if the project for this task doesn't belong to the user.    */
    /* In that case we must set $showingAllDataValue variable to 'true', */
    /* because it's the only way to show needed values at the combos     */
    if (($task[$i]['name'] != "") && !in_array($task[$i]['name'], $table_userProjects)) {
      $showingAllDataValue = TRUE;
    }
  }

  /* set selected customer and project to 'empty' when changing from a mode into the other */ 
  if (!empty($task[$i]['showAllData']) || !empty($task[$i]['showExactData'])) {
    $task[$i]['customer'] = "";
    $task[$i]['name'] = "";
  }

  /* Check if form was submited because of a combo selection */
  $comboSubmit=FALSE;
  if ($task[$i]['comboSubmit'] != "f") {
    $csvalue = $task[$i]['comboSubmit']; 
    /* check valid values */
    if ($csvalue == "customer" || $csvalue == "name") {
      $comboSubmit = $csvalue;
    }
  }
?>
<tr><td bgcolor="#FFFFFF" class="text_box">
<?
 if ($i > 0) {
?>
<a name="task<?=$i?>"></a> 
<?
 }
 if (sizeof($task) > 1 && $i==sizeof($task)-1) {
?>
<a name="last_task"></a> 
<?
 }
?>
<table border="0" cellspacing="0" cellpadding="10"><tr><td>
<font
 color="#000000" class="text_box">
<!-- text box -->
<table border="0" cellpadding="3" cellspacing="1">
<?
 foreach (array(
  "init"=>_("Start hour"),
  "_end"=>_("End hour"),
  ) as $field_key=>$field_value) {
?>
 <tr>
  <td width="200px"><?=$field_value?></td>
  <td>
    <input type="text" name="<?="task[$i][$field_key]"?>" <?=$param_blocked?>
      value="<?=$task[$i][$field_key]?>">
<?
    if ($field_key=="_end") {
?>
    <input type="submit" name="<?="currenthour[$i]"?>" value="<?=_("Current hour")?>"> 
<?
    }
?>       
  </td>
 
  <td>
<?
  if (!empty($error_task[$i][$field_key])) {
   echo msg_fail($error_task[$i][$field_key]);
  }
?>
  </td></tr>
 <?
 }

/* Set the proper $table_customer and $table_name values */
if ($showingAllDataValue) {

  /* Initialization with default values  */
  $table_customer=$table_allCustomers;
  $table_name=$table_allProjects;  
  $currentCustomer = $task[$i]['customer'];

  if ($comboSubmit == "name" && !empty($task[$i]['name'])) {
    /* If project combo was selected, we must get the  */
    /* associated customer first, in order to properly */
    /* select it and retrieve all the related projects */
    $result=@pg_exec($cnx,$query="SELECT l.code FROM label l JOIN projects p "
		     ."ON l.code = p.customer WHERE l.activation = 't' "
		     ."AND id = '".$task[$i]['name']."'")
      or die($die."$query");

    if ($row=@pg_fetch_array($result,0,PGSQL_ASSOC)) {
      $currentCustomer=$row['code'];
      $task[$i]['customer']=$currentCustomer;
    }
    @pg_freeresult($result);
  }

  /* Retrieve all projects for the selected customer (if some customer was selected) */
  if (!empty($currentCustomer) && empty($task[$i]['showAllData'])) {
    
    $result=@pg_exec($cnx,$query="SELECT l.code, l.description "
		     ."FROM label l JOIN projects p ON l.code=p.id "
		     ."WHERE l.activation='t' AND l.type='name' AND p.customer='".$currentCustomer."' "
		     ."ORDER BY description")
      or die($die."$query");
    
    $table_customerProjects=array(""=>"---");
    for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
      $table_customerProjects[$row['code']]=$row['description'];
    }
    @pg_freeresult($result);
    
    /* Set combo values for the current customer */
    $table_name = $table_customerProjects;
  }
} else {
  
  /* Initialization with default values  */
  $table_customer=$table_userCustomers;
  $currentCustomer = $task[$i]['customer'];  

  if ($comboSubmit == "name" && !empty($task[$i]['name'])) {
    /* If project combo was selected, we must get the  */
    /* associated customer first, in order to properly */
    /* select it and retrieve all the related projects */
    $result=@pg_exec($cnx,$query="SELECT l.code FROM label l JOIN projects p "
		     ."ON l.code = p.customer WHERE l.activation = 't' "
		     ."AND id = '".$task[$i]['name']."'")
      or die($die."$query");

    if ($row=@pg_fetch_array($result,0,PGSQL_ASSOC)) {
      $currentCustomer=$row['code'];
      $task[$i]['customer']=$currentCustomer;
    }
    @pg_freeresult($result);
    
  }
  
  /* Retrieve all projects for the current user and customer (if some customer was selected) */
  $query="SELECT l.code, l.description "
    ."FROM label l JOIN projects p ON l.code=p.id "
    ."WHERE l.activation='t' AND l.type='name' ";

  if (!empty($task[$i]['customer']) && empty($task[$i]['showExactData'])) {
    $query=$query."AND p.customer='".$task[$i]['customer']."' ";
  }

  $query=$query."AND code IN (SELECT name FROM project_user WHERE uid='$session_uid') "
    ."ORDER BY description";
    
  $result=@pg_exec($cnx,$query)
    or die($die."$query");
  
  $table_userCustomerProjects=array(""=>"---");
  for ($k=0;$row=@pg_fetch_array($result,$k,PGSQL_ASSOC);$k++) {
    $table_userCustomerProjects[$row['code']]=$row['description'];
  }
  @pg_freeresult($result);
  
  $table_name=$table_userCustomerProjects;

  /* Set pre-selected project to '---' if no customer was selected before */
  if ($comboSubmit == "customer" && empty($currentCustomer)) {
    $task[$i]['name'] = "";
  }
}

foreach (array( "type"=>_("Task type"), "customer"=>_("Customer"),
		"name"=>_("Project"), "ttype"=>_("Task type for this project") ) 
	 as $field_key=>$field_value) {
  
  $tabla="table_$field_key";
?>
 <tr>
  <td width="200px"><?=$field_value?></td>
  <td>
    <select name="<?="task[$i][$field_key]"?>" <?=$param_blocked?> 
    <?
    if ($field_key == "type") {
      ?>
      onchange="setCombosStatus(this.value, <?=$i?>);"
      <?
    }

    /* set javascript onchange event if needed */
    if ($field_key == "customer" || ($field_key == "name" && empty($task[$i]['customer']))) {
    ?>
    onchange="document.getElementById('comboSubmit_<?=$i?>').value='<?="$field_key"?>'; 
              document.task.action='#task<?=$i?>'; document.task.submit();"
    <?
    }
    if ($field_key == "customer" || $field_key == "name") {
      echo (" id=\"".$field_key."_combo_".$i."\" ");
      if (!empty($task[$i]['type']) && (in_array($task[$i]['type'], $noCustomer_types))) {
	echo(" disabled=\"disabled\" ");
      }
    }
    ?>
    > <!-- Close select tag -->
      <?=array_to_option(array_values($$tabla),$task[$i][$field_key],array_keys($$tabla))?>
    </select>   
  </td>
  <td>
<?
  if (!empty($error_task[$i][$field_key])) {
   echo msg_fail($error_task[$i][$field_key]);
  }
?>
  </td>
 </tr>
<?
 }
?>

 <tr>
  <td width="200px"><?=_("Story")?></td>
  <td>
   <input type="text" name="<?="task[$i][story]"?>"
    value="<?=$task[$i]['story']?>" <?=$param_blocked?>>
  </td>
  <td>
<?
  if (!empty($error_task[$i]['story'])) {
   echo msg_fail($error_task[$i]['story']);
  }
?>
  </td>
 </tr>
 <tr>
   <td width="200px"><?=_("Telework")?></td>
   <td>
    <input type="checkbox" name="<?="task[$i][telework]"?>"
      value="true"
      <?=($task[$i][telework]=='true')?"checked":""?> <?=$param_blocked?>>
   </td>
   <td>
<?
  if (!empty($error_task[$i]['story'])) {
   echo msg_fail($error_task[$i]['story']);
  }
?>
   </td>
 </tr>
 <tr>
  <td colspan="2">
   <?=_("Description:")?>
  </td>
  <td>
<?
if (!empty($error_task[$i]["text"])) {
   echo msg_fail($error_task[$i]["text"]);
  }
?>
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <textarea rows="3" cols="80" name="<?="task[$i][text]"?>" <?=$param_blocked?>
    ><?=stripslashes($task[$i][text])?></textarea>
  </td>
 </tr>
<?
if (!$blocked) {
?>
 <tr>
  <td align="left">
   <? 
    /* If user is a trainee it's no possible for him/her to    */
    /* push the "Show all data" button, he/she is only allowed */
    /* to work in his/her associated projects                  */
    if (!$isTrainee) {
      if (!$showingAllDataValue) { ?>   
   <input type="submit" name="<?="task[$i][showAllData]"?>" value="<?=_("Show all data")?>" 
	  onclick="document.task.action='#task<?=$i?>'; return true;">
   <? } else { ?>
   <input type="submit" name="<?="task[$i][showExactData]"?>" value="<?=_("Show only needed data")?>"
	  onclick="document.task.action='#task<?=$i?>'; return true;">
   <? 
      } 
    }
   ?>
  </td>
  <td colspan="2" align="right">

   <?/* Hidden fields for keeping some useful data through POST requests */?>
   <input type="hidden" id="comboSubmit_<?=$i?>" name="<?="task[$i][comboSubmit]"?>" value="f">
   <input type="hidden" name="<?="task[$i][showingAllData]"?>" value="<?=($showingAllDataValue?"t":"f")?>">

   <input type="submit" name="<?="delete_task[$i]"?>" value="<?=_("Delete task")?>">
   <input type="submit" name="<?="clone_task[$i]"?>" value="<?=_("Clone task")?>">
  </td>
 </tr>
<?
}
?>
</table>
<!-- end text box -->
</table></td></tr>
<?
}
?>
</table></td></tr></table>
<!-- end box -->
<br>
<?
require_once("include/close_db.php");

if (!$blocked) {
?>
<input type="hidden" name="editing" value="1">
<input type="submit" name="new_task" value="<?=_("New task")?>">
<input type="submit" name="save" value="<?=_("Save changes")?>">
<?
}
?>
<input type="submit" name="cancel" value=<?=_("Cancel")?>>
</center>

</td>
<td style="width: 25ex" valign="top">
<? require("include/show_count.php") ?>
<br>
<? require("include/show_sections.php") ?>
<br>
<? require("include/show_calendar.php") ?>

</td>
</tr>
</table>
<?
require("include/template-post.php");
?>
