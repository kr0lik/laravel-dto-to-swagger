<?php

declare(strict_types=1);

namespace Example\Dto\Request;

use Example\Enum\StringEnum;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Kr0lik\DtoToSwagger\Contract\JsonRequestInterface;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Property;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class JsonRequestDto implements JsonRequestInterface
{
    /**
     * @param string[]                      $arrayOfString
     * @param stdClass[]                    $arrayOfObject
     * @param array<int[]>                  $arrayWithSubArrayOfInt
     * @param SubDto[]                      $arrayOfDto
     * @param Collection<array-key, string> $collectionOfString
     */
    public function __construct(
        /** Some Description1 */
        #[Parameter(in: 'query')]
        readonly int $int,
        #[Property(description: 'some description', example: ['string', 'string2'])]
        readonly string $string,
        #[Parameter(in: 'header')]
        readonly float $float,
        readonly DateTimeImmutable $dateTime,
        readonly array $arrayOfString,
        /** Some Description2 */
        readonly array $arrayOfObject,
        readonly array $arrayWithSubArrayOfInt,
        readonly SubDto $subDto,
        readonly array $arrayOfDto,
        readonly object $objectNullable,
        readonly StringEnum $enum,
        readonly UploadedFile $uploadedFile,
        readonly Collection $collectionOfString,
    ) {
    }
}
