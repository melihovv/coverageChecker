<?php
namespace exussum12\CoverageChecker;

abstract class DiffLineHandle
{
    protected $diffFileState;

    public function __construct(DiffFileState $diff)
    {
        $this->diffFileState = $diff;
    }

    abstract public function handle($line);

    abstract public function isValid($line);
}
