<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The edge a data table column aligns to. Logical, so a start column sits on
 * the left in English and on the right in Arabic.
 */
enum DataTableAlign: string
{
    case Start = 'start';
    case End = 'end';
}
