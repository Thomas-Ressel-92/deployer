<?php
namespace axenox\Deployer\Uxon;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\UxonSchemaInterface;
use exface\Core\Interfaces\Model\MetaObjectInterface;
use exface\Core\Uxon\UxonSchema;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\DataTypes\SortingDirectionsDataType;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\DataTypes\ComparatorDataType;

/**
 * UXON-schema class for Composer auth.json.
 * 
 * @link https://getcomposer.org/doc/articles/authentication-for-private-packages.md
 * 
 * @author Andrej Kabachnik
 *
 */
class ComposerAuthJsonSchema implements UxonSchemaInterface
{
    private $parentSchema = null;
    private $workbench = null;
    
    /**
     *
     * @param WorkbenchInterface $workbench
     */
    public function __construct(WorkbenchInterface $workbench, UxonSchema $parentSchema = null)
    {
        $this->parentSchema = $parentSchema;
        $this->workbench = $workbench;
    }
    
    public static function getSchemaName() : string
    {
        return 'auth.json for Composer';
    }
    
    public function getValidValues(UxonObject $uxon, array $path, string $search = null, string $rootPrototypeClass = null, MetaObjectInterface $rootObject = null): array
    {
        return [];
    }

    public function getParentSchema(): UxonSchemaInterface
    {
        return $this->parentSchema ?? new UxonSchema($this->getWorkbench(), $this);
    }

    public function getPrototypeClass(UxonObject $uxon, array $path, string $rootPrototypeClass = null): string
    {
        return '\\' . __CLASS__;
    }

    public function getPropertiesTemplates(string $prototypeClass): array
    {
        return [
            'http-basic' => '{"example1.org": {"username": "", "password": ""}}',
            'github-oauth' => '{"github.com": "// PLACE TOKEN HERE"}',
            'gitlab-token' => '{"// PLACE DOMAIN HERE": "// PLACE TOKEN HERE"}',
            'gitlab-oauth' => '{"// PLACE DOMAIN HERE": "// PLACE TOKEN HERE"}'
        ];
    }

    public function getPropertyValueRecursive(UxonObject $uxon, array $path, string $propertyName, string $rootValue = '', string $prototypeClass = null)
    {
        return null;
    }

    public function getProperties(string $prototypeClass): array
    {
        return [
            'http-basic',
            'github-oauth',
            'gitlab-token',
            'gitlab-oauth'
        ];
    }

    public function hasParentSchema()
    {
        return $this->parentSchema !== null;
    }

    public function getWorkbench()
    {
        return $this->workbench;
    }

    public function getPropertyTypes(string $prototypeClass, string $property): array
    {
        return [];
    }

    public function getMetaObject(UxonObject $uxon, array $path, MetaObjectInterface $rootObject = null): MetaObjectInterface
    {
        return $rootObject;
    }
    
    public function getPresets(UxonObject $uxon, array $path, string $rootPrototypeClass = null) : array
    {
        $ds = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'exface.Core.UXON_PRESET');
        $ds->getColumns()->addMultiple(['UID','NAME', 'PROTOTYPE__LABEL', 'DESCRIPTION', 'PROTOTYPE', 'UXON' , 'WRAP_PATH', 'WRAP_FLAG']);
        $ds->getFilters()->addConditionFromString('UXON_SCHEMA', '\\' . __CLASS__, ComparatorDataType::EQUALS);
        $ds->getSorters()
        ->addFromString('PROTOTYPE', SortingDirectionsDataType::ASC)
        ->addFromString('NAME', SortingDirectionsDataType::ASC);
        $ds->dataRead();
        
        return $ds->getRows();
    }
    
    public function getUxonType(UxonObject $uxon, array $path, string $rootPrototypeClass = null) : ?string
    {
        return 'string';
    }
    
    public function getPropertiesByAnnotation(string $annotation, $value, string $prototypeClass = null): array
    {
        return [];
    }
}