<?php
/**
 * appBackup
 * Description: A simple php script to archive mysql based applications.
 * @author David Reagan <david@reagannetworks.com>
 */
if (isset($argv[1])) {
    for ($i = 1; $i < $argc; $i++) {
        switch ($argv[$i]) {
            case '--apps':
                $runApps = $argv[$i + 1];
                break;
            default:
                break;
        }
    }
}

if(!empty($runApps)){
    $listOfAppsToBackup = explode(',',$runApps);
}
include 'config.php';
include 'functions.php';

$logFile = fopen($apps['logfile'], 'a+');
$dateTime = new DateTime('now');
$date = $dateTime->format('Y.m.d') . '-' . $dateTime->getTimestamp();

foreach ($apps['apps'] as $name => $val) {
    if (!empty($listOfAppsToBackup) && !in_array($name,$listOfAppsToBackup)) {
        // skip to next app.
        continue;
    }
    $tempDir = $val['tmp'] . '/' . $date . '-' . $name;
    $createTmpDir = mkdir($tempDir, 0755);
    if (!$createTmpDir) {
        $logMessage = "Could not create temp directory $tempDir. Abort run.";
        logAppBackupMessage($logFile, $name, 'error', $logMessage);
        break;
    }
    if ($val['backupDatabase']) {
        $mysqldumpPath = $tempDir . '/' . $date . '-' . $name . '.sql';
        $mysqldumpCommand = 'mysqldump -u ' . $val['database']['dbuser'] . ' -h' . $val['database']['dbhost'] . ' -p' . $val['database']['dbpass'] . ' ' . $val['database']['dbname'] . '> ' . $mysqldumpPath;
        execVarCustomCommand($logFile,$name,$mysqldumpCommand,"mysqldump");
    }
    if ($val['backupData']) {
        //Check if data is inside code. If it is, skip. Otherwise, copy.
        $pattern = '^' . $val['code'] . '^';
        if (!preg_match($pattern, $val['data'])) {
            $dirName = str_replace('/', '_', $val['data']);
            $dataDestination = $tempDir . '/' . $dirName;
            copyFiles($logFile, $name, $val['data'], $dataDestination);
        }
    }
    if ($val['backupCode']) {
        $dirName = str_replace('/', '_', $val['code']);
        $dataDestination = $tempDir . '/' . $dirName;
        copyFiles($logFile, $name, $val['code'], $dataDestination);
    }
    // Create tar.gz file.
    $tarCommand = 'tar -C tmp --remove-files -cpzf ' . $val['tmp'] . '/' . $date . '-' . $name . '.tar.gz ' . $date . '-' . $name;
    execVarCustomCommand($logFile,$name,$tarCommand,"create tar archive");
    // Move to store
    $moveCommand = 'mv ' . $val['tmp'] . '/' . $date . '-' . $name . '.tar.gz ' . $val['store'];
    execVarCustomCommand($logFile,$name,$moveCommand,"move tar archive to store");
}
