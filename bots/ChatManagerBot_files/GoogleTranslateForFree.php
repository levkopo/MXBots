<?php

namespace Dejurin;

use Exception;

/**
 * GoogleTranslateForFree.php.
 *
 * Class for free use Google Translator. With attempts connecting on failure and array support.
 *
 * @category Translation
 *
 * @author Yuri Darwin
 * @author Yuri Darwin <gkhelloworld@gmail.com>
 * @copyright 2019 Yuri Darwin
 * @license https://opensource.org/licenses/MIT
 *
 * @version 1.0.0
 */

/**
 * Main class GoogleTranslateForFree.
 */
class GoogleTranslateForFree {
    /**
     * @param string $source
     * @param string $target
     * @param string|array $text
     * @param int $attempts
     *
     * @return string|array With the translation of the text in the target language
     * @throws Exception
     */
    public static function translate(string $source, string $target, string|array $text, int $attempts = 5) {
        // Request translation
        if (is_array($text)) {
            // Array
            $translation = self::requestTranslationArray($source, $target, $text);
        } else {
            // Single
            $translation = self::requestTranslation($source, $target, $text, $attempts);
        }

        return $translation;
    }

    /**
     * @param string $source
     * @param string $target
     * @param array $text
     * @return array
     * @throws Exception
     */
    protected static function requestTranslationArray(string $source, string $target, array $text) {
        $arr = [];
        foreach ($text as $value) {
            // timeout 0.5 sec
            usleep(500000);
            $arr[] = self::requestTranslation($source, $target, $value, $attempts = 5);
        }

        return $arr;
    }

    /**
     * @param string $source
     * @param string $target
     * @param string $text
     * @param int $attempts
     *
     * @return string
     * @throws Exception
     */
    protected static function requestTranslation(string $source, string $target, string $text, int $attempts) {
        // Google translate URL
        $url = 'https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=uk-RU&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e';

        $fields = [
            'sl' => urlencode($source),
            'tl' => urlencode($target),
            'q'  => urlencode($text),
        ];

        if (strlen($fields['q']) >= 5000) {
            throw new Exception('Maximum number of characters exceeded: 5000');
        }
        // URL-ify the data for the POST
        $fields_string = self::fieldsString($fields);

        $content = self::curlRequest($url, $fields, $fields_string, 0, $attempts);

        if (null === $content) {
            //echo $text,' Error',PHP_EOL;
            return '';
        } else {
            // Parse translation
            return self::getSentencesFromJSON($content);
        }
    }

    /**
     * Dump of the JSON's response in an array.
     *
     * @param string $json
     * @return string
     */
    protected static function getSentencesFromJSON(string $json)
    {
        $arr = json_decode($json, true);
        $sentences = '';

        if (isset($arr['sentences'])) {
            foreach ($arr['sentences'] as $s) {
                $sentences .= isset($s['trans']) ? $s['trans'] : '';
            }
        }

        return $sentences;
    }

    /**
     * Curl Request attempts connecting on failure.
     *
     * @param string $url
     * @param array $fields
     * @param string $fields_string
     * @param int $i
     * @param int $attempts
     *
     * @return string
     */
    protected static function curlRequest(string $url, array $fields, string $fields_string,
                                          int $i, int $attempts) {
        $i++;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (false === $result || 200 !== $httpcode) {

            if ($i >= $attempts) {
                return null;
            } else {
                usleep(1500000);
                return self::curlRequest($url, $fields, $fields_string, $i, $attempts);
            }
        } else {
            return $result; //self::getBodyCurlResponse();
        }
    }

    /**
     * Make string with post data fields.
     *
     * @param array $fields
     *
     * @return string
     */
    protected static function fieldsString(array $fields) {
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key.'='.$value.'&';
        }

        return rtrim($fields_string, '&');
    }
}