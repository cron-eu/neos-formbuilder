<?php

namespace CRON\FormBuilder\Controller;

/*                                                                        *
 * This script belongs to the Neos Flow package "CRON.FormBuilder".       *
 *                                                                        *
 *                                                                        */

use CRON\FormBuilder\Service\HoneyPotService;
use Neos\Flow\Exception;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use CRON\FormBuilder\Utils\EmailMessage;
use Neos\Flow\Mvc\Controller\ActionController;
use CRON\FormBuilder\Service\SiteService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Mvc\Exception\StopActionException;

class FormBuilderController extends ActionController
{

    /**
     * @Flow\Inject
     * @var SiteService
     */
    protected $siteService;

    /**
     * @Flow\Inject
     * @var HoneyPotService
     */
    protected $honeyPotService;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

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
     * @return void
     * @throws Exception
     * @throws StopActionException
     * @Flow\Validate(argumentName="data", type="\CRON\FormBuilder\Validation\Validator\FormBuilderValidator")
     */
    public function submitAction(array $data)
    {
        if (isset($data['subject'], $data['phone'])) {
            if (!$this->honeyPotService->validateSecret($data['phone']) && empty($data['subject'])) {
                $this->handleFormData($this->request->getInternalArgument('__node'), $data);
                if ($this->conf['Controller']['useForward']) {
                    $this->forward('submitPending');
                } else {
                    $this->redirect('submitPending');
                }
            } else {
                $this->forward('submitPending');
            }
        } else {
            $this->handleFormData($this->request->getInternalArgument('__node'), $data);
            if ($this->conf['Controller']['useForward']) {
                $this->forward('submitPending');
            } else {
                $this->redirect('submitPending');
            }
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
                    $value = implode(', ', array_filter($value, function($v){ return $v !== ''; }));
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
     * @throws \Exception
     */
    protected function sendMail($fields, $files)
    {

        /** @var NodeInterface $node */
        $node = $this->request->getInternalArgument('__node');
        $receiver = explode(',', $node->getProperty('receiver'));

        if ($node->getProperty('sendCustomerMail')) {

            $customerMail = "";
            $customerName = "";
            $customerFields = [];

            foreach ($fields as $field) {
                if (!empty($field)) {
                    if ($field['node']->getProperty('type') == "email" && $field['node']->getProperty('isCustomerMail')) {
                        $customerMail = $field['value'];
                    }

                    if ($field['node']->getProperty('type') == "name") {
                        $customerName = $field['value'];
                    }
                }
            }

            if ($customerMail == "") {
                throw new \Exception('There must be an email field and must be marked as customer mail');
            }

            $customerFields = array_filter($fields , function( $v ) {
                if (!empty($field)) {
                    return $v['node']->getProperty('filter') != true;
                }
            });

            $emailMessageCustomer = new EmailMessage('CustomerMail');
            $this->addAttachments($files, $emailMessageCustomer);
            $emailMessageCustomer->fluidView->assign('subject', $node->getProperty('customerSubject'));
            $emailMessageCustomer->fluidView->assign('name', $customerName);
            $emailMessageCustomer->fluidView->assign('fields', $customerFields);
            $emailMessageCustomer->fluidView->setControllerContext($this->controllerContext);
            $emailMessageCustomer->send($customerMail);
        }

        $emailMessage = new EmailMessage('Form');

        $this->addAttachments($files, $emailMessage);

        $emailMessage->fluidView->assign('subject',$node->getProperty('subject'));
        $emailMessage->fluidView->assign('fields', $fields);
        $emailMessage->fluidView->setControllerContext($this->controllerContext);
        $emailMessage->send($receiver);
    }

    /**
     * Add Attachments to the Mail
     * @param EmailMessage $message
     * @param array $files
     * @throws \Neos\ContentRepository\Exception\NodeException
     */

    protected function addAttachments ($files, $message): EmailMessage
    {
        foreach ($files as $id => $data) {
            // "file" maybe empty, if not uploaded
            if (is_array($data['file'])) {
                $message->addAttachment($data['node'], $data['file']);
            }
        }
        return $message;
    }
}
