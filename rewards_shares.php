<?php
// API URL
$url = "https://api.etherscan.io/v2/api?chainid=1&module=account&action=txsBeaconWithdrawal&address=[yourrocketpoolwithdrawlrecipientcontractaddress]&startblock=0&endblock=99999999&page=1&offset=100&sort=asc&apikey=[youretherscanv2apikey]";

// Fetch the API response
$response = file_get_contents($url);
if ($response === FALSE) { die("Error fetching data."); }
$data = json_decode($response, true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Etherscan Beacon Withdrawals</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; font-family: monospace; }
        th { background-color: #f2f2f2; text-align: left; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h2>Etherscan Beacon Withdrawals</h2>
<?php
if (!isset($data['result']) || !is_array($data['result'])) {
    echo "<p>No results found.</p>";
} else {
    echo "<table>";

    // Table headers (original + custom)
    $headers = array_keys($data['result'][0]);
    echo "<tr>";
    foreach ($headers as $header) {
        echo "<th>" . htmlspecialchars($header) . "</th>";
    }
    echo "<th>25% 8 eth</th><th>75% 24 eth</th><th>14% fee</th><th>reth share</th><th>myshare</th>";
    echo "</tr>";

    // Rows
    foreach ($data['result'] as $row) {
        echo "<tr>";

        // We'll store the full-precision ETH amount for math
        $amountEth = null;

        foreach ($headers as $header) {
            $value = $row[$header];

            // Format timestamp (lowercase key per API)
            if ($header === "timestamp") {
                $value = date("Y-m-d H:i:s", (int)$value);
            }

            // Convert amount (gwei -> ETH) and keep full precision for calculations
            if ($header === "amount") {
                $amountEth = ((float)$value) / 1000000000.0; // gwei → ETH
                $value = number_format($amountEth, 6, '.', '') . " ETH"; // display only
            }

            // Trim address to 6 chars
            if ($header === "address") {
                $value = substr($value, 0, 6);
            }

            echo "<td>" . htmlspecialchars($value) . "</td>";
        }

        // If amount wasn't present for some reason, treat as zero
        if ($amountEth === null) { $amountEth = 0.0; }

        // ---- Calculations (full precision) ----
        $share25 = $amountEth * 0.25;          // 25% of total
        $share75 = $amountEth * 0.75;          // 75% of total
        $fee14   = $share75 * 0.14;            // 14% of the 75% portion
        $reth    = $share75 - $fee14;          // remainder after fee
        $myshare = $share25 + $fee14;          // matches your Excel: 25% + 14% of 75%

        // ---- Display (round only at output) ----
        echo "<td>" . number_format($share25, 6, '.', '') . "</td>";
        echo "<td>" . number_format($share75, 6, '.', '') . "</td>";
        echo "<td>" . number_format($fee14, 8, '.', '') . "</td>";
        echo "<td>" . number_format($reth, 8, '.', '') . "</td>";
        echo "<td>" . number_format($myshare, 8, '.', '') . "</td>";

        echo "</tr>";
    }

    echo "</table>";
}
?>
</body>
</html>
