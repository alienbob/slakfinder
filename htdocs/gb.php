<?php session_start(); 
/*
<!--<html><body><h1>Guestbook disabled!!</h1>
<pre><a href='javascript:history.go(-1)'>Back</a> | <a href='index.php'>Slakfinder Home</a><br></pre>

</body></html>

< ?php die() ?>

-->
 */
include 'inc/includes.inc.php';
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
  .gb {border:none; border-bottom:1px dotted #000000; }
  .gb td { border-top:1px dotted #000000; padding:10px; }
/*  .gb th { border-right:none; }*/
  -->
  </style>
<body>
<pre><a href='javascript:history.go(-1)'>Back</a> | <a href='index.php'>Slakfinder Home</a><br></pre>

<h2>Guest Book</h2>
<pre><font color=green>
Thanks for using SlakFinder. I hope it is useful to you.
Now, if you want, you can write a comment. 
Just for feedback, suggestions, bug reports, repository recommendations, greetings to the world, ..., or just a signature.
</font>
<form action="gb.php" method="post">Nick:
<?php
  if(isset($_POST['nick']))$_SESSION['slakhomelinuxguestbooknick']=$_POST['nick'];
  echo "<input name=nick maxlength=15 ";
  if(isset($_SESSION['slakhomelinuxguestbooknick']))echo "value='{$_SESSION['slakhomelinuxguestbooknick']}'";
  echo ">";
?><br>message:
<textarea name=message cols=70 rows=3 maxlength="256"></textarea>
<input type=submit><br></form>
<a href='gb.php'>Reload</a> <?php
  if(isset($_POST['message'])){
    if(isset($_SESSION['slakhomelinuxguestbookmsg'])){
      if($_SESSION['slakhomelinuxguestbookmsg']==$_POST['message']){
	$gb=new guestbook();
      }else{
	$_SESSION['slakhomelinuxguestbookmsg']=$_POST['message'];
	$gb=new guestbook($_POST['message'],$_SESSION['slakhomelinuxguestbooknick']);
      }
    }else{
      $_SESSION['slakhomelinuxguestbookmsg']=$_POST['message'];
      $gb=new guestbook($_POST['message'],$_SESSION['slakhomelinuxguestbooknick']);
    }
  }else{
    $gb=new guestbook();
  }

  echo "<br><br>";
  echo tables(array("","",""),1," class='gb' ");
  //echo tables(array("Date","Nick","Message"),1," class='gb' ");

  while($message=$gb->fetch()){
    echo tables(array("<sup>{$message['date']}</sup>","<font color='red'>".$message['nick']."</font>",$message['message']));
  }

  echo tables();
  echo "</pre>";

?>
</body>
</html>
