<link rel="stylesheet" type="text/css" href="styles.css" />
<?php

require('access.php');
require('config.php');

$counter = "0";
$cellbg  = "0";
$limit	 = "15";

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

$totalmovies = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM movie"));
if(!isset($_POST["startnum"])){ $_POST["startnum"] = "0" ;};
if($_GET["startnum"] == ""){ $_GET["startnum"] = "0";};
if($_GET["startnum"] > $totalmovies[0]){
	$startnum = "0";
}ELSE{
	$startnum = $_POST["startnum"];
};

if(!isset($_GET['all'])){
	$query  = "SELECT * FROM movie ORDER BY c00 LIMIT $startnum, $limit";
}else{
	$query	= "SELECT * FROM movie";
	if(isset($_GET['search'])){
		$searchterm = $_POST['searchterm'];
		if($_POST['st'] == "titlesearch"){
			$query .= " WHERE c00 LIKE '%$searchterm%'";
		}
		if($_POST['st'] == "genresearch"){
			$query .= " WHERE c14 LIKE '%$searchterm%'";
		}
	}
	$query .= " ORDER BY c00";
}
$result = mysql_query($query);
?>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr class="mainlist">
		<td colspan="3">
			<h1>Movie list
			<?php
			if($_POST['searchterm'] == ""){
				unset($_POST['searchterm']);
			}
			if(isset($_POST['searchterm'])){ 
				if($_POST['st'] == "titlesearch"){
				echo "(Title Search: ".$_POST['searchterm']." )"; 
				}
				if($_POST['st'] == "genresearch"){
				echo "(Genre Search: ".$_POST['searchterm']." )"; 
				}
			}
			?>
			</h1>
		</td>
	</tr>
	<tr class="mainlist">
		<td colspan="3">
		Search: <form method="post" action="?all&search">
		<input type="text" name="searchterm">
		<input type="radio" name="st" value="titlesearch" checked>Title Search</option>
		<input type="radio" name="st" value="genresearch">Genre Search</option>
		<input type="submit" name="submit" value="Go">
		</form></td>
	</tr>
	<tr class="mainlist">
<?php
while($row = mysql_fetch_row($result))
{
    $movieid	= $row[0];
    $fileid	= $row[1];
    $title   	= $row[2];
    $plot 	= $row[3];
    $outline 	= $row[4];
    $tagline 	= $row[5];
    $numvotes	= $row[6];
    $rating	= $row[7];
    $writer	= $row[8];
    $year 	= $row[9];
    $thumb 	= $row[10];
    $imdbid	= $row[11];

    $runtime	= $row[13];
    $runtimeh   = floor(substr($runtime, 0, 3) / 60);
    $runtimem   = $runtime - ($runtimeh * 60);

    $rated	= $row[14];

    $genre	= $row[16];
    $director	= $row[17];

    $studio	= $row[20];

    $fanart	= $row[22];

    $resquery	= mysql_query("SELECT iVideoHeight AS vertres, iVideoWidth AS horizres, strAudioCodec AS acodec, iAudioChannels AS achannels FROM streamdetails WHERE idFile = '$fileid' AND iStreamType = '0'");
    $resquery2	= mysql_query("SELECT strAudioCodec AS acodec, iAudioChannels AS achannels FROM streamdetails WHERE idFile = '$fileid' AND iStreamType = '1'");
    $resresult	= mysql_fetch_row($resquery);
    $resresult2	= mysql_fetch_row($resquery2);
    $vertres	= $resresult[0];
    $horizres	= $resresult[1];
    $acodec	= $resresult2[0];
    $achannels	= $resresult2[1];

    $filequery	= mysql_query("SELECT strPath AS path, strFileName AS filename FROM movieview WHERE idFile = '$fileid'");
    $fileresult	= mysql_fetch_row($filequery);
    $filepath	= $fileresult[0];
    $filename	= $fileresult[1];
    if(substr($filename, 0, 8) == "stack://"){
    $filename   = explode(",", $filename);
    $filename   = str_replace("$filepath", "", $filename[0]);
    $filename   = str_replace("stack://", "", $filename);
    ;};
    $fullpath   = trim($filepath.$filename);
    $finalfile  = $fullpath;
    $playfile   = urlencode($finalfile);
    $thumbhash	= thumbnailHash($finalfile);
    $cellbg++;
    if($cellbg % 2 == 0){
?>

	<td width="33%">
<?php }ELSE{ ?>
	<td width="33%" bgcolor="#EEEEEE">
<?php }; ?>
		<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td width="75">
					<a href="movieinfo.php?movieid=<?php echo $movieid;?>"><img border="0" width="75" src="Thumbnails/Video/<?php echo $thumbhash[0];?>/<?php echo $thumbhash;?>.tbn"></a>
				</td>
				<td width="10">&nbsp;</td>
				<td class="mainlist" valign="top">
					<table cellpadding="0" cellspacing="0" border="0">
						<tr>
							<td width="300"><a class="nav" href="movieinfo.php?movieid=<?php echo $movieid;?>"><?php echo $title; if($year != "0") echo " (".$year.") ";?></a></td>
							<td width="75" class="mainlist"><?php echo $runtimeh."hr ".$runtimem."mins";?></td>
						</tr>
						<tr height="70" valign="top">
							<td colspan="2" class="mainlist"><?php echo $genre;?><br><br>
							<?php if(strlen($tagline) > "150"){echo substr($tagline, 0, 150)."...";}else{echo $tagline;};?>
							</td>
						</tr>
						<tr height="10">
							<td colspan="2" class="mainlist" align="right"><a class="nav" href="movieinfo.php?movieid=<?php echo $movieid;?>">More info...</a></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
<?php 
	$counter++;
	if($counter % 3 == 0){
		echo "</tr><tr>";
		$counter = 0;
	}
?>
	
<?php }; ?>
</table>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td align="center" class="mainlist">
			<?php if(!isset($_GET['all'])){ ?>
			<form method="post" action="index.php">
			PAGE:
			<select name="startnum">
			<?php
			if(!isset($page)){
				$page = "1";
			}
			$pages = ceil($totalmovies[0] / $limit + 1);
			while($page < $pages){
			$nextpage = $page * $limit - $limit;
			if($startnum == $nextpage){
			echo "<option selected value=\"$nextpage\">$page</option>";
			}else{
			echo "<option value=\"$nextpage\">$page</option>";
			};
			$page++;}; ?>
			</select>
			<input type="submit" value="Go" name="submit">
			</form>
			Displaying movies <?php echo $startnum + 1;?> to <?php echo $startnum + $limit;?> of <?php echo $totalmovies[0];?>
			<?php }; ?>
		</td>
	</tr>
	<?php if(isset($_GET['search'])){ ?>
	<tr>
		<td><center><br><a class="nav" href="index.php">BACK TO MAIN PAGE</a></center></td>
	</tr>
	<?php ;}; ?>
	<tr>
		<td class="mainlist">
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
			<a class="nav" href="?stop&location=lounge">Lounge</a>
			<br>
			<a class="nav" href="?action=logout">LOGOUT</a>
			</center>
		</td>
	</tr>
</table>
