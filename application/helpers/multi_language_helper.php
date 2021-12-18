<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* CodeIgniter
*
* An open source application development framework for PHP 5.1.6 or newer
*
* @package		CodeIgniter
* @author		ExpressionEngine Dev Team
* @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
* @license		http://codeigniter.com/user_guide/license.html
* @link		http://codeigniter.com
* @since		Version 1.0
* @filesource
*/


// This function helps us to get the translated phrase from the file. If it does not exist this function will save the phrase and by default it will have the same form as given
if ( ! function_exists('get_phrase'))
{
    function get_phrase($phrase = '') {
        // $CI	=&	get_instance();
        // $CI->load->database();
        // $language_code = $CI->db->get_where('settings' , array('key' => 'language'))->row()->value;
        // $key = strtolower(preg_replace('/\s+/', '_', $phrase));
        // $langArray = openJSONFile($language_code);

        // // THIS BLOCK OF CODE IS THE CORE FOR TRANSLATING
        // if ($langArray && !array_key_exists($key, $langArray) ) {
        //     $langArray[$key] = ucfirst(str_replace('_', ' ', $key));
        //     $jsonData = json_encode($langArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        //     file_put_contents(APPPATH.'language/'.$language_code.'.json', stripslashes($jsonData));
        // }
        // // THIS BLOCK OF CODE IS THE CORE FOR TRANSLATING
        // return $langArray[$key];
        $CI =&  get_instance();
        if (!$CI->session->userdata('language')) {
            $CI->session->set_userdata('language', get_settings('language'));
        }
        $language_code = $CI->session->userdata('language');
        $key = strtolower(preg_replace('/\s+/', '_', $phrase));
        $langArray = openJSONFile($language_code);

        // THIS BLOCK OF CODE IS THE CORE FOR TRANSLATING
        if ($langArray && !array_key_exists($key, $langArray) ) {
            $langArray[$key] = ucfirst(str_replace('_', ' ', $key));
            $jsonData = json_encode($langArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents(APPPATH.'language/'.$language_code.'.json', stripslashes($jsonData));
        }
        // THIS BLOCK OF CODE IS THE CORE FOR TRANSLATING
        return $langArray[$key];
    }
}

// This function helps us to get the translated phrase from the file. If it does not exist this function will save the phrase and by default it will have the same form as given
if ( ! function_exists('site_phrase'))
{
    function site_phrase($phrase = '') {
        $CI	=&	get_instance();
        if (!$CI->session->userdata('language')) {
            $CI->session->set_userdata('language', get_settings('language'));
        }
        $language_code = $CI->session->userdata('language');
        $key = strtolower(preg_replace('/\s+/', '_', $phrase));
        $langArray = openJSONFile($language_code);

        // THIS BLOCK OF CODE IS THE CORE FOR TRANSLATING
        if ($langArray && !array_key_exists($key, $langArray) ) {
            $langArray[$key] = ucfirst(str_replace('_', ' ', $key));
            $jsonData = json_encode($langArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents(APPPATH.'language/'.$language_code.'.json', stripslashes($jsonData));
        }
        // THIS BLOCK OF CODE IS THE CORE FOR TRANSLATING
        return $langArray[$key];
    }
}

// This function helps us to decode the language json and return that array to us
if ( ! function_exists('openJSONFile'))
{
    function openJSONFile($code)
    {
        $jsonString = [];
        if (file_exists(APPPATH.'language/'.$code.'.json')) {
            $jsonString = file_get_contents(APPPATH.'language/'.$code.'.json');
            $jsonString = json_decode($jsonString, true);
        }
        return $jsonString;
    }
}

// This function helps us to create a new json file for new language
if ( ! function_exists('saveDefaultJSONFile'))
{
    function saveDefaultJSONFile($language_code){
        $language_code = strtolower($language_code);
        if(!file_exists(APPPATH.'language/'.$language_code.'.json')){
            $fp = fopen(APPPATH.'language/'.$language_code.'.json', 'w');
            $newLangFile = APPPATH.'language/'.$language_code.'.json';
            $enLangFile   = APPPATH.'language/english.json';
            copy($enLangFile, $newLangFile);
            fclose($fp);
        }
    }
}

// This function helps us to update a phrase inside the language file.
if ( ! function_exists('saveJSONFile'))
{
    function saveJSONFile($language_code, $updating_key, $updating_value){
        $jsonString = [];
        if(file_exists(APPPATH.'language/'.$language_code.'.json')){
            $jsonString = file_get_contents(APPPATH.'language/'.$language_code.'.json');
            $jsonString = json_decode($jsonString, true);
            $jsonString[$updating_key] = filter_var(escapeJsonString($updating_value), FILTER_SANITIZE_SPECIAL_CHARS, FILTER_SANITIZE_STRING);
        }else {
            $jsonString[$updating_key] = filter_var(escapeJsonString($updating_value), FILTER_SANITIZE_SPECIAL_CHARS, FILTER_SANITIZE_STRING);
        }
        $jsonData = json_encode($jsonString, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        file_put_contents(APPPATH.'language/'.$language_code.'.json', stripslashes($jsonData));
    }
}


// This function helps us to update a phrase inside the language file.
if ( ! function_exists('escapeJsonString'))
{
    function escapeJsonString($value) {
        $value = str_replace('"', "'", $value);
        $escapers =     array("\\",     "/",   "\"",  "\n",  "\r",  "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t",  "\\f",  "\\b");
        $result = str_replace($escapers, $replacements, $value);
        return $result;
    }
}


function getLanguageCode() {
    $codes = [
        'ab' => 'Abkhazian',
        'aa' => 'Afar',
        'af' => 'Afrikaans',
        'ak' => 'Akan',
        'sq' => 'Albanian',
        'am' => 'Amharic',
        'ar' => 'Arabic',
        'an' => 'Aragonese',
        'hy' => 'Armenian',
        'as' => 'Assamese',
        'av' => 'Avaric',
        'ae' => 'Avestan',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'bm' => 'Bambara',
        'ba' => 'Bashkir',
        'eu' => 'Basque',
        'be' => 'Belarusian',
        'bn' => 'Bengali',
        'bh' => 'Bihari languages',
        'bi' => 'Bislama',
        'bs' => 'Bosnian',
        'br' => 'Breton',
        'bg' => 'Bulgarian',
        'my' => 'Burmese',
        'ca' => 'Catalan, Valencian',
        'km' => 'Central Khmer',
        'ch' => 'Chamorro',
        'ce' => 'Chechen',
        'ny' => 'Chichewa, Chewa, Nyanja',
        'zh' => 'Chinese',
        'cu' => 'Church Slavonic, Old Bulgarian, Old Church Slavonic',
        'cv' => 'Chuvash',
        'kw' => 'Cornish',
        'co' => 'Corsican',
        'cr' => 'Cree',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'dv' => 'Divehi, Dhivehi, Maldivian',
        'nl' => 'Dutch, Flemish',
        'dz' => 'Dzongkha',
        'en' => 'English',
        'eo' => 'Esperanto',
        'et' => 'Estonian',
        'ee' => 'Ewe',
        'fo' => 'Faroese',
        'fj' => 'Fijian',
        'fi' => 'Finnish',
        'fr' => 'French',
        'ff' => 'Fulah',
        'gd' => 'Gaelic, Scottish Gaelic',
        'gl' => 'Galician',
        'lg' => 'Ganda',
        'ka' => 'Georgian',
        'de' => 'German',
        'ki' => 'Gikuyu, Kikuyu',
        'el' => 'Greek (Modern)',
        'kl' => 'Greenlandic, Kalaallisut',
        'gn' => 'Guarani',
        'gu' => 'Gujarati',
        'ht' => 'Haitian, Haitian Creole',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hz' => 'Herero',
        'hi' => 'Hindi',
        'ho' => 'Hiri Motu',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'io' => 'Ido',
        'ig' => 'Igbo',
        'id' => 'Indonesian',
        'ia' => 'Interlingua (International Auxiliary Language Association)',
        'ie' => 'Interlingue',
        'iu' => 'Inuktitut',
        'ik' => 'Inupiaq',
        'ga' => 'Irish',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'jv' => 'Javanese',
        'kn' => 'Kannada',
        'kr' => 'Kanuri',
        'ks' => 'Kashmiri',
        'kk' => 'Kazakh',
        'rw' => 'Kinyarwanda',
        'kv' => 'Komi',
        'kg' => 'Kongo',
        'ko' => 'Korean',
        'kj' => 'Kwanyama, Kuanyama',
        'ku' => 'Kurdish',
        'ky' => 'Kyrgyz',
        'lo' => 'Lao',
        'la' => 'Latin',
        'lv' => 'Latvian',
        'lb' => 'Letzeburgesch, Luxembourgish',
        'li' => 'Limburgish, Limburgan, Limburger',
        'ln' => 'Lingala',
        'lt' => 'Lithuanian',
        'lu' => 'Luba-Katanga',
        'mk' => 'Macedonian',
        'mg' => 'Malagasy',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mt' => 'Maltese',
        'gv' => 'Manx',
        'mi' => 'Maori',
        'mr' => 'Marathi',
        'mh' => 'Marshallese',
        'ro' => 'Moldovan, Moldavian, Romanian',
        'mn' => 'Mongolian',
        'na' => 'Nauru',
        'nv' => 'Navajo, Navaho',
        'nd' => 'Northern Ndebele',
        'ng' => 'Ndonga',
        'ne' => 'Nepali',
        'se' => 'Northern Sami',
        'no' => 'Norwegian',
        'nb' => 'Norwegian Bokmål',
        'nn' => 'Norwegian Nynorsk',
        'ii' => 'Nuosu, Sichuan Yi',
        'oc' => 'Occitan (post 1500)',
        'oj' => 'Ojibwa',
        'or' => 'Oriya',
        'om' => 'Oromo',
        'os' => 'Ossetian, Ossetic',
        'pi' => 'Pali',
        'pa' => 'Panjabi, Punjabi',
        'ps' => 'Pashto, Pushto',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'qu' => 'Quechua',
        'rm' => 'Romansh',
        'rn' => 'Rundi',
        'ru' => 'Russian',
        'sm' => 'Samoan',
        'sg' => 'Sango',
        'sa' => 'Sanskrit',
        'sc' => 'Sardinian',
        'sr' => 'Serbian',
        'sn' => 'Shona',
        'sd' => 'Sindhi',
        'si' => 'Sinhala, Sinhalese',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'so' => 'Somali',
        'st' => 'Sotho, Southern',
        'nr' => 'South Ndebele',
        'es' => 'Spanish, Castilian',
        'su' => 'Sundanese',
        'sw' => 'Swahili',
        'ss' => 'Swati',
        'sv' => 'Swedish',
        'tl' => 'Tagalog',
        'ty' => 'Tahitian',
        'tg' => 'Tajik',
        'ta' => 'Tamil',
        'tt' => 'Tatar',
        'te' => 'Telugu',
        'th' => 'Thai',
        'bo' => 'Tibetan',
        'ti' => 'Tigrinya',
        'to' => 'Tonga (Tonga Islands)',
        'ts' => 'Tsonga',
        'tn' => 'Tswana',
        'tr' => 'Turkish',
        'tk' => 'Turkmen',
        'tw' => 'Twi',
        'ug' => 'Uighur, Uyghur',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        've' => 'Venda',
        'vi' => 'Vietnamese',
        'vo' => 'Volap_k',
        'wa' => 'Walloon',
        'cy' => 'Welsh',
        'fy' => 'Western Frisian',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'yo' => 'Yoruba',
        'za' => 'Zhuang, Chuang',
        'zu' => 'Zulu'
    ];
    return $codes;
}




// ------------------------------------------------------------------------
/* End of file language_helper.php */
/* Location: ./system/helpers/language_helper.php */
