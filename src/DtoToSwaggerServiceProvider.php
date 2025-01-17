<?php

declare(strict_types=1);

namespace Kr0lik\DtoToSwagger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Kr0lik\DtoToSwagger\Command\SwaggerGenerator;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\DescriptionDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\HeaderParameterDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\PathParameterDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\QueryParameterDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\RequestDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\ResponseDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\SecurityDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\Describers\TagDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriber;
use Kr0lik\DtoToSwagger\OperationDescriber\OperationDescriberInterface;
use Kr0lik\DtoToSwagger\Processor\RouteProcessor;
use Kr0lik\DtoToSwagger\Processor\RoutingProcessor;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\ArrayDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\BooleanDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\CompoundPropertyDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\DateTimeDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\EnumDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\FloatDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\IntegerDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\NullableDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\ObjectDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\Describers\StringDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriber;
use Kr0lik\DtoToSwagger\PropertyTypeDescriber\PropertyTypeDescriberInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\DocTypePreparerInterface;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\ArrayDocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\CompoundDocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\FloatDocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\IntegerDocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\ObjectDocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\DocTypePreparer\Preparers\StringDocTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\PhpDocReader;
use Kr0lik\DtoToSwagger\ReflectionPreparer\ReflectionPreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers\ArrayRefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers\CompoundRefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers\FloatRefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers\IntegerRefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers\ObjectRefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\Preparers\StringRefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparer;
use Kr0lik\DtoToSwagger\ReflectionPreparer\RefTypePreparer\RefTypePreparerInterface;
use Kr0lik\DtoToSwagger\Register\OpenApiRegister;
use phpDocumentor\Reflection\DocBlockFactory;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class DtoToSwaggerServiceProvider extends ServiceProvider
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'swagger');

        $this->registerOpenApiRegister();
        $this->registerPropertyExtractor();
        $this->registerReflectionTypePreparer();
        $this->registerDocTypePreparer();
        $this->registerPhpDocReader();
        $this->registerReflectionPreparer();
        $this->registerPropertyTypeDescriber();
        $this->registerOperationDescriber();
        $this->registerRoutingProcessor();
        $this->registerCommand();
    }

    public function boot(): void
    {
        if (function_exists('config_path')) {
            $publishPath = config_path('swagger.php');
        } else {
            $publishPath = base_path('config/swagger.php');
        }

        $this->publishes([$this->configPath() => $publishPath], 'config/swagger');
    }

    private function configPath(): string
    {
        return __DIR__.'/../config/swagger.php';
    }

    private function registerOpenApiRegister(): void
    {
        $this->app->when(OpenApiRegister::class)
            ->needs('$openApiConfig')
            ->give(config('swagger.openApi'))
        ;

        $this->app->singleton(OpenApiRegister::class, OpenApiRegister::class);
    }

    private function registerPropertyExtractor(): void
    {
        $this->app->singleton(PropertyInfoExtractor::class, static function (): PropertyInfoExtractor {
            $phpDocExtractor = new PhpDocExtractor();
            $reflectionExtractor = new ReflectionExtractor();

            return new PropertyInfoExtractor(
                [$reflectionExtractor],
                [$phpDocExtractor, $reflectionExtractor],
                [$phpDocExtractor],
                [$reflectionExtractor],
                [$reflectionExtractor]
            );
        });
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function registerReflectionTypePreparer(): void
    {
        $this->app->bind(RefTypePreparerInterface::class, static function (Application $app): array {
            return [
                StringRefTypePreparer::class,
                IntegerRefTypePreparer::class,
                FloatRefTypePreparer::class,
                ArrayRefTypePreparer::class,
                ObjectRefTypePreparer::class,
                CompoundRefTypePreparer::class,
            ];
        });

        $this->app->singleton(RefTypePreparer::class, RefTypePreparer::class);

        /** @var RefTypePreparer $refTypePreparer */
        $refTypePreparer = $this->app->get(RefTypePreparer::class);
        /** @var class-string<RefTypePreparerInterface>[] $refTypePreparerInterfaceClasses */
        $refTypePreparerInterfaceClasses = $this->app->get(RefTypePreparerInterface::class);

        foreach ($refTypePreparerInterfaceClasses as $preparer) {
            $refTypePreparer->addPreparer($this->app->make($preparer));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function registerDocTypePreparer(): void
    {
        $this->app->bind(DocTypePreparerInterface::class, static function (Application $app): array {
            return [
                StringDocTypePreparer::class,
                IntegerDocTypePreparer::class,
                FloatDocTypePreparer::class,
                ArrayDocTypePreparer::class,
                ObjectDocTypePreparer::class,
                CompoundDocTypePreparer::class,
            ];
        });

        $this->app->singleton(DocTypePreparer::class, DocTypePreparer::class);

        /** @var DocTypePreparer $docTypePreparer */
        $docTypePreparer = $this->app->get(DocTypePreparer::class);
        /** @var class-string<DocTypePreparerInterface>[] $docTypePreparerClasses */
        $docTypePreparerClasses = $this->app->get(DocTypePreparerInterface::class);

        foreach ($docTypePreparerClasses as $preparer) {
            $docTypePreparer->addPreparer($this->app->make($preparer));
        }
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerPhpDocReader(): void
    {
        $this->app->singleton(PhpDocReader::class, static function (Application $app): PhpDocReader {
            /** @var DocTypePreparer $docTypePreparer */
            $docTypePreparer = $app->make(DocTypePreparer::class);
            /** @var DocBlockFactory $docBlockFactory */
            $docBlockFactory = DocBlockFactory::createInstance();

            return new PhpDocReader(
                $docTypePreparer,
                $docBlockFactory
            );
        });
    }

    private function registerReflectionPreparer(): void
    {
        $this->app->singleton(ReflectionPreparer::class, ReflectionPreparer::class);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function registerOperationDescriber(): void
    {
        $this->app->when(TagDescriber::class)
            ->needs('$tagFromControllerName')
            ->give(config('swagger.tagFromControllerName', false))
        ;
        $this->app->when(TagDescriber::class)
            ->needs('$tagFromActionFolder')
            ->give(config('swagger.tagFromActionFolder', false))
        ;

        $this->app->when(RequestDescriber::class)
            ->needs('$requestErrorResponseSchemas')
            ->give(config('swagger.requestErrorResponseSchemas', []))
        ;
        $this->app->when(RequestDescriber::class)
            ->needs('$fileUploadType')
            ->give(config('swagger.fileUploadType'))
        ;

        $this->app->when(ResponseDescriber::class)
            ->needs('$defaultErrorResponseSchemas')
            ->give(config('swagger.defaultErrorResponseSchemas', []))
        ;

        $this->app->bind(OperationDescriberInterface::class, static function (): array {
            return [
                DescriptionDescriber::class,
                TagDescriber::class,
                SecurityDescriber::class,
                PathParameterDescriber::class,
                QueryParameterDescriber::class,
                HeaderParameterDescriber::class,
                RequestDescriber::class,
                ResponseDescriber::class,
            ];
        });

        $this->app->singleton(OperationDescriber::class, OperationDescriber::class);

        /** @var OperationDescriber $operationDescriber */
        $operationDescriber = $this->app->get(OperationDescriber::class);
        /** @var class-string<OperationDescriberInterface>[] $operationDescriberClasses */
        $operationDescriberClasses = $this->app->get(OperationDescriberInterface::class);

        foreach ($operationDescriberClasses as $describer) {
            $operationDescriber->addOperationDescriber($this->app->make($describer));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function registerPropertyTypeDescriber(): void
    {
        $this->app->bind(PropertyTypeDescriberInterface::class, static function (): array {
            return [
                StringDescriber::class,
                IntegerDescriber::class,
                BooleanDescriber::class,
                FloatDescriber::class,
                DateTimeDescriber::class,
                EnumDescriber::class,
                NullableDescriber::class,
                ArrayDescriber::class,
                CompoundPropertyDescriber::class,
                ObjectDescriber::class,
            ];
        });

        $this->app->singleton(PropertyTypeDescriber::class, PropertyTypeDescriber::class);

        /** @var PropertyTypeDescriber $propertyDescriber */
        $propertyDescriber = $this->app->get(PropertyTypeDescriber::class);
        /** @var class-string<PropertyTypeDescriberInterface>[] $propertyDescriberClasses */
        $propertyDescriberClasses = $this->app->get(PropertyTypeDescriberInterface::class);

        foreach ($propertyDescriberClasses as $describer) {
            $propertyDescriber->addPropertyDescriber($this->app->make($describer));
        }
    }

    private function registerRoutingProcessor(): void
    {
        $this->app->when(RouteProcessor::class)
            ->needs('$middlewaresToAuth')
            ->give(config('swagger.middlewaresToAuth', []))
        ;

        $this->app->when(RouteProcessor::class)
            ->needs('$tagFromMiddlewares')
            ->give(config('swagger.tagFromMiddlewares', []))
        ;

        $this->app->when(RoutingProcessor::class)
            ->needs('$includeMiddlewares')
            ->give(config('swagger.includeMiddlewares', []))
        ;
        $this->app->when(RoutingProcessor::class)
            ->needs('$includePatterns')
            ->give(config('swagger.includePatterns', []))
        ;

        $this->app->when(RoutingProcessor::class)
            ->needs('$excludeMiddlewares')
            ->give(config('swagger.excludeMiddlewares', []))
        ;
        $this->app->when(RoutingProcessor::class)
            ->needs('$excludePatterns')
            ->give(config('swagger.excludePatterns', []))
        ;

        $this->app->singleton(RouteProcessor::class, RouteProcessor::class);
        $this->app->singleton(RoutingProcessor::class, RoutingProcessor::class);
    }

    private function registerCommand(): void
    {
        $this->app->when(SwaggerGenerator::class)
            ->needs('$savePath')
            ->give(config('swagger.savePath'))
        ;

        $this->commands(SwaggerGenerator::class);
    }
}
