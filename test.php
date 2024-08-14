<?php

include 'request_handler.php';

function runTests() {
    $tests = [];

    // Test Case 1: Valid bid request with matching campaign
    $tests[] = [
        'name' => 'Valid bid request with matching campaign',
        'input' => '{
            "id": "test1",
            "imp": [
                {
                    "id": "1",
                    "banner": {
                        "format": [{"w": 320, "h": 480}]
                    },
                    "bidfloor": 0.05
                }
            ],
            "device": {
                "make": "xiaomi",
                "os": "Android",
                "geo": {
                    "lat": 23.774545669555664,
                    "lon": 90.440811157226562
                }
            }
        }',
        'expected' => 'Test_Banner_13th-31st_march_Developer'
    ];

    // Test Case 2: Bid request with no matching campaign
    $tests[] = [
        'name' => 'Bid request with no matching campaign',
        'input' => '{
            "id": "test2",
            "imp": [
                {
                    "id": "1",
                    "banner": {
                        "format": [{"w": 728, "h": 90}]
                    },
                    "bidfloor": 0.5
                }
            ],
            "device": {
                "make": "Samsung",
                "os": "iOS",
                "geo": {
                    "lat": 23.774545669555664,
                    "lon": 90.440811157226562
                }
            }
        }',
        'expected' => 'No matching campaign found'
    ];

    // Test Case 3: Invalid bid request (missing essential data)
    $tests[] = [
        'name' => 'Invalid bid request (missing essential data)',
        'input' => '{
            "id": "test3",
            "imp": [],
            "device": {
                "make": "xiaomi"
            }
        }',
        'expected' => 'Missing essential bid request data'
    ];

    // Run the tests
    foreach ($tests as $index => $test) {
        echo "Test Case " . ($index + 1) . ": " . $test['name'] . "\n";

        $result = handleBid($test['input'], $GLOBALS['campaigns']);
        $decodedResult = json_decode($result, true);

        if (isset($decodedResult['campaigns'][0]['campaign_name'])) {
            $actual = $decodedResult['campaigns'][0]['campaign_name'];
        } else {
            $actual = $decodedResult['error'];
        }

        echo "Expected: " . $test['expected'] . "\n";
        echo "Actual: " . $actual . "\n";

        if ($actual === $test['expected']) {
            echo "Result: Passed\n";
        } else {
            echo "Result: Failed\n";
        }

        echo "\n";
    }
}

runTests();
