<?php
namespace CRON\FormBuilder\Validation\Validator;

use TYPO3\Flow\Annotations as Flow;
use CRON\FormBuilder\Service\SiteService;
use TYPO3\Flow\Validation\Validator\CollectionValidator;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;


class FormBuilderValidator extends CollectionValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array();

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var SiteService
	 */
	protected $siteService;

	/**
	 * Loads the nodes and checks if entered values are valid, according to node configuration
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	protected function isValid($value) {

		foreach ($value as $index => $collectionElement) {

			$node = $this->nodeDataRepository->findOneByIdentifier($index, $this->siteService->getSiteNode()->getWorkspace());

			if($node->getProperty('required')) {
				$requiredElementValidator = $this->validatorResolver->createValidator('NotEmpty');
				$this->result->forProperty($index)->merge($requiredElementValidator->validate($collectionElement));
			}
		}
	}
}
