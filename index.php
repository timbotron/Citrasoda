<?php
include 'config.php';

//Before anything, verify if logged in
if("login"==$_GET['action'])
{
	$uri = "index.php?auth=".md5($_POST['password']."grapefruit");
	header("Location: $uri");
}

if(md5($password."grapefruit")!=$_GET['auth'])
{
echo "<html><head><title>Citrasoda</title><meta name=\"viewport\" content=\"width=device-width\"><link rel=\"stylesheet\" href=\"style.css\" type=\"text/css\" media=\"screen\" /><link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\" /></head><body><div id=\"content\">\n";
echo "<div class=\"center\"><img src=\"citrasoda.big.png\" height=\"80\" width=\"80\" /><h1>CITRASODA</h1><form name=\"loginform\" action=\"index.php?action=login\" method=\"post\">\n";
echo "<label for=\"entrytext\">Password:</label>\n";
echo "<input type=\"password\" name=\"password\"/><br />\n";
echo "<input type=\"submit\" value=\"Log In\"/></form></div></div></body></html>";
exit;
}
//TODO unset extras if user doesn't want them, toggle
if ("no"==$_GET['extra'])
{ 
	unset($extra);
	$uribonus="&extra=no";
}
?>

<html>
<head>
<title>Citrasoda</title>
<meta name="viewport" content="width=device-width"/> 
<link rel="stylesheet" href="style.css" type="text/css" media="screen" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<body>
<div id="content">
<?php
//open xml
$entxml = simplexml_load_file($entryxml) or die("Failed opening $entryxml: error was '$php_errormsg'");
$tagxml = simplexml_load_file($tagxmlfile) or die("Failed opening $tagxmlfile: error was '$php_errormsg'");
$viewingtags[0]=-1;
$tagtotals=array();


//FUNCTIONS
function process_entry($entry,$uribonus)
{
	preg_match_all('/(^|\s)#(\w+)/',$entry,$tags);
	
	foreach($tags[2] as $tag)
	{
		$link="<a href=\"index.php?auth=".$_GET['auth']."&action=tag&tag=$tag".$uribonus."\">#$tag</a>";
		$entry = str_replace("#".$tag,$link,$entry);		
	}
	return $entry;
}

function tagit($passedtagxml,$entry,$entryid)
{
	preg_match_all('/(^|\s)#(\w+)/',$entry,$tags);
	foreach($tags[2] as $tag)
	{
		if($passedtagxml->$tag)
		{
			//echo "\ntag $tag already exists!\n";
			$passedtagxml->$tag->addChild('index',$entryid);
		}
		else
		{
			//echo "\ntag $tag is new!\n";
			$passedtagxml->addChild($tag);
			$passedtagxml->$tag->addChild('index',$entryid);				
		}
	}
}
//END FUNCTIONS
//PROCESSING

//adding an entry to xml
if ("add" == $_GET['action']) 
{
	if(empty($_POST['entrytext']))
	{
		echo "<div class=\"error\">No entry found, did you put anything in the entry box?</div>";
	}
	
	else
	{
		$newentry = $entxml->addChild('entry');
		$newentry->addChild('text',$_POST['entrytext']);
		$newentry->addChild('date',date($dateformat));
		if(isset($extra))
		{
			foreach($extra as $element)
			{
				if(!empty($_POST[$element['name']])) //its filled in, so record
				{
					$newentry->addChild($element['name'],$_POST[$element['name']]);
				}
			}
		}
		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($entxml->asXML());
		$dom->save($entryxml);
	
		//begin processing for tags
		$tagcount = substr_count($_POST['entrytext'], '#');
		if($tagcount > 0)
		{
			tagit($tagxml,$_POST['entrytext'],$dom->getElementsByTagName("entry")->length-1);			
			$dom = new DOMDocument('1.0');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML($tagxml->asXML());
			$dom->save($tagxmlfile);		
		}
	}
}

//if viewing specific tag entries
if ("tag" == $_GET['action'] && isset($_GET['tag']))  
{
	 if(isset($extra))
	{
		foreach($extra as &$element)
		{
			if('num'==$element['type']) $element['total']=0;
		}
	}
	 array_pop($viewingtags);
	 foreach($tagxml->$_GET['tag']->index as $index)
	 {
	 	$index=intval($index);
	 	array_push($viewingtags,intval($index));
	 	if(isset($extra))
		{
			foreach($extra as &$element)
			{
				if(!empty($entxml->entry[$index]->$element['name']) && $element['type']=='num') //the element is set and it's numerical
				{
					
					$element['total']=$element['total']+floatval($entxml->entry[$index]->$element['name']);
				}
			}
		}
	 }
	 rsort($viewingtags);
}

//if usr wants to see all tags
if ("viewtags" == $_GET['action'])  
{
	$link="index.php?auth=".$_GET['auth']."&action=tag&tag=";
	$arcount=0;
	foreach($tagxml->children() as $tag)
	{
		$count=0;
		foreach($tag->index as $index)
		{
			$count++;
		}
		
		$tagarray[$arcount]['num']=$count;
		$tagarray[$arcount]['name']=$tag->getName();
		$arcount++;
	}
	rsort($tagarray);
	foreach($tagarray as $tag)
	{
		echo "<a href=\"".$link.$tag['name'].$uribonus."\">".$tag['name']."</a>(".$tag['num'].") ";
	}
	echo "\n<br />\n";
	
	
}

//processing for the extra toggle link
if(!isset($extra))
{
	$extrayn = '<a href="index.php?auth='.$_GET['auth'].'">Extra Off</a>';
}
else
{
	$extrayn = '<a href="index.php?auth='.$_GET['auth'].'&extra=no">Extra On</a>';
}
//END PROCESSING
//FORM 
?>
<a href="index.php?auth=<?php echo $_GET['auth'].$uribonus;?>">Home</a> - <a href="index.php?auth=<?php echo $_GET['auth'];?>&action=viewtags<?php echo $uribonus; ?>">View Tags</a> - <?php echo $extrayn; ?><br />
<form name="entryform" action="index.php?auth=<?php echo $_GET['auth'];?>&action=add<?php echo $uribonus; ?>" method="post">
<label for="entrytext">Entry:</label><br />
<textarea name="entrytext" rows="5" cols="30"></textarea><br />
<?php
//do processing for bonus elements
if(isset($extra))
{
	foreach($extra as $element)
	{
		echo '<label for="'.$element['name'].'">'.ucwords($element['name']).':</label>';
		echo '<input type="text" name="'.$element['name'].'" class="'.$element['size'].'" /><br />'."\n";
	}
}
?>
<input type="submit" value="Add Entry"/>
</form>
<?php
  //if viewing 1 tag
 if(-1!=$viewingtags[0])
 {
 	$bonus='';
 	if(isset($extra))
	{
		foreach($extra as $element)
		{
			if(isset($element['total'])) $bonus = $bonus."<br />".ucwords($element['name'])." Total: <strong>".$element['total']."</strong>";
		}
	}
	echo "<p>Entries for Tag: <strong>#".$_GET['tag']."</strong>".$bonus."</p>\n";
	foreach($viewingtags as $tagindex)
	{
	
	if(isset($extra))
	{
	$bonus='';
		foreach($extra as $element)
		{
			if(isset($entxml->entry[$tagindex]->$element['name'])) $bonus=$bonus." - ".ucwords($element['name']).": ".$entxml->entry[$tagindex]->$element['name'];
		}
	}
	echo "\t<div class=\"entry\">\n";
		echo "\t\t<div class=\"entrytxt\">".process_entry($entxml->entry[$tagindex]->text,$uribonus)."</div>\n";
		echo "\t\t<span class=\"header\">Date: ".$entxml->entry[$tagindex]->date.$bonus."</span>\n";
	echo "\t</div>\n";
	}

}
//if standard viewing entries
else 
{
	$dom = new DOMDocument('1.0');
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	$dom->loadXML($entxml->asXML());
	$numentries = $dom->getElementsByTagName("entry")->length-1;
	for($i=$numentries;$i>-1;$i--)
	{
	if(isset($extra))
	{
	$bonus='';
		foreach($extra as $element)
		{
			if(isset($entxml->entry[$i]->$element['name'])) $bonus=$bonus." - ".ucwords($element['name']).": ".$entxml->entry[$i]->$element['name'];
		}
	}
		echo "\t<div class=\"entry\">\n";
			echo "\t\t<div class=\"entrytxt\">".process_entry($entxml->entry[$i]->text,$uribonus)."</div>\n";
			echo "\t\t<span class=\"header\">Date: ".$entxml->entry[$i]->date.$bonus."</span>\n";
		echo "\t</div>\n";
	}
} 
?>

</div>
</body>
</html>
