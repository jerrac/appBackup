<?php
/**
 * @file functions.php
 * Description: Functions for appBackup.
 * @author David Reagan <david@reagannetworks.com>
 */

/**
 * fwrite a json_encode'd message to a file.
 * @param resource $logFile An open for writing file handle from fopen
 * @param string $appName The name of the app the log message applies to.
 * @param string  $level
 * @param string|array $logMessage
 */
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

/**
 * Execute a command with exec(), as well as make sure to log useful information about the command.
 * @param resource $logFile An open for writing file handle from fopen
 * @param string $name The name of the app the command is run for.
 * @param string $command The actual command to run.
 * @param string $opTag Use to make sure you know what exec the log message is about.
 */
function execVarCustomCommand($logFile,$name,$command,$opTag = ''){
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

/**
 * Copy a directory from source to destination
 * @param resource $logFile An open for writing file handle from fopen
 * @param string $name The name of the app the command is run for.
 * @param string $source Path to the directory that is being copied.
 * @param string $destination Path to where the copy should be saved.
 */
function copyFiles($logFile, $name, $source, $destination)
{
    $copyFilesCommand = "cp -r $source $destination";
    execVarCustomCommand($logFile,$name,$copyFilesCommand,"copy files");
}
