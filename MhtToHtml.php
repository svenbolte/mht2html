<?php /** @noinspection PhpUndefinedClassInspection */

/**
 * A fast memory efficient PHP class to convert MHT file to HTML (and images)
 *
 * NOTICE OF LICENSE
 *
 * Licensed under MIT License. URL: http://opensource.org/licenses/mit-license.html
 *
 * @version    2.0
 * @author     Andy Hu
 * @license    MIT
 * @copyright  (c) 2013, Andy Hu, 2020 Chris
 * @link       https://github.com/andyhu/mht2html
 */
class MhtToHtml
{
    public $file;
    public $outputDir = './htm';
    public $fileSize;
    public $imageFiles;
    public $textFiles;

    // file path
    private $STR_BOUNDARY_PREFIX = 'boundary="';


    // file stream of the mht file
    private $stream;
    // boundary string
    private $boundaryStr;
    // the start pos of the real content
    // content parts
    private $parts;
    // if there's a need to replace the image name to the name with md5 of the image file
    private $replaceImageName = false;
    // map old image name to new ones
    private $imageNameMap;

    /**
     * Constructor
     *
     * @param string $file
     * Path of the mht file to be processed
     *
     * @param string $outputDir
     * Directory of the image output, should be writable
     * @throws Exception
     */
    public function __construct($file = null, $outputDir = null)
    {
        set_time_limit(0);
        if (!$file) {
            if (php_sapi_name() === 'cli') {
                echo 'Please input file name: ';
                $file = trim(fgets(STDIN));
            }
        }
        $this->loadFile($file);
        empty($outputDir) && $outputDir = $this->outputDir;
		$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
		$this->outputDir = './'.$withoutExt;
		$outputDir = './'.$withoutExt;
        // var_dump($outputDir);
        $dirAvailable = true;
        if (!is_dir($outputDir)) {
            $dirAvailable = mkdir($outputDir);
        }
        if (!is_writable(realpath($outputDir)) || !$dirAvailable) {
            throw new Exception('Output directory doesn\'t exist or isn\'t writable');
        }

    }

    /**
     * Load MHT file
     *
     * @param string $file
     * MHT file path
     * @throws Exception
     */
    public function loadFile($file)
    {
        if (is_file($file) && is_readable($file)) {
            $this->file = realpath($file);
            $this->stream = file_get_contents($this->file);
            $this->fileSize = filesize($this->file);

            $this->boundaryStr = $this->getBoundary();
            if (!$this->boundaryStr) {
                throw new Exception('Incorrect file format: Boundary string not found!');
            }
        } else {
            throw new Exception('File doesn\'t exist or is in wrong format');
        }
    }

    /**
     * Get boundary string
     *
     * @return string
     * Return boundary string or false
     */
    private function getBoundary()
    {
        // var_dump(count(explode($this->STR_BOUNDARY_PREFIX, $this->stream)));
        if (!preg_match("/{$this->STR_BOUNDARY_PREFIX}([^\"]+)\"/", $this->stream, $m)) {
            return false;
        }
        return $m[1];
    }

    /** @noinspection PhpUnused */
    public function setReplaceImageName($check)
    {
        $this->replaceImageName = (bool)$check;
    }

    public function __destruct()
    {
        // @fclose($this->stream);
    }

    /**
     * Parse the file
     */
    public function parse()
    {
        $this->getParts();
        $this->imageNameMap = array();

        // write images to disk
        foreach ($this->parts as $i => $part) {
            // processing image
            if ($part['type'] == 'image' && !isset($this->imageNameMap[$part['image_file']])) {
                $part['image_file'] = str_replace(array('\\'), '/', $part['image_file']);
				$oldFilePath = realpath($this->outputDir) . DIRECTORY_SEPARATOR . basename(dirname($part['image_file'])) . DIRECTORY_SEPARATOR . basename($part['image_file']);
                if (!$this->replaceImageName) {
                    if (!is_dir(realpath($this->outputDir) . DIRECTORY_SEPARATOR . basename(dirname($part['image_file'])))) {
                        mkdir(realpath($this->outputDir) . DIRECTORY_SEPARATOR . basename(dirname($part['image_file'])), 0777, true);
                    }
                    $newFilePath = $oldFilePath;
                    $imageFileName = $part['image_file'];
                } else {
                    $md5FileName = md5_file($oldFilePath) . '.' . str_replace('jpeg', 'jpg', $part['format']);
                    $this->imageNameMap[$part['image_file']] = $md5FileName;
                    $newFilePath = realpath($this->outputDir) . DIRECTORY_SEPARATOR . $md5FileName;
                    $imageFileName = $md5FileName;
                }
                $this->imageFiles[] = $imageFileName;
            } elseif ($part['type'] == 'text' && $part['format'] == 'html') {
                // not right yet (??) - consider frames... need to find internet explorer somewhere to investigate
                $newFilePath = realpath($this->outputDir) . DIRECTORY_SEPARATOR . basename($part['image_file']);
                $this->replaceImageName && $this->replaceImage($part['content']);
                $this->textFiles[] = basename($newFilePath);
            } else {
                // xml or something idk
                if (!is_dir(realpath($this->outputDir) . DIRECTORY_SEPARATOR . basename(dirname($part['image_file'])))) {
                    mkdir(realpath($this->outputDir) . DIRECTORY_SEPARATOR . basename(dirname($part['image_file'])), 0777, true);
                }
                $newFilePath = realpath($this->outputDir) . DIRECTORY_SEPARATOR . basename(dirname($part['image_file'])) . DIRECTORY_SEPARATOR . basename($part['image_file']);
            }
            file_put_contents($newFilePath, $part['content']);
        }
    }

    /**
     * Get the content MIME parts (positions)
     */
    private function getParts()
    {
        $they = explode($this->boundaryStr, $this->stream);

        unset($they[0], $they[1]);
        foreach ($they as $it) {

            $it = explode("\r\n\r\n", trim($it), 2);
            if (isset($it[1])) {
                $lines = explode("\r\n", $it[0]);
                // last line is file separator
                $it[1] = rtrim($it[1], "\r\n-");
                $part = ['content' => $it[1]];

                foreach ($lines as $line) {

                    $v = sscanf($line, 'Content-Type: %s')[0];
                    if ($v) {
                        list($part['type'], $part['format']) = explode('/', $v);
                        $part['format'] = preg_replace('@;.*@', '', $part['format']);
                        continue;
                    }
                    $v = sscanf($line, 'Content-Location: %s')[0];
                    if ($v) {
                        $part['image_file'] = $v;
                        continue;
                    }
                    $v = sscanf($line, 'Content-Transfer-Encoding: %s')[0];
                    if ($v) {
                        $part['encoding'] = $v;
                        continue;
                    }
                }
                (@$part['encoding'] == 'base64') && @$part['content'] = base64_decode($part['content']);
                (@$part['encoding'] == 'quoted-printable') && @$part['content'] = quoted_printable_decode($part['content']);

                $this->parts[] = $part;
            }
        }
    }

    /**
     * Replace old image names to new image names in the html
     *
     * @param string &$content
     * HTML content block (text format)
     */
    function replaceImage(&$content)
    {
        foreach ($this->imageNameMap as $oldImg => $newImg) {
            if (strpos($content, $oldImg) !== false) {
                $content = preg_replace('/(<img\s+[^>]*?)(["\'])' . preg_quote($oldImg, '/') . '\2/si', '$1"' . $newImg . '"', $content);
            }
        }
    }
}
?>
