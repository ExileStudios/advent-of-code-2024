<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 16: Reindeer Maze
 *
 * Implements the A* pathfinding algorithm to navigate through a maze.
 * It identifies the lowest score path from the start ('S') to the end ('E')
 * and determines all tiles that are part of any optimal path.
 * 
 */
final class ReindeerMaze
{
    /**
     * Directions represented as [row_offset, col_offset].
     * @var array<int, array<int>> Directions represented as [row_offset, col_offset].
     */
    private const DIRECTIONS = [
        [0, 1],
        [1, 0],
        [0, -1],
        [-1, 0],
    ];

    /**
     * @var int Cost associated with moving to an adjacent tile.
     */
    private const MOVE_COST = 1;

    /**
     * @var array<array<string>> The maze grid, where each cell is represented by a character.
     */
    private array $maze;

    /**
     * @var array<int> Starting position as [row, column].
     */
    private array $start;

    /**
     * @var array<int> Ending position as [row, column].
     */
    private array $end;

    /**
     * ReindeerMaze constructor.
     *
     * @param string $filename Path to the input maze file.
     * @throws RuntimeException If the file cannot be accessed or parsed.
     */
    public function __construct(string $filename)
    {
        $this->maze = $this->parseMaze($filename);
        $this->start = $this->findTile('S');
        $this->end = $this->findTile('E');
    }

    /**
     * Parses the maze from the specified file.
     *
     * @param string $filename Path to the input maze file.
     * @return array<array<string>> The parsed maze grid.
     * @throws RuntimeException If the file cannot be accessed or read.
     */
    private function parseMaze(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read the file: $filename");
        }

        return array_map('str_split', $lines);
    }

    /**
     * Finds the coordinates of a specific tile in the maze.
     *
     * @param string $tile The character representing the tile to find ('S' or 'E').
     * @return array<int> Coordinates as [row, column].
     * @throws RuntimeException If the specified tile is not found in the maze.
     */
    private function findTile(string $tile): array
    {
        foreach ($this->maze as $rowIndex => $row) {
            $colIndex = array_search($tile, $row, true);
            if ($colIndex !== false) {
                return [$rowIndex, (int)$colIndex];
            }
        }
        throw new RuntimeException("Tile '$tile' not found in the maze.");
    }

    /**
     * Executes the A* pathfinding algorithm to determine the lowest score path
     * and collects all tiles that are part of any best path.
     *
     * @param array<string, bool>|null &$bestPathTiles Reference to an array that will be populated
     *                                              with tiles that are part of any best path.
     * @return int The lowest score achieved in the pathfinding.
     */
    public function aStarPathfinding(?array &$bestPathTiles = null): int
    {
        $start = [$this->start[0], $this->start[1], 0];
        $openSet = new SplPriorityQueue();
        $openSet->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $openSet->insert($start, 0);

        $gScores = [];
        $startKey = serialize($start);
        $gScores[$startKey] = 0;

        $cameFrom = [];
        $endNodes = [];
        $lowestScore = PHP_INT_MAX;

        // Track processed states to avoid reprocessing
        $processed = [];

        while (!$openSet->isEmpty()) {
            $current = $openSet->extract();
            [$row, $col, $dir] = $current['data'];
            $currentKey = serialize([$row, $col, $dir]);

            // Skip if the current state has already been processed
            if (isset($processed[$currentKey])) {
                continue;
            }

            // Mark the current state as processed
            $processed[$currentKey] = true;

            // Check if the end has been reached
            if ([$row, $col] === $this->end) {
                $currentScore = $gScores[$currentKey];
                if ($currentScore <= $lowestScore) {
                    $lowestScore = $currentScore;
                    $endNodes[$currentKey] = [$currentScore, $cameFrom];
                }
                continue;
            }

            // Explore neighboring tiles in all possible directions
            foreach (self::DIRECTIONS as $newDir => $delta) {
                $newRow = $row + $delta[0];
                $newCol = $col + $delta[1];

                if (!$this->isValidTile([$newRow, $newCol])) {
                    continue;
                }

                $rotationCost = $this->rotationCost($dir, $newDir);
                $tentativeScore = $gScores[$currentKey] + self::MOVE_COST + $rotationCost;

                $neighbor = [$newRow, $newCol, $newDir];
                $neighborKey = serialize($neighbor);

                // Update scores and predecessors if a better path is found
                if (!isset($gScores[$neighborKey]) || $tentativeScore < $gScores[$neighborKey]) {
                    $gScores[$neighborKey] = $tentativeScore;
                    $cameFrom[$neighborKey] = [$currentKey];
                    $priority = -($tentativeScore + $this->heuristic([$newRow, $newCol], $this->end));
                    $openSet->insert($neighbor, $priority);
                } elseif ($tentativeScore === $gScores[$neighborKey]) {
                    // If the score is equal, add the current state as an additional predecessor
                    if (!in_array($currentKey, $cameFrom[$neighborKey], true)) {
                        $cameFrom[$neighborKey][] = $currentKey;
                    }
                }
            }
        }

        // Reconstruct all best paths and collect the tiles
        if ($bestPathTiles !== null && !empty($endNodes)) {
            foreach ($endNodes as $endKey => [$score, $pathInfo]) {
                if ($score === $lowestScore) {
                    $stack = [unserialize($endKey)];
                    $processedReconstruction = [];

                    while (!empty($stack)) {
                        $current = array_pop($stack);
                        [$row, $col, $dir] = $current;
                        $currentStateKey = serialize($current);

                        // Skip if this state has already been processed in reconstruction
                        if (isset($processedReconstruction[$currentStateKey])) {
                            continue;
                        }

                        // Mark this state as processed in reconstruction
                        $processedReconstruction[$currentStateKey] = true;

                        // Mark the tile as part of the best path
                        $posKey = serialize([$row, $col]);
                        $bestPathTiles[$posKey] = true;

                        // Add all predecessors to the stack for further reconstruction
                        if (isset($cameFrom[$currentStateKey])) {
                            foreach ($cameFrom[$currentStateKey] as $predecessorKey) {
                                $predecessor = unserialize($predecessorKey);
                                $stack[] = $predecessor;
                            }
                        }
                    }
                }
            }

            // Count the total number of unique tiles in all best paths
            $lowestScore = $lowestScore;
        }

        return $lowestScore;
    }

    /**
     * Determines if a given tile is valid (i.e., within maze bounds and not a wall).
     *
     * @param array<int> $tile Coordinates as [row, column].
     * @return bool True if the tile is valid, false otherwise.
     */
    private function isValidTile(array $tile): bool
    {
        [$row, $col] = $tile;
        return isset($this->maze[$row][$col]) && $this->maze[$row][$col] !== '#';
    }

    /**
     * Calculates the cost associated with rotating from one direction to another.
     *
     * @param int $currentDir Current direction index.
     * @param int $newDir New direction index.
     * @return int The rotation cost.
     */
    private function rotationCost(int $currentDir, int $newDir): int
    {
        $diff = abs($newDir - $currentDir);
        return ($diff === 0) ? 0 : (($diff === 2) ? 2000 : 1000);
    }

    /**
     * Heuristic function for A* algorithm (Manhattan distance).
     *
     * @param array<int> $current Current position as [row, column].
     * @param array<int> $goal Goal position as [row, column].
     * @return int The heuristic cost.
     */
    private function heuristic(array $current, array $goal): int
    {
        return abs($current[0] - $goal[0]) + abs($current[1] - $goal[1]);
    }

    /**
     * Computes the lowest score from start to end using A* pathfinding.
     *
     * @return int The lowest score achieved.
     */
    public function computeLowestScore(): int
    {
        return $this->aStarPathfinding();
    }

    /**
     * Computes the number of unique tiles that are part of any best path.
     *
     * @return int The count of unique best path tiles.
     */
    public function computeBestPathTiles(): int
    {
        $bestPathTiles = [];
        $this->aStarPathfinding($bestPathTiles);
        return count($bestPathTiles ?? []);
    }

    /**
     * Visualizes the best path tiles on the maze by marking them with 'O'.
     *
     * @param array<string, bool> $bestPathTiles Array of serialized tile positions.
     */
    public function visualizeBestPath(array $bestPathTiles): void
    {
        $mazeCopy = $this->maze;
        foreach ($bestPathTiles as $posKey => $_) {
            [$row, $col] = unserialize($posKey);
            if ($mazeCopy[$row][$col] === '.') {
                $mazeCopy[$row][$col] = 'O';
            }
            // Ensure 'S' and 'E' remain unchanged
            if ($mazeCopy[$row][$col] === 'S' || $mazeCopy[$row][$col] === 'E') {
                $mazeCopy[$row][$col] = $mazeCopy[$row][$col];
            }
        }

        // Output the maze with best path tiles marked as 'O'
        foreach ($mazeCopy as $row) {
            echo implode('', $row) . PHP_EOL;
        }
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $reindeerMaze = new ReindeerMaze($inputFile);
    echo "Lowest Score: " . $reindeerMaze->computeLowestScore() . PHP_EOL;
    echo "Best Path Tiles: " . $reindeerMaze->computeBestPathTiles() . PHP_EOL;

    // Optional: Visualize the best path tiles
    // Uncomment the following lines to see the maze with best path tiles marked as 'O'
    /*
    echo "Maze with Best Path Tiles (marked as 'O'):\n";
    $bestPathTiles = [];
    $reindeerMaze->aStarPathfinding($bestPathTiles);
    $reindeerMaze->visualizeBestPath($bestPathTiles);
    */
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
