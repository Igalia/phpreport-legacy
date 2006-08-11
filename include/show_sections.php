   <!-- box -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   	<table border="0" cellspacing="1" cellpadding="0" width="100%">
	<tr><td bgcolor="#000000" class="title_minibox">
		<font color="#FFFFFF" class="title_minibox">
   		<!-- title box -->
   		<?=_("Sections")?>
   		<!-- end title box -->
   		</font>
	</td></tr>

   	<tr><td bgcolor="#FFFFFF" class="text_minibox">
   		<table border="0" cellspacing="0" cellpadding="10">
		<tr><td align="left">
   			<font color="#000000" class="text_minibox">
			<!-- text box -->
   			<a href="report.php?day=<?=$day?>" style="font-weight: bold;">- <?=_("Report edition")?></a>
			<br>
   			<a href="uploadxml.php" style="font-weight: bold;">- <?=_("XML import")?></a>    			
                        <br>
                        <a href="consult.php?day=<?=$day?>" style="font-weight: bold;">- <?=_("Results extraction")?></a>
<?
if ($authentication_mode=="sql") {
?>
			<br>
			   <a href="profile.php?day=<?=$day?>" style="font-weight: bold;">- <?=_("Password change")?></a>
<?
}
?>
			<br>
			   <a href="login.php?logout=1" style="font-weight: bold;">- <?=_("Exit")?></a>
			<!-- end text box -->
			</font>
		</td></tr>
		</table>
	</td></tr>
<?
if (in_array("informesadm",(array)$session_groups)) {
?>
   <tr><td bgcolor="#FFFFFF" class="text_minibox">
   <table border="0" cellspacing="0" cellpadding="10"><tr><td
    align="left">
   <font
    color="#000000" class="text_minibox">
   <!-- text box -->
    <a href="block.php?day=<?=$day?>"
     style="font-weight: bold;">- <?=_("Block reports")?></a>
   <br>
    <a href="adminlabels.php?day=<?=$day?>"
     style="font-weight: bold;">- <?=_("Labels")?></a>
   <br>
    <a href="projevaluation.php?day=<?=$day?>"
     style="font-weight: bold;">- <?=_("Project evaluation")?></a>
   <br>
    <a href="users.php?day=<?=$day?>"
     style="font-weight: bold;">- <?=_("Users management")?></a>
   <br>
    <a href="projects.php?day=<?=$day?>"
     style="font-weight: bold;">- <?=_("Project management")?></a>
<!-- end text box -->
   </font></td></tr></table></td></tr>
<?
}
?>
   </table></td></tr></table>
   <!-- end box -->
