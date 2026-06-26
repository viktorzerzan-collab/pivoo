<?php
// backend/api/update_profile.php
require_once '../core/ApiHandler.php';
require_once '../core/TOTPHandler.php'; // Přidáno pro 2FA

$api = new ApiHandler();
$user = JwtHandler::checkUser();

$action = $api->request->getParam('action', 'change_password');

// 1. Získání dat o novém QR kódu pro zapnutí 2FA
if ($action === 'setup_2fa') {
    try {
        $stmt = $api->db->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$user['user_id']]);
        $dbUser = $stmt->fetch();

        $secret = TOTPHandler::generateSecret();
        
        // Uložíme secret, ale 2FA zatím není "enabled" (musí se nejprve potvrdit kódem)
        $update = $api->db->prepare("UPDATE users SET totp_secret = ?, is_2fa_enabled = 0 WHERE id = ?");
        $update->execute([$secret, $user['user_id']]);

        // Název pro zobrazení v aplikaci (např. Google Authenticator)
        $qrCodeUrl = TOTPHandler::getQRCodeUrl('Pivoo.cz', $dbUser['email'], $secret);

        $api->response->sendSuccess("QR kód byl vygenerován.", [
            "qr_url" => $qrCodeUrl,
            "secret" => $secret
        ]);
    } catch (Exception $e) {
        $api->response->sendError("Chyba při přípravě 2FA.", 500);
    }
}

// 2. Potvrzení (aktivace) 2FA po zadání kódu z mobilu
if ($action === 'confirm_2fa') {
    $code = $api->request->getParam('totp_code');
    if (!$code) $api->response->sendError("Zadejte kód z aplikace.", 400);

    $stmt = $api->db->prepare("SELECT totp_secret FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $dbUser = $stmt->fetch();

    if ($dbUser && !empty($dbUser['totp_secret'])) {
        if (TOTPHandler::verifyCode($dbUser['totp_secret'], $code)) {
            $update = $api->db->prepare("UPDATE users SET is_2fa_enabled = 1 WHERE id = ?");
            $update->execute([$user['user_id']]);
            $api->response->sendSuccess("Dvoufázové ověření bylo úspěšně aktivováno.");
        } else {
            $api->response->sendError("Neplatný kód. Zkuste to znovu.", 400);
        }
    } else {
        $api->response->sendError("Chybí secret klíč. Zkuste to znovu od začátku.", 400);
    }
}

// 3. Vypnutí 2FA
if ($action === 'disable_2fa') {
    try {
        $update = $api->db->prepare("UPDATE users SET is_2fa_enabled = 0, totp_secret = NULL WHERE id = ?");
        $update->execute([$user['user_id']]);
        $api->response->sendSuccess("Dvoufázové ověření bylo vypnuto.");
    } catch (Exception $e) {
        $api->response->sendError("Chyba při vypínání 2FA.", 500);
    }
}

if ($action === 'update_currency') {
    $currency_raw = $api->request->getParam('default_currency');
    $currency = $currency_raw ? strtoupper(trim($currency_raw)) : null;
    $allowed = ['CZK', 'EUR', 'PLN', 'GBP'];
    
    if ($currency && in_array($currency, $allowed)) {
        try {
            $update = $api->db->prepare("UPDATE users SET default_currency = ? WHERE id = ?");
            if ($update->execute([$currency, $user['user_id']])) {
                $api->response->sendSuccess("Výchozí měna byla uložena.");
            } else {
                $api->response->sendError("Chyba při ukládání měny do databáze.", 500);
            }
        } catch (Exception $e) {
            error_log("DB Error (update_profile currency): " . $e->getMessage());
            $api->response->sendError("Interní chyba serveru.", 500);
        }
    } else {
        $api->response->sendError("Neplatná měna.", 400);
    }
}

if ($action === 'update_theme') {
    $mode = $api->request->getParam('theme_mode');
    $pref = $api->request->getParam('theme_preference');

    if ($mode || $pref) {
        try {
            if ($mode && $pref) {
                $update = $api->db->prepare("UPDATE users SET theme_mode = ?, theme_preference = ? WHERE id = ?");
                $success = $update->execute([$mode, $pref, $user['user_id']]);
            } else if ($mode) {
                $update = $api->db->prepare("UPDATE users SET theme_mode = ? WHERE id = ?");
                $success = $update->execute([$mode, $user['user_id']]);
            } else if ($pref) {
                $update = $api->db->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
                $success = $update->execute([$pref, $user['user_id']]);
            }

            if ($success) {
                $api->response->sendSuccess("Nastavení vzhledu uloženo.");
            } else {
                $api->response->sendError("Chyba při ukládání do databáze.", 500);
            }
        } catch (Exception $e) {
            error_log("DB Error (update_profile theme): " . $e->getMessage());
            $api->response->sendError("Interní chyba serveru.", 500);
        }
    } else {
        $api->response->sendError("Chybí data pro aktualizaci.", 400);
    }
}

if ($action === 'change_password') {
    $old_password = $api->request->getParam('old_password');
    $new_password = $api->request->getParam('new_password');

    if ($old_password && $new_password) {
        $stmt = $api->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user['user_id']]);
        $dbUser = $stmt->fetch();

        if ($dbUser && password_verify($old_password, $dbUser['password_hash'])) {
            if (strlen($new_password) < 8 || !preg_match('/[0-9]/', $new_password) || !preg_match('/[^a-zA-Z0-9]/', $new_password)) {
                $api->response->sendError("Nové heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak.", 400);
            }

            try {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $api->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                
                if ($update->execute([$new_hash, $user['user_id']])) {
                    $api->response->sendSuccess("Heslo bylo úspěšně změněno.");
                } else {
                    $api->response->sendError("Chyba při ukládání do databáze.", 500);
                }
            } catch (Exception $e) {
                error_log("DB Error (update_profile password): " . $e->getMessage());
                $api->response->sendError("Interní chyba serveru.", 500);
            }
        } else {
            $api->response->sendError("Původní heslo není správné.", 400);
        }
    } else {
        $api->response->sendError("Vyplňte všechna pole.", 400);
    }
}

$api->response->sendError("Neplatná akce.", 400);
?>