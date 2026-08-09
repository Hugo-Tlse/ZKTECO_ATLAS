<?php
// A exécuter chaque jour via une CRONTASK
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['sessionToken'] ?? null;
}

// Récupération des événements
function getEventList($sessionToken, $date, $startHour, $endHour, $max = 500) {
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

// Insertion SQL
function insertEvent($dbc, $event) {
    $dateh = date('Y-m-d H:i:s', strtotime($event['hwTime']));
    $evtCode = $event['evtCode'];
    $evtSubCode = $event['evtSubCode'] ?? 0;

    // Tableau complet des descriptions par evtCode.evtSubCode
    $descriptions = [
    '6.0'   => 'Connexion via application',
    '7.0'   => 'Déconnexion',
    '8.0'   => 'Connexion réussie',
	'48.0'  => 'Accès autorisé (Carte)',
    '49.0'  => 'Accès refusé (général)',
    '49.1'  => 'Accès refusé (Carte expirée)',
    '49.2'  => 'Accès refusé (Badge désactivé)',
    '49.3'  => 'Accès refusé (Tentative usurpation)',
    '49.11' => 'Accès refusé (En dehors de l\'horaire)',
    '49.12' => 'Accès refusé (Verrouillage manuel)',
    '49.13' => 'Accès refusé (Limite d\'entrée dépassée)',
    '49.14' => 'Accès refusé (Numéro de carte inconnu)',
    '50.0'  => 'Accès refusé (Porte verrouillée)',
    '51.0'  => 'Accès refusé (Alarme active)',
    '52.0'  => 'Alarme activée',
    '53.0'  => 'Alarme désactivée',
    '54.0'  => 'Connexion (portail web)',
    '55.0'  => 'Déconnexion (portail web)',
    '56.0'  => 'Tentative d’accès (carte non valide)',
	'127.0' => 'Horaire Inactif', 
    '128.0' => 'Horaire inactif',
    '212.0' => 'Connexion infructueuse (Mot de passe incorrect)',
	];


    $key = $evtCode . '.' . $evtSubCode;
    $description = $descriptions[$key] ?? "Événement code $key";

    $holder = $event['evtCredHolderRef'] ?? [];
    $prenom = $holder['first'] ?? '';
    $nom = $holder['last'] ?? '';
    $idNum = $holder['idNum'] ?? '';
    $user = trim("$prenom $nom ($idNum)");

    $badge = $event['evtCredRef']['credNum'] ?? $event['evtCredRef']['name'] ?? '';
    $porte = $event['evtDevRef']['name'] ?? $event['evtControllerRef']['name'] ?? 'Source inconnue';

    // Protection contre doublon
    $user_esc = mysqli_real_escape_string($dbc, $user);
    $badge_esc = mysqli_real_escape_string($dbc, $badge);
    $desc_esc = mysqli_real_escape_string($dbc, $description);
    $porte_esc = mysqli_real_escape_string($dbc, $porte);

    $check = mysqli_query($dbc, "SELECT 1 FROM API_ATLAS_event WHERE ATLAS_dateh = '$dateh' AND ATLAS_user = '$user_esc' AND ATLAS_Badge = '$badge_esc' LIMIT 1");
    if (mysqli_num_rows($check) === 0) {
        $stmt = mysqli_prepare($dbc, "INSERT INTO API_ATLAS_event (ATLAS_dateh, ATLAS_D, ATLAS_user, ATLAS_Badge, ATLAS_porte) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssss', $dateh, $description, $user, $badge, $porte);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}


// Exécution automatique pour la veille (J-1)
$date = (new DateTime('yesterday'))->format('Y-m-d');
$startHour = 0;
$endHour = 23;

$sessionToken = authenticate($username, $password, $apiClientType);
if ($sessionToken) {
    $events = getEventList($sessionToken, $date, $startHour, $endHour);
    if (!empty($events['instanceList'])) {
        foreach ($events['instanceList'] as $event) {
            insertEvent($dbc, $event);
        }
    }
}
?>
