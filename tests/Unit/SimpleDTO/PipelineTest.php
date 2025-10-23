<?php

declare(strict_types=1);

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDTO\Normalizers\DefaultValuesNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\TypeNormalizer;
use event4u\DataHelpers\SimpleDTO\Pipeline\CallbackStage;
use event4u\DataHelpers\SimpleDTO\Pipeline\DTOPipeline;
use event4u\DataHelpers\SimpleDTO\Pipeline\NormalizerStage;
use event4u\DataHelpers\SimpleDTO\Pipeline\PipelineStageInterface;
use event4u\DataHelpers\SimpleDTO\Pipeline\TransformerStage;
use event4u\DataHelpers\SimpleDTO\Pipeline\ValidationStage;
use event4u\DataHelpers\SimpleDTO\Transformers\TrimStringsTransformer;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('Pipeline', function(): void {
    describe('DTOPipeline', function(): void {
        it('processes data through stages', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
            $pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

            $data = ['name' => '  John  ', 'age' => '30'];
            $result = $pipeline->process($data);

            expect($result['name'])->toBe('John')
                ->and($result['age'])->toBe(30);
        });

        it('returns stages', function(): void {
            $pipeline = new DTOPipeline();
            $stage1 = new NormalizerStage(new TypeNormalizer(['age' => 'int']));
            $stage2 = new TransformerStage(new TrimStringsTransformer());

            $pipeline->addStage($stage1);
            $pipeline->addStage($stage2);

            $stages = $pipeline->getStages();

            expect($stages)->toHaveCount(2)
                ->and($stages[0])->toBe($stage1)
                ->and($stages[1])->toBe($stage2);
        });

        it('can clear stages', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
            $pipeline->clear();

            expect($pipeline->getStages())->toBeEmpty();
        });

        it('tracks context', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int']), 'type_normalizer'));
            $pipeline->addStage(new TransformerStage(new TrimStringsTransformer(), 'trim_transformer'));

            $data = ['name' => '  John  ', 'age' => '30'];
            $pipeline->process($data);

            $context = $pipeline->getContext();

            expect($context)->toHaveKey('type_normalizer')
                ->and($context)->toHaveKey('trim_transformer')
                ->and($context['type_normalizer']['status'])->toBe('success')
                ->and($context['trim_transformer']['status'])->toBe('success');
        });

        it('can clear context', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));

            $pipeline->process(['name' => 'John', 'age' => '30']);
            $pipeline->clearContext();

            expect($pipeline->getContext())->toBeEmpty();
        });
    });

    describe('TransformerStage', function(): void {
        it('applies transformer', function(): void {
            $stage = new TransformerStage(new TrimStringsTransformer());
            $data = ['name' => '  John  '];

            $result = $stage->process($data);

            expect($result['name'])->toBe('John');
        });

        it('has name', function(): void {
            $stage = new TransformerStage(new TrimStringsTransformer(), 'my_transformer');

            expect($stage->getName())->toBe('my_transformer');
        });
    });

    describe('NormalizerStage', function(): void {
        it('applies normalizer', function(): void {
            $stage = new NormalizerStage(new TypeNormalizer(['age' => 'int']));
            $data = ['name' => 'John', 'age' => '30'];

            $result = $stage->process($data);

            expect($result['age'])->toBe(30);
        });

        it('has name', function(): void {
            $stage = new NormalizerStage(new TypeNormalizer(['age' => 'int']), 'my_normalizer');

            expect($stage->getName())->toBe('my_normalizer');
        });
    });

    describe('ValidationStage', function(): void {
        it('validates required fields', function(): void {
            $stage = new ValidationStage(['name' => ['required']]);
            $data = ['name' => 'John'];

            $result = $stage->process($data);

            expect($result)->toBe($data);
        });

        it('throws on validation failure', function(): void {
            $stage = new ValidationStage(['name' => ['required']]);
            $data = ['age' => 30];

            expect(fn(): array => $stage->process($data))
                ->toThrow(ValidationException::class);
        });

        it('validates email', function(): void {
            $stage = new ValidationStage(['email' => ['email']]);

            $valid = $stage->process(['email' => 'john@example.com']);
            expect($valid)->toBe(['email' => 'john@example.com']);

            expect(fn(): array => $stage->process(['email' => 'invalid']))
                ->toThrow(ValidationException::class);
        });

        it('validates numeric', function(): void {
            $stage = new ValidationStage(['age' => ['numeric']]);

            $valid = $stage->process(['age' => 30]);
            expect($valid)->toBe(['age' => 30]);

            expect(fn(): array => $stage->process(['age' => 'abc']))
                ->toThrow(ValidationException::class);
        });

        it('validates min', function(): void {
            $stage = new ValidationStage(['age' => ['min:18']]);

            $valid = $stage->process(['age' => 20]);
            expect($valid)->toBe(['age' => 20]);

            expect(fn(): array => $stage->process(['age' => 15]))
                ->toThrow(ValidationException::class);
        });

        it('validates max', function(): void {
            $stage = new ValidationStage(['age' => ['max:100']]);

            $valid = $stage->process(['age' => 50]);
            expect($valid)->toBe(['age' => 50]);

            expect(fn(): array => $stage->process(['age' => 150]))
                ->toThrow(ValidationException::class);
        });

        it('has name', function(): void {
            $stage = new ValidationStage([], 'my_validation');

            expect($stage->getName())->toBe('my_validation');
        });
    });

    describe('CallbackStage', function(): void {
        it('executes callback', function(): void {
            $stage = new CallbackStage(function(array $data): array {
                $data['processed'] = true;

                return $data;
            });

            $result = $stage->process(['name' => 'John']);

            expect($result)->toHaveKey('processed')
                ->and($result['processed'])->toBeTrue();
        });

        it('can modify data', function(): void {
            $stage = new CallbackStage(function(array $data): array {
                if (isset($data['name'])) {
                    $data['name'] = strtoupper((string)$data['name']);
                }

                return $data;
            });

            $result = $stage->process(['name' => 'john']);

            expect($result['name'])->toBe('JOHN');
        });

        it('has name', function(): void {
            $stage = new CallbackStage(fn($data): array => $data, 'my_callback');

            expect($stage->getName())->toBe('my_callback');
        });
    });

    describe('DTO Pipeline Integration', function(): void {
        it('creates DTO with pipeline', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
            $pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

            $data = ['name' => '  John  ', 'age' => '30'];
            $user = UserDTO::fromArrayWithPipeline($data, $pipeline);

            expect($user->name)->toBe('John')
                ->and($user->age)->toBe(30);
        });

        it('processes existing DTO with pipeline', function(): void {
            $user = UserDTO::fromArray(['name' => '  Jane  ', 'age' => 25]);

            $pipeline = new DTOPipeline();
            $pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));

            $processed = $user->processWith($pipeline);

            expect($processed->name)->toBe('Jane')
                ->and($user->name)->toBe('  Jane  ');
        });

        it('handles complex pipeline', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->addStage(new NormalizerStage(new DefaultValuesNormalizer(['age' => 0])));
            $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
            $pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
            $pipeline->addStage(new ValidationStage(['name' => ['required']]));

            $data = ['name' => '  Bob  '];
            $user = UserDTO::fromArrayWithPipeline($data, $pipeline);

            expect($user->name)->toBe('Bob')
                ->and($user->age)->toBe(0);
        });
    });

    describe('Custom Pipeline Stages', function(): void {
        it('uses custom stage', function(): void {
            $stage = new class implements PipelineStageInterface {
                public function process(array $data): array
                {
                    if (isset($data['name'])) {
                        $data['name'] = ucwords((string)$data['name']);
                    }

                    return $data;
                }

                public function getName(): string
                {
                    return 'ucwords_stage';
                }
            };

            $pipeline = new DTOPipeline();
            $pipeline->addStage($stage);

            $result = $pipeline->process(['name' => 'john doe']);

            expect($result['name'])->toBe('John Doe');
        });
    });

    describe('Error Handling', function(): void {
        it('stops on error by default', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->addStage(new ValidationStage(['name' => ['required']]));
            $pipeline->addStage(new CallbackStage(fn($data): array => $data, 'never_reached'));

            expect(fn(): array => $pipeline->process(['age' => 30]))
                ->toThrow(ValidationException::class);

            $context = $pipeline->getContext();
            expect($context)->toHaveKey('validation')
                ->and($context)->not->toHaveKey('never_reached');
        });

        it('can continue on error', function(): void {
            $pipeline = new DTOPipeline();
            $pipeline->setStopOnError(false);
            $pipeline->addStage(new ValidationStage(['name' => ['required']]));
            $pipeline->addStage(new CallbackStage(function($data): array {
                $data['processed'] = true;

                return $data;
            }, 'callback'));

            $result = $pipeline->process(['age' => 30]);

            expect($result)->toHaveKey('processed');

            $context = $pipeline->getContext();
            expect($context['validation']['status'])->toBe('error')
                ->and($context['callback']['status'])->toBe('success');
        });
    });
});
