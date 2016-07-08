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
function logAppBackupMessage($logFile, $appName, $level, $logMessage)
{
    $time = new DateTime('now');
    $timestamp = $time->format(DATE_ISO8601);
    $message = array(
      'timestamp' => $timestamp,
      'level' => $level,
      'app' => $appName,
      'message' => $logMessage,
    );
    fwrite($logFile, json_encode($message) . "\n");
}

function execVarCustomCommand($logFile,$name,$command,$opTag){
    $outputVar = array();
    $returnVar = false;
    $execVar = exec($command, $outputVar, $returnVar);
    if (!empty($outputVar)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('outputVar' => $outputVar, 'opTag' => $opTag));
    }
    if (!empty($returnVar)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('returnVar' => $returnVar, 'opTag' => $opTag));
    }
    if (!empty($execVar)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('execVar' => $execVar, 'opTag' => $opTag));
    } elseif ($execVar === false) {
        logAppBackupMessage($logFile, $name, 'error',
          array('message' => 'Exec command returned FALSE', 'opTag' => $opTag));
    }
}

function copyFiles($logFile, $name, $source, $destination)
{
    $copyFilesCommand = "cp -r $source $destination";
    execVarCustomCommand($logFile,$name,$copyFilesCommand,"copy files");
}

include 'config.php';

$logFile = fopen($apps['logfile'], 'a+');

$dateTime = new DateTime('now');
//$date = ;
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
