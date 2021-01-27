<?php
namespace SuplaBundle\Model\ChannelStateGetter;

use SuplaBundle\Entity\IODeviceChannel;
use SuplaBundle\Enums\ChannelFunction;
use SuplaBundle\Supla\SuplaServerAware;

class DigiglassChannelStateGetter implements SingleChannelStateGetter {
    use SuplaServerAware;

    public function getState(IODeviceChannel $channel): array {
        $mask = $this->suplaServer->getValue('DIGIGLASS', $channel);
        $state = DigiglassState::channel($channel)->setMask($mask);
        return [
            'transparent' => $state->getTransparentSections(),
            'opaque' => $state->getOpaqueSections(),
            'mask' => $state->getMask(),
        ];
    }

    public function supportedFunctions(): array {
        return [
            ChannelFunction::DIGIGLASS_HORIZONTAL(),
            ChannelFunction::DIGIGLASS_VERTICAL(),
        ];
    }
}