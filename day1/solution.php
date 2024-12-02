<?php

/**
 * Advent of Code 2024 - Day 1: Historian Hysteria
 * Computes total distance and similarity score between two lists of location IDs.
 * 
 * Created on December 1st, 2024
 *
 * @author   ExileStudios
 * @link     https://github.com/ExileStudios
 */
class HistorianHysteria
{
    private array $leftList;
    private array $rightList;

    /**
     * Constructor to initialize location ID lists from the given file.
     *
     * @param string $filename Path to the input file containing location IDs.
     * @throws RuntimeException If the file cannot be found or read.
     */
    public function __construct(string $filename)
    {
        [$this->leftList, $this->rightList] = $this->parseLists($filename);
    }

    /**
     * Parses the input file and returns two lists of location IDs.
     *
     * @param string $filename Path to the input file.
     * @return array<array<int>> Two lists of location IDs: [leftList, rightList].
     * @throws RuntimeException If the file cannot be found or read.
     */
    private function parseLists(string $filename): array
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }

        $leftList = $rightList = [];
        foreach (file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            [$left, $right] = preg_split('/\s+/', trim($line));
            $leftList[] = (int)$left;
            $rightList[] = (int)$right;
        }

        return [$leftList, $rightList];
    }

    /**
     * Calculates the total distance between the left and right lists.
     *
     * The total distance is computed by pairing the smallest elements from each list
     * and summing the absolute differences of each pair.
     *
     * @return int The calculated total distance.
     */
    public function computeTotalDistance(): int
    {
        $leftList = $this->leftList;
        $rightList = $this->rightList;

        sort($leftList);
        sort($rightList);

        return array_sum(array_map(fn($l, $r) => abs($l - $r), $leftList, $rightList));
    }

    /**
     * Calculates the similarity score between the left and right lists.
     *
     * The similarity score is the sum of each value in the left list multiplied by its
     * frequency in the right list.
     *
     * @return int The calculated similarity score.
     */
    public function computeSimilarityScore(): int
    {
        $rightCounts = array_count_values($this->rightList);

        return array_sum(array_map(
            fn($l) => $l * ($rightCounts[$l] ?? 0),
            $this->leftList
        ));
    }
}

$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $hysteria = new HistorianHysteria($inputFile);
    echo "Total Distance: " . $hysteria->computeTotalDistance() . "\n";
    echo "Similarity Score: " . $hysteria->computeSimilarityScore() . "\n";
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
