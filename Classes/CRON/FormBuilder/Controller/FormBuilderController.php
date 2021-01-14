<?php

namespace CRON\FormBuilder\Controller;

/*                                                                        *
 * This script belongs to the Neos Flow package "CRON.FormBuilder".       *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Exception;
use Neos\Flow\Annotations as Flow;
use DateTime;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use CRON\FormBuilder\Utils\EmailMessage;
use Neos\Flow\Mvc\Controller\ActionController;
use CRON\FormBuilder\Service\SiteService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Security\Cryptography\HashService;

class FormBuilderController extends ActionController
{

    /**
     * @Flow\Inject
     * @var SiteService
     */
    protected $siteService;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $conf;

    /**
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    public function indexAction()
    {
        /** @var NodeInterface $node */
        $node = $this->request->getInternalArgument('__node');
        $this->view->assign('attributes', $this->request->getInternalArgument('__attributes'));
        $this->view->assign('elements', $this->request->getInternalArgument('__elements'));
        $this->view->assign('responseElements', $this->request->getInternalArgument('__responseElements'));
        $this->view->assign('documentNode', $this->request->getInternalArgument('__documentNode'));
        $this->view->assign('node', $node);
        $this->view->assign('submitButtonLabel', $node->getProperty('submitButtonLabel'));
        $this->view->assign('tsPackageKey', $this->request->getInternalArgument('__tsPackageKey'));
        $this->view->assign(
            'enctype',
            $this->request->getInternalArgument('__hasUploadElement') ? 'multipart/form-data' : null
        );
    }

    /**
     * Checks the form id
     * @return void
     */
    public function initializeSubmitAction()
    {
        $this->checkFormId();
    }

    /**
     * @param array $data
     * @Flow\Validate(argumentName="data", type="\CRON\FormBuilder\Validation\Validator\FormBuilderValidator")
     * @return void
     * @throws Exception
     * @throws StopActionException
     */
    public function submitAction($data)
    {
        if (!$this->checkTimestamp($data['phone']) && empty($data['subject'])) {
            $this->handleFormData($this->request->getInternalArgument('__node'), $data);
            if ($this->conf['Controller']['useForward']) {
                $this->forward('submitPending');
            } else {
                $this->redirect('submitPending');
            }
        } else {
            $this->forward('submitPending');
        }
    }

    /**
     * @return void
     */
    public function submitPendingAction()
    {
        $this->view->assign('node', $this->request->getInternalArgument('__node'));
        $this->view->assign('responseElements', $this->request->getInternalArgument('__responseElements'));
    }

    /**
     * Validates if the Honeypot field has been changed and if the transmission time is correct
     * @param string $hashedTimeStamp
     * @return bool
     * @throws Exception
     */
    protected function checkTimestamp(string $hashedTimeStamp): bool
    {
        $minimalSubmitDelayInSeconds = $this->conf['Protection']['minimalSubmitDelayInSeconds'];
        $maximalSubmitDelayInSeconds = $this->conf['Protection']['maximalSubmitDelayInSeconds'];

        $this->hashService->validateAndStripHmac($hashedTimeStamp);
        $validatedTimeStamp = (int)$this->hashService->validateAndStripHmac($hashedTimeStamp);

        if ($validatedTimeStamp) {
            $currentTime = new DateTime();
            $currentTime = $currentTime->getTimestamp();

            if (
                ($currentTime - $validatedTimeStamp) <= $minimalSubmitDelayInSeconds ||
                ($currentTime - $validatedTimeStamp) >= $maximalSubmitDelayInSeconds
            ) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception('Hashed Time could not be validated, input field was edited');
        }
    }

    /**
     * The actual handling of the submitted form data. Can be used as AOP hook
     *
     * @param NodeInterface $formNode
     * @param array $data
     * @return void
     */
    public function handleFormData($formNode, $data)
    {
        $fields = [];
        $files = [];

        /** @var NodeInterface $element */
        foreach ($formNode->getNode('elements')->getChildNodes('!CRON.FormBuilder:FileUpload') as $element) {
            $fields[$element->getIdentifier()] = $this->createMailData($element, $data);
        }

        foreach (
            $formNode->getNode('elements')
                     ->getChildNodes('CRON.FormBuilder:FileUpload,CRON.FormBuilder:FieldSet') as $element
        ) {
            if ($element->getNodeType()->isOfType('CRON.FormBuilder:FieldSet')) {
                foreach ($element->getNode('elements')->getChildNodes('CRON.FormBuilder:FileUpload') as $subElement) {
                    $files[$element->getIdentifier()] = $this->createMailAttachments($subElement, $data);
                }
            } else {
                $files[$element->getIdentifier()] = $this->createMailAttachments($element, $data);
            }
        }

        $this->sendMail($fields, $files);
    }

    /**
     * Creates the data to render in the mail
     *
     * @param NodeInterface $node
     * @param array $data
     *
     * @return array
     */
    protected function createMailData($node, $data)
    {

        if ($node->getNodeType()->isOfType('CRON.FormBuilder:FieldSet')) {
            $fields = [];

            foreach ($node->getNode('elements')->getChildNodes('!CRON.FormBuilder:FileUpload') as $subElement) {
                $fields[] = $this->createMailData($subElement, $data);
            }

            return array('node' => $node, 'values' => $fields);
        } else {
            if (array_key_exists($node->getIdentifier(), $data)) {
                $value = $data[$node->getIdentifier()];
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                return array('node' => $node, 'value' => $value);
            } else {
                return [];
            }
        }
    }

    /**
     * Creates the data to attach to the mail
     *
     * @param NodeInterface $node
     * @param array $data
     *
     * @return array
     */
    protected function createMailAttachments($node, $data)
    {
        return array(
            'node' => $node,
            'file' => $data[$node->getIdentifier()]
        );
    }

    /**
     * For multiple forms on one page we check which form is submitted and forward to index if necessary
     * @return void
     */
    protected function checkFormId()
    {
        /** @var NodeInterface $node */
        $node = $this->request->getInternalArgument('__node');

        if ($this->request->getInternalArgument('__formId') != $node->getIdentifier()) {
            $this->forward('index');
        }
    }

    /**
     * Sends your details to recipient
     * @param array $fields
     * @param array $files
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    protected function sendMail($fields, $files)
    {

        /** @var NodeInterface $node */
        $node = $this->request->getInternalArgument('__node');
        $receiver = explode(',', $node->getProperty('receiver'));

        $emailMessage = new EmailMessage('Form');

        foreach ($files as $id => $data) {
            // "file" maybe empty, if not uploaded
            if (is_array($data['file'])) {
                $emailMessage->addAttachment($data['node'], $data['file']);
            }
        }

        $emailMessage->fluidView->assign('subject', $this->request->getInternalArgument('__subject'));
        $emailMessage->fluidView->assign('fields', $fields);
        $emailMessage->fluidView->setControllerContext($this->controllerContext);
        $emailMessage->send($receiver);
    }
}
