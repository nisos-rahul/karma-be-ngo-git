<?php

require_once("Kraken.php");

$kraken = new Kraken("050138d4da963e26f8c8c57eeb797ad0", "3389e4d1a388663ebf5a44c6293404013cdd1858");

$params = array(
    "file" => "rose-113735_960_720.jpg",
    "wait" => true,
    "lossy" => true,
    "s3_store" => array(
        "key" => "AKIAIDUJLUHHOLVSM5LQ",
        "secret" => "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",
        "bucket" => "nisos-karma-dev",
        "path" => "kraken-images/1rose-113735_960_720.jpg"
    )
);

$data = $kraken->upload($params);

if (!empty($data["success"])) {

    // optimization succeeded
    echo "Success. Optimized image URL: " . $data["kraked_url"];
} elseif (isset($data["message"])) {

    // something went wrong with the optimization
    echo "Optimization failed. Error message from Kraken.io: " . $data["message"];
} else {

    // something went wrong with the request
    echo "cURL request failed. Error message: " . $data["error"];
}	
