--TEST--
ErrorHandler::notify() fatals with a throwing error handler
--SKIPIF--
<?php

if (!file_exists(__DIR__ . "/vendor/autoload.php")) {
    die("Skipped: Cannot detect PHPUnit version, run tests from project root.");
}

// If the directory is not the root of the project, this will silently fail and execute the test below.
require __DIR__ . "/vendor/autoload.php";

$series = PHPUnit_Runner_Version::series();
list($major, $minor) = explode(".", $series);

if ($major < 5 || $major == 5 && $minor < 1) {
    die("Skipped: PHPUnit 5.1 required to test for STDERR output.");
}

?>
--FILE--
<?php

require __DIR__ . "/../../vendor/autoload.php";

set_error_handler(function () {
    throw new RuntimeException;
});

AsyncInterop\Promise\ErrorHandler::notify(new Exception);

?>
--EXPECT--
Fatal error: Uncaught exception 'RuntimeException' while trying to report a throwing AsyncInterop\Promise::when() handler gracefully.
