<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 25: Code Chronicle
 * 
 * Analyzes lock and key schematics to determine valid combinations based on pin heights.
 * 
 */
final class CodeChronicle
{
    /** @var array<array<int>> Cache of processed lock heights */
    private array $lockHeights = [];
    
    /** @var array<array<int>> Cache of processed key heights */
    private array $keyHeights = [];
    
    /** @var int Grid height for validation */
    private int $gridHeight;

    /**
     * Constructor to initialize the Code Chronicle solver.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be accessed.
     */
    public function __construct(string $filename)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }
        $this->parseInput($filename);
    }

    /**
     * Parses the input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read.
     */
    private function parseInput(string $filename): void
    {
        $content = file_get_contents($filename);
        if ($content === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        $lines = explode("\n", trim($content));
        $currentSchematic = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                if (!empty($currentSchematic)) {
                    $this->processSchematic($currentSchematic);
                    $currentSchematic = [];
                }
            } else {
                $currentSchematic[] = $line;
            }
        }

        if (!empty($currentSchematic)) {
            $this->processSchematic($currentSchematic);
        }
    }

    /**
     * Processes a single schematic.
     *
     * @param array<string> $schematic Lines of the schematic.
     */
    private function processSchematic(array $schematic): void
    {
        $this->gridHeight = count($schematic);
        $width = strlen($schematic[0]);
        $heights = array_fill(0, $width, 0);
        
        $isLock = str_starts_with($schematic[0], '#');
        
        for ($col = 0; $col < $width; $col++) {
            if ($isLock) {
                for ($row = $this->gridHeight - 1; $row >= 0; $row--) {
                    if ($schematic[$row][$col] === '#') {
                        $heights[$col] = $row;
                        break;
                    }
                }
            } else {
                for ($row = 0; $row < $this->gridHeight; $row++) {
                    if ($schematic[$row][$col] === '#') {
                        $heights[$col] = $this->gridHeight - $row - 1;
                        break;
                    }
                }
            }
        }

        if ($isLock) {
            $this->lockHeights[] = $heights;
        } else {
            $this->keyHeights[] = $heights;
        }
    }

    /**
     * Checks if a lock and key pair fit.
     *
     * @param array<int> $lock Lock pin heights.
     * @param array<int> $key Key pin heights.
     * @return bool True if the pair fits, false otherwise.
     */
    private function doesPairFit(array $lock, array $key): bool
    {
        if (count($lock) !== count($key)) {
            return false;
        }

        foreach ($lock as $i => $lockHeight) {
            if ($lockHeight + $key[$i] >= $this->gridHeight - 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Counts valid lock/key pairs.
     *
     * @return int Number of valid lock/key pairs.
     */
    public function countValidPairs(): int
    {
        $validPairs = 0;

        foreach ($this->lockHeights as $lock) {
            foreach ($this->keyHeights as $key) {
                if ($this->doesPairFit($lock, $key)) {
                    $validPairs++;
                }
            }
        }

        return $validPairs;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $codeChronicle = new CodeChronicle($inputFile);
    echo "Number of valid lock/key pairs: " . $codeChronicle->countValidPairs() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
