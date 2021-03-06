<?php declare(strict_types=1);
namespace Phan\Language;

use \Phan\CodeBase\File;
use \Phan\Config;

/**
 * An object representing the context in which any
 * structural element (such as a class or method) lives.
 */
class FileRef implements \Serializable {

    /**
     * @var string
     * The path to the file in which this element is defined
     */
    protected $file = 'internal';

    /**
     * @var int
     * The starting line number of the element within the $file
     */
    protected $line_number_start = 0;

    /**
     * @var int
     * The ending line number of the element within the $file
     */
    protected $line_number_end = 0;

    /**
     * @var int
     * strict_types setting for the file
     */
    protected $strict_types = 0;

    /**
     * @param string $file
     * The path to the file in which this element is defined
     *
     * @return Context
     * This context with the given value is returned
     */
    public function withFile(string $file) : Context {
        $context = clone($this);
        $context->file = $file;
        return $context;
    }

    /**
     * @return string
     * The path to the file in which the element is defined
     */
    public function getFile() : string {
        return $this->file;
    }

    /**
     * @return string
     * The full path of the file
     */
    public function getRealPath() : string {
        return realpath($this->file) ?: $this->file;
    }

    /**
     * @return string
     * The path of the file relative to the project
     * root directory
     */
    public function getProjectRelativePath() : string {
        return File::projectRelativePathFromCWDRelativePath(
            $this->file
        );
    }

    /**
     * @return bool
     * True if this object is internal to PHP
     */
    public function isInternal() : bool {
        return ('internal' === $this->getFile());
    }

    /**
     * @var int $line_number
     * The starting line number of the element within the file
     *
     * @return Context
     * This context with the given value is returned
     */
    public function withLineNumberStart(int $line_number) : Context {
        $this->line_number_start = $line_number;
        return $this;
    }

    /*
     * @return int
     * The starting line number of the element within the file
     */
    public function getLineNumberStart() : int {
        return $this->line_number_start;
    }

    /**
     * @param int $line_number
     * The ending line number of the element within the $file
     *
     * @return Context
     * This context with the given value is returned
     */
    public function withLineNumberEnd(int $line_number) : Context {
        $this->line_number_end = $line_number;
        return $this;
    }

    /**
     * @return int
     * The ending line number of the element within the $file
     */
    public function getLineNumberEnd() : int {
        return $this->line_number_end;
    }

    /**
     * @param int $strict_types
     * The strict_type setting for the file
     *
     * @return Context
     * This context with the given value is returned
     */
    public function withStrictTypes(int $strict_types) : Context {
        $this->strict_types = $strict_types;
        return $this;
    }

    /**
     * @return int
     * The strict_types setting for the file
     */
    public function getStrictTypes() : int {
        return $this->strict_types;
    }

    /**
     * Get a string representation of the context
     *
     * @return string
     */
    public function __toString() : string {
        return implode(':', [
            $this->file,
            $this->line_number_start ?? 0,
            $this->line_number_end ?? 0,
        ]);
    }

    public function serialize() {
        return (string)$this;
    }

    public function unserialize($serialized) {
        $map = explode(':', $serialized);
        $this->file = $map[0];
        $this->line_number_start = (int)$map[1];
        $this->line_number_end = (int)$map[2];
    }

}
