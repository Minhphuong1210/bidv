<?php

namespace App\Modules\BIDV\Helpers;

class BIDVCryptoHelper
{
    /**
     * Base64URL Encode
     */
    public static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * SHA256 checksum then Base64 encode
     */
    public static function getBase64SHA256($input)
    {
        return base64_encode(hash('sha256', $input, true));
    }

    /**
     * Create HMAC SHA256 and Base64 Encode
     */
    public static function getHMACSHA256Base64($input, $key)
    {
        return base64_encode(hash_hmac('sha256', $input, $key, true));
    }

    /**
     * Create Detached JWS (JSON Web Signature)
     * @param string $payload (The raw payload or JWE string)
     * @param string $privateKeyContent (The PEM formatted private key)
     */
    public static function createDetachedJWS($payload, $privateKeyContent)
    {



        $header = json_encode(['alg' => 'RS256']);
        $encodedHeader = self::base64UrlEncode($header);
        $encodedPayload = self::base64UrlEncode($payload);

        $signingInput = $encodedHeader . '.' . $encodedPayload;

        $signature = '';
        $privateKeyId = openssl_pkey_get_private($privateKeyContent);
        

        if (!$privateKeyId) {
            throw new \Exception("Invalid private key");
        }

        openssl_sign($signingInput, $signature, $privateKeyId, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKeyId);

        $encodedSignature = self::base64UrlEncode($signature);

        // Detached JWS format: header..signature
        return $encodedHeader . '..' . $encodedSignature;
    }

    /**
     * Create JWE (JSON Web Encryption) with A256KW and A128GCM
     * @param string $payload The JSON string to encrypt
     * @param string $symmetricKeyHex The 256-bit symmetric key in HEX (from BIDV config)
     */
    public static function createJWE($payload, $symmetricKeyHex)
    {
        // 1. Generate 32-byte CEK for A128CBC-HS256
        $cek = openssl_random_pseudo_bytes(32);
        $macKey = substr($cek, 0, 16);
        $encKey = substr($cek, 16, 16);

        // 2. Generate 16-byte IV for A128CBC-HS256
        $iv = openssl_random_pseudo_bytes(16);

        // 3. Prepare JWE Protected Header
        $protectedHeader = json_encode([
            'alg' => 'A256KW',
            'enc' => 'A128CBC-HS256'
        ]);
        $encodedProtectedHeader = self::base64UrlEncode($protectedHeader);

        // 4. Encrypt Payload (Ciphertext) using A128CBC-HS256
        $ciphertext = openssl_encrypt(
            $payload,
            'aes-128-cbc',
            $encKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new \Exception("A128CBC-HS256 Encryption failed");
        }

        // 5. Calculate Authentication Tag
        $al = pack('J', strlen($encodedProtectedHeader) * 8); // 64 bit unsigned big endian
        $macData = $encodedProtectedHeader . $iv . $ciphertext . $al;
        $mac = hash_hmac('sha256', $macData, $macKey, true);
        $tag = substr($mac, 0, 16);

        // 6. Encrypt CEK with KEK using AES Key Wrap (A256KW)
        $kek = hex2bin($symmetricKeyHex);
        if (strlen($kek) !== 32) {
            throw new \Exception("Symmetric key must be 256 bits (32 bytes)");
        }
        $encryptedKey = self::aesKeyWrap($cek, $kek);

        // 7. Assemble the JWE JSON (General JWE JSON Serialization)
        $jwe = [
            'recipients' => [
                [
                    'header' => (object)[],
                    'encrypted_key' => self::base64UrlEncode($encryptedKey)
                ]
            ],
            'protected' => $encodedProtectedHeader,
            'ciphertext' => self::base64UrlEncode($ciphertext),
            'iv' => self::base64UrlEncode($iv),
            'tag' => self::base64UrlEncode($tag)
        ];

        return json_encode($jwe, JSON_UNESCAPED_SLASHES);
    }

    /**
     * AES Key Wrap (RFC 3394) implementation
     * Wraps CEK using KEK
     */
    private static function aesKeyWrap($cek, $kek)
    {
        $n = strlen($cek) / 8;
        $R = [];
        $A = pack("H*", "A6A6A6A6A6A6A6A6");

        for ($i = 0; $i < $n; $i++) {
            $R[$i + 1] = substr($cek, $i * 8, 8);
        }

        for ($j = 0; $j <= 5; $j++) {
            for ($i = 1; $i <= $n; $i++) {
                $t = ($n * $j) + $i;
                $input = $A . $R[$i];
                $B = openssl_encrypt($input, 'aes-256-ecb', $kek, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
                
                $A = substr($B, 0, 8);
                // A = A ^ t
                $t_pack = pack("J", $t); // 64-bit big endian
                $A = $A ^ $t_pack;
                
                $R[$i] = substr($B, 8, 8);
            }
        }

        $result = $A;
        for ($i = 1; $i <= $n; $i++) {
            $result .= $R[$i];
        }

        return $result;
    }
}
