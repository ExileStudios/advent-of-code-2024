<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 11: Plutonian Pebbles
 *
 * Simulates the transformation of stones on Pluto's surface after a series of blinks.
 * Each stone is split into two stones if it has an odd number of digits, or multiplied by 2024 if it has an even number of digits.
 * The simulation is run for 25 and 75 blinks, and the total number of stones is reported.
 * 
 */
final class PlutonianPebbles
{
    /** @var array<array<int>> */
    private array $stones = [];

    /**
     * Constructor initializes stones from input file into memory.
     *
     * @param string $filename The path to the input file.
     */
    public function __construct(string $filename)
    {
        $this->stones = $this->initializeStones($filename);
    }

    /**
     * Initialize stones by reading the input file into a frequency map.
     *
     * @param string $filename The path to the input file.
     * @return array<array<int>> The initialized stones.
     */
    private function initializeStones(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        if (trim($content) === '') {
            throw new RuntimeException("Invalid input: Stones must not be empty.");
        }

        $numbers = array_map('intval', explode(' ', trim($content)));        
        $stones = array_map(fn($n) => [$n, 1], $numbers);

        return $stones;
    }

    /**
     * Simulates the transformation of stones for a given number of blinks, and returns the total number of stones.
     *
     * @param int $blinks The number of blinks to simulate.
     * @return string The total number of stones after the simulation.
     */
    public function simulateBlinks(int $blinks): string 
    {
        for ($i = 0; $i < $blinks; $i++) {
            $newStones = [];
            
            foreach ($this->stones as [$stone, $count]) {
                if ($stone === 0) {
                    $this->addStone($newStones, 1, $count);
                    continue;
                }

                $strStone = (string)abs($stone);
                $digits = strlen($strStone);

                if (($digits & 1) === 0) {
                    $half = $digits >> 1;
                    $left = (int)substr($strStone, 0, $half);
                    if ($stone < 0) $left = -$left;
                    $right = (int)substr($strStone, $half);
                    $this->addStone($newStones, $left, $count);
                    $this->addStone($newStones, $right, $count);
                } else {
                    $this->addStone($newStones, $stone * 2024, $count);
                }
            }
            
            $this->stones = $newStones;
        }
        
        $total = gmp_init(0);
        foreach ($this->stones as [$stone, $count]) {
            $total = gmp_add($total, $count);
        }
        
        return gmp_strval($total);
    }

    /**
     * Adds a stone to the list of stones, incrementing the count if the value already exists.
     * 
     * @param array<array<int>> $stones The list of stones to update.
     * @param int $value The value of the stone to add.
     * @param int $count The number of stones to add.
     * @return void
     */
    private function addStone(array &$stones, int $value, $count): void 
    {
        $found = false;
        foreach ($stones as &$stone) {
            if ($stone[0] === $value) {
                $stone[1] = gmp_add($stone[1], $count);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $stones[] = [$value, $count];
        }
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $pebbles = new PlutonianPebbles($inputFile);
    echo "Total Stones After 25 Blinks: ".$pebbles->simulateBlinks(25)."\n";
    $pebbles = new PlutonianPebbles($inputFile);
    echo "Total Stones After 75 Blinks: ".$pebbles->simulateBlinks(75)."\n";
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
