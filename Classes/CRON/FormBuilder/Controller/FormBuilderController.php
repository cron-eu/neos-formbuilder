<?php
namespace CRON\FormBuilder\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CRON.FormBuilder".      *
 *                                                                        *
 *                                                                        */


use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use CRON\FormBuilder\Utils\EmailMessage;
use TYPO3\Flow\Mvc\Controller\ActionController;
use CRON\FormBuilder\Service\SiteService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;


class FormBuilderController extends ActionController {

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
	 * @Flow\InjectConfiguration(path="Controller")
	 * @var array
	 */
	protected $conf;

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('attributes',$this->request->getInternalArgument('__attributes'));
		$this->view->assign('elements',$this->request->getInternalArgument('__elements'));
		$this->view->assign('elementsArray',$this->request->getInternalArgument('__elementsArray'));
		$this->view->assign('responseElements',$this->request->getInternalArgument('__responseElements'));
		$this->view->assign('documentNode',$this->request->getInternalArgument('__documentNode'));
		$this->view->assign('node',$this->request->getInternalArgument('__node'));
		$this->view->assign('submitButtonLabel',$this->request->getInternalArgument('__submitButtonLabel'));
		$this->view->assign('tsPath',$this->request->getInternalArgument('__tsPath'));
		$this->view->assign('tsPackageKey',$this->request->getInternalArgument('__tsPackageKey'));
		$this->view->assign('tsPath',$this->request->getInternalArgument('__tsPath'));
		$this->view->assign('tsPackageKey',$this->request->getInternalArgument('__tsPackageKey'));
	}



	/**
	 * Checks the form id
	 * @return void
	 */
	public function initializeSubmitAction() {

		$this->checkFormId();
		$this->checkHmac();
	}

	/**
	 * @param array $data
	 * @Flow\Validate(argumentName="data", type="\CRON\FormBuilder\Validation\Validator\FormBuilderValidator")
	 * @return void
	 */
	public function submitAction($data) {

		$this->handleFormData($data);
	}


	/**
	 * @return void
	 */
	public function submitPendingAction() {
		$this->view->assign('node',$this->request->getInternalArgument('__node'));
		$this->view->assign('responseElements',$this->request->getInternalArgument('__responseElements'));
	}



	/**
	 * The actual handling of the submitted form data. Can be used as AOP hook
	 *
	 * @param array $data
	 * @return void
	 */
	public function handleFormData($data) {
		$fields = [];

		/** @var NodeInterface $formNode */
		$formNode = $this->request->getInternalArgument('__node');

		/** @var NodeInterface $element */
		foreach($formNode->getNode('elements')->getChildNodes() as $element) {
			$fields[$element->getIdentifier()] = $this->createMailData($element, $data);
		}

		$this->sendMail($fields);

		if ($this->conf['useForward']) {
			$this->forward('submitPending');
		} else {
			$this->redirect('submitPending');
		}
	}


	/**
	 * Creates the data to render in the mail
	 *
	 * @param NodeInterface $node
	 * @param array $data
	 *
	 * @return array
	 */
	protected function createMailData($node, $data) {

		if ($node->getNodeType()->isOfType('CRON.FormBuilder:FieldSet')) {

			$fields = [];

			foreach($node->getNode('elements')->getChildNodes() as $subElement) {
				$fields[] = $this->createMailData($subElement, $data);
			}

			return array('node' => $node, 'values' => $fields);

		} else if (array_key_exists($node->getIdentifier(), $data)) {

			$value = $data[$node->getIdentifier()];
			if (is_array($value)) $value = implode(', ', $value);
			return array('node' => $node, 'value' => $value);
		} else {
			return [];
		}

	}



	/**
	 * Checks the HMAC for the form submitted data
	 */
	protected function checkHmac() {

		$trustedProperties = $this->request->getInternalArgument('__trustedProperties');

		if ($trustedProperties) {

			if ($this->request->hasArgument('data')
			    && $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken(
					array_merge(
						array_map(
							function($key) { return "data[$key]"; },
							array_keys($this->request->getArgument('data'))
						),
						['__formId']
					)) == $trustedProperties) {
				return;
			} else {
				// hmac does not match: unauthorized request!
				$this->throwStatus(403);
			}
		} else {
			// hmac not present: unauthorized request!
			$this->throwStatus(403);
		}
	}


	/**
	 * For multiple forms on one page we check which form is submitted and forward to index if necessary
	 * @return void
	 */
	protected function checkFormId() {
		/** @var NodeInterface $node */
		$node = $this->request->getInternalArgument('__node');

		if($this->request->getInternalArgument('__formId') != $node->getIdentifier()) {
			$this->forward('index');
		}
	}


	/**
	 * Sends your details to recipient
	 * @param array $fields
	 * @return void
	 */
	protected function sendMail($fields) {

		$receiver = explode(',', $this->request->getInternalArgument('__receiver'));

		$emailMessage = new EmailMessage('Form');

		$emailMessage->fluidView->assign('subject', $this->request->getInternalArgument('__subject'));
		$emailMessage->fluidView->assign('fields', $fields);
		$emailMessage->fluidView->setControllerContext($this->controllerContext);
		$emailMessage->send($receiver);
	}


}
