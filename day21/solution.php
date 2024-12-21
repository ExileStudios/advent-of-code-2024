<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 21: Keypad Conundrum
 *
 * Solves the keypad pathfinding challenge, calculating the total complexity
 * of navigating a numeric and directional keypad for given codes.
 * 
 */
final class KeypadConundrum
{
    /**
     * Numeric keypad configuration.
     */
    private const NUMERIC_PAD = [
        '0' => [['2', '^'], ['A', '>']],
        '1' => [['2', '>'], ['4', '^']],
        '2' => [['0', 'v'], ['1', '<'], ['3', '>'], ['5', '^']],
        '3' => [['2', '<'], ['6', '^'], ['A', 'v']],
        '4' => [['1', 'v'], ['5', '>'], ['7', '^']],
        '5' => [['2', 'v'], ['4', '<'], ['6', '>'], ['8', '^']],
        '6' => [['3', 'v'], ['5', '<'], ['9', '^']],
        '7' => [['4', 'v'], ['8', '>']],
        '8' => [['5', 'v'], ['7', '<'], ['9', '>']],
        '9' => [['6', 'v'], ['8', '<']],
        'A' => [['0', '<'], ['3', '^']],
    ];

    /**
     * Directional keypad configuration.
     */
    private const DIRECTIONAL_PAD = [
        '^' => [['A', '>'], ['v', 'v']],
        '<' => [['v', '>']],
        'v' => [['<', '<'], ['^', '^'], ['>', '>']],
        '>' => [['v', '<'], ['A', '^']],
        'A' => [['^', '<'], ['>', 'v']],
    ];

    /**
     * @var string[] List of codes to process.
     */
    private array $codes;

    /**
     * @var array<string, array<string>> Cached paths to avoid recomputation.
     */
    private array $pathCache = [];

    /**
     * Initializes the Keypad Conundrum solver with input data.
     *
     * @param string $filename Path to the input file containing codes.
     * @throws RuntimeException If the file cannot be read.
     */
    public function __construct(string $filename)
    {
        $this->codes = $this->loadCodes($filename);
    }

    /**
     * Loads the codes from the input file.
     *
     * @param string $filename Path to the input file.
     * @return string[] Array of codes.
     * @throws RuntimeException If the file cannot be read.
     */
    private function loadCodes(string $filename): array
    {
        $fileContent = file_get_contents($filename);
        if ($fileContent === false) {
            throw new RuntimeException("Unable to read input file: $filename");
        }
        return array_filter(array_map('trim', explode("\n", $fileContent)));
    }

    /**
     * Performs a breadth-first search (BFS) to find all shortest paths between two keys.
     *
     * @param string $start The starting key.
     * @param string $target The target key.
     * @param array<int|string, list<list<string>>> $pad The keypad configuration to use.
     * @return string[] List of shortest paths.
     */
    private function findPaths(string $start, string $target, array $pad): array
    {
        $cacheKey = "$start$target" . ($pad === self::NUMERIC_PAD ? 'N' : 'D');
        if (isset($this->pathCache[$cacheKey])) {
            return $this->pathCache[$cacheKey];
        }

        $queue = new SplQueue();
        $queue->enqueue([$start, []]);
        $seen = [$start];
        $shortest = null;
        $results = [];

        while (!$queue->isEmpty()) {
            [$current, $path] = $queue->dequeue();

            if ($current === $target) {
                if ($shortest === null) {
                    $shortest = count($path);
                }
                if (count($path) === $shortest) {
                    $results[] = implode('', array_merge($path, ['A']));
                }
                continue;
            }

            if ($shortest !== null && count($path) >= $shortest) {
                continue;
            }

            foreach ($pad[$current] ?? [] as [$next, $direction]) {
                if (!in_array($next, $seen, true)) {
                    $queue->enqueue([$next, array_merge($path, [$direction])]);
                }
            }
        }

        $this->pathCache[$cacheKey] = $results;
        return $results;
    }

    /**
     * Recursively calculates the shortest sequence complexity for a given path.
     *
     * @param string $sequence The sequence of keys to follow.
     * @param int $depth The remaining depth of recursion.
     * @param bool $isDirectional Whether to use the directional pad configuration.
     * @param array<string, int> $memo A memoization cache for previously computed results.
     * @return int The total complexity of the sequence.
     */
    private function calculateSequenceComplexity(string $sequence, int $depth, bool $isDirectional = false, array &$memo = []): int
    {
        $pad = $isDirectional ? self::DIRECTIONAL_PAD : self::NUMERIC_PAD;
        $cacheKey = "$sequence:$depth";
        if (isset($memo[$cacheKey])) {
            return $memo[$cacheKey];
        }

        $result = 0;
        $sequence = 'A' . $sequence;

        for ($i = 0; $i < strlen($sequence) - 1; $i++) {
            $current = $sequence[$i];
            $next = $sequence[$i + 1];
            $paths = $this->findPaths($current, $next, $pad);

            if (empty($paths)) {
                $memo[$cacheKey] = PHP_INT_MAX; // No valid path
                return $memo[$cacheKey];
            }

            if ($depth === 0) {
                $result += min(array_map('strlen', $paths));
            } else {
                $lengths = array_map(function ($path) use ($depth, &$memo) {
                    return $this->calculateSequenceComplexity($path, $depth - 1, true, $memo);
                }, $paths);
                
                $result += min($lengths);
            }
        }

        $memo[$cacheKey] = $result;
        return $result;
    }

    /**
     * Computes the total complexity for all codes in the input.
     *
     * @return int The total complexity.
     */
    public function computeTotalComplexity(): int
    {
        $total = 0;
        foreach ($this->codes as $code) {
            $numericValue = (int)substr($code, 0, 3);
            if ($numericValue === 0) {
                continue; // Skip invalid or malformed codes.
            }

            $complexity = $this->calculateSequenceComplexity($code, 2);
            $total += $complexity * $numericValue;
        }

        return $total;
    }

    /**
     * Computes the total button presses needed to type each code with 25 directional keypads.
     *
     * @return int The sum of the complexities for all codes.
     */
    public function computeRobotChainComplexity(): int
    {
        $total = 0;
        $memo = [];
        foreach ($this->codes as $code) {
            $numericValue = (int)substr($code, 0, 3);
            if ($numericValue === 0) {
                continue; // Skip invalid or malformed codes.
            }

            $complexity = $this->calculateSequenceComplexity($code, 25, false, $memo);
            $total += $complexity * $numericValue;
        }

        return $total;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $solver = new KeypadConundrum($inputFile);
    echo "Total Complexity: " . $solver->computeTotalComplexity() . PHP_EOL;
    echo "Robot Chain Complexity: " . $solver->computeRobotChainComplexity() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
