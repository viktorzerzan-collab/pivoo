<?php
// backend/core/TOTPHandler.php

class TOTPHandler {
    // Znaky povolené pro Base32 kódování (požadováno pro Authenticator aplikace)
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Vygeneruje náhodný tajný klíč (secret)
     */
    public static function generateSecret($length = 16) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Vrátí URL adresu pro vygenerování QR kódu k naskenování
     */
    public static function getQRCodeUrl($companyName, $userName, $secret) {
        $companyName = rawurlencode($companyName);
        $userName = rawurlencode($userName);
        // Formát URI specifický pro Authenticator aplikace
        $otpauth = "otpauth://totp/{$companyName}:{$userName}?secret={$secret}&issuer={$companyName}";
        
        // Využijeme veřejné API pro vygenerování samotného obrázku QR kódu
        $url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . rawurlencode($otpauth);
        return $url;
    }

    /**
     * Ověří, zda uživatel zadal správný šestimístný kód
     * $discrepancy = 1 znamená, že tolerujeme zpoždění nebo předstih +/- 30 sekund
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        if (empty($secret) || empty($code)) return false;
        
        $currentTimeSlice = floor(time() / 30);
        
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, (string)$code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vypočítá platný kód pro daný časový úsek
     */
    private static function getCode($secret, $timeSlice) {
        $secretKey = self::base32Decode($secret);
        
        // 64-bit big endian (pack 'J' funguje od PHP 5.6+)
        $timeBytes = pack('J', $timeSlice);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $timeBytes, $secretKey, true);
        
        $offset = ord(substr($hash, -1)) & 0x0F;
        $hashPart = substr($hash, $offset, 4);
        $value = unpack('N', $hashPart)[1];
        $value = $value & 0x7FFFFFFF;

        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Dekóduje Base32 klíč zpět do binární podoby
     */
    private static function base32Decode($secret) {
        if (empty($secret)) return '';

        $base32chars = self::$base32Chars;
        $base32charsFlipped = array_flip(str_split($base32chars));
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = array(6, 4, 3, 1, 0);

        if (!in_array($paddingCharCount, $allowedValues)) return false;
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] && substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) return false;
        }

        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], str_split($base32chars))) return false;
            for ($j = 0; $j < 8; $j++) {
                if (!isset($secret[$i + $j])) continue;
                $x .= str_pad(base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                if (strlen($eightBits[$z]) != 8) continue;
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        return $binaryString;
    }
}
?>