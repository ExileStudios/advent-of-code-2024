<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 15: Warehouse Woes
 * 
 * Simulates a robot navigating a warehouse, moving boxes according to a grid-based
 * system, and computes GPS scores based on box positions.
 * 
 */
final class WarehouseWoes
{
    /**
     * @var array<array<string>> The grid representing the warehouse.
     */
    private array $grid;

    /**
     * @var string Instructions for robot movements.
     */
    private string $instructions;

    /**
     * @var array<string, array<int>> Directions mapping for robot movement.
     */
    private array $directions = [
        '<' => [0, -1],
        '>' => [0, 1],
        '^' => [-1, 0],
        'v' => [1, 0]
    ];

    /**
     * Initializes the WarehouseWoes class with input from the provided file.
     *
     * @param string $inputFile Path to the input file containing grid and instructions.
     * @throws RuntimeException If the file cannot be read or contains invalid data.
     */
    public function __construct(string $inputFile)
    {
        [$this->grid, $this->instructions] = $this->parseInput($inputFile);
    }

    /**
     * Parses the input file into grid and instructions.
     *
     * @param string $inputFile Path to the input file.
     * @return array{array<array<string>>, string} Parsed grid and instructions.
     * @throws RuntimeException If the file cannot be read or has invalid format.
     */
    private function parseInput(string $inputFile): array
    {
        $file = file_get_contents($inputFile);
        if ($file === false) {
            throw new RuntimeException("Unable to read file: $inputFile");
        }
        
        $file = str_replace(["\r\n", "\r"], "\n", $file);
        $parts = array_filter(explode("\n\n", $file));
        
        if (count($parts) !== 2) {
            throw new RuntimeException("Invalid input format in file: $inputFile");
        }

        [$gridPart, $instructionsPart] = $parts;
        $grid = array_map('str_split', explode("\n", trim($gridPart)));
        $instructions = str_replace("\n", '', trim($instructionsPart));

        return [$grid, $instructions];
    }

    /**
     * Finds the position of the robot in the grid.
     *
     * @return array<int> Coordinates of the robot [row, column].
     * @throws RuntimeException If no robot is found in the grid.
     */
    private function findRobot(): array
    {
        for ($row = 0; $row < count($this->grid); $row++) {
            for ($col = 0; $col < count($this->grid[0]); $col++) {
                if ($this->grid[$row][$col] === '@') {
                    return [$row, $col];
                }
            }
        }
        throw new RuntimeException("No robot found");
    }

    /**
     * Identifies all boxes in the grid.
     *
     * @return array<array<int>> Coordinates of boxes in the grid.
     */
    private function findBoxes(): array
    {
        $boxes = [];
        for ($row = 0; $row < count($this->grid); $row++) {
            for ($col = 0; $col < count($this->grid[0]); $col++) {
                if (in_array($this->grid[$row][$col], ['O', '['], true)) {
                    $boxes[] = [$row, $col];
                }
            }
        }
        return $boxes;
    }

    /**
     * Validates if the specified position in the grid is accessible.
     *
     * @param int $row Row index to validate.
     * @param int $col Column index to validate.
     * @return bool True if the position is valid; otherwise, false.
     */
    private function isValid(int $row, int $col): bool
    {
        return $row >= 0 && $row < count($this->grid) && 
               $col >= 0 && $col < count($this->grid[0]) && 
               $this->grid[$row][$col] !== '#';
    }

    /**
     * Recursively determines if a box can be moved in the specified direction.
     *
     * @param int $row Row index of the box.
     * @param int $col Column index of the box.
     * @param int $dr Row delta for the move.
     * @param int $dc Column delta for the move.
     * @param array<string, bool> $seen Positions already checked.
     * @return bool True if the box can be moved; otherwise, false.
     */
    private function canMoveBox(int $row, int $col, int $dr, int $dc, array &$seen): bool
    {
        $key = "$row,$col";
        if (isset($seen[$key])) {
            return true;
        }
        $seen[$key] = true;

        $nr = $row + $dr;
        $nc = $col + $dc;

        if (!isset($this->grid[$nr][$nc])) {
            return false;
        }

        return match($this->grid[$nr][$nc]) {
            '#' => false,
            '[' => $this->canMoveBox($nr, $nc, $dr, $dc, $seen) &&
                  $this->canMoveBox($nr, $nc + 1, $dr, $dc, $seen),
            ']' => $this->canMoveBox($nr, $nc, $dr, $dc, $seen) &&
                  $this->canMoveBox($nr, $nc - 1, $dr, $dc, $seen),
            'O' => $this->canMoveBox($nr, $nc, $dr, $dc, $seen),
            default => true
        };
    }

    /**
     * Executes a movement based on the instruction and updates the robot's position.
     *
     * @param int $row Current row of the robot.
     * @param int $col Current column of the robot.
     * @param string $instruction Movement instruction ('<', '>', '^', 'v').
     * @return array<int> Updated robot coordinates after the move.
     */
    private function executeMovement(int $row, int $col, string $instruction): array
    {
        [$dr, $dc] = $this->directions[$instruction];
        $nr = $row + $dr;
        $nc = $col + $dc;

        if (!$this->isValid($nr, $nc)) {
            return [$row, $col];
        }

        if (in_array($this->grid[$nr][$nc], ['[', ']', 'O'], true)) {
            $seen = [];
            if (!$this->canMoveBox($row, $col, $dr, $dc, $seen)) {
                return [$row, $col];
            }

            while (!empty($seen)) {
                $seenCopy = array_keys($seen);
                foreach ($seenCopy as $position) {
                    [$r, $c] = explode(',', $position);
                    $r = (int)$r;
                    $c = (int)$c;
                    $nr2 = $r + $dr;
                    $nc2 = $c + $dc;
                    
                    if (!isset($seen["$nr2,$nc2"])) {
                        if ($this->grid[$nr2][$nc2] !== '@' && $this->grid[$r][$c] !== '@') {
                            $this->grid[$nr2][$nc2] = $this->grid[$r][$c];
                            $this->grid[$r][$c] = '.';
                        }
                        unset($seen[$position]);
                    }
                }
            }
        }
        
        $this->grid[$row][$col] = '.';
        $this->grid[$nr][$nc] = '@';
        return [$nr, $nc];
    }

    /**
     * Calculates the GPS score based on box positions in the grid.
     *
     * @return int The computed GPS score.
     */
    private function calculateGPSScore(): int
    {
        $score = 0;
        foreach ($this->findBoxes() as [$row, $col]) {
            $score += 100 * $row + $col;
        }
        return $score;
    }

    /**
     * Computes the initial GPS score based on the grid and instructions.
     *
     * @return int The computed initial GPS score.
     */
    public function computeInitialGPSScore(): int
    {
        $workingGrid = array_map(fn($row) => array_values($row), $this->grid);

        $originalGrid = $this->grid;
        $this->grid = $workingGrid;
        
        [$row, $col] = $this->findRobot();
        foreach (str_split($this->instructions) as $instruction) {
            [$row, $col] = $this->executeMovement($row, $col, $instruction);
        }
        
        $result = $this->calculateGPSScore();
        $this->grid = $originalGrid;
        return $result;
    }

    /**
     * Computes the scaled GPS score with an expanded grid.
     *
     * @return int The computed scaled GPS score.
     */
    public function computeScaledGPSScore(): int
    {
        $initialGrid = array_map(fn($row) => array_values($row), $this->grid);

        $grid = [];
        for ($row = 0; $row < count($initialGrid); $row++) {
            $grid[$row] = [];
            for ($col = 0; $col < count($initialGrid[0]); $col++) {
                match($initialGrid[$row][$col]) {
                    '#' => array_push($grid[$row], '#', '#'),
                    'O' => array_push($grid[$row], '[', ']'),
                    '.' => array_push($grid[$row], '.', '.'),
                    '@' => array_push($grid[$row], '@', '.'),
                    default => throw new RuntimeException("Unexpected grid value: {$initialGrid[$row][$col]}"),
                };
            }
        }

        $this->grid = $grid;
        [$row, $col] = $this->findRobot();
        
        foreach (str_split($this->instructions) as $instruction) {
            [$row, $col] = $this->executeMovement($row, $col, $instruction);
        }
        
        $result = $this->calculateGPSScore();
        return $result;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $solver = new WarehouseWoes($inputFile);
    echo "Initial GPS Score: " . $solver->computeInitialGPSScore() . PHP_EOL;
    echo "Scaled GPS Score: " . $solver->computeScaledGPSScore() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
