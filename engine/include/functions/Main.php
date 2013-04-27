<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Description: Advanced Community Engine
 * @Version: 0.9a
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 */

/**
 * Common functions for Alto CMS
 */
class AltoFunc_Main {
    static protected $sRandChars = '!#$%()*+-0123456789:<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_abcdefghijklmnopqrstuvwxyz|~';
    static protected $aMemSizeUnits = array('B', 'K', 'M', 'G', 'T', 'P');

    static function StrUnderscore($sStr) {
        return strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $sStr));
    }

    static public function StrCamelize($sStr) {
        $aParts = explode('_', $sStr);
        $sCamelized = '';
        foreach ($aParts as $sPart) {
            $sCamelized .= ucfirst($sPart);
        }
        return $sCamelized;
    }

    static public function Str2Array($sStr, $sSeparator = ',', $bSkipEmpty = false) {
        return F::Array_Str2Array($sStr, $sSeparator, $bSkipEmpty);
    }

    static public function Str2ArrayInt($sStr, $sSeparator = ',', $bUnique = true) {
        return F::Array_Str2ArrayInt($sStr, $sSeparator, $bUnique);
    }

    static public function Val2Array($xVal, $sSeparator = ',', $bSkipEmpty = false) {
        return F::Array_Val2Array($xVal, $sSeparator, $bSkipEmpty);
    }

    /**
     * Возвращает строку со случайным набором символов
     *
     * @param   int     $nLen   - длина строка
     * @param   bool    $bHex   - только шестнадцатиричные символы [0-9a-f]
     *
     * @return  string
     */
    static public function RandomStr($nLen = 32, $bHex = true) {
        $sResult = '';
        if ($bHex) {
            while (strlen($sResult) < $nLen) {
                $sResult .= md5(uniqid(md5(rand()), true));
            }
            if (strlen($sResult) > $nLen) {
                $sResult = substr($sResult, 0, $nLen);
            }
        } else {
            $nMax = strlen(self::$sRandChars) - 1;
            while (strlen($sResult) < $nLen) {
                $sResult .= self::$sRandChars[rand(0, $nMax)];
            }
        }
        return $sResult;
    }

    /**
     * @param   float $nValue
     * @param   int   $nDecimal
     *
     * @return  string
     */
    static public function MemSizeFormat($nValue, $nDecimal = 0) {
        $aUnits = self::$aMemSizeUnits;
        $nIndex = 0;
        $nResult = intval($nValue);
        while ($nResult >= 1024) {
            $nIndex += 1;
            $nResult = $nResult / 1024;
        }
        if (isset($aUnits[$nIndex])) {
            return number_format($nResult, $nDecimal, '.', '\'') . '&nbsp;' . $aUnits[$nIndex];
        }
        return $nValue;
    }

    /**
     * Converts string as memory size into number
     *      '256'   => 256 - just number
     *      '2K'    => 2 * 1024 = 2048 - in KB
     *      '4 KB'  => 4 * 1024 = 4096 - in KB
     *      '1.5M'  => 1.5 * 1024 * 1024 = 1572864 - in MB
     *      '187X'  => 187 - invalid unit
     *
     * @param $sNum
     *
     * @return int|number
     */
    static public function MemSize2Int($sNum) {
        $nValue = floatval($sNum);
        if (!is_numeric($sChar = strtoupper(substr($sNum, -1)))) {
            if ($sChar == 'B') {
                $sChar = substr($sNum, -1);
            }
            if (($nIdx = array_search(strtoupper($sChar), self::$aMemSizeUnits)) !== false) {
                $nValue *= pow(1024, $nIdx);
            }
        }
        return intval($nValue);
    }

    /**
     * @param   mixed $xData
     *
     * @return  string
     */
    static public function JsonEncode($xData) {
        if (function_exists('json_encode')) {
            return json_encode($xData);
        }
        if (is_null($xData)) {
            return 'null';
        }
        if ($xData === false) {
            return 'false';
        }
        if ($xData === true) {
            return 'true';
        }
        if (is_scalar($xData)) {
            if (is_float($xData)) {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($xData)));
            }

            if (is_string($xData)) {
                static $jsonReplaces
                = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
                        array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $xData) . '"';
            } else {
                return $xData;
            }
        }
        $isList = true;
        for ($i = 0, reset($xData); $i < count($xData); $i++, next($xData)) {
            if (key($xData) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($xData as $v) {
                $result[] = self::jsonEncode($v);
            }
            return '[' . join(',', $result) . ']';
        } else {
            foreach ($xData as $k => $v) {
                $result[] = self::jsonEncode($k) . ':' . self::jsonEncode($v);
            }
            return '{' . join(',', $result) . '}';
        }

    }

    /**
     * Returns all IP of current user
     *
     * @param   array|string|null   $aTrasted
     * @param   array|string|null   $aNonTrasted
     *
     * @return  array
     */
    static public function GetAllUserIp($aTrasted = null, $aNonTrasted = null) {
        if (!$aTrasted) {
            if (class_exists('Config', false)) {
                $aTrasted = (array)Config::Get('sys.ip.trasted');
            }
            if (!$aTrasted)
                $aTrasted = array(
                    'REMOTE_ADDR',
                    'HTTP_X_REAL_IP',
                    'HTTP_CLIENT_IP',
                    'HTTP_X_FORWARDED_FOR',
                    'HTTP_VIA',
                );
        } else {
            $aTrasted = (array)$aTrasted;
        }

        if (!$aNonTrasted) {
            $aNonTrasted = F::_getConfig('sys.ip.non_trasted', array());
        } else {
            $aNonTrasted = (array)$aNonTrasted;
        }

        $aIp = array();
        foreach ($aTrasted as $sParam) {
            if (isset($_SERVER[$sParam]) && (!$aNonTrasted || !in_array($sParam, $aNonTrasted))) {
                // sometimes IPs separated by space
                $sIp = str_replace(' ', ',', trim($_SERVER[$sParam]));
                if (strpos($sIp, ',')) {
                    // several IPs
                    $aData = explode(',', $sIp);
                    $aIp[$sParam] = '';
                    foreach ($aData as $sData) {
                        if ($sData && filter_var($sData, FILTER_VALIDATE_IP)) {
                            if ($aIp[$sParam]) {
                                $aIp[$sParam] .= ',';
                            }
                            $aIp[$sParam] .= $sData;
                        }
                    }
                    if (!$aIp[$sParam]) {
                        unset($aIp[$sParam]);
                    }
                } else {
                    // single IP
                    if ($sIp && filter_var($sIp, FILTER_VALIDATE_IP)) {
                        $aIp[$sParam] = $sIp;
                    }
                }
            }
        }
        if (!$aIp) {
            $sIp = F::_getConfig('sys.ip.default');
            if (!$sIp || !filter_var($sIp, FILTER_VALIDATE_IP)) {
                $sIp = '127.0.0.1';
            }
            $aIp['FAKE_ADDR'] = $sIp;
        }
        return $aIp;
    }

    /**
     * Returns user's IP
     *
     * @param   array|string|null   $aTrasted
     * @param   array|string|null   $aNonTrasted
     *
     * @return  string
     */
    static public function GetUserIp($aTrasted = null, $aNonTrasted = null) {
        $aIpParams = self::GetAllUserIp($aTrasted, $aNonTrasted);
        $aExcludeIp = (array)F::_getConfig('sys.ip.exclude', array('127.0.0.1', 'fe80::1', '::1'));
        if (F::_getConfig('sys.ip.exclude_server', true) && isset($_SERVER['SERVER_ADDR'])) {
            $aExcludeIp[] = $_SERVER['SERVER_ADDR'];
        }

        $bSeekBackward = F::_getConfig('sys.ip.backward', true);
        // collect all ip
        $aIp = array();
        foreach ($aIpParams as $sIp) {
            if (strpos($sIp, ',')) {
                $aSeveralIps = explode(',', $sIp);
                if ($bSeekBackward) {
                    $aSeveralIps = array_reverse($aSeveralIps);
                }
                $aIp = array_merge($aIp, $aSeveralIps);
            } else {
                $aIp[] = $sIp;
            }
        }
        foreach ($aIp as $sIp) {
            if (!in_array($sIp, $aExcludeIp)) {
                return $sIp;
            }
        }
        $sIp = array_shift($aIp);
        return $sIp;
    }

    static public function CheckVal($sValue, $sParam, $iMin = 1, $iMax = 100) {
        if (!is_scalar($sValue)) {
            return false;
        }
        switch ($sParam) {
            case 'id':
                if (preg_match('/^\d{' . $iMin . ',' . $iMax . '}$/', $sValue)) {
                    return true;
                }
                break;
            case 'float':
                if (preg_match('/^[\-]?\d+[\.]?\d*$/', $sValue)) {
                    return true;
                }
                break;
            case 'mail':
                /*
                if (preg_match('/^[\da-z\_\-\.\+]+@[\da-z_\-\.]+\.[a-z]{2,5}$/i', $sValue)) {
                    return true;
                }
                */
                return filter_var($sValue, FILTER_VALIDATE_EMAIL) !== false;
                break;
            case 'login':
                if (preg_match('/^[\da-z\_\-]{' . $iMin . ',' . $iMax . '}$/i', $sValue)) {
                    return true;
                }
                break;
            case 'md5':
                if (preg_match('/^[\da-z]{32}$/i', $sValue)) {
                    return true;
                }
                break;
            case 'password':
                if (mb_strlen($sValue, 'UTF-8') >= $iMin) {
                    return true;
                }
                break;
            case 'text':
                if (mb_strlen($sValue, 'UTF-8') >= $iMin && mb_strlen($sValue, 'UTF-8') <= $iMax) {
                    return true;
                }
                break;
            default:
                return false;
        }
        return false;
    }

    /**
     * Вовзвращает "соленый" хеш
     *
     * @param   mixed  $xData  - хешируемая переменная
     * @param   string $sSalt  - "соль"
     *
     * @return  string
     */
    static public function DoSalt($xData, $sSalt) {
        if (!is_string($xData)) {
            $sData = serialize($xData);
        }
        else {
            $sData = (string)$xData;
        }
        return '0x:' . F::DoHashe($sData . '::' . $sSalt);
    }

    /**
     * Вовзвращает "чистый" хеш
     *
     * @param   mixed $xData  - хешируемая переменная
     *
     * @return  string
     */
    static public function DoHashe($xData) {
        if (!is_string($xData)) {
            $sData = serialize($xData);
        }
        else {
            $sData = (string)$xData;
        }
        return (md5(sha1($sData)));
    }

    /**
     * Возвращает текст, обрезанный по заданное число символов
     *
     * @param   string  $sText
     * @param   int     $nLen
     * @param   string  $sPostfix
     *
     * @return  string
     */
    static public function TruncateText($sText, $nLen, $sPostfix = '') {
        if (mb_strlen($sText, 'UTF-8') > $nLen) {
            $sText = mb_substr($sText, 0, $nLen - mb_strlen($sPostfix)) . $sPostfix;
        }
        return $sText;
    }

    /**
     * Возвращает текст, обрезанный по заданное число слов
     *
     * @param   string $sText
     * @param   int    $iCountWords
     *
     * @return  string
     */
    static public function CatText($sText, $iCountWords) {
        $aWords = preg_split('#[\s\r\n]+#um', $sText);
        if ($iCountWords < count($aWords)) {
            $aWords = array_slice($aWords, 0, $iCountWords);
        }
        return join(' ', $aWords);
    }

    /**
     * Аналог serialize() с контролем CRC32
     *
     * @param $xData
     *
     * @return string
     */
    static public function Serialize($xData) {
        $sData = serialize($xData);
        $sCrc32 = dechex(crc32($sData));
        return $sCrc32 . '|' . $sData;

    }

    /**
     * Аналог unserialize() с контролем CRC32
     *
     * @param $sData
     *
     * @return mixed|null
     */
    static public function Unserialize($sData) {
        if (is_string($sData) && strpos($sData, '|')) {
            list($sCrc32, $sData) = explode('|', $sData);
            if ($sCrc32 && $sData && $sCrc32 == dechex(crc32($sData))) {
                $xData = @unserialize($sData);
                return $xData;
            }
        }
        return null;
    }

    static public function IpRange($sIp) {
        $aIp = explode('.', $sIp) + array(0, 0, 0, 0);
        $aIp = array_map('intval', $aIp);

        if ($aIp[0] < 1 || $aIp[0] > 254) {
            // error - first part cannot be empty
            return array('0.0.0.0', '255.255.255.255');
        } else {
            $aIp1 = array();
            $aIp2 = array();
            foreach ($aIp as $nPart) {
                if ($nPart < 0 || $nPart >= 255) {
                    $aIp1[] = 0;
                } else {
                    $aIp1[] = $nPart;
                }
            }
            foreach ($aIp as $nPart) {
                if ($nPart <= 0 || $nPart > 255) {
                    $aIp2[] = 255;
                } else {
                    $aIp2[] = $nPart;
                }
            }
            return array(implode('.', $aIp1), implode('.', $aIp2));
        }
    }

    /**
     * Преобразует интервал в число секунд
     *
     * @param   string  $sInterval  - значение интервала по спецификации ISO 8601 или в человекочитаемом виде
     *
     * @return  int|null
     */
    static public function ToSeconds($sInterval) {
        if (is_numeric($sInterval)) {
            return intval($sInterval);
        }
        if (!is_string($sInterval)) {
            return null;
        }
        if (!class_exists('DateTimeInterval', false)) {
            if (!F::File_IncludeLib('DateTime/DateTimeInterval.php') || !class_exists('DateTimeInterval', false)) {
                return null;
            }
        }
        $oInterval = new DateTimeInterval($sInterval);
        return $oInterval->Seconds();
    }

    static public function DateTimeAdd($sDate, $sInterval) {
        $date = new DateTime($sDate);
        $date->add(new DateInterval('PT' . self::ToSeconds($sInterval) . 'S'));
        return $date->format('Y-m-d H:i:s');
    }

    static public function DateDiffSeconds($sDate1, $sDate2) {
        $oDatetime1 = date_create($sDate1);
        $oDatetime2 = date_create($sDate2);
        $nDiff = $oDatetime2->getTimestamp() - $oDatetime1->getTimestamp();
        return intval($nDiff);
    }

    static public function Now() {
        return date('Y-m-d H:i:s');
    }

}

// EOF