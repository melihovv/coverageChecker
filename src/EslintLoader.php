<?php

namespace exussum12\CoverageChecker;

use InvalidArgumentException;
use stdClass;

/**
 * Class EslintLoader
 * Used for reading json output from eslint
 * @package exussum12\CoverageChecker
 */
class EslintLoader implements FileChecker
{
    /** @var stdClass */
    protected $json;

    /** @var array */
    protected $invalidLines;

    /** @var array */
    protected $invalidFiles = [];

    /**
     * EslintLoader constructor.
     * @param string $filePath The file path to the json output from eslint
     */
    public function __construct($filePath)
    {
        $this->json = json_decode(file_get_contents($filePath));

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                "Can't Parse eslint json - " . json_last_error_msg()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseLines()
    {
        $this->invalidLines = [];
        foreach ($this->json as $fileErrorsData) {
            foreach ($fileErrorsData->messages as $message) {
                $this->addInvalidLine($fileErrorsData->filePath, $message);
            }
        }

        return array_unique(array_merge(
            array_keys($this->invalidLines),
            array_keys($this->invalidFiles)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorsOnLine($file, $lineNumber)
    {
        $errors = [];

        if (! empty($this->invalidFiles[$file])) {
            $errors = $this->invalidFiles[$file];
        }

        if (! empty($this->invalidLines[$file][$lineNumber])) {
            $errors = array_merge($errors, $this->invalidLines[$file][$lineNumber]);
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function handleNotFoundFile()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDescription()
    {
        return 'Parses the json report format of eslint';
    }

    /**
     * @param string $file
     * @param stdClass $message
     */
    protected function addInvalidLine($file, $message)
    {
        $line = $message->line;

        if (! isset($this->invalidLines[$file][$line])) {
            $this->invalidLines[$file][$line] = [];
        }

        $this->invalidLines[$file][$line][] = $message->message;

        if ($message->nodeType === 'Program') {
            $this->invalidFiles[$file][] = $message->message;
        }
    }
}
