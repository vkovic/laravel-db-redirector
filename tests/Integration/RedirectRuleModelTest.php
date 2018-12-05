<?php

namespace Vkovic\LaravelDbRedirector\Test\Integration;

use Vkovic\LaravelDbRedirector\Models\RedirectRule;
use Vkovic\LaravelDbRedirector\Test\TestCase;

class RedirectRuleModelTest extends TestCase
{
    public function test_it_sets_default_status_code_when_null_passed()
    {
        $redirectRule = new RedirectRule;
        $redirectRule->origin = '/eleven';
        $redirectRule->destination = '/twelve';
        $redirectRule->status_code = null;
        $redirectRule->save();

        $redirectRule = RedirectRule::find($redirectRule->id);

        $this->assertEquals(301, $redirectRule->status_code);

        $redirectRule->delete();
    }

    public function test_recursive_rule_deletion()
    {
        RedirectRule::create([
            'origin' => '/one',
            'destination' => '/two'
        ]);

        RedirectRule::create([
            'origin' => '/two',
            'destination' => '/three'
        ]);

        RedirectRule::create([
            'origin' => '/three',
            'destination' => '/four'
        ]);

        RedirectRule::deleteChainedRecursively('/four');

        $this->assertEmpty(RedirectRule::all());

        RedirectRule::truncate();
    }

    public function test_recursive_rule_delete_will_raise_exception_when_multiple_record_for_same_destination_exists()
    {
        $this->expectExceptionMessage('There is multiple redirections with the same destination');

        RedirectRule::create([
            'origin' => '/one',
            'destination' => '/ten'
        ]);

        RedirectRule::create([
            'origin' => '/two',
            'destination' => '/ten'
        ]);

        RedirectRule::deleteChainedRecursively('/ten');

        RedirectRule::truncate();
    }

    public function test_origin_and_destination_are_converted_to_lowercase()
    {
        $origin = 'ONE/TWO/THREE';
        $destination = 'FOUR/FIVE/SEVEN';

        $redirectRule = RedirectRule::create([
            'origin' => $origin,
            'destination' => $destination
        ]);


        $redirectRule = RedirectRule::find($redirectRule->id);

        $this->assertEquals(mb_strtolower($origin), $redirectRule->origin);
        $this->assertEquals(mb_strtolower($destination), $redirectRule->destination);

        $redirectRule->delete();
    }

    public function test_origin_and_destination_do_not_starts_or_ends_with_slash()
    {
        $origin = '/one/two/';
        $destination = '/three/four/';

        $redirectRule = RedirectRule::create([
            'origin' => $origin,
            'destination' => $destination
        ]);

        $redirectRule = RedirectRule::find($redirectRule->id);

        $this->assertFalse(starts_with($redirectRule->origin, '/'));
        $this->assertFalse(ends_with($redirectRule->destination, '/'));

        $redirectRule->delete();
    }
}