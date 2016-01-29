<?php
namespace CRON\FormBuilder\ViewHelpers;


use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\ViewHelpers\Form\AbstractFormViewHelper;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class FormBuilderViewHelper extends AbstractFormViewHelper {

	/**
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;



	/**
	 * Use within the fluid form helper to configure the form from element nodes
	 *
	 * @param array $elements
	 *
	 * @return void
	 */
	public function render($elements) {
		/** @var NodeInterface $element */
		foreach ($elements as $element) {
			if(!$element->getNodeType()->isOfType('CRON.FormBuilder:SubmitButton'))
				$this->registerFieldNameForFormTokenGeneration(sprintf('--data[%s]', $element->getIdentifier()));
		}
	}
}
