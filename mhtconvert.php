   <?php
    require_once 'mhttohtml.php';
    //$mth = new MhtToHtml($argv[1]??'', $argv[2]??'');

if ($handle = opendir('.')) {
    while (false !== ($file = readdir($handle)))
    {
		if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'mht') {
			// do something with $filename
			$mth = new MhtToHtml( './'.$file );
			$mth->parse();
			echo $file . '<br>';
        }
    }
    closedir($handle);
}
?>
