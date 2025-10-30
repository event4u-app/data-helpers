<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Email;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ExistsCallback;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\UniqueCallback;
use event4u\DataHelpers\LiteDto\LiteDto;

class UserValidationDto extends LiteDto
{
    private static ?EntityManagerInterface $entityManager = null;

    public function __construct(
        #[Required]
        #[Email]
        #[UniqueCallback([self::class, 'validateUniqueEmail'])]
        public readonly string $email,

        #[Required]
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateManagerExists'])]
        public readonly ?int $managerId = null,

        public readonly ?int $id = null,
    ) {}

    public static function setEntityManager(EntityManagerInterface $entityManager): void
    {
        self::$entityManager = $entityManager;
    }

    public static function validateUniqueEmail(mixed $value, array $data): bool
    {
        $qb = self::$entityManager->createQueryBuilder();
        $qb->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $value);

        if (isset($data['id'])) {
            $qb->andWhere('u.id != :id')
                ->setParameter('id', $data['id']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }

    public static function validateManagerExists(mixed $value): bool
    {
        return self::$entityManager->getRepository(User::class)->find($value) !== null;
    }
}

