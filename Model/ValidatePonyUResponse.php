<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model;

use Emergento\PonyUShipment\Exception\NoAvailableTimeSlotException;
use Emergento\PonyUShipment\Exception\OrderIntoThePastException;
use GuzzleHttp\Exception\GuzzleException;

class ValidatePonyUResponse
{
    /**
     * @param GuzzleException $exception
     * @return void
     * @throws NoAvailableTimeSlotException
     * @throws OrderIntoThePastException
     */
    public function execute(GuzzleException $exception): void
    {
        $exceptionContent = $exception->getResponse()->getBody()->getContents();
        $errorCodesInResponse = array_column(json_decode($exceptionContent, true)['errors'], 'code');
        $errorMessages = array_column(json_decode($exceptionContent, true)['errors'], 'message');

        if (in_array('order_into_the_past', $errorCodesInResponse)) {
            throw new OrderIntoThePastException(__($errorMessages[0]));
        }

        if (in_array('no_available_ts', $errorCodesInResponse) || in_array('full_delivery_range', $errorCodesInResponse)) {
            throw new NoAvailableTimeSlotException(__($errorMessages[0]));
        }
    }
}
