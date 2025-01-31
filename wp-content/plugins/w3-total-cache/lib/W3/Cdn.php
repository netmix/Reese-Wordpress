<?php

/**
 * W3 CDN Class
 */

if (!defined('W3TC_CDN_FTP')) {
    define('W3TC_CDN_FTP', 'ftp');
}

if (!defined('W3TC_CDN_S3')) {
    define('W3TC_CDN_S3', 's3');
}

if (!defined('W3TC_CDN_CF_S3')) {
    define('W3TC_CDN_CF_S3', 'cf');
}

if (!defined('W3TC_CDN_CF_CUSTOM')) {
    define('W3TC_CDN_CF_CUSTOM', 'cf2');
}

if (!defined('W3TC_CDN_MIRROR')) {
    define('W3TC_CDN_MIRROR', 'mirror');
}

if (!defined('W3TC_CDN_NETDNA')) {
    define('W3TC_CDN_NETDNA', 'netdna');
}

if (!defined('W3TC_CDN_COTENDO')) {
    define('W3TC_CDN_COTENDO', 'cotendo');
}

if (!defined('W3TC_CDN_RSCF')) {
    define('W3TC_CDN_RSCF', 'rscf');
}

if (!defined('W3TC_CDN_AZURE')) {
    define('W3TC_CDN_AZURE', 'azure');
}

/**
 * Class W3_Cdn
 */
class W3_Cdn {
    /**
     * Returns W3_Cdn_Base instance
     *
     * @param string $engine
     * @param array $config
     * @return W3_Cdn_Base
     */
    function &instance($engine, $config = array()) {
        static $instances = array();

        $instance_key = sprintf('%s_%s', $engine, md5(implode('', $config)));

        if (!isset($instances[$instance_key])) {
            switch (true) {
                case ($engine == W3TC_CDN_FTP):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Ftp.php';
                    $instances[$instance_key] = & new W3_Cdn_Ftp($config);
                    break;

                case (W3TC_PHP5 && $engine == W3TC_CDN_S3):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/S3.php';
                    $instances[$instance_key] = & new W3_Cdn_S3($config);
                    break;

                case (W3TC_PHP5 && $engine == W3TC_CDN_CF_S3):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/S3/Cf/S3.php';
                    $instances[$instance_key] = & new W3_Cdn_S3_Cf_S3($config);
                    break;

                case (W3TC_PHP5 && $engine == W3TC_CDN_CF_CUSTOM):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/S3/Cf/Custom.php';
                    $instances[$instance_key] = & new W3_Cdn_S3_Cf_Custom($config);
                    break;

                case (W3TC_PHP5 && $engine == W3TC_CDN_RSCF):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Rscf.php';
                    $instances[$instance_key] = & new W3_Cdn_Rscf($config);
                    break;

                case (W3TC_PHP5 && $engine == W3TC_CDN_AZURE):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Azure.php';
                    $instances[$instance_key] = & new W3_Cdn_Azure($config);
                    break;

                case ($engine == W3TC_CDN_MIRROR):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Mirror.php';
                    $instances[$instance_key] = & new W3_Cdn_Mirror($config);
                    break;

                case ($engine == W3TC_CDN_NETDNA):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Mirror/Netdna.php';
                    $instances[$instance_key] = & new W3_Cdn_Mirror_Netdna($config);
                    break;

                case ($engine == W3TC_CDN_COTENDO):
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Mirror/Cotendo.php';
                    $instances[$instance_key] = & new W3_Cdn_Mirror_Cotendo($config);
                    break;

                default :
                    trigger_error('Incorrect CDN engine', E_USER_WARNING);
                    require_once W3TC_LIB_W3_DIR . '/Cdn/Base.php';
                    $instances[$instance_key] = & new W3_Cdn_Base();
                    break;
            }
        }

        return $instances[$instance_key];
    }
}
