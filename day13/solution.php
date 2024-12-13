<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 13: Claw Contraption
 *
 * Calculates the minimum tokens required to win prizes from claw machines
 * using the Extended Euclidean Algorithm and linear Diophantine equations.
 * 
 */
final class ClawContraptionSolver
{
    /**
     * @var array<int, array<string, int>> Parsed configurations for all claw machines.
     */
    private array $machines;

    /**
     * Constructor loads claw machine configurations from the input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read.
     */
    public function __construct(string $filename)
    {
        $this->machines = $this->parseInput($filename);
    }

    /**
     * Parses the input file into machine configurations.
     *
     * @param string $filename Path to the input file.
     * @return array<int, array<string, int>> Parsed machine data.
     * @throws RuntimeException If the file cannot be read or the input format is invalid.
     */
    private function parseInput(string $filename): array
    {
        $lines = $this->readLines($filename);

        $machines = [];
        foreach (array_chunk($lines, 3) as $chunk) {
            if (count($chunk) !== 3) {
                throw new InvalidArgumentException("Malformed input: Each machine requires exactly 3 lines.");
            }

            $aMatch = $this->matchPattern('/Button A: X\+(\d+), Y\+(\d+)/', $chunk[0], 'Button A');
            $bMatch = $this->matchPattern('/Button B: X\+(\d+), Y\+(\d+)/', $chunk[1], 'Button B');
            $prizeMatch = $this->matchPattern('/Prize: X=(\d+), Y=(\d+)/', $chunk[2], 'Prize');

            $machines[] = [
                'aX' => (int)$aMatch[1],
                'aY' => (int)$aMatch[2],
                'bX' => (int)$bMatch[1],
                'bY' => (int)$bMatch[2],
                'pX' => (int)$prizeMatch[1],
                'pY' => (int)$prizeMatch[2],
            ];
        }

        return $machines;
    }

    /**
     * Reads lines from a file, ensuring no empty or invalid lines.
     *
     * @param string $filename Path to the input file.
     * @return array<int, string> Lines from the file.
     * @throws RuntimeException If the file cannot be read.
     */
    private function readLines(string $filename): array
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read file: $filename");
        }
        return $lines;
    }

    /**
     * Matches a line against a pattern and validates the result.
     *
     * @param string $pattern The regular expression pattern.
     * @param string $line The line to match.
     * @param string $context Context for the error message.
     * @return array<int, string> Match results.
     * @throws InvalidArgumentException If the line does not match the pattern.
     */
    private function matchPattern(string $pattern, string $line, string $context): array
    {
        if (!preg_match($pattern, $line, $matches) || !isset($matches[1], $matches[2])) {
            throw new InvalidArgumentException("Invalid input format for $context: $line");
        }
        return $matches;
    }

    /**
     * Computes the minimum tokens required to win all possible prizes.
     *
     * @return int The minimum tokens required.
     */
    public function computeMinimumTokens(): int
    {
        $totalTokens = 0;

        foreach ($this->machines as $machine) {
            $tokens = $this->calculateTokensForMachine(
                $machine['aX'], $machine['aY'],
                $machine['bX'], $machine['bY'],
                $machine['pX'], $machine['pY']
            );

            if ($tokens !== null) {
                $totalTokens += $tokens;
            }
        }

        return $totalTokens;
    }

    /**
     * Solves the linear Diophantine equations to calculate the minimum tokens for a single machine.
     *
     * @param int $aX X increment for Button A.
     * @param int $aY Y increment for Button A.
     * @param int $bX X increment for Button B.
     * @param int $bY Y increment for Button B.
     * @param int $pX Prize X coordinate.
     * @param int $pY Prize Y coordinate.
     * @return int|null Minimum tokens required, or null if no solution exists.
     */
    private function calculateTokensForMachine(int $aX, int $aY, int $bX, int $bY, int $pX, int $pY): ?int
    {
        // Calculate determinant to solve the linear system
        $det = $aX * $bY - $aY * $bX;
        if ($det === 0) {
            return null; // No solution if determinant is zero (parallel lines)
        }

        $aCount = ($pX * $bY - $pY * $bX) / $det;
        $bCount = ($pY * $aX - $pX * $aY) / $det;

        if (!is_int($aCount) || !is_int($bCount) || $aCount < 0 || $bCount < 0) {
            return null; // Solution must be non-negative integers
        }

        return 3 * (int)$aCount + (int)$bCount;
    }

    /**
     * Computes the minimum tokens required with global coordinate offsets applied.
     *
     * @return int The minimum tokens required across all machines.
     */
    public function computeOffsetCoordinateTokens(): int
    {
        $totalTokens = 0;
        $offset = 10000000000000;

        foreach ($this->machines as $machine) {
            $tokens = $this->calculateTokensForMachine(
                $machine['aX'], $machine['aY'],
                $machine['bX'], $machine['bY'],
                $machine['pX'] + $offset, $machine['pY'] + $offset
            );

            if ($tokens !== null) {
                $totalTokens += $tokens;
            }
        }

        return $totalTokens;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $solver = new ClawContraptionSolver($inputFile);
    $minimumTokens = $solver->computeMinimumTokens();
    echo "Minimum Tokens to Win All Possible Prizes: {$minimumTokens}" . PHP_EOL;

    $globalMinimumTokens = $solver->computeOffsetCoordinateTokens();
    echo "Minimum Tokens with Global Offset: {$globalMinimumTokens}" . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
