<?php

// Config API
$username = 'API'; // créer un utilisateur API
$password = 'MOT_DE_PASSE';
$apiClientType = 0;

// REMPLACER "URL.fr" par le lien URL de ATLAS
// CREER LA TABLE API_ATLAS_BADGES

/** Authentification **/
function authenticate($username, $password, $apiClientType) {
    $timestamp = round(microtime(true) * 1000);
    $url = "URL.fr/authenticate?apiClientType=$apiClientType&username=" . urlencode($username) . "&password=" . urlencode($password) . "&_=$timestamp";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['sessionToken'] ?? null;
}

/** Liste utilisateurs **/
function getUserList($sessionToken, $max = 1000) {
    $timestamp = round(microtime(true) * 1000);
    $url = "URL.fr/credHolder/list?_=$timestamp&max=$max&sort=name&order=asc";

    $headers = [
        "sessionToken: $sessionToken",
        "User-Agent: Mozilla/5.0",
        "Accept: */*",
        "Referer: URL.fr/",
        "overrideLanguage: fr"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true)['instanceList'] ?? [];
}

// Authentification
$token = authenticate($username, $password, $apiClientType);
if (!$token) {
    error_log("[ATLAS] Authentification échouée à " . date("Y-m-d H:i:s"));
    exit("Erreur d'authentification.\n");
}

// Préparation requête SQL
$stmt = $dbc->prepare("
    INSERT INTO API_ATLAS_BADGES (
        API_ATLAS_ZKID, API_ATLAS_BADGE, API_ATLAS_PRENOM, API_ATLAS_NOM,
        API_ATLAS_LICENCE, API_ATLAS_A1, API_ATLAS_A2, API_ATLAS_A3, API_ATLAS_A4
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        API_ATLAS_BADGE = VALUES(API_ATLAS_BADGE),
        API_ATLAS_PRENOM = VALUES(API_ATLAS_PRENOM),
        API_ATLAS_NOM = VALUES(API_ATLAS_NOM),
        API_ATLAS_LICENCE = VALUES(API_ATLAS_LICENCE),
        API_ATLAS_A1 = VALUES(API_ATLAS_A1),
        API_ATLAS_A2 = VALUES(API_ATLAS_A2),
        API_ATLAS_A3 = VALUES(API_ATLAS_A3),
        API_ATLAS_A4 = VALUES(API_ATLAS_A4)
");

if (!$stmt) {
    error_log("[ATLAS] Erreur de préparation SQL : " . $dbc->error);
    exit("Erreur SQL.\n");
}

// Traitement des utilisateurs
$list = getUserList($token);
$inserted = 0;
$updated = 0;

foreach ($list as $user) {
    $zkid    = $user['unid'] ?? null;
    $badge   = $user['creds'][0]['name'] ?? null;
    $prenom  = $user['first'] ?? null;
    $nom     = $user['last'] ?? null;
    $licence = $user['idNum'] ?? null;

    // Privs -> A1 à A4
    $privs = [];
    if (!empty($user['privBindings'])) {
        foreach ($user['privBindings'] as $pb) {
            $privName = $pb['priv']['name'] ?? '';
            if ($privName && !in_array($privName, $privs)) {
                $privs[] = $privName;
            }
        }
    }

    // Compléter jusqu’à 4 max
    $a1 = $privs[0] ?? null;
    $a2 = $privs[1] ?? null;
    $a3 = $privs[2] ?? null;
    $a4 = $privs[3] ?? null;

    if (!$zkid) continue;

    $stmt->bind_param("issssssss", $zkid, $badge, $prenom, $nom, $licence, $a1, $a2, $a3, $a4);
	if ($stmt->execute()) {
		if ($stmt->affected_rows > 0) {
			$inserted++;
		}
	}

}

$stmt->close();
$dbc->close();

echo "[ATLAS] Mise à jour terminée à " . date("Y-m-d H:i:s") . "\n";
echo "Insérés : $inserted\n";
echo "Mis à jour : $updated\n";


?>
