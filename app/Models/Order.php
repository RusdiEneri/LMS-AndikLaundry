<?php

namespace App\Models;

/**
 * Order is an alias subclass of Transaction.
 * Allows Order and Transaction to be used interchangeably across the codebase.
 */
class Order extends Transaction
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';
}
