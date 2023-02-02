<?php

include 'inc/includes.inc.php';

$stats=new stats();

if(isset($_GET['gdaily'])){

 # gdaily -> fa il grafico
 # time -> numero massimo di giorni (default = 365)
 # y -> altezza del grafico (default = 1px per ricerca)
 # x -> largezza del grafico (default = 6px per giorno)
 # scale -> disegna linee orizzontali ogni tot (default = max/5)
 # mid -> media calcolata sugli ultimi tot giorni (default = no medie, 0 = time

 define('DAY',86400);
 $time=(isset($_GET['time']))?$_GET['time']:365;
 $days=$stats->numdays();
 $time=($time<$days)?$time:$days;
 $date=array_reverse($stats->countbyday($time));

 $maxmid=0;$gmid=0;
 if(isset($_GET['mid'])){
	if(!$_GET['mid']){
$gmid=1;
	}else{
$maxmid=$_GET['mid'];
	}
 }
 $max=max($date);
 $scale=isset($_GET['scale'])?$_GET['scale']:round($max/5);
 if(isset($_GET['y'])){
	$multi=$_GET['y']/$max;
 }else{
	$multi=1;
 }
 if(isset($_GET['x'])){
	$large=$_GET['x']/count($date)-1;
	$space=($large<3)?0:1;
 }else{
	$large=5;
	$space=1;
 }

 $im=ImageCreate($large*count($date),$max*$multi);
 $bg=ImageColorAllocate($im,240,255,250);
 $red=ImageColorAllocate($im, 255, 0, 0);
 $blue=ImageColorAllocate($im, 0, 0, 255);
 $green=ImageColorAllocate($im, 0, 255, 0);
 $midcol=ImageColorAllocate($im, 255, 128, 200);
 $black=ImageColorAllocate($im, 0, 0, 0);

 $col=array();
 $col[0]=ImageColorAllocate($im, 0, 0, 0);
 $col[1]=ImageColorAllocate($im, 0, 0, 0);
 $c=0;

 $i=0;

 $n=0;
 $last=array(); 
 $sum=0;
 foreach($date as $k => $val){
	$key=floor(time()/DAY)-($time-$k);
	$sum+=$val;
	array_push($last,$val);
	$mid=array_sum($last)/count($last);
	if(!$i) if(date('j',($key)*DAY)<20) ImageString($im,2,$i*$large+2,1,date("M",($key)*DAY),$red);
	$bar=$col[$c];$c=1-$c;
	ImageFilledRectangle($im,$i*$large+$space,$max*$multi-$val*$multi,($i+1)*$large-1,$max*$multi,$bar);
	if($maxmid){
if(count($last)>$maxmid)array_shift($last);
ImageFilledRectangle($im, $i*$large+$space , $max*$multi-$mid*$multi, ($i+1)*$large  , $max*$multi-$mid*$multi, $midcol);
	}
	if(date('j',($key)*DAY)==1) {
ImageRectangle($im,$i*$large,0,$i*$large,$max*$multi,$red);
ImageString($im,2,$i*$large+2,1,date("M",($key)*DAY),$red);
	}
	if($space)if(!date('w',($key)*DAY)==1) ImageRectangle($im,$i*$large,$max*$multi/4,$i*$large,$max*$multi,$green);
	$i++;
 }
 for($i=$scale;$i<$max;$i+=$scale){
	ImageRectangle($im,0,$max*$multi-$i*$multi,$large*count($date)+1,$max*$multi-$i*$multi,$blue);
	ImageString($im,2,1,$max*$multi-$i*$multi,$i,$blue);
 }
 if($maxmid){
	ImageString($im,2,25,15,"Avg. last $maxmid days",$midcol);
 }

 if($gmid) {
	ImageFilledRectangle($im, 0, $max*$multi-($sum/count($date))*$multi, $large*count($date)+1 , $max*$multi-($sum/count($date))*$multi, $midcol);
	ImageString($im,2,30,15,"Avg. ".count($date)."days: ".round($sum/count($date)),$midcol);
 }


 header("Content-type: image/png");
 ImagePNG($im);
 ImageDestroy($im);
 exit;
}
### ELSE
#

echo "<pre>\n";
 echo "<a href='javascript:history.go(-1)'>Back</a> | <a href='index.php'>Slakfinder Home</a><br>\n";
 echo "<table border=1 cellspacing=0>\n";
	echo "  <tr>\n";
echo "    <td colspan=2>\n";
  $today=$stats->countbyday(1); $today=$today[0];
  $all=$stats->countall();
  $days=$stats->numdays();
  echo "      Searches from begin: $all | Today: $today | Top: ".($stats->maxbyday())." | Average: ".(round($all/$days)). " | $days days<br>\n";
  echo "      <img src='stats.php?gdaily&y=200&mid=30&time=120'>\n";
echo "    </td>\n";
	echo "  </tr>\n";
	echo "  <tr>\n";
echo "    <th>Last 100</th>\n";
echo "    <th>Top 100</th>\n";
	echo "  </tr>\n";
	echo "  <tr>\n";
echo "    <td>\n";
  foreach($stats->lastminsearch(100) as $val) echo "      ".$val['min']."' ".$val['sname']."<br>\n";
echo "    </td>\n";
echo "    <td>\n";
  foreach($stats->groupbypkg(100) as $val)    echo "      ".$val['ns']." ".$val['sname']."<br>\n";
		echo "    </td>\n";
	echo "  </tr>\n";
 echo "</table>\n";
echo "</pre>";
