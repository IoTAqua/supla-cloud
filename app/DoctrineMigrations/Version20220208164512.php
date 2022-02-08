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

namespace Supla\Migrations;

use SuplaBundle\Enums\ChannelFunction;
use SuplaBundle\Enums\ChannelType;

/**
 * New channel.config.addToHistory for EM and IC.
 * Split channel.config.numberOfAttemptsToOpenOrClose to numberOfAttemptsToOpen and numberOfAttemptsToClose.
 * Allow adding new flags for a device and channels.
 */
class Version20220208164512 extends NoWayBackMigration {
    public function migrate() {
        $this->updateImpulseCountersAddToHistory();
        $this->updateElectricityMetersAddToHistory();
        $this->migrateNumberOfAttemptsToOpenOrClose();
        $this->migrateProcedures();
    }

    private function updateImpulseCountersAddToHistory() {
        $icType = ChannelType::IMPULSECOUNTER;
        $icQuery = $this->getConnection()->executeQuery("SELECT id, user_config FROM supla_dev_channel WHERE type = $icType");
        while ($icChannel = $icQuery->fetchAssociative()) {
            $id = $icChannel['id'];
            $userConfig = json_decode($icChannel['user_config'], true);
            $initialValue = $userConfig['initialValue'] ?? 0;
            $userConfig['addToHistory'] = $initialValue > 0;
            $this->addSql(
                'UPDATE supla_dev_channel SET user_config=:config WHERE id=:id',
                ['id' => $id, 'config' => json_encode($userConfig)]
            );
        }
    }

    private function updateElectricityMetersAddToHistory() {
        $ecFunction = ChannelFunction::ELECTRICITYMETER;
        $ecQuery = $this->getConnection()->executeQuery("SELECT id, user_config FROM supla_dev_channel WHERE func = $ecFunction");
        while ($ecChannel = $ecQuery->fetchAssociative()) {
            $id = $ecChannel['id'];
            $userConfig = json_decode($ecChannel['user_config'], true);
            $initialValues = $userConfig['electricityMeterInitialValues'] ?? [];
            $nonZeroInitialValue = array_filter($initialValues);
            $userConfig['addToHistory'] = count($nonZeroInitialValue) > 0;
            $this->addSql(
                'UPDATE supla_dev_channel SET user_config=:config WHERE id=:id',
                ['id' => $id, 'config' => json_encode($userConfig)]
            );
        }
    }

    private function migrateNumberOfAttemptsToOpenOrClose() {
        $gateFunctions = implode(',', [ChannelFunction::CONTROLLINGTHEGARAGEDOOR, ChannelFunction::CONTROLLINGTHEGATE]);
        $gatesQuery = $this->getConnection()->executeQuery("SELECT id, user_config FROM supla_dev_channel WHERE func IN($gateFunctions)");
        while ($gateChannel = $gatesQuery->fetchAssociative()) {
            $id = $gateChannel['id'];
            $userConfig = json_decode($gateChannel['user_config'], true);
            if (isset($userConfig['numberOfAttemptsToOpenOrClose'])) {
                $numberOfAttempts = intval($userConfig['numberOfAttemptsToOpenOrClose']) ?: 1;
                unset($userConfig['numberOfAttemptsToOpenOrClose']);
                $userConfig['numberOfAttemptsToOpen'] = $numberOfAttempts;
                $userConfig['numberOfAttemptsToClose'] = $numberOfAttempts;
                $this->addSql(
                    'UPDATE supla_dev_channel SET user_config=:config WHERE id=:id',
                    ['id' => $id, 'config' => json_encode($userConfig)]
                );
            }
        }
    }

    private function migrateProcedures() {
        $this->addSql('DROP PROCEDURE IF EXISTS `supla_set_channel_flags`');
        $this->addSql('DROP PROCEDURE IF EXISTS `supla_update_iodevice`');

        $this->addSql(<<<PROCEDURE
CREATE PROCEDURE `supla_set_channel_flags`(IN `_user_id` INT, IN `_channel_id` INT, IN `_flags` INT)
    NO SQL
UPDATE supla_dev_channel SET flags = flags | _flags WHERE id = _channel_id AND user_id = _user_id
PROCEDURE
        );

        $this->addSql(<<<PROCEDURE
CREATE PROCEDURE `supla_update_iodevice`(IN `_name` VARCHAR(100) CHARSET utf8mb4, IN `_last_ipv4` INT(10) UNSIGNED, 
  IN `_software_version` VARCHAR(20) CHARSET utf8, IN `_protocol_version` INT(11), IN `_original_location_id` INT(11), 
  IN `_auth_key` VARCHAR(64) CHARSET utf8, IN `_id` INT(11), IN `_flags` INT(11))
    NO SQL
BEGIN
UPDATE `supla_iodevice`
SET
`name` = _name,
`last_connected` = UTC_TIMESTAMP(),
`last_ipv4` = _last_ipv4,
`software_version` = _software_version,
`protocol_version` = _protocol_version,
original_location_id = _original_location_id,
`flags` = `flags` | _flags WHERE `id` = _id;
IF _auth_key IS NOT NULL THEN
  UPDATE `supla_iodevice`
  SET `auth_key` = _auth_key WHERE `id` = _id AND `auth_key` IS NULL;
END IF;
END
PROCEDURE
        );
    }
}
