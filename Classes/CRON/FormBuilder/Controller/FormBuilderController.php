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
	}

	/**
	 * @param array $data
	 * @Flow\Validate(argumentName="data", type="\CRON\FormBuilder\Validation\Validator\FormBuilderValidator")
	 * @return void
	 */
	public function submitAction($data) {

		$siteNode = $this->siteService->getSiteNode();

		$fields = [];

		foreach($data as $identifier => $value) {
			$node = $this->nodeDataRepository->findOneByIdentifier($identifier, $siteNode->getWorkspace());

			//we can only handle registered nodes, must be a form manipulation
			if($node === NULL) $this->throwStatus(403);

			if (is_array($value)) $value = implode(', ', $value);

			$fields[] = array('label' => $node->getProperty('label'), 'value' => $value);
		}

		$this->sendMail($fields);

		if ($this->conf['useForward']) {
			$this->forward('submitPending');
		} else {
			$this->redirect('submitPending');
		}

	}


	/**
	 * @return void
	 */
	public function submitPendingAction() {
		$this->view->assign('node',$this->request->getInternalArgument('__node'));
	}



	/**
	 * For multiple forms on one page we check which form is submitted and forward to index if necessary
	 * @return void
	 */
	private function checkFormId() {
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
	private function sendMail($fields) {

		$receiver = explode(',', $this->request->getInternalArgument('__receiver'));

		$emailMessage = new EmailMessage('Form');

		$emailMessage->fluidView->assign('subject', $this->request->getInternalArgument('__subject'));
		$emailMessage->fluidView->assign('fields', $fields);
		$emailMessage->fluidView->setControllerContext($this->controllerContext);
		$emailMessage->send($receiver);
	}


}
