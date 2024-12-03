<?php

/**
 * Advent of Code 2024 - Day 3: Mull It Over
 * This class computes sums of valid multiplication instructions
 * and conditionally enabled instructions based on parsed corrupted memory.
 */
class MullItOver
{
    /**
     * @var array<string> Corrupted memory lines from the input file.
     */
    private array $corruptedMemory;

    /**
     * Constructor to initialize corrupted memory lines from the given file.
     *
     * @param string $filename Path to the input file containing corrupted memory.
     * @throws RuntimeException If the file cannot be found or read.
     */
    public function __construct(string $filename)
    {
        $this->corruptedMemory = $this->parseCorruptedMemory($filename);
    }

    /**
     * Parses the input file and returns its lines as corrupted memory.
     *
     * @param string $filename Path to the input file.
     * @return array<string> An array of lines from the input file.
     * @throws RuntimeException If the file cannot be found or read.
     */
    private function parseCorruptedMemory(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $fileLines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($fileLines === false) {
            throw new RuntimeException("File not readable: $filename");
        }

        return $fileLines;
    }

    /**
     * Computes the sum of all valid `mul(X, Y)` results from corrupted memory.
     *
     * @return int The total sum of all valid multiplication results.
     */
    public function computeAllMultiplicationsSum(): int
    {
        $totalSum = 0;

        foreach ($this->corruptedMemory as $line) {
            if (preg_match_all('/mul\((\d{1,3}),(\d{1,3})\)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $totalSum += (int)$match[1] * (int)$match[2];
                }
            }
        }

        return $totalSum;
    }

    /**
     * Computes the sum of enabled `mul(X, Y)` results considering `do()` and `don't()` instructions.
     *
     * @return int The total sum of enabled multiplication results.
     */
    public function computeEnabledMultiplicationsSum(): int
    {
        $totalSum = 0;
        $isEnabled = true;

        foreach ($this->corruptedMemory as $line) {
            if (preg_match_all('/(do\(\)|don\'t\(\)|mul\(\d{1,3},\d{1,3}\))/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $instruction = $match[0];

                    if ($instruction === 'do()') {
                        $isEnabled = true;
                    } elseif ($instruction === "don't()") {
                        $isEnabled = false;
                    } elseif ($this->isMultiplicationInstruction($instruction, $isEnabled, $result)) {
                        $totalSum += $result;
                    }
                }
            }
        }

        return $totalSum;
    }

    /**
     * Validates and parses a multiplication instruction.
     *
     * @param string $instruction The instruction string to parse.
     * @param bool $isEnabled Whether the instruction should be evaluated.
     * @param int|null $result Reference to store the multiplication result if valid.
     * @return bool True if the instruction is valid and evaluated, false otherwise.
     */
    private function isMultiplicationInstruction(string $instruction, bool $isEnabled, ?int &$result): bool
    {
        if (preg_match('/mul\(\s*(\d+)\s*,\s*(\d+)\s*\)/', $instruction, $matches) && $isEnabled) {
            $result = (int)$matches[1] * (int)$matches[2];
            return true;
        }
        $result = null;
        return false;
    }
}

// Main execution flow
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $mullItOver = new MullItOver($inputFile);
    echo "Valid multiplications: " . $mullItOver->computeAllMultiplicationsSum() . "\n";
    echo "Enabled multiplications: " . $mullItOver->computeEnabledMultiplicationsSum() . "\n";
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
