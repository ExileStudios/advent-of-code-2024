<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 19: Linen Layout
 *
 * Determines how many towel designs can be constructed using available patterns.
 * For each design, calculates the number of ways it can be constructed.
 * 
 */
final class LinenLayout
{
    /**
     * @var array<string, true> Available towel patterns as a lookup table for quick access.
     */
    private array $towelPatterns;

    /**
     * @var array<string> List of towel designs to validate and analyze.
     */
    private array $designs;

    /**
     * Constructor initializes towel patterns and designs from the input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be accessed or read.
     */
    public function __construct(string $filename)
    {
        [$patterns, $designs] = $this->parseInput($filename);
        $this->towelPatterns = array_fill_keys(array_map('trim', $patterns), true);
        $this->designs = $designs;
    }

    /**
     * Parses the input file to extract towel patterns and designs.
     *
     * @param string $filename Path to the input file.
     * @return array{0: array<string>, 1: array<string>} Tuple of patterns and designs.
     * @throws RuntimeException If the file is invalid or unreadable.
     */
    private function parseInput(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        $patterns = explode(',', trim($lines[0]));
        $designs = array_slice($lines, 1);

        return [$patterns, $designs];
    }

    /**
     * Determines if a given design can be fully constructed using the towel patterns.
     *
     * @param string $design The design to validate.
     * @return bool True if the design is possible, false otherwise.
     */
    private function canConstructDesign(string $design): bool
    {
        $length = strlen($design);
        $dp = array_fill(0, $length + 1, false);
        $dp[0] = true; // Base case: empty design is always constructible.

        for ($i = 0; $i < $length; $i++) {
            if (!$dp[$i]) {
                continue;
            }

            foreach ($this->towelPatterns as $pattern => $_) {
                $patternLength = strlen($pattern);
                if (substr($design, $i, $patternLength) === $pattern) {
                    $dp[$i + $patternLength] = true;
                }
            }
        }

        return $dp[$length];
    }

    /**
     * Counts the number of distinct ways to construct a given design using the towel patterns.
     *
     * @param string $design The design to analyze.
     * @return int The total number of ways to construct the design.
     */
    private function countWaysToConstructDesign(string $design): int
    {
        $length = strlen($design);
        $dp = array_fill(0, $length + 1, 0);
        $dp[0] = 1; // Base case: one way to construct an empty design.

        for ($i = 0; $i < $length; $i++) {
            if ($dp[$i] === 0) {
                continue;
            }

            foreach ($this->towelPatterns as $pattern => $_) {
                $patternLength = strlen($pattern);
                if (substr($design, $i, $patternLength) === $pattern) {
                    $dp[$i + $patternLength] += $dp[$i];
                }
            }
        }

        return $dp[$length];
    }

    /**
     * Counts the number of designs that can be fully constructed using the towel patterns.
     *
     * @return int The count of constructible designs.
     */
    public function countPossibleDesigns(): int
    {
        $count = 0;
        foreach ($this->designs as $design) {
            if ($this->canConstructDesign($design)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Calculates the total number of ways to construct all valid designs.
     *
     * @return int The sum of all ways to construct the designs.
     */
    public function countTotalWays(): int
    {
        $totalWays = 0;
        foreach ($this->designs as $design) {
            $totalWays += $this->countWaysToConstructDesign($design);
        }
        return $totalWays;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $linenLayout = new LinenLayout($inputFile);

    echo "Possible Designs: " . $linenLayout->countPossibleDesigns() . PHP_EOL;
    echo "Total Ways: " . $linenLayout->countTotalWays() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
