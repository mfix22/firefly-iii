<?php
/**
 * BinderTest.php
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


use Carbon\Carbon;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Http\Middleware\Binder;
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;


/**
 * Class BinderTest
 * Per object: works, not existing, not logged in + existing
 */
class BinderTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Account
     */
    public function testAccount(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{account}', function () {
            return 'OK';
        }
        );
        Log::info(sprintf('Now in %s.', get_class($this)));

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList
     */
    public function testAccountList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList
     */
    public function testAccountListAllAssets(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/allAssetAccounts');
        $count    = $this->user()->accounts()
                         ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                         ->where('account_types.type', AccountType::ASSET)
                         ->orderBy('accounts.name', 'ASC')
                         ->count();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee(sprintf('count: %d', $count));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList
     */
    public function testAccountListEmpty(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', static function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList
     */
    public function testAccountListInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/0,1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\AccountList
     */
    public function testAccountListNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{accountList}', function (Collection $accounts) {
            return 'count: ' . $accounts->count();
        }
        );
        $response = $this->get('/_test/binder/1,2');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Account
     */
    public function testAccountNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{account}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Account
     */
    public function testAccountNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{account}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Attachment
     */
    public function testAttachment(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{attachment}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Attachment
     */
    public function testAttachmentNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{attachment}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Attachment
     */
    public function testAttachmentNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{attachment}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Bill
     */
    public function testBill(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{bill}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Bill
     */
    public function testBillNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{bill}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Bill
     */
    public function testBillNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{bill}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Budget
     */
    public function testBudget(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budget}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\BudgetLimit
     */
    public function testBudgetLimit(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetLimit}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\BudgetLimit
     */
    public function testBudgetLimitNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetLimit}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\BudgetLimit
     */
    public function testBudgetLimitNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetLimit}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\BudgetList
     */
    public function testBudgetList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetList}', function (Collection $budgets) {
            return 'count: ' . $budgets->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/0,1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 3');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\BudgetList
     */
    public function testBudgetListInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetList}', function (Collection $budgets) {
            return 'count: ' . $budgets->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\BudgetList
     */
    public function testBudgetListEmpty(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budgetList}', function (Collection $budgets) {
            return 'count: ' . $budgets->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CLIToken
     */
    public function testCLIToken(): void
    {
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('all')->andReturn(new Collection([$this->user()]))->atLeast()->once();

        $token       = new Preference;
        $token->data = 'token';

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token', null])->atLeast()->once()->andReturn($token);

        Route::middleware(Binder::class)->any(
            '/_test/binder/{cliToken}', static function (string $token) {
            return sprintf('token: %s', $token);
        }
        );

        $response = $this->get('/_test/binder/token');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('token');

    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CLIToken
     */
    public function testCLITokenNotFound(): void
    {
        $repos = $this->mock(UserRepositoryInterface::class);
        $repos->shouldReceive('all')->andReturn(new Collection([$this->user()]))->atLeast()->once();

        $token       = new Preference;
        $token->data = 'token';

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token', null])->atLeast()->once()->andReturn($token);

        Route::middleware(Binder::class)->any(
            '/_test/binder/{cliToken}', static function (string $token) {
            return sprintf('token: %s', $token);
        }
        );

        $response = $this->get('/_test/binder/tokenX');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\ConfigurationName
     */
    public function testConfigName(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{configName}', static function (string $name) {
            return sprintf('configName: %s', $name);
        }
        );

        $response = $this->get('/_test/binder/is_demo_site');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('is_demo_site');
    }


    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\ConfigurationName
     */
    public function testConfigNameNotFOund(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{configName}', static function (string $name) {
            return sprintf('configName: %s', $name);
        }
        );

        $response = $this->get('/_test/binder/is_demoX_site');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Normal user can access file routine
     *
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\ImportProvider
     */
    public function testImportProvider(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $this->mock(PrerequisitesInterface::class);

        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        Route::middleware(Binder::class)->any(
            '/_test/binder/{import_provider}', static function (string $name) {
            return sprintf('import_provider: %s', $name);
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/file');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('file');
    }

    /**
     * Normal user cannot access fake import routine.
     *
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\ImportProvider
     */
    public function testImportProviderFake(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $this->mock(PrerequisitesInterface::class);

        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        Route::middleware(Binder::class)->any(
            '/_test/binder/{import_provider}', static function (string $name) {
            return sprintf('import_provider: %s', $name);
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/fake');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Nobody can access "bad" import routine.
     *
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\ImportProvider
     */
    public function testImportProviderBad(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $this->mock(PrerequisitesInterface::class);

        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        Route::middleware(Binder::class)->any(
            '/_test/binder/{import_provider}', static function (string $name) {
            return sprintf('import_provider: %s', $name);
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/bad');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Demo user cannot access file import routine.
     *
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\ImportProvider
     */
    public function testImportProviderDemoFile(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $this->mock(PrerequisitesInterface::class);

        $repository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(true)->atLeast()->once();

        Route::middleware(Binder::class)->any(
            '/_test/binder/{import_provider}', static function (string $name) {
            return sprintf('import_provider: %s', $name);
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/file');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\ImportProvider
     */
    public function testImportProviderNotLoggedIn(): void
    {
        $this->mock(UserRepositoryInterface::class);
        $this->mock(PrerequisitesInterface::class);

        Route::middleware(Binder::class)->any(
            '/_test/binder/{import_provider}', static function (string $name) {
            return sprintf('import_provider: %s', $name);
        }
        );

        $response = $this->get('/_test/binder/file');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }


    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Budget
     */
    public function testBudgetNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budget}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Budget
     */
    public function testBudgetNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{budget}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Category
     */
    public function testCategory(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{category}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CategoryList
     */
    public function testCategoryList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{categoryList}', function (Collection $categories) {
            return 'count: ' . $categories->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/0,1,2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 3');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CategoryList
     */
    public function testCategoryListInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{categoryList}', function (Collection $categories) {
            return 'count: ' . $categories->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Category
     */
    public function testCategoryNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{category}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Category
     */
    public function testCategoryNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{category}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CurrencyCode
     */
    public function testCurrencyCode(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{fromCurrencyCode}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/USD');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CurrencyCode
     */
    public function testCurrencyCodeNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{fromCurrencyCode}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/ABC');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\CurrencyCode
     */
    public function testCurrencyCodeNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{fromCurrencyCode}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/EUR');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDate(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );

        // mock fiscal helper:

        $helper = $this->mock(FiscalHelperInterface::class);
        $date   = new Carbon;
        $helper->shouldReceive('endOfFiscalYear')->andReturn($date)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/20170917');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: 2017-09-17');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDateCurrentMonthEnd(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            Log::debug(sprintf('Received in function: "%s"', $date->format('Y-m-d')));

            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $date = new Carbon;
        $date->endOfMonth();
        $testDate = clone $date;

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($date)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();
        $this->be($this->user());
        $response = $this->get('/_test/binder/currentMonthEnd');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: ' . $testDate->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDateCurrentMonthStart(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $date = new Carbon;
        $date->startOfMonth();
        $testDate = clone $date;

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($date)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/currentMonthStart');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: ' . $testDate->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDateCurrentYearEnd(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $date = new Carbon;
        $date->endOfYear();
        $testDate = clone $date;

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($date)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/currentYearEnd');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: ' . $testDate->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDateCurrentYearStart(): void
    {
        $date = new Carbon;
        $date->startOfYear();
        $testDate = clone $date;

        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($date)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/currentYearStart');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: ' . $testDate->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDateFiscalYearEnd(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );

        $date = new Carbon;
        $date->endOfYear();
        $testDate = clone $date;

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($testDate)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/currentFiscalYearEnd');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: ' . $testDate->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDateFiscalYearStart(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );

        $date = new Carbon;
        $date->startOfYear();
        $testDate = clone $date;

        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($testDate)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/currentFiscalYearStart');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('date: ' . $testDate->format('Y-m-d'));
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\Date
     */
    public function testDateInvalid(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{date}', function (Carbon $date) {
            return 'date: ' . $date->format('Y-m-d');
        }
        );
        $date = new Carbon;
        // mock fiscal helper:
        $helper = $this->mock(FiscalHelperInterface::class);
        $helper->shouldReceive('endOfFiscalYear')->andReturn($date)->atLeast()->once();
        $helper->shouldReceive('startOfFiscalYear')->andReturn($date)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get('/_test/binder/fakedate');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ImportJob
     */
    public function testImportJob(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{importJob}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/testImport');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ImportJob
     */
    public function testImportJobNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{importJob}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\ImportJob
     */
    public function testImportJobNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{importJob}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/testImport');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\JournalList
     */
    public function testJournalList(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalList}', static function (array $journals) {
            return 'count: ' . count($journals);
        }
        );
        $this->be($this->user());
        $withdrawal = $this->getRandomWithdrawal();
        $deposit    = $this->getRandomDeposit();
        $response   = $this->get(sprintf('/_test/binder/%d,%d', $withdrawal->id, $deposit->id));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\JournalList
     */
    public function testJournalListEmpty(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalList}', static function (array $journals) {
            return 'count: ' . count($journals);
        });

        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Not logged in.
     *
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\JournalList
     */
    public function testJournalListNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalList}', static function (array $journals) {
            return 'count: ' . count($journals);
        });

        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\LinkType
     */
    public function testLinkType(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{linkType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\LinkType
     */
    public function testLinkTypeNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{linkType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\LinkType
     */
    public function testLinkTypeNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{linkType}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\PiggyBank
     */
    public function testPiggyBank(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{piggyBank}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }


    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Preference
     */
    public function testPreference(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{preference}', static function (?Preference $preference) {
            return $preference->name ?? 'unknown';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/frontPageAccounts');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('frontPageAccounts');
    }


    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Recurrence
     */
    public function testRecurrence(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{recurrence}', static function (?Recurrence $recurrence) {
            return $recurrence->description ?? 'unknown';
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Recurrence
     */
    public function testRecurrenceNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{recurrence}', static function (?Recurrence $recurrence) {
            return $recurrence->description ?? 'unknown';
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionGroup
     */
    public function testTransactionGroup(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionGroup}', static function (?TransactionGroup $transactionGroup) {
            return $transactionGroup->title ?? 'unknown';
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionGroup
     */
    public function testTransactionGroupNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionGroup}', static function (?TransactionGroup $transactionGroup) {
            return $transactionGroup->title ?? 'unknown';
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }


    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\AvailableBudget
     */
    public function testAvailableBudget(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{availableBudget}', static function (?AvailableBudget $availableBudget) {
            return $availableBudget->id ?? 0;
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\AvailableBudget
     */
    public function testAvailableBudgetNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{availableBudget}', static function (?AvailableBudget $availableBudget) {
            return $availableBudget->id ?? 0;
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/-1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }


    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Preference
     */
    public function testPreferenceNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{preference}', static function (?Preference $preference) {
            return $preference->name ?? 'unknown';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/frontPageAccountsX');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\PiggyBank
     */
    public function testPiggyBankNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{piggyBank}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\PiggyBank
     */
    public function testPiggyBankNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{piggyBank}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Rule
     */
    public function testRule(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{rule}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\RuleGroup
     */
    public function testRuleGroup(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{ruleGroup}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\RuleGroup
     */
    public function testRuleGroupNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{ruleGroup}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\RuleGroup
     */
    public function testRuleGroupNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{ruleGroup}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Rule
     */
    public function testRuleNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{rule}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Rule
     */
    public function testRuleNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{rule}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournal
     */
    public function testTJ(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tj}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournal
     */
    public function testTJNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tj}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournal
     */
    public function testTJNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tj}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Tag
     */
    public function testTag(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tag}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagList
     */
    public function testTagList(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tagRepos->shouldReceive('setUser');
        $tags = $this->user()->tags()->whereIn('id', [1, 2])->get(['tags.*']);
        $tagRepos->shouldReceive('get')->once()->andReturn($tags);

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagList}', function (Collection $tags) {
            return 'count: ' . $tags->count();
        }
        );

        $names = implode(',', $tags->pluck('tag')->toArray());


        $this->be($this->user());
        $response = $this->get('/_test/binder/' . $names);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagList
     */
    public function testTagListWithId(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tagRepos->shouldReceive('setUser');
        $tags = $this->user()->tags()->whereIn('id', [1, 2, 3])->get(['tags.*']);
        $tagRepos->shouldReceive('get')->once()->andReturn($tags);

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagList}', function (Collection $tags) {
            return 'count: ' . $tags->count();
        }
        );
        $first  = $tags->get(0);
        $second = $tags->get(1);


        $this->be($this->user());
        $response = $this->get(sprintf('/_test/binder/%s,%d,bleep', $first->tag, $second->id));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $response->assertSee('count: 2');
    }

    /**
     * TODO there is a random element in this test that breaks the middleware.
     *
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagOrId
     */
    public function testTagOrIdByTag(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tag      = $this->getRandomTag();

        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('findByTag')->withArgs([$tag->tag])->andReturn($tag)->atLeast()->once();

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagOrId}', static function (?Tag $tag) {
            if ($tag) {
                return $tag->tag;
            }

            return 'unfound';
        }
        );

        $this->be($this->user());
        $response = $this->get(sprintf('/_test/binder/%s', $tag->tag));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $response->assertSee($tag->tag);
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagOrId
     */
    public function testTagOrIdById(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tag      = $this->getRandomTag();

        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('findByTag')->withArgs([(string)$tag->id])->andReturnNull()->atLeast()->once();
        $tagRepos->shouldReceive('findNull')->withArgs([$tag->id])->andReturn($tag)->atLeast()->once();

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagOrId}', static function (?Tag $tag) {
            if ($tag) {
                return $tag->tag;
            }

            return 'unfound';
        }
        );

        $this->be($this->user());
        $response = $this->get(sprintf('/_test/binder/%d', $tag->id));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $response->assertSee($tag->tag);
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagOrId
     */
    public function testTagOrIdBothNull(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tag      = $this->getRandomTag();

        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('findByTag')->withArgs([(string)$tag->id])->andReturnNull()->atLeast()->once();
        $tagRepos->shouldReceive('findNull')->withArgs([$tag->id])->andReturnNull()->atLeast()->once();

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagOrId}', static function (?Tag $tag) {
            if ($tag) {
                return $tag->tag;
            }

            return 'unfound';
        }
        );

        $this->be($this->user());
        $response = $this->get(sprintf('/_test/binder/%d', $tag->id));
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagOrId
     */
    public function testTagOrIdNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagOrId}', static function (?Tag $tag) {
            if ($tag) {
                return $tag->tag;
            }

            return 'unfound';
        }
        );

        $response = $this->get(sprintf('/_test/binder/%d', 4));
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Support\Binder\TagList
     */
    public function testTagListEmpty(): void
    {
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->once()->andReturn(new Collection());

        Route::middleware(Binder::class)->any(
            '/_test/binder/{tagList}', function (Collection $tags) {
            return 'count: ' . $tags->count();
        }
        );
        $this->be($this->user());
        $response = $this->get('/_test/binder/noblaexista');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Tag
     */
    public function testTagNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tag}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\Tag
     */
    public function testTagNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{tag}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionCurrency
     */
    public function testTransactionCurrency(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{currency}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionCurrency
     */
    public function testTransactionCurrencyNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{currency}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionCurrency
     */
    public function testTransactionCurrencyNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{currency}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournalLink
     */
    public function testTransactionJournalLink(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalLink}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournalLink
     */
    public function testTransactionJournalLinkNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalLink}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionJournalLink
     */
    public function testTransactionJournalLinkNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{journalLink}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/1');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionType
     */
    public function testTransactionType(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/withdrawal');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionType
     */
    public function testTransactionTypeNotFound(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionType}', function () {
            return 'OK';
        }
        );

        $this->be($this->user());
        $response = $this->get('/_test/binder/unknown');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\Binder
     * @covers \FireflyIII\Models\TransactionType
     */
    public function testTransactionTypeNotLoggedIn(): void
    {
        Route::middleware(Binder::class)->any(
            '/_test/binder/{transactionType}', function () {
            return 'OK';
        }
        );

        $response = $this->get('/_test/binder/withdrawal');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
