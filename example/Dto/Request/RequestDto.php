<?php

declare(strict_types=1);

namespace App\Dto\Request;

use App\Enum\StringEnum;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Kr0lik\DtoToSwagger\Attribute\Context;
use Kr0lik\DtoToSwagger\Attribute\Name;
use Kr0lik\DtoToSwagger\Contract\JsonRequestInterface;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Property;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class RequestDto implements JsonRequestInterface
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
        #[Parameter(name: 'X-FLOAT', in: 'header')]
        readonly float $float,
        #[Name('datetime')]
        #[Context(['dateTimePattern' => 'Y-m-d H:i:s'])]
        readonly DateTimeImmutable $dateTimeImmutable,
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
