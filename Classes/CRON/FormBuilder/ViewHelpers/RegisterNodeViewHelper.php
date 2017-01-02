<?php
namespace CRON\FormBuilder\ViewHelpers;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\ViewHelpers\Form\AbstractFormViewHelper;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class RegisterNodeViewHelper extends AbstractFormViewHelper
{

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    const FORM_FIELD_NAME_FORMAT = '--cron_formbuilder-plugin[data][%s]';

    /**
     * Use within the fluid form helper to configure the form from element nodes
     *
     * @param NodeInterface $node
     *
     * @return void
     */
    public function render($node)
    {
        $this->registerField($node);
    }

    /**
     * Registers fields for form token generation
     *
     * @param $node the node to register
     */
    private function registerField($node)
    {
        if ($node->getNodeType()->isOfType('CRON.FormBuilder:FieldSet') || $node->getNodeType()->isOfType('CRON.FormBuilder:Plugin')) {
            foreach ($node->getNode('elements')->getChildNodes() as $element) {
                $this->registerField($element);
            }
        } else {
            $this->registerFieldNameForFormTokenGeneration(sprintf(self::FORM_FIELD_NAME_FORMAT,
                $node->getIdentifier()));
        }
    }

}
