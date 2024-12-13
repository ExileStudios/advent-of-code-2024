<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 7: Bridge Repair
 *
 * Calculates the sum of target values achievable by applying combinations of
 * `+`, `*`, and optionally `||` operators to sequences of numbers.
 * 
 */
final class BridgeRepair
{
    /**
     * Mapping of operation types to their indices.
     * - 0: Addition (`+`)
     * - 1: Multiplication (`*`)
     * - 2: Concatenation (`||`)
     */
    private const OPERATIONS = [
        '+' => 0,
        '*' => 1,
        '||' => 2,
    ];

    /**
     * @var array{0:int,1:array<int>}[] List of equations as [target, numbers].
     */
    private array $equations;

    /**
     * Constructs the BridgeRepair instance and loads the equations from the input file.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file is unreadable or invalid.
     */
    public function __construct(string $filename)
    {
        $this->equations = $this->parseInput($filename);
    }

    /**
     * Parses the input file into a list of equations.
     *
     * Each line should follow the format: `target: number1 number2 ... numberN`
     *
     * @param string $filename Path to the input file.
     * @return array{0:int,1:array<int>}[] List of equations as [target, numbers] pairs.
     * @throws RuntimeException If the file cannot be read.
     */
    private function parseInput(string $filename): array
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("File not readable: $filename");
        }

        return array_map(static function (string $line): array {
            [$target, $numbers] = explode(':', $line, 2);
            return [
                (int)trim($target),
                array_map('intval', explode(' ', trim($numbers))),
            ];
        }, $lines);
    }

    /**
     * Determines whether a target value can be achieved using allowed operations.
     *
     * @param array<int> $numbers The sequence of numbers.
     * @param int $target The target value to match.
     * @param bool $includeConcatenation Whether to include `||` (concatenation) as an operation.
     * @return bool True if the target can be reached; otherwise, false.
     */
    private function canReachTarget(array $numbers, int $target, bool $includeConcatenation): bool
    {
        $operationCount = $includeConcatenation ? count(self::OPERATIONS) : 2;
        $opCount = count($numbers) - 1;

        $stack = [[
            'index' => 0,
            'value' => $numbers[0],
        ]];

        while ($stack) {
            $state = array_pop($stack);

            if ($state['index'] === $opCount) {
                if ($state['value'] === $target) {
                    return true;
                }
                continue;
            }

            foreach (range(0, $operationCount - 1) as $operation) {
                if ($operation === self::OPERATIONS['||'] && !$includeConcatenation) {
                    continue;
                }
            
                $nextValue = match ($operation) {
                    self::OPERATIONS['+'] => $state['value'] + $numbers[$state['index'] + 1],
                    self::OPERATIONS['*'] => $state['value'] * $numbers[$state['index'] + 1],
                    self::OPERATIONS['||'] => (int)($state['value'] . $numbers[$state['index'] + 1]),
                };
            
                if ($nextValue > $target && $operation !== self::OPERATIONS['+']) {
                    continue;
                }
            
                $stack[] = [
                    'index' => $state['index'] + 1,
                    'value' => $nextValue,
                ];
            }
        }

        return false;
    }

    /**
     * Computes the sum of all target values that can be achieved using the allowed operations.
     *
     * @param bool $includeConcatenation Whether to include `||` (concatenation) as an operation.
     * @return int The total sum of achievable target values.
     */
    private function computeTargetSum(bool $includeConcatenation): int
    {
        $sum = 0;
        foreach ($this->equations as [$target, $numbers]) {
            if ($this->canReachTarget($numbers, $target, $includeConcatenation)) {
                $sum += $target;
            }
        }
        return $sum;
    }

    /**
     * Computes the sum of target values achievable using addition and multiplication.
     *
     * @return int The total sum of achievable target values.
     */
    public function computeAddMultiplySum(): int
    {
        return $this->computeTargetSum(false);
    }

    /**
     * Computes the sum of target values achievable using addition, multiplication, and concatenation.
     *
     * @return int The total sum of achievable target values.
     */
    public function computeFullOperatorSum(): int
    {
        return $this->computeTargetSum(true);
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');
try {
    $bridgeRepair = new BridgeRepair($inputFile);
    echo "Add and Multiply Calibration Sum: " . $bridgeRepair->computeAddMultiplySum() . PHP_EOL;
    echo "Full Calibration Sum (with concatenation): " . $bridgeRepair->computeFullOperatorSum() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
