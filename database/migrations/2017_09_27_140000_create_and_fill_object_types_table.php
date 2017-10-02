<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAndFillObjectTypesTable extends Migration {

    public function up()
    {
        Schema::create('object_types', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->unique();
            $table->timestamps();
        });

        $this->fillObjectTypes();
    }

    public function down()
    {
        Schema::drop('object_types');
    }

    public function fillObjectTypes()
    {
        $changesetTrait = \Anexia\Changeset\Traits\ChangesetTrackable::class;

        /**
         * Detect classes that use ChangesetTrackable trait
         */
        $classes = $this->findAllClasses();
        foreach ($classes as $class) {
//            $traits = class_uses($class);
//            $childClass = $class;

//            while ($parentClass = get_parent_class($childClass)) {
//                $parentTraits = class_uses($parentClass);
//                if (!empty($parentTraits)) {
//                    $traits = array_unique(array_merge($traits, $parentTraits));
//                }
//
//                $childClass = $parentClass;
//            }

//            if (isset($traits[$changesetTrait])) {
//                $objectType = new \Anexia\Changeset\ObjectType();
//                $objectType->name = $class;
//                $objectType->save();
//            }
        }
    }

    public function findAllClasses()
    {
        $projectRoot = base_path();
        $filenames = $this->getFileNames($projectRoot);
        $classes = [];
        foreach ($filenames as $filename) {
            $className = $this->getFullNamespace($filename) . '\\' . $this->getClassName($filename);

            if (class_exists($className)) {
                $classes[] = $className;
            }
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