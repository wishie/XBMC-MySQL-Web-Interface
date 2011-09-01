<?php

require('access.php');
require('config.php');

$cellbg  = "0";
$limit	 = "50";

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

//count movies
$result = $db->query("SELECT COUNT(*) FROM movie");
$totalmovies = $result->fetch_row();

if (!$_REQUEST['get'])
{
    echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Language" content="en" />
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <link rel="stylesheet" type="text/css" href="css/styles.css" />
    <link rel="stylesheet" href="css/colorbox.css" type="text/css" />
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery.colorbox-min.js"></script>
    <script type="text/javascript">
    $(document).ready(function() {
            last = 0;
            $("a").colorbox();
            $("#non-javascript").hide();
            $(window).scroll(function(){
                if  ($(window).scrollTop() == $(document).height() - $(window).height()){
                    if (last <= "'.$totalmovies[0].'")
                    {
                        $(\'#mainlist\').append(\'<div id="loading"><img src="img/loading.gif" /></div>\');
                       last = last+50;
                       $.get("index.php?get=" + last, function(data) {
                        $(\'#loading\').remove();
                        $(\'#mainlist\').append(data);
                       });
                    }
                }
            }); 
        });
    </script>
    <title>MediaList</title>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr class="mainlist">
            <td colspan="3">
                <a class="nav" href="?action=logout">LOGOUT</a>
                <h1>Movie list '.$titel_plus.'</h1>
            </td>
        </tr>
        <tr class="mainlist">
            <td colspan="3">
                Search: <form method="post" action="?search">
                    <input type="text" name="searchterm">
                    <input type="radio" name="st" value="titlesearch" checked>Title Search</option>
                    <input type="radio" name="st" value="genresearch">Genre Search</option>
                    <input type="submit" name="submit" value="Go">
                </form>
            </td>
        </tr>
    </table>
    <div id="mainlist">
        ';
}

//set next movies to load
if(!isset($_REQUEST["startnum"])){ $_REQUEST["startnum"] = "0"; }
if($_REQUEST["startnum"] > $totalmovies[0])
{
	$startnum = "0";
}
else
{
	$startnum = $_REQUEST["startnum"];
}

if ($_REQUEST['get'])
{
    $startnum = $_REQUEST['get'];
}

//get movies
if(!isset($_REQUEST['searchterm']))
{
	$query  = "SELECT 
    strPath AS path, 
    strFileName AS filename, 
    idMovie AS movieid, 
    c00 AS title, 
    c01 AS plot,
    c03 AS tagline,
    c07 AS year,
    c11 AS runtime,
    c14 AS genre
    FROM movieview
    ORDER BY c00 
    LIMIT $startnum, $limit";
}
else
{
    //do search
    if($_REQUEST['st'] == "titlesearch"){
        $titel_plus = "(Title Search: ".$_REQUEST['searchterm']." )"; 
    }
    if($_REQUEST['st'] == "genresearch"){
        $titel_plus = "(Genre Search: ".$_REQUEST['searchterm']." )"; 
    }
	$query	= "SELECT 
    strPath AS path, 
    strFileName AS filename, 
    idMovie AS movieid, 
    c00 AS title, 
    c01 AS plot,
    c03 AS tagline,
    c07 AS year,
    c11 AS runtime,
    c14 AS genre
    FROM movieview";
    $searchterm = $_REQUEST['searchterm'];
    if($_REQUEST['st'] == "titlesearch")
    {
        $query .= " WHERE c00 LIKE '%$searchterm%'";
    }
    if($_REQUEST['st'] == "genresearch")
    {
        $query .= " WHERE c14 LIKE '%$searchterm%'";
    }
	$query .= " ORDER BY c00";
}

$result = $db->query($query);

while($row = $result->fetch_assoc())
{   
    $title   	= htmlentities($row[title]);
    $plot 	= htmlentities($row[plot]);  
    $tagline 	= htmlentities($row[tagline]);
    $genre	= htmlentities($row[genre]);
    
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

    if($cellbg == 1)
    {
        $cellbg = 0;
        echo '
        <div style="min-width: 275px; height: 115px; float:left;">';
    }
    else
    {
        $cellbg = 1;
	    echo '
	<div style="min-width: 275px; height: 115px; background-color: #EEEEEE; float:left;">';
    }

	echo '
            <div style="float:left;">
                <a href="movieinfo.php?movieid='.$row[movieid].'"><img border="0" style="max-width: 75px; max-height: 112px;" src="Thumbnails/Video/'.$thumbhash[0].'/'.$thumbhash.'.tbn"></a>
            </div>
            <div style="padding-left: 85px;">
                <table cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td width="200">
                            <a class="nav" href="movieinfo.php?movieid='.$row[movieid].'">'.$title; if($row[year] != "0") { echo " (".$row[year].") "; }
                        echo '<td width="75" class="mainlist">'.$runtimeh.' hr '.$runtimem.' mins</td>
                    </tr>
                    <tr height="70" valign="top">
                        <td colspan="2" class="mainlist">'.$genre.'<br><br>
                        ';
                        if(strlen($tagline) > "150")
                        {
                            echo substr($tagline, 0, 150)."...";
                        }
                        else
                        {
                            echo $tagline;
                        }
                        echo '
                        </td>
                    </tr>
                    <tr height="10">
                        <td colspan="2" class="mainlist" align="right"><a class="nav" href="movieinfo.php?movieid='.$row[movieid].'">More info...</a></td>
                    </tr>
                </table>
            </div>
        </div>
        ';
}

if (!$_REQUEST['get'])
{
    echo '
    </div>
    <div id="non-javascript" style="clear:both">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="center" class="mainlist">
                    <form method="post" action="index.php">
                        PAGE:
                        <select name="startnum">';
                            
                            if(!isset($page))
                            {
                                $page = "1";
                            }
                            $pages = ceil($totalmovies[0] / $limit + 1);
                            while($page < $pages)
                            {
                                $nextpage = $page * $limit - $limit;
                                if($startnum == $nextpage){
                                    echo "<option selected value=\"$nextpage\">$page</option>";
                                }
                                else
                                {
                                    echo "<option value=\"$nextpage\">$page</option>";
                                };
                                $page++;
                            };
                            $next = $startnum + $limit;
                            $startnum++;
                        echo '
                        </select>
                        <input type="submit" value="Go" name="submit">
                    </form>
                    Displaying movies '.$startnum.' to '.$next.' of '.$totalmovies[0].'
                </td>
            </tr>';
                    
            if(isset($_REQUEST['search'])){
                echo '
                <tr>
                    <td><center><br><a class="nav" href="index.php">BACK TO MAIN PAGE</a></center></td>
                </tr>';
            }
            echo '
            <tr>
            </tr>
        </table>
    </div>
</body>
</html>';
}
mysqli_close($db);
?>
