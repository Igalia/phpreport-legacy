   <!-- box -->
<table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   <table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
   <!-- title box -->
   <td bgcolor="#000000" class="title_minibox"
    ><a href="report.php?day=<?=$day_month_previous?>"
    ><font color="#FFFFFF" class="title_minibox">
   &nbsp;&lt;&nbsp;
   </font></a></td>
   <td bgcolor="#000000" class="title_minibox" width="100%"
    ><font color="#FFFFFF" class="title_minibox">
   <?=_("Calendar")?>
   </font></td>
   <td bgcolor="#000000" class="title_minibox"
    ><a href="report.php?day=<?=$day_month_next?>"
    ><font color="#FFFFFF" class="title_minibox">
   &nbsp;&gt;&nbsp;
   </font></a></td>
   </tr><tr><td bgcolor="#FFFFFF" class="text_minibox" colspan="3">
   <!-- end title box -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td
    align="center">
   <!-- text box -->
   <table cellpadding="3" cellspacing="0" style="text-align: center;"
    width="100%">
     <?
      $style=array(
       "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
       "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
       "N"=>"background: #E0E0F0; color: #000000; font-weight: regular; text-align: center",
       "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center"
      );
     ?>
    <tr>
     <td colspan="8" style="<?=$style["T"]?>">
<?$arrayDMA=date_web_to_arrayDMA($day); ?>
      <?=$arrayDMA[0]._(" of ").$months_minical[$arrayDMA[1]-1]._(" of ").$arrayDMA[2]?>
     </td>
    </tr>
    <tr>
     <td style="<?=$style["T"]?>">
     &nbsp;
     </td>
     <?
      // Cálculo de los titles de los días
      foreach ($days_minical as $d) {
     ?>
     <td style="<?=$style["T"]?>">
     <?=$d?>
     </td>
     <?
      }
     ?>
    </tr>
    <?
     foreach ($calendar as $s) {
    ?>
   <tr>
     <!-- Week number -->
     <td style="<?=$style["T"]?>">
      <?=strftime("%V", mktime(0, 0, 0, (($s[1][1]=="G")?$arrayDMA[1]-1:$arrayDMA[1]), $s[1][0], $arrayDMA[2]))?>
     </td>
     <?
      foreach ($s as $d) {
     ?>
     <td style="<?=$style[$d[1]]?>">
      <a href="report.php?day=<?=$d[2]?>" style="<?=$style[$d[1]]?>"
      ><?=$d[0]?></a>
     </td>
     <?
      }
     ?>
    </tr>
    <?
     }
    ?>
   </table>

   <table border="0" cellpadding="0" cellspacing="0" width="100%">
   <form name="mini_calendar" action="report.php" method="post">
   <tr><td style="background: #FFFFFF; color: #000000; text-align: center">
   <input type="text" name="day" value="<?=$day?>" size="10"
    style="color: #000000; width: 100%; height: 20px"
    onchange="javascript: document.mini_calendar.submit();">
   </td><td style="color: #000000; text-align: right">
   <input type="submit" name="change" value="<?=_("Go")?>" style="width: 95%; height: 20px"> 
   </td></tr>
   
   <tr>
    <input type="hidden" name="today" value="<?=$today?>"  onchange="javascript: document.mini_calendar.submit();">
    <td colspan="2">
     <input type="submit" name="change2" value="<?=_("Today")?>" style="width: 100%; height: 20px">


    </td>
   </tr>
   </form>
   </table>

   <!-- end text box -->
   </td></tr></table></td></tr></table></td></tr></table>
   <!-- end box -->
<br>

   <!-- box -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   	<table border="0" cellspacing="1" cellpadding="0" width="100%">
	<tr><td bgcolor="#000000" class="title_minibox">
		<font color="#FFFFFF" class="title_minibox">
   		<!-- title box -->
   		<?=_("Copy reports")?>
   		<!-- end title box -->
   		</font>
	</td></tr>

   	<tr><td bgcolor="#FFFFFF" class="text_minibox">

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
      <form name="mini" method="post">
      <tr>
        <td style="background: #FFFFFF; color: #000000; text-align: center">
          <input type="hidden" name="copy_day" value="<?=$day?>">  
          <input type="text" name="day" value="<?=$day?>" size="10"
          style="color: #000000; width: 100%; height: 20px"
          onchange="javascript: document.mini_calendar.submit();">
        </td>
      </tr>
      <tr>
        <td style="color: #000000; text-align: right">
          <input type="submit" name="copy" value="<?=_("To day")?>" style="width: 100%; height: 20px"> 
          </td>
      </tr>
      <tr>
        <td colspan="2">
          <input type="submit" name="copy2" value="<?=_("From yesterday")?>" style="width: 100%; height: 20px">
        </td>
      </tr>
      </form>
    </table>
   </td></tr>
   </table></td></tr></table>
   <!-- end box -->
