<?php

include 'bid_request.php';
include 'campaigns.php';

function handleBid($bidRequestJson, $campaigns) {
    $bidRequest = json_decode($bidRequestJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(['error' => 'Invalid JSON format']);
    }

    // Check if necessary bid request data exists
    if (empty($bidRequest['device']) || empty($bidRequest['imp']) || !isset($bidRequest['device']['make']) || !isset($bidRequest['device']['os']) || !isset($bidRequest['device']['geo'])) {
        return json_encode(['error' => 'Missing essential bid request data']);
    }
    
    $deviceMaker = $bidRequest['device']['make'];
    $os = $bidRequest['device']['os'];
    $impressions = $bidRequest['imp'];
    $userLocation = $bidRequest['device']['geo'];
    $matchingCampaigns = []; // Array to hold matching campaigns for each impression
    $radiusKm = 1000; // radius for considering a bid request(in km)

    foreach ($impressions as $imp) {
        $adformats = $imp['banner']['format'];
        $bidfloor = $imp['bidfloor'];
        $bestCampaign = null;

        foreach ($campaigns as $campaign) {
            // Check OS, device make, and if the campaign's price meets or exceeds the bid floor
            if ((stripos($campaign['hs_os'], $os) !== false || stripos($campaign['hs_os'], 'No Filter') !== false) &&
                (stripos($campaign['device_make'], $deviceMaker) !== false || stripos($campaign['device_make'], 'No Filter') !== false)) {
                
                $locationMatch = true;

                // Calculate distance between the campaign's and user's coordinates only if both lat and lng are provided
                if (!empty($campaign['lat']) && !empty($campaign['lng'])&&!empty($userLocation['lat']) && !empty($userLocation['lon'])) {
                    $distance = calculateDistance($userLocation['lat'], $userLocation['lon'], $campaign['lat'], $campaign['lng']);
                    $locationMatch = $distance <= $radiusKm;
                }

                // If location matches and price meets bidfloor, check for matching ad formats
                if ($locationMatch && $campaign['price'] >= $bidfloor) {
                    foreach ($adformats as $format) {
                        if ($campaign['dimension'] === $format['w'] . 'x' . $format['h']) {
                            // If there's no best campaign yet or the current campaign has a higher bid price, update the best campaign
                            if (!$bestCampaign || $campaign['price'] > $bestCampaign['bid_price']) {
                                $bestCampaign = [
                                    'campaign_name' => $campaign['campaignname'],
                                    'advertiser' => $campaign['advertiser'],
                                    'creative_type' => $campaign['creative_type'],
                                    'image_url' => $campaign['image_url'],
                                    'landing_page_url' => $campaign['url'],
                                    'bid_price' => $campaign['price'],
                                    'ad_id' => $campaign['code'],
                                    'creative_id' => $campaign['creative_id']
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Add the best campaign for this impression to the matching campaigns array
        if ($bestCampaign) {
            $matchingCampaigns[] = $bestCampaign;
        }
    }

    // Return the matching campaigns
    if (!empty($matchingCampaigns)) {
        return json_encode(['campaigns' => $matchingCampaigns]);
    } else {
        return json_encode(['error' => 'No matching campaign found']);
    }
}

// Function to calculate the distance between two coordinates using the Haversine formula
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadiusKm = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadiusKm * $c;
}

