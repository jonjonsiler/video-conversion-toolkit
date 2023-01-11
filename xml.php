<?php
$xml = new XMLWriter();
$xml->openMemory();
$xml->startDocument('1.0', 'utf-8');
$xml->setIndent(true);
$xml->setIndentString("\t");
$xml->startElement('playlist');
$xml->writeAttribute("version","1");
$xml->writeAttribute("xmlns","http://xspf.org/ns/0/");
$xml->writeAttribute('xmlns:ov','http://www.oeta.tv/2009/ov/');
$xml->writeElement('title','Title of the Playlist');
$xml->writeElement('creator','OETA');
$xml->writeElement('annotation','Annotation field');
$xml->startElement('meta');
$xml->writeAttribute("rel", "generator");
$xml->text('MetaMorphISIS at OETA');
$xml->endElement();
$xml->startElement('meta');
$xml->writeAttribute("rel", "generatorURL");
$xml->text('http://www.oeta.tv');
$xml->endElement();

$xml->startElement("trackList");

	$xml->startElement("track");
	$xml->writeElement("title", "Track Title");
	$xml->writeElement("location", "Location");
	$xml->writeElement("image", "image");
	$xml->writeElement("identifier", "Identifier");
	$xml->writeElement("annotation", "Track Description’&#8217;");
	$xml->writeElement("trackNum", "Track Order Number");
	$xml->writeElement("ov:subcategory", "Subcategory of main category");
	$xml->startElement("ov:topics");
		$xml->startElement("ov:topic");
		$xml->writeAttribute("id","36");
		$xml->writeAttribute("name","Arts & Literature");
			$xml->startElement("ov:subtopic");
				$xml->writeAttribute("id","39");
				$xml->writeAttribute("name","Crafts");
			$xml->endElement(); //end ov:subtopic element
			$xml->startElement("ov:subtopic");
				$xml->writeAttribute("id","88");
				$xml->writeAttribute("name","Mixed Media");
			$xml->endElement(); //end ov:subtopic element
		$xml->endElement(); //end ov:topic element
	$xml->endElement(); //end ov:topics element
	$xml->endElement(); // end track element

$xml->endElement(); //end trackList

$xml->endElement(); //end playlist element
$xml->endDocument(); //end Document
$DOM = new DOMDocument();
$string= $xml->flush(true);
$string = str_replace("’","&#8217;", $string);
echo $string;
//$sub=$DOM->getElementsByTagName("subcategory");
//foreach ($sub as $element) {
//	echo $element->prefix.":".$element->localName.":".$element->lookupPrefix($element->namespaceURI);
//}
echo "<br />";
echo htmlentities('’', ENT_QUOTES);
echo "<br/>";
echo utf8_encode("’");
echo "<br/>";
echo iconv("UTF-8","UTF-8//TRANSLIT","’");
echo "<br/>";

if (mb_check_encoding("’", "UTF-8"))
{
	echo "UTF8 Safe";
} else {
	echo mb_detect_encoding("’");
}

print_r(get_html_translation_table(HTML_ENTITIES));