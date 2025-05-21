<?php
echo "Checking logs directory...\n";
$logsDir = __DIR__ . '/logs';
if (is_dir($logsDir)) {
    echo "logs directory exists.\n";

    // Check if logs/php_errors.log is writable
    $errorLogFile = $logsDir . '/php_errors.log';
    echo "Checking if $errorLogFile is writable...\n";
    if (file_exists($errorLogFile)) {
        if (is_writable($errorLogFile)) {
            echo "$errorLogFile is writable.\n";
            // Try appending a message
            $timestamp = date('Y-m-d H:i:s');
            if (file_put_contents($errorLogFile, "[$timestamp] Test message from check_logging.php\n", FILE_APPEND)) {
                echo "Successfully appended to $errorLogFile.\n";
            } else {
                echo "Failed to append to $errorLogFile.\n";
            }
        } else {
            echo "$errorLogFile is not writable.\n";
        }
    } else {
        echo "$errorLogFile does not exist. Trying to create it...\n";
        if (touch($errorLogFile)) {
            echo "Successfully created $errorLogFile.\n";
            if (is_writable($errorLogFile)) {
                echo "$errorLogFile is now writable.\n";
                 // Try appending a message
                $timestamp = date('Y-m-d H:i:s');
                if (file_put_contents($errorLogFile, "[$timestamp] Test message from check_logging.php after creation\n", FILE_APPEND)) {
                    echo "Successfully appended to $errorLogFile after creation.\n";
                } else {
                    echo "Failed to append to $errorLogFile after creation.\n";
                }
            } else {
                echo "$errorLogFile is not writable even after creation.\n";
            }
        } else {
            echo "Failed to create $errorLogFile. Check directory permissions.\n";
        }
    }

    // Check if logs directory is writable for daily log files
    $dailyLogFile = $logsDir . '/' . date('Y-m-d') . '.log';
    echo "Checking if logs directory is writable by attempting to create $dailyLogFile...\n";
    if (touch($dailyLogFile)) {
        echo "Successfully created $dailyLogFile. logs directory is writable.\n";
        // Clean up the test file
        unlink($dailyLogFile);
    } else {
        echo "Failed to create $dailyLogFile. logs directory might not be writable.\n";
    }

} else {
    echo "logs directory does not exist. Attempting to create it...\n";
    if (mkdir($logsDir, 0755, true)) {
        echo "logs directory created successfully.\n";
        // Now check writability of php_errors.log and logs directory again
        $errorLogFile = $logsDir . '/php_errors.log';
        echo "Checking if $errorLogFile is writable...\n";
         if (touch($errorLogFile)) {
            echo "Successfully created $errorLogFile.\n";
            if (is_writable($errorLogFile)) {
                echo "$errorLogFile is now writable.\n";
                // Try appending a message
                $timestamp = date('Y-m-d H:i:s');
                if (file_put_contents($errorLogFile, "[$timestamp] Test message from check_logging.php after logs dir and file creation\n", FILE_APPEND)) {
                    echo "Successfully appended to $errorLogFile after logs dir and file creation.\n";
                } else {
                    echo "Failed to append to $errorLogFile after logs dir and file creation.\n";
                }
            } else {
                echo "$errorLogFile is not writable even after creation.\n";
            }
        } else {
            echo "Failed to create $errorLogFile. Check directory permissions.\n";
        }

        $dailyLogFile = $logsDir . '/' . date('Y-m-d') . '.log';
        echo "Checking if logs directory is writable by attempting to create $dailyLogFile...\n";
        if (touch($dailyLogFile)) {
            echo "Successfully created $dailyLogFile. logs directory is writable.\n";
            // Clean up the test file
            unlink($dailyLogFile);
        } else {
            echo "Failed to create $dailyLogFile. logs directory might not be writable.\n";
        }
    } else {
        echo "Failed to create logs directory.\n";
    }
}

?>
