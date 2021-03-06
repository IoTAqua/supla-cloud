<?php
namespace SuplaApiBundle\Model\ChannelParamsUpdater;

use SuplaBundle\Enums\ChannelFunction;

class ControllingTheGarageDoorRelatedSensor extends ControllingAnyLockRelatedSensor {
    public function __construct() {
        parent::__construct(ChannelFunction::CONTROLLINGTHEGARAGEDOOR(), ChannelFunction::OPENINGSENSOR_GARAGEDOOR());
    }
}
