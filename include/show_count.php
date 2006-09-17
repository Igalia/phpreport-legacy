   <!-- box -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   	<table border="0" cellspacing="1" cellpadding="0" width="100%">
	<tr><td bgcolor="#000000" class="title_minibox">
		<font color="#FFFFFF" class="title_minibox">
   		<!-- title box -->
   		<?=_("Hour count")?>
   		<!-- end title box -->
   		</font>
	</td></tr>

   	<tr><td bgcolor="#FFFFFF" class="text_minibox">
   		<table border="0" cellspacing="0" cellpadding="10">
		<tr><td align="left">
   			<font color="#000000" class="text_minibox">
			<!-- text box -->
			<?=_("Today")?>: <?=$daily_minutes/60?>h
			(<?=hour_sql_to_web($daily_minutes) ?>)
			<br>
			<?=_("Week")?>: <?=$weekly_minutes/60?>h
			(<?=hour_sql_to_web($weekly_minutes) ?>)			
			<!-- end text box -->
			</font>
		</td></tr>
		</table>
	</td></tr>
   </table></td></tr></table>
   <!-- end box -->
