<?php
namespace CRON\FormBuilder\Service;

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * Validator for not empty values.
 *
 * @Flow\Scope("singleton")
 */
class SiteService
{

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var \TYPO3\Neos\Domain\Repository\SiteRepository
     */
    protected $siteRepository;

    /**
     * @var NodeInterface $siteNode
     */
    protected $siteNode = null;

    /**
     * Get the root site node
     *
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
     */
    public function getSiteNode()
    {

        if (!$this->siteNode) {
            $this->siteNode = $this->createContext()->getCurrentSiteNode();
        }

        return $this->siteNode;
    }

    /**
     * @param string $workspace
     * @param bool $showInvisibleAndInaccessibleContent
     *
     * @throws \Exception
     * @return \TYPO3\Neos\Domain\Service\ContentContext
     */
    public function createContext($workspace = 'live', $showInvisibleAndInaccessibleContent = true)
    {

        $currentSite = $this->siteRepository->findFirstOnline();
        if ($currentSite === null) {
            throw new \Exception('no online site available');
        }

        return $this->contextFactory->create([
            'workspaceName' => $workspace,
            'currentSite' => $currentSite,
            'invisibleContentShown' => $showInvisibleAndInaccessibleContent,
            'inaccessibleContentShown' => $showInvisibleAndInaccessibleContent
        ]);
    }
}
