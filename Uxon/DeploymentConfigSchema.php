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
 * UXON-schema class for actions.
 * 
 * @see UxonSchema for general information.
 * 
 * @author Andrej Kabachnik
 *
 */
class DeploymentConfigSchema implements UxonSchemaInterface
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
    
    /**
     * 
     * @return string
     */
    public static function getSchemaName() : string
    {
        return 'Deployment Config';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getValidValues()
     */
    public function getValidValues(UxonObject $uxon, array $path, string $search = null, string $rootPrototypeClass = null, MetaObjectInterface $rootObject = null): array
    {
        return [];
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getParentSchema()
     */
    public function getParentSchema(): UxonSchemaInterface
    {
        return $this->parentSchema ?? new UxonSchema($this->getWorkbench(), $this);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getPrototypeClass()
     */
    public function getPrototypeClass(UxonObject $uxon, array $path, string $rootPrototypeClass = null): string
    {
        return '\\' . __CLASS__;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getPropertiesTemplates()
     */
    public function getPropertiesTemplates(string $prototypeClass): array
    {
        return [
            'local_vendors' => '[""]',
            'default_app_config' => '{"app.Alias.config.json": {"": ""}}'
        ];
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getPropertyValueRecursive()
     */
    public function getPropertyValueRecursive(UxonObject $uxon, array $path, string $propertyName, string $rootValue = '')
    {
        return null;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getProperties()
     */
    public function getProperties(string $prototypeClass): array
    {
        return [
            'local_vendors',
            'default_app_config'
        ];
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::hasParentSchema()
     */
    public function hasParentSchema()
    {
        return $this->parentSchema !== null;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\WorkbenchDependantInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->workbench;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getPropertyTypes()
     */
    public function getPropertyTypes(string $prototypeClass, string $property): array
    {
        return [];
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getMetaObject()
     */
    public function getMetaObject(UxonObject $uxon, array $path, MetaObjectInterface $rootObject = null): MetaObjectInterface
    {
        return $rootObject;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getPresets()
     */
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
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getUxonType()
     */
    public function getUxonType(UxonObject $uxon, array $path, string $rootPrototypeClass = null) : ?string
    {
        return 'string';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\UxonSchemaInterface::getPropertiesByAnnotation()
     */
    public function getPropertiesByAnnotation(string $annotation, $value, string $prototypeClass = null): array
    {
        return [];
    }
}