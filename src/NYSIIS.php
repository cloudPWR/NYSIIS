<?php
namespace cloudPWR;

/**
 * A PHP implementation of the New York State Identification and Intelligence
 * System Phonetic Code.
 *
 * @copyright cloudPWR 2016
 * @author Nathan Horter <nate@cloudpwr.com>
 * @license http://opensource.org/licenses/GPL-3.0 GPL-3.0
 */
class NYSIIS
{
    /**
     * The maximum length of the encoded output to return.
     * @var integer
     */
    protected $max_output_length;

    /**
     * Creates a new NYSIIS object
     *
     * @param integer $max_output_length The maximum length of the encoded
     *  output. Zero or any negative value indicates unlimited length. The
     *  originial NYSIIS algorithm truncated to 6 characters.
     */
    public function __construct($max_output_length = 0)
    {
        if (is_float($max_output_length) || !is_numeric($max_output_length)) {
            throw new \Exception(
                'cloudPWR\\NYSIIS was created with an invalid max_output_length.'
            );
        }
        $this->max_output_length = (integer)$max_output_length;
    }

    /**
     * Encodes the passed name using the NYSIIS algorithm. Returns the encoded
     * string.
     *
     * @param string $name The name to encode.
     * @return string
     */
    public function encode($name)
    {
        if (!is_string($name)) {
            throw new \Exception(
                'cloudPWR\\NYSIIS::encode was passed an invalid name.'
            );
        }
        $name = $this->normalizeName($name);
        $name = $this->translateFirstCharacters($name);
        $name = $this->translateLastCharacters($name);
        $key = mb_substr($name, 0, 1);
        $name = mb_substr($name, 1);

        for ($pos = 0; $pos <= mb_strlen($name); $pos++) {
            $name = $this->translateVowels($name, $pos);
            $name = $this->translateSuperficialLetters($name, $pos);
            $name = $this->translateKay($name, $pos);
            $name = $this->translateHomophones($name, $pos);
            $h_name = $this->translateAitch($name, $pos);
            $h_delete = false;
            if ($h_name != $name) {
                $h_delete = true;
                $name = $h_name;
            }
            $name = $this->translateDoubleU($name, $pos);
            if (!$h_delete && mb_substr($key, -1) != mb_substr($name, $pos, 1)) {
                $key .= mb_substr($name, $pos, 1);
            }
            if ($h_delete) {
                $pos -= 1;
            }
        }
        
        $key = $this->stripEss($key);
        $key = $this->replaceWye($key);
        $key = $this->stripA($key);

        if (mb_strlen($key) > $this->max_output_length &&
            $this->max_output_length > 0
        ) {
            $key = mb_substr($key, 0, $this->max_output_length);
        }
        return $key;
    }

    /**
     * Normalizes the incoming name so it can be properly encoded. The string
     * is converted to uppercase and all non-word characters are removed.
     *
     * @param string $name The name to normalize
     * @return string.
     */
    protected function normalizeName($name)
    {
        $name = mb_strtoupper($name);
        $name = preg_replace('/[\W]/iu', '', $name);
        return $name;
    }

    /**
     * Translates the first characters of the name as according to the NYSIIS
     * algorithm.
     *
     * @param string $name The name to translate
     * @return string
     */
    protected function translateFirstCharacters($name)
    {
        $first_translate = array(
            "MAC" => "MCC",
            "KN" => "N",
            "K" => "C",
            "PH" => "FF",
            "PF" => "FF",
            "SCH" => "SSS",
        );
        $translated_name = $name;
        foreach ($first_translate as $from => $to) {
            $translated_name = $this->replaceAt($name, $from, $to, 0);
            if ($name !== $translated_name) {
                break;
            }
        }
        return $translated_name;
    }

    /**
     * Translates the last characters of the name as according to the NYSIIS
     * algorithm.
     *
     * @param string $name The name to translate
     * @return string
     */
    protected function translateLastCharacters($name)
    {
        $last_translate = array(
            "EE" => "Y",
            "IE" => "Y",
            "DT" => "D",
            "RT" => "D",
            "RD" => "D",
            "NT" => "D",
            "ND" => "D",
        );
        $translated_name = $name;
        foreach ($last_translate as $from => $to) {
            $translated_name = $this->replaceAt(
                $name,
                $from,
                $to,
                mb_strlen($name) - mb_strlen($from)
            );
            if ($name !== $translated_name) {
                break;
            }
        }
        return $translated_name;
    }

    /**
     * Translates any vowels as the passed position
     *
     * @param string $name The name to translate
     * @param integer $pos The position to translate at
     * @return string
     */
    protected function translateVowels($name, $pos)
    {
        $translated_name = $this->replaceAt($name, 'EV', 'AF', $pos);
        
        $vowel_translate = array(
            "E",
            "I",
            "O",
            "U",
            "À",
            "È",
            "Ì",
            "Ò",
            "Ù",
            "Ȁ",
            "Ȅ",
            "Ȉ",
            "Ȍ",
            "Ȕ",
            "Á",
            "É",
            "Í",
            "Ó",
            "Ú",
            "Ý",
            "Ő",
            "Ű",
            "Â",
            "Ê",
            "Î",
            "Ô",
            "Û",
            "Ä",
            "Ë",
            "Ï",
            "Ö",
            "Ü",
            "Ã",
            "Ẽ",
            "Ĩ",
            "Õ",
            "Ũ",
            "Ą",
            "Ę",
            "Į",
            "Ǫ",
            "Ų",
            "Ā",
            "Ē",
            "Ī",
            "Ō",
            "Ū",
            "Ă",
            "Ĕ",
            "Ĭ",
            "Ŏ",
            "Ŭ",
            "Ǎ",
            "Ě",
            "Ǐ",
            "Ǒ",
            "Ǔ",
            "Ȧ",
            "Ė",
            "Ȯ",
            "Ạ",
            "Ẹ",
            "Ị",
            "Ọ",
            "Ụ",
            "Ḛ",
            "Ḭ",
            "Ṵ",
            "Ṳ",
        );
        $translated_name = $name;
        foreach ($vowel_translate as $from) {
            $translated_name = $this->replaceAt($name, $from, 'A', $pos);
            if ($name !== $translated_name) {
                break;
            }
        }
        return $translated_name;
    }

    /**
     * Translates letters that have a similar appearance
     *
     * @param string $name The name to translate
     * @param integer $pos The position to translate at
     * @return string
     */
    protected function translateSuperficialLetters($name, $pos)
    {
        $super_translate = array(
            "Q" => "G",
            "Z" => "S",
            "M" => "N",
        );
        $translated_name = $name;
        foreach ($super_translate as $from => $to) {
            $translated_name = $this->replaceAt($name, $from, $to, $pos);
            if ($name !== $translated_name) {
                break;
            }
        }
        return $translated_name;
    }

    /**
     * Translates the letter K
     *
     * @param string $name The name to translate
     * @param integer $pos The position to translate at
     * @return string
     */
    protected function translateKay($name, $pos)
    {
        $super_translate = array(
            "KN" => "N",
            "K" => "C",
        );
        $translated_name = $name;
        foreach ($super_translate as $from => $to) {
            $translated_name = $this->replaceAt($name, $from, $to, $pos);
            if ($name !== $translated_name) {
                break;
            }
        }
        return $translated_name;
    }

    /**
     * Translates similar sounding letters
     *
     * @param string $name The name to translate
     * @param integer $pos The position to translate at
     * @return string
     */
    protected function translateHomophones($name, $pos)
    {
        $phone_translate = array(
            "SCH" => "SSS",
            "PH" => "FF",
        );
        $translated_name = $name;
        foreach ($phone_translate as $from => $to) {
            $translated_name = $this->replaceAt($name, $from, $to, $pos);
            if ($name !== $translated_name) {
                break;
            }
        }
        return $translated_name;
    }

    /**
     * Translates the letter H
     *
     * @param string $name The name to translate
     * @param integer $pos The position to translate at
     * @return string
     */
    protected function translateAitch($name, $pos)
    {
        if (mb_strpos($name, 'H') !== $pos) {
            return $name;
        }
        $translated_name = $name;
        $vowels = array('A','E','I','O','U');
        if ((
                $pos-1 < 0 ||
                !in_array(mb_substr($name, $pos-1, 1), $vowels)
            ) ||
            (
                $pos+1 < mb_strlen($name) &&
                !in_array(mb_substr($name, $pos+1, 1), $vowels)
            )
        ) {
            if ($pos-1 > 0) {
                $replacement = mb_substr($name, $pos-1, 1);
            } else {
                $replacement = '';
            }
            $translated_name = $this->replaceAt($name, 'H', $replacement, $pos);
        }
        return $translated_name;
    }

    /**
     * Translates the letter W
     *
     * @param string $name The name to translate
     * @param integer $pos The position to translate at
     * @return string
     */
    protected function translateDoubleU($name, $pos)
    {
        if (mb_strpos($name, 'W') !== $pos) {
            return $name;
        }
        $translated_name = $name;
        $vowels = array('A','E','I','O','U');
        if (in_array(mb_substr($name, $pos-1, 1), $vowels)) {
            $translated_name = $this->replaceAt($name, 'W', 'A', $pos);
        }
        return $translated_name;
    }

    /**
     * Removes the letter S from the end of the name
     *
     * @param string $name The name to translate
     * @return string
     */
    protected function stripEss($name)
    {
        $translated_name = $name;
        if (mb_substr($name, -1) == 'S') {
            $translated_name = mb_substr($name, 0, -1);
        }
        return $translated_name;
    }

    /**
     * Translates the letter Y at the end of the name
     *
     * @param string $name The name to translate
     * @return string
     */
    protected function replaceWye($name)
    {
        $last_translate = array(
            "AY" => "Y",
        );
        $translated_name = $name;
        foreach ($last_translate as $from => $to) {
            $translated_name = $this->replaceAt(
                $name,
                $from,
                $to,
                mb_strlen($name) - mb_strlen($from)
            );
            if ($name !== $translated_name) {
                break;
            }
        }
        return $translated_name;
    }

    /**
     * Removes the letter A from the end of the name
     *
     * @param string $name The name to translate
     * @return string
     */
    protected function stripA($name)
    {
        $translated_name = $name;
        if (mb_substr($name, -1) == 'A') {
            $translated_name = mb_substr($name, 0, -1);
        }
        return $translated_name;
    }
    
    /**
     * Replaces the from string with the to string at the passed position.
     * Returns the altered string.
     *
     * @param string $string The string to replace values in.
     * @param string $from The value to search for in the string
     * @param string $to The value with which to replace the found from string
     * @param integer $position The charachter position of to look for the from
     *  string
     * @return string
     */
    protected function replaceAt($string, $from, $to, $position)
    {
        if (mb_strpos($string, $from) !== $position) {
            return $string;
        }
        
        $string_before = mb_substr($string, 0, $position);
        $string_after = mb_substr($string, $position + mb_strlen($from));
        
        return $string_before.$to.$string_after;
    }
}
