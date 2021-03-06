<?php
namespace SuplaApiBundle\Model\ChannelActionExecutor;

use SuplaBundle\Enums\ChannelFunction;
use SuplaBundle\Enums\ChannelFunctionAction;

class TurnOnActionExecutor extends SetCharValueActionExecutor {
    public function getSupportedFunctions(): array {
        return [
            ChannelFunction::POWERSWITCH(),
            ChannelFunction::LIGHTSWITCH(),
            ChannelFunction::STAIRCASETIMER(),
        ];
    }

    public function getSupportedAction(): ChannelFunctionAction {
        return ChannelFunctionAction::TURN_ON();
    }
}
