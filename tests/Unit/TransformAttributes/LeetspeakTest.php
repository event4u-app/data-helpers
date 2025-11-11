<?php

declare(strict_types=1);

namespace Tests\Unit\TransformAttributes;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Leetspeak;

class LeetspeakTestDto extends SimpleDto
{
    public function __construct(
        #[Leetspeak]
        public readonly string $username,
        #[Leetspeak]
        public readonly ?string $message = null,
    ) {}
}

describe('Leetspeak Transform Attribute', function(): void {
    it('transforms leet to 1337', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'leet']);
        expect($dto->username)->toBe('1337'); // l->1, e->3, e->3, t->7
    });

    it('transforms elite', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'elite']);
        expect($dto->username)->toBe('31!73'); // e->3, l->1, i->!, t->7, e->3
    });

    it('transforms mixed case', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'ElItE']);
        expect($dto->username)->toBe('31!73'); // E->3, l->1, I->!, t->7, E->3
    });

    it('transforms wikipedia', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'wikipedia']);
        expect($dto->username)->toBe('w!k!p3d!4'); // w->w, i->!, k->k, i->!, p->p, e->3, d->d, i->!, a->4
    });

    it('transforms common words', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'hacker']);
        expect($dto->username)->toBe('h4ck3r');

        $dto = LeetspeakTestDto::from(['username' => 'gamer']);
        expect($dto->username)->toBe('94m3r');

        $dto = LeetspeakTestDto::from(['username' => 'beast']);
        expect($dto->username)->toBe('83457'); // b->8, e->3, a->4, s->5, t->7
    });

    it('handles null values', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'test', 'message' => null]);
        expect($dto->message)->toBeNull();
    });

    it('handles empty strings', function(): void {
        $dto = LeetspeakTestDto::from(['username' => '']);
        expect($dto->username)->toBe('');
    });

    it('preserves non-leet characters', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'test123']);
        expect($dto->username)->toBe('7357123');
    });

    it('transforms sentences', function(): void {
        $dto = LeetspeakTestDto::from(['username' => 'test', 'message' => 'Hello World']);
        expect($dto->message)->toBe('H3110 W0r1d'); // H->H, e->3, l->1, l->1, o->0, W->W, o->0, r->r, l->1, d->d
    });
});
