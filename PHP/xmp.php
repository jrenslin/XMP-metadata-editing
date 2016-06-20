<?PHP

function sidecar_from_file ($file) {
	$content = file_get_contents($file);

	$start = strpos($content, '<x:xmpmeta');
	$end = strpos($content, '</x:xmpmeta>');

	$handle = fopen($file.'_metadata-sidecar.xml', 'w');
	
	fwrite($handle, substr($content, $start, $end - $start + 12));	

	fclose($handle);
}

function get_xml ($file) {
	$content = simplexml_load_string(str_replace(':', '___', file_get_contents($file)));

	$toreturn = readxml_rec($content);

	return ($toreturn);
}

function readxml_rec($xml) {
	$toreturn = array('name' => $xml->getName(), 'value' => trim(strval($xml)), 'children' => array());
	foreach ($xml->children() as $child) {
		$toreturn['children'][] = readxml_rec($child);
	}
	return ($toreturn);
}

function get_rdf_attrib ($input, $attrib) {

	if (isset($input[0])) get_rdf_attrib ($input[0], $attrib);
	else {

		foreach ($input['children'] as $value) {
			if ($value['name'] == $attrib) {
				$toreturn = $value;
			}
			else $toreturn = get_rdf_attrib ($value, $attrib);
		}

	}

	if (isset($toreturn)) return ($toreturn);

}

function rdf_seq_to_array($input) {

	$output = array();

	foreach ($input as $entry) {
		if (isset($entry['name']) and $entry['name'] == 'rdf___li') $output[] = str_replace('___', ':', $entry['value']);
		else if (is_array($entry) == True) $output = rdf_seq_to_array($entry);
	}

	return ($output);
	
}

function ensure_is_array($input) {
	if (!is_array($input)) return array($input);
	else return ($input);
}

function format_rdf_string ($category, $input) {
	$toreturn = '';
	$toreturn .= '<dc:'.$category.'>'.PHP_EOL.'<rdf:Seq>'.PHP_EOL;
	foreach ($input as $entry) $toreturn .= '<rdf:li>'.$entry.'</rdf:li>'.PHP_EOL;
	$toreturn .= '</rdf:Seq>'.PHP_EOL.'</dc:'.$category.'>';
	return ($toreturn);
}

function write_xmp_to_file ($file, $values) {

	$towrite = '';
	
	# Add beginning tags to $towrite
	$towrite = trim('<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Exempi + XMP Core 5.1.2">
				<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
  				<rdf:Description rdf:about=""
    			xmlns:dc="http://purl.org/dc/elements/1.1/">');

	foreach ($values as $key => $value) $towrite .= format_rdf_string ($key, $value);
	
	# Add end tags to $towrite
	$towrite .= trim('</rdf:Description>
				</rdf:RDF>
				</x:xmpmeta>');

	$content = file_get_contents($file);

	$start = strpos($content, '<x:xmpmeta');
	$end = strpos($content, '</x:xmpmeta>');

	$xmp = substr($content, $start, $end - $start + 12);

	if (strlen($towrite) > strlen($xmp)) echo 'Cannot write. Too much data.';
	else {
		$counter = strlen($xmp) - strlen($towrite);
		
		$towrite = substr($towrite, 0, strpos($towrite, '</x:xmpmeta>'));
		for ($i = 1; $i <= $counter; $i++) { $towrite .= ' '; }
		$towrite .= '</x:xmpmeta>';

		$handle = fopen($file, 'w');
	
		fwrite($handle, str_replace('&', '.', (str_replace($xmp, $towrite, $content))));

		fclose($handle);

		if (file_exists($file.'_metadata-sidecar.xml')) unlink($file.'_metadata-sidecar.xml');
	}

}

?>
