<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 24: Crossed Wires
 *
 * Simulates a system of boolean logic gates to compute the final decimal number 
 * represented by output wires starting with "z". For Part Two, identifies four pairs 
 * of swapped gate outputs needed to make the system correctly perform binary addition.
 * 
 */
final class CrossedWires
{
    /** @var array<string, int> Wire values indexed by their names. */
    private array $wires = [];

    /** @var array<string, array<string>> Logic gates and their input/output definitions. */
    private array $gates = [];

    /**
     * Constructor to initialize the Crossed Wires solver with the input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be accessed.
     */
    public function __construct(string $filename)
    {
        $this->parseInput($filename);
    }

    /**
     * Parses the input file to initialize wire values and gate definitions.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read or parsed.
     */
    private function parseInput(string $filename): void
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$wire, $value] = explode(':', $line);
                $this->wires[trim($wire)] = (int)trim($value);
            } elseif (preg_match('/(\w+) (AND|OR|XOR) (\w+) -> (\w+)/', $line, $matches)) {
                [, $input1, $operation, $input2, $output] = $matches;
                $this->gates[$output] = [$input1, $operation, $input2];
            }
        }
    }

    /**
     * Simulates the logic gates to calculate all wire values.
     */
    public function simulate(): void
    {
        while (true) {
            $progress = false;

            foreach ($this->gates as $output => [$input1, $operation, $input2]) {
                if (isset($this->wires[$output])) {
                    continue;
                }

                if (isset($this->wires[$input1]) && isset($this->wires[$input2])) {
                    $value1 = $this->wires[$input1];
                    $value2 = $this->wires[$input2];
                    $this->wires[$output] = $this->computeGate($value1, $value2, $operation);
                    $progress = true;
                }
            }

            if (!$progress) {
                break;
            }
        }
    }

    /**
     * Computes the output of a logic gate based on its operation.
     *
     * @param int $input1 First input value.
     * @param int $input2 Second input value.
     * @param string $operation The gate operation (AND, OR, XOR).
     * @return int The computed output value.
     * @throws RuntimeException If the operation is invalid.
     */
    private function computeGate(int $input1, int $input2, string $operation): int
    {
        return match ($operation) {
            'AND' => $input1 & $input2,
            'OR'  => $input1 | $input2,
            'XOR' => $input1 ^ $input2,
            default => throw new RuntimeException("Unknown operation: $operation"),
        };
    }

    /**
     * Computes the final decimal output represented by wires prefixed with "z".
     *
     * @return int The computed decimal output.
     */
    public function computeDecimalOutput(): int
    {
        ksort($this->wires);
        $binary = '';

        foreach ($this->wires as $wire => $value) {
            if (str_starts_with($wire, 'z')) {
                $binary = $value . $binary;
            }
        }

        return bindec($binary);
    }

    /**
     * Identifies incorrectly configured gates and returns them as a list.
     *
     * @return string A comma-separated list of incorrect gates.
     */
    public function findSwappedWires(): string
    {
        $wrong = [];
        $highestZ = max(array_filter(array_keys($this->gates), fn($key) => str_starts_with($key, 'z')));

        foreach ($this->gates as $output => [$input1, $operation, $input2]) {
            if (
                (str_starts_with($output, 'z') && $operation !== 'XOR' && $output !== $highestZ) ||
                ($operation === 'XOR' && !$this->validXorInputs($output, $input1, $input2)) ||
                ($operation === 'AND' && $this->invalidAndInputs($output, $input1, $input2)) ||
                ($operation === 'XOR' && $this->xorFeedsOr($output))
            ) {
                $wrong[] = $output;
            }
        }

        sort($wrong);
        return implode(',', array_unique($wrong));
    }

    /**
     * Checks if XOR inputs are valid.
     *
     * @param string $output
     * @param string $input1
     * @param string $input2
     * @return bool
     */
    private function validXorInputs(string $output, string $input1, string $input2): bool
    {
        $prefixes = ['x', 'y', 'z'];
        return $this->startsWithAny($output, $prefixes) ||
            $this->startsWithAny($input1, $prefixes) ||
            $this->startsWithAny($input2, $prefixes);
    }

    /**
     * Checks if AND inputs are invalid.
     *
     * @param string $output
     * @param string $input1
     * @param string $input2
     * @return bool
     */
    private function invalidAndInputs(string $output, string $input1, string $input2): bool
    {
        if ($input1 === 'x00' || $input2 === 'x00') {
            return false;
        }

        foreach ($this->gates as [$subInput1, $subOp, $subInput2]) {
            if (($output === $subInput1 || $output === $subInput2) && $subOp !== 'OR') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if XOR output feeds into OR.
     *
     * @param string $output
     * @return bool
     */
    private function xorFeedsOr(string $output): bool
    {
        foreach ($this->gates as [$subInput1, $subOp, $subInput2]) {
            if (($output === $subInput1 || $output === $subInput2) && $subOp === 'OR') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a string starts with any of the given prefixes.
     *
     * @param string $string
     * @param array<string> $prefixes
     * @return bool
     */
    private function startsWithAny(string $string, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($string, $prefix)) {
                return true;
            }
        }

        return false;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $crossedWires = new CrossedWires($inputFile);
    $crossedWires->simulate();
    echo "Decimal Output: " . $crossedWires->computeDecimalOutput() . PHP_EOL;
    echo "Swapped Wires: " . $crossedWires->findSwappedWires() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
