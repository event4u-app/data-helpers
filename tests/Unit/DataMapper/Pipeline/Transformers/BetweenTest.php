<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;

describe('Between Transformer', function(): void {
    it('returns true for value within range (inclusive by default)', function(): void {
        $template = ['result' => '{{ value | between:3:5 }}'];

        $result1 = DataMapper::source(['value' => 3])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => 4])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => 5])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeTrue();
        expect($result2['result'])->toBeTrue();
        expect($result3['result'])->toBeTrue();
    });

    it('returns false for value outside range', function(): void {
        $template = ['result' => '{{ value | between:3:5 }}'];

        $result1 = DataMapper::source(['value' => 2])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => 6])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => 0])->template($template)->map()->getTarget();
        $result4 = DataMapper::source(['value' => 10])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeFalse();
        expect($result2['result'])->toBeFalse();
        expect($result3['result'])->toBeFalse();
        expect($result4['result'])->toBeFalse();
    });

    it('works with strict mode (exclusive boundaries)', function(): void {
        $template = ['result' => '{{ value | between:3:5:strict }}'];

        // In strict mode, 3 and 5 are NOT included
        $result1 = DataMapper::source(['value' => 3])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => 4])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => 5])->template($template)->map()->getTarget();
        $result4 = DataMapper::source(['value' => 2])->template($template)->map()->getTarget();
        $result5 = DataMapper::source(['value' => 6])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeFalse(); // 3 is not > 3
        expect($result2['result'])->toBeTrue();  // 4 is > 3 and < 5
        expect($result3['result'])->toBeFalse(); // 5 is not < 5
        expect($result4['result'])->toBeFalse();
        expect($result5['result'])->toBeFalse();
    });

    it('works with negative ranges', function(): void {
        $template = ['result' => '{{ value | between:-5:5 }}'];

        $result1 = DataMapper::source(['value' => -5])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => 0])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => 5])->template($template)->map()->getTarget();
        $result4 = DataMapper::source(['value' => -6])->template($template)->map()->getTarget();
        $result5 = DataMapper::source(['value' => 6])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeTrue();
        expect($result2['result'])->toBeTrue();
        expect($result3['result'])->toBeTrue();
        expect($result4['result'])->toBeFalse();
        expect($result5['result'])->toBeFalse();
    });

    it('works with decimal values', function(): void {
        $template = ['result' => '{{ value | between:1.5:3.5 }}'];

        $result1 = DataMapper::source(['value' => 1.5])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => 2.5])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => 3.5])->template($template)->map()->getTarget();
        $result4 = DataMapper::source(['value' => 1.4])->template($template)->map()->getTarget();
        $result5 = DataMapper::source(['value' => 3.6])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeTrue();
        expect($result2['result'])->toBeTrue();
        expect($result3['result'])->toBeTrue();
        expect($result4['result'])->toBeFalse();
        expect($result5['result'])->toBeFalse();
    });

    it('returns false for non-numeric values', function(): void {
        $template = ['result' => '{{ value | between:3:5 }}'];

        $result1 = DataMapper::source(['value' => 'abc'])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => null])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeFalse();
        expect($result2['result'])->toBeFalse();
    });

    it('handles string numeric values', function(): void {
        $template = ['result' => '{{ value | between:3:5 }}'];

        $result1 = DataMapper::source(['value' => '3'])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => '4'])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => '5'])->template($template)->map()->getTarget();
        $result4 = DataMapper::source(['value' => '2'])->template($template)->map()->getTarget();
        $result5 = DataMapper::source(['value' => '6'])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeTrue();
        expect($result2['result'])->toBeTrue();
        expect($result3['result'])->toBeTrue();
        expect($result4['result'])->toBeFalse();
        expect($result5['result'])->toBeFalse();
    });

    it('works with zero boundaries', function(): void {
        $template = ['result' => '{{ value | between:0:10 }}'];

        $result1 = DataMapper::source(['value' => 0])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => 5])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => 10])->template($template)->map()->getTarget();
        $result4 = DataMapper::source(['value' => -1])->template($template)->map()->getTarget();
        $result5 = DataMapper::source(['value' => 11])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeTrue();
        expect($result2['result'])->toBeTrue();
        expect($result3['result'])->toBeTrue();
        expect($result4['result'])->toBeFalse();
        expect($result5['result'])->toBeFalse();
    });

    it('handles edge case where min equals max', function(): void {
        $template = ['result' => '{{ value | between:5:5 }}'];

        $result1 = DataMapper::source(['value' => 5])->template($template)->map()->getTarget();
        $result2 = DataMapper::source(['value' => 4])->template($template)->map()->getTarget();
        $result3 = DataMapper::source(['value' => 6])->template($template)->map()->getTarget();

        expect($result1['result'])->toBeTrue();
        expect($result2['result'])->toBeFalse();
        expect($result3['result'])->toBeFalse();
    });

    it('demonstrates difference from clamp', function(): void {
        $betweenTemplate = ['result' => '{{ value | between:3:5 }}'];
        $clampTemplate = ['result' => '{{ value | clamp:3:5 }}'];

        // Between returns boolean
        $betweenResult1 = DataMapper::source(['value' => 2])->template($betweenTemplate)->map()->getTarget();
        $betweenResult2 = DataMapper::source(['value' => 3])->template($betweenTemplate)->map()->getTarget();
        $betweenResult3 = DataMapper::source(['value' => 6])->template($betweenTemplate)->map()->getTarget();

        expect($betweenResult1['result'])->toBeFalse();
        expect($betweenResult2['result'])->toBeTrue();
        expect($betweenResult3['result'])->toBeFalse();

        // Clamp limits the value
        $clampResult1 = DataMapper::source(['value' => 2])->template($clampTemplate)->map()->getTarget();
        $clampResult2 = DataMapper::source(['value' => 6])->template($clampTemplate)->map()->getTarget();

        expect($clampResult1['result'])->toBe(3.0);
        expect($clampResult2['result'])->toBe(5.0);
    });
});
