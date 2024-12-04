<?php

/**
 * Advent of Code 2024 - Day 4: Ceres Search
 * Counts occurrences of the word "XMAS" and "X-MAS" patterns in a 2D word search grid.
 */
class CeresSearch
{
    /**
     * The 2D word search grid.
     *
     * @var array<array<string>>
     */
    private array $grid;

    /**
     * Number of rows in the grid.
     */
    private int $rows;

    /**
     * Number of columns in the grid.
     */
    private int $cols;

    /**
     * The word to search for.
     */
    private const WORD_XMAS = 'XMAS';

    /**
     * Directions for word searching (horizontal, vertical, diagonal).
     * Each direction is represented as [dx, dy].
     *
     * @var array<array<int>>
     */
    private const DIRECTIONS = [
        [0, 1],
        [1, 0],
        [0, -1],
        [-1, 0],
        [1, 1],
        [-1, -1],
        [1, -1],
        [-1, 1],
    ];

    /**
     * Possible MAS arrangements for the X-MAS pattern.
     *
     * @var array<array<string>>
     */
    private const MAS_ARRANGEMENTS = [
        ['M', 'S', 'M', 'S'],
        ['M', 'S', 'S', 'M'],
        ['S', 'M', 'M', 'S'],
        ['S', 'M', 'S', 'M'],
    ];

    /**
     * @param string $filename Path to the input file containing the word search grid.
     * @throws RuntimeException If the file cannot be found or read.
     */
    public function __construct(string $filename)
    {
        $this->grid = $this->loadGrid($filename);
        $this->rows = count($this->grid);
        $this->cols = ($this->rows > 0) ? count($this->grid[0]) : 0;
    }

    /**
     * @param string $filename Path to the input file.
     * @return array<array<string>> A 2D array representing the word search grid.
     * @throws RuntimeException If the file cannot be found or read.
     */
    private function loadGrid(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("File not readable: $filename");
        }

        return array_map(static fn(string $line): array => str_split($line), $lines);
    }

    /**
     * @return int The total count of "XMAS" occurrences.
     */
    public function countXMASWords(): int
    {
        if ($this->rows === 0) {
            return 0;
        }

        $total = 0;
        for ($row = 0; $row < $this->rows; $row++) {
            for ($col = 0; $col < $this->cols; $col++) {
                $total += $this->countXMASAtPosition($row, $col);
            }
        }

        return $total;
    }

    /**
     * @return int The total count of X-MAS patterns.
     */
    public function countXMASPatterns(): int
    {
        if ($this->rows === 0) {
            return 0;
        }

        $total = 0;
        for ($row = 0; $row < $this->rows; $row++) {
            for ($col = 0; $col < $this->cols; $col++) {
                $total += $this->countXMASPatternAtPosition($row, $col);
            }
        }

        return $total;
    }

    /**
     * @param int $row
     * @param int $col
     * @return int The total occurrences found at this position.
     */
    private function countXMASAtPosition(int $row, int $col): int
    {
        $count = 0;
        foreach (self::DIRECTIONS as [$dx, $dy]) {
            $count += $this->countMatches(self::WORD_XMAS, $row, $col, $dx, $dy);
        }

        return $count;
    }

    /**
     * @param string $word
     * @param int    $row
     * @param int    $col
     * @param int    $dx
     * @param int    $dy
     * @return int
     */
    private function countMatches(string $word, int $row, int $col, int $dx, int $dy): int
    {
        $length = strlen($word);
        $count = 0;

        while (true) {
            for ($i = 0; $i < $length; $i++) {
                $currentRow = $row + $i * $dx;
                $currentCol = $col + $i * $dy;

                if (
                    $currentRow < 0 || $currentRow >= $this->rows ||
                    $currentCol < 0 || $currentCol >= $this->cols ||
                    $this->grid[$currentRow][$currentCol] !== $word[$i]
                ) {
                    return $count;
                }
            }
            $count++;
            $row += $dx;
            $col += $dy;
        }
    }

    /**
     * @param int $row
     * @param int $col
     * @return int 1 if found, 0 otherwise.
     */
    private function countXMASPatternAtPosition(int $row, int $col): int
    {
        if (
            $row <= 0 || $row >= $this->rows - 1 ||
            $col <= 0 || $col >= $this->cols - 1 ||
            $this->grid[$row][$col] !== 'A'
        ) {
            return 0;
        }

        $diagonalPairs = [
            [[-1, -1, 1, 1], [-1, 1, 1, -1]],
            [[-1, 1, 1, -1], [-1, -1, 1, 1]]
        ];

        foreach ($diagonalPairs as [$first, $second]) {
            if ($this->validateDiagonalPattern([
                [$row + $first[0], $col + $first[1]],
                [$row + $first[2], $col + $first[3]],
                [$row + $second[0], $col + $second[1]],
                [$row + $second[2], $col + $second[3]]
            ])) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * Validates if the given diagonal coordinates form a valid X-MAS arrangement.
     *
     * @param array<array<int>> $coordinates Array of [[row, col], [row, col], ...]
     * @return bool
     */
    private function validateDiagonalPattern(array $coordinates): bool
    {
        $chars = $this->getCharsAtPositions($coordinates);
        return $chars !== null && in_array($chars, self::MAS_ARRANGEMENTS, true);
    }

    /**
     * Returns an array of characters from the grid for the given coordinates, or null if any are invalid.
     *
     * @param array<array<int>> $coordinates Array of [[row, col], ...]
     * @return array<string>|null
     */
    private function getCharsAtPositions(array $coordinates): ?array
    {
        $chars = [];
        foreach ($coordinates as [$r, $c]) {
            if (!isset($this->grid[$r][$c])) {
                return null;
            }
            $chars[] = $this->grid[$r][$c];
        }
        return $chars;
    }
}

// Main execution flow
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $ceresSearch = new CeresSearch($inputFile);
    echo "Occurrences of 'XMAS': " . $ceresSearch->countXMASWords() . "\n";
    echo "Occurrences of 'X-MAS': " . $ceresSearch->countXMASPatterns() . "\n";
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
