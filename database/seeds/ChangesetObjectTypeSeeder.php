<?php

use Illuminate\Database\Seeder;

class ChangesetObjectTypeSeeder extends Seeder {

    public function run()
    {
        $this->fillObjectTypes();
    }

    /**
     * Iterate through all php files in /app and /vendor and
     * store those that use the ChangesetTrackable trait as ObjectTypes
     */
    public function fillObjectTypes()
    {
        $changesetTrait = \Anexia\Changeset\Traits\ChangesetTrackable::class;

        /**
         * Detect classes that use ChangesetTrackable trait
         */
        $classes = $this->findAllClasses();
        foreach ($classes as $class) {
            if (class_exists($class, false)) {
                $traits = class_uses($class);
                $childClass = $class;

                while ($parentClass = get_parent_class($childClass)) {
                    $parentTraits = class_uses($parentClass);
                    if (!empty($parentTraits)) {
                        $traits = array_unique(array_merge($traits, $parentTraits));
                    }

                    $childClass = $parentClass;
                }

                if (isset($traits[$changesetTrait])) {
                    $objectType = new \Anexia\Changeset\ObjectType();
                    $objectType->name = $class;
                    $objectType->save();
                }
            }
        }
    }

    public function findAllClasses()
    {
        $appDir = base_path() . '/app';
        $vendorDir = base_path() . '/vendor';
        $appFilenames = $this->getFileNames($appDir);
        $vendorFilenames = $this->getFileNames($vendorDir);

        $filenames = array_merge($appFilenames, $vendorFilenames);

        $classes = [];
        foreach ($filenames as $filename) {
            $classes[] = $this->getFullNamespace($filename) . '\\' . $this->getClassName($filename);
        }

        return $classes;
    }

    public function getFullNamespace($filename)
    {
        $lines = file($filename);
        $lines = preg_grep('/^namespace /', $lines);
        $namespaceLine = array_shift($lines);
        $match = [];
        preg_match('/^namespace (.*);$/', $namespaceLine, $match);
        $fullNamespace = array_pop($match);

        return $fullNamespace;
    }

    public function getClassName($filename)
    {
        $directoriesAndFilename = explode('/', $filename);
        $filename = array_pop($directoriesAndFilename);
        $nameAndExtension = explode('.', $filename);
        $className = array_shift($nameAndExtension);

        return $className;
    }

    public function getFileNames($dir)
    {
        $finderFiles = \Symfony\Component\Finder\Finder::create()->files()->in($dir)->name('*.php');
        $filenames = [];
        foreach ($finderFiles as $finderFile) {
            $filenames[] = $finderFile->getRealpath();
        }

        return $filenames;
    }
}