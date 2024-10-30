<?php

namespace LiveCodeCoverage;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use Webmozart\Assert\Assert;

final class LiveCodeCoverage
{
    private string $coverageId;
    private CodeCoverage $codeCoverage;
    private string $storageDirectory;

    private function __construct(CodeCoverage $codeCoverage, $storageDirectory, $coverageId)
    {
        $this->codeCoverage = $codeCoverage;
        $this->coverageId = $coverageId;
        $this->storageDirectory = $storageDirectory;
    }

    /**
     * @param bool $collectCodeCoverage
     * @param string $storageDirectory
     * @param string|null $phpunitConfigFilePath
     * @param string $coverageId
     * @return callable|array|\Closure
     */
    public static function bootstrap(bool $collectCodeCoverage, string $storageDirectory, string $phpunitConfigFilePath = null, string $coverageId = 'live-coverage'): callable|array|\Closure
    {
        Assert::boolean($collectCodeCoverage);
        if (!$collectCodeCoverage) {
            return function () {
                // do nothing - code coverage is not enabled
            };
        }

        if ($phpunitConfigFilePath !== null) {
            Assert::file($phpunitConfigFilePath);
            $codeCoverage = CodeCoverageFactory::createFromPhpUnitConfiguration($phpunitConfigFilePath);
        } else {
            $codeCoverage = CodeCoverageFactory::createDefault();
        }

        $liveCodeCoverage = new self($codeCoverage, $storageDirectory, $coverageId);

        $liveCodeCoverage->start();

        return [$liveCodeCoverage, 'stopAndSave'];
    }

    private function start(): void
    {
        $this->codeCoverage->start($this->coverageId);
    }

    /**
     * Stop collecting code coverage data and save it.
     */
    public function stopAndSave(): void
    {
        $this->codeCoverage->stop();

        Storage::storeCodeCoverage(
            $this->codeCoverage,
            $this->storageDirectory,
            uniqid(date('YmdHis'), true)
        );
    }
}
