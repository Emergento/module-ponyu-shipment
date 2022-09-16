<?php
declare(strict_types=1);

namespace Emergento\PonyUShipment\Model\Service;

use Magento\Sales\Api\Data\OrderInterface;

class IsPonyUOrder
{
    public function execute(OrderInterface $order): bool
    {
        return str_starts_with($order->getShippingMethod(), 'ponyu_');
    }
}
