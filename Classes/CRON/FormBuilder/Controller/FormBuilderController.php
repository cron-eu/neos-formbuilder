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
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('attributes',$this->request->getInternalArgument('__attributes'));
		$this->view->assign('elements',$this->request->getInternalArgument('__elements'));
		$this->view->assign('elementsArray',$this->request->getInternalArgument('__elementsArray'));
		$this->view->assign('documentNode',$this->request->getInternalArgument('__documentNode'));
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

			$fields[] = array('label' => $node->getProperty('label'), 'value' => $value);
		}

		$this->sendMail($fields);

		$this->redirect('submitPending');
	}

	/**
	 * @return void
	 */
	public function submitPendingAction() {}


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
