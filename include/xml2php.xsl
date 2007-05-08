<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- xml2php.xsl: This file defines all PHP sentences needed to input         -->
<!-- all the data of a XML weekly report into the DB.                         -->
<!-- To do that, a started session in the DB defined by $cnx is needed        -->
<!-- together with $session_uid variable, which identifies the user that      -->
<!-- inputs the data.                                                         -->

<!-- This stylesheet needs to receive 6 parameters: start year, start month,  -->
<!-- start day, end year, end month and end day, that is, the dates (week)    -->
<!-- for which the report ranges (they are in the XML report filename).       -->
<!-- This XSL doesn't verify that the data is coherent, and if some error     -->
<!-- happens during interpretation of the generated PHP code, an $error       -->
<!-- variable will appear defined containing the related message.             -->

<!-- Before doing the report task saving onto DB, all the existing tasks for  -->
<!-- that period are deleted.                                                 -->

<!-- This is designed to be used with PHP "eval" directive, which evaluates   -->
<!-- the PHP code contained in a variable.                                    -->

<!-- xml2php.xsl,  José Riguera, 2003 <jriguera@igalia.com>                   -->

<xsl:output method="text" encoding="UTF-8"/>

<xsl:param name="Iuser" select="0"/>

<xsl:param name="Iyearini" select="0"/>
<xsl:param name="Imonthini" select="0"/>
<xsl:param name="Idayini" select="0"/>

<xsl:param name="Iyearend" select="3000"/>
<xsl:param name="Imonthend" select="12"/>
<xsl:param name="Idayend" select="31"/>



<xsl:template name="Program" match="/">

	<xsl:text>
	
	/* INSERTION OF ALL THE TASKS INTO A WEEKLY REPORT */
	
        $num_tasks = 0;
        $num_days = 0;
        
        if (empty($session_uid)) $error = _("without session or with an unknown one.");
        if (empty($cnx)) $error = _("the DB session was not started.");
        
        $user = "</xsl:text><xsl:value-of select="$Iuser"/><xsl:text>";
        
        if (empty($user)) $error = _("without session or with an unknown one.");
        
 	/* LOCK MANAGEMENT */

	if (empty($error))
        {        
          	/* REPORTS AND TASKS SAVING TRANSACTION */

		$query = "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; BEGIN TRANSACTION; ";
  		if (!@pg_exec($cnx, $query)) 
                {
   			$error = _("transaction starting impossible.");
  		}
                else
                {
			$query = "SELECT _date FROM block WHERE uid='$user'";
			if (!$result = @pg_exec($cnx, $query)) $error = _("lock date computing impossible.");
	    		else
	    		{
	   			if (@pg_numrows($result) == 0)
	   			{
	    				/* DEFAULT LOCK SETTING */
	    				@pg_freeresult($result);
	    				$query = "INSERT INTO block (uid,_date) VALUES ('$user', '1999-12-31')";
              if (!$result = @pg_exec($cnx, $query)) 
                $error = _("default lock setting impossible.");
	    				else @pg_freeresult($result);
	   			}
	   			else
	   			{
	   				$locked = false;
					if ($row = @pg_fetch_row($result)) $locked = !('</xsl:text><xsl:call-template name="DateInf2Sql"/><xsl:text>' > $row[0]);
					@pg_freeresult($result);
          if ($locked) 
            $error = sprintf(_('report inserting is locked for dates before %1$s .'),
              $row[0]);
	   			}
			}
                        
                        /* BEFORE, PREVIOUSLY INSERTED TASKS SHOULD BE DELETED */
                        
			if (empty($error))
                        {
				$query = "DELETE FROM task WHERE uid='$user' AND _date BETWEEN DATE('</xsl:text>
				<xsl:call-template name="Date2Sql">
					<xsl:with-param name="day" select="$Idayini"/>
					<xsl:with-param name="month" select="$Imonthini"/>
					<xsl:with-param name="year" select="$Iyearini"/>
				</xsl:call-template><xsl:text>') AND DATE('</xsl:text>
				<xsl:call-template name="Date2Sql">
					<xsl:with-param name="day" select="$Idayend"/>
					<xsl:with-param name="month" select="$Imonthend"/>
					<xsl:with-param name="year" select="$Iyearend"/>
				</xsl:call-template><xsl:text>')";
                                
	    			if (!$result = @pg_exec($cnx, $query)) $error = _("previous task deletion impossible ...");
	    			else @pg_freeresult($result);
                        }                                               
                        
                        /* Gets HTML entity to symbol translation table */
                        $trans_table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
                        $trans_table = array_flip($trans_table);
                        $trans_table["&amp;apos;"] = "'"; 
                        $trans_table["&amp;divide;"] = "\n"; 
                        
			/* ALL REPORT TASKS INSERTION */
		
	</xsl:text>

	<xsl:variable name="monthsreport" select="count(weeklyDedication/dedication)"/>

	<xsl:variable name="rangesmonths">
  		<xsl:choose>
    			<xsl:when test="$Imonthend = $Imonthini">
  				<xsl:value-of select="1"/>
      			</xsl:when>
    			<xsl:otherwise>
				<xsl:value-of select="2"/>
      			</xsl:otherwise>
    		</xsl:choose>
  	</xsl:variable>

	<xsl:choose>
		<xsl:when test="$monthsreport = 2">

  			<xsl:apply-templates select="weeklyDedication/dedication[1]">
				<xsl:with-param name="year" select="$Iyearini"/>
  			</xsl:apply-templates>
  
  			<xsl:apply-templates select="weeklyDedication/dedication[2]">
				<xsl:with-param name="year" select="$Iyearend"/>
  			</xsl:apply-templates>
    
    		</xsl:when>
		<xsl:when test="$monthsreport = 1"> 
    			<xsl:choose>
    				<xsl:when test="$monthsreport != $rangesmonths">
      
					<xsl:variable name="numbermonth">
						<xsl:call-template name="Month2number">
							<xsl:with-param name="month" select="weeklyDedication/dedication[1]/@month"/>
						</xsl:call-template>
					</xsl:variable>
          
 					<xsl:choose>
	 					<xsl:when test="$numbermonth = $Imonthini">
  	 					<xsl:apply-templates select="weeklyDedication/dedication[1]">
    							<xsl:with-param name="year" select="$Iyearini"/>
      						</xsl:apply-templates>
						</xsl:when>
						<xsl:otherwise>
						 	<xsl:apply-templates select="weeklyDedication/dedication[1]">
								<xsl:with-param name="year" select="$Iyearend"/>
							</xsl:apply-templates>
 						</xsl:otherwise>
 					</xsl:choose>
          
				</xsl:when>
    				<xsl:otherwise>
        
	  				<xsl:apply-templates select="weeklyDedication/dedication[1]">
						<xsl:with-param name="year" select="$Iyearini"/>
  					</xsl:apply-templates>
          
      				</xsl:otherwise>
    			</xsl:choose>
    		</xsl:when>                           
		<xsl:otherwise>
    
			<xsl:text>
      
  			$error = _("incorrect report");
        
      			</xsl:text>
      
    		</xsl:otherwise>
	</xsl:choose>

	<xsl:text>

  			if (empty($error)) $query = "COMMIT TRANSACTION";
  			else $query = "ROLLBACK TRANSACTION";
  
   			if (!@pg_exec($cnx,$query)) $error = _("transaction closing impossible.");
		}
		/* END OF TASK INSERTION */
        }
	
	</xsl:text>

</xsl:template>



<xsl:template name="InsertTask" match="dedication">
	<xsl:param name="year"/>

	<xsl:variable name="nummonth">
		<xsl:call-template name="Month2number">
			<xsl:with-param name="month" select="@month"/>
		</xsl:call-template>
	</xsl:variable>

	<xsl:for-each select="dailyDedication">
		<xsl:variable name="day" select="@day"/>

               	<xsl:text>

		            $num_days++;
                $new_day = true;
                        
                </xsl:text>


		<xsl:for-each select="task">
		                
			<xsl:variable name="customer">
				<xsl:choose>
					<xsl:when test="@customer">
						<xsl:value-of select="@customer"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text></xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<xsl:variable name="name">
				<xsl:choose>
					<xsl:when test="@name">
						<xsl:value-of select="@name"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text></xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<xsl:variable name="ttype">
				<xsl:choose>
					<xsl:when test="@ttype">
						<xsl:value-of select="@ttype"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text></xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
      <xsl:variable name="telework">
        <xsl:choose>
          <xsl:when test="@telework">
            <xsl:text>true</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>false</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      
			<xsl:text>
			
			if (empty($error))
			{
				/* TASK INSERT */

				$query = "SELECT modification_date FROM report WHERE uid='$user' AND _date='</xsl:text>
				<xsl:call-template name="Date2Sql">
					<xsl:with-param name="day" select="$day"/>
					<xsl:with-param name="month" select="$nummonth"/>
					<xsl:with-param name="year" select="$year"/>
				</xsl:call-template><xsl:text>'";
			
				if (!$result = @pg_exec($cnx, $query)) $error = _("last modification time can't be computed.");
				else
				{
  			 		if (@pg_numrows($result) > 0)
   					{
   						@pg_freeresult($result);
						$query = "UPDATE report SET modification_date=now() WHERE uid='$user' AND _date='</xsl:text>
						<xsl:call-template name="Date2Sql">
							<xsl:with-param name="day" select="$day"/>
							<xsl:with-param name="month" select="$nummonth"/>
							<xsl:with-param name="year" select="$year"/>
						</xsl:call-template><xsl:text>'";
					
  		  				if (!$result = @pg_exec($cnx, $query)) $error = _("last modification time updating impossible.");
   					}
   					else
   					{
   						@pg_freeresult($result);
  						$query = "INSERT INTO report (uid,_date,modification_date) VALUES ('$user','</xsl:text>
						<xsl:call-template name="Date2Sql">
							<xsl:with-param name="day" select="$day"/>
							<xsl:with-param name="month" select="$nummonth"/>
							<xsl:with-param name="year" select="$year"/>
						</xsl:call-template><xsl:text>',now())";
					
						if (!$result = @pg_exec($cnx, $query)) $error = _("modification time setting impossible.");
    					}
				}
			
				if (empty($error))
				{                                
                                        /*
                                        // If this is activated, complementary reports will be allowed for
                                        // insertion into the DB, but that isn't wanted...
                                        
                                	if ($new_day)
                                        {
						$query = "DELETE FROM task WHERE uid='$user' AND _date='</xsl:text>
                                        	<xsl:call-template name="Date2Sql">
					    		<xsl:with-param name="day" select="$day"/>
					    		<xsl:with-param name="month" select="$nummonth"/>
					      		<xsl:with-param name="year" select="$year"/>
						</xsl:call-template><xsl:text>'";

  						if (!@pg_exec($cnx, $query)) 
                                        	{
                                        		$error = _("date preinsertion into the DB impossible.");
                                        	}
                                                $new_day = false;
                                        }
                                        */
                                        
  					if (empty($error))
  					{
                                        	$task_text = "</xsl:text><xsl:apply-templates select="."/><xsl:text>";
                                                
                                                $task_text =  addslashes(strtr($task_text, $trans_table));
                                        
						$query = "INSERT INTO task (uid,_date,init,_end,customer,name,type,story,ttype,telework,text) VALUES ('$user','</xsl:text>
						<xsl:call-template name="Date2Sql">
							<xsl:with-param name="day" select="$day"/>
							<xsl:with-param name="month" select="$nummonth"/>
							<xsl:with-param name="year" select="$year"/>
						</xsl:call-template><xsl:text>', '</xsl:text>
						<xsl:call-template name="Time2Sqlm">
							<xsl:with-param name="time" select="@start"/>
						</xsl:call-template><xsl:text>', '</xsl:text>
						<xsl:call-template name="Time2Sql24m">
							<xsl:with-param name="time" select="@end"/>
						</xsl:call-template><xsl:text>',
						'</xsl:text>
						<xsl:value-of select="$customer"/><xsl:text>', '</xsl:text>
						<xsl:value-of select="$name"/><xsl:text>', lower('</xsl:text>
						<xsl:value-of select="@type"/><xsl:text>'), '</xsl:text>
						<xsl:value-of select="@story"/><xsl:text>', '</xsl:text>
            <xsl:value-of select="$ttype"/><xsl:text>', '</xsl:text>
            <xsl:value-of select="$telework"/><xsl:text>', '$task_text')";
     
   				 		if (!@pg_exec($cnx, $query)) $error = _("DB value inserting impossible.");
                                                else $num_tasks++; 
					}
				}
				
				/* TASK INSERTION END	*/
			}
			
			</xsl:text>
		</xsl:for-each>
		
	</xsl:for-each>
</xsl:template>



<xsl:template name="DateInf2Sql">
	<xsl:value-of select="$Iyearini"/><xsl:text>-</xsl:text>

	<xsl:variable name="len1">
		<xsl:value-of select="string-length($Imonthini)" />
	</xsl:variable>
        <xsl:if test="2 != $len1">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$Imonthini"/><xsl:text>-</xsl:text>

	<xsl:variable name="len2">
		<xsl:value-of select="string-length($Idayini)" />
	</xsl:variable>
        <xsl:if test="2 != $len2">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$Idayini"/>
</xsl:template>



<xsl:template name="Date2Sql">
	<xsl:param name="day"/>
	<xsl:param name="month"/>
	<xsl:param name="year"/>

	<xsl:value-of select="$year"/><xsl:text>-</xsl:text>
	<xsl:variable name="len1">
		<xsl:value-of select="string-length($month)" />
	</xsl:variable>
        <xsl:if test="2 != $len1">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$month"/><xsl:text>-</xsl:text>
	<xsl:variable name="len2">
		<xsl:value-of select="string-length($day)" />
	</xsl:variable>
        <xsl:if test="2 != $len2">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$day"/>
</xsl:template>



<xsl:template name="Time2Sql">
	<xsl:param name="time"/>

	<xsl:variable name="m" select="$time mod 100"/>
	<xsl:variable name="h" select="($time - $m) div 100"/>

        <xsl:if test="10 > $h">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$h"/>
	<xsl:text>:</xsl:text>
        <xsl:if test="10 > $m">
        	<xsl:text>0</xsl:text>
        </xsl:if>
	<xsl:value-of select="$m"/>
	<xsl:text>:00</xsl:text>
</xsl:template>



<xsl:template name="Time2Sqlm">
	<xsl:param name="time"/>

	<xsl:variable name="m" select="$time mod 100"/>
	<xsl:variable name="h" select="($time - $m) div 100"/>

	<xsl:value-of select="($h * 60) + $m"/>
</xsl:template>



<xsl:template name="Time2Sql24m">
	<xsl:param name="time"/>

	<xsl:variable name="m" select="$time mod 100"/>
	<xsl:variable name="h" select="($time - $m) div 100"/>
        
        
      	<xsl:choose>
      		<xsl:when test="($m = $h) and (0 = $m)">
                	<xsl:value-of select="24 * 60"/>
		</xsl:when>
		<xsl:otherwise>
                	<xsl:value-of select="($h * 60) + $m"/>
     		</xsl:otherwise>
     	</xsl:choose>
</xsl:template>



<xsl:template name="Month2number">
	<xsl:param name="month"/>

	<xsl:variable name="monthlowercase">
		<xsl:value-of select="translate($month,'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')"/>
	</xsl:variable>

	<xsl:choose>
		<xsl:when test="'january' = $monthlowercase">
			<xsl:text>1</xsl:text>
		</xsl:when>
		<xsl:when test="'february' = $monthlowercase">
			<xsl:text>2</xsl:text>
		</xsl:when>
		<xsl:when test="'march' = $monthlowercase">
			<xsl:text>3</xsl:text>
		</xsl:when>
		<xsl:when test="'april' = $monthlowercase">
			<xsl:text>4</xsl:text>
		</xsl:when>
		<xsl:when test="'may' = $monthlowercase">
			<xsl:text>5</xsl:text>
		</xsl:when>
		<xsl:when test="'june' = $monthlowercase">
			<xsl:text>6</xsl:text>
		</xsl:when>
		<xsl:when test="'july' = $monthlowercase">
			<xsl:text>7</xsl:text>
		</xsl:when>
		<xsl:when test="'august' = $monthlowercase">
			<xsl:text>8</xsl:text>
		</xsl:when>
		<xsl:when test="'september' = $monthlowercase">
			<xsl:text>9</xsl:text>
		</xsl:when>
		<xsl:when test="'october' = $monthlowercase">
			<xsl:text>10</xsl:text>
		</xsl:when>
		<xsl:when test="'november' = $monthlowercase">
			<xsl:text>11</xsl:text>
		</xsl:when>
		<xsl:when test="'december' = $monthlowercase">
			<xsl:text>12</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>-1</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>



<xsl:template match="task">

	<xsl:variable name="txt">
	  	<xsl:call-template name="escape">
	    		<xsl:with-param name="arg" select="text()"/>
	  	</xsl:call-template>
	</xsl:variable>

<!--	<xsl:variable name="txtreplace">
		<xsl:call-template name="chop">	
			<xsl:with-param name="text" select="$txt" />
		</xsl:call-template>
	</xsl:variable>
-->
	<xsl:value-of select="$txt" /> 
	
</xsl:template>



<xsl:template name="chop">
	<xsl:param name="text"/>

	<xsl:variable name="len">
		<xsl:value-of select="string-length($text)" />
	</xsl:variable>

	<xsl:variable name="outside">
		<xsl:value-of select="substring($text, $len)" />
	</xsl:variable>

	<xsl:call-template name="escape-one">
        	<xsl:with-param name="arg" select="$text"/>
             	<xsl:with-param name="target" select="$outside"/>
             	<xsl:with-param name="replace" select="'&amp;divide;'"/>
        </xsl:call-template>

</xsl:template>


<!-- Gotten and corrected from http://www.tackline.demon.co.uk/xslt/escape/xslt/escape.xsl -->

<xsl:template name="escape">
  <xsl:param name="arg"/>
  <xsl:call-template name="escape-one">
    <xsl:with-param name="arg">
      <xsl:call-template name="escape-one">
        <xsl:with-param name="arg">
          <xsl:call-template name="escape-one">
            <xsl:with-param name="arg">
              <xsl:call-template name="escape-one">
                <xsl:with-param name="arg">
                  <xsl:call-template name="escape-one">
                    <xsl:with-param name="arg" select="$arg"/>
                    <xsl:with-param name="target" select="'&amp;'"/>
                    <xsl:with-param name="replace" select="'&amp;amp;'"/>
                  </xsl:call-template>
                </xsl:with-param>
                <xsl:with-param name="target" select="'&lt;'"/>
                <xsl:with-param name="replace" select="'&amp;lt;'"/>
              </xsl:call-template>
            </xsl:with-param>
            <xsl:with-param name="target" select="'&gt;'"/>
            <xsl:with-param name="replace" select="'&amp;gt;'"/>
          </xsl:call-template>
        </xsl:with-param>
        <xsl:with-param name="target" select="'&quot;'"/>
        <xsl:with-param name="replace" select="'&amp;quot;'"/>
      </xsl:call-template>
    </xsl:with-param>
    <xsl:with-param name="target" select='"&apos;"'/>
    <xsl:with-param name="replace" select='"&amp;apos;"'/>
  </xsl:call-template>
</xsl:template>



<xsl:template name="escape-one">
	<xsl:param name="arg"/>
  	<xsl:param name="target"/>
  	<xsl:param name="replace"/>

  	<xsl:choose>
    		<xsl:when test="contains($arg, $target)">
      			<xsl:variable name="before" select="substring-before($arg, $target)"/>
      			<xsl:variable name="after" select="substring-after($arg, $target)"/>

      			<xsl:value-of select="$before"/>
      			<xsl:value-of select="$replace"/>   

      			<xsl:call-template name="escape-one">
			        <xsl:with-param name="arg" select="$after"/>
			        <xsl:with-param name="target" select="$target"/>
			        <xsl:with-param name="replace" select="$replace"/>
		      </xsl:call-template>
	    	</xsl:when>
	    	<xsl:otherwise>
		      <xsl:value-of select="$arg"/>
    		</xsl:otherwise>
  	</xsl:choose>
</xsl:template>



</xsl:stylesheet>

