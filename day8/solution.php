<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 8: Resonant Collinearity
 *
 * Determines the number of unique antinodes generated by antenna placements in a grid
 * under different alignment rules.
 */
final class ResonantCollinearity
{
    /**
     * @var array<array<string>> The grid map of antennas and '.' for empty.
     */
    private array $grid;

    /**
     * @var int Number of rows in the grid.
     */
    private int $rows;

    /**
     * @var int Number of columns in the grid.
     */
    private int $cols;

    /**
     * @var array<string,array<array{int,int}>> Mapping from frequency to list of antenna positions.
     */
    private array $freqPositions = [];

    /**
     * Constructs the ResonantCollinearity instance from the given input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file is unreadable or invalid.
     */
    public function __construct(string $filename)
    {
        $this->grid = $this->loadGrid($filename);
        $this->rows = count($this->grid);
        $this->cols = ($this->rows > 0) ? count($this->grid[0]) : 0;
        $this->collectFrequencies();
    }

    /**
     * Loads the grid from the specified file.
     *
     * @param string $filename Path to the input file.
     * @return array<array<string>> The loaded grid.
     * @throws RuntimeException If the file cannot be read.
     */
    private function loadGrid(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException("File not readable: $filename");
        }

        return array_map(static fn($l) => str_split($l), $lines);
    }

    /**
     * Collects the antenna frequencies and their positions from the grid.
     */
    private function collectFrequencies(): void
    {
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                $ch = $this->grid[$r][$c];
                if ($ch !== '.') {
                    $this->freqPositions[$ch][] = [$r, $c];
                }
            }
        }
    }

    /**
     * Computes the number of unique antinodes generated by pairwise antenna alignment.
     *
     * @return int The number of unique antinodes.
     */
    public function computeCollinearAntinodes(): int
    {
        $uniqueAntinodes = [];

        foreach ($this->freqPositions as $positions) {
            $positionCount = count($positions);
            if ($positionCount < 2) {
                continue;
            }

            for ($i = 0; $i < $positionCount; $i++) {
                [$rowA, $colA] = $positions[$i];
                for ($j = $i + 1; $j < $positionCount; $j++) {
                    [$rowB, $colB] = $positions[$j];

                    $this->addAntinode($uniqueAntinodes, $rowA, $colA, $rowB, $colB);
                    $this->addAntinode($uniqueAntinodes, $rowB, $colB, $rowA, $colA);
                }
            }
        }

        return count($uniqueAntinodes);
    }

    /**
     * Adds an antinode to the set if it lies within the grid bounds.
     *
     * @param array<string, bool> $uniqueAntinodes
     * @param int $row1 Starting row.
     * @param int $col1 Starting column.
     * @param int $row2 Second row.
     * @param int $col2 Second column.
     */
    private function addAntinode(array &$uniqueAntinodes, int $row1, int $col1, int $row2, int $col2): void
    {
        $row = 2 * $row2 - $row1;
        $col = 2 * $col2 - $col1;

        if ($row >= 0 && $row < $this->rows && $col >= 0 && $col < $this->cols) {
            $uniqueAntinodes["$row,$col"] = true;
        }
    }

    /**
     * Computes the number of unique antinodes generated by linear alignment of antennas.
     *
     * @return int The number of unique antinodes.
     */
    public function computeLinearAntinodes(): int
    {
        $uniqueAntinodes = [];

        foreach ($this->freqPositions as $positions) {
            $lines = $this->calculateLines($positions);

            foreach ($lines as $lineKey => $linePoints) {
                $this->enumerateLineAntinodes($uniqueAntinodes, $linePoints, $lineKey);
            }
        }

        return count($uniqueAntinodes);
    }

    /**
     * Calculates all unique lines formed by the given positions.
     *
     * @param array<array<int>> $positions
     * @return array<string, array<array<int>>>
     */
    private function calculateLines(array $positions): array
    {
        $lines = [];
        $positionCount = count($positions);

        for ($i = 0; $i < $positionCount; $i++) {
            [$x1, $y1] = $positions[$i];
            for ($j = $i + 1; $j < $positionCount; $j++) {
                [$x2, $y2] = $positions[$j];

                $dx = $x2 - $x1;
                $dy = $y2 - $y1;
                $gcd = gmp_intval(gmp_gcd($dx, $dy));
                $dx /= $gcd;
                $dy /= $gcd;

                if ($dx < 0 || ($dx === 0 && $dy < 0)) {
                    $dx = -$dx;
                    $dy = -$dy;
                }

                $key = "$dx,$dy," . ($dy * $x1 - $dx * $y1);
                $lines[$key][] = [$x1, $y1];
                $lines[$key][] = [$x2, $y2];
            }
        }

        return $lines;
    }

    /**
     * Enumerates antinodes along the line defined by the key and points.
     *
     * @param array<string, bool> $uniqueAntinodes
     * @param array<array<int>> $linePoints
     * @param string $lineKey
     */
    private function enumerateLineAntinodes(array &$uniqueAntinodes, array $linePoints, string $lineKey): void
    {
        $linePoints = array_unique($linePoints, SORT_REGULAR);
        usort($linePoints, fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        [$dx, $dy] = array_map('intval', explode(',', $lineKey, 3));

        [$anchorX, $anchorY] = $linePoints[0];

        for ($t = 0; ; $t++) {
            $row = $anchorX + $t * $dx;
            $col = $anchorY + $t * $dy;
            if ($row < 0 || $row >= $this->rows || $col < 0 || $col >= $this->cols) break;
            $uniqueAntinodes["$row,$col"] = true;
        }

        for ($t = -1; ; $t--) {
            $row = $anchorX + $t * $dx;
            $col = $anchorY + $t * $dy;
            if ($row < 0 || $row >= $this->rows || $col < 0 || $col >= $this->cols) break;
            $uniqueAntinodes["$row,$col"] = true;
        }
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');
try {
    $resonance = new ResonantCollinearity($inputFile);
    echo "Collinear Antinodes: " . $resonance->computeCollinearAntinodes() . PHP_EOL;
    echo "Linear Antinodes: " . $resonance->computeLinearAntinodes() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
