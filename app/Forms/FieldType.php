<?php

namespace App\Forms;

/**
 * The kinds of field a registration form can contain.
 */
enum FieldType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case YesNo = 'yes_no';
    /** A heading and note that splits the form into sections. It collects nothing. */
    case Section = 'section';

    public function label(): string
    {
        return match ($this) {
            self::ShortText => 'Short answer',
            self::LongText => 'Long answer',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Select => 'Dropdown',
            self::YesNo => 'Yes or no',
            self::Section => 'Section heading',
        };
    }

    public function collectsAnswer(): bool
    {
        return $this !== self::Section;
    }

    public function hasOptions(): bool
    {
        return $this === self::Select;
    }
}
