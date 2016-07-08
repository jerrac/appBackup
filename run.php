<?php
/**
 * appBackup
 * Description: A simple php script to archive mysql based applications.
 * @author David Reagan <david@reagannetworks.com>
 * Date: 7/8/16
 * Time: 10:42 AM
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

function copyFiles($logFile, $name, $source, $destination)
{
    $copyFilesOutput = array();
    $copyFilesReturn = false;
    $copyFilesCommand = "cp -r $source $destination";
    $copyFilesExec = exec($copyFilesCommand, $copyFilesOutput,
      $copyFilesReturn);
    if (!empty($copyFilesOutput)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('copyFilesOutput' => $copyFilesOutput));
    }
    if (!empty($copyFilesReturn)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('copyFilesReturn' => $copyFilesReturn));
    }
    if (!empty($copyFilesExec)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('mysqlExec' => $copyFilesExec));
    } elseif ($copyFilesExec === false) {
        logAppBackupMessage($logFile, $name, 'error',
          "copyFilesExec command returned FALSE for source $source");
    }
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
        $mysqldumpOutput = array();
        $mysqldumpReturn = false;
        $mysqldumpPath = $tempDir . '/' . $date . '-' . $name . '.sql';
        $mysqldumpCommand = 'mysqldump -u ' . $val['database']['dbuser'] . ' -h' . $val['database']['dbhost'] . ' -p' . $val['database']['dbpass'] . ' ' . $val['database']['dbname'] . '> ' . $mysqldumpPath;
        $mysqlExec = exec($mysqldumpCommand, $mysqldumpOutput,
          $mysqldumpReturn);
        if (!empty($mysqldumpOutput)) {
            logAppBackupMessage($logFile, $name, 'info',
              array('mysqldumpOutput' => $mysqldumpOutput));
        }
        if (!empty($mysqldumpReturn)) {
            logAppBackupMessage($logFile, $name, 'info',
              array('mysqldumpReturn' => $mysqldumpReturn));
        }
        if (!empty($mysqlExec)) {
            logAppBackupMessage($logFile, $name, 'info',
              array('mysqlExec' => $mysqlExec));
        } elseif ($mysqlExec === false) {
            logAppBackupMessage($logFile, $name, 'error',
              "mysqlDumpExec command returned FALSE.");
        }
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
    $tarOutput = array();
    $tarReturn = false;
    $tarCommand = 'tar -C tmp --remove-files -cpzf ' . $val['tmp'] . '/' . $date . '-' . $name . '.tar.gz ' . $date . '-' . $name;
    $tarExec = exec($tarCommand, $tarOutput, $tarReturn);
    if (!empty($tarOutput)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('tarOutput' => $tarOutput));
    }
    if (!empty($tarReturn)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('tarReturn' => $tarReturn));
    }
    if (!empty($tarExec)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('tarExec' => $tarExec));
    } elseif ($tarExec === false) {
        logAppBackupMessage($logFile, $name, 'error',
          "tarExec command returned FALSE");
    }

    // Move to store
    $moveOutput = array();
    $moveReturn = false;
    $moveCommand = 'mv ' . $val['tmp'] . '/' . $date . '-' . $name . '.tar.gz ' . $val['store'];
    $moveExec = exec($moveCommand, $moveOutput, $moveReturn);
    if (!empty($moveOutput)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('moveOutput' => $moveOutput));
    }
    if (!empty($moveReturn)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('moveReturn' => $moveReturn));
    }
    if (!empty($moveExec)) {
        logAppBackupMessage($logFile, $name, 'info',
          array('moveExec' => $moveExec));
    } elseif ($moveExec === false) {
        logAppBackupMessage($logFile, $name, 'error',
          "moveExec command returned FALSE");
    }
}
