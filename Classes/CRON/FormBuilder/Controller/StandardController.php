<?php
namespace CRON\FormBuilder\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CRON.FormBuilder".      *
 *                                                                        *
 *                                                                        */


use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use CRON\FormBuilder\Utils\EmailMessage;


class StandardController extends \TYPO3\Flow\Mvc\Controller\ActionController {

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
	public $siteNode = NULL;


	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('elements',$this->request->getInternalArgument('__elements'));
		$legend = $this->request->getInternalArgument('__legend');
		$this->view->assign('legend', $legend);
	}

	/**
	 * Get the root site node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function getSiteNode() {

		if (!$this->siteNode) {

			/** @var ContentContext $contentContext */
			$contentContext = $this->createContext();
			$this->siteNode = $contentContext->getCurrentSiteNode();
		}

		return $this->siteNode;
	}

	/**
	 * @return void
	 */
	public function transferAction() {

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
	}

	/**
	 * Sends your details to recipient
	 * @param array $fields
	 * @return void
	 */
	public function sendMail($fields) {


		$receiver = $this->request->getInternalArgument('__receiver');
		//$copytouser = $this->request->getInternalArgument('__copytouser');


		$emailMessage = new EmailMessage('Form');

		$sender = $this->request->getInternalArgument('__sender');
		if ($sender) {
			$emailMessage->mail->setFrom($sender);
		}

		$emailMessage->fluidView->assign('fields', $fields);
		$emailMessage->fluidView->setControllerContext($this->controllerContext);
		$emailMessage->send(explode(',', $receiver));
	}

	/**
	 * @param string $workspace
	 * @param bool   $showInvisibleAndInaccessibleContent
	 *
	 * @throws \Exception
	 * @return \TYPO3\Neos\Domain\Service\ContentContext
	 */
	public function createContext($workspace = 'live', $showInvisibleAndInaccessibleContent = TRUE) {

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
