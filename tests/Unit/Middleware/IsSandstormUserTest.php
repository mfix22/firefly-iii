<?php
/**
 * IsSandstormUserTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use FireflyIII\Http\Middleware\IsSandStormUser;
use Log;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class IsSandstormUserTest
 */
class IsSandstormUserTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
        Route::middleware(IsSandStormUser::class)->any(
            '/_test/is-sandstorm',static function () {
            return 'OK';
        }
        );
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsSandStormUser
     */
    public function testMiddlewareNotAuthenticated(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-sandstorm');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsSandStormUser
     */
    public function testMiddlewareNotSandStorm(): void
    {
        $this->withoutExceptionHandling();
        $this->be($this->user());
        $response = $this->get('/_test/is-sandstorm');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsSandStormUser
     */
    public function testMiddlewareSandstorm(): void
    {
        putenv('SANDSTORM=1');
        $this->withoutExceptionHandling();
        $this->be($this->user());
        $response = $this->get('/_test/is-sandstorm');

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertSessionHas('warning', (string)trans('firefly.sandstorm_not_available'));
        $response->assertRedirect(route('index'));
        putenv('SANDSTORM=0');
    }
}
