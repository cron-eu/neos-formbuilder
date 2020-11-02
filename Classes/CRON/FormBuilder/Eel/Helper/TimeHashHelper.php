<?php

namespace CRON\FormBuilder\Eel\Helper;

use DateTime;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Exception;

class TimeHashHelper implements ProtectedContextAwareInterface {

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Log\SystemLoggerInterface
     *
     */

    protected $systemLogger;

    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $conf;

    /**
     * @return string a encrypted timestamp to protect the form for spam bots
     */
    public function getTimeHash()
    {
        return $this->createTimeHash();
    }

    /**
     * Creates an encrypted timestamp
     * @return string
     * @throws Exception
     */
    protected function createTimeHash()
    {

        $key = $this->conf['Protection']['key'];
        $cipher = $this->conf['Protection']['cipher'];
        $iv = $this->conf['Protection']['iv'];

        $time = new DateTime();
        if(in_array($cipher, openssl_get_cipher_methods())) {
            return openssl_encrypt($time->getTimestamp(), $cipher, $key, 0, $iv);
        }
        throw new Exception('Encryption method not available');
    }

    /**
     * All methods are considered safe, i.e. can be executed from within Eel
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
