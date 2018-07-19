<?php
namespace exussum12\CoverageChecker\tests;

use exussum12\CoverageChecker\EslintLoader;
use PHPUnit\Framework\TestCase;

class LoadEslintReportTest extends TestCase
{
    public function testCanMakeClass()
    {
        $eslint = new EslintLoader(__DIR__ . '/fixtures/eslint.json');
        $eslint->parseLines();

        $this->assertEquals(
            ['Strings must use singlequote.'],
            $eslint->getErrorsOnLine('/gulp/tasks/build.js', 1)
        );
        $this->assertEquals(
            [],
            $eslint->getErrorsOnLine('/gulp/tasks/build.js', 2)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRejectsInvalidData()
    {
        new EslintLoader(__DIR__ . '/fixtures/change.txt');
    }

    public function testCorrectMissingFile()
    {
        $eslint = new EslintLoader(__DIR__ . '/fixtures/eslint.json');

        $this->assertTrue($eslint->handleNotFoundFile());
    }

    public function testWholeFileError()
    {
        $eslint = new EslintLoader(__DIR__ . '/fixtures/eslint.json');
        $eslint->parseLines();

        $this->assertEquals(
            [
                "Expected linebreaks to be 'LF' but found 'CRLF'.",
                'Newline required at end of file but not found.',
            ],
            $eslint->getErrorsOnLine('/webpack/prod.config.js', 100500)
        );
    }
}
