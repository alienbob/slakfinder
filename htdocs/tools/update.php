<?php

chdir('..');
include 'inc/includes.inc.php';

if (!isset($_SERVER["_"])){
  $PASS=isset($_GET['PASS'])?$_GET['PASS']:isset($_SERVER['PASS'])?$_SERVER['PASS']:null;

  if($updatepassword != $PASS){
    echo "Bad password for update!";
    die();
  }
}

/*
 * Parameters:
 *
 * DROPDB: Delete and rebuild the database except for the counters which are preserved
 * REPO=id: Only scan the specified repository
 * REDEFINE: Redefines and fixes the repository headers in the database, without redownloading everything
 * SHOWQ: Shows the queries being executed
 * DEBUG: Enable debugging mode
 * DIE: On failure do not continue execution
 *
 */

$NL="\n";
if(isset($_SERVER['HTTP_HOST'])){
//  $NL="<br>\n";
  echo '<pre>';
}

$repoinprogress=0;
function shutdown() { 
  global $db,$repoinprogress;
  if($repoinprogress){
    echo "REPOSITORY DESTRUCTION ({$repoinprogress}) in progress...";
    $rep=new repository($repoinprogress);
    $repoinprogress=0;
    $rep->drop();
    echo "REPOSITORY DESTROYED.";
  }
  echo "Closing application";
  flush();
}

register_shutdown_function('shutdown');

$db=new database();
echo "Clearing cache$NL";
$db->db->dropcache();

if(isset($_SERVER['DROPDB'])or isset($_GET['DROPDB'])){
  echo "deleting database... ";
  $out=$db->dropdb();
  if(!$out){
    echo "error!$NL$NL";
    echo "details:$NL";
    var_dump($db);
    die();
  }else{
    echo "done$NL";
  }

  echo "creating database... ";
  $out=$db->createdb();
  if(!$out){
    echo "error!$NL$NL";
    echo "details:$NL";
    var_dump($db);
    die();
  }else{
    echo "done$NL";
  }
}

if(isset($_SERVER['REPO'])){
  $defrepo=array($_SERVER['REPO'] => $defrepo[$_SERVER['REPO']]);
}
if(isset($_GET['REPO'])){
  $defrepo=array($_GET['REPO'] => $defrepo[$_GET['REPO']]);
}
foreach($defrepo as $id => $repo){
if(isset($_SERVER['CREATE'])){ $repo['info']['create']=$_SERVER['CREATE']; }
if(isset($_GET['CREATE'])){ $repo['info']['create']=$_GET['CREATE']; }
if($repo['info']['create']){
  flush();
  $info=$repo['info'];
  $create=$info['create'];
  $repo['id']=$id;
  unset ($repo['info']);
  echo "REPOSITORY: $id => {$repo['name']}... ";
  flush();
  $rep=new repository($id);
  if($create==3){
    echo "removal...";
    if(!$out=$rep->drop()){
      echo "ERROR IN DESTRUCTION!!! ";
      if(isset($_SERVER['DIE'])or isset($GET['DIE'])){die();}else {continue;}
    };
    echo "removal completed.$NL";
    continue;
  }
  if($rep->exists()){
    if(isset($_SERVER['REDEFINE'])or isset($_GET['REDEFINE'])){
      $rep->redefine($repo,(isset($_SERVER['REDEFINE'])?$_SERVER['REDEFINE']:0)+(isset($_GET['REDEFINE'])?$_GET['REDEFINE']:0));
      echo "updated$NL";
      continue;
    }
    echo "already exists... ";
    if($create==2){
      echo "forced destruction... ";
      if(!$out=$rep->drop()){
	echo "ERROR IN DESTRUCTION!!! ";
	if(isset($_SERVER['DIE'])or isset($GET['DIE'])){die();}else {continue;}
      };
    }

    if($rep->exists()){
      if($rep->needupdate()){
	echo "requires update... ";
	echo "deletion in progress... ";
	flush();
	if(!$rep->drop()){
	  echo "ERROR EMPTYING THE REPOSITORY!!! ";
	  if(isset($_SERVER['DIE'])or isset($GET['DIE'])){die();}else {continue;}
	}
      }else{
	echo "already updated!$NL";
      }
    }
  }
  flush();
  $rep=new repository($id);
  if(!$rep->exists()){
    echo "creating repository... ";
    $repoinprogress=$id;
    if(!$out=$rep->add($repo)){
      echo "ERROR adding repository!!! ";
      echo "DESTRUCTION in progress... ";
      if(!$out=$rep->drop()){
	$repoinprogress=0;
	echo "ERROR IN DESTRUCTION!!! ";
	if(isset($_SERVER['DIE'])or isset($GET['DIE'])){die();}else {continue;}
      };
      $repoinprogress=0;
      echo "DESTRUCTION done.. jump to next repository.$NL";
      if(isset($_SERVER['DIE'])or isset($GET['DIE'])){die();}else {continue;}
    }
    $repoinprogress=0;
    echo "Creation done... ";
    echo "populating in progress...$NL";
    flush();
    $repoinprogress=$id;
    if(!$err=$rep->popolate()){
      echo $NL."ERROR populating the repository!!! ";
      echo "DESTRUCTION in progress... ";
      if(!$out=$rep->drop()){
	$repoinprogress=0;
	echo "ERROR IN DESTRUCTION!!! ";
	if(isset($_SERVER['DIE'])or isset($GET['DIE'])){die();}else {continue;}
      };
      $repoinprogress=0;
      echo "DESTRUCTION done.. jump to next repository.$NL";
      if(isset($_SERVER['DIE'])or isset($GET['DIE'])){die();}else {continue;}
    }
    $repoinprogress=0;
    echo "Repository created$NL";
  }
}}

echo "{$NL}";
