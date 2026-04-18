<?php
/**
 * CATS
 * Encryption Library
 *
 * Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 *
 *
 * The contents of this file are subject to the CATS Public License
 * Version 1.1a (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.catsone.com/.
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "CATS Standard Edition".
 *
 * The Initial Developer of the Original Code is Cognizo Technologies, Inc.
 * Portions created by the Initial Developer are Copyright (C) 2005 - 2007
 * (or from the year in which this file was created to the year 2007) by
 * Cognizo Technologies, Inc. All Rights Reserved.
 *
 * @package    CATS
 * @subpackage Library
 * @copyright Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 * @version    $Id: Encryption.php 3587 2007-11-13 03:55:57Z will $
 */

/**
 *	Encryption Library
 *	@package    CATS
 *	@subpackage Library
 */
class Encryption
{
    protected $_cipher = false;
    protected $_key = '';
    protected $_iv = '';
    protected $_blockSize = 16;
    protected $_useZeroPadding = true;
    protected $_isInitialized = false;

    public function __construct($key, $algorithm, $mode = 'ecb', $iv = false)
    {
        /* In non-ECB mode, an initialization vector is required. */
        if ($mode != 'ecb' && $iv === false) {
            return false;
        }

        /* Resolve the requested mcrypt algorithm/mode to an OpenSSL cipher. */
        $cipher = $this->_resolveCipher($algorithm, $mode);
        if ($cipher === false) {
            return false;
        }

        $ivLength = openssl_cipher_iv_length($cipher);
        if ($ivLength === false) {
            return false;
        }

        if (strtolower((string) $mode) === 'ecb' || $ivLength === 0) {
            $iv = '';
        } elseif ($iv === false) {
            $iv = random_bytes($ivLength);
        } else {
            $iv = substr((string) $iv, 0, $ivLength);
            if (strlen($iv) < $ivLength) {
                $iv = str_pad($iv, $ivLength, "\0");
            }
        }

        $keyLength = $this->_resolveKeyLength($cipher);
        if ($keyLength > 0) {
            $key = substr((string) $key, 0, $keyLength);
        } else {
            $key = (string) $key;
        }

        $this->_cipher = $cipher;
        $this->_key = $key;
        $this->_iv = $iv;
        $this->_blockSize = $this->_resolveBlockSize($cipher);
        $this->_useZeroPadding = $this->_shouldUseZeroPadding($mode);
        $this->_isInitialized = true;
    }

    public function encrypt($plainText)
    {
        if (!$this->_isInitialized) {
            return false;
        }

        /* mcrypt used zero-padding for block ciphers. */
        if ($this->_useZeroPadding && $this->_blockSize > 1) {
            $paddingLength = strlen((string) $plainText) % $this->_blockSize;
            if ($paddingLength !== 0) {
                $plainText .= str_repeat("\0", $this->_blockSize - $paddingLength);
            }
        }

        /* Base64 encode data to protect special characters. */
        $encrypted = openssl_encrypt(
            (string) $plainText,
            $this->_cipher,
            $this->_key,
            OPENSSL_RAW_DATA | ($this->_useZeroPadding ? OPENSSL_ZERO_PADDING : 0),
            $this->_iv
        );
        if ($encrypted === false) {
            return false;
        }

        return base64_encode($encrypted);
    }

    public function decrypt($cypherText)
    {
        if (!$this->_isInitialized) {
            return false;
        }

        /* Base64-decode the encrypted data and decrypt it. */
        $plainText = openssl_decrypt(
            base64_decode((string) $cypherText),
            $this->_cipher,
            $this->_key,
            OPENSSL_RAW_DATA | ($this->_useZeroPadding ? OPENSSL_ZERO_PADDING : 0),
            $this->_iv
        );
        if ($plainText === false) {
            return false;
        }

        /* Remove any \0 padding. */
        return rtrim($plainText, "\0");
    }

    public function __destruct()
    {
        $this->_isInitialized = false;
    }

    protected function _resolveCipher($algorithm, $mode)
    {
        $algorithm = strtolower(str_replace('_', '-', (string) $algorithm));
        $mode = strtolower((string) $mode);

        if ($mode == 'nofb') {
            $mode = 'ofb';
        } elseif ($mode == 'ncfb') {
            $mode = 'cfb';
        }

        /* Only map algorithms that are safely equivalent in OpenSSL. */
        if ($algorithm == 'rijndael-128') {
            $algorithm = 'aes-128';
        } elseif ($algorithm == 'rijndael-192') {
            /* mcrypt rijndael-192 is not equivalent to AES-192. */
            return false;
        } elseif ($algorithm == 'rijndael-256') {
            /* mcrypt rijndael-256 is not equivalent to AES-256. */
            return false;
        } elseif ($algorithm == 'tripledes' || $algorithm == '3des') {
            $algorithm = 'des-ede3';
        } elseif ($algorithm == 'blowfish') {
            $algorithm = 'bf';
        }

        $ciphers = openssl_get_cipher_methods();
        $candidate = $algorithm . '-' . $mode;
        if (in_array($candidate, $ciphers)) {
            return $candidate;
        }

        if (in_array($algorithm, $ciphers)) {
            return $algorithm;
        }

        return false;
    }

    protected function _resolveKeyLength($cipher)
    {
        if (strpos($cipher, 'aes-128-') === 0) {
            return 16;
        }
        if (strpos($cipher, 'aes-192-') === 0) {
            return 24;
        }
        if (strpos($cipher, 'aes-256-') === 0) {
            return 32;
        }
        if (strpos($cipher, 'des-ede3-') === 0) {
            return 24;
        }
        if (strpos($cipher, 'des-') === 0) {
            return 8;
        }

        return 0;
    }

    protected function _resolveBlockSize($cipher)
    {
        if (strpos($cipher, 'des-') === 0 || strpos($cipher, 'bf-') === 0) {
            return 8;
        }

        return 16;
    }

    protected function _shouldUseZeroPadding($mode)
    {
        $mode = strtolower((string) $mode);

        return ($mode == 'ecb' || $mode == 'cbc');
    }
}
