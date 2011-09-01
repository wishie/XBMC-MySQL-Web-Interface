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

$db = @new mysqli($hostname, $db_user, $db_pass, $database);
//$db->query("SET NAMES utf8");

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="en" />
<meta name="description" content="" />
<meta name="keywords" content="" />
<link rel="stylesheet" type="text/css" href="css/styles.css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
		$("#non-javascript").hide();
	});
</script>
<title>MediaList</title>
</head>';

if(!isset($_GET['movieid']))
{

echo '
<script type="text/javascript">
<!--
function delayer(){
    window.location = "index.php"
}
//-->
</script>
<body onLoad="setTimeout(\'delayer()\', 5000)">
<h2>An error has occured!</h2>
<p>This page has been called without a valid MovieID.. Please wait while we return you to the main list</p>
</body>';

exit;
}
else
{
    echo '<body>';
}
    
$result = $db->query("SELECT 
	idFile AS fileid,
    strPath AS path, 
    strFileName AS filename, 
    c00 AS title, 
    c01 AS plot,
    c03 AS tagline,
    c04 AS numvotes,
    c05 AS rating,
    c06 AS writer,
    c07 AS year,
    c09 AS imdbid,
    c11 AS runtime,
    c12 AS rated,
    c14 AS genre,
    c15 AS director
    FROM movieview WHERE idMovie = '$_GET[movieid]'");

while($row = $result->fetch_assoc())
{

    $title   	= htmlentities($row[title]);
    $plot 	= htmlentities($row[plot]);  
    $tagline 	= htmlentities($row[tagline]);
    $genre	= htmlentities($row[genre]);
    $writer     = htmlentities($row[writer]);
    $director   = htmlentities($row[director]);
    $rated      = htmlentities($row[rated]);
    
    $runtimeh   = floor(substr($row[runtime], 0, 3) / 60);
    $runtimem   = $row[runtime] - ($runtimeh * 60);

    $filepath	= $row[path];
    $filename	= $row[filename];
    if(substr($filename, 0, 8) == "stack://"){
	    $filename   = explode(",", $filename);
	    $filename   = str_replace("$filepath", "", $filename[0]);
	    $filename   = str_replace("stack://", "", $filename);
    }
    $fullpath   = trim($filepath.$filename);
    $finalfile  = utf8_encode($fullpath);
    $playfile   = urlencode($finalfile);
    $thumbhash	= substr(thumbnailHash($finalfile),-8);

    $res_vid   = $db->query("SELECT iVideoHeight AS vertres, iVideoWidth AS horizres, strAudioCodec AS acodec, iAudioChannels AS achannels FROM streamdetails WHERE idFile = '$row[fileid]' AND iStreamType = '0'");
    $res_aud  = $db->query("SELECT strAudioCodec AS acodec, iAudioChannels AS achannels FROM streamdetails WHERE idFile = '$row[fileid]' AND iStreamType = '1'");
    $vid  = $res_vid->fetch_assoc();
    $aud = $res_aud->fetch_assoc();

    if(isset($_GET['play']))
    {
        $fh = fopen('/dev/null', 'w');
        $ch = curl_init();
        if(!isset($_GET['location']))
        {
            $_GET['location'] = "lounge";
        }
        if($_GET['location'] == "lounge")
        {
            curl_setopt($ch, CURLOPT_URL, "http://$xbmc_lounge/xbmcCmds/xbmcHttp?command=ExecBuiltIn(PlayMedia(\"$playfile\"))");
        }
        curl_setopt($ch, CURLOPT_FILE, $fh); 
        curl_exec($ch);
        curl_close($ch);
        fclose($fh);
    }

    echo '
    <table width="900" cellpadding="0" cellspacing="0" border="0" bordercolor="black">
        <tr>
            <td>
    <table width="900" cellpadding="0" cellspacing="0" border="0" bordercolor="black">
        <tr>
    <td width="300" valign="">
        <img height="500" src="Thumbnails/Video/'.$thumbhash[0].'/'.$thumbhash.'.tbn">
    </td>
    <td width="600" valign="top">
        <table width="600" cellpadding="0" cellspacing="0" border="0">
            <tr height="40">
                <td class="title">&nbsp;'.$title; if($row[year] != "0") echo " (".$row[year].") "; echo '</td>
            </tr>
            <tr height="30">
                <td class="desc">&nbsp;'.$genre.'</td>
            </tr>
            <tr height="20">
                <td class="desc">&nbsp;Tagline: '.$tagline.'</td>
            </tr>
            <tr height="282" valign="top">
                <td><table cellpadding="5" border="0"><tr><td class="desc">'.$plot.'</td></tr></table></td>
            </tr>
            <tr height="60" valign="top">
                <td>
                    <table width="100%" cellpadding="5" cellspacing="0" border="0">
                        <tr>
                            <td colspan="2" class="desc">Classification: '.$rated.'</td>
                        </tr>
                        <tr>
                            <td class="desc" width="300">Runtime: '.$runtimeh.' hr '.$runtimem.' mins</td>
                            <td class="desc" width="300">Rating: '.substr($row[rating],0,3).'/10 (From '.$row[numvotes].' votes)</td>
                        </tr>
                        <tr>
                            <td class="desc" width="300">Director: '.$director.'</td>
                            <td class="desc" width="300">Writer: '.$writer.' </td>
                        </tr>
                        <tr>
                            <td class="desc" width="300">Links: <a class="desc" target="_blank" href="http://www.imdb.com/title/'.$row[imdbid].'">IMDB</a> | <a class="desc" href="Thumbnails/Video/Fanart/'.$thumbhash.'.tbn" onClick="popup = window.open(\'Thumbnails/Video/Fanart/'.$thumbhash.'.tbn\', \'PopupPage\', \'height=720, width=1280, scrollbars=yes, resizeable=no\'); return false" target="_blank">Fanart</a></td>
                        </tr>
    
                    </table>
                </td>
            </tr>
            <tr height="50">
                <td>';
                    //Video resolutions
                    if($vid[horizres] == "1280"){ echo "<img src=\"img/video/720.png\">" ;}
                    if($vid[horizres] == "1920"){ echo "<img src=\"img/video/1080.png\">";}
                    if($vid[horizres] < "1280"){echo "<img src=\"img/video/sd.png\">";}
                    //Audio codecs
                    if($aud[acodec] == "mp3"){echo "<img src=\"img/audio/mp3.png\">";}
                    if($aud[acodec] == "ac3"){echo "<img src=\"img/audio/ac3.png\">";}
                    if($aud[acodec] == "vorbis"){echo "<img src=\"img/audio/ogg.png\">";}
                    if($aud[acodec] == "aac"){echo "<img src=\"img/audio/aac.png\">";}
                    if($aud[acodec] == "dca" || $row[acodec] == "dts"){echo "<img src=\"img/audio/dts.png\">";}
                    //Audio channels
                    if($aud[achannels] == "1"){echo "<img src=\"img/audio/1.png\">";}
                    if($aud[achannels] == "2"){echo "<img src=\"img/audio/2.png\">";}
                    if($aud[achannels] == "6"){echo "<img src=\"img/audio/6.png\">";}
                echo'
                </td>
            </tr>
        </table>
    </td>
    ';

}

echo '
	</tr>
</table>
</td>
</tr>
</table>
<div id="non-javascript">
    <table width="900" cellspacing="0" cellpadding="0" border="0">
    <tr>
    <td class="mainlist" align="center">
    <br>
    <center>
        <a class="nav" href="index.php">BACK TO MOVIE LIST</a>
        <a class="nav" href="?action=logout">LOGOUT</a>
    </center>
    </td>
    </tr>
    </table>
</div>
</body>
</html>';
?>
