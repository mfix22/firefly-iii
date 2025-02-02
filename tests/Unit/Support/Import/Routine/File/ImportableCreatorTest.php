<?php
/**
 * ImportableCreatorTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Support\Import\Routine\File;


use FireflyIII\Support\Import\Placeholder\ColumnValue;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
use FireflyIII\Support\Import\Routine\File\ImportableCreator;
use Log;
use Tests\TestCase;

/**
 * Class ImportableCreatorTest
 */
class ImportableCreatorTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\Routine\File\ImportableCreator
     */
    public function testConvertSets(): void
    {
        $columnValue = new ColumnValue();
        $columnValue->setOriginalRole('account-name');
        $columnValue->setRole('account-id');
        $columnValue->setValue('Checking Account');
        $columnValue->setMappedValue(1);

        $input = [
            [
                $columnValue,
            ],
        ];


        $creator = new ImportableCreator;
        $result  = $creator->convertSets($input);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(ImportTransaction::class, $result[0]);
        $this->assertEquals(1, $result[0]->accountId);

    }

}
