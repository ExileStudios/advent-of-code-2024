<?php

/**
 * Advent of Code 2024 - Day 6: Guard Gallivant
 * 
 * Simulates the guard's path through the lab grid and determines valid trap placements
 * that force the guard into a loop.
 * 
 */
class GuardGallivant
{
    /**
     * @var array<array<string>> The grid representing the lab layout.
     */
    private array $originalGrid;

    /**
     * @var int The number of rows in the grid.
     */
    private int $rows;

    /**
     * @var int The number of columns in the grid.
     */
    private int $cols;

    /**
     * @var int The row of the guard's starting position.
     */
    private int $startRow;

    /**
     * @var int The column of the guard's starting position.
     */
    private int $startCol;

    /**
     * @var int The direction the guard starts facing (0=up, 1=right, 2=down, 3=left).
     */
    private int $startDirection;

    /**
     * @var array<string,int> Maps direction characters to indices.
     */
    private const DIRECTIONS = ['^' => 0, '>' => 1, 'v' => 2, '<' => 3];

    /**
     * @var array<array<int>> Directional movements: up, right, down, left.
     */
    private const MOVES = [
        [-1, 0],
        [0, 1],
        [1, 0],
        [0, -1],
    ];

    /**
     * @var int Maximum steps to prevent infinite loops.
     */
    private const MAX_STEPS = 100000;

    /**
     * @var array<int, array{row: int, col: int, direction: int}>
     */
    private array $path = [];

    /**
     * @var SplFixedArray<int> Tracks steps for cell visits to analyze paths.
     */
    private SplFixedArray $cellVisitStep;

    /**
     * Constructor initializes the grid and guard state from the input file.
     *
     * @param string $filename Path to the input file.
     *
     * @throws RuntimeException If the file is unreadable or the guard is not found.
     */
    public function __construct(string $filename)
    {
        $this->originalGrid = $this->parseGrid($filename);
        $this->rows = count($this->originalGrid);
        $this->cols = $this->rows > 0 ? count($this->originalGrid[0]) : 0;

        [$this->startRow, $this->startCol, $directionChar] = $this->findGuard();
        $this->startDirection = self::DIRECTIONS[$directionChar];
        $this->originalGrid[$this->startRow][$this->startCol] = '.';

        $this->cellVisitStep = new \SplFixedArray($this->rows * $this->cols);
        for ($i = 0; $i < $this->cellVisitStep->getSize(); $i++) {
            $this->cellVisitStep[$i] = -1;
        }
    }

    /**
     * Parses the input file to construct the grid.
     *
     * @param string $filename Path to the input file.
     *
     * @return array<array<string>> The parsed grid as a 2D array.
     *
     * @throws RuntimeException If the file is unreadable.
     */
    private function parseGrid(string $filename): array
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read file: $filename");
        }
        return array_map('str_split', $lines);
    }

    /**
     * Finds the guard's starting position and facing direction.
     *
     * @return array{int,int,string} The row, column, and direction character.
     *
     * @throws RuntimeException If the guard is not found.
     */
    private function findGuard(): array
    {
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                $ch = $this->originalGrid[$r][$c];
                if (isset(self::DIRECTIONS[$ch])) {
                    return [$r, $c, $ch];
                }
            }
        }
        throw new RuntimeException("Guard not found in the grid.");
    }

    /**
     * Simulates and records the entire path.
     *
     * @param array<array<string>> $grid The grid configuration.
     * @param int $startRow Starting row.
     * @param int $startCol Starting column.
     * @param int $startDir Starting direction.
     *
     * @return array{count: int, looped: bool, visitedCells: SplFixedArray<mixed>}
     */
    private function simulate(array $grid, int $startRow, int $startCol, int $startDir): array 
    {
        $visitedPositions = new \SplFixedArray($this->rows * $this->cols);
        $visitedPositions[$startRow * $this->cols + $startCol] = true;
        $visitCount = 1;

        // Pre-calculate state base to avoid multiplication in hot loop
        $stateBase = $startRow * $this->cols + $startCol;
        $seenStates = [$stateBase << 2 | $startDir => true];
        
        $row = $startRow;
        $col = $startCol;
        $dir = $startDir;
        
        // Only track path in main simulation
        if (empty($this->path)) {
            $this->path[] = ['row' => $row, 'col' => $col, 'direction' => $dir];
            $this->cellVisitStep[$row * $this->cols + $col] = 0;
            $trackPath = true;

            // Reset cellVisitStep for new simulation
            for ($i = 0; $i < $this->cellVisitStep->getSize(); $i++) {
                if ($i !== ($row * $this->cols + $col)) {
                    $this->cellVisitStep[$i] = -1;
                }
            }
        } else {
            $trackPath = false;
        }

        // Cache values and unroll frequently accessed array
        $moves = self::MOVES;
        $moveRow = [$moves[0][0], $moves[1][0], $moves[2][0], $moves[3][0]];
        $moveCol = [$moves[0][1], $moves[1][1], $moves[2][1], $moves[3][1]];
        $cols = $this->cols;
        $rows = $this->rows;
        
        $lastValidStep = 0;
        
        for ($steps = 1; $steps <= self::MAX_STEPS; $steps++) {
            // Unrolled move calculation
            $nr = $row + $moveRow[$dir];
            $nc = $col + $moveCol[$dir];
            
            // Combined boundary check
            if ($nr < 0 || $nr >= $rows || $nc < 0 || $nc >= $cols) {
                if ($trackPath) {
                    // Mark last step before hitting boundary
                    $this->cellVisitStep[$row * $cols + $col] = $lastValidStep;
                }
                return [
                    'count' => $visitCount,
                    'looped' => false,
                    'visitedCells' => $visitedPositions
                ];
            }
            
            if ($grid[$nr][$nc] === '#') {
                $dir = ($dir + 1) & 3;
                continue;
            }
            
            $row = $nr;
            $col = $nc;
            
            // Pre-calculate position index
            $posIndex = $row * $cols + $col;
            if (!$visitedPositions[$posIndex]) {
                $visitedPositions[$posIndex] = true;
                $visitCount++;
                if ($trackPath) {
                    $lastValidStep = $steps;
                }
            }
            
            if ($trackPath) {
                if ($this->cellVisitStep[$posIndex] === -1 || 
                    $steps < $this->cellVisitStep[$posIndex] ||
                    ($steps - $this->cellVisitStep[$posIndex]) > $this->cols * $this->rows) {
                    
                    $this->cellVisitStep[$posIndex] = $steps;
                    $this->path[] = ['row' => $row, 'col' => $col, 'direction' => $dir];
                    
                    // Check if this completes a meaningful loop
                    if ($this->isPartOfLoop($row, $col, $steps)) {
                        return [
                            'count' => $visitCount,
                            'looped' => true,
                            'visitedCells' => $visitedPositions
                        ];
                    }
                }
            }
            
            // Combined state calculation
            $state = $posIndex << 2 | $dir;
            if (isset($seenStates[$state])) {
                return [
                    'count' => $visitCount,
                    'looped' => true,
                    'visitedCells' => $visitedPositions
                ];
            }
            $seenStates[$state] = true;
        }
        
        return [
            'count' => $visitCount,
            'looped' => true,
            'visitedCells' => $visitedPositions
        ];
    }

    /**
     * Determines if the current position forms part of a meaningful loop.
     *
     * @param int $row Current row position
     * @param int $col Current column position
     * @param int $currentStep Current step count
     * @return bool True if position is part of a loop
     */
    private function isPartOfLoop(int $row, int $col, int $currentStep): bool
    {
        $posIndex = $row * $this->cols + $col;
        $previousStep = $this->cellVisitStep[$posIndex];
        
        // If we haven't visited this cell before, it's not part of a loop
        if ($previousStep === -1) {
            return false;
        }
        
        // Calculate the step difference to detect loops
        $stepDifference = $currentStep - $previousStep;
        
        if ($stepDifference >= 4 && $stepDifference <= ($this->rows * $this->cols * 2)) {
            $uniqueCells = 0;
            $cellsSeen = [];
            
            // Look back through the path to count unique cells in potential loop
            for ($i = $currentStep; $i >= $previousStep; $i--) {
                $pathIndex = $i % count($this->path);
                $cellIndex = $this->path[$pathIndex]['row'] * $this->cols + $this->path[$pathIndex]['col'];
                
                if (!isset($cellsSeen[$cellIndex])) {
                    $cellsSeen[$cellIndex] = true;
                    $uniqueCells++;
                    
                    if ($uniqueCells >= 4) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Computes the number of distinct positions visited by the guard.
     *
     * @return int The total number of unique positions visited.
     */
    public function computePatrolPath(): int 
    {
        $result = $this->simulate($this->originalGrid, $this->startRow, $this->startCol, $this->startDirection);
        $count = 0;
        for ($i = 0; $i < $result['visitedCells']->getSize(); $i++) {
            if ($result['visitedCells'][$i]) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Computes the number of valid trap placements that force the guard into a loop.
     *
     * @return int The total number of valid trap positions.
     */
    public function computeTrapLocations(): int 
    {
        $baseResult = $this->simulate($this->originalGrid, $this->startRow, $this->startCol, $this->startDirection);
        if ($baseResult['looped']) {
            return 0;
        }

        $loopablePositions = 0;
        $startPosIndex = $this->startRow * $this->cols + $this->startCol;
        
        for ($posIndex = 0; $posIndex < $baseResult['visitedCells']->getSize(); $posIndex++) {
            if (!$baseResult['visitedCells'][$posIndex]) {
                continue;
            }
            
            $r = (int)($posIndex / $this->cols);
            $c = $posIndex % $this->cols;
            
            if ($this->originalGrid[$r][$c] !== '.' || $posIndex === $startPosIndex) {
                continue;
            }

            $this->originalGrid[$r][$c] = '#';
            $testResult = $this->simulate($this->originalGrid, $this->startRow, $this->startCol, $this->startDirection);
            $this->originalGrid[$r][$c] = '.';
            
            if ($testResult['looped']) {
                $loopablePositions++;
            }
        }

        return $loopablePositions;
    }
}

$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $guardGallivant = new GuardGallivant($inputFile);
    echo "Patrol Path: " . $guardGallivant->computePatrolPath() . "\n";
    echo "Trap Locations: " . $guardGallivant->computeTrapLocations() . "\n";
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
