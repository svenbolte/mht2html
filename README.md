Mht to HTML
===========

A PHP class and script to convert all MHT files in the folder to HTML (and images)

Usage:
------
call mhtconvert.php from four php webserver where you installed this code
It searches installed folder for .mht files, created a folder with mht filename
and outputs html and jpeg files conteined in the mht archive

You can also place this php code in your script to call the class from there programmatically:

    <?php
    require_once 'mht2html.php';
    $mth = new MhtToHtml($argv[1]??'', $argv[2]??'');
    $mth->parse();
	?>


Changes in 2.0.1 Fork
-------------------
* tested and works with php8 (and under windows apache) too
* Loop for converting all mht files in the folder and create subfolders (named like the respective mht file) and output there
* Original filenames are preserved
* Swapped from explicit C-style processing to high-level PHP-style (probably slower, still practically instantaneous)
* Consume more memory
* More delicate handling of different filetypes and encodings (eg quoted_printable)
* re-imagined `is_dir` and `mkdir` usage (fewer notices)
* `getParts()` loop now has an exit condition
* minimal cmd example in readme. stick it in a file, chmod +x, maybe put it in your path, away you go!?
* Now needs PHP7 due to some of the syntactic sugar

Consider
--------
* convert to shortcode [[mhtconvert]] for wordpress to convert all mht files in a specific subfolder under upload
  (protected and available only if admin is logged on)
* The constructor could be moved into `parse()` and the class could become static
* Maybe return an array or object representing all the files and contents - does operator really want to just save them all with impunity?
* Other languages?

Thanks
------
Brilliant tool, Andy Hu! I was surprised that there wasn't something like this on my system already