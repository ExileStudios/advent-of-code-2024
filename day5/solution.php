<?php

/**
 * Advent of Code 2024 - Day 5: Print Queue
 *
 * Identifies which updates are already correctly ordered according to the provided rules.
 * Sums the middle pages from all such valid updates.
 *
 * Takes all incorrectly ordered updates and uses the rules to reorder them.
 * After reordering, sums the middle pages of these fixed invalid updates.
 */
class PrintQueue
{
    /**
     * @var array<array{0:int,1:int}> The ordering rules, each a pair [X, Y],
     *                               indicating X must come before Y if both are present.
     */
    private array $rules;

    /**
     * @var array<array<int>> The list of updates, each update is an array of page numbers.
     */
    private array $updates;

    /**
     * Constructor loads rules and updates from the given input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be accessed or read.
     */
    public function __construct(string $filename)
    {
        [$this->rules, $this->updates] = $this->parseInput($filename);
    }

    /**
     * Parses the input file into rules and updates.
     * The file is expected to have rules before a blank line and updates after.
     *
     * @param string $filename Path to the input file.
     * @return array{0:array<array{0:int,1:int}>,1:array<array<int>>} [rules, updates]
     * @throws RuntimeException If the file cannot be accessed or read.
     */
    private function parseInput(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException("File not readable: $filename");
        }

        $rules = [];
        $updates = [];
        $isRules = true;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $isRules = false;
                continue;
            }

            if ($isRules) {
                if (strpos($trimmed, '|') !== false) {
                    [$x, $y] = explode('|', $trimmed);
                    $rules[] = [(int)$x, (int)$y];
                }
            } else {
                $updates[] = array_map('intval', explode(',', $trimmed));
            }
        }

        return [$rules, $updates];
    }

    /**
     * Checks if a given update follows all applicable ordering rules.
     *
     * @param array<int> $update The update array of pages.
     * @return bool True if valid according to the rules, false otherwise.
     */
    private function isValidOrder(array $update): bool
    {
        $positions = array_flip($update);

        foreach ($this->rules as [$x, $y]) {
            if (isset($positions[$x], $positions[$y]) && $positions[$x] > $positions[$y]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the middle page of an update.
     * The middle is at index floor(count/2).
     *
     * @param array<int> $update The update array of pages.
     * @return int The middle page, or 0 if empty.
     */
    private function findMiddlePage(array $update): int
    {
        if (empty($update)) {
            return 0;
        }

        return $update[(int)floor(count($update) / 2)];
    }

    /**
     * Builds a graph for topological sorting based on the rules relevant to the given update.
     *
     * @param array<int> $update The update array of pages.
     * @return array{0:array<int,array<int>>,1:array<int,int>} [graph, inDegree]
     */
    private function buildGraph(array $update): array
    {
        $pagesInUpdate = array_flip($update);
        $graph = [];
        $inDegree = [];

        foreach ($update as $page) {
            $graph[$page] = [];
            $inDegree[$page] = 0;
        }

        foreach ($this->rules as [$x, $y]) {
            if (isset($pagesInUpdate[$x], $pagesInUpdate[$y])) {
                $graph[$x][] = $y;
                $inDegree[$y]++;
            }
        }

        return [$graph, $inDegree];
    }

    /**
     * Reorders an update using topological sort to satisfy the rules.
     *
     * @param array<int> $update The update array of pages.
     * @return array<int> The reordered update array.
     */
    private function reorderUpdate(array $update): array
    {
        [$graph, $inDegree] = $this->buildGraph($update);

        $queue = [];
        foreach ($inDegree as $node => $deg) {
            if ($deg === 0) {
                $queue[] = $node;
            }
        }

        $result = [];
        while (!empty($queue)) {
            $current = array_shift($queue);
            $result[] = $current;

            foreach ($graph[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }

        return (count($result) === count($update)) ? $result : $update;
    }

    /**
     * Computes the sum of middle pages from all valid updates.
     *
     * @return int The sum of middle pages of valid updates.
     */
    public function computeMiddleSumOfValid(): int
    {
        $sum = 0;
        foreach ($this->updates as $update) {
            if (!empty($update) && $this->isValidOrder($update)) {
                $sum += $this->findMiddlePage($update);
            }
        }

        return $sum;
    }

    /**
     * Computes the sum of middle pages from invalid updates after reordering them.
     *
     * @return int The sum of middle pages of reordered invalid updates.
     */
    public function computeMiddleSumOfFixedInvalid(): int
    {
        $sum = 0;
        foreach ($this->updates as $update) {
            if (!empty($update) && !$this->isValidOrder($update)) {
                $corrected = $this->reorderUpdate($update);
                $sum += $this->findMiddlePage($corrected);
            }
        }

        return $sum;
    }
}

// Main execution flow
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $printQueue = new PrintQueue($inputFile);
    echo "Sum of middle pages (valid updates): " . $printQueue->computeMiddleSumOfValid() . "\n";
    echo "Sum of middle pages (fixed invalid updates): " . $printQueue->computeMiddleSumOfFixedInvalid() . "\n";
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
