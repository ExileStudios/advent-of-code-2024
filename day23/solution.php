<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 23: LAN Party
 *
 * Analyzes a network map to identify interconnected groups of computers. 
 * Determines the count of triads containing at least one computer starting with "t". 
 * Finds the largest clique of computers and outputs a password based on their names.
 * 
 */
final class LanParty
{
    /** @var array<string, array<string>> Adjacency list representing the network graph. */
    private array $network = [];

    /**
     * Constructor initializes the LAN Party solver from the given network connections file.
     *
     * @param string $filename Path to the input file containing network connections.
     * @throws RuntimeException If the file is unreadable or invalid.
     */
    public function __construct(string $filename)
    {
        $this->loadNetwork($filename);
    }

    /**
     * Loads the network connections into an adjacency list.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read.
     */
    private function loadNetwork(string $filename): void
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new RuntimeException("File not accessible: $filename");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Failed to read file: $filename");
        }

        foreach ($lines as $line) {
            [$a, $b] = explode('-', trim($line));
            $this->network[$a][] = $b;
            $this->network[$b][] = $a;
        }
    }

    /**
     * Counts all sets of three interconnected computers that include at least one computer
     * whose name starts with "t".
     *
     * @return int The count of valid triads.
     */
    public function countTriadsWithT(): int
    {
        $triads = $this->findAllTriads();
        return count(array_filter($triads, fn(array $triad) => $this->containsTComputer($triad)));
    }

    /**
     * Finds all unique sets of three interconnected computers in the network.
     *
     * @return array<array<string>> List of all triads.
     */
    private function findAllTriads(): array
    {
        $triads = [];

        foreach ($this->network as $computer => $neighbors) {
            foreach ($neighbors as $neighborA) {
                foreach ($neighbors as $neighborB) {
                    if ($neighborA !== $neighborB && in_array($neighborB, $this->network[$neighborA], true)) {
                        $triad = [$computer, $neighborA, $neighborB];
                        sort($triad); // Ensure uniqueness by sorting the names.
                        $triads[implode(',', $triad)] = $triad;
                    }
                }
            }
        }

        return array_values($triads);
    }

    /**
     * Checks if a triad contains a computer with a name starting with "t".
     *
     * @param array<string> $triad The triad to check.
     * @return bool True if at least one computer starts with "t".
     */
    private function containsTComputer(array $triad): bool
    {
        return array_reduce($triad, fn(bool $carry, string $name) => $carry || $name[0] === 't', false);
    }

    /**
     * Finds the largest clique (fully connected set of computers) in the network.
     * Generates the password by sorting the clique members and joining them with commas.
     *
     * @return string The LAN party password.
     */
    public function findLargestCliquePassword(): string
    {
        $maxClique = [];
        $computers = array_keys($this->network);

        $this->findCliques($computers, [], 0, function(array $clique) use (&$maxClique): void {
            if (count($clique) > count($maxClique)) {
                $maxClique = $clique;
            }
        });

        sort($maxClique);
        return implode(',', $maxClique);
    }

    /**
     * Recursive function to identify all cliques in the network.
     *
     * @param array<string> $candidates Remaining candidates for the clique.
     * @param array<string> $current Current clique being explored.
     * @param int $depth Current recursion depth.
     * @param callable $callback Callback to process each found clique.
     */
    private function findCliques(array $candidates, array $current, int $depth, callable $callback): void
    {
        foreach ($candidates as $candidate) {
            $newClique = array_merge($current, [$candidate]);

            if ($this->isClique($newClique)) {
                $callback($newClique);
                $newCandidates = array_filter($candidates, fn(string $c) => $c > $candidate);
                $this->findCliques($newCandidates, $newClique, $depth + 1, $callback);
            }
        }
    }

    /**
     * Verifies if a given set of computers forms a fully connected clique.
     *
     * @param array<string> $computers The set of computers to check.
     * @return bool True if the set forms a clique.
     */
    private function isClique(array $computers): bool
    {
        foreach ($computers as $a) {
            foreach ($computers as $b) {
                if ($a !== $b && !in_array($b, $this->network[$a] ?? [], true)) {
                    return false;
                }
            }
        }
        return true;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $lanParty = new LanParty($inputFile);

    echo "Number of triads containing 't': " . $lanParty->countTriadsWithT() . PHP_EOL;
    echo "LAN party password: " . $lanParty->findLargestCliquePassword() . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
