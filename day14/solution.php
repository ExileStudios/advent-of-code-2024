<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 14: Restroom Redoubt
 *
 * Predicts the positions of teleporting robots after a given time and calculates 
 * the safety factor by counting robots in each quadrant.
 * 
 */
final class RestroomRedoubtSolver
{
    /**
     * @var array<int, array<string, int>> Parsed robots data.
     */
    private array $robots;

    /**
     * Constructor loads robots' positions and velocities from the input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read.
     */
    public function __construct(string $filename)
    {
        $this->robots = $this->parseInput($filename);
    }

    /**
     * Parses the input file into robots' positions and velocities.
     *
     * @param string $filename Path to the input file.
     * @return array<int, array<string, int>> Parsed robot data.
     * @throws RuntimeException If the file cannot be read.
     */
    private function parseInput(string $filename): array
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read file: $filename");
        }

        $robots = [];
        foreach ($lines as $line) {
            if (!preg_match('/p=(-?\d+),(-?\d+) v=(-?\d+),(-?\d+)/', $line, $matches)) {
                throw new InvalidArgumentException("Malformed input line: $line");
            }

            $robots[] = [
                'px' => (int)$matches[1],
                'py' => (int)$matches[2],
                'vx' => (int)$matches[3],
                'vy' => (int)$matches[4],
            ];
        }

        return $robots;
    }

    /**
     * Simulates the motion of robots after a given number of seconds.
     *
     * @param int $seconds The time to simulate.
     * @param int $width The width of the grid.
     * @param int $height The height of the grid.
     * @return array<int, array<string, int>> Robots' positions after the simulation.
     */
    private function simulate(int $seconds, int $width, int $height): array
    {
        $positions = [];
        foreach ($this->robots as $robot) {
            $px = (($robot['px'] + $robot['vx'] * $seconds) % $width + $width) % $width;
            $py = (($robot['py'] + $robot['vy'] * $seconds) % $height + $height) % $height;

            $positions[] = ['px' => $px, 'py' => $py];
        }
        return $positions;
    }

    /**
     * Calculates the safety factor by counting robots in each quadrant.
     *
     * @param array<int, array<string, int>> $positions Robots' positions.
     * @param int $width The width of the grid.
     * @param int $height The height of the grid.
     * @return int The safety factor.
     */
    private function countRobotsInQuadrants(array $positions, int $width, int $height): int
    {
        $centerX = intdiv($width, 2);
        $centerY = intdiv($height, 2);

        $quadrants = [0, 0, 0, 0];
        foreach ($positions as $pos) {
            if ($pos['px'] !== $centerX && $pos['py'] !== $centerY) {
                $index = (($pos['py'] > $centerY) << 1) | ($pos['px'] > $centerX);
                $quadrants[$index]++;
            }
        }

        return (int)array_product($quadrants);
    }

    /**
     * Computes the safety factor after a given number of seconds.
     *
     * @param int $seconds Time to simulate.
     * @param int $width Width of the grid.
     * @param int $height Height of the grid.
     * @return int The calculated safety factor.
     */
    public function computeSafetyFactor(int $seconds, int $width, int $height): int
    {
        $positions = $this->simulate($seconds, $width, $height);
        return $this->countRobotsInQuadrants($positions, $width, $height);
    }

    /**
     * Finds the fewest seconds required for robots to align into a recognizable pattern.
     *
     * @param int $width The width of the grid.
     * @param int $height The height of the grid.
     * @return int The fewest seconds required for alignment.
     */
    public function findEasterEggSeconds(int $width, int $height): int
    {
        $time = 0;
        while (true) {
            $positions = $this->simulate($time, $width, $height);
            if ($this->isEasterEgg($positions, $width, $height)) {
                return $time;
            }
            $time++;
        }
    }

    /**
     * Checks if the current robot positions form an Easter egg pattern.
     *
     * @param array<int, array<string, int>> $positions Robots' positions.
     * @param int $width The width of the grid.
     * @param int $height The height of the grid.
     * @return bool True if the positions form the Easter egg pattern, false otherwise.
     */
    private function isEasterEgg(array $positions, int $width, int $height): bool
    {
        $grid = array_fill(0, $height, array_fill(0, $width, false));

        foreach ($positions as $pos) {
            $grid[$pos['py']][$pos['px']] = true;
        }

        // Detect a Christmas tree pattern
        for ($row = 1; $row < $height - 1; $row++) {
            for ($col = 1; $col < $width - 1; $col++) {
                if (
                    $grid[$row][$col] &&
                    $grid[$row - 1][$col] &&
                    $grid[$row + 1][$col] &&
                    $grid[$row][$col - 1] &&
                    $grid[$row][$col + 1] &&
                    $grid[$row + 1][$col - 1] &&
                    $grid[$row + 1][$col + 1]
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $solver = new RestroomRedoubtSolver($inputFile);

    $safetyFactor = $solver->computeSafetyFactor(100, 101, 103);
    echo "Safety Factor after 100 seconds: {$safetyFactor}" . PHP_EOL;

    $easterEggSeconds = $solver->findEasterEggSeconds(101, 103);
    echo "Fewest seconds to form Easter egg: {$easterEggSeconds}" . PHP_EOL;
} catch (RuntimeException | InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
