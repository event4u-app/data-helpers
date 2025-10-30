<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ExistsCallback;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\UniqueCallback;
use event4u\DataHelpers\LiteDto\LiteDto;

class ProductValidationDto extends LiteDto
{
    private static ?EntityManagerInterface $entityManager = null;

    public function __construct(
        #[Required]
        #[UniqueCallback([self::class, 'validateUniqueSku'])]
        public readonly string $sku,

        #[Required]
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateRelatedProductExists'])]
        public readonly ?int $relatedProductId = null,

        public readonly ?int $id = null,
    ) {}

    public static function setEntityManager(EntityManagerInterface $entityManager): void
    {
        self::$entityManager = $entityManager;
    }

    public static function validateUniqueSku(mixed $value, array $data): bool
    {
        $qb = self::$entityManager->createQueryBuilder();
        $qb->select('COUNT(p.id)')
            ->from(Product::class, 'p')
            ->where('p.sku = :sku')
            ->setParameter('sku', $value);

        if (isset($data['id'])) {
            $qb->andWhere('p.id != :id')
                ->setParameter('id', $data['id']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }

    public static function validateRelatedProductExists(mixed $value): bool
    {
        $product = self::$entityManager->getRepository(Product::class)->find($value);

        return $product !== null && $product->isActive();
    }
}

