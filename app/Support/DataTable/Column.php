<?php

declare(strict_types=1);

namespace App\Support\DataTable;

use App\Constants\DataTable;
use App\Constants\DataTableAlign;
use App\Constants\DataTableColumnType;

/**
 * One column of an admin data table, declared by the screen: its key (also its
 * sort key when `sortable`), heading, type, width (a CSS length, null to share
 * what is left), alignment (the type's own unless given), whether it shows
 * before the user chooses (`visible`) and its priority on small screens
 * (DataTable::PRIORITY_*). The primary column can never be hidden.
 */
final readonly class Column
{
    public DataTableAlign $align;

    public function __construct(
        public string $key,
        public string $label,
        public DataTableColumnType $type = DataTableColumnType::Text,
        public bool $sortable = false,
        public ?string $width = null,
        ?DataTableAlign $align = null,
        public bool $visible = true,
        public int $priority = DataTable::PRIORITY_TABLET,
    ) {
        $this->align = $align ?? $type->align();
    }

    public static function primary(string $key, string $label, bool $sortable = false): self
    {
        return new self($key, $label, DataTableColumnType::Primary, $sortable, null, null, true, DataTable::PRIORITY_ESSENTIAL);
    }

    public static function text(string $key, string $label, bool $sortable = false, ?string $width = null, bool $visible = true, int $priority = DataTable::PRIORITY_DESKTOP): self
    {
        return new self($key, $label, DataTableColumnType::Text, $sortable, $width, null, $visible, $priority);
    }

    public static function status(string $key, string $label, bool $sortable = false, string $width = '9.5rem'): self
    {
        return new self($key, $label, DataTableColumnType::Status, $sortable, $width, null, true, DataTable::PRIORITY_ESSENTIAL);
    }

    public static function relative(string $key, string $label, bool $sortable = true, bool $visible = true, string $width = '8.5rem'): self
    {
        return new self($key, $label, DataTableColumnType::Relative, $sortable, $width, null, $visible, DataTable::PRIORITY_TABLET);
    }

    public static function date(string $key, string $label, bool $sortable = true, bool $visible = true, string $width = '8rem', int $priority = DataTable::PRIORITY_TABLET): self
    {
        return new self($key, $label, DataTableColumnType::Date, $sortable, $width, null, $visible, $priority);
    }

    public static function number(string $key, string $label, bool $sortable = false, string $width = '7rem', bool $visible = true, int $priority = DataTable::PRIORITY_TABLET): self
    {
        return new self($key, $label, DataTableColumnType::Number, $sortable, $width, null, $visible, $priority);
    }

    public static function badges(string $key, string $label, ?string $width = null, bool $visible = true, int $priority = DataTable::PRIORITY_TABLET): self
    {
        return new self($key, $label, DataTableColumnType::Badges, false, $width, null, $visible, $priority);
    }

    public function isPrimary(): bool
    {
        return $this->type === DataTableColumnType::Primary;
    }

    public function hideable(): bool
    {
        return ! $this->isPrimary();
    }
}
