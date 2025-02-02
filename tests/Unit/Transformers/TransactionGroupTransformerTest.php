<?php
/**
 * TransactionGroupTransformerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Transformers;


use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class TransactionGroupTransformerTest
 */
class TransactionGroupTransformerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Transformers\TransactionGroupTransformer
     */
    public function testBasic(): void
    {
        $repository = $this->mock(TransactionGroupRepositoryInterface::class);
        $group      = $this->getRandomWithdrawalGroup();
        $first      = $group->transactionJournals()->first();

        // mock calls
        $repository->shouldReceive('getMetaFields')->withArgs([$first->id, Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getMetaDateFields')->withArgs([$first->id, Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getNoteText')->atLeast()->once()->andReturn('note');
        $repository->shouldReceive('getTags')->atLeast()->once()->andReturn([]);

        $transformer = new TransactionGroupTransformer;
        $result      = $transformer->transformObject($group);

    }

    /**
     * @covers \FireflyIII\Transformers\TransactionGroupTransformer
     */
    public function testArray(): void {
        $repository = $this->mock(TransactionGroupRepositoryInterface::class);
        $group      = $this->getRandomWithdrawalGroupAsArray();

        // mock calls
        $repository->shouldReceive('getMetaFields')->withArgs([Mockery::any(), Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getMetaDateFields')->withArgs([Mockery::any(), Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getNoteText')->atLeast()->once()->andReturn('note');
        $repository->shouldReceive('getTags')->atLeast()->once()->andReturn([]);

        $transformer = new TransactionGroupTransformer;
        $result      = $transformer->transform($group);
    }


    /**
     * @covers \FireflyIII\Transformers\TransactionGroupTransformer
     */
    public function testArrayDeposit(): void {
        $repository = $this->mock(TransactionGroupRepositoryInterface::class);
        $group      = $this->getRandomDepositGroupAsArray();

        // mock calls
        $repository->shouldReceive('getMetaFields')->withArgs([Mockery::any(), Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getMetaDateFields')->withArgs([Mockery::any(), Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getNoteText')->atLeast()->once()->andReturn('note');
        $repository->shouldReceive('getTags')->atLeast()->once()->andReturn([]);

        $transformer = new TransactionGroupTransformer;
        $result      = $transformer->transform($group);
    }

    /**
     * @covers \FireflyIII\Transformers\TransactionGroupTransformer
     */
    public function testDeposit(): void
    {
        $repository = $this->mock(TransactionGroupRepositoryInterface::class);
        $group      = $this->getRandomDepositGroup();
        $first      = $group->transactionJournals()->first();

        // mock calls
        $repository->shouldReceive('getMetaFields')->withArgs([$first->id, Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getMetaDateFields')->withArgs([$first->id, Mockery::any()])->andReturn(new NullArrayObject([]))->atLeast()->once();
        $repository->shouldReceive('getNoteText')->atLeast()->once()->andReturn('note');
        $repository->shouldReceive('getTags')->atLeast()->once()->andReturn([]);

        $transformer = new TransactionGroupTransformer;
        $result      = $transformer->transformObject($group);

    }
}