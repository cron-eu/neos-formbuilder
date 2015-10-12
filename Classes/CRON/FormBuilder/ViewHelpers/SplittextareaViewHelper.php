<?php
namespace CRON\FormBuilder\ViewHelpers;

use CRON\DavShop\Domain\Service\CustomerService;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Flow\Annotations as Flow;

/**
 * Get the Values of radiobutton
 */
class SplittextareaViewHelper extends AbstractViewHelper
{

    /**
     * @param string $inputname
     */
    public function render($inputname)
    {

        $values = preg_split('/[\n\r]+/', $this->renderChildren());

        $ret = '';

        for ($i = 0; $i < count($values); $i++) {
            $ret .= "<input name='" . $inputname . "' type='radio' name='' value='" . $values[$i] . "'><label>"
                    . $values[$i] . "</label>";
        }

        return $ret;
    }

}