<?php
function lerPasta($dir) { 
	$arquivosLidos = array(); 
	$dirLidos = array(); 

	$paraLer[] = $dir; 
	$i = 0; 
	while (isset($paraLer[$i])) { 
		$dirLidos[] = $paraLer[$i]; 
		$ponteiro = opendir($paraLer[$i]); 
		while (false !== ($item = readdir($ponteiro))) { 
			if ($item != "." && $item != ".." && $item != ".svn" && $item != "_doc") { 
				if (!is_dir($paraLer[$i] . $item . '/')) { 
					if(in_array(end(explode('.',$item)),array('php','phtml'))){
						$arquivosLidos[] = $paraLer[$i] . $item; 
					}
				} else { 
					$paraLer[] = $paraLer[$i] . $item . '/'; 
				} 
			} 
		} 
		$i++; 
	} 
	sort($arquivosLidos); 
	return $arquivosLidos;
}

echo "<pre>";

$ar = lerPasta("../");
$arText = array();

foreach($ar as $file){
	$fp = fopen($file,'r');
	$content = fread($fp,1024 * 1024);
	preg_match_all("/\_\(\"(.*?)\"\)/",$content,$out);
	$arText = array_merge ($arText,$out[1]);
	fclose($fp);
}

$arText = array_unique($arText);

/// Making PO (do Baum!)

$head[] = 'msgid ""';
$head[] = 'msgstr ""';
$head[] = '"Project-Id-Version: \n"';
$head[] = '"POT-Creation-Date: \n"';
$head[] = '"PO-Revision-Date: \n"';
$head[] = '"Last-Translator: Cláudio <cla.gomess@gmail.com>\n"';
$head[] = '"Language-Team: \n"';
$head[] = '"MIME-Version: 1.0\n"';
$head[] = '"Content-Type: text/plain; charset=UTF-8\n"';
$head[] = '"Content-Transfer-Encoding: 8bit\n"';
$head[] = '"X-Generator: Poedit 1.5.7\n"';

$out  = implode("\r\n",$head);
$out .= "\r\n\r\n";

foreach($arText as $label){
	$out .= "msgid \"{$label}\"\r\nmsgstr \"\"\r\n\r\n";
}

$fp = fopen("../public/locale/master.po",'w+');
fwrite($fp,$out);
fclose($fp);

echo "OK!";


