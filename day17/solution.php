<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 17: Chronospatial Computer
 *
 * Simulates a virtual 3-bit computer, outputs program results, and finds the smallest
 * starting value of register A that reproduces the program's output.
 * 
 */
final class ChronospatialComputer
{
    private const OPCODE_ADV = 0; // Arithmetic Right Shift Division (updates register A)
    private const OPCODE_BDV = 6; // Arithmetic Right Shift Division (updates register B)
    private const OPCODE_CDV = 7; // Arithmetic Right Shift Division (updates register C)
    private const OPCODE_BXL = 1; // Bitwise XOR with Literal (updates register B)
    private const OPCODE_BST = 2; // Bitwise Set (modulo 8, updates register B)
    private const OPCODE_JNZ = 3; // Jump if Not Zero (conditional jump based on register A)
    private const OPCODE_BXC = 4; // Bitwise XOR with C (updates register B)
    private const OPCODE_OUT = 5; // Output (modulo 8 of combo operand)

    /**
     * @var int Initial value of register A.
     */
    private int $initialA;

    /**
     * @var array<int> The computer program instructions.
     */
    private array $program;

    /**
     * Constructs the ChronospatialComputer instance from the input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read.
     */
    public function __construct(string $filename)
    {
        [$this->initialA, $this->program] = $this->loadProgram($filename);
    }

    /**
     * Loads and parses the input file to extract initial register A and program instructions.
     *
     * @param string $filename Path to the input file.
     * @return array{int, array<int>} Parsed values: Initial register A value and program instructions.
     * @throws RuntimeException If the file cannot be read.
     */
    private function loadProgram(string $filename): array
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read the file: $filename");
        }

        $a = (int)filter_var($lines[0], FILTER_SANITIZE_NUMBER_INT);

        $programLine = (string)array_pop($lines);
        $position = strpos($programLine, ':');
        if ($position === false) {
            throw new RuntimeException("Invalid program format.");
        }

        $program = array_map('intval', explode(',', substr($programLine, $position + 1)));

        return [$a, $program];
    }

    /**
     * Executes the program instructions using the given initial register A value.
     * Outputs produced by the program are collected and returned.
     *
     * @param int $a Initial value of register A.
     * @return array<int> The output values produced by the program.
     */
    public function executeProgram(int $a): array
    {
        $b = 0;
        $c = 0;
        $pointer = 0;
        $outputs = [];

        while ($pointer < count($this->program)) {
            $opcode = $this->program[$pointer];
            $operand = $this->program[$pointer + 1] ?? 0;

            $combo = match ($operand) {
                0, 1, 2, 3 => $operand,
                4 => $a,
                5 => $b,
                6 => $c,
                default => 0,
            };

            switch ($opcode) {
                case self::OPCODE_ADV:
                    $a >>= $combo;
                    break;
                case self::OPCODE_BXL:
                    $b ^= $operand;
                    break;
                case self::OPCODE_BST:
                    $b = $combo % 8;
                    break;
                case self::OPCODE_JNZ:
                    if ($a !== 0) {
                        $pointer = $operand - 2;
                    }
                    break;
                case self::OPCODE_BXC:
                    $b ^= $c;
                    break;
                case self::OPCODE_OUT:
                    $outputs[] = $combo % 8;
                    break;
                case self::OPCODE_BDV:
                    $b = $a >> $combo;
                    break;
                case self::OPCODE_CDV:
                    $c = $a >> $combo;
                    break;
            }

            $pointer += 2;
        }

        return $outputs;
    }

    /**
     * Recursively determines the smallest valid starting value of register A that produces the specified target output sequence.
     *
     * @param int $position Current position in the output sequence.
     * @param int $result Accumulated A value.
     * @param array<int> $targetOutput The target program output sequence.
     * @return int|null The smallest valid value for register A, or null if not found.
     */
    public function findSmallestRegisterA(int $position, int $result, array $targetOutput): ?int
    {
        if ($position < 0) {
            return $result;
        }

        for ($digit = 0; $digit < 8; $digit++) {
            $a = ($result << 3) | $digit;
            $outputs = $this->executeProgram($a);

            if (!empty($outputs) && $outputs[0] === $targetOutput[$position]) {
                $found = $this->findSmallestRegisterA($position - 1, $a, $targetOutput);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Determines the program output for the initial state of the registers.
     *
     * @return string The program output joined as a comma-separated string.
     */
    public function determineProgramOutput(): string
    {
        $outputs = $this->executeProgram($this->initialA);
        return implode(',', $outputs);
    }

    /**
     * Finds the smallest valid starting value of register A that reproduces the program's output sequence.
     *
     * @return int|null The smallest valid A value, or null if no solution exists.
     */
    public function findMinimalRegisterA(): ?int
    {
        return $this->findSmallestRegisterA(count($this->program) - 1, 0, $this->program);
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $computer = new ChronospatialComputer($inputFile);
    echo "Program Output: " . $computer->determineProgramOutput() . PHP_EOL;
    $smallestA = $computer->findMinimalRegisterA();
    if ($smallestA !== null) {
        echo "Smallest Register A: $smallestA" . PHP_EOL;
    } else {
        echo "No valid solution for smallest register A found." . PHP_EOL;
    }
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
