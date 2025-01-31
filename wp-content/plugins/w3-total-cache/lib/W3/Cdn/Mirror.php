<?php

/**
 * W3 CDN Mirror Class
 */
require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';

/**
 * Class W3_Cdn_Mirror
 */
class W3_Cdn_Mirror extends W3_Cdn_Base {
    /**
     * PHP5 Constructor
     *
     * @param array $config
     */
    function __construct($config = array()) {
        $config = array_merge(array(
            'domain' => array(),
        ), $config);

        parent::__construct($config);
    }

    /**
     * PHP4 Constructor
     *
     * @param array $config
     */
    function W3_Cdn_Mirror($config = array()) {
        $this->__construct($config);
    }

    /**
     * Uploads files stub
     *
     * @param array $files
     * @param array $results
     * @param boolean $force_rewrite
     * @return boolean
     */
    function upload($files, &$results, $force_rewrite = false) {
        $results = $this->_get_results($files, W3TC_CDN_RESULT_OK, 'OK');

        return true;
    }

    /**
     * Deletes files stub
     *
     * @param array $files
     * @param array $results
     * @return boolean
     */
    function delete($files, &$results) {
        $results = $this->_get_results($files, W3TC_CDN_RESULT_OK, 'OK');

        return true;
    }

    /**
     * Returns array of CDN domains
     *
     * @return array
     */
    function get_domains() {
        if (!empty($this->_config['domain'])) {
            return (array) $this->_config['domain'];
        }

        return array();
    }
}
