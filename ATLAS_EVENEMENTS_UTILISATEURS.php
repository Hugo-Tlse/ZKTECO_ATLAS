<?php
date_default_timezone_set('Europe/Paris');

// Configuration API
$username = 'API';
$password = 'MOT_DE_PASSE';
$apiClientType = 0;

// Authentification
function authenticate($username, $password, $apiClientType) {
    $timestamp = round(microtime(true) * 1000);
    $url = "URL.fr/authenticate?apiClientType=$apiClientType&username=" . urlencode($username) . "&password=" . urlencode($password) . "&_=$timestamp";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // désactive la vérif SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['sessionToken'] ?? null;
}

// Récupération brute des événements
function getRawEvents($sessionToken, $date, $startHour = 0, $endHour = 23, $max = 50) {
    $timestamp = round(microtime(true) * 1000);
    $dt = new DateTime($date);

    $params = http_build_query([
        'max' => $max,
        'evtCategoryRestriction.evtCategories' => [0, 1, 2, 3],
        'hwTimeRestriction.beforeDate_year' => $dt->format('Y'),
        'hwTimeRestriction.beforeDate_month' => $dt->format('n'),
        'hwTimeRestriction.beforeDate_day' => $dt->format('j'),
        'hwTimeRestriction.beforeDate_hour' => $endHour,
        'hwTimeRestriction.afterDate_year' => $dt->format('Y'),
        'hwTimeRestriction.afterDate_month' => $dt->format('n'),
        'hwTimeRestriction.afterDate_day' => $dt->format('j'),
        'hwTimeRestriction.afterDate_hour' => $startHour,
        '_' => $timestamp
    ]);

    $url = "URL.fr/evt/list?$params";

    $headers = [
        "sessionToken: $sessionToken",
        "User-Agent: Mozilla/5.0",
        "Accept: */*",
        "Referer: URL.fr/",
        "overrideLanguage: fr"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// --- Lancement ---
$date = (new DateTime('yesterday'))->format('Y-m-d'); // ou date('Y-m-d') pour aujourd’hui
$sessionToken = authenticate($username, $password, $apiClientType);

if ($sessionToken) {
    $events = getRawEvents($sessionToken, $date);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "Échec d'authentification auprès de la centrale.";
}
?>
