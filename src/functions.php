<?php
namespace exussum12\CoverageChecker;

use Exception;

function findAutoLoader()
{
    $locations = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../autoload.php'
    ];

    $found = false;

    foreach ($locations as $file) {
        if (file_exists($file)) {
            require_once($file);
            $found = true;
            break;
        }
    }
    // @codeCoverageIgnoreStart
    if (!$found) {
        error_log(
            "Can't find the autoload file," .
            "please make sure 'composer install' has been run"
        );

        exit(1);
    // @codeCoverageIgnoreEnd
    }
}

function checkCallIsCorrect(ArgParser $args)
{
    if (!$args->getArg(1) || !$args->getArg(2)) {
        throw new Exception(
            "Missing arguments, please call with diff and check file\n" .
            "e.g. vendor/bin/diffFilter --phpcs diff.txt phpcs.json",
            1
        );
    }
}

/**
 * @codeCoverageIgnore
 */
function adjustForStdIn($argument)
{
    if ($argument == "-") {
        return "php://stdin";
    }

    return $argument;
}

function getMinPercent($percent)
{
    $minimumPercentCovered = 100;

    if (is_numeric($percent)) {
        $minimumPercentCovered = min(
            $minimumPercentCovered,
            max(0, $percent)
        );
    }

    return $minimumPercentCovered;
}

function handleOutput($lines, $minimumPercentCovered)
{
    $coveredLines = calculateLines($lines['coveredLines']);
    $uncoveredLines = calculateLines($lines['uncoveredLines']);


    if ($coveredLines + $uncoveredLines == 0) {
        echo "No lines found!";
        return;
    }
    $percentCovered = 100 * ($coveredLines / ($coveredLines + $uncoveredLines));

    $extra = PHP_EOL;

    if ($lines['uncoveredLines']) {
        $extra = ', Missed lines '.
            $extra .
            generateOutput($lines['uncoveredLines']) .  "\n"
        ;
    }

    printf('%.2f%% Covered%s', $percentCovered, $extra);

    if ($percentCovered >= $minimumPercentCovered) {
        return;
    }

    throw new Exception(
        "Failing due to coverage being lower than threshold",
        2
    );
}

function calculateLines($lines)
{
    return count($lines, COUNT_RECURSIVE) - count($lines);
}

function addExceptionHandler()
{
    set_exception_handler(
        function (Exception $exception) {
            // @codeCoverageIgnoreStart
            error_log($exception->getMessage());
            exit($exception->getCode());
            // @codeCoverageIgnoreEnd
        }
    );
}

function getFileChecker(ArgParser $args, array $argMapper, $filename)
{
    foreach ($argMapper as $arg => $class) {
        if ($args->getArg($arg)) {
            $class = __NAMESPACE__ . '\\' . $class;
            return new $class($filename);
        }
    }
    printOptions($argMapper);
    throw new Exception("Can not find file handler");
}

function printOptions(array $arguments)
{
    $tabWidth = 8;
    $defaultWidth = 80;

    $width = (int) (`tput cols` ?: $defaultWidth);
    $width -= 2 * $tabWidth;
    foreach ($arguments as $argument => $class) {
        $class = __NAMESPACE__ . '\\' . $class;

        $argument = '--' . $argument;
        if (strlen($argument) < $tabWidth) {
            $argument .= "\t";
        }

        printf(
            "%s\t%s\n",
            $argument,
            wordwrap(
                $class::getDescription(),
                $width,
                "\n\t\t",
                true
            )
        );
    }
}
function generateOutput($coverage)
{
    $output = '';
    foreach ($coverage as $filename => $lines) {
        $output .= "\n\n'$filename' has no coverage for the following lines:\n";
        foreach ($lines as $line => $message) {
            $output .= generateOutputLine($line, $message);
        }
    }

    return trim($output);
}

function generateOutputLine($line, $message)
{
    $output = "Line $line:\n";
    if (!empty($message)) {
        foreach ((array)$message as $part) {
            $output .= "\t$part\n";
        }
    }

    return $output . "\n";
}
