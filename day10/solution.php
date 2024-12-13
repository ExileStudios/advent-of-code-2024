<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 10: Hoof It
 *
 * Calculates the sum of scores and ratings for all trailheads on a given topographic map.
 * Score - Number of height-9 positions reachable via valid hiking trails.
 * Rating - Number of distinct hiking trails starting at a trailhead.
 * 
 */
final class LavaHikingTrails
{
    /**
     * The maximum height value on the topographic map.
     * Indicates the endpoint for a valid hiking trail.
     */
    private const MAX_HEIGHT = 9;

    /**
     * The minimum height value on the topographic map.
     * Indicates the starting point (trailhead) for hiking trails.
     */
    private const MIN_HEIGHT = 0;

    /**
     * @var array<array<int>> Directional offsets for up, down, left, and right movements.
     */
    private const DIRECTIONS = [
        [-1, 0], // Up
        [1, 0],  // Down
        [0, -1], // Left
        [0, 1],  // Right
    ];

    /**
     * @var array<array<int>> The parsed topographic map.
     */
    private array $map;

    /**
     * Constructor to initialize the topographic map.
     *
     * @param string $filename The path to the input file.
     *
     * @throws RuntimeException If the file is unreadable or has an invalid format.
     */
    public function __construct(string $filename)
    {
        $this->map = $this->loadTopographicMap($filename);
    }

    /**
     * Loads the topographic map from the given file.
     *
     * @param string $filename The file containing the topographic map.
     *
     * @return array<array<int>> The parsed map as a 2D array.
     *
     * @throws RuntimeException If the file is inaccessible or invalid.
     */
    private function loadTopographicMap(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        $map = [];
        foreach ($lines as $line) {
            $map[] = array_map('intval', str_split(trim($line)));
        }

        return $map;
    }

    /**
     * Calculates the sum of scores of all trailheads on the map.
     *
     * @return int The total score.
     */
    public function calculateTrailheadScores(): int
    {
        $totalScore = 0;
        foreach ($this->findTrailheads() as [$row, $col]) {
            $totalScore += $this->calculateTrailheadScore($row, $col);
        }
        return $totalScore;
    }

    /**
     * Calculates the sum of ratings of all trailheads on the map.
     *
     * @return int The total rating.
     */
    public function calculateTrailheadRatings(): int
    {
        $totalRating = 0;
        foreach ($this->findTrailheads() as [$row, $col]) {
            $totalRating += $this->calculateTrailheadRating($row, $col);
        }
        return $totalRating;
    }

    /**
     * Finds all trailhead positions (height 0) on the map.
     *
     * @return array<array<int>> An array of [row, col] positions of trailheads.
     */
    private function findTrailheads(): array
    {
        $trailheads = [];
        foreach ($this->map as $rowIndex => $row) {
            foreach ($row as $colIndex => $height) {
                if ($height === 0) {
                    $trailheads[] = [$rowIndex, $colIndex];
                }
            }
        }
        return $trailheads;
    }

    /**
     * Calculates the score for a single trailhead.
     *
     * @param int $startRow The starting row index of the trailhead.
     * @param int $startCol The starting column index of the trailhead.
     *
     * @return int The trailhead score.
     */
    private function calculateTrailheadScore(int $startRow, int $startCol): int
    {
        $rows = count($this->map);
        $cols = count($this->map[0]);
        $visited = [];
        $score = 0;

        $queue = [[$startRow, $startCol, self::MIN_HEIGHT]];

        while (!empty($queue)) {
            [$row, $col, $height] = array_shift($queue);

            $key = "$row,$col";
            if (isset($visited[$key]) || $this->map[$row][$col] !== $height) {
                continue;
            }
            $visited[$key] = true;

            if ($height === self::MAX_HEIGHT) {
                $score++;
                continue;
            }

            $nextHeight = $height + 1;
            foreach (self::DIRECTIONS as [$dRow, $dCol]) {
                $newRow = $row + $dRow;
                $newCol = $col + $dCol;
                if ($newRow >= 0 && $newRow < $rows && $newCol >= 0 && $newCol < $cols) {
                    $queue[] = [$newRow, $newCol, $nextHeight];
                }
            }
        }

        return $score;
    }

    /**
     * Calculates the rating for a single trailhead.
     *
     * @param int $startRow The starting row index of the trailhead.
     * @param int $startCol The starting column index of the trailhead.
     *
     * @return int The trailhead rating.
     */
    private function calculateTrailheadRating(int $startRow, int $startCol): int
    {
        $rows = count($this->map);
        $cols = count($this->map[0]);
        $visitedTrails = [];
        $rating = 0;

        $queue = [[$startRow, $startCol, self::MIN_HEIGHT, []]];

        while (!empty($queue)) {
            [$row, $col, $height, $trailPath] = array_shift($queue);

            $key = "$row,$col";
            if ($this->map[$row][$col] !== $height) {
                continue;
            }

            $trailPath[] = $key;
            $trailHash = implode('->', $trailPath);
            if ($height === self::MAX_HEIGHT && !isset($visitedTrails[$trailHash])) {
                $visitedTrails[$trailHash] = true;
                $rating++;
                continue;
            }

            $nextHeight = $height + 1;
            foreach (self::DIRECTIONS as [$dRow, $dCol]) {
                $newRow = $row + $dRow;
                $newCol = $col + $dCol;
                if ($newRow >= 0 && $newRow < $rows && $newCol >= 0 && $newCol < $cols) {
                    $queue[] = [$newRow, $newCol, $nextHeight, $trailPath];
                }
            }
        }

        return $rating;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');
try {
    $hikingTrails = new LavaHikingTrails($inputFile);
    echo "Total Trailhead Scores: " . $hikingTrails->calculateTrailheadScores() . PHP_EOL;
    echo "Total Trailhead Ratings: " . $hikingTrails->calculateTrailheadRatings() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
