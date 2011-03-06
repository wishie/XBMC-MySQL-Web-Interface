<link rel="stylesheet" type="text/css" href="styles.css" />
<?php

require('access.php');
require('config.php');

function thumbnailHash($input) {
        $chars = strtolower($input);
        $crc = 0xffffffff;
        for ($ptr = 0; $ptr < strlen($chars); $ptr++) {
                $chr = ord($chars[$ptr]);
                $crc ^= $chr << 24;
                for ($i=0; $i<8; $i++){
                        if ($crc & 0x80000000) {
                                $crc = ($crc << 1) ^ 0x04C11DB7;
                        } else {
                                $crc <<= 1;
                        }
                }
        }
        //Formatting the output in a 8 character hex
        if ($crc>=0){
                //positive results will hash properly without any issues
                return sprintf("%08s",sprintf("%x",sprintf("%u",$crc)));
        } else {
                /*
                 * negative values will need to be properly converted to 
                 * unsigned integers before the value can be determined. 
                 */
                return sprintf("%08s",gmp_strval(gmp_init(sprintf("%u",$crc)),16));
        }
}

mysql_pconnect($hostname, $db_user, $db_pass);
mysql_select_db($database);

if(!isset($_GET['movieid'])){ ?>

<script type="text/javascript">
<!--
function delayer(){
    window.location = "index.php"
}
//-->
</script>
<body onLoad="setTimeout('delayer()', 5000)">
<h2>An error has occured!</h2>
<p>This page has been called without a valid MovieID.. Please wait while we return you to the main list</p>
</body>

<?php exit;};?>

<?php
$query  = "SELECT * FROM movie WHERE idMovie = ". $_GET["movieid"]."";
$result = mysql_query($query);

while($row = mysql_fetch_row($result))
{
    $movieid    = $row[0];
    $fileid     = $row[1];
    $title      = $row[2];
    $plot       = $row[3];
    $outline    = $row[4];
    $tagline    = $row[5];
    $numvotes   = $row[6];
    $rating     = $row[7];
    $writer     = $row[8];
    $year       = $row[9];
    $thumb      = $row[10];
    $imdbid     = $row[11];

    $runtime    = $row[13];
    $runtimeh	= floor(substr($runtime, 0, 3) / 60);
    $runtimem	= $runtime - ($runtimeh * 60);
    $rated      = $row[14];

    $genre      = $row[16];
    $director   = $row[17];

    $studio     = $row[20];

    $fanart     = $row[22];

    $resquery   = mysql_query("SELECT iVideoHeight AS vertres, iVideoWidth AS horizres, strAudioCodec AS acodec, iAudioChannels AS achannels FROM streamdetails WHERE idFile = '$fileid' AND iStreamType = '0'");
    $resquery2  = mysql_query("SELECT strAudioCodec AS acodec, iAudioChannels AS achannels FROM streamdetails WHERE idFile = '$fileid' AND iStreamType = '1'");
    $resresult  = mysql_fetch_row($resquery);
    $resresult2 = mysql_fetch_row($resquery2);
    $vertres    = $resresult[0];
    $horizres   = $resresult[1];
    $acodec     = $resresult2[0];
    $achannels  = $resresult2[1];

    $filequery  = mysql_query("SELECT strPath AS path, strFileName AS filename FROM movieview WHERE idFile = '$fileid'");
    $fileresult = mysql_fetch_row($filequery);
    $filepath   = $fileresult[0];
    $filename   = $fileresult[1];
    if(substr($filename, 0, 8) == "stack://"){
    $filename	= explode(",", $filename);
    $filename	= str_replace("$filepath", "", $filename[0]);
    $filename	= str_replace("stack://", "", $filename);
    ;};
    $fullpath   = trim($filepath.$filename);
    $finalfile  = $fullpath;
    $playfile	= urlencode($finalfile);
    $thumbhash  = thumbnailHash("$finalfile");

    if(isset($_GET['play'])){
	$fh = fopen('/dev/null', 'w');
   	$ch = curl_init();
	if(!isset($_GET['location'])){
		$_GET['location'] = "lounge";
	};
	if($_GET['location'] == "lounge"){
		curl_setopt($ch, CURLOPT_URL, "http://$xbmc_lounge/xbmcCmds/xbmcHttp?command=ExecBuiltIn(PlayMedia(\"$playfile\"))");
	};
	curl_setopt($ch, CURLOPT_FILE, $fh); 
	curl_exec($ch);
	curl_close($ch);
	fclose($fh);
    };

?>
<table width="900" cellpadding="0" cellspacing="0" border="1" bordercolor="black">
	<tr>
		<td>
<table width="900" cellpadding="0" cellspacing="0" border="0" bordercolor="black">
	<tr>
<td width="300" valign="" bgcolor="#eeeeee">
	<img height="500" src="Thumbnails/Video/<?php echo $thumbhash[0];?>/<?php echo $thumbhash;?>.tbn">
</td>
<td width="600" valign="top">
	<table width="600" cellpadding="0" cellspacing="0" border="0">
		<tr height="40">
			<td bgcolor="#eeeeee" class="title">&nbsp;<?php echo $title; if($year != "0") echo " (".$year.") ";?></td>
		</tr>
		<tr height="30">
			<td class="desc">&nbsp;<?php echo $genre;?></td>
		</tr>
		<tr height="20">
			<td class="desc">&nbsp;Tagline: <?php echo $tagline;?></td>
		</tr>
		<tr height="282" valign="top">
			<td><table cellpadding="5" border="0"><tr><td class="desc"><?php echo $plot;?></td></tr></table></td>
		</tr>
		<tr height="60" valign="top">
			<td>
				<table width="100%" cellpadding="5" cellspacing="0" border="0">
					<tr>
						<td colspan="2" class="desc">Classification: <?php echo $rated;?></td>
					</tr>
					<tr>
						<td class="desc" width="300">Runtime: <?php echo $runtimeh."hr ".$runtimem."mins";?></td>
						<td class="desc" width="300">Rating: <?php echo substr($rating,0,3)."/10 (From ".$numvotes." votes)";?></td>
					</tr>
					<tr>
						<td class="desc" width="300">Director: <?php echo $director;?></td>
						<td class="desc" width="300">Writer: <?php echo $writer;?> </td>
					</tr>
					<tr>
						<td class="desc" width="300">Play in: <a class="desc" href="movieinfo.php?movieid=<?php echo $movieid;?>&play&location=lounge">Lounge Room</a></td>
						<td class="desc" width="300">Links: <a class="desc" target="_blank" href="http://www.imdb.com/title/<?php echo $imdbid;?>">IMDB</a> | <a class="desc" href="Thumbnails/Video/Fanart/<?php echo $thumbhash;?>.tbn" onClick="popup = window.open('Thumbnails/Video/Fanart/<?php echo $thumbhash;?>.tbn', 'PopupPage', 'height=720, width=1280, scrollbars=yes, resizeable=no'); return false" target="_blank">Fanart</a></td>
					</tr>

				</table>
			</td>
		</tr>
		<tr height="50">
			<td bgcolor="#eeeeee">
			<?php
				//Video resolutions
				if($horizres == "1280"){ echo "<img src=\"img/video/720.png\">" ;};
				if($horizres == "1920"){ echo "<img src=\"img/video/1080.png\">";};
				if($horizres < "1280"){echo "<img src=\"img/video/sd.png\">";};
				//Audio codecs
				if($acodec == "mp3"){echo "<img src=\"img/audio/mp3.png\">";};
				if($acodec == "ac3"){echo "<img src=\"img/audio/ac3.png\">";};
				if($acodec == "vorbis"){echo "<img src=\"img/audio/ogg.png\">";};
				if($acodec == "aac"){echo "<img src=\"img/audio/aac.png\">";};
				if($acodec == "dca" || $acodec == "dts"){echo "<img src=\"img/audio/dts.png\">";};
				//Audio channels

				if($achannels == "1"){echo "<img src=\"img/audio/1.png\">";};
				if($achannels == "2"){echo "<img src=\"img/audio/2.png\">";};
				if($achannels == "6"){echo "<img src=\"img/audio/6.png\">";};
			?>
			</td>
		</tr>
	</table>
</td>

<?php
}
?>
	</tr>
</table>
		</td>
	</tr>
</table>
<table width="900" cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td class="mainlist" align="center">
<br>
<center><a class="nav" href="index.php">BACK TO MOVIE LIST</a></center>
<?php
    if(isset($_GET['stop'])){
        $fh = fopen('/dev/null', 'w');
        $ch = curl_init();
        if(!isset($_GET['location'])){
                $_GET['location'] = "lounge";
        };
        if($_GET['location'] == "lounge"){
                curl_setopt($ch, CURLOPT_URL, "http://$xbmc_lounge/xbmcCmds/xbmcHttp?command=ExecBuiltIn(PlayerControl(stop))");
        };
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_exec($ch);
        curl_close($ch);
        fclose($fh);
    };
?>
<center>
Stop playback: 
<a class="nav" href="movieinfo.php?movieid=<?php echo $movieid;?>&stop&location=lounge">Lounge</a><br>
<a class="nav" href="?action=logout">LOGOUT</a>
</center>
		</td>
	</tr>
</table>
