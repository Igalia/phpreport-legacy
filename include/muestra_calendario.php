   <!-- caja -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%">
   <tr><td bgcolor="#000000">
   <table border="0" cellspacing="1" cellpadding="0" width="100%"><tr>
   <!-- título caja -->
   <td bgcolor="#000000" class="titulo_minicaja"
    ><a href="?dia=<?=$dia_mes_anterior?>"
    ><font color="#FFFFFF" class="titulo_minicaja">
   &nbsp;&lt;&nbsp;
   </font></a></td>
   <td bgcolor="#000000" class="titulo_minicaja" width="100%"
    ><font color="#FFFFFF" class="titulo_minicaja">
   Calendario
   </font></td>
   <td bgcolor="#000000" class="titulo_minicaja"
    ><a href="?dia=<?=$dia_mes_siguiente?>"
    ><font color="#FFFFFF" class="titulo_minicaja">
   &nbsp;&gt;&nbsp;
   </font></a></td>
   </tr><tr><td bgcolor="#FFFFFF" class="texto_minicaja" colspan="3">
   <!-- fin título caja -->
   <table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td
    align="center">
   <!-- texto caja -->
   <table cellpadding="3" cellspacing="0" style="text-align: center;"
    width="100%">
     <?
      $estilo=array(
       "T"=>"background: #C0C0D0; color: #000000; font-weight: bold; text-align: center",
       "G"=>"background: #E0E0F0; color: #808080; font-weight: regular; text-align: center",
       "N"=>"background: #E0E0F0; color: #000000; font-weight: regular; text-align: center",
       "H"=>"background: #C0C0D0; color: #000000; font-weight: regular; text-align: center"
      );
     ?>
    <tr>
     <td colspan="7" style="<?=$estilo["T"]?>">
      <?=$arrayDMA[0]?> de <?=$meses_minical[$arrayDMA[1]-1]?> de <?=$arrayDMA[2]?>
     </td>
    </tr>
    <tr>
     <?
      // Cálculo de los títulos de los días
      foreach ($dias_minical as $d) {
     ?>
     <td style="<?=$estilo["T"]?>">
     <?=$d?>
     </td>
     <?
      }
     ?>
    </tr>
    <?
     foreach ($calendario as $s) {
    ?>
   <tr>
     <?
      foreach ($s as $d) {
     ?>
     <td style="<?=$estilo[$d[1]]?>">
      <a href="?dia=<?=$d[2]?>" style="<?=$estilo[$d[1]]?>"
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
   <form name="mini_calendario" method="get">
   <tr><td style="background: #FFFFFF; color: #000000; text-align: center">
   <input type="text" name="dia" value="<?=$dia?>" size="10"
    style="color: #000000; width: 100%; height: 20px"
    onchange="javascript: document.mini_calendario.submit();">
   </td><td style="color: #000000; text-align: right">
   <input type="submit" name="cambiar" value="Ir a día" style="width: 100%; height: 20px">
   </td></tr>
   </form>
   <form>
   <tr>
    <td colspan="2">
     <input type="submit" value="Ir a hoy" style="width: 100%; height: 20px">
    </td>
   </tr>
   </form>
   </table>

   <!-- fin texto caja -->
   </td></tr></table></td></tr></table></td></tr></table>
   <!-- fin caja -->
