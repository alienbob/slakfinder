<?php session_start(); 
echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Package Finder</title>
  </head>
  <style type="text/css">
  <!--
    a:link    {text-decoration: none; color: blue;}
    a:visited {text-decoration: none; color: blue;}
    a:hover   {text-decoration: underline; color: red;}
    input {border:1px solid #000000;}
  .repository {border:1px solid #000000;}
  .repository td { border-top:1px solid #000000; border-right:1px dotted; }
  .repository th { border-right:1px dotted; }
  .results {border:1px solid #000000;}
  .results td { border-top:1px solid #000000; border-right:1px dotted; }
  .results th { border-right:1px dotted; }
  -->
  </style>
<body>
<?php

  include 'inc/includes.inc.php';

  $maxresult=30;
  $start=0;
  $db=new database();
  if(!isset($_SESSION['last_search']))$_SESSION['last_search']="";
  if(!isset($_SESSION['searcher_visitor'])){
    $db->counter_inc('visits');
    $_SESSION['searcher_visitor']=$db->counter_get('visits');
  }
  #echo "You are visitor number ".$_SESSION['searcher_visitor']."<br />";
  $name=$desc=$file=$repo=$order=null;
  foreach($_GET as $key => $value)$$key=$value;
  if ($name or $desc or $file) {
    if(($start==0)and($_SESSION['last_search']!="name=$name&desc=$desc&file=$file")){
      $_SESSION['last_search']="name=$name&desc=$desc&file=$file";
      $db->counter_inc('searches');
    }
  }
  #echo "Searched ".$db->counter_get('searches')." packages from 6 March 2010<br /><br />";
  $hrepos="";
  if ($name or $desc or $file){
    $hrepos.="<div style='color:red' id='wait1'>Wait a moment...";
    if($file)$hrepos.=" (up to 2 minutes)";
    $hrepos.="</div>";
  }
  $hrepos.="

<form action='old.php?#results'>
  <input type='hidden' name='act' value='search'>
  <input type='hidden' name='start' value='0'>
  <input type='hidden' name='order' value=''>
  <input type='hidden' name='maxresult' value='$maxresult'>
  ";
  $hrepos.=writerepos($repo); 
  if ($name or $desc or $file){
    $hrepos.="<div style='color:red' id='wait2'>Wait a moment...";
    if($file)$hrepos.=" (up to 2 minutes)";
    $hrepos.="</div>";
  }
  $hrepos.="
  <a name='results'></a>
  <nobr>Search<sup>(*)</sup>: <input name='name' value='$name' /> 
        <input type='submit' value='go' /> - 
	Description: <input name='desc' value='$desc' /> - 
        Filename: <input name='file' value='$file' /> 
	<br /><sup><i>(*) NEW!!!! Enter one or more words <u>space separated</u>. Do not enter package version (it will be ignored)</i></sup>
  </form>
  ";
  $ord="";

  echo $hrepos;
  if ($name or $desc or $file){
    echo "<pre>";
    $sname=$name; $sdesc=$desc; $sfile=$file;
    $name=urlencode($name);
    $desc=urlencode($desc);
    $file=urlencode($file);
    $sname=str_replace(' ','%',$sname);
    $sdesc=str_replace(' ','%',$sdesc);
    $sfile=str_replace(' ','%',$sfile);
    $sname=preg_replace('/[%-][0-9].*/','',$sname);
    if($sfile){ $sfile="%$sfile%";}
    if(!$file){ ///////////////////////////////////// PACKAGES.TXT RESULTS ////////////////////////////////////////////////
      switch($order){
	case "ranku": $ord='rank desc';break;
	case "rankd": $ord='rank';break;
	case "pkgu": $ord='P.name';break;
	case "pkgd": $ord='P.name desc';break;
	case "veru": $ord='P.version desc';break;
	case "verd": $ord='P.version';break;
	case "archu": $ord='P.arch';break;
	case "archd": $ord='P.arch desc';break;
	case "distu": $ord='R.version desc';break;
	case "distd": $ord='R.version';break;
	case "fileu": $ord='F.filename';break;
	case "filed": $ord='F.filename desc';break;
	case "pathu": $ord='F.fullpath';break;
	case "pathd": $ord='F.filename desc';break;
	case "repou": $ord='R.description';break;
	case "repod": $ord='R.description desc';break;
	case "locu": $ord='P.location';break;
	case "locd": $ord='P.location desc';break;
      }
      $pkg=new package();
      $nres=$pkg->find($sname,$sdesc,$repo,$ord);
      
      if(isset($_GET['debug']))var_dump($pkg,$nres);
      $to=$start+$maxresult; if($to > $nres)$to=$nres;
      echo "Time: ".(round($pkg->db->msec/1000,3))." msec<br />";
      echo "Results ".($start+1)."-$to of $nres:                    ";
	if($start > 0){
	  echo "<a href='index.php?start=0&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;&lt;</a>  ";
	  $from=$start-$maxresult;if($from<0)$from=0;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;</a>  ";
	}
	if($to < $nres){
	  $pg=round($nres/$maxresult-0.5,0);
	  $from=$start+$maxresult;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;</a>  ";
	  $from=$maxresult*$pg;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;&gt;</a>  ";
	}
	echo tables(array(
	  "<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='ranku')?'rankd':'ranku')."#results'>rank</a>",
	  "<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='pkgu')?'pkgd':'pkgu')."#results'>package</a>",
	  "<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='veru')?'verd':'veru')."#results'>version</a>",
	  "<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='archu')?'archd':'archu')."#results'>arch</a>",
	  "<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='distu')?'distd':'distu')."#results'>distro</a>",
	  "<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='repou')?'repod':'repou')."#results'>repository</a>",
	  "<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='locu')?'locd':'locu')."#results'>location</a>",
	  "&nbsp;"),1,"class='results' width='100%'");
      if($nres>$start){
	$repos=null;
	$pkg->find(null,$start);
	for ( $i=$start ; $i < $to; $i++ ){
	  $pkg->find();
	  echo tables(array(
	    $pkg->rank,
	    "<a title='".(str_replace(array("'","\n"),array(" "," "),$pkg->description))."' href='show.php?pkg={$pkg->id}'>{$pkg->name}</a>",
	    $pkg->version,
	    $pkg->arch,
	    $pkg->repover,
	    "<a title='{$pkg->url}' href='showrepo.php?repo={$pkg->repoid}'>{$pkg->repobrief}</a>",
	    "<a href='{$pkg->url}{$pkg->location}/'>{$pkg->location}/</a>",
	    "<a href='{$pkg->url}{$pkg->location}/{$pkg->filename}'>download</a>"));
	}
      }
      echo tables();
      if($start > 0){
	echo "<a href='index.php?start=0&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;&lt;</a>  ";
	$from=$start-$maxresult;if($from<0)$from=0;
	echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;</a>  ";
      }
      if($to < $nres){
      $pg=round($nres/$maxresult-0.5,0);
      $from=$start+$maxresult;
      echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;</a>  ";
	$from=$maxresult*$pg;
	echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;&gt;</a>  ";
      }
      echo "<br /><br /><br />";
    }else{ /////////////////////////////////////////////////// MANIFEST.bz2 RESULTS ///////////////////////////////////////////////////////////
  //    $maxresult=80;
      switch($order){
	case "ranku": $ord='rank desc';break;
	case "rankd": $ord='rank';break;
	case "pkgu": $ord='pkgname';break;
	case "pkgd": $ord='pkgname desc';break;
	case "veru": $ord='version desc';break;
	case "verd": $ord='version';break;
	case "archu": $ord='arch';break;
	case "archd": $ord='arch desc';break;
	case "distu": $ord='distro desc';break;
	case "distd": $ord='distro';break;
	case "fileu": $ord='filename';break;
	case "filed": $ord='filename desc';break;
	case "pathu": $ord='fullpath';break;
	case "pathd": $ord='fullpath desc';break;
	case "repou": $ord='repobrief';break;
	case "repod": $ord='repobrief desc';break;
	case "locu": $ord='pkgloc';break;
	case "locd": $ord='pkgloc desc';break;
      }
      $fl=new filelist();
      $nres=$fl->find($sfile,$sname,$sdesc,$repo,$ord);
      if(isset($_GET['debug']))var_dump($fl,$nres);
      $to=$start+$maxresult; if($to > $nres)$to=$nres;
      echo "Time: ".(round($fl->db->msec/1000,3))." msec<br />";

      echo "Results ".($start+1)."-$to of $nres:                    ";

	if($start > 0){
	  echo "<a href='index.php?start=0&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;&lt;</a>  ";
	  $from=$start-$maxresult;if($from<0)$from=0;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;</a>  ";
	}
	if($to < $nres){
	  $pg=round($nres/$maxresult-0.5,0);
	  $from=$start+$maxresult;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;</a>  ";
	  $from=$maxresult*$pg;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;&gt;</a>  ";
	}




//      echo tables(array('rank','package','version','arch','distro','file','path','repository','location','&nbsp'),1,"class='results' width='100%'");
      echo tables(array(
	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='ranku')?'rankd':'ranku')."#results'>rank</a>",
	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='pkgu')?'pkgd':'pkgu')."#results'>package</a>",
	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='veru')?'verd':'veru')."#results'>version</a>",
	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='archu')?'archd':'archu')."#results'>arch</a>",
	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='distu')?'distd':'distu')."#results'>distro</a>",

	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='fileu')?'filed':'fileu')."#results'>file</a>",
	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='pathu')?'pathd':'pathu')."#results'>path</a>",

	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='repou')?'repod':'repou')."#results'>repository</a>",
	"<a href='index.php?start=$start&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=".(($order=='locu')?'locd':'locu')."#results'>location</a>",
	"&nbsp;"),1,"class='results' width='100%'");
      if($nres>$start){
	$fl->find(null,$start);
	for ( $i=$start ; $i < $to ; $i++ ){
	  $fl->find();
	  echo tables(array(
	    $fl->rank,
#	    "<a href='show.php?pkg={$fl->pkgid}'>{$fl->pkgname}</a>",
	    "<a title='".(str_replace(array("'","\n"),array(" "," "),$fl->pkgdesc))."' href='show.php?pkg={$fl->pkgid}'>{$fl->pkgname}</a>",

	    $fl->version,
	    $fl->arch,
	    $fl->distro,
	    str_ireplace("$file","<b style='color:red;'>$file</b>",$fl->filename),
	    $fl->fullpath,
	    "<a title='{$fl->repodesc}' href='{$fl->url}'>{$fl->repobrief}</a>",
	    "<a href='{$fl->url}{$fl->pkgloc}/'>{$fl->pkgloc}/</a>",
	    "<a href='{$fl->url}{$fl->pkgloc}/{$fl->pkgfile}'>download</a>"));
	}
      }
      echo tables();
	if($start > 0){
	  echo "<a href='index.php?start=0&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;&lt;</a>  ";
	  $from=$start-$maxresult;if($from<0)$from=0;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&lt;</a>  ";
	}
	if($to < $nres){
	  $pg=round($nres/$maxresult-0.5,0);
	  $from=$start+$maxresult;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;</a>  ";
	  $from=$maxresult*$pg;
	  echo "<a href='index.php?start=$from&maxresult=$maxresult&repo=$repo&name=$name&desc=$desc&file=$file&order=$order#results'>&gt;&gt;</a>  ";
	}





      echo "</pre>";
      echo "<br /><br /><br />";
    }
    ?>
    <script>
    var wait=document.getElementById('wait1');
    wait.style.color="white";
    var wait=document.getElementById('wait2');
    wait.style.color="white";
    </script>
    <?php
  }
?>
<p>To report a bug, mail to <a href='mailto:zerouno@slacky.it'>zerouno@slacky.it</a>. Thanks.</p>
</body></html>
