<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 9: Disk Fragmenter
 *
 * Simulates block-level disk compaction by compacting individual blocks
 * or entire files while preserving file ordering constraints.
 * 
 */
final class DiskFragmenter
{
    /**
     * @var string The encoded disk map as a single-line string.
     */
    private string $diskMap;

    /**
     * @var array<int> The expanded disk map as a block-level array.
     */
    private array $expandedBlocks = [];

    /**
     * Constructor initializes the disk map from the input file.
     *
     * @param string $filename The path to the input file.
     *
     * @throws RuntimeException If the file is unreadable or has an invalid format.
     */
    public function __construct(string $filename)
    {
        $this->diskMap = $this->loadDiskMap($filename);
    }

    /**
     * Loads the disk map from the given file.
     *
     * @param string $filename The file containing the disk map.
     *
     * @return string The disk map as a single-line string.
     *
     * @throws RuntimeException If the file is inaccessible or invalid.
     */
    private function loadDiskMap(string $filename): string
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || count($lines) !== 1) {
            throw new RuntimeException("Invalid disk map format: expected a single line.");
        }
        return $lines[0];
    }

    /**
     * Simulates compacting individual file blocks.
     *
     * @return int The checksum of the compacted disk.
     */
    public function compactIndividualBlocks(): int
    {
        $this->expandDiskMap();
        $this->moveIndividualBlocks();
        return $this->calculateChecksum();
    }

    /**
     * Simulates compacting entire files.
     *
     * @return int The checksum of the compacted disk.
     */
    public function compactWholeFiles(): int
    {
        $this->expandDiskMap();
        $this->moveWholeFiles();
        return $this->calculateChecksum();
    }

    /**
     * Expands the encoded disk map into a block-level representation.
     *
     * @return void
     */
    private function expandDiskMap(): void
    {
        $fileId = 0;
        $position = 0;
        $length = strlen($this->diskMap);

        for ($i = 0; $i < $length; $i++) {
            $size = (int)$this->diskMap[$i];

            if ($i % 2 === 0) {
                // File block
                for ($j = 0; $j < $size; $j++) {
                    $this->expandedBlocks[$position++] = $fileId;
                }
                $fileId++;
            } else {
                // Free space block
                for ($j = 0; $j < $size; $j++) {
                    $this->expandedBlocks[$position++] = -1;
                }
            }
        }
    }

    /**
     * Moves individual file blocks to compact the disk.
     *
     * @return void
     */
    private function moveIndividualBlocks(): void
    {
        $totalBlocks = count($this->expandedBlocks);
        $freeSpaces = [];

        // Collect all free spaces
        for ($i = 0; $i < $totalBlocks; $i++) {
            if ($this->expandedBlocks[$i] === -1) {
                $freeSpaces[] = $i;
            }
        }

        // Move files from right to left
        $freeSpaceIndex = 0;
        for ($i = $totalBlocks - 1; $i >= 0 && $freeSpaceIndex < count($freeSpaces); $i--) {
            if ($this->expandedBlocks[$i] !== -1) {
                $freeSpacePos = $freeSpaces[$freeSpaceIndex];
                if ($freeSpacePos < $i) {
                    $this->expandedBlocks[$freeSpacePos] = $this->expandedBlocks[$i];
                    $this->expandedBlocks[$i] = -1;
                    $freeSpaceIndex++;
                }
            }
        }
    }

    /**
     * Moves entire files to compact the disk.
     *
     * @return void
     */
    private function moveWholeFiles(): void
    {
        $totalBlocks = count($this->expandedBlocks);
        $files = [];

        // Identify file positions and sizes
        for ($i = 0; $i < $totalBlocks; $i++) {
            $fileId = $this->expandedBlocks[$i];
            if ($fileId !== -1) {
                if (!isset($files[$fileId])) {
                    $files[$fileId] = [
                        'id' => $fileId,
                        'start' => $i,
                        'size' => 1,
                        'blocks' => [$i],
                    ];
                } else {
                    $files[$fileId]['size']++;
                    $files[$fileId]['blocks'][] = $i;
                }
            }
        }

        // Process files in descending order
        krsort($files);
        foreach ($files as $file) {
            $bestPosition = $this->findBestFitPosition($file['start'], $file['size']);
            if ($bestPosition !== -1 && $bestPosition < $file['start']) {
                foreach ($file['blocks'] as $index => $oldPos) {
                    $newPos = $bestPosition + $index;
                    $this->expandedBlocks[$newPos] = $file['id'];
                    $this->expandedBlocks[$oldPos] = -1;
                }
            }
        }
    }

    /**
     * Finds the best-fit position for moving a file.
     *
     * @param int $currentStart The current start position of the file.
     * @param int $size The size of the file.
     *
     * @return int The best-fit position or -1 if not found.
     */
    private function findBestFitPosition(int $currentStart, int $size): int
    {
        $consecutiveFreeSpace = 0;
        $startPosition = -1;

        for ($i = 0; $i < $currentStart; $i++) {
            if ($this->expandedBlocks[$i] === -1) {
                if ($consecutiveFreeSpace === 0) {
                    $startPosition = $i;
                }
                $consecutiveFreeSpace++;
                if ($consecutiveFreeSpace >= $size) {
                    return $startPosition;
                }
            } else {
                $consecutiveFreeSpace = 0;
                $startPosition = -1;
            }
        }

        return -1;
    }

    /**
     * Calculates the checksum of the current disk state.
     *
     * @return int The calculated checksum.
     */
    private function calculateChecksum(): int
    {
        $checksum = 0;
        foreach ($this->expandedBlocks as $position => $fileId) {
            if ($fileId !== -1) {
                $checksum += $position * $fileId;
            }
        }
        return $checksum;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');
try {
    $fragmenter = new DiskFragmenter($inputFile);
    echo "Compact Individual Blocks: " . $fragmenter->compactIndividualBlocks() . PHP_EOL;
    echo "Compact Whole Files: " . $fragmenter->compactWholeFiles() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
