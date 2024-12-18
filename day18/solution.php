<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 18: RAM Run
 *
 * Simulates the effect of falling bytes in a 2D memory grid and determines the shortest path
 * from the top-left corner to the bottom-right corner while avoiding corrupted cells.
 * 
 */
final class RAMRun
{
    private const GRID_SIZE = 71;
    private const BYTE_COUNT = 1024;

    /** @var array<array<bool>> Memory grid representation (true = corrupted, false = safe). */
    private array $grid = [];

    /** @var array<array<int>> List of bytes to fall (X,Y coordinates). */
    private array $fallingBytes;

    /**
     * Constructor initializes the grid and loads falling bytes from the input file.
     *
     * @param string $filename The path to the input file.
     */
    public function __construct(string $filename)
    {
        $this->grid = array_fill(0, self::GRID_SIZE, array_fill(0, self::GRID_SIZE, false));
        $this->fallingBytes = $this->loadBytes($filename);
    }

    /**
     * Loads the falling bytes from the input file.
     *
     * @param string $filename The path to the input file.
     * @return array<array<int>> Array of byte positions [X, Y].
     * @throws RuntimeException If the file cannot be read or is invalid.
     */
    private function loadBytes(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        $bytes = [];
        foreach ($lines as $line) {
            $parts = explode(',', $line);
            if (count($parts) !== 2) {
                throw new RuntimeException("Invalid input format: $line");
            }

            $x = (int)$parts[0];
            $y = (int)$parts[1];
            if ($x < 0 || $x >= self::GRID_SIZE || $y < 0 || $y >= self::GRID_SIZE) {
                throw new RuntimeException("Invalid byte position: $line");
            }

            $bytes[] = [$x, $y];
        }

        return $bytes;
    }

    /**
     * Simulates the bytes falling onto the grid, marking corrupted cells.
     *
     * @param int $byteLimit The maximum number of bytes to simulate.
     */
    private function simulateFallingBytes(int $byteLimit): void
    {
        $count = min($byteLimit, count($this->fallingBytes));
        for ($i = 0; $i < $count; $i++) {
            [$x, $y] = $this->fallingBytes[$i];
            $this->grid[$y][$x] = true; // Mark the cell as corrupted.
        }
    }

    /**
     * Computes the shortest path from the top-left to the bottom-right corner.
     *
     * @return int|null The minimum number of steps, or null if no path exists.
     */
    public function computeShortestPath(): ?int
    {
        $queue = [[0, 0, 0]]; // [X, Y, Steps]
        $visited = array_fill(0, self::GRID_SIZE, array_fill(0, self::GRID_SIZE, false));
        $directions = [[1, 0], [0, 1], [-1, 0], [0, -1]]; // Right, Down, Left, Up.

        while (!empty($queue)) {
            [$x, $y, $steps] = array_shift($queue);

            if ($x === self::GRID_SIZE - 1 && $y === self::GRID_SIZE - 1) {
                return $steps; // Reached the exit.
            }

            if ($visited[$y][$x]) {
                continue;
            }

            $visited[$y][$x] = true;

            foreach ($directions as [$dx, $dy]) {
                $nx = $x + $dx;
                $ny = $y + $dy;

                if ($nx >= 0 && $nx < self::GRID_SIZE && $ny >= 0 && $ny < self::GRID_SIZE &&
                    !$this->grid[$ny][$nx] && !$visited[$ny][$nx]) {
                    $queue[] = [$nx, $ny, $steps + 1];
                }
            }
        }

        return null; // No path exists.
    }

    /**
     * Simulates bytes and computes the shortest path for Part One.
     *
     * @return int|null The shortest path length.
     */
    public function computeShortestPathFull(): ?int
    {
        $this->simulateFallingBytes(self::BYTE_COUNT);
        return $this->computeShortestPath();
    }

    /**
     * Finds the first byte that blocks the path from start to exit.
     *
     * @return string|null Coordinates of the blocking byte in "X,Y" format, or null if no byte blocks the path.
     */
    public function findFirstBlockingByte(): ?string
    {
        for ($i = 1; $i <= count($this->fallingBytes); $i++) {
            $this->simulateFallingBytes($i);
            if ($this->computeShortestPath() === null) {
                [$x, $y] = $this->fallingBytes[$i - 1];
                return "$x,$y";
            }
        }

        return null; // No blocking byte found within the input limit.
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $ramRun = new RAMRun($inputFile);
    echo "Shortest Path (Full): " . $ramRun->computeShortestPathFull() . " steps" . PHP_EOL;
    echo "First Blocking Byte: " . $ramRun->findFirstBlockingByte() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
