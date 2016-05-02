<?php
/**
 * Created by Zakhovalko Pavel
 * Date: 29.04.2016
 */

require_once(dirname(__FILE__) . '/common.php');

class normalize_text
{
    public $separator = ' ';
    public $min_word_len = 2;
    public $db_min_word_len = 4;
    public $need_append_word = true;
    public $replace_character = 'z';
    public $default_lang = 'rus';
    public $replace_pattern = '/&[a-zA-Z]{1,10};/u';
    public $split_pattern = '#\s|[,.:;!?"\'()«»“„]#u';
    public $replace_words_pattern = '/^&.*;$/u';

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

        $dict_dir = isset($params['dict_dir']) ? $params['dict_dir'] : $this->get_dict_dir($lang);

        $dict_bundle = new phpMorphy_FilesBundle($dict_dir, $lang);
        $this->phpmorphy = new phpMorphy($dict_bundle, $opts);
    }

    private function get_dict_dir($lang) {
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

    private function set_options($params)
    {
        if (isset($params['replace_pattern'])) {

            $this->replace_pattern = $params['replace_pattern'];
        }
        if (isset($params['split_pattern'])) {

            $this->split_pattern = $params['split_pattern'];
        }

        if (isset($params['min_word_len'])) {

            $this->min_word_len = $params['min_word_len'];
        }

        if (isset($params['db_min_word_len'])) {

            $this->db_min_word_len = $params['db_min_word_len'];
        }

        if (isset($params['need_append_word'])) {

            $this->need_append_word = $params['need_append_word'];
        }

        if (isset($params['replace_character'])) {

            $this->replace_character = $params['replace_character'];
        }

        if (isset($params['replace_words_pattern'])) {

            $this->replace_words_pattern = $params['replace_words_pattern'];
        }

        if (isset($params['separator'])) {

            $this->separator = $params['separator'];
        }
    }

    function normalize($text, $params = array())
    {
        $this->set_options($params);
        $this->set_phpmorfy($params);
        $text = preg_replace($this->replace_pattern, '', $text);
        $words = preg_split($this->split_pattern, $text, -1, PREG_SPLIT_NO_EMPTY);
        for($i=0; $i<count($words); $i++)
        {
            $words[$i] = $this->baseForm($words[$i]);
        }
        $words = array_diff( $words, array( '' ) );
        $result_text = implode($this->separator, $words);
        if($this->need_append_word)
        {
            $result_text = $this->append_short_words($result_text);
        }
        return $result_text;
    }

    function baseForm($word)
    {
        $word = _strtoupper($word);
        $results_array = $this->phpmorphy->getBaseForm($word);
        if($results_array === false)
        {
            if((_strlen($word) < $this->min_word_len) || preg_match($this->replace_words_pattern, $word, $matches))
            {
                $result = '';
            }
            else {
                $result = $word;
            }
        }
        else
        {
            if(_strlen($results_array[0]) < $this->min_word_len)
            {
                $result = '';
            }
            else {
                $result = implode($this->separator, $results_array);
            }
        }
        return $result;
    }
    //need for indexation words with length lower than ft_min_word_len (MySQL)
    function append_short_words($text)
    {
        $replace_pattern = array();
        $result_pattern = array();
        if($this->db_min_word_len>$this->min_word_len)
        {
            for($i=$this->min_word_len; $i<$this->db_min_word_len; $i++)
            {
                $replace_pattern[] = '/(^| )([^\s]{'.$i.'})($| )/u';
                $result_pattern[] = '$1$2'.str_pad('', $this->db_min_word_len-$i, $this->replace_character).'$3';
            }
        }
        return preg_replace($replace_pattern, $result_pattern, $text);
    }
}