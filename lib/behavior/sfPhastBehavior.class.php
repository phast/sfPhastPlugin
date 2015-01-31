<?php


class sfPhastBehavior extends SfPropelBehaviorBase
{


    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            switch ($table->getName()) {
                case 'widget':
                    $table->setBaseClass('PhastWidget');
                    break;
                case 'user':
                    $table->setBaseClass('PhastUser');
                    break;
                case 'user_sign':
                    $table->setBaseClass('PhastUserSign');
                    break;
                case 'user_session':
                    $table->setBaseClass('PhastUserSession');
                    break;
                case 'user_group':
                    $table->setBaseClass('PhastUserGroup');
                    break;
                case 'user_group_section':
                    $table->setBaseClass('PhastUserGroupSection');
                    break;
                case 'mailing_schedule':
                    $table->setBaseClass('PhastMailingSchedule');
                    break;
                case 'mailing_broadcast':
                    $table->setBaseClass('PhastMailingBroadcast');
                    break;
                case 'image':
                    $table->setBaseClass('PhastImage');
                    break;
                case 'page':
                    $table->setBaseClass('PhastPage');
                    break;
                case 'gallery_rel':
                    $table->setBaseClass('PhastGalleryRel');
                    break;
                case 'holder':
                    $table->setBaseClass('PhastHolder');
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
        $script .= 'protected $cached = [];';
        $script .= 'protected function cache($key, Closure $closure){

                        if(array_key_exists($key, $this->cached)){
                            return $this->cached[$key];
                        }

                        return $this->cached[$key] = $closure();
                    }
        ';

        $imageColumns = [];
        $dateColumns = [];
        foreach ($this->getTable()->getColumns() as $column) {
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

        foreach($dateColumns as $prefix => $column){
            $script .= "public function get{$prefix}Date(\$mode = 'simple'){return sfPhastUtils::date(\$mode, strtotime(\$this->get{$prefix}At()));}\n";
        }

        foreach ($imageColumns as $column) {
            $prefix = $column['prefix'];
            $column = $column['column'];
            $method = count($imageColumns) > 1 ? "getImageRelatedBy{$column->getPhpName()}" : 'getImage';
            $script .= "public function get{$prefix}ImageObject(){
                            return \$this->{$method}();
                    }";
            $script .= "public function get{$prefix}ImageTag(\$width = null, \$height = null, \$scale = null, \$inflate = null){
                        if(\$image = \$this->get{$prefix}ImageObject()){
                            return \$image->getTag(\$width, \$height, \$scale, \$inflate);
                        }else{
                            return '';
                        }
                    }";
            $script .= "public function get{$prefix}ImageUri(\$width = null, \$height = null, \$scale = null, \$inflate = null){
                        if(\$image = \$this->get{$prefix}ImageObject()){
                            return \$image->getUri(\$width, \$height, \$scale, \$inflate);
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
