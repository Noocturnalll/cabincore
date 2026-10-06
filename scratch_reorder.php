<?php
$lines = file('resources/views/components/layouts/partials/sidebar.blade.php');
$blocks = [
    'A' => array_slice($lines, 0, 25),
    'B' => array_slice($lines, 25, 32), // 26-57 -> 32 lines
    'C' => array_slice($lines, 57, 27), // 58-84 -> 27 lines
    'D' => array_slice($lines, 84, 42), // 85-126 -> 42 lines
    'E' => array_slice($lines, 126, 25), // 127-151 -> 25 lines
    'F' => array_slice($lines, 151, 25), // 152-176 -> 25 lines
    'G' => array_slice($lines, 176, 15), // 177-191 -> 15 lines
    'H' => array_slice($lines, 191, 38), // 192-229 -> 38 lines
    'I' => array_slice($lines, 229, 29), // 230-258 -> 29 lines
    'J' => array_slice($lines, 258, 34), // 259-292 -> 34 lines
    'K' => array_slice($lines, 292, 77), // 293-369 -> 77 lines
    'L' => array_slice($lines, 369), // 370-end
];
$total = 0;
foreach($blocks as $k => $b) { 
    $total += count($b); 
}
if ($total == count($lines)) {
    // Write new order
    $newLines = array_merge(
        $blocks['A'],
        $blocks['B'],
        $blocks['D'], // Cabin Maintenance
        $blocks['H'], // Aircraft Cleaning
        $blocks['I'], // Team Painting
        $blocks['J'], // Team CM Irreg
        $blocks['K'], // Inventory Management
        $blocks['E'], // NSRDI Management
        $blocks['F'], // ICT Management
        $blocks['G'], // Capacity Management
        $blocks['C'], // Operasional
        $blocks['L']  // Analitik & Sistem
    );
    file_put_contents('resources/views/components/layouts/partials/sidebar.blade.php', implode('', $newLines));
    echo "Success: ".count($newLines)." lines written.";
} else {
    echo "Error: Total $total does not match original ".count($lines);
}
