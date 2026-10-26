<?php

declare(strict_types=1);

namespace App\Exceptions\Imports;

use RuntimeException;

final class ImportRejected extends RuntimeException
{
    /**
     * @param  list<string>  $errors  one message per problem, as ImportService::validate() lists them
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(trans_choice('The file was refused: :count problem.|The file was refused: :count problems.', count($errors)));
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
