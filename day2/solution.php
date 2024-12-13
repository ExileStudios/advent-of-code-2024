<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 2: Red-Nosed Reports
 * 
 * Computes the number of safe reports based on specific safety criteria, including
 * the ability to tolerate a single bad level with the Problem Dampener.
 * 
 */
final class RedNosedReports
{
    /**
     * @var array<array<int>>
     */
    private array $reports;

    /**
     * Initializes the class with input data from the specified file.
     *
     * @param string $filename Path to the input file containing reports.
     * @throws RuntimeException If the file cannot be found or read.
     */
    public function __construct(string $filename)
    {
        $this->reports = $this->parseReports($filename);
    }

    /**
     * Parses the input file and returns a list of reports.
     *
     * @param string $filename Path to the input file.
     * @return array<array<int>> A list of reports.
     * @throws RuntimeException If the file cannot be found or read.
     */
    private function parseReports(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $fileLines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($fileLines === false) {
            throw new RuntimeException("File not readable: $filename");
        }

        return array_map(
            function ($line) {
                $parts = preg_split('/\s+/', trim($line));
                if ($parts === false) {
                    throw new RuntimeException("Invalid line format: $line");
                }
                return array_map('intval', $parts);
            },
            $fileLines
        );
    }

    /**
     * Determines if a given report meets the safety criteria.
     *
     * A report is considered safe if:
     * - All adjacent levels differ by 1 to 3 inclusive.
     * - The levels are consistently increasing or decreasing.
     *
     * @param array<int> $report An array representing the levels in a report.
     * @return bool True if the report is safe, false otherwise.
     */
    private function isSafeReport(array $report): bool
    {
        for ($i = 1, $direction = null; $i < count($report); $i++) {
            $diff = $report[$i] - $report[$i - 1];

            // Differences must be within the range [1, 3]
            if ($diff < -3 || $diff > 3 || $diff === 0) {
                return false;
            }

            // Establish or enforce direction
            $currentDirection = $diff > 0 ? 1 : -1;
            if ($direction === null) {
                $direction = $currentDirection;
            } elseif ($currentDirection !== $direction) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if a report can be made safe by removing a single level.
     *
     * @param array<int> $report An array representing the levels in a report.
     * @return bool True if the report can be made safe by removing one level, false otherwise.
     */
    private function canBeSafeWithDampener(array $report): bool
    {
        for ($i = 0; $i < count($report); $i++) {
            $modifiedReport = array_values(array_diff_key($report, [$i => $report[$i]]));
            if ($this->isSafeReport($modifiedReport)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Computes the total number of safe reports.
     *
     * @return int The count of reports that meet safety criteria.
     */
    public function computeSafeReports(): int
    {
        return count(array_filter($this->reports, fn($report) => $this->isSafeReport($report)));
    }

    /**
     * Computes the total number of reports that are safe with the Problem Dampener.
     *
     * @return int The count of reports that are safe or can be made safe with the Problem Dampener.
     */
    public function computeSafeReportsWithDampener(): int
    {
        return count(array_filter(
            $this->reports,
            fn($report) => $this->isSafeReport($report) || $this->canBeSafeWithDampener($report)
        ));
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $redNosedReports = new RedNosedReports($inputFile);
    echo "Safe reports: " . $redNosedReports->computeSafeReports() . PHP_EOL;
    echo "Safe reports with Problem Dampener: " . $redNosedReports->computeSafeReportsWithDampener() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
