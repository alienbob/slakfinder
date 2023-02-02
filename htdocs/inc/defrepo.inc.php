<?php

$classes = array();
$npackages = 0;

function redefrepo($reposelected = 0) {
  global $defrepo, $classes, $npackages;
  $defrepo = array();
  $classes = array();
  $repo = new repository();
  $nrepos = $repo->find();

  while ($repo->fetch()) {
    $id = $repo->id;
    $defrepo[$id] = (array)$repo;
    unset($defrepo[$id]['db']);
    $defrepo[$id]['selected'] = ($id==$reposelected);
    $classes[$repo->class]['class'] = $repo->class;
    $classes[$repo->class]['arch'] = $repo->arch;
    $classes[$repo->class]['version'] = $repo->version;
    $classes[$repo->class]['selected'] = ($repo->class==$reposelected);
    $classes[$repo->class]['repo'][] = $repo->id;
    $npackages += $repo->npkgs;
  }
  
  $nrepos = $repo->find();
  $stats = new stats();
  $names = $stats->lastsearch(15);

  echo "<a href='javascript:history.go(-1)'> Back</a> | <a href='index.php'>Slakfinder Home</a><br><br>";
  echo "<table border='0' width='100%'>" . 
       "<tr>" . 
       "<td>" . 
       "You are the {$_SESSION['searcher_visitor']}st visitor<br />" . 
       "Searched ".$GLOBALS['db']->counter_get('searches')." packages from 24 Apr 2012<br /><br />" . 
       "<code>{$nrepos} repositories ({$npackages} packages)</code><br/><br/>\n" . 
       "</td>" . 
       "<td>" . 
       "<table border=1 cellspacing=0>" . 
       "<tr><td colspan='3' align='center'><code><b><a href='stats.php'>Recents</a></b></code></td></tr>" . 
       "<tr><td><code>",$names[0]."<br />".$names[1]."<br />".$names[2]."<br />".$names[3]."<br />".$names[4]."</code></td>" . 
       "<td><code>",$names[5]."<br />".$names[6]."<br />".$names[7]."<br />".$names[8]."<br />".$names[9]."</code></td>" . 
       "<td><code>",$names[10]."<br />".$names[11]."<br />".$names[12]."<br />".$names[13]."<br />".$names[14]."</code></td></tr>" . 
       "</table>" . 
       "</td>" . 
       "<td>" . 
       "<table border='1' cellspacing='0' cellpadding='0'>" . 
       "<tr><td><a href='stats.php'><img border='0' src='stats.php?gdaily&y=95&time=60&mid=0'></a></td></tr>" . 
       "</table>" . 
       "</tr></table>";
	return;
}

function writerepos($reposelected) {
  global $classes;
  global $defrepo;
  redefrepo($reposelected);
  $cells = array();

  foreach($classes as $class) {
    $cell = array();
    $cell['arch'] = "<code>{$class['arch']}</code>";
    $cell['version'] = "<code>{$class['version']}</code>";
    $cell['use'] = "<input type='radio' name='repo' value={$class['class']} ".($class['selected']?"checked='checked'":'').'>';
    $cell['content'] = '';
    foreach($class['repo'] as $repoid) {
      $repo=$defrepo[$repoid];
      if($cell['content'])
		  $cell['content'] .= ' - ';
      $cell['content'] .= "<code><nobr>
  <input type='radio' name='repo' value={$repo['id']} ".($repo['selected']?"checked='checked'":"").">
  <a title='".(str_replace(array("'","\n"),array(" "," "),$repo['description']))."' href='showrepo.php?repo={$repo['id']}'>{$repo['brief']}</a>".
  "<sup>({$repo['npkgs']}".($repo['manifest']?"F":"").($repo['deps']?"D":"").")</sup>".
      "</nobr></code>";
    }
    $cells[$class['class']]=$cell;
  }

  $out=tables(array("use","arch","distro","Repository"),1," class='repository'  ");
  $out .= tables(array("&nbsp;","all","all",  
    		     "<input type='radio' name='repo' value=0".((!$reposelected)?" checked='checked'":"").">All repositories"));
  foreach($cells as $cell){
    $out .= tables(array($cell['use'],$cell['arch'],$cell['version'],$cell['content']));
  }
  $out .= tables();
  $out .= "<code>(F) File search support enabled ; (D) View dependencies enabled</code><br><br>";
  return $out;
}

function writereposcompact($reposelected, $righttxt = '') {
  global $classes;
  global $defrepo;
  redefrepo($reposelected);
  $cells = array();
  $tab = array();
  $versions = array();
  $show = false;
  $jsarrclass = "'".implode("','", array_keys($classes))."'";
 
  foreach($classes as $class){
    $hide=true;
    $versions[$class['version']]="";
    $archs[$class['arch']]="";
    $cell=array();
    $cell['arch']="<code>{$class['arch']}</code>";
    $cell['version']="<code>{$class['version']}</code>";
    //$cell['use']="<input type='radio' name='repo' value={$class['class']} ".($class['selected']?"checked='checked'":"").">";
    $cell['content']="";
    foreach($class['repo'] as $repoid){
      $repo=$defrepo[$repoid];
      if($cell['content'])$cell['content'] .= " - ";
      $cell['content'] .= "<code><nobr>
	<input type='radio' name='repo' value={$repo['id']} ".($repo['selected']?"checked='checked'":"").">
	<a title='".(str_replace(array("'","\n"),array(" "," "),$repo['description']))."' href='showrepo.php?repo={$repo['id']}'>{$repo['brief']}</a>".
	"<sup>({$repo['npkgs']}".($repo['manifest']?"F":"").($repo['deps']?"D":"").")</sup>".
      "</nobr></code>";
      if($repo['selected'])$hide=false;
    }
    if($class['selected'])$hide=false;
    $cell['tr']=" id='{$class['class']}' ".(($hide)?"style='display:none'":"");
    $cells[$class['class']]=$cell;
    $tab[$class['version']][$class['arch']]=$class['class'];
    if(!$hide)$show=true;
  }
  $versions=array_keys($versions);
  rsort($versions);
  $out="";
    
  $repo1=tables(array_merge(array("<input type='radio' name='repo' value=0".((!$reposelected)?" checked='checked'":"").">All"),array_keys($archs)),1," class='repository' width='100%'");
  foreach($versions as $version){
    $tmparr=array_merge(array("<b>".$version."</b>"),$archs);
    foreach(array_keys($archs) as $arch){
      if(isset($tab[$version][$arch])){
	$class=$classes[$tab[$version][$arch]];
	$tmparr[$arch]="<input type='radio' name='repo' onclick='showclass(\"{$class['class']}\")' value={$class['class']} ".($class['selected']?"checked='checked'":"").">";
      }

    }
    $repo1 .= tables($tmparr);
  }
  $repo1 .= tables();
  $link="<a href='javascript:void(0)' onclick='javascript:showclass(\"0\");' id='showlink'>show all repositories</a>";
  $repo2="<div id='reposlist' ".((!$show)?"style='display:none'":""). ">";
  $repo2 .= tables(array("arch","distro","Repository"),1," class='repository'  ");
  foreach($cells as $cell){
    $repo2 .= tables(array($cell['arch'],$cell['version'],$cell['content']),2,"",$cell['tr']);
  }
  $repo2 .= tables();
  $repo2 .= "<code>(F) File search support enabled ; (D) View dependencies enabled</code><br><br>";
  $repo2 .= "</div>\n";
?>
  <script>
  function showclass(cls){
    document.getElementById('reposlist').style.display='';
    var c;
    var classes=new Array(<?php echo $jsarrclass; ?>);
    if(cls=='0'){
      document.getElementById("showlink").style.display="none";
      for (c in classes){ document.getElementById(classes[c]).style.display=''; }
    }else{
      document.getElementById("showlink").style.display="";
      for (c in classes){ document.getElementById(classes[c]).style.display='none'; }
      document.getElementById(cls).style.display='';
    }
  }
  </script>
<?php
  $out .= "<table width='100%'><tr><td colspan='2'>{$repo2}</td></tr><tr><td>{$repo1}</td><td valign='top' width='100%'>{$link}<br/>{$righttxt}</td></tr></table>";
  return $out;
}
