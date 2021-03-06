<?php declare(strict_types=1);
namespace Phan;

use \Phan\CodeBase\File;
use \Phan\Language\Context;
use \Phan\Language\Element\{Clazz, Element, Method};
use \Phan\Language\FQSEN;

/**
 * A CodeBase represents the known state of a code base
 * we're analyzing.
 *
 * In order to understand internal classes, interfaces,
 * traits and functions, a CodeBase needs to be
 * initialized with the list of those elements begotten
 * before any classes are loaded.
 *
 * # Example
 * ```
 * // Grab these before we define our own classes
 * $internal_class_name_list = get_declared_classes();
 * $internal_interface_name_list = get_declared_interfaces();
 * $internal_trait_name_list = get_declared_traits();
 * $internal_function_name_list = get_defined_functions()['internal'];
 *
 * // Load any required code ...
 *
 * $code_base = new CodeBase(
 *     $internal_class_name_list,
 *     $internal_interface_name_list,
 *     $internal_trait_name_list,
 *     $internal_function_name_list
 *  );
 *
 *  // Do stuff ...
 * ```
 */
class CodeBase {
    use \Phan\CodeBase\ClassMap;
    use \Phan\CodeBase\MethodMap;
    use \Phan\CodeBase\ConstantMap;
    use \Phan\CodeBase\PropertyMap;
    use \Phan\CodeBase\GlobalVariableMap;
    use \Phan\CodeBase\FileMap;

    /**
     * Set a version on this class so that we can
     * error out when reading old versions of serialized
     * files
     */
    const CODE_BASE_VERSION = 2;
    private $code_base_version;

    public function __construct(
        array $internal_class_name_list,
        array $internal_interface_name_list,
        array $internal_trait_name_list,
        array $internal_function_name_list
    ) {
        $this->addClassesByNames($internal_class_name_list);
        $this->addClassesByNames($internal_interface_name_list);
        $this->addClassesByNames($internal_trait_name_list);
        $this->addFunctionsByNames($internal_function_name_list);

        // Set a version on this class so that we can
        // error out when reading old versions of serialized
        // files
        $this->code_base_version =
            CodeBase::CODE_BASE_VERSION;
    }

    /**
     * @param string[] $function_name_list
     * A list of function names to load type information for
     */
    private function addFunctionsByNames(array $function_name_list) {
        foreach ($function_name_list as $i => $function_name) {
            foreach (Method::methodListFromFunctionName($this, $function_name)
                as $method
            ) {
                $this->addMethod($method);
            }
        }
    }

    /**
     * @return int
     * The version number of this code base
     */
    public function getVersion() : int {
        return $this->code_base_version ?? -1;
    }

    /**
     * @return bool
     * True if a serialized code base exists and can be read
     * else false
     */
    public static function storedCodeBaseExists() : bool {
        return (
            Config::get()->stored_state_file_path
            && file_exists(Config::get()->stored_state_file_path)
        );
    }

    public function store() {
        if (!Database::isEnabled()) {
            return;
        }

        $this->storeClassMap();
        $this->storeMethodMap();
        $this->storeConstantMap();
        $this->storePropertyMap();
        $this->storeFileMap();
    }
}
