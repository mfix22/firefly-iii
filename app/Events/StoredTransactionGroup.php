<?php

/**
 * StoredTransactionGroup.php
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

declare(strict_types=1);

namespace FireflyIII\Events;

use FireflyIII\Models\TransactionGroup;
use Illuminate\Queue\SerializesModels;

/**
 * Class StoredTransactionGroup.
 *
 * @codeCoverageIgnore
 */
class StoredTransactionGroup extends Event
{
    use SerializesModels;

    /** @var TransactionGroup The group that was stored. */
    public $transactionGroup;

    public $applyRules;

    /**
     * Create a new event instance.
     *
     * @param TransactionGroup $transactionGroup
     * @param bool $applyRules
     */
    public function __construct(TransactionGroup $transactionGroup, bool $applyRules = true)
    {
        $this->transactionGroup = $transactionGroup;
        $this->applyRules       = $applyRules;
    }
}
