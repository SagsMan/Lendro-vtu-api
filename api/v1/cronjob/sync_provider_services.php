<?php

require __DIR__ . "/../db.php";
require __DIR__ . "/../ProviderFactory.php";

//$stmt = $db->query("SELECT * FROM providers WHERE active = 1");
$providers = PROVIDER_SLUGS;

foreach ($providers as $providerData) {

    echo "Syncing: " . $providerData['slug'] . PHP_EOL;

    $provider = ProviderFactory::make($providerData['slug']);

    $services = $provider->getServices(); 
    // MUST be implemented per provider

    foreach ($services as $service) {
        /*
        Expected structure:
        [
            'service_key' => 'mtn_data_1gb_sme',
            'name' => 'MTN 1GB',
            'type' => 'data',
            'network' => 'MTN',
            'code' => 'MTN_1GB_A',
            'price' => 1100
        ]
        */

        // 1. find or create internal service
        $stmt = $db->prepare("SELECT id FROM services WHERE service_key = ?");

        $stmt->execute([
            $service['service_key']
        ]);

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {

            $stmt = $db->prepare("
                INSERT INTO services (name, type, network, price)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $service['name'],
                $service['type'],
                $service['network'],
                $service['price']
            ]);

            $serviceId = $db->lastInsertId();

        } else {
            $serviceId = $existing['id'];
        }

        // 2. map provider service
        $stmt = $db->prepare("
            SELECT id FROM provider_services
            WHERE service_id = ? AND provider_id = ?
        ");

        $stmt->execute([
            $serviceId,
            $providerData['id']
        ]);

        $map = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$map) {

            $stmt = $db->prepare("
                INSERT INTO provider_services
                (service_id, provider_id, provider_code, cost_price, active)
                VALUES (?, ?, ?, ?, 1)
            ");

            $stmt->execute([
                $serviceId,
                $providerData['id'],
                $service['code'],
                $service['price']
            ]);
        }
    }
}