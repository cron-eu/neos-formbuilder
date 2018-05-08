<?php
namespace CRON\FormBuilder\Utils;

use Neos\Flow\Annotations as Flow;
use TYPO3\SwiftMailer\Message;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * @property string viewName
 */
class EmailMessage
{

    /**
     * @Flow\Inject
     * @var \TYPO3\Fluid\View\StandaloneView
     */
    public $fluidView;

    /**
     * @var Message
     */
    public $mail;

    /**
     * @Flow\InjectConfiguration(path="Email")
     * @var array
     */
    protected $conf;

    /**
     * Creates a new email message instance
     *
     * @param string $name name of the view (Templates/Email/$name.html)
     */
    function __construct($name)
    {

        $this->viewName = $name;
    }

    public function initializeObject()
    {

        $this->fluidView->setLayoutRootPath($this->conf['layoutRootPath']);
        $this->fluidView->setTemplatePathAndFilename(sprintf(
            $this->conf['templatePathAndFilenameFormat'],
            $this->viewName
        ));

        $this->mail = (new Message())->setFrom($this->conf['defaults']['from']);
    }

    /**
     * Attaches an file to the email
     * @param NodeInterface $node
     * @param array $data
     */
    public function addAttachment($node, $data)
    {

        $this->mail->attach(
            \Swift_Attachment::newInstance(
                file_get_contents($data['tmp_name']),
                join('-', [$node->getProperty('label'), $data['name']]),
                $data['type']
            )
        );
    }

    /**
     * Sends the email
     *
     * @param mixed $recipients single or multiple recipients
     *
     * @return int the number of recipients who were accepted for delivery
     */
    public function send($recipients)
    {

        $this->mail->setBody($this->fluidView->render(), 'text/plain');

        // set the subject only if not already set
        if (!$this->mail->getSubject()) {

            // render the subject from template, if available
            if ($subject = $this->fluidView->renderSection('Subject', null, true)) {
                $this->mail->setSubject($subject);
            } else {
                // else use the default subject from settings
                $this->mail->setSubject($this->conf['defaults']['subject']);
            }
        }

        $this->mail->setTo($recipients);

        return $this->mail->send();
    }
}
