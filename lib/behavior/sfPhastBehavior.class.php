<?php


class sfPhastBehavior extends SfPropelBehaviorBase
{

    protected $phastTables = [];


    public function parentClass($builder){
        $classname = preg_replace('/^Base/', '', $builder->getClassname());
        if(in_array($classname, $this->phastTables)){
            return 'Phast' . $classname;
        };
    }

    public function modifyDatabase()
    {

        foreach((new sfFinder)->name('*.php')->in(__DIR__ . '/../model') as $path){
            $this->phastTables[] = preg_replace('/^Phast|\.php$/', '', basename($path));
        }

        foreach ($this->getDatabase()->getTables() as $table) {
/*
            if(in_array($table->getPhpName(), $phastTables)){
                $table->setBaseClass('Phast' . $table->getPhpName());
            };

            if(in_array($table->getPhpName() . 'Peer', $phastTables)){
                $table->setBasePeer('Phast' . $table->getPhpName() . 'Peer');
            };
*/

            switch ($table->getName()) {
                case 'holder':
                    foreach ($table->getForeignTableNames() as $rel) {
                        $this
                            ->getDatabase()
                            ->getTable($rel)
                            ->setDescription('~holder');
                    }
                    break;

            }
        }

        parent::modifyDatabase();
    }

    public function preSave()
    {
        if ($this->isDisabled()) {
            return;
        }

        return '';
    }

    public function preInsert()
    {
        if ($this->isDisabled()) {
            return;
        }

        return '';
    }


    public function objectMethods()
    {
        if ($this->isDisabled()) {
            return;
        }

        $script = '';
        $script .= '
                    protected $cached = [];
                    protected function cache($key, Closure $closure){

                        if(array_key_exists($key, $this->cached)){
                            return $this->cached[$key];
                        }

                        return $this->cached[$key] = $closure();
                    }
        ';

        $script .= '
                    protected static $staticCached = [];
                    protected static function staticCache($key, Closure $closure){

                        if(array_key_exists($key, static::$staticCached)){
                            return static::$staticCached[$key];
                        }

                        return static::$staticCached[$key] = $closure();
                    }
        ';


        foreach($this->getTable()->getReferrers() as $rel){
            $relTable = $rel->getTable();
            $primaryKeys = $relTable->getPrimaryKey();

            if(count($primaryKeys) == 2){
                $sourceColumn = $relTable->getColumn($rel->getLocalColumns()[0]);
                $targetColumn = $primaryKeys[$primaryKeys[0]->getPhpName() == $sourceColumn->getPhpName() ? 1 : 0];

                $script .= "
                    public function assign{$relTable->getPhpName()}s(\$values){

                        if(\$values === null){
                            \$values = [];
                        }

                        \$values_ex = [];
                        foreach(\$this->get{$relTable->getPhpName()}s() as \$rel){
                            if(in_array(\$rel->get{$targetColumn->getPhpName()}(), \$values)){
                                \$values_ex[] = \$rel->get{$targetColumn->getPhpName()}();
                            }else{
                                \$rel->delete();
                            }
                        }

                        foreach(array_diff(\$values, \$values_ex) as \$value){
                            \$rel = new {$targetColumn->getTable()->getPhpName()}();
                            \$rel->set{$sourceColumn->getPhpName()}(\$this->getId());
                            \$rel->set{$targetColumn->getPhpName()}(\$value);
                            \$rel->save();
                        }

                        return \$this;

                    }
                ";

            }
        }


        $galleryColumns = [];
        $imageColumns = [];
        $dateColumns = [];
        $i18nColumns = [];

        foreach ($this->getTable()->getColumns() as $column) {

            if (preg_match('/^(\w+)?Ru$/', $column->getPhpName(), $match)) {
                $i18nColumns[$match[1]] = $column;
            }

            if (preg_match('/^(\w+)?GalleryId$/', $column->getPhpName(), $match)) {
                if($this->getTable()->getPhpName() == 'Image') continue;
                $prefix = isset($match[1]) ? $match[1] : '';
                $galleryColumns[$prefix] = $column;
            }

            /** @var $column Column */
            if (preg_match('/^(\w+)?ImageId$/', $column->getPhpName(), $match)) {
                $prefix = isset($match[1]) ? $match[1] : '';
                $imageColumns[] = [
                    'column' => $column,
                    'prefix' => $prefix
                ];
            }else if (preg_match('/^(\w+)?At$/', $column->getPhpName(), $match)) {
                $dateColumns[$match[1]] = $column;
            }
        }

        foreach($i18nColumns as $prefix => $column){
            $script .= "public function get{$prefix}(\$culture = null){return (null !== \$culture && 'ru' == \$culture or null === \$culture && 'ru' == sfContext::getInstance()->getUser()->getCulture()) ? \$this->get{$prefix}Ru() : \$this->get{$prefix}En();}\n";
        }

        foreach($dateColumns as $prefix => $column){
            $script .= "public function get{$prefix}Date(\$mode = 'simple'){return sfPhastUtils::date(\$mode, is_int(\$this->get{$prefix}At()) ? \$this->get{$prefix}At() : strtotime(\$this->get{$prefix}At()));}\n";
        }

        foreach ($galleryColumns as $prefix => $column){
            $method = count($imageColumns) > 1 ? "getImageRelatedBy{$column->getPhpName()}" : 'getGallery';
            $script .= "
                public function get{$prefix}GalleryRels(){
                    if(\$gallery = \$this->{$method}()){
                        return \$gallery->getRels();
                    }
                    return new PropelCollection;
                }
            ";
        }

        foreach ($imageColumns as $column) {
            $prefix = $column['prefix'];
            $column = $column['column'];
            $method = count($imageColumns) > 1 ? "getImageRelatedBy{$column->getPhpName()}" : 'getImage';
            $script .= "public function get{$prefix}ImageObject(){
                            return \$this->{$method}();
                    }";
            $script .= "public function get{$prefix}ImageTag(\$width = null, \$height = null, \$scale = null, \$inflate = null, \$filters = null){
                        if(\$image = \$this->get{$prefix}ImageObject()){
                            return \$image->getTag(\$width, \$height, \$scale, \$inflate, \$filters);
                        }else{
                            return '';
                        }
                    }";
            $script .= "public function get{$prefix}ImageUri(\$width = null, \$height = null, \$scale = null, \$inflate = null, \$filters = null){
                        if(\$image = \$this->get{$prefix}ImageObject()){
                            return \$image->getUri(\$width, \$height, \$scale, \$inflate, \$filters);
                        }else{
                            return '';
                        }
                    }";
            $script .= "public function upload{$prefix}Image(\$request, \$uploadPath){
                        \$upload = new sfPhastUpload(\$request);
                        \$upload->path(sfConfig::get('sf_upload_dir') . \$uploadPath);
                        \$upload->type('web_images');
                        \$upload->save();
                        if (\$this->get{$prefix}ImageId()) {
                            \$this->get{$prefix}ImageObject()->updateFromUpload(\$upload);
                        } else {
                            \$this->set{$prefix}ImageId(Image::createFromUpload(\$upload)->getId());
                        }
                    }";
        }


        return $script;

    }

}
