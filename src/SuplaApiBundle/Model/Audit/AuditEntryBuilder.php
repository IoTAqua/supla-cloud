<?php
namespace SuplaApiBundle\Model\Audit;

use Assert\Assertion;
use Doctrine\ORM\EntityManagerInterface;
use MyCLabs\Enum\Enum;
use SuplaBundle\Entity\AuditEntry;
use SuplaBundle\Entity\User;
use SuplaBundle\Enums\AuditedEvent;
use SuplaBundle\Model\TimeProvider;

class AuditEntryBuilder {
    /** @var AuditedEvent */
    private $event;
    /** @var User|null */
    private $user;
    private $textParam;
    private $intParam;

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TimeProvider */
    private $timeProvider;
    private $ipv4;

    public function __construct(EntityManagerInterface $entityManager, TimeProvider $timeProvider = null) {
        $this->entityManager = $entityManager;
        $this->timeProvider = $timeProvider ?: new TimeProvider();
    }

    public function setEvent(AuditedEvent $event): AuditEntryBuilder {
        $this->event = $event;
        return $this;
    }

    /** @param User|null $user */
    public function setUser($user): AuditEntryBuilder {
        $this->user = $user;
        return $this;
    }

    public function setIpv4($ipv4): AuditEntryBuilder {
        $this->ipv4 = $ipv4 ? ip2long($ipv4) : null;
        return $this;
    }

    public function setTextParam(string $value): AuditEntryBuilder {
        $this->textParam = $value;
        return $this;
    }

    public function setIntParam($value): AuditEntryBuilder {
        if ($value instanceof Enum) {
            $value = $value->getValue();
        }
        Assertion::numeric($value);
        $this->intParam = $value;
        return $this;
    }

    public function build(): AuditEntry {
        Assertion::notNull($this->event, 'Audit Entry must have an event.');
        return new AuditEntry(
            $this->timeProvider->getDateTime(),
            $this->event,
            $this->user,
            $this->ipv4,
            $this->textParam,
            $this->intParam
        );
    }

    public function buildAndSave(): AuditEntry {
        $entry = $this->build();
        $this->entityManager->persist($entry);
        return $entry;
    }

    public function buildAndFlush(): AuditEntry {
        $entry = $this->buildAndSave();
        $this->entityManager->flush();
        return $entry;
    }
}
