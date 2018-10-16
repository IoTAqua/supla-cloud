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

namespace SuplaBundle\Supla;

use SuplaBundle\Model\UserManager;

class SuplaAutodiscoverMock extends SuplaAutodiscover {
    const SECONDARY_INSTANCE = 'localhost:81';

    public function __construct(UserManager $userManager) {
        parent::__construct(self::SECONDARY_INSTANCE ? 'mocked-autodiscover' : false, 'http', 'http://supla.local', $userManager);
    }

    protected function remoteRequest($endpoint, $post = false, &$responseStatus = null) {
        if (preg_match('#/users/(.+)#', $endpoint, $match)) {
            $server = $this->getServerForUsername(urldecode($match[1]));
            if ($server) {
                $responseStatus = 200;
                return ['server' => $server];
            }
        } elseif (preg_match('#/new-account-server/#', $endpoint)) {
            $responseStatus = 200;
            return ['server' => self::SECONDARY_INSTANCE];
        } elseif (preg_match('#/mapped-client-id/(.+)/(.+)#', $endpoint, $match)) {
            $mappedClientId = $this->getMappedClientId($match[1], $match[2]);
            if ($mappedClientId) {
                $responseStatus = 200;
                return ['mapped_client_id' => $mappedClientId];
            }
        } elseif (preg_match('#/mapped-client-data/(.+)/(.+)#', $endpoint, $match)) {
            $responseStatus = 200;
            return $this->getMappedClientData($match[1], $match[2]);
        }
        $responseStatus = 404;
        return false;
    }

    private function getServerForUsername($username) {
        if (strpos($username, 'user2') !== false) {
            return self::SECONDARY_INSTANCE;
        } elseif (strpos($username, 'user') !== false) {
            return 'supla.local';
        }
    }

    private function getMappedClientId($clientId, $targetCloudAddress) {
        return [
                '100_supla' => '100_generate',
            ][$clientId] ?? '100_generate';
    }

    // http://localhost:81/oauth/v2/auth?client_id=2_19fmbgwtxl8ko40wgcscwg088c4wow4cw4g4ckgcsc08g088c0&redirect_uri=http%3A%2F%2Fsuplascripts.local%2Fapi%2Foauth&response_type=code&scope=account_r

    private function getMappedClientData($clientId, $targetCloudAddress) {
        $key = urldecode($clientId) . '.' . urldecode($targetCloudAddress);
        return [
                '100_generate.http://supla.local' => [
                    'redirectUris' => ['http://suplascripts.local/authorize'],
                    'name' => 'SUPLA Scripts',
                    'description' => 'A must-have extensions of your SUPLA!',
                ],
            ][$key] ?? false;
    }
}