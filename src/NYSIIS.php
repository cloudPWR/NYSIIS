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
        for ($key_pointer = 1; $key_pointer <= mb_strlen($name); $key_pointer++) {
            $key_1n = mb_substr($name, $key_pointer-1, 1);
            $key_n = mb_substr($name, $key_pointer, 1);
            $key_n1 = mb_substr($name, $key_pointer+1, 1);
            $key_n2 = mb_substr($name, $key_pointer+2, 1);
            
            if ($this->translateVowels(
                    $name,
                    $key_n,
                    $key_n1,
                    $key_pointer
                )
            ) {
            } elseif ($this->translateSuperficialLetters(
                    $name,
                    $key_n,
                    $key_pointer
                )
            ) {
            } elseif ($this->translateKay(
                    $name,
                    $key_n,
                    $key_n1,
                    $key_pointer
                )
            ) {
            } elseif ($this->translateHomophones(
                $name,
                $key_n,
                $key_n1,
                $key_n2,
                $key_pointer
                )
            ) {
            } elseif ($this->translateAitch(
                $name,
                $key_1n,
                $key_n,
                $key_n1,
                $key_pointer
                )
            ) {
            } elseif ($this->translateDoubleU(
                $name,
                $key_1n,
                $key_n,
                $key_pointer
                )
            ) {
            }
            $new_key_n = mb_substr($name, $key_pointer, 1);
            if ($key_1n != $new_key_n) {
                $key .= $new_key_n;
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
                $this->getPosition($name, $from)
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
     * @param string $name A reference to the name to translate
     * @param string $key_n The character at the current pointer
     * @param string $key_n1 The character after the current pointer
     * @param integer $key_pointer The position to translate at
     * @return boolean
     */
    protected function translateVowels(
        &$name,
        $key_n,
        $key_n1,
        $key_pointer
    ) {
        if ($key_n.$key_n1 == 'EV') {
            $name = $this->replaceAt($name, 'EV', 'AF', $key_pointer);
            return true;
        }
        
        foreach ($this->getVowels() as $from) {
            if ($key_n == $from) {
                $name = $this->replaceAt($name, $from, 'A', $key_pointer);
                return true;
            }
        }
        return false;
    }

    /**
     * Translates letters that have a similar appearance
     *
     * @param string $name A reference to the name to translate
     * @param string $key_n The character at the current pointer
     * @param integer $key_pointer The position to translate at
     * @return boolean
     */
    protected function translateSuperficialLetters(
        &$name,
        $key_n,
        $key_pointer
    ) {
        $super_translate = array(
            "Q" => "G",
            "Z" => "S",
            "M" => "N",
        );
        foreach ($super_translate as $from => $to) {
            if ($key_n == $from) {
                $name = $this->replaceAt($name, $from, $to, $key_pointer);
                return true;
            }
        }
        return false;
    }

    /**
     * Translates the letter K
     *
     * @param string $name A reference to the name to translate
     * @param string $key_n The character at the current pointer
     * @param string $key_n1 The character after the current pointer
     * @param integer $key_pointer The position to translate at
     * @return boolean
     */
    protected function translateKay(
        &$name,
        $key_n,
        $key_n1,
        $key_pointer
    ) {
        if ($key_n == 'K') {
            if ($key_n1 == 'N') {
                $name = $this->replaceAt($name, 'KN', 'N', $key_pointer);
            } else {
                $name = $this->replaceAt($name, 'K', 'C', $key_pointer);
            }
            return true;
        }
        return false;
    }

    /**
     * Translates similar sounding letters
     *
     * @param string $name A reference to the name to translate
     * @param string $key_n The character at the current pointer
     * @param string $key_n1 The character after the current pointer
     * @param integer $key_pointer The position to translate at
     * @return boolean
     */
    protected function translateHomophones(
        &$name,
        $key_n,
        $key_n1,
        $key_n2,
        $key_pointer
    ) {
        if ($key_n.$key_n1.$key_n2 == 'SCH') {
            $name = $this->replaceAt($name, 'SCH', 'SSS', $key_pointer);
            return true;
        }
        if ($key_n.$key_n1== 'PH') {
            $name = $this->replaceAt($name, 'PH', 'FF', $key_pointer);
            return true;
        }
        return false;
    }

    /**
     * Translates the letter H
     *
     * @param string $name A reference to the name to translate
     * @param string $key_1n The character before the current pointer
     * @param string $key_n The character at the current pointer
     * @param string $key_n1 The character after the current pointer
     * @param integer $key_pointer The position to translate at
     * @return boolean
     */
    protected function translateAitch(
        &$name,
        $key_1n,
        $key_n,
        $key_n1,
        $key_pointer
    ) {
        if ($key_n !== 'H') {
            return false;
        }
        if (!in_array($key_1n, $this->getVowels()) ||
            !in_array($key_n1, $this->getVowels())
        ) {
            $name = $this->replaceAt($name, 'H', $key_1n, $key_pointer);
            return true;
        }
        return false;
    }

    /**
     * Translates the letter W
     *
     * @param string $name A reference to the name to translate
     * @param string $key_1n The character before the current pointer
     * @param string $key_n The character at the current pointer
     * @param integer $key_pointer The position to translate at
     * @return boolean
     */
    protected function translateDoubleU(
        &$name,
        $key_1n,
        $key_n,
        $key_pointer
    ) {
        if ($key_n != 'W') {
            return false;
        }
        if (in_array($key_1n, $this->getVowels())) {
            $name = $this->replaceAt($name, 'W', $key_1n, $key_pointer);
            return true;
        }
        return false;
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
                $this->getPosition($name, $from)
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
        if (mb_strpos($string, $from, $position) !== $position) {
            return $string;
        }
        
        $string_before = mb_substr($string, 0, $position);
        $string_after = mb_substr($string, $position + mb_strlen($from));
        
        return $string_before.$to.$string_after;
    }
    
    /**
     * Returns an array of all vowels
     * @return array
     */
    protected function getVowels()
    {
        return array(
            "A",
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
    }
    
    /**
     * Get the position to start looking for the from string
     * @param string $name The name to translate
     * @param string $from The from string we are translating
     * @return integer
     */
    protected function getPosition($name, $from)
    {
        $position = mb_strlen($name) - mb_strlen($from);
        if ($position < 0 || $position > mb_strlen($name)) {
            $position = mb_strlen($name);
        }
        return $position;
    }
}
