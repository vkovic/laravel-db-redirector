<?php

namespace Vkovic\LaravelDbRedirector\Test\Integration;

use Vkovic\LaravelDbRedirector\Models\RedirectRule;
use Vkovic\LaravelDbRedirector\Test\TestCase;

class RoutingViaDbRedirectorTest extends TestCase
{
    public function test_simple_redirect()
    {
        $redirectRule = RedirectRule::create([
            'origin' => '/one',
            'destination' => '/two'
        ]);

        $this->get('/one')
            ->assertRedirect('/two')
            ->assertStatus(301);

        $redirectRule->delete();
    }

    public function test_router_misses_non_matching_similar_rule()
    {
        $rules = [
            'one/two/{c}' => 'a',
            '{a}/two/{c}' => 'b',
            '{a}/two/{c?}' => 'c',
        ];

        foreach ($rules as $origin => $destination) {
            RedirectRule::create([
                'origin' => $origin,
                'destination' => $destination
            ]);
        }

        $this->get('/one/two_/X')->assertStatus(404);
        $this->get('/X/tw_o/Y')->assertStatus(404);
        $this->get('/X/tw_o/Y/z')->assertStatus(404);

        RedirectRule::truncate();
    }

    public function test_non_default_redirect_status_code()
    {
        $redirectRule = RedirectRule::create([
            'origin' => '/one',
            'destination' => '/two',
            'status_code' => 302
        ]);

        $this->get('/one')
            ->assertRedirect('/two')
            ->assertStatus(302);

        $redirectRule->delete();
    }

    public function test_route_can_use_single_named_param()
    {
        $redirectRule = RedirectRule::create([
            'origin' => '/one/{a}/two',
            'destination' => '/three/{a}'
        ]);

        $this->get('/one/X/two')
            ->assertRedirect('/three/X');

        $redirectRule->delete();
    }

    public function test_route_can_use_multiple_named_params()
    {
        $redirectRule = RedirectRule::create([
            'origin' => '/one/{a}/{b}/two/{c}',
            'destination' => '/{c}/{b}/{a}/three'
        ]);

        $this->get('/one/X/Y/two/Z')
            ->assertRedirect('/Z/Y/X/three');

        $redirectRule->delete();
    }

    public function test_route_can_use_multiple_named_params_in_one_segment()
    {
        $redirectRule = RedirectRule::create([
            'origin' => '/one/two/{a}-{b}/{c}',
            'destination' => '/three/{a}/four/{b}/{a}-{c}'
        ]);

        $this->get('/one/two/X-Y/Z')
            ->assertRedirect('/three/X/four/Y/X-Z');

        $redirectRule->delete();
    }

    public function test_route_can_use_optional_named_parameters()
    {
        $redirectRule = RedirectRule::create([
            'origin' => '/one/{a?}/{b?}',
            'destination' => '/two/{a}/{b}'
        ]);

        $this->get('/one/X')->assertRedirect('/two/X');
        $this->get('/one/X/Y')->assertRedirect('/two/X/Y');
        $this->get('/one')->assertRedirect('/two');

        $redirectRule->delete();
    }

    public function test_router_can_perform_chained_redirects()
    {
        RedirectRule::create([
            'origin' => '/one',
            'destination' => '/two'
        ]);

        RedirectRule::create([
            'origin' => '/two',
            'destination' => '/three'
        ]);

        // TODO.IMPROVE
        // This is actually working but i'm not sure how to test
        // chained redirects. For now we'll test one by one.

        $this->get('/one')->assertRedirect('/two');
        $this->get('/two')->assertRedirect('/three');

        RedirectRule::truncate();
    }

    public function test_router_matches_order_for_rules_with_named_params()
    {
        // Rules in this array are ordered like the logic
        // in router works - we'll shuffle them later, just in case
        $rules = [
            // 3 segments
            'one/two/{c}' => 'a',
            '{a}/two/{c}' => 'b',
            // 4 segments
            'one/two/{d}/{e}' => 'c',
            'one/{b}/three/{d}' => 'd',
            'one/{b}/{c}/{d}' => 'e',
            // 6 segments
            'one/{b}/three/{d}/five/{f}' => 'f',
            'one/two/{c}/{d}/{e}/{f}' => 'g',
            'one/{b}/three/{d}/{e}/{f}' => 'h',
            // 7 segments
            'one/two/three/four/five/six/{g}' => 'i',
            '{a}/two/three/four/five/six/{g}' => 'j',
        ];

        // Shuffle routes to avoid coincidentally
        // matching (by order in database)
        uksort($rules, function () {
            return rand() > rand();
        });

        foreach ($rules as $origin => $destination) {
            RedirectRule::create([
                'origin' => $origin,
                'destination' => $destination
            ]);
        }

        // 3 segments
        $this->get('one/two/X')->assertRedirect('/a');
        $this->get('X/two/Y')->assertRedirect('/b');

        // 4 segments
        $this->get('one/two/X/Y')->assertRedirect('/c');
        $this->get('one/X/three/Y')->assertRedirect('/d');
        $this->get('one/X/Y/Z')->assertRedirect('/e');

        // 6 segments
        $this->get('one/X/three/Y/five/Z')->assertRedirect('/f');
        $this->get('one/two/X/Y/Z/K')->assertRedirect('/g');
        $this->get('one/X/three/Y/Z/K')->assertRedirect('/h');

        // 7 segments
        $this->get('one/two/three/four/five/six/X')->assertRedirect('/i');
        $this->get('X/two/three/four/five/six/Y')->assertRedirect('/j');

        RedirectRule::truncate();
    }

    public function test_router_matches_order_for_rules_with_optional_named_params()
    {
        // Rules in this array are ordered like the logic
        // in router works - we'll shuffle them later, just in case
        $rules = [
            // 3 segments
            'one/two/{c?}' => 'a',
            '{a}/two/{c?}' => 'b',
            // 6 segments
            'one/{b}/three/{d?}/five/{f?}' => 'c',
            'one/two/{c}/{d?}/{e?}/{f?}' => 'd',
            'one/{b}/three/{d}/{e?}/{f?}' => 'e',
        ];

        // Shuffle routes to avoid accidentally
        // matching (by order in database)
        uksort($rules, function () {
            return rand() > rand();
        });

        foreach ($rules as $origin => $destination) {
            RedirectRule::create([
                'origin' => $origin,
                'destination' => $destination
            ]);
        }

        // 3 segments
        $this->get('one/two')->assertRedirect('/a');
        $this->get('one/two/X')->assertRedirect('/a');

        $this->get('X/two')->assertRedirect('/b');
        $this->get('X/two/Y')->assertRedirect('/b');

        // 6 segments
        $this->get('one/X/three/Y/five')->assertRedirect('/c');
        $this->get('one/X/three/Y/five/X')->assertRedirect('/c');

        $this->get('one/two/X/Y')->assertRedirect('/d');
        $this->get('one/two/X/Y/X/K')->assertRedirect('/d');

        $this->get('one/X/three/Y/Z')->assertRedirect('/e');
        $this->get('one/X/three/Y/Z/K')->assertRedirect('/e');

        RedirectRule::truncate();
    }
}