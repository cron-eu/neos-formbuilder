<?php
namespace CRON\FormBuilder\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CRON.FormBuilder".      *
 *                                                                        *
 *                                                                        */


use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use CRON\FormBuilder\Utils\EmailMessage;


class FormBuilderController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;


	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;


	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @var Node $siteNode
	 */
	protected $siteNode = NULL;


	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('elements',$this->request->getInternalArgument('__elements'));
	}

	/**
	 * @return void
	 */
	public function submitAction() {

		$siteNode = $this->getSiteNode();
		$values = $this->request->getArguments();
		$hideFields = array('CRON.FormBuilder:SubmitButton');

		$nodes = [];

		foreach($values as $identifier => $value) {
			$node = $this->nodeDataRepository->findOneByIdentifier($identifier, $siteNode->getWorkspace());
			if(!in_array($node->getNodeType(), $hideFields)){
				$nodes[] = array('label' => $node->getProperty('label'), 'value' => $value);
			}
		}

		$this->sendMail($nodes);

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

	/**
	 * Get the root site node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	private function getSiteNode() {

		if (!$this->siteNode) {

			/** @var ContentContext $contentContext */
			$contentContext = $this->createContext();
			$this->siteNode = $contentContext->getCurrentSiteNode();
		}

		return $this->siteNode;
	}

	/**
	 * @param string $workspace
	 * @param bool   $showInvisibleAndInaccessibleContent
	 *
	 * @throws \Exception
	 * @return \TYPO3\Neos\Domain\Service\ContentContext
	 */
	private function createContext($workspace = 'live', $showInvisibleAndInaccessibleContent = TRUE) {

		/** @var Site $currentSite */
		$currentSite = $this->siteRepository->findFirstOnline();
		if ($currentSite === NULL) {
			throw new \Exception('no online site available');
		}

		return $this->contextFactory->create([
			'workspaceName'            => $workspace,
			'currentSite'              => $currentSite,
			'invisibleContentShown'    => $showInvisibleAndInaccessibleContent,
			'inaccessibleContentShown' => $showInvisibleAndInaccessibleContent
		]);
	}

}
