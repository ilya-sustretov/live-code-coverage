<?php

namespace LiveCodeCoverage;

use PHPUnit\TextUI\XmlConfiguration\Configuration;
use PHPUnit\TextUI\XmlConfiguration\Loader;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\FileIterator\Facade as FileIteratorFacade;
use SebastianBergmann\CodeCoverage\Filter;

final class CodeCoverageFactory
{
    /**
     * @param string $phpunitFilePath
     * @return CodeCoverage
     */
    public static function createFromPhpUnitConfiguration(string $phpunitFilePath): CodeCoverage
    {
        $codeCoverage = self::createDefault();

        $loader = new Loader();
        self::configure($codeCoverage, $loader->load($phpunitFilePath));

        return $codeCoverage;
    }

    private static function configure(CodeCoverage $codeCoverage, Configuration $configuration): void
    {
        $files = [];
        $codeCoverageConfiguration = $configuration->codeCoverage();

        // The following code is copied from PHPUnit\TextUI\TestRunner
        if ($codeCoverageConfiguration->hasNonEmptyListOfFilesToBeIncludedInCodeCoverageReport()) {
            if ($codeCoverageConfiguration->includeUncoveredFiles()) {
                $codeCoverage->includeUncoveredFiles();
            } else {
                $codeCoverage->excludeUncoveredFiles();
            }
        }

        foreach ($configuration->source()->includeDirectories() as $directoryToInclude => $details) {
            foreach ((new FileIteratorFacade())->getFilesAsArray($directoryToInclude, '.php') as $fileToInclude) {
                $files[realpath($fileToInclude)] = realpath($fileToInclude);
            }
        }

        foreach ($configuration->source()->includeFiles() as $fileToInclude) {
            $files[$fileToInclude->path()] = $fileToInclude;
        }

        foreach ($configuration->source()->excludeDirectories() as $directoryToExclude => $details) {
            foreach ((new FileIteratorFacade())->getFilesAsArray($directoryToExclude,'.php') as $fileToExclude) {
                unset($files[$fileToExclude]);
            }
        }

        foreach ($configuration->source()->excludeFiles() as $fileToExclude) {
            unset($files[realpath($fileToExclude->path())]);
        }

        foreach ($files as $file) {
            $codeCoverage->filter()->includeFile($file);
        }
    }

    /**
     * @return CodeCoverage
     */
    public static function createDefault(): CodeCoverage
    {
        $filter = new Filter();
        $driverSelector = new Selector();
        $driver = $driverSelector->forLineCoverage($filter);
        return new CodeCoverage($driver, $filter);
    }
}
