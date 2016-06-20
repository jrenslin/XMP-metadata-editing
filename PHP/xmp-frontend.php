<?PHP

include ('xmp.php');

if (isset($_POST['action'])) $action = $_POST['action'];

if (isset($_POST['file'])) $file = trim($_POST['file']);

if (isset($_POST['creator'])) $creator = $_POST['creator'];
if (isset($_POST['title'])) $title = $_POST['title'];
if (isset($_POST['references'])) $references = explode(PHP_EOL, $_POST['references']);

if (isset($file) and isset($action) and $action == 'load') {
	
	if (file_exists($file.'_metadata-sidecar.xml')) unlink($file.'_metadata-sidecar.xml');
	if (!file_exists($file.'_metadata-sidecar.xml')) sidecar_from_file ($file);

	if (filesize($file.'_metadata-sidecar.xml') > 50) {
					
		if ($xml = get_xml ($file.'_metadata-sidecar.xml')) {

			$xmpplain = file_get_contents($file.'_metadata-sidecar.xml');
			
			foreach (array('creator', 'title') as $entry) {  
				${$entry} = trim(strip_tags(substr($xmpplain, strpos($xmpplain, '<dc:'.$entry.'>'), strpos($xmpplain, '</dc:'.$entry.'>') - strpos($xmpplain, '<dc:'.$entry.'>'))));
			}

			$references = get_rdf_attrib ($xml, 'dc___source');
			$references = rdf_seq_to_array($references);

		}
	}
}

?>

<html>
	<head>
		<title>PHP XMP Reference Editor</title>
		<style>
			html {padding:0px;margin:0px;width:100%;}
			body {padding:0px;margin:0px;width:100%;font-family:Sans;}
			header {padding:0px;margin:10px 30px;width:calc(100% - 60px);text-align:center;border-bottom:1px solid #888;}
			header h1 {font-size:1.2em;}

			main {width:calc(100% - 60px);padding:30px;height:80%;}
			main section {width:calc(33% - 32px);border-right:1px solid #888;display:inline-block;vertical-align:top;padding:5px;margin:0px 10px;height:calc(100% - 150px);}
			main section:last-child {border-right:0px solid #888;}
		
			table {width:100%;}
			input, textarea {padding:5px;border:1px solid #aaa;width:calc(100% - 10px);margin:5px 0px;}
			textarea {height:350px;}
			button {padding:5px;border:0px solid #aaa;width:calc(100% - 10px);background-color:#333;color:#fff;margin:5px 0px;}
			button:hover {background-color:#888;}

			footer {display:block;padding:10px;margin:20px 30px 0px 30px;width:calc(100% - 60px);border-top:1px solid #888;}
		</style>
	</head>
	<body>

		<header><h1>PHP XMP Reference Editor</h1></header>

		<main>

		<section>
			<form method="post" enctype="multipart/form-data" action="./xmp_frontend.php">
				<label>File</label>
				<input name="file" value="<?PHP if (isset($file)) echo $file; ?>" />
				<input type="hidden" name="action" value="load" />
				<button type="send">Load</button>
			</form>
		</section>

		<section>
			<form method="post" enctype="multipart/form-data" action="./xmp_frontend.php">
				<table>
					<input type="hidden" name="action" value="write" />
					<?PHP if (isset($file)) { ?><input type="hidden" name="file" value="<?PHP echo $file; ?>" /><?PHP } ?>
					<tr><td>Creator</td><td><input name="creator" value="<?PHP if (isset($creator)) echo $creator; ?>"/></td></tr>
					<tr><td>Title</td><td><input name="title" value="<?PHP if (isset($title)) echo $title; ?>"/></td></tr>
					<tr><td>References</td><td><textarea name="references"><?PHP if (isset($references) and count($references) > 0) { foreach ($references as $reference) { echo $reference.PHP_EOL; } } ?></textarea></td></tr>
				</table>
				<button type="send">Save</button>
			</form>
		</section>

		<section>
			<label>Raw XMP metadata</label>
			<textarea><?PHP if (isset($xmpplain)) echo $xmpplain; ?></textarea>
		</section>

		</main>

		<footer>
			<?PHP 
				if (isset($file) and !is_file($file)) echo $file.' is not a file!!!';
				else if (isset($file) and isset($action) and $action == 'load') echo 'Loaded '.$file.' and created sidecar file at '.$file.'.xml';
				else if (isset($file) and isset($action) and $action == 'write') {
					$towrite = array();
					$towrite['creator'] = array($creator);
					$towrite['title'] = array($title);
					$towrite['source'] = $references;
					write_xmp_to_file ($file, $towrite);
				}
			?>
		</footer>

	</body>
</html>
