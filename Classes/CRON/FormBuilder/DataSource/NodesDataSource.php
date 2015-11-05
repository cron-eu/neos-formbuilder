<?php
namespace CRON\FormBuilder\DataSource;

use TYPO3\Neos\Service\DataSource\AbstractDataSource;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class NodesDataSource extends AbstractDataSource {

    /**
     * @var string
     */
    static protected $identifier = 'cron-formbuilder-formnodes';

    /**
     * Get data
     *
     * @param NodeInterface $node The node that is currently edited (optional)
     * @param array $arguments Additional arguments (key / value)
     * @return array JSON serializable data
     */
    public function getData(NodeInterface $node = NULL, array $arguments) {

        $return = [];
        $tester = $node->getChildNodes();
        $test = $tester[0]->getChildNodes();

        foreach ($test as $nodes){
            $test1 = $nodes->getLabel();
            $return[] = array('label' => $test1);
        }

        return $return;
    }

}