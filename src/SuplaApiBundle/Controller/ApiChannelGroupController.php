<?php
/*
 Copyright (C) AC SOFTWARE SP. Z O.O.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace SuplaApiBundle\Controller;

use Assert\Assertion;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SuplaApiBundle\Model\ChannelActionExecutor\ChannelActionExecutor;
use SuplaBundle\Entity\IODeviceChannelGroup;
use SuplaBundle\Enums\ChannelFunctionAction;
use SuplaBundle\Model\Transactional;
use SuplaBundle\Supla\SuplaServerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiChannelGroupController extends RestController {
    use Transactional;
    use SuplaServerAware;

    /** @var ChannelActionExecutor */
    private $channelActionExecutor;

    public function __construct(ChannelActionExecutor $channelActionExecutor) {
        $this->channelActionExecutor = $channelActionExecutor;
    }

    /**
     * @Rest\Get("/channel-groups")
     */
    public function getChannelGroupsAction(Request $request) {
        $channelGroups = $this->getUser()->getChannelGroups();
        $view = $this->view($channelGroups, Response::HTTP_OK);
        $this->setSerializationGroups($view, $request, ['channels']);
        return $view;
    }

    /**
     * @Rest\Get("/channel-groups/{channelGroup}")
     * @Security("channelGroup.belongsToUser(user)")
     */
    public function getChannelGroupAction(Request $request, IODeviceChannelGroup $channelGroup) {
        $view = $this->view($channelGroup, Response::HTTP_OK);
        $this->setSerializationGroups($view, $request, ['channels', 'iodevice', 'location']);
        return $view;
    }

    /**
     * @Rest\Post("/channel-groups")
     */
    public function postChannelGroupAction(IODeviceChannelGroup $channelGroup) {
        $user = $this->getUser();
        Assertion::lessThan($user->getChannelGroups()->count(), $user->getLimitChannelGroup(), 'Channel group limit has been exceeded');
        Assertion::lessOrEqualThan(
            $channelGroup->getChannels()->count(),
            $user->getLimitChannelPerGroup(),
            'Too many channels in this group'
        );
        return $this->transactional(function (EntityManagerInterface $em) use ($channelGroup) {
            $em->persist($channelGroup);
            $this->suplaServer->reconnect();
            return $this->view($channelGroup, Response::HTTP_CREATED);
        });
    }

    /**
     * @Rest\Put("/channel-groups/{channelGroup}")
     */
    public function putChannelGroupAction(IODeviceChannelGroup $channelGroup, IODeviceChannelGroup $updated) {
        $user = $this->getUser();
        Assertion::lessOrEqualThan($updated->getChannels()->count(), $user->getLimitChannelPerGroup(), 'Too many channels in this group');
        return $this->transactional(function (EntityManagerInterface $em) use ($channelGroup, $updated) {
            $channelGroup->setCaption($updated->getCaption());
            $channelGroup->setAltIcon($updated->getAltIcon());
            $channelGroup->setChannels($updated->getChannels());
            $channelGroup->setHidden($updated->getHidden());
            $channelGroup->setLocation($updated->getLocation());
            $em->persist($channelGroup);
            $this->suplaServer->reconnect();
            return $this->view($channelGroup, Response::HTTP_CREATED);
        });
    }

    /**
     * @Rest\Delete("/channel-groups/{channelGroup}")
     * @Security("channelGroup.belongsToUser(user)")
     */
    public function deleteChannelGroupAction(IODeviceChannelGroup $channelGroup) {
        return $this->transactional(function (EntityManagerInterface $em) use ($channelGroup) {
            $em->remove($channelGroup);
            $this->suplaServer->reconnect();
            return new Response('', Response::HTTP_NO_CONTENT);
        });
    }

    /**
     * @Rest\Patch("/channel-groups/{channelGroup}")
     * @Security("channelGroup.belongsToUser(user)")
     */
    public function patchChannelGroupAction(Request $request, IODeviceChannelGroup $channelGroup) {
        $params = json_decode($request->getContent(), true);
        Assertion::keyExists($params, 'action', 'Missing action.');
        $action = ChannelFunctionAction::fromString($params['action']);
        unset($params['action']);
        $this->channelActionExecutor->executeAction($channelGroup, $action, $params);
        return $this->handleView($this->view(null, Response::HTTP_NO_CONTENT));
    }
}
