<html><head>
  <title>Repository viewer</title>
  <style type="text/css">
  <!--
    a:link    {text-decoration: none; color: blue;}
    a:visited {text-decoration: none; color: blue;}
    a:hover   {text-decoration: underline; color: red;}                                                                                                                                                           
    input {border:1px solid #000000;}                                                                                                                                                                             
  .tab {border:1px solid #000000;}                                                                                                                                                                         
  .tab td { border-top:1px solid #000000; border-right:1px dotted; }                                                                                                                                       
  .tab th { border-right:1px dotted; }                                                                                                                                                                     
  -->                                                                                                                                                                                                             
  </style>    
</head><body>
<?php
  include 'inc/includes.inc.php';

  $db=new database();
  echo "<pre>";
  $repo=0;
  foreach($_GET as $key => $value) $$key=htmlspecialchars(strip_tags($value));
  $id=$repo;
  unset($repo);

  echo "<a href='javascript:history.go(-1)'> Back</a> | <a href='index.php'>Slakfinder Home</a><br><br>";

  if($id){
    $repo=new repository($id);
    if($repo->id){
      echo "Repository Info:";
      echo tables(array(),1,"class='tab'");
      echo tables(array("ID",$repo->id));
      echo tables(array("URL","<a href='{$repo->url}' target='_blank'>{$repo->url}</a>"));
      echo tables(array("Rank",$repo->rank));
      echo tables(array("File list",(($repo->manifest)?"<a href='{$repo->url}{$repo->manifest}' target='_blank'>{$repo->manifest}</a>":"unsupported")));
      echo tables(array("Packages","<a href='{$repo->url}{$repo->packages}' target='_blank'>{$repo->packages}</a>"));
      echo tables(array("Slackware Version",$repo->version));
      echo tables(array("Arch",$repo->arch));
      echo tables(array("Class",$repo->class));
      echo tables(array("Last update",$repo->mtime));
      echo tables(array("Name",$repo->name));
      echo tables(array("Nr. of packages",$repo->npkgs));
      echo tables(array("Nr. of files",$repo->nfiles));
      echo tables(array("Dependencies",(($repo->deps)?"supported":"unsupported")));
      echo tables(array("Description",$repo->description."<br>"));
      echo tables(array("Brief Descr.",$repo->brief));
      echo tables();
    }else{
      $id=0;
    }
  }

  if(!$id){
      echo tables(array('id','brief','arch','version','description'),1,"class='tab'");
      $repo=new repository();
      $repo->find();
      while($r=$repo->fetch()){
	echo tables(array(
	  $repo->id,
	  "<a href='showrepo.php?repo={$repo->id}' target='_blank'>{$repo->brief}</a>",
	  $repo->arch,
	  $repo->version,
	  $repo->description."<br>"
	  ));
      }
      echo tables();
  }
  echo "</pre>";

  echo "<hr>";
  echo "<p>The <a href='http://slakfinder.org/'>slakfinder</a> is created by <a href='mailto:zerouno@slacky.it'>zerouno@slacky.it</a>.<br>";
  echo "To report a bug on this modified instance at <i>slackware.nl/slakfinder</i>, mail to <a href='mailto:alien@slackware.com'>alien@slackware.com</a>. Thanks.</p>";

  echo "</body></html>";
