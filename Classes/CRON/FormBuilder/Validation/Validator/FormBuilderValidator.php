<?php
namespace CRON\FormBuilder\Validation\Validator;

use Neos\Flow\Annotations as Flow;
use CRON\FormBuilder\Service\SiteService;
use Neos\Flow\Error\Error;
use Neos\Flow\Validation\Validator\CollectionValidator;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

class FormBuilderValidator extends CollectionValidator
{

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
     * @Flow\InjectConfiguration(path="Upload")
     * @var array
     */
    protected $uploadConf;

    /**
     * Loads the nodes and checks if entered values are valid, according to node configuration
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {

        foreach ($value as $index => $collectionElement) {

            $node = $this->nodeDataRepository->findOneByIdentifier($index,
                $this->siteService->getSiteNode()->getWorkspace());

            if ($node->getProperty('required')) {
                $requiredElementValidator = $this->validatorResolver->createValidator('NotEmpty');
                $this->result->forProperty($index)->merge($requiredElementValidator->validate($collectionElement));
            }

            if ($node->getNodeType()->isOfType('CRON.FormBuilder:FileUpload') && is_array($collectionElement)) {
                if (!in_array($collectionElement['type'], $this->uploadConf['allowedMimeTypes'])) {
                    $this->result->forProperty($index)->addError(new Error('The media type "%s" is not allowed for this file', 1483368544, [$collectionElement['type']]));
                }

                if ($collectionElement['size'] > $this->uploadConf['maxFileSize']) {
                    $this->result->forProperty($index)->addError(new Error('The size of this file is too big', 1483368545));
                }
            }
        }
    }
}
