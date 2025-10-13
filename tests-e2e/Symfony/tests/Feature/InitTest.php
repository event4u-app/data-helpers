<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InitTest extends KernelTestCase
{
    public function testContainerHasLoggerService(): void
    {
        self::bootKernel();
        $this->assertTrue(static::getContainer()->has('logger'));
    }
}
