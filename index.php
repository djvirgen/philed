<?php

/* Confiuration ====================================*/
$config['path']                 = './data';

/* =================================================*/

$uri = urldecode($_SERVER['REQUEST_URI']);
$script = $_SERVER['SCRIPT_NAME'];
$scriptPath = dirname($script);

// Remove base path
$relPath = mb_substr($uri, strlen($scriptPath));

// Remove query
if (false !== ($pos = strpos($relPath, '?'))) {
    $relPath = mb_substr($relPath, 0, $pos);
}

// Remove leading and trailing slashes
$relPath = trim($relPath, '/');

// Determind absolute path
$basePath = rtrim(realpath($config['path']), DIRECTORY_SEPARATOR);
$absPath = rtrim(realpath($basePath . DIRECTORY_SEPARATOR . $relPath), DIRECTORY_SEPARATOR);

// Ensure that absolute path is within base path
if (false === $absPath OR substr($absPath, 0, strlen($basePath)) != $basePath) {
    //header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found", true);
    header("Status: 404 Not Found");
    echo "File or directory not found.";
    exit;
}

// Serve file if it's a regular file
if (is_file($absPath)) {
    serveFile($absPath);
    exit;
}

// Assume directory
$files = getFiles($absPath);
//$di = new DirectoryIterator($absPath);
$totalFiles = 0;
$totalSize = 0;

?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo htmlentities('Philed: /' . $relPath) ?></title>
<link rel="stylesheet" href="<?php echo url('css/default.css') ?>" media="screen" type="text/css" />
</head>
<body>
<h1>File Browser</h1>
<header>
    <h2>/<?php echo $relPath ?></h2>
</header>
<table id="files">
    <thead>
        <tr>
            <th class="name">File</th>
            <th class="size">Size</th>
        </tr>
    </thead>
    
    <tbody>
        <?php foreach ($files as $file): ?>
        <?php if ('.' == substr($file['name'], 0, 1) AND '..' != $file['name']) continue; ?>
        <?php if ('..' == $file['name'] AND $absPath == $basePath) continue; ?>
        <tr>
            <td class="name">
                <a href="<?php echo fileUrl($file['name']); ?>"><?php if ($file['type'] == 'dir'): ?><img src="<?php echo url('images/icons/folder.png') ?>" alt="" class="icon" /><?php else: ?><img src="<?php echo url('images/icons/page.png') ?>" alt="" class="icon" /><?php endif; ?><?php echo $file['name']; ?></a>
            </td>
            <td class="size">
                <?php if ('file' == $file['type']) echo pretty_size($file['size']); ?>
            </td>
        </tr>
        <?php 
        if ('file' == $file['type']) {
            $totalSize += $file['size'];
            $totalFiles++;
        }
        ?>
        <?php endforeach; ?>
    </tbody>
    
    <tfoot>
        <tr>
            <td class="name">
                Total files: <?php echo $totalFiles; ?> 
            </td>
            <td class="size">
                <?php echo pretty_size($totalSize); ?>
            </td>
        </tr>
    </tfoot>
</table>
<footer>
    Copyright &copy; <?php echo date('Y'); ?> <a href="http://www.virgentech.com">Hector Virgen</a>
</footer>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo url('js/main.js'); ?>"></script>
</body>
</html>
<?php
function fileUrl($filename)
{
    global $scriptPath, $relPath;
    $url = empty($relPath) ? '' : $relPath . '/';
    $url .= $filename;
    return url($url);
}

function url($path)
{
    global $scriptPath;
    $url = rtrim($scriptPath, '/');
    $url .= '/' . $path;
    return $url;
}

function serveFile($absPath)
{
    $filename = basename($absPath);
    $mime = system("file -bi " . escapeshellarg($absPath));
    header("Content-type: ${mime}");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    readfile($absPath);
}

function getFiles($path)
{
    $di = new DirectoryIterator($path);
    $files = array();
    foreach ($di as $file) {
        $files[] = array(
            'name'      => $file->getFilename(),
            'size'      => $file->getSize(),
            'type'      => $file->isFile() ? 'file' : 'dir'
        );
    }
    
    uasort($files, 'sortName');
    //Zend_Debug::dump($files); exit;
    return $files;
}

function sortType($a, $b)
{
    if ($a['type'] == $b['type']) return 0;
    return ($a['type'] == 'file') ? 1 : -1;
}

function sortSize($a, $b)
{
    if ($a['type'] == 'dir') return -1;
    
    if ($a['size'] == $b['size']) return 0;
    return ($a['size'] > $b['size']) ? 1 : -1;
}

function sortName($a, $b)
{
    if ($a['type'] == 'dir' AND $b['type'] == 'file') return -1;
    if ($a['type'] == 'file' AND $b['type'] == 'dir') return 1;
    
    $ar = array(
        $a['name'],
        $b['name']
    );
    natcasesort($ar);
    reset($ar);
    $cur = current($ar);
    return ($a['name'] != $cur) ? 1 : -1;
}

function pretty_size($size)
{
    if ($size < 1024) {
        return number_format($size) . " B";
    } else if ($size < 1000000) {
        return number_format($size / 1024, 2) . "KB";
    } else {
        return number_format($size / 1048576, 2) . "MB";
    }
}