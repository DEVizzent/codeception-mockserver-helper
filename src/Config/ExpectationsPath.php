<?php

namespace DEVizzent\CodeceptionMockServerHelper\Config;

use DEVizzent\CodeceptionMockServerHelper\MockServerHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ExpectationsPath
{
    private string $path = '';

    public function __construct(string $path = '')
    {
        if (!empty($path)) {
            $this->set($path);
        }
    }

    private function set(string $path)
    {
        $path = realpath($path);
        if ($path) {
            $this->path = $path;
            return;
        }
        throw new \InvalidArgumentException(sprintf('"%s" is not a valid path for "expectationsPath"', $path));
    }

    public function getExpectationsFiles(): iterable
    {
        if (empty($this->path)) {
            return [];
        }
        if (!is_dir($this->path)) {
            return [$this->path];
        }
        $recursiveIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
        $files = [];
        /** @var SplFileInfo $file */
        foreach ($recursiveIterator as $file) {
            if ($file->isDir()){
                continue;
            }
            $files[] = $file->getPathname();
        }
        return $files;
    }
}