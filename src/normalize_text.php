<?php
/**
 * Created by Zakhovalko Pavel
 * Date: 29.04.2016
 */

require_once(dirname(__FILE__) . '/common.php');

class normalize_text
{
    public $separator = ' ';
    public $min_str_len = 2;
    public $default_lang = 'rus';
    public $replace_pattern = '/&[a-zA-Z]{1,10};/';
    public $split_pattern = '#\s|[,.:;!?"\'()«»“„]#isu';

    public $opts = array(
        'storage' => PHPMORPHY_STORAGE_FILE,
            // Extend graminfo for getAllFormsWithGramInfo method call
        'with_gramtab' => false,
            // Enable prediction by suffix
        'predict_by_suffix' => true,
            // Enable prediction by prefix
        'predict_by_db' => true
    );

    private function set_phpmorfy($params) {
        $opts = isset($params['opts']) ? $params['opts'] : $this->opts;
        $lang = isset($params['lang']) ? $params['langs'] : $this->default_lang;

        if(isset($params['min_str_len']))
        {
            $this->min_str_len =  $params['min_str_len'];
        }

        $dict_dir = $this->get_dict_dir($lang);

        $dict_bundle = new phpMorphy_FilesBundle($dict_dir, $lang);
        $this->phpmorphy = new phpMorphy($dict_bundle, $opts);
    }

    function get_dict_dir($lang) {
        switch($lang)
        {
            case 'rus':
                $encoding = 'utf8';
                break;
            case 'eng':
                $encoding = 'cp1250';
                break;
            case 'ger':
                $encoding = 'utf8';
                break;
            case 'ukr':
                $encoding = 'utf8';
                break;
        }
        if(!empty($encoding)) {
            return dirname(__FILE__) . '/../dicts/' . $encoding . '/' . $lang;
        }
        else
        {
            return false;
        }
    }

    function normalize($text, $params = array())
    {
        $this->set_phpmorfy($params);
        $text = preg_replace($this->replace_pattern, '', $text);
        $words = preg_split($this->split_pattern, $text, -1, PREG_SPLIT_NO_EMPTY);
        for($i=0; $i<count($words); $i++)
        {
            $words[$i] = $this->baseForm($words[$i]);
        }
        $words = array_diff( $words, array( '' ) );
        return implode($this->separator, $words);
    }

    function baseForm($word)
    {
        $word = _strtoupper($word);
        $results_array = $this->phpmorphy->getBaseForm($word);
        if($results_array === false)
        {
            if((_strlen($word) < $this->min_str_len) || preg_match('/^&.*;$/', $word, $matches))
            {
                $result = '';
            }
            else {
                $result = $word;
            }
        }
        else
        {
            if(_strlen($results_array[0]) < $this->min_str_len)
            {
                $result = '';
            }
            else {
                $result = implode($this->separator, $results_array);
            }
        }
        return $result;
    }
}