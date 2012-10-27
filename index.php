<?php
print "<html>
<head>
<meta http-equiv='content-type' content='text/html; charset=utf-8'>
<script type='text/javascript'>
function click_da_button () {
	lat1 = document.getElementById('lat1').value ;
	lat2 = document.getElementById('lat2').value ;
	lat3 = document.getElementById('lat3').value ;
	lat4 = document.getElementById('lat4').value ;
	latd = document.getElementById('lat_dec').value ;

	lon1 = document.getElementById('lon1').value ;
	lon2 = document.getElementById('lon2').value ;
	lon3 = document.getElementById('lon3').value ;
	lon4 = document.getElementById('lon4').value ;
	lond = document.getElementById('lon_dec').value ;
	
	if ( latd != '' ) {
		sign = latd > 0 ? 1 : -1 ;
		lat4 = latd > 0 ? 'N' : 'S' ;
		latd *= sign ;
		lat1 = Math.floor ( latd ) ;
		lat2 = Math.floor ( ( latd - lat1 ) * 60 ) ;
		lat3 = Math.floor ( ( latd - lat1 - lat2 / 60 ) * 3600 ) ;
	}
	
	if ( lond != '' ) {
		sign = lond > 0 ? 1 : -1 ;
		lon4 = lond > 0 ? 'E' : 'W' ;
		lond *= sign ;
		lon1 = Math.floor ( lond ) ;
		lon2 = Math.floor ( ( lond - lon1 ) * 60 ) ;
		lon3 = Math.floor ( ( lond - lon1 - lon2 / 60 ) * 3600 ) ;
	}
	
	p = lat1 + '_' + lat2 + '_' + lat3 + '_' + lat4 + '_' ;
	p += lon1 + '_' + lon2 + '_' + lon3 + '_' + lon4 ;
	document.getElementById('params').value = p ;
}
</script>
</head>
<body>
<table border='1'>
<form method='get' action='./geohack.php' id='theform'>

<tr>
<th>Latitude</th>
<td><input type='text' size='2' value='' id='lat1' /></td>
<td><input type='text' size='2' value='' id='lat2' /></td>
<td><input type='text' size='2' value='' id='lat3' /></td>
<td><input type='text' size='1' value='N' id='lat4' /></td>
<th>or decimal</th>
<td><input type='text' size='10' value='' id='lat_dec' /></td>
</tr>

<tr>
<th>Longitude</th>
<td><input type='text' size='2' value='' id='lon1' /></td>
<td><input type='text' size='2' value='' id='lon2' /></td>
<td><input type='text' size='2' value='' id='lon3' /></td>
<td><input type='text' size='1' value='E' id='lon4' /></td>
<th>or decimal</th>
<td><input type='text' size='10' value='' id='lon_dec' /></td>
</tr>


<tr>
<td colspan='7'>
<input type='hidden' name='params' id='params'/>
<input type='submit' value='Do it' onclick='click_da_button();'/>
</td>
</tr>

</form>
</table>
</body></html>";
?>