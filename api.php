<?php
// Directorio de DATOS escribibles (config, jobs, logs, binarios, cache).
// En modo empaquetado (.exe) el launcher define CM_DATA_DIR apuntando a
// %LOCALAPPDATA%\ControlMusica (escribible). En dev/XAMPP queda sin definir
// y usa __DIR__, así que el comportamiento actual no cambia.
function cm_data_dir() {
    static $dir = null;
    if ($dir !== null) return $dir;
    $env = getenv('CM_DATA_DIR');
    $dir = (is_string($env) && $env !== '') ? rtrim($env, "\\/") : __DIR__;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

$action = $_GET['action'] ?? '';
$documentsPath = resolveDocumentsPath();
$downloadsPath = resolveDownloadsPath($documentsPath);
$downloadJobsPath = cm_data_dir() . DIRECTORY_SEPARATOR . 'download_jobs';
$folderConfigPath = cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'folders.json';
$playlistConfigPath = cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'playlists.json';

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', cm_data_dir() . DIRECTORY_SEPARATOR . 'php_debug.log');

$isJsonAction = $action !== 'play';

if ($isJsonAction) {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, HEAD, OPTIONS');
}

register_shutdown_function(function () use ($isJsonAction) {
    if (!$isJsonAction) return;

    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'ok' => false,
            'error' => 'Error fatal en PHP. Revisa php_debug.log.',
            'phpError' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Limpieza periodica (a lo sumo una vez por hora) de archivos temporales de
// jobs y rotacion de logs. Se omite en acciones de alta frecuencia/streaming.
if (!in_array($action, ['play', 'download_status', 'native_player_state'], true)) {
    maybeCleanupRuntimeFiles();
}

// Elimina archivos viejos de download_jobs/player_jobs y recorta logs grandes.
// Es seguro: solo borra archivos generados por la app con mas de 24 h (una
// descarga/sesion activa siempre tiene archivos recientes), y esta limitada a
// una pasada por hora mediante un archivo marcador.
function maybeCleanupRuntimeFiles() {
    $appData = cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data';
    $marker = $appData . DIRECTORY_SEPARATOR . 'last_cleanup.txt';
    $now = time();

    $last = is_file($marker) ? (int) @file_get_contents($marker) : 0;
    if ($now - $last < 3600) {
        return;
    }

    if (!is_dir($appData)) {
        @mkdir($appData, 0777, true);
    }
    @file_put_contents($marker, (string) $now, LOCK_EX);

    $maxAge = 24 * 3600;

    foreach (['download_jobs', 'player_jobs'] as $dir) {
        $path = cm_data_dir() . DIRECTORY_SEPARATOR . $dir;
        if (!is_dir($path)) {
            continue;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === '.htaccess') {
                continue;
            }

            $full = $path . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($full)) {
                continue;
            }

            $mtime = @filemtime($full);
            if ($mtime !== false && ($now - $mtime) > $maxAge) {
                @unlink($full);
            }
        }
    }

    foreach (['debug_download.log', 'php_debug.log'] as $logName) {
        $logPath = cm_data_dir() . DIRECTORY_SEPARATOR . $logName;
        if (is_file($logPath) && (int) @filesize($logPath) > 524288) {
            $data = @file_get_contents($logPath);
            if (is_string($data)) {
                @file_put_contents($logPath, substr($data, -65536), LOCK_EX);
            }
        }
    }
}

function logDebug($message, $context = []) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    file_put_contents(cm_data_dir() . DIRECTORY_SEPARATOR . 'debug_download.log', $line . PHP_EOL, FILE_APPEND);
}

function envValue($name) {
    $value = getenv($name);

    if ($value === false || $value === '') {
        $value = $_SERVER[$name] ?? '';
    }

    return is_string($value) ? trim($value) : '';
}

function normalizeWindowsPath($path) {
    return rtrim(str_replace('/', '\\', trim((string) $path)), "\\ \t\n\r\0\x0B");
}

function expandWindowsEnvVars($path) {
    return preg_replace_callback('/%([^%]+)%/', function ($matches) {
        return envValue($matches[1]) ?: $matches[0];
    }, $path);
}

function addPathCandidate(&$candidates, $path) {
    $path = normalizeWindowsPath(expandWindowsEnvVars($path));

    if ($path !== '' && !in_array($path, $candidates, true)) {
        $candidates[] = $path;
    }
}

function powershellKnownFolder($folderName) {
    if (!function_exists('shell_exec')) return '';

    $folderName = preg_replace('/[^A-Za-z]/', '', $folderName);
    if ($folderName === '') return '';

    $cmd = 'powershell.exe -NoProfile -Command "[Environment]::GetFolderPath(' . "'" . $folderName . "'" . ')"';
    $result = @shell_exec($cmd);

    return is_string($result) ? trim($result) : '';
}

function resolveDocumentsPath() {
    $candidates = [];

    addPathCandidate($candidates, envValue('CONTROL_MUSICA_DOCUMENTS'));

    $userProfile = envValue('USERPROFILE');
    if ($userProfile !== '') {
        addPathCandidate($candidates, $userProfile . '\\Documents');
    }

    $homeDrive = envValue('HOMEDRIVE');
    $homePath = envValue('HOMEPATH');
    if ($homeDrive !== '' && $homePath !== '') {
        addPathCandidate($candidates, $homeDrive . $homePath . '\\Documents');
    }

    foreach (['OneDrive', 'OneDriveCommercial', 'OneDriveConsumer'] as $envName) {
        $oneDrive = envValue($envName);
        if ($oneDrive !== '') {
            addPathCandidate($candidates, $oneDrive . '\\Documents');
        }
    }

    addWindowsProfileDocumentCandidates($candidates);

    foreach ($candidates as $path) {
        if (is_dir($path)) return $path;
    }

    addPathCandidate($candidates, powershellKnownFolder('MyDocuments'));

    foreach ($candidates as $path) {
        if (is_dir($path) && stripos($path, '\\systemprofile\\') === false) return $path;
    }

    addPathCandidate($candidates, 'C:\\Users\\Public\\Documents');

    foreach ($candidates as $path) {
        if (is_dir($path)) return $path;
    }

    return $candidates[0] ?? 'C:\\Users\\Public\\Documents';
}

function resolveDownloadsPath($documentsPath) {
    $candidates = [];

    addPathCandidate($candidates, envValue('CONTROL_MUSICA_DOWNLOADS'));

    $userProfile = envValue('USERPROFILE');
    if ($userProfile !== '') {
        addPathCandidate($candidates, $userProfile . '\\Downloads');
    }

    addPathCandidate($candidates, dirname($documentsPath) . '\\Downloads');

    foreach ($candidates as $path) {
        if (is_dir($path)) return $path;
    }

    return $candidates[0] ?? dirname($documentsPath) . '\\Downloads';
}

function resolveYtDlpPath($downloadsPath) {
    $candidates = [];

    addPathCandidate($candidates, cm_data_dir() . '\\app_data\\bin\\yt-dlp.exe');
    addPathCandidate($candidates, __DIR__ . '\\yt-dlp.exe');

    $localAppData = envValue('LOCALAPPDATA');
    if ($localAppData !== '') {
        addPathCandidate($candidates, $localAppData . '\\Microsoft\\WinGet\\Links\\yt-dlp.exe');
        addPathCandidate($candidates, $localAppData . '\\Microsoft\\WinGet\\Links\\yt-dlp.cmd');
    }

    $userProfile = envValue('USERPROFILE');
    if ($userProfile !== '') {
        addPathCandidate($candidates, $userProfile . '\\AppData\\Local\\Microsoft\\WinGet\\Links\\yt-dlp.exe');
        addPathCandidate($candidates, $userProfile . '\\AppData\\Local\\Microsoft\\WinGet\\Links\\yt-dlp.cmd');
    }

    addPathCandidate($candidates, $downloadsPath . '\\yt-dlp.exe');
    addPathCandidate($candidates, $downloadsPath . '\\yt-dlp.cmd');

    foreach ($candidates as $path) {
        if (is_file($path)) return $path;
    }

    return '';
}

function projectYtDlpPath() {
    return cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'yt-dlp.exe';
}

function validYtDlpBinary($path) {
    return is_file($path) && filesize($path) > 500000;
}

function downloadYtDlpBinary($targetPath, &$error = '') {
    $url = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe';
    $dir = dirname($targetPath);

    if (!ensureInternalDirectory($dir)) {
        $error = 'No se pudo crear la carpeta interna para yt-dlp.';
        return false;
    }

    $tmpPath = $targetPath . '.download';
    @unlink($tmpPath);

    if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 90,
                'follow_location' => 1,
                'user_agent' => 'ControlMusica/1.0'
            ]
        ]);

        $data = @file_get_contents($url, false, $context);

        if (is_string($data) && strlen($data) > 500000 && @file_put_contents($tmpPath, $data, LOCK_EX) !== false) {
            @rename($tmpPath, $targetPath);

            if (validYtDlpBinary($targetPath)) {
                return true;
            }
        }

        @unlink($tmpPath);
    }

    if (!function_exists('shell_exec')) {
        $error = 'No se pudo descargar yt-dlp y shell_exec está deshabilitado.';
        return false;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'cm_ytdlp_');
    if ($tmp === false) {
        $error = 'No se pudo crear un archivo temporal para descargar yt-dlp.';
        return false;
    }

    $ps1 = $tmp . '.ps1';
    @unlink($tmp);

    $ps = "";
    $ps .= "\$ErrorActionPreference = 'Stop'\r\n";
    $ps .= "\$ProgressPreference = 'SilentlyContinue'\r\n";
    $ps .= "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12\r\n";
    $ps .= "\$url = " . psQuote($url) . "\r\n";
    $ps .= "\$target = " . psQuote($tmpPath) . "\r\n";
    $ps .= "Invoke-WebRequest -UseBasicParsing -Uri \$url -OutFile \$target\r\n";

    if (@file_put_contents($ps1, "\xEF\xBB\xBF" . $ps) === false) {
        $error = 'No se pudo crear el script temporal para descargar yt-dlp.';
        return false;
    }

    $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File ' . cmdQuote($ps1) . ' 2>&1';
    $output = @shell_exec($cmd);
    @unlink($ps1);

    if (validYtDlpBinary($tmpPath)) {
        @rename($tmpPath, $targetPath);

        if (validYtDlpBinary($targetPath)) {
            return true;
        }
    }

    @unlink($tmpPath);
    $error = 'No se pudo descargar yt-dlp automáticamente.' . (is_string($output) && trim($output) !== '' ? ' Detalle: ' . trim($output) : '');
    return false;
}

function ensureYtDlpPath($downloadsPath) {
    $path = resolveYtDlpPath($downloadsPath);

    if ($path !== '') {
        return ['path' => $path, 'downloaded' => false, 'error' => ''];
    }

    $targetPath = projectYtDlpPath();
    $error = '';

    if (downloadYtDlpBinary($targetPath, $error)) {
        return ['path' => $targetPath, 'downloaded' => true, 'error' => ''];
    }

    return ['path' => '', 'downloaded' => false, 'error' => $error ?: 'No se encontró yt-dlp.'];
}

function resolveFfmpegPath() {
    $candidates = [];

    addPathCandidate($candidates, cm_data_dir() . '\\app_data\\bin\\ffmpeg.exe');
    addPathCandidate($candidates, __DIR__ . '\\ffmpeg.exe');

    $localAppData = envValue('LOCALAPPDATA');
    if ($localAppData !== '') {
        addPathCandidate($candidates, $localAppData . '\\Microsoft\\WinGet\\Links\\ffmpeg.exe');
    }

    $programData = envValue('ProgramData');
    if ($programData !== '') {
        addPathCandidate($candidates, $programData . '\\chocolatey\\bin\\ffmpeg.exe');
    }

    foreach ($candidates as $path) {
        if (is_file($path)) return $path;
    }

    return '';
}

function projectFfmpegPath() {
    return cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg.exe';
}

function validFfmpegBinary($path) {
    return is_file($path) && filesize($path) > 500000;
}

function downloadFfmpegBinary($targetPath, &$error = '') {
    if (stripos(PHP_OS_FAMILY, 'Windows') === false) {
        $error = 'La descarga automática de ffmpeg está preparada para Windows.';
        return false;
    }

    if (!function_exists('shell_exec')) {
        $error = 'No se pudo descargar ffmpeg porque shell_exec está deshabilitado.';
        return false;
    }

    $dir = dirname($targetPath);

    if (!ensureInternalDirectory($dir)) {
        $error = 'No se pudo crear la carpeta interna para ffmpeg.';
        return false;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'cm_ffmpeg_');
    if ($tmp === false) {
        $error = 'No se pudo crear un archivo temporal para descargar ffmpeg.';
        return false;
    }

    $ps1 = $tmp . '.ps1';
    @unlink($tmp);

    $url = 'https://github.com/BtbN/FFmpeg-Builds/releases/latest/download/ffmpeg-master-latest-win64-gpl.zip';
    $zipPath = $targetPath . '.zip';
    $extractPath = dirname($targetPath) . DIRECTORY_SEPARATOR . 'ffmpeg_extract';

    $ps = "";
    $ps .= "\$ErrorActionPreference = 'Stop'\r\n";
    $ps .= "\$ProgressPreference = 'SilentlyContinue'\r\n";
    $ps .= "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12\r\n";
    $ps .= "\$url = " . psQuote($url) . "\r\n";
    $ps .= "\$zip = " . psQuote($zipPath) . "\r\n";
    $ps .= "\$extract = " . psQuote($extractPath) . "\r\n";
    $ps .= "\$target = " . psQuote($targetPath) . "\r\n";
    $ps .= "if (Test-Path -LiteralPath \$zip) { Remove-Item -LiteralPath \$zip -Force }\r\n";
    $ps .= "if (Test-Path -LiteralPath \$extract) { Remove-Item -LiteralPath \$extract -Recurse -Force }\r\n";
    $ps .= "Invoke-WebRequest -UseBasicParsing -Uri \$url -OutFile \$zip\r\n";
    $ps .= "Expand-Archive -LiteralPath \$zip -DestinationPath \$extract -Force\r\n";
    $ps .= "\$ffmpeg = Get-ChildItem -LiteralPath \$extract -Recurse -Filter ffmpeg.exe | Select-Object -First 1\r\n";
    $ps .= "if (-not \$ffmpeg) { throw 'ffmpeg.exe no encontrado dentro del ZIP.' }\r\n";
    $ps .= "Copy-Item -LiteralPath \$ffmpeg.FullName -Destination \$target -Force\r\n";
    $ps .= "Remove-Item -LiteralPath \$zip -Force -ErrorAction SilentlyContinue\r\n";
    $ps .= "Remove-Item -LiteralPath \$extract -Recurse -Force -ErrorAction SilentlyContinue\r\n";

    if (@file_put_contents($ps1, "\xEF\xBB\xBF" . $ps) === false) {
        $error = 'No se pudo crear el script temporal para descargar ffmpeg.';
        return false;
    }

    $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File ' . cmdQuote($ps1) . ' 2>&1';
    $output = @shell_exec($cmd);
    @unlink($ps1);

    if (validFfmpegBinary($targetPath)) {
        return true;
    }

    $error = 'No se pudo descargar ffmpeg automáticamente.' . (is_string($output) && trim($output) !== '' ? ' Detalle: ' . trim($output) : '');
    return false;
}

function ensureFfmpegPath() {
    $path = resolveFfmpegPath();

    if ($path !== '') {
        return ['path' => $path, 'downloaded' => false, 'error' => ''];
    }

    $targetPath = projectFfmpegPath();
    $error = '';

    if (downloadFfmpegBinary($targetPath, $error)) {
        return ['path' => $targetPath, 'downloaded' => true, 'error' => ''];
    }

    return ['path' => '', 'downloaded' => false, 'error' => $error ?: 'No se encontró ffmpeg.'];
}

function addWindowsProfileDocumentCandidates(&$candidates) {
    $systemDrive = envValue('SystemDrive') ?: 'C:';
    $usersRoot = normalizeWindowsPath($systemDrive . '\\Users');

    if (!is_dir($usersRoot)) return;

    $excluded = [
        'all users' => true,
        'default' => true,
        'default user' => true,
        'public' => true
    ];

    $profiles = [];

    foreach (scandir($usersRoot) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;

        $profilePath = $usersRoot . '\\' . $name;
        if (!is_dir($profilePath)) continue;

        $key = strtolower($name);
        if (isset($excluded[$key])) continue;

        $documents = $profilePath . '\\Documents';
        if (!is_dir($documents)) continue;

        $score = 0;

        foreach ([$profilePath, $documents, $profilePath . '\\NTUSER.DAT'] as $path) {
            if (file_exists($path)) {
                $time = @filemtime($path);
                if ($time !== false) $score = max($score, $time);
            }
        }

        $profiles[] = [
            'score' => $score,
            'documents' => $documents,
            'profile' => $profilePath
        ];
    }

    usort($profiles, fn($a, $b) => $b['score'] <=> $a['score']);

    foreach ($profiles as $profile) {
        addPathCandidate($candidates, $profile['documents']);

        foreach (glob($profile['profile'] . '\\OneDrive*', GLOB_ONLYDIR) ?: [] as $oneDrivePath) {
            addPathCandidate($candidates, $oneDrivePath . '\\Documents');
        }
    }
}

function sendJson($data) {
    while (ob_get_level()) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
    );

    if ($json === false || $json === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'No se pudo convertir la respuesta a JSON.',
            'jsonError' => json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo $json;
    exit;
}

function ensureInternalDirectory($path) {
    return is_dir($path) || mkdir($path, 0777, true);
}

function readJsonFile($path, $fallback) {
    if (!is_file($path)) return $fallback;

    $raw = @file_get_contents($path);
    if ($raw === false) return $fallback;

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $data = json_decode($raw, true);

    return is_array($data) ? $data : $fallback;
}

function writeJsonFile($path, $data) {
    $dir = dirname($path);

    if (!ensureInternalDirectory($dir)) return false;

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    if ($json === false) return false;

    return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
}

function isVideoFile($filename) {
    $videoExts = ['mp4', 'mkv', 'webm', 'avi', 'mov', 'wmv', 'flv', 'm4v', 'mpg', 'mpeg', '3gp', 'ts', 'mts', 'm2ts'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $videoExts, true);
}

function isImageFile($filename) {
    $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'tiff', 'tif', 'heic', 'heif', 'avif'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $imageExts, true);
}

function normalizeMediaType($type) {
    return strtolower((string) $type) === 'image' ? 'image' : 'video';
}

function isMediaFileByType($filename, $type) {
    return normalizeMediaType($type) === 'image' ? isImageFile($filename) : isVideoFile($filename);
}

function normalizeFolderPath($path) {
    $path = trim((string) $path);
    $path = trim($path, "\"'");
    if ($path === '') return '';
    $real = realpath($path);
    $path = $real !== false ? $real : $path;
    return normalizeWindowsPath($path);
}

function configuredFolderId($path) {
    $normalized = strtolower(normalizeFolderPath($path));
    return 'f_' . substr(hash('sha256', $normalized), 0, 24);
}

function folderDisplayName($path) {
    $name = basename(normalizeFolderPath($path));
    return $name !== '' ? $name : normalizeFolderPath($path);
}

function folderIdentity($path) {
    if (!is_dir($path)) return [];

    $stat = @stat($path);

    if (!$stat) return [];

    return [
        'dev' => (string) ($stat['dev'] ?? ''),
        'ino' => (string) ($stat['ino'] ?? ''),
        'ctime' => (string) (@filectime($path) ?: '')
    ];
}

function sameFolderIdentity($a, $b) {
    if (!is_array($a) || !is_array($b)) return false;

    $aDev = (string) ($a['dev'] ?? '');
    $bDev = (string) ($b['dev'] ?? '');
    $aIno = (string) ($a['ino'] ?? '');
    $bIno = (string) ($b['ino'] ?? '');

    if ($aDev !== '' && $bDev !== '' && $aIno !== '' && $bIno !== '' && $aIno !== '0' && $bIno !== '0') {
        return $aDev === $bDev && $aIno === $bIno;
    }

    $aCtime = (string) ($a['ctime'] ?? '');
    $bCtime = (string) ($b['ctime'] ?? '');

    return $aCtime !== '' && $aCtime === $bCtime;
}

function recoverConfiguredFolderPath($item, $missingPath) {
    if (!is_array($item)) return '';

    $parent = normalizeFolderPath($item['parentPath'] ?? dirname($missingPath));
    $identity = $item['identity'] ?? null;

    if ($parent === '' || !is_dir($parent) || !is_array($identity)) return '';

    foreach (scandir($parent) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;

        $candidate = $parent . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($candidate)) continue;

        if (sameFolderIdentity($identity, folderIdentity($candidate))) {
            return normalizeFolderPath($candidate);
        }
    }

    return '';
}

function countDirectVideos($folderPath) {
    if (!is_dir($folderPath)) return 0;

    $count = 0;

    foreach (scandir($folderPath) ?: [] as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $folderPath . DIRECTORY_SEPARATOR . $file;

        if (is_file($fullPath) && isVideoFile($file)) {
            $count++;
        }
    }

    return $count;
}

function countDirectImages($folderPath) {
    if (!is_dir($folderPath)) return 0;

    $count = 0;

    foreach (scandir($folderPath) ?: [] as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $folderPath . DIRECTORY_SEPARATOR . $file;

        if (is_file($fullPath) && isImageFile($file)) {
            $count++;
        }
    }

    return $count;
}

function normalizeConfiguredFolder($item) {
    if (!is_array($item)) return null;

    $path = normalizeFolderPath($item['realPath'] ?? $item['absolutePath'] ?? '');

    if ($path === '') return null;

    if (!is_dir($path)) {
        $recoveredPath = recoverConfiguredFolderPath($item, $path);

        if ($recoveredPath !== '') {
            $path = $recoveredPath;
        } else {
            return null;
        }
    }

    $id = $item['id'] ?? configuredFolderId($path);

    if (!preg_match('/^f_[a-f0-9]{24}$/', $id)) {
        $id = configuredFolderId($path);
    }

    $name = folderDisplayName($path);

    return [
        'id' => $id,
        'name' => $name,
        'path' => $id,
        'realPath' => $path,
        'parentPath' => normalizeFolderPath(dirname($path)),
        'identity' => folderIdentity($path),
        'videoCount' => countDirectVideos($path),
        'imageCount' => countDirectImages($path),
        'addedAt' => $item['addedAt'] ?? date('c')
    ];
}

function readConfiguredFolders($folderConfigPath) {
    $data = readJsonFile($folderConfigPath, []);
    $items = $data['folders'] ?? $data;
    $folders = [];
    $seen = [];

    if (!is_array($items)) return [];

    foreach ($items as $item) {
        $folder = normalizeConfiguredFolder($item);
        if (!$folder) continue;

        $key = strtolower($folder['realPath']);
        if (isset($seen[$key])) continue;

        $seen[$key] = true;
        $folders[] = $folder;
    }

    usort($folders, fn($a, $b) => strcasecmp($a['name'], $b['name']));

    return $folders;
}

function publicConfiguredFolder($folder) {
    return [
        'id' => $folder['id'],
        'name' => $folder['name'],
        'path' => $folder['id'],
        'videoCount' => $folder['videoCount'],
        'imageCount' => $folder['imageCount'] ?? countDirectImages($folder['realPath']),
        'source' => 'api'
    ];
}

function saveConfiguredFolders($folderConfigPath, $folders) {
    $payload = [
        'version' => 1,
        'updatedAt' => date('c'),
        'folders' => array_map(function ($folder) {
            return [
                'id' => $folder['id'],
                'name' => $folder['name'],
                'realPath' => $folder['realPath'],
                'parentPath' => $folder['parentPath'] ?? normalizeFolderPath(dirname($folder['realPath'])),
                'identity' => $folder['identity'] ?? folderIdentity($folder['realPath']),
                'addedAt' => $folder['addedAt'] ?? date('c')
            ];
        }, $folders)
    ];

    return writeJsonFile($folderConfigPath, $payload);
}

function addConfiguredFolder($folderConfigPath, $folderPath) {
    $folderPath = normalizeFolderPath($folderPath);

    if ($folderPath === '' || !is_dir($folderPath)) {
        return ['error' => 'La carpeta seleccionada no existe.'];
    }

    $folders = readConfiguredFolders($folderConfigPath);
    $id = configuredFolderId($folderPath);
    $videoCount = countDirectVideos($folderPath);
    $imageCount = countDirectImages($folderPath);

    foreach ($folders as $folder) {
        if ($folder['id'] === $id || strcasecmp($folder['realPath'], $folderPath) === 0) {
            $folder['videoCount'] = $videoCount;
            $folder['imageCount'] = $imageCount;
            return [
                'ok' => true,
                'alreadyAdded' => true,
                'folder' => publicConfiguredFolder($folder)
            ];
        }
    }

    $folder = [
        'id' => $id,
        'name' => folderDisplayName($folderPath),
        'path' => $id,
        'realPath' => $folderPath,
        'parentPath' => normalizeFolderPath(dirname($folderPath)),
        'identity' => folderIdentity($folderPath),
        'videoCount' => $videoCount,
        'imageCount' => $imageCount,
        'addedAt' => date('c')
    ];

    $folders[] = $folder;

    if (!saveConfiguredFolders($folderConfigPath, $folders)) {
        return ['error' => 'No se pudo guardar la carpeta en la configuracion interna.'];
    }

    return [
        'ok' => true,
        'alreadyAdded' => false,
        'folder' => publicConfiguredFolder($folder)
    ];
}

function findConfiguredFolder($folderConfigPath, $folderId) {
    if (!preg_match('/^f_[a-f0-9]{24}$/', $folderId)) return null;

    foreach (readConfiguredFolders($folderConfigPath) as $folder) {
        if ($folder['id'] === $folderId) return $folder;
    }

    return null;
}

function listConfiguredFolderVideos($folder) {
    return listConfiguredFolderMedia($folder, 'video');
}

function listConfiguredFolderImages($folder) {
    return listConfiguredFolderMedia($folder, 'image');
}

// Quita el sufijo " [id]" que yt-dlp agrega al final del nombre (p. ej.
// " [6yI9VBPpILU]") SOLO para mostrarlo; el nombre real del archivo no cambia.
// Conservador para no borrar sufijos legitimos: el contenido del corchete debe
// no tener espacios y, o medir 11 (id de YouTube), o tener 8+ caracteres con
// algun digito/guion/guion_bajo. Asi "[Official]" o "[En Vivo]" se conservan.
function stripDisplayIdSuffix($title) {
    return preg_replace_callback(
        '/\s*\[([A-Za-z0-9_-]+)\]\s*$/u',
        function ($m) {
            $id = $m[1];
            $len = strlen($id);
            $looksLikeId = ($len === 11) || ($len >= 8 && preg_match('/[0-9_-]/', $id) === 1);
            return $looksLikeId ? '' : $m[0];
        },
        (string) $title
    );
}

function listConfiguredFolderMedia($folder, $type) {
    $type = normalizeMediaType($type);
    $videos = [];
    $folderPath = $folder['realPath'];

    if (!is_dir($folderPath)) return $videos;

    $entries = scandir($folderPath);
    if ($entries === false) return $videos;

    // Cache liviano del listado: evita re-statear cada archivo (is_file +
    // filesize + info.json) en cada peticion (cambio de carpeta y sondeo
    // periodico). La firma combina los nombres del directorio + su mtime, de
    // modo que agregar/quitar/renombrar archivos invalida el cache al instante.
    $dirMtime = @filemtime($folderPath);
    $signature = md5('v2|' . $type . '|' . $dirMtime . '|' . implode("\0", $entries));
    $cacheDir = cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'cache';
    $cacheId = (isset($folder['id']) && $folder['id'] !== '') ? $folder['id'] : md5($folderPath);
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'media_' . preg_replace('/[^a-zA-Z0-9_]/', '', (string) $cacheId) . '_' . $type . '.json';

    $cachedRaw = @file_get_contents($cacheFile);
    if (is_string($cachedRaw) && $cachedRaw !== '') {
        $cached = json_decode($cachedRaw, true);
        if (is_array($cached) && ($cached['sig'] ?? null) === $signature && isset($cached['data']) && is_array($cached['data'])) {
            return $cached['data'];
        }
    }

    foreach ($entries as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $folderPath . DIRECTORY_SEPARATOR . $file;

        if (is_file($fullPath) && isMediaFileByType($file, $type)) {
            $videos[] = [
                'name' => $file,
                'title' => stripDisplayIdSuffix($type === 'image' ? pathinfo($file, PATHINFO_FILENAME) : videoDisplayTitle($folderPath, $file)),
                'size' => filesize($fullPath),
                'folder' => $folder['id'],
                'folderName' => $folder['name'],
                'source' => 'api',
                'kind' => $type
            ];
        }
    }

    usort($videos, fn($a, $b) => strcasecmp($a['title'], $b['title']));

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    @file_put_contents($cacheFile, json_encode(['sig' => $signature, 'data' => $videos], JSON_UNESCAPED_UNICODE), LOCK_EX);

    return $videos;
}

function cleanMediaBaseName($value) {
    $value = trim((string) $value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = trim($value, " \t\n\r\0\x0B.");

    if ($value === '' || !safeName($value)) {
        return '';
    }

    $reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];

    if (in_array(strtoupper($value), $reserved, true)) {
        return '';
    }

    return $value;
}

function renameConfiguredMediaFile($folderConfigPath, $folderId, $file, $type, $newBaseName) {
    $type = normalizeMediaType($type);

    if (!$folderId || !$file || !safeName($file)) {
        return ['error' => 'Archivo no valido para renombrar.'];
    }

    $folder = findConfiguredFolder($folderConfigPath, $folderId);

    if (!$folder) {
        return ['error' => 'Carpeta no valida para renombrar.'];
    }

    $oldPath = $folder['realPath'] . DIRECTORY_SEPARATOR . $file;

    if (!is_file($oldPath) || !isMediaFileByType($file, $type)) {
        return ['error' => 'El archivo no existe o no es compatible.'];
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $newBaseName = cleanMediaBaseName($newBaseName);

    if ($newBaseName === '') {
        return ['error' => 'Escribe un nombre valido.'];
    }

    $typedExt = strtolower(pathinfo($newBaseName, PATHINFO_EXTENSION));
    if ($typedExt === $ext) {
        $newBaseName = pathinfo($newBaseName, PATHINFO_FILENAME);
        $newBaseName = cleanMediaBaseName($newBaseName);
    }

    if ($newBaseName === '') {
        return ['error' => 'Escribe un nombre valido.'];
    }

    if ((function_exists('mb_strlen') ? mb_strlen($newBaseName) : strlen($newBaseName)) > 120) {
        return ['error' => 'El nombre es demasiado largo (maximo 120 caracteres).'];
    }

    $newName = $newBaseName . '.' . $ext;
    $newPath = $folder['realPath'] . DIRECTORY_SEPARATOR . $newName;

    if (strcasecmp($file, $newName) === 0) {
        return [
            'ok' => true,
            'unchanged' => true,
            'item' => [
                'name' => $file,
                'title' => $type === 'image' ? pathinfo($file, PATHINFO_FILENAME) : videoDisplayTitle($folder['realPath'], $file),
                'folder' => $folder['id'],
                'folderName' => $folder['name'],
                'source' => 'api',
                'kind' => $type,
                'size' => filesize($oldPath)
            ]
        ];
    }

    if (file_exists($newPath)) {
        return ['error' => 'Ya existe un archivo con ese nombre en la carpeta.'];
    }

    if (!@rename($oldPath, $newPath)) {
        return ['error' => 'Windows no permitio cambiar el nombre del archivo.'];
    }

    return [
        'ok' => true,
        'oldName' => $file,
        'newName' => $newName,
        'item' => [
            'name' => $newName,
            'title' => $type === 'image' ? pathinfo($newName, PATHINFO_FILENAME) : videoDisplayTitle($folder['realPath'], $newName),
            'folder' => $folder['id'],
            'folderName' => $folder['name'],
            'source' => 'api',
            'kind' => $type,
            'size' => filesize($newPath)
        ]
    ];
}

function safePlaylistId($value) {
    return is_string($value) && preg_match('/^pl_[a-f0-9]{16,32}$/', $value);
}

function cleanPlaylistName($value) {
    $value = trim((string) $value);
    $value = preg_replace('/\s+/', ' ', $value);
    $value = trim($value);

    if ($value === '') return '';

    $value = preg_replace('/[\\\\\/:*?"<>|]/', '-', $value);
    $value = trim($value, " .\t\n\r\0\x0B");

    return function_exists('mb_substr') ? mb_substr($value, 0, 80) : substr($value, 0, 80);
}

function normalizePlaylistItem($folderConfigPath, $item) {
    if (!is_array($item)) return null;

    $folderId = trim((string) ($item['folder'] ?? ''));
    $file = trim((string) ($item['name'] ?? ''));

    if (!$folderId || !$file || !safeName($file)) return null;

    $folder = findConfiguredFolder($folderConfigPath, $folderId);
    if (!$folder) return null;

    $filePath = $folder['realPath'] . DIRECTORY_SEPARATOR . $file;
    if (!is_file($filePath) || !isVideoFile($file)) return null;

    return [
        'name' => $file,
        'title' => stripDisplayIdSuffix(videoDisplayTitle($folder['realPath'], $file)),
        'size' => filesize($filePath),
        'folder' => $folder['id'],
        'folderName' => $folder['name'],
        'source' => 'api',
        'kind' => 'video'
    ];
}

function readSavedPlaylists($playlistConfigPath, $folderConfigPath) {
    $data = readJsonFile($playlistConfigPath, []);
    $items = $data['playlists'] ?? [];

    if (!is_array($items)) return [];

    $playlists = [];

    foreach ($items as $playlist) {
        if (!is_array($playlist)) continue;

        $id = (string) ($playlist['id'] ?? '');
        if (!safePlaylistId($id)) continue;

        $name = cleanPlaylistName($playlist['name'] ?? '');
        if ($name === '') continue;

        $rawItems = $playlist['items'] ?? [];
        if (!is_array($rawItems)) $rawItems = [];

        $mediaItems = [];
        $seen = [];

        foreach ($rawItems as $rawItem) {
            $mediaItem = normalizePlaylistItem($folderConfigPath, $rawItem);
            if (!$mediaItem) continue;

            $key = strtolower($mediaItem['folder'] . '|' . $mediaItem['name']);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $mediaItems[] = $mediaItem;
        }

        $playlists[] = [
            'id' => $id,
            'name' => $name,
            'createdAt' => (string) ($playlist['createdAt'] ?? date('c')),
            'updatedAt' => (string) ($playlist['updatedAt'] ?? ($playlist['createdAt'] ?? date('c'))),
            'items' => $mediaItems,
            'count' => count($mediaItems)
        ];
    }

    usort($playlists, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

    return $playlists;
}

function writeSavedPlaylists($playlistConfigPath, $playlists) {
    $payload = [
        'version' => 1,
        'updatedAt' => date('c'),
        'playlists' => array_values($playlists)
    ];

    return writeJsonFile($playlistConfigPath, $payload);
}

function savePlaylist($playlistConfigPath, $folderConfigPath, $name, $items) {
    $name = cleanPlaylistName($name);

    if ($name === '') {
        return ['error' => 'Escribe un nombre para la playlist.'];
    }

    if (!is_array($items)) {
        return ['error' => 'La playlist no tiene videos validos.'];
    }

    $mediaItems = [];
    $seen = [];

    foreach ($items as $item) {
        $mediaItem = normalizePlaylistItem($folderConfigPath, $item);
        if (!$mediaItem) continue;

        $key = strtolower($mediaItem['folder'] . '|' . $mediaItem['name']);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $mediaItems[] = $mediaItem;
    }

    if (!count($mediaItems)) {
        return ['error' => 'Selecciona al menos un video valido.'];
    }

    $playlists = readSavedPlaylists($playlistConfigPath, $folderConfigPath);
    $now = date('c');
    $playlist = [
        'id' => 'pl_' . bin2hex(random_bytes(8)),
        'name' => $name,
        'createdAt' => $now,
        'updatedAt' => $now,
        'items' => $mediaItems,
        'count' => count($mediaItems)
    ];

    array_unshift($playlists, $playlist);

    if (!writeSavedPlaylists($playlistConfigPath, $playlists)) {
        return ['error' => 'No se pudo guardar la playlist.'];
    }

    return ['ok' => true, 'playlist' => $playlist, 'playlists' => $playlists];
}

function deletePlaylist($playlistConfigPath, $folderConfigPath, $id) {
    if (!safePlaylistId($id)) {
        return ['error' => 'Playlist no valida.'];
    }

    $playlists = readSavedPlaylists($playlistConfigPath, $folderConfigPath);
    $before = count($playlists);
    $playlists = array_values(array_filter($playlists, fn($playlist) => $playlist['id'] !== $id));

    if (count($playlists) === $before) {
        return ['error' => 'La playlist ya no existe.'];
    }

    if (!writeSavedPlaylists($playlistConfigPath, $playlists)) {
        return ['error' => 'No se pudo eliminar la playlist.'];
    }

    return ['ok' => true, 'playlists' => $playlists];
}

function removePlaylistItem($playlistConfigPath, $folderConfigPath, $id, $index) {
    if (!safePlaylistId($id)) {
        return ['error' => 'Playlist no valida.'];
    }

    $index = intval($index);
    if ($index < 0) {
        return ['error' => 'Video no valido.'];
    }

    $playlists = readSavedPlaylists($playlistConfigPath, $folderConfigPath);
    $updatedPlaylist = null;
    $found = false;

    foreach ($playlists as &$playlist) {
        if ($playlist['id'] !== $id) continue;

        $found = true;
        $items = $playlist['items'] ?? [];

        if (!array_key_exists($index, $items)) {
            return ['error' => 'Ese video ya no existe en la playlist.'];
        }

        array_splice($items, $index, 1);
        $playlist['items'] = array_values($items);
        $playlist['count'] = count($playlist['items']);
        $playlist['updatedAt'] = date('c');
        $updatedPlaylist = $playlist;
        break;
    }
    unset($playlist);

    if (!$found) {
        return ['error' => 'La playlist ya no existe.'];
    }

    if (!writeSavedPlaylists($playlistConfigPath, $playlists)) {
        return ['error' => 'No se pudo actualizar la playlist.'];
    }

    return ['ok' => true, 'playlist' => $updatedPlaylist, 'playlists' => $playlists];
}

function videoDisplayTitle($folderPath, $file) {
    $fallback = pathinfo($file, PATHINFO_FILENAME);
    $infoPath = $folderPath . DIRECTORY_SEPARATOR . $fallback . '.info.json';

    if (!is_file($infoPath)) {
        return $fallback;
    }

    $raw = @file_get_contents($infoPath);
    if (!is_string($raw) || $raw === '') {
        return $fallback;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return $fallback;
    }

    $title = trim((string) ($data['title'] ?? ''));
    return $title !== '' ? $title : $fallback;
}

function safeName($value) {
    return $value !== '' &&
        strpos($value, '..') === false &&
        !preg_match('/[\\\\\/:*?"<>|]/', $value);
}

function safeJobId($value) {
    return is_string($value) && preg_match('/^[a-f0-9]{24,64}$/', $value);
}

function isValidDownloadUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;

    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');

    return in_array($scheme, ['http', 'https'], true);
}

function isYouTubeUrl($url) {
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');

    return str_contains($host, 'youtube.com') ||
           str_contains($host, 'youtu.be') ||
           str_contains($host, 'music.youtube.com');
}

function isInstagramUrl($url) {
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');

    return str_contains($host, 'instagram.com');
}

function isFacebookUrl($url) {
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');

    return str_contains($host, 'facebook.com') ||
           str_contains($host, 'fb.watch');
}

function isInstagramOrFacebookUrl($url) {
    return isInstagramUrl($url) || isFacebookUrl($url);
}

function isUnsupportedFacebookReelUrl($url) {
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $path = strtolower(rtrim($parts['path'] ?? '', '/'));

    return str_contains($host, 'facebook.com') && $path === '/reel';
}

function youtubeUrlQuery($url) {
    $parts = parse_url($url);
    $query = [];
    parse_str($parts['query'] ?? '', $query);

    return $query;
}

function youtubeListId($url) {
    $query = youtubeUrlQuery($url);
    return trim((string) ($query['list'] ?? ''));
}

function isYouTubeAutoMixPlaylist($url) {
    if (!isYouTubeUrl($url)) return false;

    $query = youtubeUrlQuery($url);
    $listId = strtoupper(trim((string) ($query['list'] ?? '')));

    return ($listId !== '' && str_starts_with($listId, 'RD')) ||
           !empty($query['start_radio']);
}

function youtubeVideoId($url) {
    if (!isYouTubeUrl($url)) return '';

    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $query = youtubeUrlQuery($url);

    if (!empty($query['v'])) {
        return trim((string) $query['v']);
    }

    $path = trim($parts['path'] ?? '', '/');
    $segments = $path !== '' ? explode('/', $path) : [];

    if (($segments[0] ?? '') === 'shorts' && !empty($segments[1])) {
        return trim((string) $segments[1]);
    }

    if (str_contains($host, 'youtu.be')) {
        return trim((string) ($segments[0] ?? ''));
    }

    return '';
}

function normalizeYouTubeMixDownloadUrl($url) {
    if (preg_match('/([?&]index=)\d+/i', $url)) {
        return preg_replace('/([?&]index=)\d+/i', '${1}1', $url, 1);
    }

    return $url . (str_contains($url, '?') ? '&' : '?') . 'index=1';
}

function normalizeFacebookDownloadUrl($url) {
    if (!isFacebookUrl($url)) return $url;

    $parts = parse_url($url);
    $path = trim((string) ($parts['path'] ?? ''), '/');
    $pathLower = strtolower('/' . $path);
    $query = [];
    parse_str($parts['query'] ?? '', $query);

    if (!empty($query['v']) && preg_match('/^\d+$/', (string) $query['v'])) {
        return 'https://www.facebook.com/watch/?v=' . rawurlencode((string) $query['v']);
    }

    $segments = $path !== '' ? explode('/', $path) : [];

    foreach ($segments as $index => $segment) {
        $segmentLower = strtolower(trim((string) $segment));
        $next = trim((string) ($segments[$index + 1] ?? ''));

        if ($segmentLower === 'reel' && preg_match('/^\d+$/', $next)) {
            return 'https://www.facebook.com/reel/' . rawurlencode($next);
        }

        if ($segmentLower === 'r' && preg_match('/^\d+$/', $next)) {
            return 'https://www.facebook.com/reel/' . rawurlencode($next);
        }

        if ($segmentLower === 'videos' && preg_match('/^\d+$/', $next)) {
            return 'https://www.facebook.com/watch/?v=' . rawurlencode($next);
        }

        if (preg_match('/^\d{6,}$/', $segment)) {
            $kind = (str_contains($pathLower, '/reel') || str_contains($pathLower, '/share/r')) ? 'reel' : 'watch';
            return $kind === 'reel'
                ? 'https://www.facebook.com/reel/' . rawurlencode($segment)
                : 'https://www.facebook.com/watch/?v=' . rawurlencode($segment);
        }
    }

    return $url;
}

function normalizeSingleVideoDownloadUrl($url) {
    if (isFacebookUrl($url)) {
        return normalizeFacebookDownloadUrl($url);
    }

    if (!isYouTubeUrl($url)) return $url;

    $videoId = youtubeVideoId($url);

    if ($videoId !== '') {
        return 'https://www.youtube.com/watch?v=' . rawurlencode($videoId);
    }

    return $url;
}

function psQuote($value) {
    return "'" . str_replace("'", "''", $value) . "'";
}

function cmdQuote($value) {
    return '"' . str_replace('"', '""', $value) . '"';
}

function getJobFiles($jobId, $downloadJobsPath) {
    return [
        'log'  => $downloadJobsPath . DIRECTORY_SEPARATOR . $jobId . '.log',
        'done' => $downloadJobsPath . DIRECTORY_SEPARATOR . $jobId . '.done',
        'pid'  => $downloadJobsPath . DIRECTORY_SEPARATOR . $jobId . '.pid',
        'cancel' => $downloadJobsPath . DIRECTORY_SEPARATOR . $jobId . '.cancel',
        'ps1'  => $downloadJobsPath . DIRECTORY_SEPARATOR . $jobId . '.ps1',
        'bat'  => $downloadJobsPath . DIRECTORY_SEPARATOR . $jobId . '.bat'
    ];
}

function readJobPid($pidPath) {
    if (!is_file($pidPath)) return 0;

    $raw = @file_get_contents($pidPath);
    if (!is_string($raw)) return 0;

    if (preg_match('/PID\s*=\s*(\d+)/', $raw, $matches)) {
        return intval($matches[1]);
    }

    return 0;
}

function stopWindowsProcessTree($pid) {
    $pid = intval($pid);

    if ($pid <= 0 || stripos(PHP_OS_FAMILY, 'Windows') === false || !function_exists('shell_exec')) {
        return false;
    }

    $ps = "";
    $ps .= "\$ErrorActionPreference = 'SilentlyContinue'\r\n";
    $ps .= "\$root = " . $pid . "\r\n";
    $ps .= <<<'PS'
function Stop-Tree([int]$id) {
  Get-CimInstance Win32_Process -Filter "ParentProcessId=$id" | ForEach-Object {
    Stop-Tree ([int]$_.ProcessId)
  }

  Stop-Process -Id $id -Force -ErrorAction SilentlyContinue
}

Stop-Tree $root
PS;

    $tmp = tempnam(sys_get_temp_dir(), 'cm_cancel_');
    if ($tmp === false) return false;

    $ps1 = $tmp . '.ps1';
    @unlink($tmp);

    if (@file_put_contents($ps1, "\xEF\xBB\xBF" . $ps) === false) {
        return false;
    }

    @shell_exec('powershell.exe -NoProfile -ExecutionPolicy Bypass -File ' . cmdQuote($ps1) . ' 2>&1');
    @unlink($ps1);

    return true;
}

function tailText($text, $maxLines = 12) {
    $lines = preg_split('/\R/', trim($text));

    if (!$lines) return '';

    $lines = array_values(array_filter($lines, fn($line) => trim($line) !== ''));

    return implode("\n", array_slice($lines, -$maxLines));
}

function publicDownloadLogTail($text, $maxLines = 12) {
    if (preg_match('/ERROR: Facebook no entrego[^\r\n]*/', $text, $facebookMatch)) {
        return trim($facebookMatch[0]);
    }

    $tail = tailText($text, $maxLines);

    if ($tail === '') return '';

    $tail = preg_replace('/\s*\|\s*Velocidad:\s*[^|\r\n]*/u', '', $tail);
    $tail = preg_replace('/\s*\|\s*ETA:\s*[^|\r\n]*/u', '', $tail);

    return trim($tail);
}

function normalizeJobLogText($text) {
    if (!is_string($text) || $text === '') return '';

    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);

    if (strpos($text, "\0") !== false) {
        $text = str_replace("\0", '', $text);
    }

    return preg_replace('/\x1B\[[0-9;]*m/', '', $text);
}

function findVideoFileRecursive($path, $targetFile) {
    if (!is_dir($path)) return null;

    $items = scandir($path);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $path . '\\' . $item;

        if (is_file($fullPath) && $item === $targetFile && isVideoFile($item)) {
            return $fullPath;
        }

        if (is_dir($fullPath)) {
            $found = findVideoFileRecursive($fullPath, $targetFile);

            if ($found) return $found;
        }
    }

    return null;
}


function safePlayerSid($value) {
    return is_string($value) && preg_match('/^[a-zA-Z0-9_-]{4,80}$/', $value);
}

function resolveChromePath() {
    $candidates = [
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe'
    ];

    $localAppData = getenv('LOCALAPPDATA');
    if ($localAppData) {
        $candidates[] = $localAppData . '\Google\Chrome\Application\chrome.exe';
    }

    foreach ($candidates as $path) {
        if ($path && is_file($path)) return $path;
    }

    return null;
}

function normalizeWindowsScreensPayload($raw) {
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    $decoded = json_decode(trim($raw), true);
    if (!is_array($decoded)) return [];

    if (isset($decoded['value'])) {
        $decoded = [$decoded];
    }

    $screens = [];

    foreach ($decoded as $item) {
        if (!is_array($item)) continue;

        $width = intval($item['width'] ?? 0);
        $height = intval($item['height'] ?? 0);

        if ($width < 300 || $height < 200) continue;

        $screens[] = [
            'value' => (string) ($item['value'] ?? ('screen_' . (count($screens) + 1))),
            'label' => (string) ($item['label'] ?? ('Monitor ' . (count($screens) + 1) . " ({$width}x{$height})")),
            'left' => intval($item['left'] ?? 0),
            'top' => intval($item['top'] ?? 0),
            'width' => $width,
            'height' => $height,
            'primary' => !empty($item['primary']),
            'source' => 'windows',
            'deviceName' => (string) ($item['deviceName'] ?? '')
        ];
    }

    return $screens;
}

function detectWindowsScreens() {
    if (stripos(PHP_OS_FAMILY, 'Windows') === false || !function_exists('shell_exec')) {
        return [];
    }
    $ps = <<<'PS'
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms

$screens = @([System.Windows.Forms.Screen]::AllScreens)

if (-not $screens -or $screens.Count -lt 1) {
  @() | ConvertTo-Json -Compress
  exit 0
}

$primary = @($screens | Where-Object { $_.Primary } | Select-Object -First 1)[0]
if (-not $primary) { $primary = $screens[0] }

$ordered = @()
$ordered += $primary
$ordered += @($screens | Where-Object { -not $_.Primary } | Sort-Object { $_.Bounds.X }, { $_.Bounds.Y })

$idx = 0
$items = foreach ($screen in $ordered) {
  $idx++
  $bounds = $screen.Bounds
  $work = $screen.WorkingArea
  $primaryBounds = $primary.Bounds
  $primaryCenterX = $primaryBounds.X + ($primaryBounds.Width / 2)

  if ($idx -eq 1) {
    $direction = 'Principal'
  } elseif ($bounds.X -ge $primaryCenterX) {
    $direction = 'Derecha'
  } elseif (($bounds.X + $bounds.Width) -le $primaryCenterX) {
    $direction = 'Izquierda'
  } elseif ($bounds.Y -lt $primaryBounds.Y) {
    $direction = 'Arriba'
  } elseif ($bounds.Y -gt $primaryBounds.Y) {
    $direction = 'Abajo'
  } else {
    $direction = 'Secundario'
  }

  $value = if ($idx -eq 1) { 'main' } elseif ($idx -eq 2) { 'right' } else { 'screen_' + $idx }
  $label = if ($idx -eq 1) {
    'Monitor 1 - Principal (' + $bounds.Width + 'x' + $bounds.Height + ')'
  } else {
    'Monitor ' + $idx + ' - ' + $direction + ' (' + $bounds.Width + 'x' + $bounds.Height + ')'
  }

  [pscustomobject]@{
    value = $value
    label = $label
    left = [int]$bounds.X
    top = [int]$bounds.Y
    width = [int]$bounds.Width
    height = [int]$bounds.Height
    workLeft = [int]$work.X
    workTop = [int]$work.Y
    workWidth = [int]$work.Width
    workHeight = [int]$work.Height
    primary = [bool]$screen.Primary
    deviceName = [string]$screen.DeviceName
    source = 'windows'
  }
}

@($items) | ConvertTo-Json -Compress -Depth 4
PS;

    $activeScreens = detectWindowsScreensFromActiveSession($ps);
    if ($activeScreens) {
        return $activeScreens;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'cm_screens_');
    if ($tmp === false) return [];

    $ps1 = $tmp . '.ps1';
    @unlink($tmp);

    if (file_put_contents($ps1, "\xEF\xBB\xBF" . $ps) === false) {
        return [];
    }

    $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File ' . cmdQuote($ps1);
    $raw = @shell_exec($cmd);
    @unlink($ps1);

    return normalizeWindowsScreensPayload($raw);
}

function detectWindowsScreensFromActiveSession($screenScript) {
    $jobsPath = cm_data_dir() . DIRECTORY_SEPARATOR . 'player_jobs';
    $sid = 'cm_screens_' . bin2hex(random_bytes(5));

    if (!is_dir($jobsPath)) {
        @mkdir($jobsPath, 0777, true);
    }

    $outputFile = $jobsPath . DIRECTORY_SEPARATOR . $sid . '.json';
    $ps1 = $jobsPath . DIRECTORY_SEPARATOR . $sid . '.ps1';
    $screenScript = str_replace(
        "@() | ConvertTo-Json -Compress\r\n  exit 0",
        "Set-Content -LiteralPath \$outputFile -Value '[]' -Encoding UTF8\r\n  exit 0",
        $screenScript
    );
    $screenScript = str_replace(
        '@($items) | ConvertTo-Json -Compress -Depth 4',
        "\$screenJson = @(\$items) | ConvertTo-Json -Compress -Depth 4\r\nSet-Content -LiteralPath \$outputFile -Value \$screenJson -Encoding UTF8",
        $screenScript
    );

    $ps = "\$outputFile = " . psQuote($outputFile) . "\r\n" . $screenScript;

    if (@file_put_contents($ps1, "\xEF\xBB\xBF" . $ps) === false) {
        return [];
    }

    if (!runPowerShellInActiveSession($sid, $ps1, $jobsPath)) {
        @unlink($ps1);
        return [];
    }

    $raw = '';
    $deadline = microtime(true) + 3.0;

    while (microtime(true) < $deadline) {
        if (is_file($outputFile)) {
            $raw = (string) @file_get_contents($outputFile);
            break;
        }

        usleep(100000);
    }

    @unlink($ps1);
    @unlink($outputFile);
    $launcherFiles = getNativePlayerFiles($sid, $jobsPath);
    @unlink($launcherFiles['launcher']);
    @unlink($launcherFiles['launcherLog']);

    return normalizeWindowsScreensPayload($raw);
}

function getControlMusicaBaseUrl() {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/CONTROL_MUSICA/api.php'));
    $dir = rtrim($dir, '/');

    if ($dir === '' || $dir === '.') {
        $dir = '';
    }

    return $scheme . '://' . $host . $dir . '/';
}

function runDetachedCommand($cmd) {
    if (!function_exists('popen')) {
        return false;
    }

    $h = @popen($cmd, 'r');
    if (!$h) return false;
    @pclose($h);
    return true;
}

function writeActiveSessionLauncherPowerShell($sid, $targetPs1, $jobsPath) {
    if (!is_dir($jobsPath)) {
        mkdir($jobsPath, 0777, true);
    }

    $files = getNativePlayerFiles($sid, $jobsPath);
    $launcherPs1 = $files['launcher'];
    $launcherLog = $files['launcherLog'];
    $workDir = cm_data_dir();
    $powershellExe = getenv('SystemRoot')
        ? rtrim(getenv('SystemRoot'), '\\/') . '\\System32\\WindowsPowerShell\\v1.0\\powershell.exe'
        : 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';

    $ps = "";
    $ps .= "\$ErrorActionPreference = 'Stop'\r\n";
    $ps .= "\$launcherLog = " . psQuote($launcherLog) . "\r\n";
    $ps .= "\$targetExe = " . psQuote($powershellExe) . "\r\n";
    $ps .= "\$targetArgs = " . psQuote('-NoProfile -WindowStyle Hidden -STA -ExecutionPolicy Bypass -File "' . $targetPs1 . '"') . "\r\n";
    $ps .= "\$workDir = " . psQuote($workDir) . "\r\n";
    $ps .= <<<'PS'
function Write-CMActiveLaunchLog([string]$message) {
  try { (Get-Date -Format 'yyyy-MM-dd HH:mm:ss') + ' ' + $message | Out-File -FilePath $launcherLog -Append -Encoding UTF8 } catch {}
}

try {
  Add-Type @"
using System;
using System.ComponentModel;
using System.Runtime.InteropServices;

public class CMActiveSessionLauncher {
  [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
  public struct STARTUPINFO {
    public Int32 cb;
    public string lpReserved;
    public string lpDesktop;
    public string lpTitle;
    public Int32 dwX;
    public Int32 dwY;
    public Int32 dwXSize;
    public Int32 dwYSize;
    public Int32 dwXCountChars;
    public Int32 dwYCountChars;
    public Int32 dwFillAttribute;
    public Int32 dwFlags;
    public Int16 wShowWindow;
    public Int16 cbReserved2;
    public IntPtr lpReserved2;
    public IntPtr hStdInput;
    public IntPtr hStdOutput;
    public IntPtr hStdError;
  }

  [StructLayout(LayoutKind.Sequential)]
  public struct PROCESS_INFORMATION {
    public IntPtr hProcess;
    public IntPtr hThread;
    public Int32 dwProcessId;
    public Int32 dwThreadId;
  }

  [DllImport("kernel32.dll")]
  public static extern uint WTSGetActiveConsoleSessionId();

  [DllImport("wtsapi32.dll", SetLastError = true)]
  public static extern bool WTSQueryUserToken(uint SessionId, out IntPtr phToken);

  [DllImport("advapi32.dll", SetLastError = true)]
  public static extern bool DuplicateTokenEx(IntPtr hExistingToken, uint dwDesiredAccess, IntPtr lpTokenAttributes, int ImpersonationLevel, int TokenType, out IntPtr phNewToken);

  [DllImport("userenv.dll", SetLastError = true)]
  public static extern bool CreateEnvironmentBlock(out IntPtr lpEnvironment, IntPtr hToken, bool bInherit);

  [DllImport("userenv.dll", SetLastError = true)]
  public static extern bool DestroyEnvironmentBlock(IntPtr lpEnvironment);

  [DllImport("advapi32.dll", SetLastError = true, CharSet = CharSet.Unicode)]
  public static extern bool CreateProcessAsUser(IntPtr hToken, string lpApplicationName, string lpCommandLine, IntPtr lpProcessAttributes, IntPtr lpThreadAttributes, bool bInheritHandles, UInt32 dwCreationFlags, IntPtr lpEnvironment, string lpCurrentDirectory, ref STARTUPINFO lpStartupInfo, out PROCESS_INFORMATION lpProcessInformation);

  [DllImport("kernel32.dll", SetLastError = true)]
  public static extern bool CloseHandle(IntPtr hObject);

  private const UInt32 MAXIMUM_ALLOWED = 0x02000000;
  private const UInt32 CREATE_UNICODE_ENVIRONMENT = 0x00000400;
  private const UInt32 CREATE_NO_WINDOW = 0x08000000;

  public static int Launch(string application, string arguments, string workDir) {
    uint sessionId = WTSGetActiveConsoleSessionId();

    if (sessionId == 0xFFFFFFFF) {
      throw new Exception("No hay sesion de consola activa.");
    }

    IntPtr userToken = IntPtr.Zero;
    IntPtr primaryToken = IntPtr.Zero;
    IntPtr environment = IntPtr.Zero;
    PROCESS_INFORMATION pi = new PROCESS_INFORMATION();

    try {
      if (!WTSQueryUserToken(sessionId, out userToken)) {
        throw new Win32Exception(Marshal.GetLastWin32Error(), "WTSQueryUserToken fallo para la sesion " + sessionId);
      }

      if (!DuplicateTokenEx(userToken, MAXIMUM_ALLOWED, IntPtr.Zero, 2, 1, out primaryToken)) {
        throw new Win32Exception(Marshal.GetLastWin32Error(), "DuplicateTokenEx fallo");
      }

      if (!CreateEnvironmentBlock(out environment, primaryToken, false)) {
        environment = IntPtr.Zero;
      }

      STARTUPINFO si = new STARTUPINFO();
      si.cb = Marshal.SizeOf(typeof(STARTUPINFO));
      si.lpDesktop = "winsta0\\default";
      si.dwFlags = 1;
      si.wShowWindow = 0;

      string commandLine = "\"" + application + "\" " + arguments;
      UInt32 flags = CREATE_UNICODE_ENVIRONMENT | CREATE_NO_WINDOW;

      if (!CreateProcessAsUser(primaryToken, application, commandLine, IntPtr.Zero, IntPtr.Zero, false, flags, environment, workDir, ref si, out pi)) {
        throw new Win32Exception(Marshal.GetLastWin32Error(), "CreateProcessAsUser fallo");
      }

      return pi.dwProcessId;
    } finally {
      if (pi.hThread != IntPtr.Zero) CloseHandle(pi.hThread);
      if (pi.hProcess != IntPtr.Zero) CloseHandle(pi.hProcess);
      if (environment != IntPtr.Zero) DestroyEnvironmentBlock(environment);
      if (primaryToken != IntPtr.Zero) CloseHandle(primaryToken);
      if (userToken != IntPtr.Zero) CloseHandle(userToken);
    }
  }
}
"@

  $launchedPid = [CMActiveSessionLauncher]::Launch($targetExe, $targetArgs, $workDir)
  Write-CMActiveLaunchLog ('OK interactive pid=' + $launchedPid)
  Write-Output ('OK interactive pid=' + $launchedPid)
  exit 0
} catch {
  Write-CMActiveLaunchLog ('ERROR interactive launch: ' + $_.Exception.Message)
  Write-Output ('ERROR interactive launch: ' + $_.Exception.Message)
  exit 1
}
PS;

    if (@file_put_contents($launcherPs1, "\xEF\xBB\xBF" . $ps) === false) {
        return '';
    }

    return $launcherPs1;
}

// El "lanzador de sesion activa" (CreateProcessAsUser via WTSQueryUserToken)
// SOLO funciona cuando PHP corre como SYSTEM (Apache instalado como servicio).
// Cuando XAMPP se inicia como el propio usuario (caso comun), ese lanzador
// SIEMPRE falla, pero hoy igual se intenta en cada reproduccion pagando un
// arranque de PowerShell en frio + compilacion .NET inutiles. Detectamos el
// caso una sola vez (cacheado) para saltarlo y caer directo al lanzamiento
// normal, lo que acelera mucho el primer video.
function shouldUseActiveSessionLauncher() {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    if (stripos(PHP_OS_FAMILY, 'Windows') === false || !function_exists('shell_exec')) {
        return $cached = false;
    }

    $cacheFile = cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'native_launch_mode.json';
    $data = @json_decode((string) @file_get_contents($cacheFile), true);

    if (is_array($data) && isset($data['useLauncher'])) {
        return $cached = (bool) $data['useLauncher'];
    }

    // whoami /user imprime el SID. El de SYSTEM es S-1-5-18 (no se localiza).
    $who = @shell_exec('whoami /user 2>&1');

    if (!is_string($who) || trim($who) === '') {
        // No se pudo determinar: conservar el comportamiento actual (intentarlo)
        // sin cachear, para reintentar la deteccion en la proxima peticion.
        return $cached = true;
    }

    $isSystem = stripos($who, 'S-1-5-18') !== false;
    @file_put_contents($cacheFile, json_encode(['useLauncher' => $isSystem]), LOCK_EX);

    return $cached = $isSystem;
}

function runPowerShellInActiveSession($sid, $targetPs1, $jobsPath) {
    if (stripos(PHP_OS_FAMILY, 'Windows') === false || !function_exists('shell_exec')) {
        return false;
    }

    if (!shouldUseActiveSessionLauncher()) {
        return false;
    }

    $launcherPs1 = writeActiveSessionLauncherPowerShell($sid, $targetPs1, $jobsPath);

    if ($launcherPs1 === '') {
        return false;
    }

    $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File ' . cmdQuote($launcherPs1) . ' 2>&1';
    $output = @shell_exec($cmd);
    $files = getNativePlayerFiles($sid, $jobsPath);

    if (is_string($output) && trim($output) !== '') {
        @file_put_contents($files['launcherLog'], trim($output) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    return is_string($output) && stripos($output, 'OK interactive pid=') !== false;
}

function writePlayerPowerShell($sid, $mode, $left, $top, $width, $height, $jobsPath) {
    if (!is_dir($jobsPath)) {
        mkdir($jobsPath, 0777, true);
    }

    $safeMode = preg_replace('/[^a-z_]/', '', $mode);
    $ps1 = $jobsPath . DIRECTORY_SEPARATOR . $sid . '_' . $safeMode . '.ps1';
    $log = $jobsPath . DIRECTORY_SEPARATOR . $sid . '_' . $safeMode . '.log';

    $ps = "";
    $ps .= "\$ErrorActionPreference = 'SilentlyContinue'\r\n";
    $ps .= "\$targetTitle = " . psQuote('CONTROL_MUSICA_PLAYER_' . $sid) . "\r\n";
    $ps .= "\$mode = " . psQuote($mode) . "\r\n";
    $ps .= "\$left = " . intval($left) . "\r\n";
    $ps .= "\$top = " . intval($top) . "\r\n";
    $ps .= "\$width = " . intval($width) . "\r\n";
    $ps .= "\$height = " . intval($height) . "\r\n";
    $ps .= "\$log = " . psQuote($log) . "\r\n";
    $ps .= <<<'PSADD'
Add-Type @"
using System;
using System.Text;
using System.Runtime.InteropServices;

public class CMWinApi {
    public delegate bool EnumWindowsProc(IntPtr hWnd, IntPtr lParam);

    [DllImport("user32.dll")]
    public static extern bool EnumWindows(EnumWindowsProc lpEnumFunc, IntPtr lParam);

    [DllImport("user32.dll")]
    public static extern bool IsWindowVisible(IntPtr hWnd);

    [DllImport("user32.dll", CharSet = CharSet.Unicode)]
    public static extern int GetWindowText(IntPtr hWnd, StringBuilder lpString, int nMaxCount);

    [DllImport("user32.dll", EntryPoint="GetWindowLongPtr")]
    public static extern IntPtr GetWindowLongPtr(IntPtr hWnd, int nIndex);

    [DllImport("user32.dll", EntryPoint="SetWindowLongPtr")]
    public static extern IntPtr SetWindowLongPtr(IntPtr hWnd, int nIndex, IntPtr dwNewLong);

    [DllImport("user32.dll")]
    public static extern bool SetWindowPos(IntPtr hWnd, IntPtr hWndInsertAfter, int X, int Y, int cx, int cy, uint uFlags);

    [DllImport("user32.dll")]
    public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);

    [DllImport("user32.dll")]
    public static extern bool SetForegroundWindow(IntPtr hWnd);

    [DllImport("user32.dll")]
    public static extern bool PostMessage(IntPtr hWnd, uint Msg, IntPtr wParam, IntPtr lParam);

    [DllImport("user32.dll")]
    public static extern bool SetCursorPos(int X, int Y);

    [DllImport("user32.dll")]
    public static extern void mouse_event(uint dwFlags, uint dx, uint dy, uint dwData, UIntPtr dwExtraInfo);
}
"@
PSADD;
    $ps .= <<<'PSMAIN'
function Write-CMLog([string]$message) {
  try { (Get-Date -Format 'yyyy-MM-dd HH:mm:ss') + ' ' + $message | Out-File -FilePath $log -Append -Encoding UTF8 } catch {}
}

function Find-CMPlayerWindow {
  $script:found = [IntPtr]::Zero

  [CMWinApi]::EnumWindows({
    param([IntPtr]$hWnd, [IntPtr]$lParam)

    if (-not [CMWinApi]::IsWindowVisible($hWnd)) { return $true }

    $sb = New-Object System.Text.StringBuilder 512
    [void][CMWinApi]::GetWindowText($hWnd, $sb, $sb.Capacity)
    $title = $sb.ToString()

    if ($title -like "*$targetTitle*") {
      $script:found = $hWnd
      return $false
    }

    return $true
  }, [IntPtr]::Zero) | Out-Null

  return $script:found
}

$hwnd = [IntPtr]::Zero

for ($i = 0; $i -lt 200; $i++) {
  $hwnd = Find-CMPlayerWindow
  if ($hwnd -ne [IntPtr]::Zero) { break }
  Start-Sleep -Milliseconds 50
}

if ($hwnd -eq [IntPtr]::Zero) {
  Write-CMLog "ERROR: ventana no encontrada: $targetTitle"
  exit 2
}

Write-CMLog "Ventana encontrada. Mode=$mode hwnd=$hwnd"

if ($mode -eq 'close') {
  [void][CMWinApi]::PostMessage($hwnd, 0x0010, [IntPtr]::Zero, [IntPtr]::Zero)
  exit 0
}

[void][CMWinApi]::ShowWindow($hwnd, 9)
[void][CMWinApi]::SetForegroundWindow($hwnd)
Start-Sleep -Milliseconds 40

if ($mode -eq 'activate') {
  $cx = [int]($left + ($width / 2))
  $cy = [int]($top + ($height / 2))
  [void][CMWinApi]::SetCursorPos($cx, $cy)
  Start-Sleep -Milliseconds 70
  [CMWinApi]::mouse_event(0x0002, 0, 0, 0, [UIntPtr]::Zero)
  Start-Sleep -Milliseconds 50
  [CMWinApi]::mouse_event(0x0004, 0, 0, 0, [UIntPtr]::Zero)
  Write-CMLog "Click de activación en $cx,$cy"
  exit 0
}

$GWL_STYLE = -16
$WS_CAPTION = 0x00C00000
$WS_THICKFRAME = 0x00040000
$WS_MINIMIZEBOX = 0x00020000
$WS_MAXIMIZEBOX = 0x00010000
$WS_SYSMENU = 0x00080000
$mask = [Int64]($WS_CAPTION -bor $WS_THICKFRAME -bor $WS_MINIMIZEBOX -bor $WS_MAXIMIZEBOX -bor $WS_SYSMENU)

$SWP_FRAMECHANGED = 0x0020
$SWP_SHOWWINDOW = 0x0040
$HWND_TOPMOST = [IntPtr]::new(-1)
$flags = [uint32]($SWP_FRAMECHANGED -bor $SWP_SHOWWINDOW)

for ($j = 0; $j -lt 10; $j++) {
  $style = [CMWinApi]::GetWindowLongPtr($hwnd, $GWL_STYLE).ToInt64()
  $newStyle = $style -band (-bnot $mask)
  [void][CMWinApi]::SetWindowLongPtr($hwnd, $GWL_STYLE, [IntPtr]::new($newStyle))
  [void][CMWinApi]::SetWindowPos($hwnd, $HWND_TOPMOST, $left, $top, $width, $height, $flags)
  Start-Sleep -Milliseconds 90
}

Write-CMLog "Posicionado: x=$left y=$top w=$width h=$height"
exit 0
PSMAIN;

    file_put_contents($ps1, $ps);
    return $ps1;
}

function runPlayerPowerShell($sid, $mode, $left, $top, $width, $height, $jobsPath) {
    $ps1 = writePlayerPowerShell($sid, $mode, $left, $top, $width, $height, $jobsPath);
    $cmd = 'cmd /c start "" /min powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ' . cmdQuote($ps1);
    return runDetachedCommand($cmd);
}

function getNativePlayerFiles($sid, $jobsPath) {
    return [
        'ps1' => $jobsPath . DIRECTORY_SEPARATOR . $sid . '_native.ps1',
        'launcher' => $jobsPath . DIRECTORY_SEPARATOR . $sid . '_native.launcher.ps1',
        'launcherLog' => $jobsPath . DIRECTORY_SEPARATOR . $sid . '_native.launcher.log',
        'pid' => $jobsPath . DIRECTORY_SEPARATOR . $sid . '_native.pid',
        'log' => $jobsPath . DIRECTORY_SEPARATOR . $sid . '_native.log',
        'state' => $jobsPath . DIRECTORY_SEPARATOR . $sid . '_native.state.json',
        'command' => $jobsPath . DIRECTORY_SEPARATOR . $sid . '_native.command.json'
    ];
}

function writeNativePlayerPowerShell($sid, $filePath, $mediaType, $left, $top, $width, $height, $jobsPath) {
    if (!is_dir($jobsPath)) {
        mkdir($jobsPath, 0777, true);
    }

    $files = getNativePlayerFiles($sid, $jobsPath);

    $ps = "";
    $ps .= "\$ErrorActionPreference = 'Stop'\r\n";
    $ps .= "\$OutputEncoding = [Console]::OutputEncoding = [System.Text.UTF8Encoding]::new()\r\n";
    $ps .= "\$sid = " . psQuote($sid) . "\r\n";
    $ps .= "\$filePath = " . psQuote($filePath) . "\r\n";
    $ps .= "\$mediaType = " . psQuote(normalizeMediaType($mediaType)) . "\r\n";
    $ps .= "\$left = " . intval($left) . "\r\n";
    $ps .= "\$top = " . intval($top) . "\r\n";
    $ps .= "\$width = " . intval($width) . "\r\n";
    $ps .= "\$height = " . intval($height) . "\r\n";
    $ps .= "\$pidFile = " . psQuote($files['pid']) . "\r\n";
    $ps .= "\$log = " . psQuote($files['log']) . "\r\n";
    $ps .= "\$stateFile = " . psQuote($files['state']) . "\r\n";
    $ps .= "\$commandFile = " . psQuote($files['command']) . "\r\n";
    // Modo pre-calentado: sin archivo => host listo pero oculto, espera 'load'.
    $ps .= "\$idleStart = " . ($filePath === '' ? '$true' : '$false') . "\r\n";

    // Rutas de DLLs cacheadas para los componentes C#. Compilar (csc) en cada
    // arranque de PowerShell es lento en discos lentos/gama baja; al cachear el
    // resultado, los lanzamientos siguientes cargan la DLL ya compilada.
    $nativeTypeCacheDir = cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'cache';
    if (!is_dir($nativeTypeCacheDir)) {
        @mkdir($nativeTypeCacheDir, 0777, true);
    }
    $ps .= "\$cmDllNativeWin = " . psQuote($nativeTypeCacheDir . DIRECTORY_SEPARATOR . 'cm_nativewin_v1.dll') . "\r\n";
    $ps .= "\$cmDllWmpHost = " . psQuote($nativeTypeCacheDir . DIRECTORY_SEPARATOR . 'cm_wmphost_v1.dll') . "\r\n";
    $ps .= <<<'PS'
function Write-CMNativeLog([string]$message) {
  try { (Get-Date -Format 'yyyy-MM-dd HH:mm:ss') + ' ' + $message | Out-File -FilePath $log -Append -Encoding UTF8 } catch {}
}

function Write-CMNativeState([double]$current, [double]$duration, [bool]$paused, [bool]$ended, [string]$errorMessage, [bool]$closed = $false) {
  try {
    $state = [pscustomobject]@{
      sid = $sid
      mediaType = $mediaType
      current = [math]::Max(0, $current)
      duration = [math]::Max(0, $duration)
      paused = $paused
      ended = $ended
      closed = $closed
      nav = [string]$script:nav
      navId = [string]$script:navId
      error = $errorMessage
      ts = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
    }

    $tmpStateFile = $stateFile + '.tmp'
    $state | ConvertTo-Json -Compress | Set-Content -LiteralPath $tmpStateFile -Encoding UTF8
    Move-Item -LiteralPath $tmpStateFile -Destination $stateFile -Force
  } catch {}
}

function Get-CMNaturalDurationSeconds($media) {
  try {
    if ($media -and $media.NaturalDuration.HasTimeSpan) {
      return [double]$media.NaturalDuration.TimeSpan.TotalSeconds
    }
  } catch {}

  return 0
}

try {
  $cmSrcNativeWin = @'
using System;
using System.Runtime.InteropServices;

public class CMNativeConsole {
  [DllImport("kernel32.dll")]
  public static extern IntPtr GetConsoleWindow();

  [DllImport("user32.dll")]
  public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);
}

public class CMNativeWinApi {
  [DllImport("user32.dll")]
  public static extern bool SetWindowPos(IntPtr hWnd, IntPtr hWndInsertAfter, int X, int Y, int cx, int cy, UInt32 uFlags);

  [DllImport("user32.dll")]
  public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);

  [DllImport("user32.dll")]
  public static extern bool SetForegroundWindow(IntPtr hWnd);

  [DllImport("user32.dll")]
  public static extern short GetAsyncKeyState(int vKey);
}
'@
  if (Test-Path -LiteralPath $cmDllNativeWin) { try { Add-Type -Path $cmDllNativeWin -ErrorAction Stop } catch {} }
  if (-not ('CMNativeWinApi' -as [type])) {
    try { Add-Type -TypeDefinition $cmSrcNativeWin -OutputAssembly $cmDllNativeWin -ErrorAction Stop; Add-Type -Path $cmDllNativeWin -ErrorAction Stop } catch {}
  }
  if (-not ('CMNativeWinApi' -as [type])) { Add-Type -TypeDefinition $cmSrcNativeWin }

  try {
    $consoleHandle = [CMNativeConsole]::GetConsoleWindow()
    if ($consoleHandle -ne [IntPtr]::Zero) {
      [void][CMNativeConsole]::ShowWindow($consoleHandle, 0)
    }
  } catch {}

  Set-Content -LiteralPath $pidFile -Value ('PID=' + $PID) -Encoding UTF8
  Write-CMNativeLog ('Abriendo: ' + $filePath)
  $script:isPaused = $true
  $script:lastCommandId = ''
  $script:endedCloseDue = 0
  $script:hasPlayed = $false
  $script:hasMedia = $false
  $script:idleDeadlineMs = 0
  $script:lastStateWriteMs = 0
  $script:unmuteOnPlay = $false
  $script:unmuteSetMs = 0
  $script:nav = ''
  $script:navId = ''
  $script:lastNavAt = 0
  $script:leftWasDown = $false
  $script:rightWasDown = $false
  Write-CMNativeState 0 0 $true $false ''

  if ($mediaType -eq 'video') {
    Add-Type -AssemblyName System.Windows.Forms
    Add-Type -AssemblyName System.Drawing
    $cmSrcWmpHost = @'
using System;
using System.Windows.Forms;

public class CMWmpHost : AxHost {
  public CMWmpHost() : base("6BF52A52-394A-11d3-B153-00C04F79FAA6") {}
  public object OcxObject { get { return this.GetOcx(); } }
}
'@
    if (Test-Path -LiteralPath $cmDllWmpHost) { try { Add-Type -Path $cmDllWmpHost -ErrorAction Stop } catch {} }
    if (-not ('CMWmpHost' -as [type])) {
      try { Add-Type -TypeDefinition $cmSrcWmpHost -OutputAssembly $cmDllWmpHost -ReferencedAssemblies System.Windows.Forms -ErrorAction Stop; Add-Type -Path $cmDllWmpHost -ErrorAction Stop } catch {}
    }
    if (-not ('CMWmpHost' -as [type])) { Add-Type -TypeDefinition $cmSrcWmpHost -ReferencedAssemblies System.Windows.Forms }

    [System.Windows.Forms.Application]::EnableVisualStyles()

    $form = New-Object System.Windows.Forms.Form
    $form.Text = 'CONTROL_MUSICA_NATIVE_' + $sid
    $form.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::None
    $form.StartPosition = [System.Windows.Forms.FormStartPosition]::Manual
    $form.BackColor = [System.Drawing.Color]::Black
    $form.ShowInTaskbar = $false
    $form.TopMost = $true
    $form.KeyPreview = $true
    $form.Cursor = [System.Windows.Forms.Cursors]::None
    $form.SetBounds($left, $top, $width, $height)

    if ($idleStart) {
      # Pre-calentado: ventana invisible y fuera de pantalla hasta el primer
      # 'load'. Si nadie la usa en 30 min, se cierra sola.
      $form.Opacity = 0
      $form.SetBounds(-32000, -32000, 320, 180)
      $script:idleDeadlineMs = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds() + 1800000
    }

    $wmpHost = New-Object CMWmpHost
    $wmpHost.Dock = [System.Windows.Forms.DockStyle]::Fill
    $wmpHost.BackColor = [System.Drawing.Color]::Black
    $form.Controls.Add($wmpHost)

    $script:wmp = $null

    function Show-CMWmpWindow([string]$reason) {
      try {
        $scaleX = 1.0
        $scaleY = 1.0
        $graphics = [System.Drawing.Graphics]::FromHwnd($form.Handle)
        if ($graphics) {
          $scaleX = [double]$graphics.DpiX / 96.0
          $scaleY = [double]$graphics.DpiY / 96.0
          $graphics.Dispose()
        }

        $pixelLeft = [int][math]::Round($left * $scaleX)
        $pixelTop = [int][math]::Round($top * $scaleY)
        $pixelWidth = [int][math]::Round($width * $scaleX)
        $pixelHeight = [int][math]::Round($height * $scaleY)
        $HWND_TOPMOST = [IntPtr]::new(-1)
        $SWP_SHOWWINDOW = 0x0040
        $SWP_FRAMECHANGED = 0x0020
        $flags = [uint32]($SWP_SHOWWINDOW -bor $SWP_FRAMECHANGED)

        [void][CMNativeWinApi]::ShowWindow($form.Handle, 5)
        [void][CMNativeWinApi]::SetWindowPos($form.Handle, $HWND_TOPMOST, $pixelLeft, $pixelTop, $pixelWidth, $pixelHeight, $flags)
        [void][CMNativeWinApi]::SetForegroundWindow($form.Handle)
        Write-CMNativeLog ('WMP visible [' + $reason + ']: hwnd=' + $form.Handle + ' dip=' + $left + ',' + $top + ',' + $width + 'x' + $height + ' px=' + $pixelLeft + ',' + $pixelTop + ',' + $pixelWidth + 'x' + $pixelHeight + ' scale=' + $scaleX + 'x' + $scaleY)
      } catch {
        Write-CMNativeLog ('ERROR WMP ShowWindow [' + $reason + ']: ' + $_.Exception.Message)
      }
    }

    function Get-CMWmpDuration {
      try {
        if ($script:wmp -and $script:wmp.currentMedia) {
          return [double]$script:wmp.currentMedia.duration
        }
      } catch {}
      return 0
    }

    function Get-CMWmpCurrent {
      try {
        if ($script:wmp) {
          return [double]$script:wmp.controls.currentPosition
        }
      } catch {}
      return 0
    }

    function Start-CMWmpFile([string]$path) {
      if (-not $script:wmp) { return }
      $script:isPaused = $true
      $script:endedCloseDue = 0
      $script:hasPlayed = $false
      Write-CMNativeState 0 0 $true $false ''
      # Silencio durante la transición: aunque se haga stop(), WMP puede dejar
      # sonar un instante del medio anterior. Se silencia aquí y se reactiva
      # el audio en el tick cuando el medio NUEVO ya está reproduciendo.
      try { $script:wmp.settings.mute = $true } catch {}
      $script:unmuteOnPlay = $true
      $script:unmuteSetMs = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
      try { $script:wmp.controls.stop() } catch {}
      $script:wmp.URL = $path
      $script:wmp.controls.currentPosition = 0
      $script:wmp.controls.play()
      $script:isPaused = $false
      Write-CMNativeLog ('WMP Load: ' + $path)
    }

    $form.Add_Shown({
      try {
        $script:wmp = $wmpHost.OcxObject
        $script:wmp.uiMode = 'none'
        $script:wmp.enableContextMenu = $false
        $script:wmp.stretchToFit = $true
        $script:wmp.settings.autoStart = $true
        $script:wmp.settings.volume = 100
        $script:wmp.settings.mute = $false
        if (-not $idleStart) {
          $script:hasMedia = $true
          Start-CMWmpFile $filePath
          Show-CMWmpWindow 'shown-video'
        } else {
          Write-CMNativeLog 'Host WMP precalentado (idle): listo, esperando load'
        }
      } catch {
        Write-CMNativeLog ('ERROR WMP Shown: ' + $_.Exception.Message)
        Write-CMNativeState 0 0 $true $false $_.Exception.Message
        $form.Close()
      }
    })

    $form.Add_KeyDown({
      param($sender, $args)
      if ($args.KeyCode -eq [System.Windows.Forms.Keys]::Escape) {
        $form.Close()
      }
    })

    $timer = New-Object System.Windows.Forms.Timer
    # 120 ms: los comandos (load/seek/pause) se recogen casi al instante sin
    # costo apreciable de CPU.
    $timer.Interval = 120
    $timer.Add_Tick({
      try {
        # ESC solo cierra cuando hay medio visible (no mata al host idle).
        # 0x8001: bit alto = tecla abajo ahora; bit bajo = se pulsó desde el
        # último chequeo (atrapa pulsaciones breves entre ticks).
        if ($script:hasMedia -and (([CMNativeWinApi]::GetAsyncKeyState(0x1B) -band 0x8001) -ne 0)) {
          $form.Close()
          return
        }

        if (-not $script:hasMedia -and $script:idleDeadlineMs -gt 0 -and
            [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds() -ge $script:idleDeadlineMs) {
          Write-CMNativeLog 'Idle sin uso: cerrando host precalentado'
          $form.Close()
          return
        }

        if ($script:wmp -and (Test-Path -LiteralPath $commandFile)) {
          $rawCommand = Get-Content -LiteralPath $commandFile -Raw -Encoding UTF8 -ErrorAction SilentlyContinue
          if (-not [string]::IsNullOrWhiteSpace($rawCommand)) {
            $command = $rawCommand | ConvertFrom-Json -ErrorAction SilentlyContinue
            if ($command -and $command.id -and $command.id -ne $script:lastCommandId) {
              $script:lastCommandId = [string]$command.id
              $commandType = [string]$command.type

              if ($commandType -eq 'play') {
                $script:endedCloseDue = 0
                $script:wmp.controls.play()
                $script:isPaused = $false
              } elseif ($commandType -eq 'pause') {
                $script:wmp.controls.pause()
                $script:isPaused = $true
              } elseif ($commandType -eq 'seek') {
                $seconds = [double]$command.time
                if ($seconds -lt 0) { $seconds = 0 }
                # Silenciar mientras WMP salta de posición: si no, se sigue
                # oyendo el punto anterior una fracción de segundo.
                try { $script:wmp.settings.mute = $true } catch {}
                $script:unmuteOnPlay = $true
                $script:unmuteSetMs = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
                $script:wmp.controls.currentPosition = $seconds
              } elseif ($commandType -eq 'load') {
                $nextPath = [string]$command.filePath
                $nextType = [string]$command.mediaType
                if ($nextType -eq 'video' -and -not [string]::IsNullOrWhiteSpace($nextPath) -and (Test-Path -LiteralPath $nextPath)) {
                  # Coordenadas frescas del monitor elegido (si vienen): el host
                  # se reposiciona en cada load, incluido el primer load tras
                  # el pre-calentado.
                  if ($command.PSObject.Properties['left'] -and $command.PSObject.Properties['width']) {
                    $script:left = [int]$command.left
                    $script:top = [int]$command.top
                    $script:width = [int]$command.width
                    $script:height = [int]$command.height
                  }
                  $script:hasMedia = $true
                  $script:idleDeadlineMs = 0
                  try { $form.Opacity = 1 } catch {}
                  Start-CMWmpFile $nextPath
                  Show-CMWmpWindow 'load-video'
                }
              } elseif ($commandType -eq 'close') {
                $form.Close()
                return
              }
            }
          }
        }

        if ($script:wmp) {
          $duration = Get-CMWmpDuration
          $current = Get-CMWmpCurrent

          if ($script:endedCloseDue -gt 0) {
            if ($duration -gt 0) { $current = $duration }
            Write-CMNativeState $current $duration $true $true ''
            if ([DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds() -ge $script:endedCloseDue) {
              $form.Close()
              [System.Windows.Forms.Application]::Exit()
            }
            return
          }

          $stateCode = 0
          try { $stateCode = [int]$script:wmp.playState } catch {}
          if ($stateCode -eq 3 -or $current -gt 0.2) {
            $script:hasPlayed = $true
          }

          # Reactivar audio cuando el medio nuevo ya reproduce (o tras 1.5s
          # como red de seguridad si quedó en pausa).
          if ($script:unmuteOnPlay) {
            $nowUnmuteMs = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
            if ($stateCode -eq 3 -or ($nowUnmuteMs - $script:unmuteSetMs) -ge 1500) {
              try { $script:wmp.settings.mute = $false } catch {}
              $script:unmuteOnPlay = $false
            }
          }

          $ended = $stateCode -eq 8 -or ($script:hasPlayed -and $stateCode -eq 1 -and $duration -gt 0)

          if ($stateCode -eq 3) {
            $script:isPaused = $false
          } elseif ($stateCode -eq 1 -or $stateCode -eq 2 -or $ended) {
            $script:isPaused = $true
          }

          if ($ended) {
            if ($duration -gt 0) { $current = $duration }
            if ($script:endedCloseDue -le 0) {
              $script:endedCloseDue = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds() + 2200
              Write-CMNativeLog 'WMP Ended'
            }

            Write-CMNativeState $current $duration $true $true ''

            if ([DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds() -ge $script:endedCloseDue) {
              $form.Close()
              [System.Windows.Forms.Application]::Exit()
              return
            }
          } else {
            # Escribir estado cada ~350 ms (no en cada tick): el tick corre en
            # el hilo de la UI y escribir a disco 8 veces/s producia
            # micro-trabones en la reproduccion.
            $nowStateMs = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
            if (($nowStateMs - $script:lastStateWriteMs) -ge 350) {
              $script:lastStateWriteMs = $nowStateMs
              Write-CMNativeState $current $duration $script:isPaused $false ''
            }
          }
        }
      } catch {
        Write-CMNativeLog ('ERROR WMP Tick: ' + $_.Exception.Message)
      }
    })

    $timer.Start()

    $form.Add_FormClosed({
      try { if ($timer) { $timer.Stop() } } catch {}
      try { if ($script:wmp) { $script:wmp.controls.stop() } } catch {}
      try {
        $closedEnded = $script:endedCloseDue -gt 0
        $closedDuration = Get-CMWmpDuration
        $closedCurrent = Get-CMWmpCurrent
        if ($closedEnded -and $closedDuration -gt 0) { $closedCurrent = $closedDuration }
        Write-CMNativeState $closedCurrent $closedDuration $true $closedEnded '' $true
      } catch {
        Write-CMNativeState 0 0 $true $false '' $true
      }
      Write-CMNativeLog 'Cerrado'
    })

    [System.Windows.Forms.Application]::Run($form)
    exit 0
  }

  Add-Type -AssemblyName PresentationCore
  Add-Type -AssemblyName PresentationFramework
  Add-Type -AssemblyName WindowsBase

  $media = $null
  $image = $null

  $window = New-Object System.Windows.Window
  $window.Title = 'CONTROL_MUSICA_NATIVE_' + $sid
  $window.WindowStyle = [System.Windows.WindowStyle]::None
  $window.ResizeMode = [System.Windows.ResizeMode]::NoResize
  $window.WindowStartupLocation = [System.Windows.WindowStartupLocation]::Manual
  $window.Left = $left
  $window.Top = $top
  $window.Width = $width
  $window.Height = $height
  $window.Topmost = $true
  $window.ShowInTaskbar = $false
  $window.Background = [System.Windows.Media.Brushes]::Black
  $window.Cursor = [System.Windows.Input.Cursors]::None
  $window.Focusable = $true
  $window.ShowActivated = $true

  function Show-CMNativeWindow([string]$reason) {
    try {
      $window.Left = $left
      $window.Top = $top
      $window.Width = $width
      $window.Height = $height

      $helper = New-Object System.Windows.Interop.WindowInteropHelper($window)
      $hwnd = $helper.Handle
      if ($hwnd -ne [IntPtr]::Zero) {
        $scaleX = 1.0
        $scaleY = 1.0
        $source = [System.Windows.PresentationSource]::FromVisual($window)
        if ($source -and $source.CompositionTarget) {
          $scaleX = [double]$source.CompositionTarget.TransformToDevice.M11
          $scaleY = [double]$source.CompositionTarget.TransformToDevice.M22
        }

        $pixelLeft = [int][math]::Round($left * $scaleX)
        $pixelTop = [int][math]::Round($top * $scaleY)
        $pixelWidth = [int][math]::Round($width * $scaleX)
        $pixelHeight = [int][math]::Round($height * $scaleY)
        $HWND_TOPMOST = [IntPtr]::new(-1)
        $SWP_SHOWWINDOW = 0x0040
        $SWP_FRAMECHANGED = 0x0020
        $flags = [uint32]($SWP_SHOWWINDOW -bor $SWP_FRAMECHANGED)
        [void][CMNativeWinApi]::ShowWindow($hwnd, 5)
        [void][CMNativeWinApi]::SetWindowPos($hwnd, $HWND_TOPMOST, $pixelLeft, $pixelTop, $pixelWidth, $pixelHeight, $flags)
        [void][CMNativeWinApi]::SetForegroundWindow($hwnd)
        Write-CMNativeLog ('Window visible [' + $reason + ']: hwnd=' + $hwnd + ' dip=' + $left + ',' + $top + ',' + $width + 'x' + $height + ' px=' + $pixelLeft + ',' + $pixelTop + ',' + $pixelWidth + 'x' + $pixelHeight + ' scale=' + $scaleX + 'x' + $scaleY)
      } else {
        Write-CMNativeLog ('Window handle vacio [' + $reason + ']')
      }
    } catch {
      Write-CMNativeLog ('ERROR ShowWindow [' + $reason + ']: ' + $_.Exception.Message)
    }
  }

  $window.Add_SourceInitialized({
    Show-CMNativeWindow 'source'
  })

  function New-CMNativeBitmap([string]$path) {
    $bitmap = New-Object System.Windows.Media.Imaging.BitmapImage
    $bitmap.BeginInit()
    $bitmap.CacheOption = [System.Windows.Media.Imaging.BitmapCacheOption]::OnLoad
    $bitmap.UriSource = [Uri]::new($path, [System.UriKind]::Absolute)
    $bitmap.EndInit()
    $bitmap.Freeze()
    return $bitmap
  }

  function Request-CMNativeImageNav([string]$direction) {
    try {
      if ($mediaType -ne 'image') { return }
      if ($direction -ne 'next' -and $direction -ne 'prev') { return }

      $nowMs = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
      if (($nowMs - [int64]$script:lastNavAt) -lt 280) { return }

      $script:lastNavAt = $nowMs
      $script:nav = $direction
      $script:navId = [string]$nowMs + '_' + $direction
      Write-CMNativeState 0 0 $true $false ''
      Write-CMNativeLog ('NavImage: ' + $direction)
    } catch {}
  }

  if ($mediaType -eq 'image') {
    $image = New-Object System.Windows.Controls.Image
    $image.Source = New-CMNativeBitmap $filePath
    $image.Stretch = [System.Windows.Media.Stretch]::Uniform
    $image.HorizontalAlignment = [System.Windows.HorizontalAlignment]::Center
    $image.VerticalAlignment = [System.Windows.VerticalAlignment]::Center
    [System.Windows.Media.RenderOptions]::SetBitmapScalingMode($image, [System.Windows.Media.BitmapScalingMode]::HighQuality)
    $window.Content = $image

    $window.Add_Loaded({
      Show-CMNativeWindow 'loaded-image'
      $window.Activate()
      $window.Focus()
      $window.Topmost = $false
      $window.Topmost = $true
    })
  } else {
    $media = New-Object System.Windows.Controls.MediaElement
    $media.LoadedBehavior = [System.Windows.Controls.MediaState]::Manual
    $media.UnloadedBehavior = [System.Windows.Controls.MediaState]::Manual
    $media.ScrubbingEnabled = $true
    $media.Stretch = [System.Windows.Media.Stretch]::Uniform
    $media.Volume = 0
    $media.Source = [Uri]::new($filePath, [System.UriKind]::Absolute)
    $window.Content = $media

    $window.Add_Loaded({
      Show-CMNativeWindow 'loaded-video'
      $window.Activate()
      $window.Focus()
      $media.Volume = 0
      $media.Play()
      $script:isPaused = $true
      Write-CMNativeLog 'Loaded: precarga silenciosa solicitada'
    })

    $media.Add_MediaOpened({
      Show-CMNativeWindow 'opened-video'
      $window.Activate()
      $duration = Get-CMNaturalDurationSeconds $media
      $media.Pause()
      $media.Position = [TimeSpan]::Zero
      Write-CMNativeState 0 $duration $true $false ''
      Write-CMNativeLog ('MediaOpened: duration=' + $duration)
      $media.Dispatcher.BeginInvoke([Action]{
        try {
          $media.Volume = 1
          $media.Play()
          $script:isPaused = $false
          Write-CMNativeState ([double]$media.Position.TotalSeconds) (Get-CMNaturalDurationSeconds $media) $false $false ''
          Write-CMNativeLog 'Play iniciado despues de MediaOpened'
        } catch {
          Write-CMNativeLog ('ERROR Play diferido: ' + $_.Exception.Message)
        }
      }, [System.Windows.Threading.DispatcherPriority]::ApplicationIdle) | Out-Null
    })

    $media.Add_MediaEnded({
      Write-CMNativeState (Get-CMNaturalDurationSeconds $media) (Get-CMNaturalDurationSeconds $media) $true $true ''
      Write-CMNativeLog 'MediaEnded'
      $window.Close()
    })

    $media.Add_MediaFailed({
      param($sender, $args)
      Write-CMNativeLog ('ERROR MediaFailed: ' + $args.ErrorException.Message)
      Write-CMNativeState 0 0 $true $false $args.ErrorException.Message
      $window.Close()
    })
  }

  $timer = New-Object System.Windows.Threading.DispatcherTimer
  $timer.Interval = [TimeSpan]::FromMilliseconds(250)
  $timer.Add_Tick({
    try {
      if (([CMNativeWinApi]::GetAsyncKeyState(0x1B) -band 0x8000) -ne 0) {
        $window.Close()
        return
      }

      if ($mediaType -eq 'image') {
        $leftDown = (([CMNativeWinApi]::GetAsyncKeyState(0x25) -band 0x8000) -ne 0)
        $rightDown = (([CMNativeWinApi]::GetAsyncKeyState(0x27) -band 0x8000) -ne 0)

        if ($leftDown -and -not $script:leftWasDown) {
          Request-CMNativeImageNav 'prev'
        }

        if ($rightDown -and -not $script:rightWasDown) {
          Request-CMNativeImageNav 'next'
        }

        $script:leftWasDown = $leftDown
        $script:rightWasDown = $rightDown
      }

      if ($mediaType -eq 'video' -or $mediaType -eq 'image') {
        if (Test-Path -LiteralPath $commandFile) {
          $rawCommand = Get-Content -LiteralPath $commandFile -Raw -Encoding UTF8 -ErrorAction SilentlyContinue
          if (-not [string]::IsNullOrWhiteSpace($rawCommand)) {
            $command = $rawCommand | ConvertFrom-Json -ErrorAction SilentlyContinue
            if ($command -and $command.id -and $command.id -ne $script:lastCommandId) {
              $script:lastCommandId = [string]$command.id
              $commandType = [string]$command.type

              if ($commandType -eq 'play' -and $media) {
                $media.Play()
                $script:isPaused = $false
              } elseif ($commandType -eq 'pause' -and $media) {
                $media.Pause()
                $script:isPaused = $true
              } elseif ($commandType -eq 'seek' -and $media) {
                $seconds = [double]$command.time
                if ($seconds -lt 0) { $seconds = 0 }
                $media.Position = [TimeSpan]::FromSeconds($seconds)
              } elseif ($commandType -eq 'load') {
                $nextPath = [string]$command.filePath
                $nextType = [string]$command.mediaType
                if ($nextType -eq 'video' -and $media -and -not [string]::IsNullOrWhiteSpace($nextPath) -and (Test-Path -LiteralPath $nextPath)) {
                  $media.Stop()
                  $script:isPaused = $true
                  Write-CMNativeState 0 0 $true $false ''
                  $media.Volume = 0
                  $media.Source = [Uri]::new($nextPath, [System.UriKind]::Absolute)
                  $media.Play()
                  Show-CMNativeWindow 'load-video'
                  Write-CMNativeLog ('Load: ' + $nextPath)
                } elseif ($nextType -eq 'image' -and $image -and -not [string]::IsNullOrWhiteSpace($nextPath) -and (Test-Path -LiteralPath $nextPath)) {
                  $image.Source = New-CMNativeBitmap $nextPath
                  $mediaType = 'image'
                  Write-CMNativeState 0 0 $true $false ''
                  Show-CMNativeWindow 'load-image'
                  Write-CMNativeLog ('LoadImage: ' + $nextPath)
                }
              } elseif ($commandType -eq 'close') {
                $window.Close()
                return
              }
            }
          }
        }

        if ($mediaType -eq 'video' -and $media) {
          $duration = Get-CMNaturalDurationSeconds $media
          $current = [double]$media.Position.TotalSeconds
          Write-CMNativeState $current $duration $script:isPaused $false ''
        }
      }
    } catch {
      Write-CMNativeLog ('ERROR Tick: ' + $_.Exception.Message)
    }
  })
  $timer.Start()

  $window.Add_KeyDown({
    param($sender, $args)
    if ($args.Key -eq [System.Windows.Input.Key]::Escape) {
      $window.Close()
    } elseif ($args.Key -eq [System.Windows.Input.Key]::Left) {
      Request-CMNativeImageNav 'prev'
    } elseif ($args.Key -eq [System.Windows.Input.Key]::Right) {
      Request-CMNativeImageNav 'next'
    }
  })

  $window.Add_PreviewKeyDown({
    param($sender, $args)
    if ($args.Key -eq [System.Windows.Input.Key]::Escape) {
      $window.Close()
    } elseif ($args.Key -eq [System.Windows.Input.Key]::Left) {
      Request-CMNativeImageNav 'prev'
    } elseif ($args.Key -eq [System.Windows.Input.Key]::Right) {
      Request-CMNativeImageNav 'next'
    }
  })

  $window.Add_Closed({
    try { if ($timer) { $timer.Stop() } } catch {}
    try { if ($media) { $media.Stop() } } catch {}
    Write-CMNativeState 0 0 $true $false '' $true
    Write-CMNativeLog 'Cerrado'
  })

  $app = New-Object System.Windows.Application
  [void]$app.Run($window)
} catch {
  Write-CMNativeLog ('ERROR: ' + $_.Exception.Message)
  exit 1
}
PS;

    if (@file_put_contents($files['ps1'], "\xEF\xBB\xBF" . $ps) === false) {
        return '';
    }

    return $files['ps1'];
}

function runNativePlayerPowerShell($sid, $filePath, $mediaType, $left, $top, $width, $height, $jobsPath) {
    $ps1 = writeNativePlayerPowerShell($sid, $filePath, $mediaType, $left, $top, $width, $height, $jobsPath);

    if ($ps1 === '') {
        return false;
    }

    if (runPowerShellInActiveSession($sid, $ps1, $jobsPath)) {
        return true;
    }

    // /min: sin esto, "start" muestra la consola un instante antes de que
    // -WindowStyle Hidden la oculte; el usuario final no debe verla.
    $cmd = 'cmd /c start "" /min powershell.exe -NoProfile -WindowStyle Hidden -STA -ExecutionPolicy Bypass -File ' . cmdQuote($ps1);
    return runDetachedCommand($cmd);
}

if (in_array($action, ['launch_player', 'launch_native_player', 'prewarm_native_player', 'native_player_state', 'native_player_command', 'position_player', 'close_player', 'activate_player'], true)) {
    $sid = trim($_GET['sid'] ?? '');

    if (!safePlayerSid($sid)) {
        sendJson(['error' => 'sid inválido para el player.']);
    }

    $left = intval($_GET['left'] ?? 0);
    $top = intval($_GET['top'] ?? 0);
    $width = intval($_GET['width'] ?? 1280);
    $height = intval($_GET['height'] ?? 720);

    if ($width < 300) $width = 1280;
    if ($height < 200) $height = 720;

    $playerJobsPath = cm_data_dir() . DIRECTORY_SEPARATOR . 'player_jobs';

    if ($action === 'native_player_state') {
        $nativeFiles = getNativePlayerFiles($sid, $playerJobsPath);
        $state = [];

        if (is_file($nativeFiles['state'])) {
            $rawState = @file_get_contents($nativeFiles['state']);
            if (is_string($rawState)) {
                $rawState = preg_replace('/^\xEF\xBB\xBF/', '', $rawState);
            }
            $decodedState = is_string($rawState) ? json_decode($rawState, true) : null;

            if (is_array($decodedState)) {
                $state = $decodedState;
            }
        }

        sendJson([
            'ok' => true,
            'sid' => $sid,
            'state' => $state
        ]);
    }

    if ($action === 'native_player_command') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJson(['error' => 'Método no permitido para el comando del player.']);
        }

        $type = trim((string) ($_POST['type'] ?? ''));
        $time = isset($_POST['time']) ? (float) $_POST['time'] : 0;

        if (!in_array($type, ['play', 'pause', 'seek', 'load', 'close'], true)) {
            sendJson(['error' => 'Comando no válido para el player.']);
        }

        if (!is_dir($playerJobsPath)) {
            @mkdir($playerJobsPath, 0777, true);
        }

        $nativeFiles = getNativePlayerFiles($sid, $playerJobsPath);
        $payload = [
            'id' => bin2hex(random_bytes(8)),
            'type' => $type,
            'time' => $time,
            'ts' => time()
        ];

        if ($type === 'load') {
            $folder = $_POST['folder'] ?? '';
            $file = $_POST['file'] ?? '';
            $mediaType = normalizeMediaType($_POST['mediaType'] ?? 'video');

            if (!$folder || !$file || !safeName($file)) {
                sendJson(['error' => 'Archivo no valido para cargar en el player.']);
            }

            $configuredFolder = findConfiguredFolder($folderConfigPath, $folder);

            if (!$configuredFolder) {
                sendJson(['error' => 'Carpeta no valida para cargar en el player.']);
            }

            $filePath = $configuredFolder['realPath'] . DIRECTORY_SEPARATOR . $file;

            if (!is_file($filePath) || !isMediaFileByType($file, $mediaType)) {
                sendJson(['error' => 'El archivo no existe o no es compatible.']);
            }

            $payload['filePath'] = $filePath;
            $payload['mediaType'] = $mediaType;
            $payload['file'] = basename($filePath);

            // Coordenadas del monitor elegido: el host se reposiciona en cada
            // load (necesario para revelar el host precalentado en la pantalla
            // correcta y para cambios de monitor entre videos).
            if (isset($_POST['left'], $_POST['top'], $_POST['width'], $_POST['height'])) {
                $payload['left'] = intval($_POST['left']);
                $payload['top'] = intval($_POST['top']);
                $payload['width'] = intval($_POST['width']);
                $payload['height'] = intval($_POST['height']);
            }
        }

        if (@file_put_contents($nativeFiles['command'], json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, LOCK_EX) === false) {
            sendJson(['error' => 'No se pudo enviar el comando al player.']);
        }

        sendJson(['ok' => true, 'sid' => $sid, 'command' => $payload]);
    }

    if ($action === 'prewarm_native_player') {
        // Pre-calienta el host nativo de video: PowerShell + WMP quedan listos
        // con la ventana oculta. El primer play se reduce a un 'load' (rápido).
        if (!runNativePlayerPowerShell($sid, '', 'video', $left, $top, $width, $height, $playerJobsPath)) {
            sendJson(['error' => 'No se pudo precalentar el reproductor.']);
        }

        sendJson(['ok' => true, 'mode' => 'prewarm_native_player', 'sid' => $sid]);
    }

    if ($action === 'launch_native_player') {
        $folder = $_GET['folder'] ?? '';
        $file = $_GET['file'] ?? '';
        $type = normalizeMediaType($_GET['type'] ?? 'video');

        if (!$folder || !$file || !safeName($file)) {
            sendJson(['error' => 'Archivo no válido para el reproductor de Windows.']);
        }

        $configuredFolder = findConfiguredFolder($folderConfigPath, $folder);

        if (!$configuredFolder) {
            sendJson(['error' => 'Carpeta no válida para el reproductor de Windows.']);
        }

        $filePath = $configuredFolder['realPath'] . DIRECTORY_SEPARATOR . $file;

        if (!is_file($filePath) || !isMediaFileByType($file, $type)) {
            sendJson(['error' => 'El archivo no existe o no es compatible.']);
        }

        if (!runNativePlayerPowerShell($sid, $filePath, $type, $left, $top, $width, $height, $playerJobsPath)) {
            sendJson(['error' => 'No se pudo abrir el reproductor de Windows.']);
        }

        sendJson([
            'ok' => true,
            'mode' => 'launch_native_player',
            'sid' => $sid,
            'type' => $type,
            'file' => basename($filePath),
            'target' => compact('left', 'top', 'width', 'height')
        ]);
    }

    if ($action === 'launch_player') {
        $chromePath = resolveChromePath();

        if (!$chromePath) {
            sendJson([
                'error' => 'No se encontró Google Chrome. Revisa si está instalado en Program Files.'
            ]);
        }

        $playerParams = [
            'sid' => $sid,
            'v' => '3'
        ];

        $initialFolder = trim((string) ($_GET['folder'] ?? ''));
        $initialFile = trim((string) ($_GET['file'] ?? ''));

        if ($initialFolder !== '' && $initialFile !== '') {
            $playerParams['folder'] = $initialFolder;
            $playerParams['file'] = $initialFile;
            $playerParams['source'] = trim((string) ($_GET['source'] ?? 'api')) === 'browser' ? 'browser' : 'api';
            $playerParams['type'] = normalizeMediaType($_GET['type'] ?? 'video');
            $playerParams['title'] = trim((string) ($_GET['title'] ?? $initialFile));
        }

        $url = getControlMusicaBaseUrl() . 'player.html?' . http_build_query($playerParams, '', '&', PHP_QUERY_RFC3986);

        $cmd = 'cmd /c start "" ' . cmdQuote($chromePath)
            . ' --new-window'
            . ' --app=' . cmdQuote($url)
            . ' --window-position=' . intval($left) . ',' . intval($top)
            . ' --window-size=' . intval($width) . ',' . intval($height)
            . ' --autoplay-policy=no-user-gesture-required';

        if (!runDetachedCommand($cmd)) {
            sendJson(['error' => 'No se pudo ejecutar Chrome desde PHP.']);
        }

        runPlayerPowerShell($sid, 'position', $left, $top, $width, $height, $playerJobsPath);

        sendJson([
            'ok' => true,
            'mode' => 'launch_player',
            'sid' => $sid,
            'url' => $url,
            'target' => compact('left', 'top', 'width', 'height')
        ]);
    }

    if ($action === 'position_player') {
        runPlayerPowerShell($sid, 'position', $left, $top, $width, $height, $playerJobsPath);
        sendJson(['ok' => true, 'mode' => 'position_player', 'sid' => $sid]);
    }

    if ($action === 'activate_player') {
        runPlayerPowerShell($sid, 'activate', $left, $top, $width, $height, $playerJobsPath);
        sendJson(['ok' => true, 'mode' => 'activate_player', 'sid' => $sid]);
    }

    if ($action === 'close_player') {
        $nativeFiles = getNativePlayerFiles($sid, $playerJobsPath);
        $nativePid = readJobPid($nativeFiles['pid']);

        if ($nativePid > 0) {
            stopWindowsProcessTree($nativePid);
        }

        runPlayerPowerShell($sid, 'close', $left, $top, $width, $height, $playerJobsPath);
        sendJson(['ok' => true, 'mode' => 'close_player', 'sid' => $sid]);
    }
}

if ($action === 'debug_env') {
    $ytDlpPath = resolveYtDlpPath($downloadsPath);

    sendJson([
        'ok' => true,
        'phpVersion' => PHP_VERSION,
        'os' => PHP_OS_FAMILY,
        'documentsPath' => $documentsPath,
        'documentsExists' => is_dir($documentsPath),
        'downloadsPath' => $downloadsPath,
        'downloadsExists' => is_dir($downloadsPath),
        'downloadJobsPath' => $downloadJobsPath,
        'downloadJobsExists' => is_dir($downloadJobsPath),
        'ytDlpDetectedInDownloads' => $ytDlpPath,
        'projectYtDlpPath' => projectYtDlpPath(),
        'projectYtDlpExists' => validYtDlpBinary(projectYtDlpPath()),
        'ffmpegDetected' => resolveFfmpegPath(),
        'projectFfmpegPath' => projectFfmpegPath(),
        'projectFfmpegExists' => validFfmpegBinary(projectFfmpegPath()),
        'popenExists' => function_exists('popen'),
        'shellExecExists' => function_exists('shell_exec'),
        'disableFunctions' => ini_get('disable_functions')
    ]);
}

if ($action === 'screens') {
    sendJson([
        'ok' => true,
        'screens' => detectWindowsScreens()
    ]);
}

if ($action === 'folders') {
    $configuredFolders = readConfiguredFolders($folderConfigPath);
    saveConfiguredFolders($folderConfigPath, $configuredFolders);

    $folders = array_map(
        fn($folder) => publicConfiguredFolder($folder),
        $configuredFolders
    );

    sendJson($folders);
}

if ($action === 'folder_add') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Método no permitido']);
    }

    $folderPath = trim($_POST['path'] ?? '');

    if ($folderPath === '') {
        sendJson(['error' => 'Pega la ruta completa de la carpeta.']);
    }

    sendJson(addConfiguredFolder($folderConfigPath, $folderPath));
}

if ($action === 'folders_reset') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Método no permitido']);
    }

    if (!writeJsonFile($folderConfigPath, [
        'version' => 1,
        'updatedAt' => date('c'),
        'folders' => []
    ])) {
        sendJson(['error' => 'No se pudo limpiar la lista interna de carpetas.']);
    }

    sendJson(['ok' => true]);
}

if ($action === 'rename_media') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Metodo no permitido']);
    }

    $folder = trim((string) ($_POST['folder'] ?? ''));
    $file = trim((string) ($_POST['file'] ?? ''));
    $type = normalizeMediaType($_POST['type'] ?? 'video');
    $newName = trim((string) ($_POST['newName'] ?? ''));

    sendJson(renameConfiguredMediaFile($folderConfigPath, $folder, $file, $type, $newName));
}

if ($action === 'playlists') {
    sendJson([
        'ok' => true,
        'playlists' => readSavedPlaylists($playlistConfigPath, $folderConfigPath)
    ]);
}

if ($action === 'playlist_save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Metodo no permitido']);
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $itemsRaw = (string) ($_POST['items'] ?? '[]');
    $items = json_decode($itemsRaw, true);

    sendJson(savePlaylist($playlistConfigPath, $folderConfigPath, $name, is_array($items) ? $items : []));
}

if ($action === 'playlist_delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Metodo no permitido']);
    }

    $id = trim((string) ($_POST['id'] ?? ''));
    sendJson(deletePlaylist($playlistConfigPath, $folderConfigPath, $id));
}

if ($action === 'playlist_remove_item') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Metodo no permitido']);
    }

    $id = trim((string) ($_POST['id'] ?? ''));
    $index = intval($_POST['index'] ?? -1);
    sendJson(removePlaylistItem($playlistConfigPath, $folderConfigPath, $id, $index));
}

if ($action === 'download_start') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Método no permitido']);
    }

    $folder = trim($_POST['folder'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $downloadType = strtolower(trim($_POST['downloadType'] ?? 'video'));
    $playlistLimit = intval($_POST['playlistLimit'] ?? 25);

    if (!in_array($downloadType, ['video', 'playlist'], true)) {
        $downloadType = 'video';
    }

    if ($downloadType === 'playlist') {
        $playlistLimit = 0;
    } else {
        $playlistLimit = 1;
    }

    $configuredFolder = findConfiguredFolder($folderConfigPath, $folder);

    if (!$configuredFolder) {
        sendJson(['error' => 'Selecciona una carpeta válida.']);
    }

    if (!isValidDownloadUrl($url)) {
        sendJson(['error' => 'Ingresa un enlace válido que empiece con http o https.']);
    }

    if (isUnsupportedFacebookReelUrl($url)) {
        sendJson(['error' => 'Ese primer reel de Facebook no trae un enlace descargable. Abre otro reel o copia el enlace del video con identificador.']);
    }

    if (isInstagramOrFacebookUrl($url) || isYouTubeAutoMixPlaylist($url)) {
        $downloadType = 'video';
        $playlistLimit = 1;
    }

    $playlistMode = $downloadType === 'playlist';
    $limitedPlaylist = false;

    if ($playlistMode && isYouTubeUrl($url) && youtubeListId($url) === '') {
        sendJson(['error' => 'Para descargar una playlist, el enlace debe incluir una lista de YouTube.']);
    }

    if (!$playlistMode) {
        $url = normalizeSingleVideoDownloadUrl($url);
    }

    $isInstagramDownload = isInstagramUrl($url);
    $isFacebookDownload = isFacebookUrl($url);
    $facebookCookiesPath = cm_data_dir() . DIRECTORY_SEPARATOR . 'app_data' . DIRECTORY_SEPARATOR . 'facebook_cookies.txt';
    $defaultFormatSelector = 'bestvideo[vcodec^=avc1][width<=1920][height<=1920]+bestaudio[acodec^=mp4a]/bestvideo[width<=1920][height<=1920]+bestaudio/best[width<=1920][height<=1920][vcodec!=none]/best[vcodec!=none]';
    $instagramFormatSelector = 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/bestvideo+bestaudio/best[ext=mp4]/best';
    $formatSelector = $isInstagramDownload ? $instagramFormatSelector : $defaultFormatSelector;

    $folderPath = $configuredFolder['realPath'];

    if (!is_dir($folderPath)) {
        sendJson(['error' => 'La carpeta seleccionada ya no existe.']);
    }

    if (!is_dir($downloadJobsPath) && !mkdir($downloadJobsPath, 0777, true)) {
        sendJson(['error' => 'No se pudo crear la carpeta interna de descargas.']);
    }

    if (!function_exists('popen')) {
        sendJson(['error' => 'La función popen está deshabilitada en PHP.']);
    }

    $jobId = bin2hex(random_bytes(16));
    $files = getJobFiles($jobId, $downloadJobsPath);

    file_put_contents($files['log'], "Preparando descarga...\n");

    if (file_exists($files['done'])) {
        unlink($files['done']);
    }

    if (file_exists($files['pid'])) {
        unlink($files['pid']);
    }

    if (file_exists($files['cancel'])) {
        unlink($files['cancel']);
    }

    if (stripos(PHP_OS_FAMILY, 'Windows') === false) {
        sendJson(['error' => 'Esta descarga automática está preparada para Windows con CMD.']);
    }

    $ytDlpInfo = ensureYtDlpPath($downloadsPath);
    $ytDlpPath = $ytDlpInfo['path'];

    if ($ytDlpPath === '') {
        sendJson(['error' => $ytDlpInfo['error'] ?: 'No se encontró yt-dlp.']);
    }

    $ffmpegInfo = ensureFfmpegPath();
    $ffmpegPath = $ffmpegInfo['path'];

    if ($ffmpegPath === '') {
        sendJson(['error' => $ffmpegInfo['error'] ?: 'No se encontró ffmpeg para unir video y audio en alta calidad.']);
    }

    $psScript = "";
    $psScript .= "\$OutputEncoding = [Console]::OutputEncoding = [System.Text.UTF8Encoding]::new()\r\n";
    $psScript .= "\$env:PYTHONIOENCODING = 'utf-8'\r\n";
    $psScript .= "\$ErrorActionPreference = 'Continue'\r\n";

    $psScript .= "\$log = " . psQuote($files['log']) . "\r\n";
    $psScript .= "\$done = " . psQuote($files['done']) . "\r\n";
    $psScript .= "\$pidFile = " . psQuote($files['pid']) . "\r\n";
    $psScript .= "\$cancelFile = " . psQuote($files['cancel']) . "\r\n";
    $psScript .= "\$destination = " . psQuote($folderPath) . "\r\n";
    $psScript .= "\$url = " . psQuote($url) . "\r\n";
    $psScript .= "\$downloadFolder = " . psQuote($downloadsPath) . "\r\n";
    $psScript .= "\$ytDlpPath = " . psQuote($ytDlpPath) . "\r\n";
    $psScript .= "\$ffmpegPath = " . psQuote($ffmpegPath) . "\r\n";
    $psScript .= "\$playlistMode = " . ($playlistMode ? '$true' : '$false') . "\r\n";
    $psScript .= "\$limitedPlaylist = " . ($limitedPlaylist ? '$true' : '$false') . "\r\n";
    $psScript .= "\$playlistLimit = " . intval($playlistLimit) . "\r\n";
    $psScript .= "\$instagramMode = " . ($isInstagramDownload ? '$true' : '$false') . "\r\n";
    $psScript .= "\$facebookMode = " . ($isFacebookDownload ? '$true' : '$false') . "\r\n";
    $psScript .= "\$facebookCookiesPath = " . psQuote($facebookCookiesPath) . "\r\n";

    $psScript .= "\$utf8NoBom = [System.Text.UTF8Encoding]::new(\$false)\r\n";
    $psScript .= "function Write-TextFile([string]\$path, [string]\$value) { [System.IO.File]::WriteAllText(\$path, \$value + [Environment]::NewLine, \$utf8NoBom) }\r\n";
    $psScript .= "function Add-TextFile([string]\$path, [string]\$value) { [System.IO.File]::AppendAllText(\$path, \$value + [Environment]::NewLine, \$utf8NoBom) }\r\n";
    $psScript .= "function Write-Log([string]\$msg) { Write-Host \$msg; Add-TextFile \$log \$msg }\r\n";
    $psScript .= "function Write-Done([int]\$code) { Write-TextFile \$done ('EXIT_CODE=' + \$code) }\r\n";
    $psScript .= "\$jobStartedAt = Get-Date\r\n";
    $psScript .= "function Set-CMUrlIndex([string]\$value, [int]\$index) {\r\n";
    $psScript .= "  if (\$value -match '([?&]index=)\\d+') { return [regex]::Replace(\$value, '([?&]index=)\\d+', ('" . '${1}' . "' + \$index), 1) }\r\n";
    $psScript .= "  \$sep = if (\$value.Contains('?')) { '&' } else { '?' }\r\n";
    $psScript .= "  return \$value + \$sep + 'index=' + \$index\r\n";
    $psScript .= "}\r\n";
    $psScript .= "function Get-CMYouTubeVisitorData([string]\$value) {\r\n";
    $psScript .= "  try {\r\n";
    $psScript .= "    \$headers = @{ 'User-Agent' = 'Mozilla/5.0'; 'Accept-Language' = 'es-419,es;q=0.9,en;q=0.8' }\r\n";
    $psScript .= "    \$response = Invoke-WebRequest -Uri \$value -UseBasicParsing -Headers \$headers -TimeoutSec 20\r\n";
    $psScript .= "    \$content = [string]\$response.Content\r\n";
    $psScript .= "    if (\$content -match '\"visitorData\":\"([^\"]+)\"') { return \$matches[1] }\r\n";
    $psScript .= "  } catch {\r\n";
    $psScript .= "    Write-Log ('AVISO: No se pudo fijar visitorData del mix: ' + \$_.Exception.Message)\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  return ''\r\n";
    $psScript .= "}\r\n";
    $psScript .= "function Test-CMWindowsVideoReady([string]\$file) {\r\n";
    $psScript .= "  if ([string]::IsNullOrWhiteSpace(\$file) -or -not (Test-Path -LiteralPath \$file)) { return \$false }\r\n";
    $psScript .= "  \$mediaInfo = (& \$ffmpegPath -nostdin -hide_banner -i \$file 2>&1 | Out-String)\r\n";
    $psScript .= "  \$videoReady = (\$mediaInfo -match 'Video:\\s*h264\\b') -and (\$mediaInfo -match 'yuv420p')\r\n";
    $psScript .= "  \$hasAudio = \$mediaInfo -match 'Audio:'\r\n";
    $psScript .= "  \$audioReady = (-not \$hasAudio) -or ((\$mediaInfo -match 'Audio:\\s*aac\\b') -and (\$mediaInfo -notmatch 'HE-AAC'))\r\n";
    $psScript .= "  return (\$videoReady -and \$audioReady)\r\n";
    $psScript .= "}\r\n";
    $psScript .= "function Convert-CMWindowsVideo([string]\$file, [string]\$sourceName) {\r\n";
    $psScript .= "  if ([string]::IsNullOrWhiteSpace(\$file) -or -not (Test-Path -LiteralPath \$file)) { return \$false }\r\n";
    $psScript .= "  if (Test-CMWindowsVideoReady \$file) { Write-Log ('Compatibilidad de ' + \$sourceName + ' ya lista.'); return \$true }\r\n";
    $psScript .= "  \$dir = [System.IO.Path]::GetDirectoryName(\$file)\r\n";
    $psScript .= "  \$name = [System.IO.Path]::GetFileNameWithoutExtension(\$file)\r\n";
    $psScript .= "  \$tmp = [System.IO.Path]::Combine(\$dir, \$name + '.cm_h264_tmp.mp4')\r\n";
    $psScript .= "  if (Test-Path -LiteralPath \$tmp) { Remove-Item -LiteralPath \$tmp -Force -ErrorAction SilentlyContinue }\r\n";
    $psScript .= "  Write-Log ('Ajustando compatibilidad de ' + \$sourceName + ' para Windows... (puede tardar un momento)')\r\n";
    // Conversión vía System.Diagnostics.Process con timeout duro: no puede
    // colgarse (antes ffmpeg dentro del pipe se quedaba esperando y el trabajo
    // nunca llegaba al 'done' quedándose en 100%).
    $psScript .= "  \$ffArgList = @('-nostdin','-hide_banner','-loglevel','error','-y','-i',\$file,'-map','0:v:0','-map','0:a?','-vf','scale=trunc(iw/2)*2:trunc(ih/2)*2','-c:v','libx264','-preset','veryfast','-crf','20','-pix_fmt','yuv420p','-c:a','aac','-b:a','192k','-movflags','+faststart',\$tmp)\r\n";
    $psScript .= "  \$ffArgsStr = ((\$ffArgList | ForEach-Object { '\"' + \$_ + '\"' }) -join ' ')\r\n";
    $psScript .= "  \$ffPsi = New-Object System.Diagnostics.ProcessStartInfo\r\n";
    $psScript .= "  \$ffPsi.FileName = \$ffmpegPath\r\n";
    $psScript .= "  \$ffPsi.Arguments = \$ffArgsStr\r\n";
    $psScript .= "  \$ffPsi.UseShellExecute = \$false\r\n";
    $psScript .= "  \$ffPsi.CreateNoWindow = \$true\r\n";
    $psScript .= "  \$ffPsi.RedirectStandardOutput = \$true\r\n";
    $psScript .= "  \$ffPsi.RedirectStandardError = \$true\r\n";
    $psScript .= "  \$convertCode = 1\r\n";
    $psScript .= "  try {\r\n";
    $psScript .= "    \$ffProc = [System.Diagnostics.Process]::Start(\$ffPsi)\r\n";
    $psScript .= "    \$ffErrTask = \$ffProc.StandardError.ReadToEndAsync()\r\n";
    $psScript .= "    \$ffOutTask = \$ffProc.StandardOutput.ReadToEndAsync()\r\n";
    $psScript .= "    if (\$ffProc.WaitForExit(360000)) {\r\n";
    $psScript .= "      \$convertCode = \$ffProc.ExitCode\r\n";
    $psScript .= "      \$ffErrText = \$ffErrTask.Result\r\n";
    $psScript .= "      if (-not [string]::IsNullOrWhiteSpace(\$ffErrText)) { Write-Log ('FFmpeg: ' + \$ffErrText.Trim()) }\r\n";
    $psScript .= "    } else {\r\n";
    $psScript .= "      try { \$ffProc.Kill() } catch {}\r\n";
    $psScript .= "      Write-Log ('AVISO: La conversion de ' + \$sourceName + ' tardo demasiado; se conserva el video original.')\r\n";
    $psScript .= "      if (Test-Path -LiteralPath \$tmp) { Remove-Item -LiteralPath \$tmp -Force -ErrorAction SilentlyContinue }\r\n";
    $psScript .= "      return \$false\r\n";
    $psScript .= "    }\r\n";
    $psScript .= "  } catch {\r\n";
    $psScript .= "    Write-Log ('AVISO: No se pudo iniciar la conversion de ' + \$sourceName + ': ' + \$_.Exception.Message)\r\n";
    $psScript .= "    return \$false\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  if (\$convertCode -eq 0 -and (Test-Path -LiteralPath \$tmp) -and ((Get-Item -LiteralPath \$tmp).Length -gt 0)) {\r\n";
    $psScript .= "    Move-Item -LiteralPath \$tmp -Destination \$file -Force\r\n";
    $psScript .= "    Write-Log ('Compatibilidad de ' + \$sourceName + ' lista.')\r\n";
    $psScript .= "    return \$true\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  if (Test-Path -LiteralPath \$tmp) { Remove-Item -LiteralPath \$tmp -Force -ErrorAction SilentlyContinue }\r\n";
    $psScript .= "  Write-Log ('AVISO: No se pudo ajustar compatibilidad de ' + \$sourceName + '. Codigo: ' + \$convertCode)\r\n";
    $psScript .= "  return \$false\r\n";
    $psScript .= "}\r\n";
    $psScript .= "Write-TextFile \$pidFile ('PID=' + \$PID)\r\n";

    $psScript .= "Clear-Host\r\n";
    $psScript .= "Write-Log 'Iniciando descarga con yt-dlp...'\r\n";
    $psScript .= "Write-Log ('Destino: ' + \$destination)\r\n";
    $psScript .= "if (\$limitedPlaylist) { Write-Log 'Modo: mix automático de YouTube' } elseif (\$playlistMode) { Write-Log 'Modo: playlist de usuario' } else { Write-Log 'Modo: video único' }\r\n";
    $psScript .= "if (\$limitedPlaylist) { Write-Log ('Límite del mix: ' + \$playlistLimit + ' videos') }\r\n";

    $psScript .= "if (\$instagramMode) { Write-Log 'Modo Instagram: salida MP4 compatible con Windows.' }\r\n";
    $psScript .= "if (\$facebookMode) { Write-Log 'Modo Facebook: salida MP4 compatible con Windows y reintento con sesion local si hace falta.' }\r\n";
    $psScript .= "\$cmd = Get-Command yt-dlp -ErrorAction SilentlyContinue\r\n";
    $psScript .= "\$yt = \$null\r\n";
    $psScript .= "if (\$cmd) { \$yt = \$cmd.Source }\r\n";
    $psScript .= "if (-not \$yt -and \$ytDlpPath -and (Test-Path -LiteralPath \$ytDlpPath)) { \$yt = \$ytDlpPath }\r\n";

    $psScript .= "if (-not \$yt) {\r\n";
    $psScript .= "  Write-Log 'ERROR: No se encontró yt-dlp.'\r\n";
    $psScript .= "  Write-Done 9009\r\n";
    $psScript .= "  Read-Host 'Presiona ENTER para cerrar'\r\n";
    $psScript .= "  exit 9009\r\n";
    $psScript .= "}\r\n";

    $psScript .= "Write-Log ('Ejecutable: ' + \$yt)\r\n";
    $psScript .= "Write-Log ('FFmpeg: ' + \$ffmpegPath)\r\n";

    $psScript .= "if (-not (Test-Path -LiteralPath \$destination)) {\r\n";
    $psScript .= "  Write-Log 'ERROR: La carpeta destino no existe.'\r\n";
    $psScript .= "  Write-Done 2\r\n";
    $psScript .= "  Read-Host 'Presiona ENTER para cerrar'\r\n";
    $psScript .= "  exit 2\r\n";
    $psScript .= "}\r\n";

    $psScript .= "\$ytArgs = @(\r\n";
    $psScript .= "  '--newline',\r\n";
    $psScript .= "  '--color', 'never',\r\n";
    $psScript .= "  '--encoding', 'utf-8',\r\n";
    $psScript .= "  '--windows-filenames',\r\n";
    $psScript .= "  '--no-mtime',\r\n";
    $psScript .= "  '--no-write-info-json',\r\n";
    $psScript .= "  '--no-write-playlist-metafiles',\r\n";
    $psScript .= "  '--progress',\r\n";
    $psScript .= "  '--progress-delta', '0.15',\r\n";
    $psScript .= "  '--file-access-retries', '10',\r\n";
    $psScript .= "  '--retries', '10',\r\n";
    $psScript .= "  '--concurrent-fragments', '4',\r\n";
    $psScript .= "  '-f', " . psQuote($formatSelector) . ",\r\n";
    $psScript .= "  '--merge-output-format', 'mp4',\r\n";
    $psScript .= "  '--ffmpeg-location', \$ffmpegPath,\r\n";
    $psScript .= "  '-P', \$destination,\r\n";
    $psScript .= "  '-o', '%(title).190s.%(ext)s',\r\n";
    $psScript .= "  '--print', 'before_dl:ITEM:%(playlist_index)s/%(playlist_count)s:%(title)s',\r\n";
    $psScript .= "  '--print', 'after_move:FILE:%(filepath)s',\r\n";
    $psScript .= "  '--progress-template', 'download:PROGRESS:%(progress._percent_str)s|%(progress._speed_str)s|%(progress._eta_str)s'\r\n";
    $psScript .= ")\r\n";

    $psScript .= "if (\$playlistMode -or \$limitedPlaylist) {\r\n";
    $psScript .= "  \$ytArgs += '--yes-playlist'\r\n";
    $psScript .= "  if (\$playlistMode) { \$ytArgs += '--ignore-errors' }\r\n";
    $psScript .= "} else {\r\n";
    $psScript .= "  \$ytArgs += '--no-playlist'\r\n";
    $psScript .= "}\r\n";

    // Node empaquetado con la app (runtime\node\node.exe) tiene prioridad:
    // así las descargas funcionan para TODOS los usuarios aunque no tengan
    // Node.js instalado. Fallback: node del sistema (PATH).
    $bundledNode = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'node' . DIRECTORY_SEPARATOR . 'node.exe';
    $psScript .= "\$bundledNode = " . psQuote($bundledNode) . "\r\n";
    $psScript .= "if (Test-Path -LiteralPath \$bundledNode) {\r\n";
    $psScript .= "  \$ytArgs += '--js-runtimes'\r\n";
    $psScript .= "  \$ytArgs += ('node:' + \$bundledNode)\r\n";
    $psScript .= "  Write-Log ('JS runtime: node empaquetado (' + \$bundledNode + ')')\r\n";
    $psScript .= "} else {\r\n";
    $psScript .= "  \$nodeCmd = Get-Command node -ErrorAction SilentlyContinue\r\n";
    $psScript .= "  if (\$nodeCmd) {\r\n";
    $psScript .= "    \$ytArgs += '--js-runtimes'\r\n";
    $psScript .= "    \$ytArgs += ('node:' + \$nodeCmd.Source)\r\n";
    $psScript .= "    Write-Log ('JS runtime: node (' + \$nodeCmd.Source + ')')\r\n";
    $psScript .= "  } else {\r\n";
    $psScript .= "    Write-Log 'JS runtime: no se detecto node; yt-dlp usara su configuracion por defecto.'\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "}\r\n";

    $psScript .= "if (\$limitedPlaylist) {\r\n";
    $psScript .= "  \$visitorData = Get-CMYouTubeVisitorData \$url\r\n";
    $psScript .= "  if (-not [string]::IsNullOrWhiteSpace(\$visitorData)) {\r\n";
    $psScript .= "    \$ytArgs += '--extractor-args'\r\n";
    $psScript .= "    \$ytArgs += ('youtube:visitor_data=' + \$visitorData)\r\n";
    $psScript .= "    Write-Log 'Mix: orden fijado para este trabajo.'\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "}\r\n";

    $psScript .= "if (Test-Path -LiteralPath \$cancelFile) {\r\n";
    $psScript .= "  Write-Log 'Descarga cancelada antes de iniciar.'\r\n";
    $psScript .= "  Write-Done 130\r\n";
    $psScript .= "  exit 130\r\n";
    $psScript .= "}\r\n";

    $psScript .= "function Invoke-CMYtDlpRetry([array]\$retryArgs, [string]\$targetUrl, [string]\$attemptLabel) {\r\n";
    $psScript .= "  \$script:lastYtDlpOutput = New-Object System.Collections.Generic.List[string]\r\n";
    $psScript .= "  if (-not [string]::IsNullOrWhiteSpace(\$attemptLabel)) { Write-Log \$attemptLabel }\r\n";
    $psScript .= "  & \$yt @retryArgs \$targetUrl 2>&1 | ForEach-Object {\r\n";
    $psScript .= "    \$line = [regex]::Replace([string]\$_, \"`e\\[[\\d;]*m\", '')\r\n";
    $psScript .= "    [void]\$script:lastYtDlpOutput.Add(\$line)\r\n";

    $psScript .= "    if (\$line -match '^ITEM:([^/]*)/([^:]*):(.*)$') {\r\n";
    $psScript .= "      \$i = \$matches[1]\r\n";
    $psScript .= "      \$c = \$matches[2]\r\n";
    $psScript .= "      \$t = \$matches[3]\r\n";
    $psScript .= "      if (-not (\$i -match '^\\d+$')) { \$i = '1' }\r\n";
    $psScript .= "      if (-not (\$c -match '^\\d+$')) { \$c = if (\$limitedPlaylist) { [string]\$playlistLimit } else { \$i } }\r\n";
    $psScript .= "      if (\$limitedPlaylist) { \$i = [string]\$script:activeMixIndex; \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "      if (\$limitedPlaylist -and ([int]\$c -gt \$playlistLimit)) { \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "      Write-Log ('Archivo ' + \$i + ' de ' + \$c + ': ' + \$t)\r\n";
    $psScript .= "      return\r\n";
    $psScript .= "    }\r\n";

    $psScript .= "    if (\$line -match '^FILE:(.+)$') {\r\n";
    $psScript .= "      \$readyFile = ([string]\$matches[1]).Trim()\r\n";
    $psScript .= "      if (-not [string]::IsNullOrWhiteSpace(\$readyFile)) { [void]\$script:downloadedFiles.Add(\$readyFile) }\r\n";
    $psScript .= "      return\r\n";
    $psScript .= "    }\r\n";

    $psScript .= "    if (\$line -match '^\\[download\\]\\s+Downloading item\\s+(\\d+)\\s+of\\s+(\\d+)') {\r\n";
    $psScript .= "      \$i = \$matches[1]\r\n";
    $psScript .= "      \$c = \$matches[2]\r\n";
    $psScript .= "      if (\$limitedPlaylist) { \$i = [string]\$script:activeMixIndex; \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "      if (\$limitedPlaylist -and ([int]\$c -gt \$playlistLimit)) { \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "      Write-Log ('Archivo ' + \$i + ' de ' + \$c + ': preparando video')\r\n";
    $psScript .= "      return\r\n";
    $psScript .= "    }\r\n";

    $psScript .= "    if (\$line -match '^PROGRESS:\\s*([0-9]+(?:\\.[0-9]+)?)%\\s*\\|(.*?)\\|(.*)$') {\r\n";
    $psScript .= "      \$speed = ([string]\$matches[2]).Trim()\r\n";
    $psScript .= "      \$eta = ([string]\$matches[3]).Trim()\r\n";
    $psScript .= "      \$detail = 'Progreso: ' + \$matches[1] + '%'\r\n";
    $psScript .= "      if (-not [string]::IsNullOrWhiteSpace(\$speed)) { \$detail += ' | Velocidad: ' + \$speed }\r\n";
    $psScript .= "      if (-not [string]::IsNullOrWhiteSpace(\$eta) -and \$eta -ne 'NA') { \$detail += ' | ETA: ' + \$eta }\r\n";
    $psScript .= "      Write-Log \$detail\r\n";
    $psScript .= "      return\r\n";
    $psScript .= "    }\r\n";

    $psScript .= "    if (\$line -match '\\[download\\]\\s+([0-9]+(?:\\.[0-9]+)?)%') {\r\n";
    $psScript .= "      Write-Log ('Progreso: ' + \$matches[1] + '%')\r\n";
    $psScript .= "      return\r\n";
    $psScript .= "    }\r\n";

    $psScript .= "    if (\$line -match 'could not find .*cookies database|Failed to decrypt|Could not copy .*cookie|--cookies-from-browser|unsupported platform') {\r\n";
    $psScript .= "      return\r\n";
    $psScript .= "    }\r\n";
    $psScript .= "    if (\$line -match 'ERROR|WARNING|Destination|already been downloaded|Deleting original|has already been downloaded') {\r\n";
    $psScript .= "      Write-Log \$line\r\n";
    $psScript .= "    }\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  \$retryCode = \$LASTEXITCODE\r\n";
    $psScript .= "  if (\$null -eq \$retryCode) { \$retryCode = 1 }\r\n";
    $psScript .= "  return [int]\$retryCode\r\n";
    $psScript .= "}\r\n";

    $psScript .= "Write-Log 'Descargando...'\r\n";

    $psScript .= "\$targetUrls = @(\$url)\r\n";
    $psScript .= "if (\$limitedPlaylist) {\r\n";
    $psScript .= "  \$targetUrls = @()\r\n";
    $psScript .= "  for (\$mixIndex = 1; \$mixIndex -le \$playlistLimit; \$mixIndex++) {\r\n";
    $psScript .= "    \$targetUrls += (Set-CMUrlIndex \$url \$mixIndex)\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "}\r\n";
    $psScript .= "\$overallCode = 0\r\n";
    $psScript .= "\$script:activeMixIndex = 0\r\n";
    $psScript .= "\$script:downloadedFiles = New-Object System.Collections.Generic.List[string]\r\n";
    $psScript .= "\$script:lastYtDlpOutput = New-Object System.Collections.Generic.List[string]\r\n";
    $psScript .= "foreach (\$targetUrl in \$targetUrls) {\r\n";
    $psScript .= "  if (Test-Path -LiteralPath \$cancelFile) { Write-Log 'Descarga cancelada por el usuario.'; \$overallCode = 130; break }\r\n";
    $psScript .= "  \$runArgs = @(\$ytArgs)\r\n";
    $psScript .= "  if (\$limitedPlaylist) {\r\n";
    $psScript .= "    \$script:activeMixIndex++\r\n";
    $psScript .= "    \$runArgs += '--playlist-items'\r\n";
    $psScript .= "    \$runArgs += [string]\$script:activeMixIndex\r\n";
    $psScript .= "    \$runArgs += '--ignore-errors'\r\n";
    $psScript .= "    Write-Log ('Archivo ' + \$script:activeMixIndex + ' de ' + \$playlistLimit + ': index=' + \$script:activeMixIndex)\r\n";
    $psScript .= "    Write-Log ('URL: ' + \$targetUrl)\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  \$script:lastYtDlpOutput = New-Object System.Collections.Generic.List[string]\r\n";
    $psScript .= "& \$yt @runArgs \$targetUrl 2>&1 | ForEach-Object {\r\n";
    $psScript .= "  \$line = [regex]::Replace([string]\$_, \"`e\\[[\\d;]*m\", '')\r\n";
    $psScript .= "  [void]\$script:lastYtDlpOutput.Add(\$line)\r\n";

    $psScript .= "  if (\$line -match '^ITEM:([^/]*)/([^:]*):(.*)$') {\r\n";
    $psScript .= "    \$i = \$matches[1]\r\n";
    $psScript .= "    \$c = \$matches[2]\r\n";
    $psScript .= "    \$t = \$matches[3]\r\n";
    $psScript .= "    if (-not (\$i -match '^\\d+$')) { \$i = '1' }\r\n";
    $psScript .= "    if (-not (\$c -match '^\\d+$')) { \$c = if (\$limitedPlaylist) { [string]\$playlistLimit } else { \$i } }\r\n";
    $psScript .= "    if (\$limitedPlaylist) { \$i = [string]\$script:activeMixIndex; \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "    if (\$limitedPlaylist -and ([int]\$c -gt \$playlistLimit)) { \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "    Write-Log ('Archivo ' + \$i + ' de ' + \$c + ': ' + \$t)\r\n";
    $psScript .= "    return\r\n";
    $psScript .= "  }\r\n";

    $psScript .= "  if (\$line -match '^FILE:(.+)$') {\r\n";
    $psScript .= "    \$readyFile = ([string]\$matches[1]).Trim()\r\n";
    $psScript .= "    if (-not [string]::IsNullOrWhiteSpace(\$readyFile)) { [void]\$script:downloadedFiles.Add(\$readyFile) }\r\n";
    $psScript .= "    return\r\n";
    $psScript .= "  }\r\n";

    $psScript .= "  if (\$line -match '^\\[download\\]\\s+Downloading item\\s+(\\d+)\\s+of\\s+(\\d+)') {\r\n";
    $psScript .= "    \$i = \$matches[1]\r\n";
    $psScript .= "    \$c = \$matches[2]\r\n";
    $psScript .= "    if (\$limitedPlaylist) { \$i = [string]\$script:activeMixIndex; \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "    if (\$limitedPlaylist -and ([int]\$c -gt \$playlistLimit)) { \$c = [string]\$playlistLimit }\r\n";
    $psScript .= "    Write-Log ('Archivo ' + \$i + ' de ' + \$c + ': preparando video')\r\n";
    $psScript .= "    return\r\n";
    $psScript .= "  }\r\n";

    $psScript .= "  if (\$line -match '^PROGRESS:\\s*([0-9]+(?:\\.[0-9]+)?)%\\s*\\|(.*?)\\|(.*)$') {\r\n";
    $psScript .= "    \$speed = ([string]\$matches[2]).Trim()\r\n";
    $psScript .= "    \$eta = ([string]\$matches[3]).Trim()\r\n";
    $psScript .= "    \$detail = 'Progreso: ' + \$matches[1] + '%'\r\n";
    $psScript .= "    if (-not [string]::IsNullOrWhiteSpace(\$speed)) { \$detail += ' | Velocidad: ' + \$speed }\r\n";
    $psScript .= "    if (-not [string]::IsNullOrWhiteSpace(\$eta) -and \$eta -ne 'NA') { \$detail += ' | ETA: ' + \$eta }\r\n";
    $psScript .= "    Write-Log \$detail\r\n";
    $psScript .= "    return\r\n";
    $psScript .= "  }\r\n";

    $psScript .= "  if (\$line -match '\\[download\\]\\s+([0-9]+(?:\\.[0-9]+)?)%') {\r\n";
    $psScript .= "    Write-Log ('Progreso: ' + \$matches[1] + '%')\r\n";
    $psScript .= "    return\r\n";
    $psScript .= "  }\r\n";

    $psScript .= "  if (\$line -match 'ERROR|WARNING|Destination|already been downloaded|Deleting original|has already been downloaded') {\r\n";
    $psScript .= "    Write-Log \$line\r\n";
    $psScript .= "  }\r\n";

    $psScript .= "}\r\n";

    $psScript .= "  \$itemCode = \$LASTEXITCODE\r\n";
    $psScript .= "  if (\$null -eq \$itemCode) { \$itemCode = 1 }\r\n";
    $psScript .= "  if (\$facebookMode -and \$itemCode -ne 0 -and \$itemCode -ne 130) {\r\n";
    $psScript .= "    Write-Log 'Facebook: el primer intento fallo; probando acceso con sesion local.'\r\n";
    $psScript .= "    \$facebookRetryPlans = @()\r\n";
    $psScript .= "    if (Test-Path -LiteralPath \$facebookCookiesPath) { \$facebookRetryPlans += ,@('--cookies', \$facebookCookiesPath) }\r\n";
    $psScript .= "    \$facebookRetryPlans += ,@('--cookies-from-browser', 'edge')\r\n";
    $psScript .= "    \$facebookRetryPlans += ,@('--cookies-from-browser', 'chrome')\r\n";
    $psScript .= "    foreach (\$cookieArgs in \$facebookRetryPlans) {\r\n";
    $psScript .= "      if (Test-Path -LiteralPath \$cancelFile) { Write-Log 'Descarga cancelada por el usuario.'; \$itemCode = 130; break }\r\n";
    $psScript .= "      \$retryArgs = @(\$runArgs) + \$cookieArgs\r\n";
    $psScript .= "      \$attemptName = if (\$cookieArgs[0] -eq '--cookies') { 'archivo app_data\\facebook_cookies.txt' } else { 'cookies de ' + \$cookieArgs[1] }\r\n";
    $psScript .= "      \$itemCode = Invoke-CMYtDlpRetry \$retryArgs \$targetUrl ('Facebook: reintentando con ' + \$attemptName + '...')\r\n";
    $psScript .= "      if (\$itemCode -eq 0) { Write-Log 'Facebook: descarga rescatada con sesion local.'; break }\r\n";
    $psScript .= "    }\r\n";
    $psScript .= "    if (\$itemCode -ne 0 -and \$itemCode -ne 130) {\r\n";
    $psScript .= "      Write-Log 'ERROR: Facebook no entrego este video en modo publico. Intenta con otro enlace del mismo video.'\r\n";
    $psScript .= "    }\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  if (\$itemCode -ne 0) {\r\n";
    $psScript .= "    \$overallCode = \$itemCode\r\n";
    $psScript .= "    if (\$limitedPlaylist) { Write-Log ('ERROR: index ' + \$script:activeMixIndex + ' termino con codigo ' + \$itemCode); continue }\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  if (\$limitedPlaylist) { Write-Log 'Progreso: 100%' }\r\n";
    $psScript .= "}\r\n";
    $psScript .= "if ((\$instagramMode -or \$facebookMode) -and \$overallCode -eq 0) {\r\n";
    $psScript .= "  if (\$script:downloadedFiles.Count -eq 0) {\r\n";
    $psScript .= "    \$recentFile = Get-ChildItem -LiteralPath \$destination -Filter '*.mp4' -File -ErrorAction SilentlyContinue | Where-Object { \$_.LastWriteTime -ge \$jobStartedAt.AddMinutes(-10) } | Sort-Object LastWriteTime -Descending | Select-Object -First 1\r\n";
    $psScript .= "    if (\$recentFile) { [void]\$script:downloadedFiles.Add(\$recentFile.FullName) }\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "  \$sourceName = if (\$facebookMode) { 'Facebook' } else { 'Instagram' }\r\n";
    $psScript .= "  \$seenFiles = @{}\r\n";
    $psScript .= "  foreach (\$downloadedFile in \$script:downloadedFiles) {\r\n";
    $psScript .= "    if ([string]::IsNullOrWhiteSpace(\$downloadedFile) -or \$seenFiles.ContainsKey(\$downloadedFile)) { continue }\r\n";
    $psScript .= "    \$seenFiles[\$downloadedFile] = \$true\r\n";
    // La conversión es un ajuste de compatibilidad best-effort: si falla o
    // agota el tiempo, se conserva el archivo descargado y el trabajo NO se
    // marca como error (la descarga en sí ya fue exitosa).
    $psScript .= "    if (-not (Convert-CMWindowsVideo \$downloadedFile \$sourceName)) { Write-Log ('AVISO: Se guardo el video de ' + \$sourceName + ' sin el ajuste de compatibilidad; deberia reproducirse igual.') }\r\n";
    $psScript .= "  }\r\n";
    $psScript .= "}\r\n";
    $psScript .= "\$code = \$overallCode\r\n";
    $psScript .= "Get-ChildItem -LiteralPath \$destination -Filter '*.info.json' -File -ErrorAction SilentlyContinue | Where-Object { \$_.LastWriteTime -ge \$jobStartedAt.AddSeconds(-5) } | Remove-Item -Force -ErrorAction SilentlyContinue\r\n";
    $psScript .= "Write-Done \$code\r\n";

    $psScript .= "if (\$code -eq 0) {\r\n";
    $psScript .= "  Write-Log 'Descarga completada correctamente.'\r\n";
    $psScript .= "} else {\r\n";
    $psScript .= "  Write-Log ('La descarga terminó con error. Código: ' + \$code)\r\n";
    $psScript .= "}\r\n";

    $psScript .= "exit \$code\r\n";

    $psScriptWithBom = "\xEF\xBB\xBF" . $psScript;

    if (file_put_contents($files['ps1'], $psScriptWithBom) === false) {
        sendJson(['error' => 'No se pudo crear el script interno de descarga.']);
    }

    // Consola oculta: el usuario final sigue el avance con la barra de
    // progreso de la app; la ventana negra no aporta nada. El progreso se
    // sigue leyendo del .log y la cancelación usa el PID (independiente de
    // la ventana).
    $cmd = 'cmd /c start "" /min powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ' . cmdQuote($files['ps1']);

    logDebug('Comando CMD generado', [
        'cmd' => $cmd,
        'ps1File' => $files['ps1'],
        'batFile' => $files['bat'],
        'folderPath' => $folderPath,
        'url' => $url,
        'downloadType' => $downloadType,
        'playlistLimit' => $playlistLimit,
        'limitedPlaylist' => $limitedPlaylist,
        'ffmpegPath' => $ffmpegPath,
        'playlistMode' => $playlistMode,
        'instagramMode' => $isInstagramDownload,
        'facebookMode' => $isFacebookDownload
    ]);

    $handle = @popen($cmd, 'r');

    if (!$handle) {
        sendJson(['error' => 'No se pudo abrir CMD.']);
    }

    pclose($handle);

    sendJson([
        'ok' => true,
        'jobId' => $jobId,
        'folder' => $configuredFolder['id'],
        'downloadType' => $downloadType,
        'playlistLimit' => $playlistLimit,
        'limitedPlaylist' => $limitedPlaylist,
        'playlistMode' => $playlistMode,
        'message' => 'Descarga iniciada.'
    ]);
}

if ($action === 'download_cancel') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['error' => 'Método no permitido']);
    }

    $jobId = trim($_POST['job'] ?? '');

    if (!safeJobId($jobId)) {
        sendJson(['error' => 'Trabajo de descarga no válido.']);
    }

    $files = getJobFiles($jobId, $downloadJobsPath);

    if (!is_dir($downloadJobsPath)) {
        sendJson(['error' => 'No existe la carpeta interna de descargas.']);
    }

    @file_put_contents($files['cancel'], date('c') . PHP_EOL, LOCK_EX);
    @file_put_contents($files['log'], "Descarga cancelada por el usuario.\n", FILE_APPEND | LOCK_EX);

    $pid = readJobPid($files['pid']);
    $stopped = $pid > 0 ? stopWindowsProcessTree($pid) : false;

    if (!is_file($files['done'])) {
        @file_put_contents($files['done'], "EXIT_CODE=130\n", LOCK_EX);
    }

    sendJson([
        'ok' => true,
        'jobId' => $jobId,
        'pid' => $pid,
        'stopped' => $stopped,
        'message' => $pid > 0 ? 'Descarga cancelada.' : 'Cancelación solicitada.'
    ]);
}

if ($action === 'download_status') {
    $jobId = $_GET['job'] ?? '';

    if (!safeJobId($jobId)) {
        sendJson([
            'ok' => false,
            'error' => 'Trabajo de descarga no válido.',
            'jobId' => $jobId
        ]);
    }

    $files = getJobFiles($jobId, $downloadJobsPath);

    $log = '';

    if (is_file($files['log'])) {
        clearstatcache(true, $files['log']);
        $content = @file_get_contents($files['log']);
        $log = $content !== false ? normalizeJobLogText($content) : '';
    }

    $done = is_file($files['done']);
    $exitCode = null;

    if ($done) {
        clearstatcache(true, $files['done']);
        $doneText = @file_get_contents($files['done']);

        if ($doneText !== false && preg_match('/EXIT_CODE\s*=\s*(-?\d+)/', $doneText, $m)) {
            $exitCode = intval($m[1]);
        }
    }

    $currentPercent = 0;
    $currentSpeed = '';
    $currentEta = '';
    $itemIndex = 1;
    $itemCount = 1;
    $itemTitle = '';

    $lines = preg_split('/\R/u', $log);

    if (!is_array($lines)) {
        $lines = [];
    }

    foreach ($lines as $line) {
        $line = trim((string) $line);

        if ($line === '') {
            continue;
        }

        if (preg_match('/Archivo\s+(\d+)\s+de\s+(\d+):\s*(.*)/', $line, $itemMatch)) {
            $itemIndex = max(1, intval($itemMatch[1]));
            $itemCount = max(1, intval($itemMatch[2]));
            $itemTitle = trim((string) $itemMatch[3]);
            $currentPercent = 0;
            $currentSpeed = '';
            $currentEta = '';
            continue;
        }

        if (preg_match('/Progreso:\s*([0-9]+(?:\.[0-9]+)?)%([^\r\n]*)/', $line, $progressMatch)) {
            $currentPercent = (float) $progressMatch[1];
            $detail = (string) ($progressMatch[2] ?? '');
            $currentSpeed = '';
            $currentEta = '';

            if (preg_match('/Velocidad:\s*([^|]+)/', $detail, $speedMatch)) {
                $currentSpeed = trim($speedMatch[1]);
            }

            if (preg_match('/ETA:\s*([^|]+)/', $detail, $etaMatch)) {
                $currentEta = trim($etaMatch[1]);
            }
        }
    }

    if (preg_match('/L(?:i|í)mite del mix:\s*(\d+)/u', $log, $limitMatch)) {
        $limitCount = max(1, intval($limitMatch[1]));
        $itemCount = min(max($itemCount, $itemIndex), $limitCount);
    }

    if ($itemCount > 1) {
        $progress = (($itemIndex - 1) + ($currentPercent / 100)) / $itemCount * 100;
    } else {
        $progress = $currentPercent;
    }

    if ($done && $exitCode === 0) {
        $progress = 100;
        $currentPercent = 100;
    }

    sendJson([
        'ok' => true,
        'jobId' => $jobId,
        'done' => $done,
        'exitCode' => $exitCode,
        'progress' => max(0, min(100, $progress)),
        'itemProgress' => max(0, min(100, $currentPercent)),
        'itemIndex' => $itemIndex,
        'itemCount' => $itemCount,
        'itemTitle' => $itemTitle,
        'speed' => $currentSpeed,
        'eta' => $currentEta,
        'logTail' => publicDownloadLogTail($log)
    ]);
}

if ($action === 'all') {
    $type = normalizeMediaType($_GET['type'] ?? 'video');
    $videos = [];

    foreach (readConfiguredFolders($folderConfigPath) as $folder) {
        $videos = array_merge($videos, listConfiguredFolderMedia($folder, $type));
    }

    usort($videos, fn($a, $b) => strcasecmp($a['title'], $b['title']));
    sendJson($videos);
}

if ($action === 'videos' || $action === 'media') {
    $folder = $_GET['folder'] ?? '';
    $type = $action === 'media' ? normalizeMediaType($_GET['type'] ?? 'video') : 'video';

    $configuredFolder = findConfiguredFolder($folderConfigPath, $folder);

    if (!$configuredFolder) {
        sendJson(['error' => 'Carpeta no válida']);
    }

    sendJson(listConfiguredFolderMedia($configuredFolder, $type));
}

if ($action === 'play') {
    while (ob_get_level()) ob_end_clean();

    $folder = $_GET['folder'] ?? '';
    $file = $_GET['file'] ?? '';

    if (!$folder || !$file || !safeName($file)) {
        http_response_code(400);
        exit('Archivo no válido');
    }

    $configuredFolder = findConfiguredFolder($folderConfigPath, $folder);

    if (!$configuredFolder) {
        http_response_code(400);
        exit('Carpeta no válida');
    }

    $filePath = $configuredFolder['realPath'] . DIRECTORY_SEPARATOR . $file;

    if (!$filePath || !file_exists($filePath) || !is_file($filePath)) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    $mimeTypes = [
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
        'mkv' => 'video/x-matroska',
        'avi' => 'video/x-msvideo',
        'wmv' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'mpg' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        '3gp' => 'video/3gpp',
        'ts' => 'video/mp2t',
        'mts' => 'video/mp2t',
        'm2ts' => 'video/mp2t',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'heic' => 'image/heic',
        'heif' => 'image/heif',
        'avif' => 'image/avif'
    ];

    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    $size = filesize($filePath);
    $start = 0;
    $end = $size - 1;

    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        header('Content-Length: ' . $size);
        exit;
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = intval($matches[1]);

            if ($matches[2] !== '') {
                $end = intval($matches[2]);
            }

            if ($start > $end || $start >= $size) {
                header("Content-Range: bytes */$size");
                http_response_code(416);
                exit;
            }

            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");
        }
    }

    $length = $end - $start + 1;
    header('Content-Length: ' . $length);

    $fp = fopen($filePath, 'rb');

    if (!$fp) {
        http_response_code(500);
        exit('No se pudo abrir el archivo');
    }

    fseek($fp, $start);
    $bufferSize = 1024 * 1024;

    while (!feof($fp) && ftell($fp) <= $end) {
        $remaining = $end - ftell($fp) + 1;
        echo fread($fp, min($bufferSize, $remaining));
        flush();

        if (connection_aborted()) {
            break;
        }
    }

    fclose($fp);
    exit;
}

sendJson(['error' => 'Acción no válida']);
