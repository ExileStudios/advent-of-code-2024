<?php
declare(strict_types=1);

/**
 * Advent of Code 2024 - Day 12: Garden Groups
 *
 * Computes fencing costs for garden regions:
 * Total Fence Cost - Based on the region's perimeter.
 * Bulk Discount Fence Cost - Based on the number of distinct sides.
 * 
 */
final class GardenFenceCalculator
{
    /**
     * @var array<array<string>> The garden grid representation.
     */
    private array $grid;

    /**
     * @var int Number of rows in the grid.
     */
    private int $rows;

    /**
     * @var int Number of columns in the grid.
     */
    private int $cols;

    /**
     * Constructor initializes the grid and its dimensions.
     *
     * @param string $filename Path to the input file.
     * @throws RuntimeException If the file cannot be read.
     */
    public function __construct(string $filename)
    {
        $this->grid = $this->loadGrid($filename);
        $this->rows = count($this->grid);
        $this->cols = $this->rows > 0 ? count($this->grid[0]) : 0;
    }

    /**
     * Reads and parses the input grid from the file.
     *
     * @param string $filename Path to the input file.
     * @return array<array<string>> Parsed grid.
     * @throws RuntimeException If the file cannot be read.
     */
    private function loadGrid(string $filename): array
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read file: $filename");
        }
        return array_map('str_split', $lines);
    }

    /**
     * Calculates fencing costs for each region.
     *
     * @return array<string, int> Costs for total fence and bulk discount fence.
     */
    public function calculateFencingCosts(): array
    {
        $visited = array_fill(0, $this->rows, array_fill(0, $this->cols, false));
        $totalFenceCost = 0;
        $bulkDiscountFenceCost = 0;

        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                if (!$visited[$r][$c]) {
                    [$area, $perimeter, $distinctSides, $regionType] = $this->measureRegion($r, $c, $visited);

                    $totalFenceCost += $area * $perimeter;
                    $bulkDiscountFenceCost += $area * $distinctSides;
                }
            }
        }

        return [
            'totalFenceCost' => $totalFenceCost,
            'bulkDiscountFenceCost' => $bulkDiscountFenceCost,
        ];
    }

    /**
     * Measures the area, perimeter, and distinct sides of a region.
     *
     * @param int $startRow Starting row of the region.
     * @param int $startCol Starting column of the region.
     * @param array<array<bool>> &$visited Grid to track visited cells.
     * @return array{int, int, int, string} Area, perimeter, distinct sides, and region type.
     */
    private function measureRegion(int $startRow, int $startCol, array &$visited): array
    {
        $regionType = $this->grid[$startRow][$startCol];
        $queue = [[$startRow, $startCol]];
        $visited[$startRow][$startCol] = true;

        $area = 0;
        $edges = [];

        $deltas = [
            [-1, 0], // Up
            [1, 0],  // Down
            [0, -1], // Left
            [0, 1],  // Right
        ];

        while (!empty($queue)) {
            [$row, $col] = array_pop($queue);
            $area++;

            foreach ($deltas as $d => [$dr, $dc]) {
                $newRow = $row + $dr;
                $newCol = $col + $dc;

                if ($this->isOutOfBounds($newRow, $newCol) || $this->grid[$newRow][$newCol] !== $regionType) {
                    $edges[] = $this->canonicalEdge($row, $col, $d);
                } elseif (!$visited[$newRow][$newCol]) {
                    $visited[$newRow][$newCol] = true;
                    $queue[] = [$newRow, $newCol];
                }
            }
        }

        $perimeter = count($edges);
        $distinctSides = $this->countDistinctSides($edges);

        return [$area, $perimeter, $distinctSides, $regionType];
    }

    /**
     * Converts a grid cell and direction into a canonical edge.
     *
     * @param int $r Row of the cell.
     * @param int $c Column of the cell.
     * @param int $direction Direction index.
     * @return array<int> Canonical edge coordinates.
     */
    private function canonicalEdge(int $r, int $c, int $direction): array
    {
        switch ($direction) {
            case 0: return [$r, $c, $r, $c+1];       // Up
            case 1: return [$r+1, $c, $r+1, $c+1];   // Down
            case 2: return [$r, $c, $r+1, $c];       // Left
            case 3: return [$r, $c+1, $r+1, $c+1];   // Right
        }
        throw new RuntimeException("Invalid direction: $direction");
    }

    /**
     * Counts the distinct sides of a region by merging collinear edges.
     *
     * @param array<array<int>> $edges The list of edges.
     * @return int The number of distinct sides.
     */
    private function countDistinctSides(array $edges): int
    {
        // Build a graph of points. Each point is (row,col).
        // Each edge connects two points. We must find closed loops.
        // Then, for each loop, order edges and merge collinear segments.

        // Convert edges to adjacency list
        // Node key: "r,c" string
        $graph = [];
        foreach ($edges as [$r1, $c1, $r2, $c2]) {
            $p1 = "$r1,$c1";
            $p2 = "$r2,$c2";
            $graph[$p1][] = [$p2, $r1, $c1, $r2, $c2];
            $graph[$p2][] = [$p1, $r2, $c2, $r1, $c1];
        }

        $visitedNodes = [];
        $totalSides = 0;

        // Find closed loops. Each loop: start from a node, follow edges until closed.
        foreach (array_keys($graph) as $startNode) {
            if (isset($visitedNodes[$startNode])) {
                continue;
            }
            // Perform a cycle detection. Each connected component of edges is expected to form one or more loops.
            // Each node in this problem has even degree (2 or more), forming closed loops.
            // Extract all edges in this connected component.
            $componentNodes = [];
            $stack = [$startNode];
            $visitedSet = [];
            $componentEdges = [];

            while (!empty($stack)) {
                $node = array_pop($stack);
                if (isset($visitedSet[$node])) continue;
                $visitedSet[$node] = true;
                $componentNodes[] = $node;
                foreach ($graph[$node] as [$adj, $r1, $c1, $r2, $c2]) {
                    $componentEdges[] = [$r1,$c1,$r2,$c2];
                    if (!isset($visitedSet[$adj])) $stack[] = $adj;
                }
            }

            // Mark visited nodes
            foreach ($componentNodes as $n) {
                $visitedNodes[$n] = true;
            }

            // The component may form one or more loops. We must identify loops by tracing edges.
            // Represent edges in a way we can follow them to form loops.
            // We'll build a multi-graph. Each edge appears twice in componentEdges, once per direction.
            // We must remove duplicates and then find loops by walking the polygon boundary.
            $uniqueEdges = [];
            foreach ($componentEdges as $e) {
                $key1 = "{$e[0]},{$e[1]}-{$e[2]},{$e[3]}";
                $key2 = "{$e[2]},{$e[3]}-{$e[0]},{$e[1]}";
                if (!isset($uniqueEdges[$key1]) && !isset($uniqueEdges[$key2])) {
                    $uniqueEdges[$key1] = $e;
                }
            }
            $componentEdges = array_values($uniqueEdges);

            // Build adjacency for loop reconstruction (undirected but we must order edges around the loop)
            $edgeMap = [];
            foreach ($componentEdges as [$r1,$c1,$r2,$c2]) {
                $p1 = "$r1,$c1";
                $p2 = "$r2,$c2";
                $edgeMap[$p1][] = [$r2,$c2];
                $edgeMap[$p2][] = [$r1,$c1];
            }

            // Find loops: pick an unused edge, follow until back to start.
            $usedEdges = [];
            $loops = [];
            foreach ($componentEdges as $e) {
                $ekey = "{$e[0]},{$e[1]}-{$e[2]},{$e[3]}";
                $ekeyRev = "{$e[2]},{$e[3]}-{$e[0]},{$e[1]}";
                if (isset($usedEdges[$ekey]) || isset($usedEdges[$ekeyRev])) continue;

                // trace a loop
                $loop = [];
                $cur = [$e[0], $e[1]];
                $end = [$e[0], $e[1]];
                $next = [$e[2], $e[3]];
                $loop[] = [$cur[0],$cur[1],$next[0],$next[1]];
                $usedEdges[$ekey] = true;
                $usedEdges[$ekeyRev] = true;

                while (!($next[0] == $end[0] && $next[1] == $end[1])) {
                    $neighbors = $edgeMap["{$next[0]},{$next[1]}"];
                    // Find the next edge not used yet
                    $found = false;
                    foreach ($neighbors as [$nr,$nc]) {
                        $k1 = "{$next[0]},{$next[1]}-{$nr},{$nc}";
                        $k2 = "{$nr},{$nc}-{$next[0]},{$next[1]}";
                        if (!isset($usedEdges[$k1]) && !isset($usedEdges[$k2])) {
                            $loop[] = [$next[0],$next[1],$nr,$nc];
                            $usedEdges[$k1] = true;
                            $usedEdges[$k2] = true;
                            $next = [$nr,$nc];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        // Should not happen if loops are well-formed
                        break;
                    }
                }
                $loops[] = $loop;
            }

            // For each loop, merge collinear edges
            foreach ($loops as $loop) {
                $totalSides += $this->countLoopSides($loop);
            }
        }

        return $totalSides;
    }

    /**
     * Counts the number of distinct sides in a polygonal loop by merging collinear edges.
     *
     * @param array<array<int>> $loop The list of edges forming the loop, 
     *                                where each edge is represented as [startRow, startCol, endRow, endCol].
     * @return int The number of distinct sides in the loop.
     */
    private function countLoopSides(array $loop): int
    {
        // loop is an array of edges: [r1,c1,r2,c2]
        // First, ensure edges are in polygon order. They should be from the loop construction.
        // Merge consecutive collinear edges.
        // Two edges are collinear if they lie in the same direction (horizontal or vertical)
        // We know each edge is either horizontal or vertical from how we built them.
        // So check direction and continuity.

        // The loop is already in order. Just iterate and check consecutive edges.
        // Because it's a loop, wrap around at the end.
        // Convert edges to direction vectors
        $segments = [];
        foreach ($loop as [$r1,$c1,$r2,$c2]) {
            $dr = $r2 - $r1;
            $dc = $c2 - $c1;
            // Normalize direction: horizontal (dr=0), vertical (dc=0)
            // Store as (dr,dc,startR,startC,endR,endC)
            $segments[] = [$dr,$dc,$r1,$c1,$r2,$c2];
        }

        $merged = [];
        $current = $segments[0];

        for ($i = 1; $i < count($segments); $i++) {
            $next = $segments[$i];
            if ($next[0] == $current[0] && $next[1] == $current[1]) {
                // same direction, merge
                $current[4] = $next[4]; // endR
                $current[5] = $next[5]; // endC
            } else {
                $merged[] = $current;
                $current = $next;
            }
        }

        // Check if last and first can merge (loop wrap-around)
        // Compare the direction of $merged[0] (if exists) and $current
        if (!empty($merged)) {
            $first = $merged[0];
            if ($first[0] == $current[0] && $first[1] == $current[1]) {
                // merge these two
                $merged[0] = [$first[0],$first[1],$current[2],$current[3],$first[4],$first[5]];
            } else {
                $merged[] = $current;
            }
        } else {
            // Only one segment in loop
            $merged[] = $current;
        }

        // Number of sides = number of merged segments
        return count($merged);
    }
    
    /**
     * Checks if a cell is outside the grid bounds.
     *
     * @param int $row Row index.
     * @param int $col Column index.
     * @return bool True if out of bounds, false otherwise.
     */
    private function isOutOfBounds(int $row, int $col): bool
    {
        return $row < 0 || $row >= $this->rows || $col < 0 || $col >= $this->cols;
    }
}

// Main execution
$inputFile = $argv[1] ?? (__DIR__ . '/input.txt');

try {
    $calculator = new GardenFenceCalculator($inputFile);
    $costs = $calculator->calculateFencingCosts();
    echo "Total Fence Cost (Perimeter-based): {$costs['totalFenceCost']}" . PHP_EOL;
    echo "Bulk Discount Fence Cost (Distinct Sides): {$costs['bulkDiscountFenceCost']}" . PHP_EOL;
} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
