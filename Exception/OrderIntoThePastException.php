<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class OrderIntoThePastException extends LocalizedException
{
    /**
     * @param Phrase|null $phrase
     * @param \Exception|null $cause
     * @param int $code
     */
    public function __construct(Phrase $phrase = null, \Exception $cause = null, $code = 0)
    {
        if ($phrase === null) {
            $phrase = new Phrase('The order is in to the past.');
        }
        parent::__construct($phrase, $cause, $code);
    }
}
