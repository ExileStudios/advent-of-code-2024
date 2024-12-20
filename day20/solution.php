<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 20: Race Condition
 *
 * Computes shortest paths in a grid and identifies potential shortcuts (cheats)
 * that save significant time under specified constraints.
 * 
 */
final class RaceCondition
{
    /**
     * @var array<array<string>> Grid representation of the map.
     */
    private array $grid;

    /**
     * @var array{int, int} Starting coordinates in the grid.
     */
    private array $start;

    /**
     * @var array{int, int} Ending coordinates in the grid.
     */
    private array $end;

    /**
     * @var array<array<int>> Precomputed distances from the start position.
     */
    private array $baseDistances;

    /**
     * @var array<array<int>> Precomputed distances from the end position.
     */
    private array $endDistances;

    /**
     * @var int Number of rows in the grid.
     */
    private int $rows;

    /**
     * @var int Number of columns in the grid.
     */
    private int $cols;

    /**
     * @var int Shortest path length under normal conditions.
     */
    private int $normalShortestPath;

    /**
     * Direction vectors for grid traversal.
     */
    private const DIRECTIONS = [[0, 1], [1, 0], [0, -1], [-1, 0]];

    /**
     * Constructs the RaceCondition instance by loading the grid and precomputing distances.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read or parsed.
     */
    public function __construct(string $filename)
    {
        $this->grid = $this->parseGrid($filename);
        $this->rows = count($this->grid);
        $this->cols = count($this->grid[0]);
        $this->start = $this->findTile('S');
        $this->end = $this->findTile('E');

        $this->baseDistances = $this->bfs($this->start);
        $this->endDistances = $this->bfs($this->end);
        $this->normalShortestPath = $this->baseDistances[$this->end[0]][$this->end[1]];
    }

    /**
     * Parses the grid from the input file.
     *
     * @param string $filename Path to the input file.
     * @return array<array<string>> Parsed grid.
     * @throws RuntimeException If the file cannot be read.
     */
    private function parseGrid(string $filename): array
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        return array_map('str_split', $lines);
    }

    /**
     * Finds the coordinates of a specific tile in the grid.
     *
     * @param string $tile The tile to locate (e.g., 'S', 'E').
     * @return array{int, int} Coordinates of the tile.
     * @throws RuntimeException If the tile is not found.
     */
    private function findTile(string $tile): array
    {
        foreach ($this->grid as $r => $row) {
            if (($c = array_search($tile, $row, true)) !== false) {
                return [$r, $c];
            }
        }
        throw new RuntimeException("Tile '$tile' not found");
    }

    /**
     * Computes distances from a starting position using breadth-first search (BFS).
     *
     * @param array{int, int} $start Starting coordinates.
     * @return array<array<int>> Distance grid from the starting position.
     */
    private function bfs(array $start): array
    {
        $distances = array_fill(0, $this->rows, array_fill(0, $this->cols, PHP_INT_MAX));
        $queue = new SplQueue();

        $distances[$start[0]][$start[1]] = 0;
        $queue->enqueue($start);

        while (!$queue->isEmpty()) {
            [$r, $c] = $queue->dequeue();
            $dist = $distances[$r][$c];

            foreach (self::DIRECTIONS as [$dr, $dc]) {
                $nr = $r + $dr;
                $nc = $c + $dc;

                if ($this->isValidPos($nr, $nc) &&
                    $this->isTrack($nr, $nc) &&
                    $distances[$nr][$nc] === PHP_INT_MAX) {
                    $distances[$nr][$nc] = $dist + 1;
                    $queue->enqueue([$nr, $nc]);
                }
            }
        }

        return $distances;
    }

    /**
     * Checks if a grid position is within bounds.
     *
     * @param int $r Row index.
     * @param int $c Column index.
     * @return bool True if the position is valid.
     */
    private function isValidPos(int $r, int $c): bool
    {
        return $r >= 0 && $r < $this->rows && $c >= 0 && $c < $this->cols;
    }

    /**
     * Checks if a grid position is traversable (not a wall).
     *
     * @param int $r Row index.
     * @param int $c Column index.
     * @return bool True if the position is a track.
     */
    private function isTrack(int $r, int $c): bool
    {
        return $this->grid[$r][$c] !== '#';
    }

    /**
     * Processes cheats that save at least 100 units of time.
     *
     * @param int $maxSteps Maximum allowed cheat steps.
     * @return int Number of cheats that save ≥100 time units.
     */
    private function processCheats(int $maxSteps): int
    {
        $uniqueCheats = [];
        $cheatsOver100 = 0;

        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                if (!$this->isTrack($r, $c) || $this->baseDistances[$r][$c] === PHP_INT_MAX) {
                    continue;
                }

                for ($er = max(0, $r - $maxSteps); $er <= min($this->rows - 1, $r + $maxSteps); $er++) {
                    for ($ec = max(0, $c - $maxSteps); $ec <= min($this->cols - 1, $c + $maxSteps); $ec++) {
                        if (!$this->isTrack($er, $ec) || $this->endDistances[$er][$ec] === PHP_INT_MAX) {
                            continue;
                        }

                        $cheatDist = abs($er - $r) + abs($ec - $c);
                        if ($cheatDist > $maxSteps) {
                            continue;
                        }

                        $totalPath = $this->baseDistances[$r][$c] + $cheatDist + $this->endDistances[$er][$ec];
                        $timeSaved = $this->normalShortestPath - $totalPath;

                        $cheatKey = "$r,$c:$er,$ec";
                        if ($timeSaved >= 100 && !isset($uniqueCheats[$cheatKey])) {
                            $uniqueCheats[$cheatKey] = true;
                            $cheatsOver100++;
                        }
                    }
                }
            }
        }

        return $cheatsOver100;
    }

    /**
     * Counts cheats saving ≥100 time units using 2 steps.
     *
     * @return int Number of cheats saving ≥100 time units.
     */
    public function countCheatsSavingAtLeast100(): int
    {
        return $this->processCheats(2);
    }

    /**
     * Counts extended cheats saving ≥100 time units using 20 steps.
     *
     * @return int Number of extended cheats saving ≥100 time units.
     */
    public function countCheatsSavingAtLeast100Extended(): int
    {
        return $this->processCheats(20);
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $raceCondition = new RaceCondition($inputFile);
    echo "Part 1 - Cheats saving ≥100 time units: " . $raceCondition->countCheatsSavingAtLeast100() . PHP_EOL;
    echo "Part 2 - Extended cheats saving ≥100 time units: " . $raceCondition->countCheatsSavingAtLeast100Extended() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
