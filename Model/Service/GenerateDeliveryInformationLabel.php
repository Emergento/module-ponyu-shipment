<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Service;

use Emergento\PonyUShippingMethod\Model\GenerateSlotLabel;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;

/**
 * Generate a readable delivery information label
 * @api
 */
class GenerateDeliveryInformationLabel
{
    public function __construct(
        private readonly GenerateSlotLabel $generateSlotLabel,
        private readonly Json $json
    ) {
    }

    public function execute(Order $order): Phrase
    {
        $deliverySlot = $this->json->unserialize($order->getData('ponyu_slot'));
        return __('Order will be delivered %1.', $this->generateSlotLabel->execute(
            new \DateTime($deliverySlot['deliveryDateStart']),
            new \DateTime($deliverySlot['deliveryDateEnd'])
        ));
    }
}
